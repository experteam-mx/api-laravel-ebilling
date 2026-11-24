<?php

namespace Experteam\ApiLaravelEBilling\Saga\Commands;

use Experteam\ApiLaravelEBilling\Saga\Services\FiscalEventConsumer;
use Illuminate\Console\Command;

class ConsumeFiscalEvents extends Command
{
    protected $signature = 'ebilling:saga:consume {--once} {--consumer=} {--block=}';

    protected $description = 'Consume operation.requested events from the configured Redis Stream.';

    public function handle(FiscalEventConsumer $consumer): int
    {
        $consumer->consume(
            once: (bool) $this->option('once'),
            consumerName: $this->option('consumer') ?: null,
            blockMs: $this->option('block') !== null ? (int) $this->option('block') : null,
        );

        return self::SUCCESS;
    }
}
