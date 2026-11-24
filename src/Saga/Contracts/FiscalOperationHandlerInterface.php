<?php

namespace Experteam\ApiLaravelEBilling\Saga\Contracts;

interface FiscalOperationHandlerInterface
{
    /** Return the result that API A will receive in operation.completed. */
    public function issueInvoice(array $payload, string $sagaId): array;

    /** The country application decides whether to cancel directly or issue a credit note. */
    public function cancelDocument(array $payload, string $sagaId): array;
}
