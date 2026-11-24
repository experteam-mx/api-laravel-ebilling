<?php

namespace Experteam\ApiLaravelEBilling\Saga\Models;

use Illuminate\Database\Eloquent\Model;

class FiscalOperation extends Model
{
    protected $table = 'ebilling_fiscal_operations';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id', 'event_id', 'saga_id', 'operation', 'country_id', 'correlation_id',
        'status', 'request_payload', 'response_payload', 'error_message', 'processed_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'processed_at' => 'datetime',
    ];
}
