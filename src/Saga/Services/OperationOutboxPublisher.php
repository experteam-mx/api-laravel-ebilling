<?php

namespace Experteam\ApiLaravelEBilling\Saga\Services;

use Experteam\ApiLaravelEBilling\Saga\Enums\OutboxStatus;
use Experteam\ApiLaravelEBilling\Saga\Models\OperationOutbox;
use Illuminate\Support\Facades\Log;
use Throwable;

class OperationOutboxPublisher
{
    public function __construct(private readonly RedisStreamEventBus $eventBus)
    {
    }

    public function publishBatch(): int
    {
        $stream = (string) config('ebilling-saga.results_stream');
        $batchSize = (int) config('ebilling-saga.outbox_batch_size', 100);
        $published = 0;

        $events = OperationOutbox::query()
            ->where('status', OutboxStatus::PENDING)
            ->orderBy('created_at')
            ->limit($batchSize)
            ->get();

        foreach ($events as $event) {
            try {
                $this->eventBus->publish($stream, $event->payload);

                $event->update([
                    'status' => OutboxStatus::PUBLISHED,
                    'published_at' => now(),
                    'attempts' => $event->attempts + 1,
                    'last_error' => null,
                ]);
                $published++;
            } catch (Throwable $exception) {
                $event->update([
                    'attempts' => $event->attempts + 1,
                    'last_error' => $exception->getMessage(),
                ]);

                Log::error('Unable to publish eBilling operation result.', [
                    'outbox_id' => $event->id,
                    'exception' => $exception,
                ]);
            }
        }

        return $published;
    }
}
