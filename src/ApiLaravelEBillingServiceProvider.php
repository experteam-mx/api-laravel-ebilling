<?php

namespace Experteam\ApiLaravelEBilling;

use Experteam\ApiLaravelEBilling\Utils\DocumentRequestManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

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
        
        // Register routes
        $this->registerRoutes();
    }
    
    /**
     * Register the package routes.
     *
     * @return void
     */
    protected function registerRoutes(): void
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/routes.php');
        });
    }
    
    /**
     * Get the route group configuration array.
     *
     * @return array
     */
    protected function routeConfiguration(): array
    {
        return [
            'prefix' => 'api',
            'middleware' => ['api'],
        ];
    }
}