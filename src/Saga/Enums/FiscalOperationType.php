<?php

namespace Experteam\ApiLaravelEBilling\Saga\Enums;

enum FiscalOperationType: string
{
    case ISSUE_INVOICE = 'ISSUE_INVOICE';
    case CANCEL_DOCUMENT = 'CANCEL_DOCUMENT';
}
