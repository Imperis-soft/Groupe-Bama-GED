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
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DocumentShareController;
use App\Http\Controllers\DocumentCommentController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\DocumentSignatureController;
use App\Http\Controllers\DocumentLockController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\BulkDocumentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DocumentFavoriteController;
use App\Http\Controllers\TrashController;
use App\Http\Controllers\PasswordResetController;
use Illuminate\Support\Facades\Storage;

// --- Routes Publiques ---
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::match(['get', 'put', 'options', 'head', 'propfind'], '/webdav/{id}', [WebDavController::class, 'handle'])
    ->name('webdav.handle');

// Routes de vérification publique
Route::get('/verify/{code}', [DocumentVerificationController::class, 'show'])->name('verification.show');
Route::post('/verify/{code}', [DocumentVerificationController::class, 'verify'])->name('verification.verify');

// Accès document par lien partagé (public)
Route::get('/share/{token}', [DocumentShareController::class, 'accessByToken'])->name('documents.share.access');

// Mot de passe oublié (public)
Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.forgot');
Route::post('/forgot-password', [PasswordResetController::class, 'sendReset'])->name('password.send');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');

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

    // Profil
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::get('/profile/activity', [ProfileController::class, 'activity'])->name('profile.activity');
    Route::get('/profile/sessions', [ProfileController::class, 'sessions'])->name('profile.sessions');
    Route::delete('/profile/sessions/{sessionId}', [ProfileController::class, 'revokeSession'])->name('profile.sessions.revoke');

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
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    Route::post('/documents/{document}/upload-version', [DocumentController::class, 'uploadVersion'])->name('documents.upload-version');
    
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

    // API interne (appelée en AJAX depuis les vues)
    Route::get('/api/documents/search', [DocumentController::class, 'apiSearch'])->name('documents.api.search');
    Route::get('/api/documents/{document}', [DocumentController::class, 'apiShow'])->name('documents.api.show');

    // Partages
    Route::get('/documents/{document}/shares', [DocumentShareController::class, 'index'])->name('documents.shares');
    Route::post('/documents/{document}/shares', [DocumentShareController::class, 'store'])->name('documents.shares.store');
    Route::delete('/documents/{document}/shares/{share}', [DocumentShareController::class, 'revoke'])->name('documents.shares.revoke');

    // Commentaires
    Route::post('/documents/{document}/comments', [DocumentCommentController::class, 'store'])->name('documents.comments.store');
    Route::put('/documents/{document}/comments/{comment}', [DocumentCommentController::class, 'update'])->name('documents.comments.update');
    Route::delete('/documents/{document}/comments/{comment}', [DocumentCommentController::class, 'destroy'])->name('documents.comments.destroy');

    // Workflow d'approbation
    Route::get('/documents/{document}/approval', [ApprovalController::class, 'index'])->name('documents.approval');
    Route::post('/documents/{document}/approval/setup', [ApprovalController::class, 'setup'])->name('documents.approval.setup');
    Route::post('/documents/{document}/approval/{step}/approve', [ApprovalController::class, 'approve'])->name('documents.approval.approve');
    Route::post('/documents/{document}/approval/{step}/reject', [ApprovalController::class, 'reject'])->name('documents.approval.reject');

    // Signatures
    Route::get('/documents/{document}/signatures', [DocumentSignatureController::class, 'index'])->name('documents.signatures');
    Route::post('/documents/{document}/signatures', [DocumentSignatureController::class, 'store'])->name('documents.signatures.store');
    Route::get('/documents/{document}/signatures/{signature}/verify', [DocumentSignatureController::class, 'verify'])->name('documents.signatures.verify');

    // Verrous
    Route::post('/documents/{document}/lock', [DocumentLockController::class, 'acquire'])->name('documents.lock.acquire');
    Route::delete('/documents/{document}/lock', [DocumentLockController::class, 'release'])->name('documents.lock.release');
    Route::get('/documents/{document}/lock/status', [DocumentLockController::class, 'status'])->name('documents.lock.status');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::delete('/notifications/read', [NotificationController::class, 'destroyRead'])->name('notifications.destroy-read');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.count');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Opérations en masse
    Route::post('/documents/bulk', [BulkDocumentController::class, 'action'])->name('documents.bulk');

    // Rapports (admin seulement)
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index')->middleware('role:admin');
    Route::get('/reports/export-csv', [ReportController::class, 'exportCsv'])->name('reports.export-csv')->middleware('role:admin');
    Route::get('/reports/export-audit', [ReportController::class, 'exportAuditCsv'])->name('reports.export-audit')->middleware('role:admin');

    // Favoris
    Route::post('/documents/{document}/favorite', [DocumentFavoriteController::class, 'toggle'])->name('documents.favorite');
    Route::get('/favorites', [DocumentFavoriteController::class, 'index'])->name('documents.favorites');

    // Corbeille
    Route::get('/trash', [TrashController::class, 'index'])->name('trash.index');
    Route::post('/trash/{id}/restore', [TrashController::class, 'restore'])->name('trash.restore');
    Route::delete('/trash/{id}/force', [TrashController::class, 'forceDelete'])->name('trash.force-delete')->middleware('role:admin');
    Route::delete('/trash/empty', [TrashController::class, 'emptyTrash'])->name('trash.empty')->middleware('role:admin');
});