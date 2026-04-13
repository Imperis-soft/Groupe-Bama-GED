<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// API pour la recherche avancée de documents
Route::middleware('auth')->group(function () {
    Route::get('/documents/search', [DocumentController::class, 'apiSearch']);
});

// API pour récupérer un document (utilise auth web car appelé depuis interface web)
Route::middleware('auth')->group(function () {
    Route::get('/documents/{document}', [DocumentController::class, 'apiShow']);
});
