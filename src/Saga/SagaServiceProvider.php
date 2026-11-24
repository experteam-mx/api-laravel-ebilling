<?php

namespace Experteam\ApiLaravelEBilling\Saga;

use Experteam\ApiLaravelEBilling\Saga\Commands\ConsumeFiscalEvents;
use Experteam\ApiLaravelEBilling\Saga\Commands\PublishOutbox;
use Experteam\ApiLaravelEBilling\Saga\Contracts\FiscalOperationHandlerInterface;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class SagaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $root = dirname(__DIR__, 2);
        $this->mergeConfigFrom($root . '/config/ebilling-saga.php', 'ebilling-saga');

        $this->app->bind(FiscalOperationHandlerInterface::class, function ($app) {
            $handler = config('ebilling-saga.handler');

            if (! is_string($handler) || $handler === '') {
                throw new RuntimeException('Configure EBILLING_FISCAL_HANDLER.');
            }

            return $app->make($handler);
        });
    }

    public function boot(): void
    {
        $root = dirname(__DIR__, 2);

        $this->loadMigrationsFrom($root . '/database/migrations/saga');
        $this->publishes([
            $root . '/config/ebilling-saga.php' => config_path('ebilling-saga.php'),
        ], 'ebilling-saga-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ConsumeFiscalEvents::class,
                PublishOutbox::class,
            ]);
        }
    }
}
