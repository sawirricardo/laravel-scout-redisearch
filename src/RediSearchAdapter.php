<?php

namespace Sawirricardo\Laravel\Scout\RediSearch;

use Ehann\RedisRaw\AbstractRedisRawClient;

class RediSearchAdapter extends AbstractRedisRawClient
{
    public function __construct($redis)
    {
        $this->redis = $redis;
    }

    public function multi(bool $usePipeline = false)
    {
        return $usePipeline ? $this->redis->pipeline() : $this->redis->multi();
    }

    public function rawCommand(string $command, array $arguments)
    {
        $arguments = $this->prepareRawCommandArguments($command, $arguments);
        $rawResult = null;
        try {
            $rawResult = $this->redis->executeRaw($arguments);
        } catch (\Exception $e) {
            $this->validateRawCommandResults($e, $command, $arguments);
        }

        return $this->normalizeRawCommandResult($rawResult);
    }
}
