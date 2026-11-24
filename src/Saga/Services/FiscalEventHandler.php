<?php

namespace Experteam\ApiLaravelEBilling\Saga\Services;

use Experteam\ApiLaravelEBilling\Saga\Contracts\FiscalOperationHandlerInterface;
use Experteam\ApiLaravelEBilling\Saga\DTO\FiscalEvent;
use Experteam\ApiLaravelEBilling\Saga\Enums\FiscalOperationStatus;
use Experteam\ApiLaravelEBilling\Saga\Enums\FiscalOperationType;
use Experteam\ApiLaravelEBilling\Saga\Enums\OutboxStatus;
use Experteam\ApiLaravelEBilling\Saga\Exceptions\FiscalOperationRejectedException;
use Experteam\ApiLaravelEBilling\Saga\Models\FiscalOperation;
use Experteam\ApiLaravelEBilling\Saga\Models\OperationOutbox;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FiscalEventHandler
{
    public function __construct(
        private readonly FiscalOperationHandlerInterface $handler,
    ) {
    }

    public function handle(FiscalEvent $event): void
    {
        $operation = FiscalOperation::query()->firstOrCreate(
            ['saga_id' => $event->sagaId],
            [
                'id' => (string) Str::uuid(),
                'event_id' => $event->eventId,
                'operation' => $event->operation->value,
                'country_id' => $event->countryId,
                'correlation_id' => $event->correlationId,
                'status' => FiscalOperationStatus::PROCESSING,
                'request_payload' => $event->payload,
            ]
        );

        // A repeated delivery of a finished saga must not execute it again.
        if (FiscalOperationStatus::isFinished($operation->status)) {
            return;
        }

        try {
            $result = match ($event->operation) {
                FiscalOperationType::ISSUE_INVOICE => $this->handler->issueInvoice(
                    $event->payload,
                    $event->sagaId
                ),
                FiscalOperationType::CANCEL_DOCUMENT => $this->handler->cancelDocument(
                    $event->payload,
                    $event->sagaId
                ),
            };

            $eventType = 'operation.completed';
            $status = FiscalOperationStatus::COMPLETED;
            $errorMessage = null;
        } catch (FiscalOperationRejectedException $exception) {
            $result = [
                'error_code' => $exception->errorCode,
                'message' => $exception->getMessage(),
            ];
            $eventType = 'operation.failed';
            $status = FiscalOperationStatus::FAILED;
            $errorMessage = $exception->getMessage();
        }

        $this->saveResult($operation, $event, $eventType, $result, $status, $errorMessage);
    }

    private function saveResult(
        FiscalOperation $operation,
        FiscalEvent $request,
        string $eventType,
        array $payload,
        string $status,
        ?string $errorMessage = null,
    ): void {
        DB::transaction(function () use ($operation, $request, $eventType, $payload, $status, $errorMessage) {
            $response = [
                'event_id' => (string) Str::uuid(),
                'saga_id' => $request->sagaId,
                'event_type' => $eventType,
                'event_version' => 1,
                'correlation_id' => $request->correlationId,
                'occurred_at' => now()->toISOString(),
                'payload' => $payload,
            ];

            $operation->update([
                'status' => $status,
                'response_payload' => $payload,
                'error_message' => $errorMessage,
                'processed_at' => now(),
            ]);

            OperationOutbox::query()->create([
                'id' => (string) Str::uuid(),
                'saga_id' => $request->sagaId,
                'payload' => $response,
                'status' => OutboxStatus::PENDING,
                'attempts' => 0,
            ]);
        });
    }
}
