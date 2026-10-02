<?php

namespace App\Http\Controllers;

use App\Support\Tenant;
use App\Support\FileType;
use App\Models\Document;
use App\Models\DocumentVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\DocumentArchivalService;
use Exception;
use Illuminate\Support\Facades\Log;

class DocumentController extends Controller
{
    /**
     * Construit et retourne l'URL publique MinIO pour un chemin donné.
     */
    private function buildMinioUrl(string $path): string
    {
        return Tenant::organization()->fileUrl($path);
    }

    /**
     * Disque MinIO (bucket) de l'entreprise courante.
     */
    private function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Tenant::organization()->disk();
    }

    /**
     * Génère une référence de document unique (PREFIXE-XXXXXX, préfixe propre à chaque entreprise).
     */
    private function generateReference(): string
    {
        $prefix = Tenant::organization()?->reference_prefix ?: 'DOC';
        do {
            $ref = $prefix . '-' . strtoupper(Str::random(6));
        } while (Document::withoutGlobalScopes()->where('reference', $ref)->exists());

        return $ref;
    }

    /**
     * Extension réelle du fichier stocké (docx par défaut).
     */
    private function fileExtension(Document $document): string
    {
        return strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION)) ?: 'docx';
    }

    /**
     * Dossier MinIO des documents de l'entreprise courante.
     */
    private function storageDir(): string
    {
        return Tenant::organization()->documentsDir();
    }

    /**
     * Règles de validation d'un fichier importé (formats du registre FileType).
     */
    private function fileRules(bool $required): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'max:' . config('ged.max_upload_kb', 153600),
            function (string $attribute, $value, \Closure $fail) {
                if ($value instanceof \Illuminate\Http\UploadedFile && ($error = FileType::uploadError($value))) {
                    $fail($error);
                }
            },
        ];
    }

    /**
     * Message d'erreur si le fichier dépasse le quota de stockage de l'offre, sinon null.
     */
    private function quotaError(int $bytes): ?string
    {
        $organization = Tenant::organization();
        if ($organization->canStore($bytes)) {
            return null;
        }
        $maxMb = $organization->currentPlan()?->max_storage_mb;
        return "Espace de stockage de votre offre atteint ({$maxMb} Mo). Contactez " . config('saas.vendor_name') . ' pour passer à une offre supérieure.';
    }

    // Afficher la liste des documents avec options de recherche et de filtrage
    public function index(Request $request)
    {
        $user    = auth()->user();
        $filters = \App\Support\DocumentFilters::fromRequest($request, $user);
        // Arborescence avec le nombre de documents visibles (panneau « Dossiers »)
        $tree = new \App\Support\CategoryTree();
        $filters->tree = $tree;

        $query = Document::visibleTo()->with(['category', 'creator']);
        $filters->apply($query);
        $documents = $query->paginate($filters->get('per_page'))->withQueryString();

        // Données des filtres
        $categories = $tree->flat();
        $creators = \App\Models\User::whereIn('id', Document::visibleTo()->select('creator_id'))
            ->orderBy('full_name')->get(['id', 'full_name']);
        $tagSuggestions = Document::visibleTo()->whereNotNull('tags')->latest()->limit(300)->pluck('tags')
            ->flatten()->filter()->map(fn ($t) => trim($t))->countBy()->sortDesc()->keys()->take(30)->values();

        $savedFilters = \App\Models\SavedFilter::availableTo($user)->orderBy('name')->get();
        $activeView = $savedFilters->firstWhere('id', (int) $request->query('view'));
        // Une vue modifiée depuis son ouverture n'est plus « active »
        if ($activeView && $activeView->params != $filters->filterParams()) {
            $activeView = null;
        }

        $scopes = collect(\App\Support\DocumentFilters::SCOPES)
            ->when($user->hasRole('admin') || !$user->viewableCategoryIds(), fn ($c) => $c->except('department'));

        $searchTerms = $filters->terms;
        $sort = $filters->sort();

        return view('documents.index', compact(
            'documents', 'categories', 'creators', 'tagSuggestions', 'savedFilters', 'activeView',
            'filters', 'scopes', 'searchTerms', 'sort', 'tree'
        ));
    }

    // Afficher un document spécifique
    public function show(Document $document)
    {
        if (!$document->canView()) {
            abort(403, 'Accès refusé à ce document.');
        }

        $document->load([
            'category',
            'creator',
            'verification',
            'approvalSteps.approver',
            'versions.creator',
            'auditLogs.user',
        ]);

        // Logger la consultation
        app(\App\Services\DocumentArchivalService::class)->logAction(
            $document, 'viewed', 'Document consulté'
        );

        // Les partages reçus ne sont plus « nouveaux » dans la boîte À traiter
        $document->shares()
            ->where('shared_with', auth()->id())
            ->whereNull('accessed_at')
            ->update(['accessed_at' => now()]);
        return view('documents.show', compact('document'));
    }

    // Déposer un nouveau document (tout format accepté) : référence, code de vérification et version 1
    public function store(Request $request)
    {
        $request->validate([
            'title'          => 'required|string|max:255',
            'category_id'    => ['nullable', Tenant::exists('categories')],
            'category'       => ['nullable', Tenant::exists('categories')],
            'is_confidential'=> 'nullable|boolean',
            'retention_years'=> 'nullable|integer|min:0|max:100',
            'expires_at'     => 'nullable|date|after:today',
            'tags'           => 'nullable|string|max:1000',
            'approval_workflow' => 'nullable|json',
            'metadata'       => ['nullable', new \App\Rules\ValidMetadata()],
            'import_file'    => $this->fileRules(true),
        ], ['import_file.required' => 'Choisissez le fichier à déposer.']);

        $categoryId = $request->input('category_id') ?? $request->input('category');
        if (!auth()->user()->canFileInCategory($categoryId ? (int) $categoryId : null)) {
            return $this->storeError($request, 'category_id', 'Votre service n\'a pas le droit de ranger des documents dans cette catégorie.');
        }

        if ($error = $this->quotaError($request->file('import_file')->getSize())) {
            return $this->storeError($request, 'import_file', $error);
        }

        try {
            // Augmenter le timeout pour les uploads volumineux vers MinIO
            set_time_limit(300);

            $title    = $request->input('title');
            $ref      = $this->generateReference();

            // Fichier déposé tel quel (Word, Excel, PowerPoint, PDF, image…)
            $file   = $request->file('import_file');
            $ext    = strtolower($file->getClientOriginalExtension()) ?: 'docx';

            // Doublon : même fichier, ou même contenu (texte lu dans le fichier, OCR compris pour les scans)
            $checksum = hash_file('sha256', $file->getRealPath());
            $text     = app(\App\Services\TextExtractor::class)->fromFile($file->getRealPath(), $ext);
            $detector = app(\App\Services\DuplicateDetector::class);
            if ($match = $detector->find(Tenant::id(), $checksum, $text)) {
                // Doublon avéré : seul un administrateur peut forcer l'import ; simple ressemblance : l'utilisateur confirme
                $forced = $request->boolean('allow_duplicate') && (!$match['blocking'] || auth()->user()->hasRole('admin'));
                if (!$forced) {
                    $answer = $detector->describe($match);
                    return $this->storeError($request, 'import_file', $answer['message'], 409, ['duplicate' => $answer['duplicate']]);
                }
                Log::info('Import d\'un doublon confirmé', ['existing_id' => $match['document']->id, 'level' => $match['level'], 'score' => $match['score'], 'user_id' => auth()->id()]);
            }
            $fileName = $ref . '.' . $ext;
            $path     = $this->storageDir() . $fileName;

            Log::info("Import document: début upload vers MinIO", [
                'path' => $path,
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ]);

            $stream = fopen($file->getRealPath(), 'r');
            $this->disk()->put($path, $stream);
            if (is_resource($stream)) fclose($stream);

            Log::info("Import document: upload MinIO terminé", ['path' => $path]);

            $verificationCode = DocumentVerification::generateCode();
            $document   = Document::make([
                'reference'       => $ref,
                'title'           => $title,
                'file_path'       => $path,
                'minio_url'       => $this->buildMinioUrl($path),
                'version'         => 1,
                'status'          => 'draft', // le statut n'évolue que par le circuit
                'is_confidential' => $request->boolean('is_confidential'),
                'retention_years' => $this->retentionYears($request, $categoryId),
                'expires_at'      => $request->input('expires_at'),
                'creator_id'      => auth()->id(),
                'category_id'     => $categoryId,
                'tags'            => $request->input('tags') ? array_map('trim', explode(',', $request->input('tags'))) : null,
                'checksum'        => $checksum,
            ])->fillContent($text);
            $document->save();

            DocumentVerification::create(['document_id' => $document->id, 'verification_code' => $verificationCode]);

            // Créer la version initiale (v1) dans l'historique
            \App\Models\DocumentVersion::create([
                'document_id' => $document->id,
                'version_number' => 1,
                'file_path' => $path,
                'checksum' => $checksum,
                'change_description' => 'Version initiale (import)',
                'created_by' => auth()->id(),
                'metadata' => [
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ],
            ]);

            // Texte déjà lu pour la détection des doublons ; sinon nouvel essai en arrière-plan
            if (blank($text)) {
                \App\Jobs\IndexDocumentText::dispatch($document->id);
            }

            $workflowError = app(\App\Services\ApprovalWorkflow::class)->autoStart($document);

            // Import multiple (file d'envoi côté navigateur)
            if ($request->expectsJson()) {
                return response()->json([
                    'id'        => $document->id,
                    'reference' => $document->reference,
                    'title'     => $document->title,
                    'url'       => route('documents.show', $document),
                    'workflow'  => $workflowError,
                ], 201);
            }

            return $this->afterCreation($document, 'Document déposé : ' . $ref . '.', $workflowError);

        } catch (Exception $e) {
            // Log l'erreur pour le debug si besoin
            Log::error("Erreur dépôt document: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->storeError($request, 'error', 'Erreur lors de l\'enregistrement du document. Consultez les logs ou contactez un administrateur.', 500);
        }
    }

    // Après création : circuit lancé automatiquement, à configurer, ou facultatif
    private function afterCreation(Document $document, string $message, ?string $workflowError)
    {
        $document->refresh();
        if ($document->status === 'review') {
            return redirect()->route('documents.approval', $document)
                ->with('success', $message . ' Le circuit d\'approbation imposé par sa catégorie est lancé.');
        }
        if ($workflowError) {
            return redirect()->route('documents.approval', $document)->with('error', $message . ' ' . $workflowError);
        }
        if ($document->workflowRule()['signature']) {
            return redirect()->route('documents.signatures', $document)
                ->with('success', $message . ' Sa catégorie exige des signatures : envoyez les demandes.');
        }

        return redirect()->route('documents.approval', $document)
            ->with('success', $message . ' Vous pouvez lancer un circuit d\'approbation si nécessaire.');
    }

    /**
     * Durée de conservation d'un nouveau document : celle saisie, sinon celle de sa catégorie
     * (ou de la catégorie parente la plus proche), sinon la durée par défaut de la plateforme.
     */
    private function retentionYears(Request $request, $categoryId): int
    {
        if ($request->filled('retention_years')) {
            return (int) $request->input('retention_years');
        }

        $category = $categoryId ? \App\Models\Category::find($categoryId) : null;
        for ($depth = 0; $category && $depth < 50; $depth++) {
            if ($category->default_retention_years) {
                return (int) $category->default_retention_years;
            }
            $category = $category->parent;
        }

        return (int) config('ged.default_retention_years', 5);
    }

    // Erreur de création : JSON pour l'import multiple, redirection sinon
    private function storeError(Request $request, string $field, string $message, int $status = 422, array $extra = [])
    {
        if ($request->expectsJson()) {
            return response()->json(array_merge(['message' => $message, 'errors' => [$field => [$message]]], $extra), $status);
        }

        return back()->withErrors([$field => $message])->withInput();
    }

    // Télécharger un document
    public function download(Document $document)
    {
        if (!$document->canView()) {
            abort(403, 'Accès refusé à ce document.');
        }

        if (!$this->disk()->exists($document->file_path)) {
            abort(404, 'Fichier introuvable sur MinIO.');
        }

        $ext = $this->fileExtension($document);

        // Watermark pour les documents confidentiels
        if ($document->is_confidential) {
            if ($ext === 'docx') {
                return $this->downloadWithWatermark($document);
            }
            // Pas de watermark possible sur ce format : réservé aux administrateurs
            if (!auth()->user()->hasRole('admin')) {
                return back()->with('error', 'Ce document confidentiel ne peut pas être marqué (format .' . $ext . '). Téléchargement réservé aux administrateurs ; utilisez l\'aperçu.');
            }
        }

        app(\App\Services\DocumentArchivalService::class)->logAction(
            $document, 'downloaded', 'Document téléchargé'
        );

        return $this->disk()->download($document->file_path, $document->title . '.' . $ext);
    }

    /**
     * Télécharge un document confidentiel avec watermark "CONFIDENTIEL — Nom — Date"
     */
    private function downloadWithWatermark(Document $document): \Symfony\Component\HttpFoundation\Response
    {
        try {
            $tempOut = tempnam(sys_get_temp_dir(), 'wm_out_') . '.docx';
            file_put_contents($tempOut, $this->disk()->get($document->file_path));

            $wmText = 'CONFIDENTIEL — ' . auth()->user()->full_name . ' — ' . now()->format('d/m/Y H:i');
            app(\App\Services\WordTemplate::class)->watermark($tempOut, $wmText);

            $filename = $document->title . '_CONFIDENTIEL.docx';
            $response = response()->download($tempOut, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);

            app(\App\Services\DocumentArchivalService::class)->logAction(
                $document, 'downloaded', 'Téléchargement confidentiel avec watermark'
            );

            return $response;

        } catch (Exception $e) {
            if (isset($tempOut)) @unlink($tempOut);
            Log::error('Watermark error: ' . $e->getMessage(), ['document_id' => $document->id]);
            // Ne jamais livrer un document confidentiel sans watermark
            return back()->with('error', 'Impossible d\'appliquer le filigrane de confidentialité. Téléchargement annulé.');
        }
    }

    // Afficher le formulaire d'édition d'un document
    public function edit(Document $document)
    {
        if (!$document->canEdit()) {
            abort(403, 'Vous n\'avez pas la permission de modifier ce document.');
        }
        $categories = \App\Models\Category::orderBy('name')->get();
        return view('documents.edit', compact('document', 'categories'));
    }

    // Mettre à jour un document existant
    public function update(Request $request, Document $document)
    {
        if (!$document->canEdit()) {
            abort(403, 'Vous n\'avez pas la permission de modifier ce document.');
        }

        // Vérifier le Legal Hold
        if ($document->isUnderLegalHold()) {
            return back()->with('error', 'Ce document est sous gel juridique (Legal Hold) et ne peut pas être modifié.');
        }
        if ($document->isLockedByOther()) {
            return back()->with('error', 'Ce document est verrouillé par ' . $document->lock->lockedBy->full_name . '.');
        }
        $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => ['nullable', Tenant::exists('categories')],
            'is_confidential' => 'nullable|boolean',
            'retention_years' => 'nullable|integer|min:0|max:100',
            'expires_at' => 'nullable|date',
            'tags' => 'nullable|string|max:1000',
            'approval_workflow' => 'nullable|json',
            'metadata' => ['nullable', new \App\Rules\ValidMetadata()],
        ]);

        // Clean tags into array
        $tagsInput = $request->input('tags');
        $tags = null;
        if ($tagsInput) {
            $pieces = array_filter(array_map('trim', explode(',', $tagsInput)));
            $pieces = array_values(array_unique($pieces));
            $tags = $pieces ?: null;
        }

        // Parse metadata JSON safely
        $metadata = null;
        if ($request->filled('metadata')) {
            $decoded = json_decode($request->input('metadata'), true);
            $metadata = is_array($decoded) ? $decoded : null;
        }

        // Parse workflow JSON safely
        $workflow = null;
        if ($request->filled('approval_workflow')) {
            $decodedWf = json_decode($request->input('approval_workflow'), true);
            $workflow = is_array($decodedWf) ? $decodedWf : null;
        }

        // Le statut n'évolue que par le circuit ; la catégorie (qui fixe le circuit) ne change pas pendant un circuit
        $newCategory = $request->input('category_id') ? (int) $request->input('category_id') : null;
        if ($newCategory !== $document->category_id && $document->isInWorkflow()) {
            return back()->withErrors(['category_id' => 'La catégorie ne peut pas changer pendant un circuit d\'approbation ou de signature.'])->withInput();
        }
        if ($newCategory !== $document->category_id && !auth()->user()->canFileInCategory($newCategory)) {
            return back()->withErrors(['category_id' => 'Votre service n\'a pas le droit de ranger des documents dans cette catégorie.'])->withInput();
        }

        $tracked   = ['title', 'category_id', 'status', 'is_confidential', 'retention_years', 'expires_at'];
        $oldValues = $document->only($tracked);

        $document->title = $request->input('title');
        $document->category_id = $request->input('category_id');
        $document->is_confidential = $request->boolean('is_confidential');
        $document->retention_years = (int) $request->input('retention_years');
        $document->expires_at = $request->input('expires_at');
        $document->tags = $tags;
        
        if ($metadata !== null) $document->metadata = $metadata;
        if ($workflow !== null) $document->approval_workflow = $workflow;
        
        $document->save();

        app(\App\Services\DocumentArchivalService::class)->logAction(
            $document, 'updated', 'Métadonnées modifiées', $oldValues, $document->only($tracked)
        );

        // Re-dispatch indexing job to refresh content_text/search
        if (class_exists('\App\Jobs\IndexDocumentText')) {
            \App\Jobs\IndexDocumentText::dispatch($document->id);
        }

        return redirect()->route('documents.index')->with('success', 'Document mis à jour.');
    }

    // Supprimer un document (soft delete → corbeille)
    public function destroy(Document $document)
    {
        // Vérifier le Legal Hold
        if ($document->isUnderLegalHold()) {
            return back()->with('error', 'Ce document est sous gel juridique (Legal Hold) et ne peut pas être supprimé.');
        }
        if ($document->isArchived()) {
            return back()->with('error', 'Un document archivé ne peut pas être supprimé. Un administrateur doit d\'abord le désarchiver.');
        }

        $user = auth()->user();
        $canDelete = $user->hasRole('admin')
            || $document->creator_id === $user->id
            || ($document->permissions()->where('user_id', $user->id)->first()?->can_delete ?? false);

        if (!$canDelete) {
            abort(403, 'Vous n\'avez pas la permission de supprimer ce document.');
        }

        // Soft delete — le fichier reste sur MinIO
        app(\App\Services\DocumentArchivalService::class)->logAction(
            $document, 'deleted', 'Déplacé dans la corbeille'
        );
        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Document déplacé dans la corbeille.');
    }

   // Archiver un document
    public function archive(Document $document)
    {
        if (!$document->canManage()) {
            abort(403, 'Seul le créateur ou un administrateur peut archiver ce document.');
        }

        $reason = request()->validate(['reason' => 'nullable|string|max:500'])['reason'] ?? null;
        if ($error = app(DocumentArchivalService::class)->archive($document, $reason)) {
            return back()->with('error', $error);
        }

        return redirect()->back()->with('success', 'Document archivé : il est maintenant figé (lecture seule).');
    }

    // Copie d'archivage PDF/A
    public function archivalCopy(Document $document)
    {
        if (!$document->canView()) {
            abort(403, 'Accès refusé à ce document.');
        }
        abort_unless($document->archival_copy_path && $this->disk()->exists($document->archival_copy_path), 404, 'Copie PDF/A introuvable.');

        return $this->disk()->download($document->archival_copy_path, $document->reference . ' (PDF-A).pdf');
    }

    // Copie officielle : document en PDF + certificat de validation (QR code, approbations, signatures)
    public function officialCopy(Document $document)
    {
        if (!$document->canView()) {
            abort(403, 'Accès refusé à ce document.');
        }
        abort_unless($document->official_copy_path && $this->disk()->exists($document->official_copy_path), 404, 'Copie officielle introuvable.');

        app(DocumentArchivalService::class)->logAction($document, 'downloaded', 'Copie officielle téléchargée');

        return $this->disk()->download($document->official_copy_path, $document->reference . ' (copie officielle v' . $document->official_copy_version . ').pdf');
    }

    // Désarchiver : administrateur uniquement, motif obligatoire (tracé dans le journal)
    public function unarchive(Document $document)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403, 'Seul un administrateur peut désarchiver un document.');
        }
        if (!$document->isArchived()) {
            return back()->with('error', 'Ce document n\'est pas archivé.');
        }

        $data = request()->validate(['reason' => 'required|string|min:5|max:500'], [
            'reason.required' => 'Indiquez le motif du désarchivage.',
            'reason.min'      => 'Indiquez le motif du désarchivage.',
        ]);
        app(DocumentArchivalService::class)->unarchive($document, $data['reason']);

        return back()->with('success', 'Document désarchivé : il est de nouveau modifiable.');
    }

    // Afficher les versions d'un document
    public function versions(Document $document)
    {
        if (!$document->canView()) {
            abort(403, 'Accès refusé à ce document.');
        }
        $versions = $document->versions()->paginate(20);
        return view('documents.versions', compact('document', 'versions'));
    }

    // Restaurer une version précédente
    public function restoreVersion(Document $document, $versionNumber)
    {
        if (!$document->canEdit()) {
            abort(403, 'Vous n\'avez pas la permission de restaurer une version.');
        }
        if ($document->isUnderLegalHold()) {
            return back()->with('error', 'Ce document est sous gel juridique (Legal Hold) et ne peut pas être modifié.');
        }
        if ($document->isLockedByOther()) {
            return back()->with('error', 'Ce document est verrouillé par ' . $document->lock->lockedBy->full_name . '.');
        }
        if ($error = $this->frozenContentError($document)) {
            return back()->with('error', $error);
        }

        $service = new DocumentArchivalService();

        if ($service->restoreVersion($document, $versionNumber)) {
            return redirect()->back()->with('success', "Version {$versionNumber} restaurée.");
        }

        return redirect()->back()->with('error', 'Version introuvable.');
    }

    // Afficher le journal d'audit d'un document
    public function audit(Document $document)
    {
        if (!$document->canView()) {
            abort(403, 'Accès refusé à ce document.');
        }
        $auditLogs = $document->auditLogs()->paginate(50);
        return view('documents.audit', compact('document', 'auditLogs'));
    }

    // Streamer le fichier depuis MinIO vers le navigateur (évite les problèmes CORS)
    public function stream(Document $document)
    {
        if (!$document->canView()) {
            abort(403, 'Accès refusé à ce document.');
        }

        if (!$this->disk()->exists($document->file_path)) {
            abort(404, 'Fichier introuvable.');
        }

        $type   = $document->fileType();
        $stream = $this->disk()->readStream($document->file_path);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => $type->mime,
            // Formats inconnus : jamais interprétés par le navigateur
            'Content-Disposition' => ($type->family === 'other' ? 'attachment' : 'inline') . '; filename="' . $document->reference . '.' . $type->extension . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-cache',
        ]);
    }

    // Uploader une nouvelle version d'un document existant
    public function uploadVersion(Request $request, Document $document)
    {
        if (!$document->canEdit()) {
            abort(403, 'Vous n\'avez pas la permission d\'uploader une nouvelle version.');
        }

        // Vérifier le Legal Hold
        if ($document->isUnderLegalHold()) {
            return back()->with('error', 'Ce document est sous gel juridique (Legal Hold) et ne peut pas être modifié.');
        }

        if ($document->isLockedByOther()) {
            return back()->with('error', 'Ce document est verrouillé par ' . $document->lock->lockedBy->full_name . '.');
        }
        if ($error = $this->frozenContentError($document)) {
            return back()->with('error', $error);
        }

        $request->validate([
            'file' => $this->fileRules(true),
        ]);

        // Vérifier que le format de la nouvelle version correspond au format actuel du document
        $file        = $request->file('file');
        $newExt      = strtolower($file->getClientOriginalExtension());
        $currentExt  = strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION));

        // Une nouvelle version doit rester de la même famille (Word → Word, Excel → Excel…)
        $newType     = FileType::fromExtension($newExt);
        $currentType = FileType::fromExtension($currentExt);

        if (!$newType->isCompatibleWith($currentType)) {
            return back()->withErrors([
                'file' => "Le fichier envoyé ({$newType->label()}, .{$newExt}) ne correspond pas au format du document ({$currentType->label()}, .{$currentExt}). Envoyez un fichier du même type."
            ]);
        }

        if ($error = $this->quotaError($file->getSize())) {
            return back()->withErrors(['file' => $error]);
        }

        try {
            // Augmenter le timeout pour les uploads volumineux vers MinIO
            set_time_limit(300);

            $ext      = $newExt ?: 'docx';
            $path     = $this->storageDir() . $document->reference . '_v' . (intval($document->version) + 1) . '.' . $ext;

            // Upload vers MinIO
            $upload = fopen($file->getRealPath(), 'r');
            $this->disk()->put($path, $upload);
            if (is_resource($upload)) fclose($upload);

            // Mettre à jour minio_url
            $document->update(['minio_url' => $this->buildMinioUrl($path)]);

            // Créer une version via le service
            $service = new DocumentArchivalService();
            $service->createVersion($document, $path, $request->input('change_description', 'Nouvelle version importée'));

            // Dispatch indexing
            \App\Jobs\IndexDocumentText::dispatch($document->id);

            return back()->with('success', 'Document mis à jour avec succès. Version ' . $document->fresh()->version . ' créée.');

        } catch (Exception $e) {
            Log::error('Erreur upload version: ' . $e->getMessage());
            return back()->withErrors(['file' => 'Erreur lors de l\'upload du fichier. Consultez les logs ou contactez un administrateur.']);
        }
    }

    // Contenu gelé : archivé, ou circuit d'approbation / de signature en cours
    private function frozenContentError(Document $document): ?string
    {
        if ($document->isArchived()) {
            return 'Un document archivé est figé : un administrateur doit d\'abord le désarchiver.';
        }
        if ($document->isInWorkflow()) {
            return 'Le contenu est gelé pendant le circuit ' . ($document->status === 'review' ? 'd\'approbation' : 'de signature')
                . ' : retirez d\'abord le document du circuit pour le modifier.';
        }

        return null;
    }

    // Prévisualiser un document (lecture seule, HTML via Mammoth.js)
    public function preview(Document $document)
    {
        if (!$document->canView()) {
            abort(403, 'Accès refusé à ce document.');
        }

        if (!$this->disk()->exists($document->file_path)) {
            abort(404, 'Fichier introuvable.');
        }
        $fileType   = $document->fileType();
        $canConvert = app(\App\Services\DocumentConverter::class)->canConvertToPdf();
        return view('documents.preview', compact('document', 'fileType', 'canConvert'));
    }

    // Afficher la page de recherche avancée
    public function advancedSearch()
    {
        $categories = \App\Models\Category::orderBy('name')->get();
        $users = \App\Models\User::orderBy('full_name')->get();

        return view('documents.advanced-search', compact('categories', 'users'));
    }

    // API endpoint to get document details
    public function apiShow(Document $document)
    {
        if (!$document->canView()) {
            abort(403, 'Accès refusé à ce document.');
        }
        // Le texte intégral n'est jamais renvoyé : seulement un court extrait. Aucune adresse de stockage non plus.
        $document->load(['category', 'creator']);
        $data = $document->makeHidden(['content_text', 'minio_url', 'archival_copy_path'])->toArray();
        $data['excerpt'] = $document->content_text ? mb_substr(trim(preg_replace('/\s+/u', ' ', $document->content_text)), 0, 500) : null;

        return response()->json($data);
    }
}
