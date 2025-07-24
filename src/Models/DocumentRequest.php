<?php

namespace Experteam\ApiLaravelEBilling\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Models/DocumentRequest
 *
 * @property int $id
 * @property int $document_id
 * @property string $document_status_code
 * @property string $service
 * @property array $times
 * @property string $observation
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @method static Builder|DocumentRequest whereDocumentId($value)
 * @method static Builder|DocumentRequest whereService($value)
 * @method static Builder|DocumentRequest whereType($value)
 * @method static Builder|DocumentRequest whereObservation($value)
 */
class DocumentRequest extends Model
{
    protected $fillable = [
        'document_id',
        'document_status_code',
        'service',
        'times',
        'observation',
    ];

    protected $casts = [
        'times' => 'array',
    ];
}