<?php

namespace Experteam\ApiLaravelEBilling\Saga\Exceptions;

use RuntimeException;

class FiscalOperationRejectedException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode = 'FISCAL_OPERATION_REJECTED'
    ) {
        parent::__construct($message);
    }
}
