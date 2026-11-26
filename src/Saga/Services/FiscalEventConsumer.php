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
        $blockMs ??= (int) config('ebilling-saga.consumer.block_ms', 5000);

        if ($stream === '') {
            throw new RuntimeException('Configure EBILLING_REQUESTS_STREAM and EBILLING_CONSUMER_GROUP.');
        }

        do {

            $messages = $this->eventBus->readGroup(
                $stream,
                (int) config('ebilling-saga.consumer.count', 10),
                $blockMs,
            );

            foreach ($messages as $message) {
                try {
                    $event = FiscalEvent::fromJson($message['data']);
                    $this->eventHandler->handle($event);
                } catch (InvalidArgumentException $exception) {
                    Log::error('Invalid eBilling event discarded.', [
                        'message_id' => $message['id'],
                        'error' => $exception->getMessage(),
                    ]);
                } catch (Throwable $exception) {
                    Log::error('Unable to process eBilling event.', [
                        'message_id' => $message['id'],
                        'exception' => $exception,
                    ]);
                }
            }
        } while (! $once);
    }
}
