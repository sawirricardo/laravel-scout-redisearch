<?php

namespace Sawirricardo\Laravel\Scout\Engines;

use Ehann\RediSearch\Index;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Laravel\Scout\Jobs\RemoveableScoutCollection;

class RediSearchEngine extends Engine
{
    /**
     * @var \Ehann\RediSearch\Redis\RedisClient|\Ehann\RedisRaw\RedisRawClientInterface
     */
    protected $rediSearch;

    /**
     * @var bool
     */
    protected $softDelete;

    public function __construct($client, $softDelete)
    {
        $this->rediSearch = $client;
        $this->softDelete = $softDelete;
    }

    public function __call($name, $arguments)
    {
        return $this->rediSearch->$name(...$arguments);
    }

    public function update($models)
    {
        if ($models->isEmpty()) {
            return;
        }
        $model = $models->first();

        $index = $this->createIndex($model->searchableAs());

        foreach ($model->searchableSchema() as $name => $schema) {
            $indexFunc = data_get([
                \Ehann\RediSearch\Fields\TextField::class => 'addTextField',
                \Ehann\RediSearch\Fields\GeoField::class => 'addGeoField',
                \Ehann\RediSearch\Fields\NumericField::class => 'addNumericField',
            ], $schema);

            $index->$indexFunc($name);
        }

        $models->each(function ($model) use ($index) {
            $document = $index->makeDocument($model->getScoutKey());

            foreach ($model->toSearchableArray() as $name => $value) {
                if ($name !== $model->getScoutKeyName()) {
                    $value = $value ?? '';
                    $document->$name->setValue($value);
                }
            }

            try {
                $index->add($document);
            } catch (\Throwable $e) {
                if ($e->getMessage() == 'Document already exists') {
                    $index->replace($document);
                }
            }
        });
    }

    public function delete($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        $index = $this->createIndex($models->first()->searchableAs());

        $keys = $models instanceof RemoveableScoutCollection
            ? $models->pluck($models->first()->getScoutKeyName())
            : $models->map->getScoutKey();

        foreach ($keys as $key) {
            $index->delete($key);
        }
    }

    public function search(Builder $builder)
    {
        $index = $this->createIndex(
            $builder->index ?: $builder->model->searchableAs()
        );

        if ($builder->callback) {
            return call_user_func($builder->callback, $index)
                ->search($builder->query);
        }

        return $index->search($builder->query);
    }

    public function paginate(Builder $builder, $perPage, $page)
    {
        $index = $this->createIndex($builder->index ?: $builder->model->searchableAs());

        if ($builder->callback) {
            return collect(
                call_user_func($builder->callback, $index)
                    ->limit($page, $perPage)
                    ->search($builder->query)
            );
        }

        return collect($index
            ->limit($page, $perPage)
            ->search($builder->query));
    }

    public function mapIds($results)
    {
        return collect($results->getDocuments())
            ->pluck('id')
            ->values();
    }

    public function map(Builder $builder, $results, $model)
    {
        $results = collect($results);

        if ($results->first()->isEmpty()) {
            return collect();
        }
        $documents = $results->last();
        $keys = collect($documents)
            ->pluck($model->getScoutKeyName())
            ->values()
            ->all();
        $models = $model
            ->whereIn($model->getQualifiedKeyName(), $keys)
            ->get()
            ->keyBy($model->getScoutKeyName());

        return collect($documents)
            ->map(function ($hit) use ($models) {
                $key = $hit->id;
                if (isset($models[$key])) {
                    return $models[$key];
                }
            })->filter();
    }

    public function getTotalCount($results)
    {
        return $results->getCount();
    }

    public function flush($model)
    {
        $this->createIndex($model->searchableAs())->drop();
    }

    public function deleteIndex($name)
    {
        $this->createIndex($name)->drop();
    }

    public function lazyMap(Builder $builder, $results, $model)
    {
    }

    public function createIndex($name, array $options = [])
    {
        return new Index($this->rediSearch, $name);
    }

    protected function usesSoftDelete($model)
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model));
    }
}
