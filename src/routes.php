<?php

use Experteam\ApiLaravelEBilling\Controllers\DocumentRequestController;
use Experteam\ApiLaravelEBilling\Controllers\UrlConfigController;
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
Route::get('/url-config', [UrlConfigController::class, 'index'])
    ->name('url-config');
Route::put('/url-config/{index}', [UrlConfigController::class, 'update'])
    ->name('url-config.update');
