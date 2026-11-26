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
        $group = (string) config('ebilling-saga.consumer.group');
        $blockMs ??= (int) config('ebilling-saga.consumer.block_ms', 5000);

        if ($stream === '' || $group === '') {
            throw new RuntimeException('Configure EBILLING_REQUESTS_STREAM and EBILLING_CONSUMER_GROUP.');
        }

        $hostname = gethostname();
        $consumerName = $consumerName ?: (($hostname !== false ? $hostname : 'consumer') . '-' . getmypid());

        do {
            $messages = $this->eventBus->readGroup(
                $stream,
                $group,
                $consumerName,
                (int) config('ebilling-saga.consumer.count', 10),
                $blockMs,
                (int) config('ebilling-saga.consumer.pending_idle_ms', 60000),
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
                    $this->eventBus->acknowledge($stream, $group, $message['id']);
                    continue;
                } catch (Throwable $exception) {
                    Log::error('Unable to process eBilling event.', [
                        'message_id' => $message['id'],
                        'exception' => $exception,
                    ]);
                    continue;
                }

                $this->eventBus->acknowledge($stream, $group, $message['id']);
            }
        } while (! $once);
    }
}
