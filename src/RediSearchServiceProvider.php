<?php

namespace Sawirricardo\Laravel\Scout\RediSearch;

use Laravel\Scout\EngineManager;
use Sawirricardo\Laravel\Scout\Engines\RediSearchEngine;
use Sawirricardo\Laravel\Scout\RediSearch\Commands\RediSearchCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class RediSearchServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-scout-redisearch')
            ->hasCommand(RediSearchCommand::class);
    }

    public function packageRegistered()
    {
        $this->app->singleton(RedisRawClientInterface::class, function () {
            return new RediSearchAdapter($this->app->make('redis'));
        });
    }

    public function packageBooted()
    {
        resolve(EngineManager::class)->extend('redisearch', function () {
            return new RediSearchEngine($this->app->make(RedisRawClientInterface::class), config('scout.soft_delete'));
        });
    }
}
