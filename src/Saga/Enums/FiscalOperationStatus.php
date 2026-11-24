<?php

namespace Experteam\ApiLaravelEBilling\Saga\Enums;

final class FiscalOperationStatus
{
    public const PROCESSING = 'PROCESSING';
    public const COMPLETED = 'COMPLETED';
    public const FAILED = 'FAILED';

    public static function isFinished(string $status): bool
    {
        return in_array($status, [self::COMPLETED, self::FAILED], true);
    }
}
