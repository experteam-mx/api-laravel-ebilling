<?php

namespace Experteam\ApiLaravelEBilling\Saga\Models;

use Illuminate\Database\Eloquent\Model;

class OperationOutbox extends Model
{
    protected $table = 'ebilling_operation_outbox';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id', 'saga_id', 'payload', 'status', 'attempts', 'published_at', 'last_error',
    ];

    protected $casts = [
        'payload' => 'array',
        'published_at' => 'datetime',
    ];
}
