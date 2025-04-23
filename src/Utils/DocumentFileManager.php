<?php

namespace Experteam\ApiLaravelEBilling\Utils;

use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Experteam\ApiLaravelEBilling\Models\DocumentFile;

class DocumentFileManager
{
    public static function store(string $type, string $service, int $documentId, mixed $payload)
    {
        $payload = self::validatePayload($payload);
        $timestamp = Carbon::now()->format('YmdHis');
        $extension = self::getExtensionFromContent($payload);
        $fileName = "{$type}_{$documentId}_{$service}_$timestamp.$extension";
        $path = "invoice/" . Carbon::now()->toDateString();

        Storage::put("$path/$fileName", $payload);

        $documentFile = DocumentFile::where('document_id', $documentId)
            ->where('type', $type)
            ->where('service', $service)
            ->first();

        if ($documentFile) {
            Storage::delete("$documentFile->path/$documentFile->file_name");

            $documentFile->update([
                'file_name' => $fileName,
                'path' => $path,
                'times' => [...$documentFile->times, Carbon::now()],
            ]);
            return $documentFile;
        }

        return DocumentFile::create([
            'document_id' => $documentId,
            'file_name' => $fileName,
            'path' => $path,
            'type' => $type,
            'service' => $service,
            'times' => [Carbon::now()],
        ]);
    }

    public static function getExtensionFromContent(string $content): string
    {
        $trimmed = trim($content);

        return match (true) {
            Str::startsWith($trimmed, '{'),
            Str::startsWith($trimmed, '[') => 'json',
            Str::startsWith($trimmed, '<') => 'xml',
            default => 'txt',
        };
    }

    public static function validatePayload(mixed $payload): string
    {
        if (is_array($payload)) {
            $payload = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else if ($payload instanceof \SimpleXMLElement) {
            $payload = $payload->asXML();
        } else if (!is_string($payload)) {
            throw new \InvalidArgumentException('The payload should be string, array or XML.');
        }

        return $payload;
    }
}