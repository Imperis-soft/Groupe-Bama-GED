<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;
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
use App\Http\Controllers\DocumentArchiveController;
use App\Http\Controllers\SubscriptionStatusController;
use App\Http\Controllers\DemoRequestController;
use App\Http\Controllers\SuperAdmin;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SavedFilterController;
use App\Http\Controllers\ApprovalTemplateController;
use App\Http\Controllers\DocumentPreviewController;
use App\Http\Controllers\SignatureRequestController;

// --- Routes Publiques ---
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::match(['get', 'put', 'options', 'head', 'propfind'], '/webdav/{id}', [WebDavController::class, 'handle'])
    ->name('webdav.handle')
    ->middleware('throttle:60,1');

// Routes de vérification publique
Route::get('/verify', [DocumentVerificationController::class, 'lookup'])->name('verification.lookup')->middleware('throttle:20,1');
Route::get('/verify/{code}', [DocumentVerificationController::class, 'show'])->name('verification.show')->middleware('throttle:60,1');
Route::post('/verify/{code}', [DocumentVerificationController::class, 'verify'])->name('verification.verify')->middleware('throttle:20,1');

// Accès document par lien partagé (public)
Route::get('/share/{token}', [DocumentShareController::class, 'accessByToken'])->name('documents.share.access');

// Mot de passe oublié (public)
Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.forgot');
Route::post('/forgot-password', [PasswordResetController::class, 'sendReset'])->name('password.send')->middleware('throttle:3,1');
Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update')->middleware('throttle:5,1');

// Public home page (redirige les utilisateurs connectés vers /dashboard)
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->isSuperAdmin() ? 'super.dashboard' : 'dashboard');
    }

    // Offres SaaS affichées sur la page d'accueil (gérées depuis l'espace super admin)
    $plans = \App\Models\Plan::where('is_active', true)->orderBy('sort_order')->get();

    return view('welcome', compact('plans'));
})->name('home');

// Demande de démo / devis depuis la page d'accueil
Route::post('/demo-request', [DemoRequestController::class, 'store'])->name('demo-request.store')->middleware('throttle:5,1');

// --- Abonnement expiré / entreprise suspendue (connecté, hors entreprise) ---
Route::middleware('auth')->group(function () {
    Route::get('/subscription/expired', [SubscriptionStatusController::class, 'expired'])->name('subscription.expired');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// --- Espace super administrateur (Imperis) ---
Route::middleware(['auth', 'super_admin'])->prefix('super-admin')->name('super.')->group(function () {
    Route::get('/', [SuperAdmin\DashboardController::class, 'index'])->name('dashboard');

    // Entreprises
    Route::get('/organizations', [SuperAdmin\OrganizationController::class, 'index'])->name('organizations.index');
    Route::get('/organizations/create', [SuperAdmin\OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('/organizations', [SuperAdmin\OrganizationController::class, 'store'])->name('organizations.store');
    Route::get('/organizations/{organization}', [SuperAdmin\OrganizationController::class, 'show'])->name('organizations.show');
    Route::get('/organizations/{organization}/edit', [SuperAdmin\OrganizationController::class, 'edit'])->name('organizations.edit');
    Route::put('/organizations/{organization}', [SuperAdmin\OrganizationController::class, 'update'])->name('organizations.update');
    Route::post('/organizations/{organization}/suspend', [SuperAdmin\OrganizationController::class, 'suspend'])->name('organizations.suspend');
    Route::post('/organizations/{organization}/activate', [SuperAdmin\OrganizationController::class, 'activate'])->name('organizations.activate');
    Route::delete('/organizations/{organization}', [SuperAdmin\OrganizationController::class, 'destroy'])->name('organizations.destroy');
    Route::post('/organizations/{organization}/storage/check', [SuperAdmin\OrganizationController::class, 'checkStorage'])->name('organizations.storage.check');
    Route::post('/organizations/{organization}/enter', [SuperAdmin\OrganizationController::class, 'enter'])->name('organizations.enter');
    Route::post('/organizations/{organization}/users', [SuperAdmin\OrganizationController::class, 'storeUser'])->name('organizations.users.store');
    Route::post('/organizations/{organization}/exports', [SuperAdmin\OrganizationController::class, 'export'])->name('organizations.exports.store');
    Route::get('/organizations/{organization}/exports/{export}/download', [SuperAdmin\OrganizationController::class, 'downloadExport'])->name('organizations.exports.download');

    // Journal système : santé de la plateforme et problèmes détectés
    Route::get('/system', [SuperAdmin\SystemController::class, 'index'])->name('system.index');
    Route::post('/system/resolve-all', [SuperAdmin\SystemController::class, 'resolveAll'])->name('system.resolve-all');
    Route::post('/system/{event}/resolve', [SuperAdmin\SystemController::class, 'resolve'])->name('system.resolve');

    // Services communs à toutes les entreprises
    Route::get('/departments', [SuperAdmin\DepartmentController::class, 'index'])->name('departments.index');
    Route::post('/departments', [SuperAdmin\DepartmentController::class, 'store'])->name('departments.store');
    Route::put('/departments/{department}', [SuperAdmin\DepartmentController::class, 'update'])->name('departments.update');
    Route::delete('/departments/{department}', [SuperAdmin\DepartmentController::class, 'destroy'])->name('departments.destroy');
    Route::get('/organizations/{organization}/settings', [SuperAdmin\OrganizationSettingsController::class, 'edit'])->name('organizations.settings');
    Route::post('/organizations/{organization}/settings', [SuperAdmin\OrganizationSettingsController::class, 'update'])->name('organizations.settings.update');

    // Abonnements
    Route::get('/subscriptions', [SuperAdmin\SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('/organizations/{organization}/subscriptions/create', [SuperAdmin\SubscriptionController::class, 'create'])->name('subscriptions.create');
    Route::post('/organizations/{organization}/subscriptions', [SuperAdmin\SubscriptionController::class, 'store'])->name('subscriptions.store');
    Route::post('/subscriptions/{subscription}/cancel', [SuperAdmin\SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');

    // Offres
    Route::get('/plans', [SuperAdmin\PlanController::class, 'index'])->name('plans.index');
    Route::get('/plans/create', [SuperAdmin\PlanController::class, 'create'])->name('plans.create');
    Route::post('/plans', [SuperAdmin\PlanController::class, 'store'])->name('plans.store');
    Route::get('/plans/{plan}/edit', [SuperAdmin\PlanController::class, 'edit'])->name('plans.edit');
    Route::put('/plans/{plan}', [SuperAdmin\PlanController::class, 'update'])->name('plans.update');
    Route::delete('/plans/{plan}', [SuperAdmin\PlanController::class, 'destroy'])->name('plans.destroy');

    // Utilisateurs de toutes les entreprises
    Route::get('/users', [SuperAdmin\UserController::class, 'index'])->name('users.index');
    Route::post('/users/{user}/toggle-active', [SuperAdmin\UserController::class, 'toggleActive'])->name('users.toggle-active');
    Route::put('/users/{user}/password', [SuperAdmin\UserController::class, 'updatePassword'])->name('users.password');

    // Demandes de démo reçues depuis la page d'accueil
    Route::get('/demo-requests', [SuperAdmin\DemoRequestController::class, 'index'])->name('demo-requests.index');
    Route::put('/demo-requests/{demoRequest}', [SuperAdmin\DemoRequestController::class, 'update'])->name('demo-requests.update');

    // Journal et paramètres de la plateforme
    Route::get('/activity', [SuperAdmin\ActivityController::class, 'index'])->name('activity.index');
    Route::get('/settings', [SuperAdmin\SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SuperAdmin\SettingsController::class, 'update'])->name('settings.update');

    // Quitter l'entreprise dans laquelle le super admin intervient
    Route::post('/leave', [SuperAdmin\OrganizationController::class, 'leave'])->name('leave');
});

// --- Routes Protégées (connecté + entreprise active avec abonnement en cours) ---
Route::middleware(['auth', 'tenant'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Boîte « À traiter »
    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox.index');

    // Profil
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::put('/profile/absence', [ProfileController::class, 'updateAbsence'])->name('profile.absence');
    Route::get('/profile/activity', [ProfileController::class, 'activity'])->name('profile.activity');
    Route::get('/profile/sessions', [ProfileController::class, 'sessions'])->name('profile.sessions');
    Route::delete('/profile/sessions/{sessionId}', [ProfileController::class, 'revokeSession'])->name('profile.sessions.revoke');

    // Gestion des documents
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    // Modèles de circuit d'approbation (administrateurs)
    Route::middleware('role:admin')->group(function () {
        Route::get('/approval-templates', [ApprovalTemplateController::class, 'index'])->name('approval-templates.index');
        Route::get('/approval-templates/create', [ApprovalTemplateController::class, 'create'])->name('approval-templates.create');
        Route::post('/approval-templates', [ApprovalTemplateController::class, 'store'])->name('approval-templates.store');
        Route::get('/approval-templates/{approvalTemplate}/edit', [ApprovalTemplateController::class, 'edit'])->name('approval-templates.edit');
        Route::put('/approval-templates/{approvalTemplate}', [ApprovalTemplateController::class, 'update'])->name('approval-templates.update');
        Route::delete('/approval-templates/{approvalTemplate}', [ApprovalTemplateController::class, 'destroy'])->name('approval-templates.destroy');
    });

    // Vues enregistrées de la liste des documents
    Route::post('/saved-filters', [SavedFilterController::class, 'store'])->name('saved-filters.store');
    Route::delete('/saved-filters/{savedFilter}', [SavedFilterController::class, 'destroy'])->name('saved-filters.destroy');
    Route::get('/documents/advanced-search', [DocumentController::class, 'advancedSearch'])->name('documents.advanced-search');
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::post('/documents/create', [DocumentController::class, 'store'])->name('documents.store')->middleware('role:admin,editor');
    Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::put('/documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::post('/documents/{document}/archive', [DocumentController::class, 'archive'])->name('documents.archive');
    Route::post('/documents/{document}/unarchive', [DocumentController::class, 'unarchive'])->name('documents.unarchive');
    Route::get('/documents/{document}/archival-copy', [DocumentController::class, 'archivalCopy'])->name('documents.archival-copy');
    Route::get('/documents/{document}/official-copy', [DocumentController::class, 'officialCopy'])->name('documents.official-copy');
    Route::get('/documents/{document}/versions', [DocumentController::class, 'versions'])->name('documents.versions');
    Route::post('/documents/{document}/versions/{version}/restore', [DocumentController::class, 'restoreVersion'])->name('documents.versions.restore');
    Route::get('/documents/{document}/audit', [DocumentController::class, 'audit'])->name('documents.audit');
    Route::get('/documents/{document}/stream', [DocumentController::class, 'stream'])->name('documents.stream');
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    Route::get('/documents/{document}/preview/pdf', [DocumentPreviewController::class, 'pdf'])->name('documents.preview.pdf');
    Route::get('/documents/{document}/preview/archive', [DocumentPreviewController::class, 'archive'])->name('documents.preview.archive');
    Route::post('/documents/{document}/upload-version', [DocumentController::class, 'uploadVersion'])->name('documents.upload-version');
    
    // Catégories
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create')->middleware('role:admin');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store')->middleware('role:admin');
    Route::get('/categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
    Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit')->middleware('role:admin');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update')->middleware('role:admin');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy')->middleware('role:admin');

    // Dossiers (catégories créées depuis l'explorateur ; droits vérifiés dans le contrôleur)
    Route::middleware('role:admin,editor')->group(function () {
        Route::post('/folders', [\App\Http\Controllers\FolderController::class, 'store'])->name('folders.store');
        Route::put('/folders/{category}', [\App\Http\Controllers\FolderController::class, 'update'])->name('folders.update');
        Route::delete('/folders/{category}', [\App\Http\Controllers\FolderController::class, 'destroy'])->name('folders.destroy');
    });

    // Users
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create')->middleware('role:admin');
    Route::post('/users', [UserController::class, 'store'])->name('users.store')->middleware('role:admin');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit')->middleware('role:admin');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update')->middleware('role:admin');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy')->middleware('role:admin');
    Route::get('/users/{user}/roles', [UserController::class, 'editRoles'])->name('users.roles.edit')->middleware('role:admin');
    Route::put('/users/{user}/roles', [UserController::class, 'updateRoles'])->name('users.roles.update')->middleware('role:admin');

    // Fin de conservation : décisions et procès-verbaux d'élimination (admin seulement)
    Route::middleware('role:admin')->group(function () {
        Route::get('/retention', [\App\Http\Controllers\RetentionController::class, 'index'])->name('retention.index');
        Route::post('/retention/eliminate', [\App\Http\Controllers\RetentionController::class, 'eliminate'])->name('retention.eliminate');
        Route::post('/retention/extend', [\App\Http\Controllers\RetentionController::class, 'extend'])->name('retention.extend');
        Route::post('/retention/keep', [\App\Http\Controllers\RetentionController::class, 'keep'])->name('retention.keep');
        Route::get('/retention/records/{record}/pdf', [\App\Http\Controllers\RetentionController::class, 'pdf'])->name('retention.records.pdf');
        Route::post('/retention/verify', [\App\Http\Controllers\RetentionController::class, 'verify'])->name('retention.verify');
        Route::post('/retention/exports', [\App\Http\Controllers\RetentionController::class, 'export'])->name('retention.exports.store');
        Route::get('/retention/exports/{export}/download', [\App\Http\Controllers\RetentionController::class, 'downloadExport'])->name('retention.exports.download');
    });

    // Services : liste commune gérée par le super admin ; l'admin de l'entreprise règle membres, responsable et droits
    Route::middleware('role:admin')->group(function () {
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
        Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
    });


    // API interne (appelée en AJAX depuis les vues)
    Route::get('/api/documents/search', [SearchController::class, 'documents'])->name('documents.api.search');
    Route::get('/api/search/quick', [SearchController::class, 'quick'])->name('search.quick');
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
    Route::post('/documents/{document}/approval/{step}/remind', [ApprovalController::class, 'remind'])->name('documents.approval.remind');
    Route::post('/documents/{document}/workflow/withdraw', [ApprovalController::class, 'withdraw'])->name('documents.workflow.withdraw');

    // Signatures
    Route::get('/documents/{document}/signatures', [DocumentSignatureController::class, 'index'])->name('documents.signatures');
    Route::post('/documents/{document}/signatures', [DocumentSignatureController::class, 'store'])->name('documents.signatures.store');
    Route::get('/documents/{document}/signatures/{signature}/verify', [DocumentSignatureController::class, 'verify'])->name('documents.signatures.verify');

    // Demandes de signature
    Route::post('/documents/{document}/signature-requests', [SignatureRequestController::class, 'store'])->name('documents.signature-requests.store');
    Route::delete('/documents/{document}/signature-requests/{signatureRequest}', [SignatureRequestController::class, 'cancel'])->name('documents.signature-requests.cancel');
    Route::post('/documents/{document}/signature-requests/{signatureRequest}/decline', [SignatureRequestController::class, 'decline'])->name('documents.signature-requests.decline');

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
    Route::post('/documents/bulk', [BulkDocumentController::class, 'action'])->name('documents.bulk')->middleware('role:admin,editor');

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

    // Archivage avancé
    Route::post('/documents/{document}/legal-hold', [DocumentArchiveController::class, 'enableLegalHold'])->name('documents.legal-hold.enable')->middleware('role:admin');
    Route::delete('/documents/{document}/legal-hold', [DocumentArchiveController::class, 'disableLegalHold'])->name('documents.legal-hold.disable')->middleware('role:admin');
    Route::get('/documents/{document}/compare-versions', [DocumentArchiveController::class, 'compareVersions'])->name('documents.compare-versions');
    Route::get('/documents/{document}/export-archive', [DocumentArchiveController::class, 'exportArchive'])->name('documents.export-archive');
});