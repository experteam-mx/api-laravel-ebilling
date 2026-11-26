<?php

namespace Experteam\ApiLaravelEBilling\Saga\Services;

use Illuminate\Support\Facades\Redis;
use RuntimeException;

/**
 * Redis Streams adapter for the Laravel PhpRedis clients.
 */
class RedisStreamEventBus
{
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

    /** @return array<int, array{id:string,data:string}> */
    public function readGroup(
        string $stream,
        int $count = 10,
        int $blockMs = 5000
    ): array {
        $client = $this->client();
        $stream = $this->streamKey($stream);

        $response = $client->xRead(
            [$stream => '0-0'],
            $count,
            $blockMs
        );

        return $this->normalizeReadResponse($response);
    }

    private function client()
    {
        return Redis::connection(config('ebilling-saga.redis_connection', 'default'))->client();
    }

    private function streamKey(string $stream): string
    {
        return config('ebilling-saga.redis_prefix', '') . $stream;
    }

    /** @return array<int, array{id:string,data:string}> */
    private function normalizeReadResponse(mixed $response): array
    {
        if (! is_array($response) || $response === []) {
            return [];
        }

        $entries = [];
        foreach ($response as $streamKey => $messages) {
            foreach ($messages as $messageId => $fields) {
                $this->client()->xDel($streamKey, [$messageId]);
                $data = $fields['message'] ?? null;

                if (!$data) {
                    continue;
                }

                $entries[] = [
                    'id' => (string) $messageId,
                    'data' => (string) $data,
                ];
            }
        }

        return $entries;
    }
}
