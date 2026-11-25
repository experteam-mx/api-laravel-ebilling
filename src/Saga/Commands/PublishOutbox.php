<?php

namespace Experteam\ApiLaravelEBilling\Saga\Commands;

use Experteam\ApiLaravelEBilling\Saga\Services\OperationOutboxPublisher;
use Illuminate\Console\Command;

class PublishOutbox extends Command
{
    protected $signature = 'ebilling:saga:publish-outbox {--max-cycles=1000}';

    protected $description = 'Publish operation.completed and operation.failed events.';

    public function handle(OperationOutboxPublisher $publisher): int
    {
        $maxCycles = (int)$this->option('max-cycles');
        $cycles = 0;

        do {
            $cycles++;
            $this->info('Published ' . $publisher->publishBatch() . ' result event(s).');
            sleep(1);
        } while ($cycles < $maxCycles);

        return self::SUCCESS;
    }
}
