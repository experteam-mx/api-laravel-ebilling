<?php

use Experteam\ApiLaravelEBilling\Controllers\DocumentRequestController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for the package.
|
*/

// Document Request routes
Route::get('/document-requests/{documentId}', [DocumentRequestController::class, 'getByDocumentId'])
    ->name('document-requests');