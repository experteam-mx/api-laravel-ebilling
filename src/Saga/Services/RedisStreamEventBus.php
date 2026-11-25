<?php

namespace Experteam\ApiLaravelEBilling\Saga\Services;

use Illuminate\Support\Facades\Redis;
use RuntimeException;
use Throwable;

/**
 * Redis Streams adapter for the Laravel PhpRedis and Predis clients.
 * Redis server 6.2+ is required for XAUTOCLAIM.
 */
class RedisStreamEventBus
{
    public function publish(string $stream, array $event): string
    {
        $json = json_encode($event, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $client = $this->client();
        $stream = $this->streamKey($stream, $client);

        if ($this->isPhpRedis($client)) {
            $id = $client->xAdd($stream, '*', ['data' => $json]);
        } else {
            $id = $this->executeRaw(['XADD', $stream, '*', 'data', $json]);
        }

        if (! is_string($id) && ! is_numeric($id)) {
            throw new RuntimeException('Redis XADD did not return a stream entry ID.');
        }

        return (string) $id;
    }

    public function ensureConsumerGroup(string $stream, string $group): void
    {
        try {
            $client = $this->client();
            $stream = $this->streamKey($stream, $client);

            if ($this->isPhpRedis($client)) {
                $client->xGroup('CREATE', $stream, $group, '0', true);
            } else {
                $this->executeRaw(['XGROUP', 'CREATE', $stream, $group, '0', 'MKSTREAM']);
            }
        } catch (Throwable $exception) {
            if (stripos($exception->getMessage(), 'BUSYGROUP') === false) {
                throw $exception;
            }
        }
    }

    /** @return array<int, array{id:string,data:string}> */
    public function readGroup(
        string $stream,
        string $group,
        string $consumer,
        int $count = 10,
        int $blockMs = 5000
    ): array {
        $client = $this->client();
        $stream = $this->streamKey($stream, $client);

        if ($this->isPhpRedis($client)) {
            $response = $client->xReadGroup(
                $group,
                $consumer,
                [$stream => '>'],
                $count,
                $blockMs
            );
        } else {
            $arguments = ['XREADGROUP', 'GROUP', $group, $consumer, 'COUNT', (string) $count];
            if ($blockMs > 0) {
                $arguments[] = 'BLOCK';
                $arguments[] = (string) $blockMs;
            }
            $arguments[] = 'STREAMS';
            $arguments[] = $stream;
            $arguments[] = '>';
            $response = $this->executeRaw($arguments);
        }

        return $this->normalizeReadResponse($response);
    }

    /** @return array<int, array{id:string,data:string}> */
    public function claimPending(
        string $stream,
        string $group,
        string $consumer,
        int $minimumIdleMs,
        int $count = 10
    ): array {
        $client = $this->client();
        $stream = $this->streamKey($stream, $client);

        if ($this->isPhpRedis($client)) {
            $response = $client->xAutoClaim(
                $stream,
                $group,
                $consumer,
                $minimumIdleMs,
                '0-0',
                $count
            );
        } else {
            $response = $this->executeRaw([
                'XAUTOCLAIM',
                $stream,
                $group,
                $consumer,
                (string) $minimumIdleMs,
                '0-0',
                'COUNT',
                (string) $count,
            ]);
        }

        if (! is_array($response) || ! isset($response[1]) || ! is_array($response[1])) {
            return [];
        }

        return $this->normalizeEntries($response[1]);
    }

    public function acknowledge(string $stream, string $group, string $messageId): void
    {
        $client = $this->client();
        $stream = $this->streamKey($stream, $client);

        if ($this->isPhpRedis($client)) {
            $client->xAck($stream, $group, [$messageId]);
            $client->xDel($stream, [$messageId]);
            return;
        }

        $this->executeRaw(['XACK', $stream, $group, $messageId]);
        $this->executeRaw(['XDEL', $stream, $messageId]);
    }

    private function connection()
    {
        return Redis::connection(config('ebilling-saga.redis_connection', 'default'));
    }

    private function client()
    {
        return $this->connection()->client();
    }

    private function isPhpRedis(object $client): bool
    {
        return (class_exists('Redis') && $client instanceof \Redis)
            || (class_exists('RedisCluster') && $client instanceof \RedisCluster);
    }

    private function streamKey(string $stream, object $client): string
    {
        // PhpRedis applies Laravel's configured key prefix internally. Predis
        // executeRaw bypasses its prefix processor, so configure the prefix here.
        if ($this->isPhpRedis($client)) {
            return $stream;
        }

        return (string) config('ebilling-saga.redis_prefix', '') . $stream;
    }

    private function executeRaw(array $arguments): mixed
    {
        $client = $this->client();

        if (! method_exists($client, 'executeRaw')) {
            throw new RuntimeException('The configured Redis client must be PhpRedis or Predis with executeRaw support.');
        }

        return $client->executeRaw(array_map(static fn ($value) => (string) $value, $arguments));
    }

    /** @return array<int, array{id:string,data:string}> */
    private function normalizeReadResponse(mixed $response): array
    {
        if (! is_array($response) || $response === []) {
            return [];
        }

        // PhpRedis returns [stream => [message-id => fields]]. Predis raw RESP
        // returns [[stream, [[message-id, fields], ...]]].
        $entries = [];

        foreach ($response as $streamKey => $messages) {
            if (is_int($streamKey) && is_array($messages) && count($messages) >= 2) {
                $messages = $messages[1];
            }

            if (is_array($messages)) {
                $entries = array_merge($entries, $this->normalizeEntries($messages));
            }
        }

        return $entries;
    }

    /** @return array<int, array{id:string,data:string}> */
    private function normalizeEntries(array $messages): array
    {
        $normalized = [];

        foreach ($messages as $messageId => $fields) {
            // Redis raw RESP can encode an entry as [id, [field, value, ...]].
            if (is_int($messageId) && is_array($fields) && count($fields) >= 2) {
                $messageId = $fields[0];
                $fields = $fields[1];
            }

            if (! is_array($fields)) {
                continue;
            }

            if (array_is_list($fields)) {
                $mapped = [];
                for ($index = 0; $index + 1 < count($fields); $index += 2) {
                    $mapped[(string) $fields[$index]] = $fields[$index + 1];
                }
                $fields = $mapped;
            }

            if (! isset($fields['data'])) {
                continue;
            }

            $normalized[] = [
                'id' => (string) $messageId,
                'data' => (string) $fields['data'],
            ];
        }

        return $normalized;
    }
}
