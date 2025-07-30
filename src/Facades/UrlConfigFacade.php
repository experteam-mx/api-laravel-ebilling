<?php

namespace Experteam\ApiLaravelEBilling\Facades;

use Illuminate\Support\Facades\Facade;

class UrlConfigFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'url_config';
    }
}