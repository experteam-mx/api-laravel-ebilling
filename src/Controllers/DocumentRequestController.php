<?php

namespace Experteam\ApiLaravelEBilling\Controllers;

use Experteam\ApiLaravelEBilling\Utils\DocumentRequestManager;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class DocumentRequestController extends Controller
{
    /**
     * Get all document requests for a specific document ID
     */
    public function getByDocumentId(int $documentId): Response|ResponseFactory
    {
        return jsend_success(['requests' => DocumentRequestManager::getByDocumentId($documentId)]);
    }
}