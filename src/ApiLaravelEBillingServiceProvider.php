<?php

namespace Experteam\ApiLaravelEBilling;

use Experteam\ApiLaravelEBilling\Utils\DocumentFileManager;
use Experteam\ApiLaravelEBilling\Utils\DocumentRequestManager;
use Illuminate\Support\ServiceProvider;

class ApiLaravelEBillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind('DocumentRequestManager', function () {
            return new DocumentRequestManager();
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}