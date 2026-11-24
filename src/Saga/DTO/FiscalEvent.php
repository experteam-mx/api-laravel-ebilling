<?php

namespace Experteam\ApiLaravelEBilling\Saga\DTO;

use Experteam\ApiLaravelEBilling\Saga\Enums\FiscalOperationType;
use InvalidArgumentException;
use JsonException;

final class FiscalEvent
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $sagaId,
        public readonly string $eventType,
        public readonly FiscalOperationType $operation,
        public readonly int $eventVersion,
        public readonly int $countryId,
        public readonly string $correlationId,
        public readonly string $occurredAt,
        public readonly array $payload,
    ) {
    }

    /** @throws InvalidArgumentException|JsonException */
    public static function fromJson(string $json): self
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('The event body is not valid JSON.', 0, $exception);
        }

        if (! is_array($data)) {
            throw new InvalidArgumentException('The event body must be a JSON object.');
        }

        foreach ([
            'event_id', 'saga_id', 'event_type', 'operation', 'event_version',
            'country_id', 'correlation_id', 'occurred_at', 'payload',
        ] as $field) {
            if (! array_key_exists($field, $data)) {
                throw new InvalidArgumentException("Missing required event field: {$field}");
            }
        }

        if ($data['event_type'] !== 'operation.requested') {
            throw new InvalidArgumentException('Unsupported event type.');
        }

        try {
            $operation = FiscalOperationType::from((string) $data['operation']);
        } catch (\ValueError) {
            throw new InvalidArgumentException('Unsupported operation.');
        }

        if (! is_array($data['payload'])) {
            throw new InvalidArgumentException('The payload must be a JSON object.');
        }

        return new self(
            eventId: (string) $data['event_id'],
            sagaId: (string) $data['saga_id'],
            eventType: (string) $data['event_type'],
            operation: $operation,
            eventVersion: (int) $data['event_version'],
            countryId: (int) $data['country_id'],
            correlationId: (string) $data['correlation_id'],
            occurredAt: (string) $data['occurred_at'],
            payload: $data['payload'],
        );
    }
}
