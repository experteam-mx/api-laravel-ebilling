<?php

namespace Experteam\ApiLaravelEBilling;

use Experteam\ApiLaravelEBilling\Utils\DocumentFileManager;
use Illuminate\Support\ServiceProvider;

class ApiLaravelEBillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind('DocumentFileManager', function () {
            return new DocumentFileManager();
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}