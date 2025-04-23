<?php

namespace Experteam\ApiLaravelEBilling\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Models/DocumentFile
 *
 * @property int $id
 * @property int $document_id
 * @property string $file_name
 * @property string $path
 * @property string $type
 * @property string $service
 * @property array $times
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @method static Builder|DocumentFile whereDocumentId($value)
 * @method static Builder|DocumentFile whereService($value)
 * @method static Builder|DocumentFile whereType($value)
 */
class DocumentFile extends Model
{
    protected $fillable = [
        'document_id',
        'file_name',
        'path',
        'type',
        'service',
        'times'
    ];

    protected $casts = [
        'times' => 'array',
    ];
}