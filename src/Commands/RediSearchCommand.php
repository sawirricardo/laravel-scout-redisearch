<?php

namespace Sawirricardo\Laravel\Scout\RediSearch\Commands;

use Illuminate\Console\Command;

class RediSearchCommand extends Command
{
    public $signature = 'laravel-scout-redisearch';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
