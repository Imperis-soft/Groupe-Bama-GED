<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// La recherche et la fiche document (/api/documents/...) sont déclarées dans routes/web.php,
// derrière les middlewares auth + tenant (session web et cloisonnement par entreprise).
