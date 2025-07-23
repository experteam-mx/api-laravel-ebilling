<?php

namespace Experteam\ApiLaravelEBilling\Utils;

use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Experteam\ApiLaravelEBilling\Models\DocumentRequest;

class DocumentRequestManager
{
    public static function store(int $documentId, string $service, ?string $observation = null)
    {
        $documentRequest = DocumentRequest::where('document_id', $documentId)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($documentRequest?->service == $service && $documentRequest?->observation == $observation) {
            $documentRequest->update([
                'times' => [...$documentRequest->times, Carbon::now()],
            ]);
            return $documentRequest;
        }

        return DocumentRequest::create([
            'document_id' => $documentId,
            'service' => $service,
            'times' => [Carbon::now()],
            'observation' => $observation,
        ]);
    }
}