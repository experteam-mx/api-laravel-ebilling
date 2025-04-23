<?php

namespace Experteam\ApiLaravelEBilling\Facades;

use Experteam\ApiLaravelEBilling\Utils\DocumentFileManager;
use Illuminate\Support\Facades\Facade;

class DocumentFileManagerFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DocumentFileManager::class;
    }
}