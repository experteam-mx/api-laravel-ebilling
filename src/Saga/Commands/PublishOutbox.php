<?php

namespace Experteam\ApiLaravelEBilling\Saga\Commands;

use Experteam\ApiLaravelEBilling\Saga\Services\OperationOutboxPublisher;
use Illuminate\Console\Command;

class PublishOutbox extends Command
{
    protected $signature = 'ebilling:saga:publish-outbox';

    protected $description = 'Publish operation.completed and operation.failed events.';

    public function handle(OperationOutboxPublisher $publisher): int
    {
        $this->info('Published ' . $publisher->publishBatch() . ' result event(s).');

        return self::SUCCESS;
    }
}
