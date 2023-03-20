<?php

namespace Sawirricardo\Laravel\Scout\RediSearch\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Sawirricardo\Laravel\Scout\RediSearch\RediSearch
 */
class RediSearch extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Sawirricardo\Laravel\Scout\RediSearch\RediSearch::class;
    }
}
