<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SiteConfigController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebDavController;
use App\Http\Controllers\DocumentVerificationController;
use Illuminate\Support\Facades\Storage;

// --- Routes Publiques ---
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::match(['get', 'put', 'options', 'head', 'propfind'], '/webdav/{id}', [WebDavController::class, 'handle'])
    ->name('webdav.handle');

// Routes de vérification publique
Route::get('/verify/{code}', [DocumentVerificationController::class, 'show'])->name('verification.show');
Route::post('/verify/{code}', [DocumentVerificationController::class, 'verify'])->name('verification.verify');

    Route::get('/test-minio', function () {
    try {
        Storage::disk('s3')->put('test.txt', 'Connexion Groupe Bama OK');
        return "Fichier envoyé avec succès ! Vérifie ton dashboard MinIO.";
    } catch (\Exception $e) {
        return "Erreur MinIO : " . $e->getMessage();
    }
});

// Public home page (redirige les utilisateurs connectés vers /dashboard)
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
})->name('home');

// --- Routes Protégées (nécessitent d'être connecté) ---
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Gestion des documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/advanced-search', [DocumentController::class, 'advancedSearch'])->name('documents.advanced-search');
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::post('/documents/create', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::put('/documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::post('/documents/{document}/archive', [DocumentController::class, 'archive'])->name('documents.archive');
    Route::get('/documents/{document}/versions', [DocumentController::class, 'versions'])->name('documents.versions');
    Route::post('/documents/{document}/versions/{version}/restore', [DocumentController::class, 'restoreVersion'])->name('documents.versions.restore');
    Route::get('/documents/{document}/audit', [DocumentController::class, 'audit'])->name('documents.audit');
    Route::get('/documents/{document}/edit-online', [DocumentController::class, 'editOnline'])->name('documents.edit-online');
    Route::post('/documents/{document}/save-online', [DocumentController::class, 'saveOnline'])->name('documents.save-online');
    Route::get('/documents/{document}/stream', [DocumentController::class, 'stream'])->name('documents.stream');
    
    // Catégories
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
    Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Users
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create')->middleware('role:admin');
    Route::post('/users', [UserController::class, 'store'])->name('users.store')->middleware('role:admin');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit')->middleware('role:admin');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update')->middleware('role:admin');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy')->middleware('role:admin');
    Route::get('/users/{user}/roles', [UserController::class, 'editRoles'])->name('users.roles.edit')->middleware('role:admin');
    Route::put('/users/{user}/roles', [UserController::class, 'updateRoles'])->name('users.roles.update')->middleware('role:admin');

    // Settings
    Route::get('/settings', [SiteConfigController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SiteConfigController::class, 'update'])->name('settings.update');
    
    // Route de déconnexion
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});