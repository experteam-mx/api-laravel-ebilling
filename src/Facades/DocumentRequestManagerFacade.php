<?php

namespace Experteam\ApiLaravelEBilling\Facades;

use Experteam\ApiLaravelEBilling\Utils\DocumentRequestManager;
use Illuminate\Support\Facades\Facade;

class DocumentRequestManagerFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DocumentRequestManager::class;
    }
}