<?php

namespace Experteam\ApiLaravelEBilling\Saga\Services;

use Illuminate\Support\Facades\Redis;
use RuntimeException;
use Throwable;

/**
 * Redis Streams adapter for the Laravel PhpRedis clients.
 */
class RedisStreamEventBus
{
    /** @var array<string, true> */
    private array $consumerGroups = [];

    /** @var array<string, string> */
    private array $pendingCursors = [];

    /**
     * @throws \JsonException
     */
    public function publish(string $stream, array $event): string
    {
        $json = json_encode($event, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $client = $this->client();
        $stream = $this->streamKey($stream);

        $id = $client->xAdd($stream, '*', ['data' => $json]);

        if (! is_string($id) && ! is_numeric($id)) {
            throw new RuntimeException('Redis XADD did not return a stream entry ID.');
        }

        return (string) $id;
    }

    /** @return array<int, array{id:string,data:string}>
     * @throws Throwable
     */
    public function readGroup(
        string $stream,
        string $group,
        string $consumer,
        int $count = 10,
        int $blockMs = 5000,
        int $pendingIdleMs = 60000,
    ): array {
        $client = $this->client();
        $stream = $this->streamKey($stream);

        $this->ensureConsumerGroup($client, $stream, $group);
        $groupKey = $stream . "\0" . $group;

        $claimedResponse = $client->xAutoClaim(
            $stream,
            $group,
            $consumer,
            $pendingIdleMs,
            $this->pendingCursors[$groupKey] ?? '0-0',
            $count,
        );
        if (is_array($claimedResponse) && isset($claimedResponse[0])) {
            $this->pendingCursors[$groupKey] = (string) $claimedResponse[0];
        }

        $claimedMessages = is_array($claimedResponse) && isset($claimedResponse[1])
            ? $this->normalizeReadResponse([$stream => $claimedResponse[1]])
            : [];

        if ($claimedMessages !== []) {
            return array_slice($claimedMessages, 0, $count);
        }

        $newMessagesResponse = $client->xReadGroup(
            $group,
            $consumer,
            [$stream => '>'],
            $count,
            $blockMs,
        );

        return $this->normalizeReadResponse($newMessagesResponse);
    }

    public function acknowledge(string $stream, string $group, string $messageId): void
    {
        $client = $this->client();
        $stream = $this->streamKey($stream);
        $acknowledged = $client->xAck(
            $stream,
            $group,
            [$messageId],
        );

        if ((int) $acknowledged !== 1) {
            throw new RuntimeException("Redis did not acknowledge stream entry {$messageId}.");
        }

        $deleted = $client->xDel($stream, [$messageId]);
        if ((int) $deleted !== 1) {
            throw new RuntimeException("Redis did not delete stream entry {$messageId}.");
        }
    }

    private function client()
    {
        return Redis::connection(config('ebilling-saga.redis_connection', 'default'))->client();
    }

    private function streamKey(string $stream): string
    {
        return config('ebilling-saga.redis_prefix', '') . $stream;
    }

    private function ensureConsumerGroup($client, string $stream, string $group): void
    {
        $key = $stream . "\0" . $group;

        if (isset($this->consumerGroups[$key])) {
            return;
        }

        try {
            $client->xGroup('CREATE', $stream, $group, '0', true);
        } catch (Throwable $exception) {
            if (! str_contains($exception->getMessage(), 'BUSYGROUP')) {
                throw $exception;
            }
        }

        $this->consumerGroups[$key] = true;
    }

    /** @return array<int, array{id:string,data:string}> */
    private function normalizeReadResponse(mixed $response): array
    {
        if (! is_array($response) || $response === []) {
            return [];
        }

        if (array_is_list($response)) {
            $streams = [];
            foreach ($response as $streamEntry) {
                if (is_array($streamEntry) && isset($streamEntry[0], $streamEntry[1])) {
                    $streams[$streamEntry[0]] = $streamEntry[1];
                }
            }
            $response = $streams;
        }

        $entries = [];
        foreach ($response as $streamKey => $messages) {
            if (! is_array($messages)) {
                continue;
            }

            if (array_is_list($messages)) {
                $normalizedMessages = [];
                foreach ($messages as $message) {
                    if (is_array($message) && isset($message[0], $message[1])) {
                        $normalizedMessages[$message[0]] = $message[1];
                    }
                }
                $messages = $normalizedMessages;
            }

            foreach ($messages as $messageId => $fields) {
                if (! is_array($fields)) {
                    $fields = [];
                } elseif (array_is_list($fields)) {
                    $normalizedFields = [];
                    for ($index = 0; $index + 1 < count($fields); $index += 2) {
                        $normalizedFields[$fields[$index]] = $fields[$index + 1];
                    }
                    $fields = $normalizedFields;
                }

                $entries[] = [
                    'id' => (string) $messageId,
                    'data' => (string) ($fields['data'] ?? $fields['message'] ?? ''),
                ];
            }
        }

        return $entries;
    }
}
