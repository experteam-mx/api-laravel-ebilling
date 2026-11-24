<?php

namespace Experteam\ApiLaravelEBilling\Saga\Services;

use Experteam\ApiLaravelEBilling\Saga\DTO\FiscalEvent;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class FiscalEventConsumer
{
    public function __construct(
        private readonly RedisStreamEventBus $eventBus,
        private readonly FiscalEventHandler $eventHandler,
    ) {
    }

    public function consume(bool $once = false, ?string $consumerName = null, ?int $blockMs = null): void
    {
        $stream = (string) config('ebilling-saga.request_stream');
        $group = (string) config('ebilling-saga.consumer_group');
        $consumerName ??= gethostname() . '-' . getmypid();
        $blockMs ??= (int) config('ebilling-saga.consumer.block_ms', 5000);

        if ($stream === '' || $group === '') {
            throw new RuntimeException('Configure EBILLING_REQUESTS_STREAM and EBILLING_CONSUMER_GROUP.');
        }

        $this->eventBus->ensureConsumerGroup($stream, $group);

        do {
            $messages = $this->eventBus->claimPending(
                $stream,
                $group,
                $consumerName,
                (int) config('ebilling-saga.consumer.pending_idle_ms', 60000),
                (int) config('ebilling-saga.consumer.count', 10),
            );

            if ($messages === []) {
                $messages = $this->eventBus->readGroup(
                    $stream,
                    $group,
                    $consumerName,
                    (int) config('ebilling-saga.consumer.count', 10),
                    $blockMs,
                );
            }

            foreach ($messages as $message) {
                try {
                    $event = FiscalEvent::fromJson($message['data']);
                    $this->eventHandler->handle($event);
                    $this->eventBus->acknowledge($stream, $group, $message['id']);
                } catch (InvalidArgumentException $exception) {
                    Log::error('Invalid eBilling event discarded.', [
                        'message_id' => $message['id'],
                        'error' => $exception->getMessage(),
                    ]);
                    $this->eventBus->acknowledge($stream, $group, $message['id']);
                } catch (Throwable $exception) {
                    // Leave the message pending; Redis Streams will make it available
                    // for another consumer through XAUTOCLAIM.
                    Log::error('Unable to process eBilling event.', [
                        'message_id' => $message['id'],
                        'exception' => $exception,
                    ]);
                }
            }
        } while (! $once);
    }
}
