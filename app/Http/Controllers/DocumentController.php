<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Endroid\QrCode\QrCode as EndroidQrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Str;
use App\Services\DocumentArchivalService;
use Exception;
use Illuminate\Support\Facades\Log;

class DocumentController extends Controller
{
    // Afficher la liste des documents avec options de recherche et de filtrage
    public function index(Request $request)
    {
        $query = Document::query();

        if ($q = $request->input('q')) {
            // Use Postgres full-text search (french configuration) when available
            $query->whereRaw("to_tsvector('french', coalesce(title,'') || ' ' || coalesce(reference,'') || ' ' || coalesce(content_text,'')) @@ plainto_tsquery('french', ?)", [$q]);
        }

        if ($category = $request->input('category')) {
            $query->where('category_id', $category);
        }

        // Sorting
        $sort = $request->input('sort');
        if ($sort === 'version') {
            $query->orderBy('version', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $documents = $query->paginate(15)->withQueryString();
        $categories = \App\Models\Category::orderBy('name')->get();

        return view('documents.index', compact('documents', 'categories'));
    }

    // Afficher un document spécifique
    public function show(Document $document)
    {
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
        return view('documents.show', compact('document'));
    }

    // Créer et stocker un nouveau document Word avec QR code de vérification
    public function store(Request $request)
    {
        $request->validate([
            'title'          => 'required|string|max:255',
            'category_id'    => 'nullable|exists:categories,id',
            'status'         => 'nullable|in:draft,review,approved',
            'is_confidential'=> 'nullable|boolean',
            'retention_years'=> 'nullable|integer|min:0|max:100',
            'expires_at'     => 'nullable|date|after:today',
            'tags'           => 'nullable|string|max:1000',
            'approval_workflow' => 'nullable|json',
            'metadata'       => ['nullable', new \App\Rules\ValidMetadata()],
            'import_file'    => 'nullable|file|mimes:docx,doc|max:51200',
        ]);

        try {
            $title    = $request->input('title');
            $ref      = 'BAMA-' . strtoupper(Str::random(6));
            $fileName = $ref . '.docx';
            $path     = 'documents/' . $fileName;

            // Mode import : utiliser le fichier uploadé directement
            if ($request->boolean('import_mode') && $request->hasFile('import_file')) {
                $file   = $request->file('import_file');
                $stream = fopen($file->getRealPath(), 'r');
                Storage::disk('s3')->put($path, $stream);
                if (is_resource($stream)) fclose($stream);

                $verificationCode = Str::random(32);
                $categoryId = $request->input('category_id');
                $document   = Document::create([
                    'reference'       => $ref,
                    'title'           => $title,
                    'file_path'       => $path,
                    'version'         => 1,
                    'status'          => $request->input('status', 'draft'),
                    'is_confidential' => $request->boolean('is_confidential'),
                    'retention_years' => $request->input('retention_years', 5),
                    'expires_at'      => $request->input('expires_at'),
                    'creator_id'      => auth()->id(),
                    'category_id'     => $categoryId,
                    'tags'            => $request->input('tags') ? array_map('trim', explode(',', $request->input('tags'))) : null,
                ]);

                DocumentVerification::create(['document_id' => $document->id, 'verification_code' => $verificationCode]);
                \App\Jobs\IndexDocumentText::dispatch($document->id);

                return redirect()->route('documents.index')->with('success', 'Document importé : ' . $ref);
            }

            // 1. Création du contenu Word
            $phpWord = new PhpWord();

            // Style par défaut
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(11);

            $section = $phpWord->addSection();

            // Générer le code de vérification
            $verificationCode = Str::random(32);

            // === EN-TÊTE AMÉLIORÉ ===
            // Logo et titre principal
            $header = $section->addHeader();
            $header->addText("GROUPE BAMA", ['bold' => true, 'size' => 18, 'color' => 'FF6600'], ['alignment' => 'center']);
            $header->addText("Système de Gestion Documentaire", ['size' => 10], ['alignment' => 'center']);
            $header->addTextBreak(1);

            // Informations principales dans un tableau
            $table = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 80]);
            $table->addRow();
            $table->addCell(4000)->addText("Référence:", ['bold' => true]);
            $table->addCell(6000)->addText($ref, ['bold' => true, 'size' => 12]);
            $table->addCell(3000)->addText("Version: 1.0", ['bold' => true]);

            $table->addRow();
            $table->addCell(4000)->addText("Titre:", ['bold' => true]);
            $table->addCell(6000)->addText($title);
            $table->addCell(3000)->addText("Statut: " . ucfirst($request->input('status', 'draft')), ['bold' => true]);

            $table->addRow();
            $table->addCell(4000)->addText("Créé par:", ['bold' => true]);
            $table->addCell(6000)->addText(auth()->user()->full_name);
            $table->addCell(3000)->addText("Date: " . now()->format('d/m/Y'));

            $section->addTextBreak(1);

            // Ajouter les informations de sécurité si confidentiel
            if ($request->boolean('is_confidential')) {
                $section->addText("CONFIDENTIEL - ACCÈS RESTREINT", ['bold' => true, 'color' => 'FF0000', 'size' => 12], ['alignment' => 'center']);
                $section->addTextBreak(1);
            }

            // Ligne de séparation
            $section->addHR(['width' => 100, 'height' => 1]);

            // Contenu du document
            $section->addText("Contenu du document :", ['bold' => true, 'size' => 12]);
            $section->addTextBreak(1);
            $section->addText("Tapez votre texte ici...", ['italic' => true]);

            // Pied de page avec QR code
            $footer = $section->addFooter();

            // Générer le QR code pour le pied de page
            $verificationUrl = rtrim(config('app.url'), '/') . '/verify/' . $verificationCode;
            $qrCode = EndroidQrCode::create($verificationUrl)->setSize(100);
            $qrWriter = new PngWriter();
            $qrResult = $qrWriter->write($qrCode);
            $qrImagePath = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
            file_put_contents($qrImagePath, $qrResult->getString());

            // Centrer le QR code dans le pied de page
            $footer->addImage($qrImagePath, ['width' => 30, 'height' => 30, 'alignment' => 'center']);

            // 2. Sauvegarde temporaire locale
            $tempFile = tempnam(sys_get_temp_dir(), 'phpword_');
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempFile);

            // Nettoyer le fichier QR temporaire
            if (isset($qrImagePath) && file_exists($qrImagePath)) {
                @unlink($qrImagePath);
            }

            // 3. Upload vers MinIO via le disque S3 en stream (mémoire limitée)
            $stream = fopen($tempFile, 'r');
            if ($stream === false) {
                throw new Exception('Impossible d ouvrir le fichier temporaire pour lecture.');
            }

            Storage::disk('s3')->put($path, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            // On supprime le fichier temporaire immédiatement
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }

            // 4. Enregistrement en base de données PostgreSQL
            $categoryId = $request->input('category_id') ?? $request->input('category');

            // Calculer la date d'expiration si retention_years est spécifié
            $expiresAt = $request->input('expires_at');
            $retentionYears = (int) $request->input('retention_years');
            if (!$expiresAt && $retentionYears > 0) {
                $expiresAt = now()->addYears($retentionYears)->toDateString();
            }

            $document = Document::create([
                'reference' => $ref,
                'title' => $title,
                'file_path' => $path,
                'version' => 1,
                'status' => $request->input('status', 'draft'),
                'is_confidential' => $request->boolean('is_confidential'),
                'retention_years' => $request->input('retention_years', 5),
                'expires_at' => $expiresAt,
                'creator_id' => auth()->id(),
                'category_id' => $categoryId,
                'tags' => $request->input('tags') ? array_map('trim', explode(',', $request->input('tags'))) : null,
                'approval_workflow' => $request->input('approval_workflow') ? json_decode($request->input('approval_workflow'), true) : null,
                'metadata' => null,
            ]);

            // Créer l'entrée de vérification
            DocumentVerification::create([
                'document_id' => $document->id,
                'verification_code' => $verificationCode,
            ]);

            // Dispatch indexing job (OCR / text extraction)
            \App\Jobs\IndexDocumentText::dispatch($document->id);

            return redirect()->route('documents.index')->with('success', 'Le document ' . $ref . ' a été généré avec succès. Le QR code de vérification est inclus.');

        } catch (Exception $e) {
            // Log l'erreur pour le debug si besoin
            Log::error("Erreur génération document: " . $e->getMessage());

            return redirect()->back()->withErrors(['error' => 'Erreur lors de la génération du document : ' . $e->getMessage()]);
        }
    }

    // Télécharger un document
    public function download(Document $document)
    {
        if (!Storage::disk('s3')->exists($document->file_path)) {
            abort(404, 'Fichier introuvable sur MinIO.');
        }

        return Storage::disk('s3')->download($document->file_path, $document->title . '.docx');
    }

    // Afficher le formulaire d'édition d'un document
    public function edit(Document $document)
    {
        $categories = \App\Models\Category::orderBy('name')->get();
        return view('documents.edit', compact('document', 'categories'));
    }

    // Mettre à jour un document existant
    public function update(Request $request, Document $document)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'nullable|in:draft,review,approved',
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

        $document->title = $request->input('title');
        $document->category_id = $request->input('category_id');
        $document->status = $request->input('status', 'draft');
        $document->is_confidential = $request->boolean('is_confidential');
        $document->retention_years = (int) $request->input('retention_years');
        $document->expires_at = $request->input('expires_at');
        $document->tags = $tags;
        
        if ($metadata !== null) $document->metadata = $metadata;
        if ($workflow !== null) $document->approval_workflow = $workflow;
        
        $document->save();

        // Re-dispatch indexing job to refresh content_text/search
        if (class_exists('\App\Jobs\IndexDocumentText')) {
            \App\Jobs\IndexDocumentText::dispatch($document->id);
        }

        return redirect()->route('documents.index')->with('success', 'Document mis à jour.');
    }

    // Supprimer un document (soft delete → corbeille)
    public function destroy(Document $document)
    {
        if (!auth()->user()->hasRole('admin')) {
            abort(403);
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
        $service = new DocumentArchivalService();
        $service->archiveDocument($document, request('reason'));

        return redirect()->back()->with('success', 'Document archivé avec succès.');
    }

    // Afficher les versions d'un document
    public function versions(Document $document)
    {
        $versions = $document->versions()->paginate(20);
        return view('documents.versions', compact('document', 'versions'));
    }

    // Restaurer une version précédente
    public function restoreVersion(Document $document, $versionNumber)
    {
        $service = new DocumentArchivalService();

        if ($service->restoreVersion($document, $versionNumber)) {
            return redirect()->back()->with('success', "Version {$versionNumber} restaurée.");
        }

        return redirect()->back()->with('error', 'Version introuvable.');
    }

    // Afficher le journal d'audit d'un document
    public function audit(Document $document)
    {
        $auditLogs = $document->auditLogs()->paginate(50);
        return view('documents.audit', compact('document', 'auditLogs'));
    }

    // Streamer le fichier DOCX depuis MinIO vers le navigateur (évite les problèmes CORS)
    public function stream(Document $document)
    {
        if (!Storage::disk('s3')->exists($document->file_path)) {
            abort(404, 'Fichier introuvable.');
        }

        $stream = Storage::disk('s3')->readStream($document->file_path);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'inline; filename="' . $document->reference . '.docx"',
            'Cache-Control' => 'no-cache',
        ]);
    }

    // Uploader une nouvelle version d'un document existant
    public function uploadVersion(Request $request, Document $document)
    {
        $request->validate([
            'file' => 'required|file|mimes:docx,doc|max:51200',
        ]);

        try {
            $file     = $request->file('file');
            $path     = 'documents/' . $document->reference . '_v' . (intval($document->version) + 1) . '.docx';

            // Upload vers MinIO
            Storage::disk('s3')->put($path, file_get_contents($file->getRealPath()));

            // Créer une version via le service
            $service = new DocumentArchivalService();
            $service->createVersion($document, $path, $request->input('change_description', 'Mise à jour depuis Word'));

            // Dispatch indexing
            \App\Jobs\IndexDocumentText::dispatch($document->id);

            return back()->with('success', 'Document mis à jour avec succès. Version ' . $document->fresh()->version . ' créée.');

        } catch (Exception $e) {
            Log::error('Erreur upload version: ' . $e->getMessage());
            return back()->withErrors(['file' => 'Erreur lors de l\'upload : ' . $e->getMessage()]);
        }
    }

    // Prévisualiser un document (lecture seule, HTML via Mammoth.js)
    public function preview(Document $document)
    {
        if (!Storage::disk('s3')->exists($document->file_path)) {
            abort(404, 'Fichier introuvable.');
        }
        return view('documents.preview', compact('document'));
    }

    // Afficher l'éditeur en ligne pour un document
    public function editOnline(Document $document)
    {
        // Vérifier que l'utilisateur peut éditer ce document
        if (!auth()->user()->hasRole('admin') && $document->creator_id !== auth()->id()) {
            abort(403, 'Vous n\'avez pas les permissions pour éditer ce document.');
        }

        return view('documents.edit-online', compact('document'));
    }

    // Sauvegarder les modifications depuis l'éditeur en ligne
    public function saveOnline(Request $request, Document $document)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        try {
            // Vérifier que l'utilisateur peut éditer ce document
            if (!auth()->user()->hasRole('admin') && $document->creator_id !== auth()->id()) {
                return response()->json(['error' => 'Permissions insuffisantes'], 403);
            }

            // Convertir le HTML en DOCX
            $phpWord = new PhpWord();
            $phpWord->setDefaultFontName('Arial');
            $phpWord->setDefaultFontSize(11);

            $section = $phpWord->addSection();

            // Restaurer l'en-tête original
            $header = $section->addHeader();
            $header->addText("GROUPE BAMA", ['bold' => true, 'size' => 18, 'color' => 'FF6600'], ['alignment' => 'center']);
            $header->addText("Système de Gestion Documentaire", ['size' => 10], ['alignment' => 'center']);
            $header->addTextBreak(1);

            // Informations principales dans un tableau
            $table = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 80]);
            $table->addRow();
            $table->addCell(4000)->addText("Référence:", ['bold' => true]);
            $table->addCell(6000)->addText($document->reference, ['bold' => true, 'size' => 12]);
            $table->addCell(3000)->addText("Version: " . $document->version, ['bold' => true]);

            $table->addRow();
            $table->addCell(4000)->addText("Titre:", ['bold' => true]);
            $table->addCell(6000)->addText($document->title);
            $table->addCell(3000)->addText("Statut: " . ucfirst($document->status), ['bold' => true]);

            $table->addRow();
            $table->addCell(4000)->addText("Créé par:", ['bold' => true]);
            $table->addCell(6000)->addText($document->creator->full_name);
            $table->addCell(3000)->addText("Date: " . $document->created_at->format('d/m/Y'));

            $section->addTextBreak(1);

            // Ajouter les informations de sécurité si confidentiel
            if ($document->is_confidential) {
                $section->addText("CONFIDENTIEL - ACCÈS RESTREINT", ['bold' => true, 'color' => 'FF0000', 'size' => 12], ['alignment' => 'center']);
                $section->addTextBreak(1);
            }

            // Ligne de séparation
            $section->addHR(['width' => 100, 'height' => 1]);

            // Convertir le HTML en contenu Word
            $htmlContent = $request->input('content');
            \PhpOffice\PhpWord\Shared\Html::addHtml($section, $htmlContent);

            // Pied de page avec QR code (si le document a une vérification)
            $verification = $document->verification;
            if ($verification) {
                $footer = $section->addFooter();
                $qrCode = EndroidQrCode::create(rtrim(config('app.url'), '/') . '/verify/' . $verification->verification_code)->setSize(100);
                $qrWriter = new PngWriter();
                $qrResult = $qrWriter->write($qrCode);
                $qrImagePath = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
                file_put_contents($qrImagePath, $qrResult->getString());
                $footer->addImage($qrImagePath, ['width' => 30, 'height' => 30, 'alignment' => 'center']);
                @unlink($qrImagePath);
            }

            // Sauvegarder temporairement
            $tempFile = tempnam(sys_get_temp_dir(), 'phpword_');
            $writer = IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempFile);

            // Upload vers MinIO
            $stream = fopen($tempFile, 'r');
            Storage::disk('s3')->put($document->file_path, $stream);
            fclose($stream);

            // Nettoyer
            @unlink($tempFile);

            // Créer une nouvelle version
            $document->version += 0.1;
            $document->save();

            // Dispatch indexing job
            \App\Jobs\IndexDocumentText::dispatch($document->id);

            return response()->json([
                'success' => true,
                'message' => 'Document sauvegardé avec succès',
                'version' => $document->version
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur sauvegarde document en ligne: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de la sauvegarde: ' . $e->getMessage()
            ], 500);
        }
    }

    // Afficher la page de recherche avancée
    public function advancedSearch()
    {
        $categories = \App\Models\Category::orderBy('name')->get();
        $users = \App\Models\User::orderBy('full_name')->get();

        return view('documents.advanced-search', compact('categories', 'users'));
    }

    // API endpoint for advanced document search
    public function apiSearch(Request $request)
    {
        $query = Document::with(['category', 'creator']);

        // Recherche textuelle
        if ($q = $request->input('q')) {
            $query->where(function ($qry) use ($q) {
                $qry->where('title', 'ILIKE', "%{$q}%")
                    ->orWhere('reference', 'ILIKE', "%{$q}%")
                    ->orWhere('content_text', 'ILIKE', "%{$q}%");
            });
        }

        // Filtres
        if ($category = $request->input('category')) {
            $query->where('category_id', $category);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($creator = $request->input('creator')) {
            $query->where('created_by', $creator);
        }

        if ($confidential = $request->input('confidential')) {
            $query->where('is_confidential', $confidential === '1');
        }

        // Période
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Tags
        if ($tags = $request->input('tags')) {
            $tagArray = array_map('trim', explode(',', $tags));
            foreach ($tagArray as $tag) {
                $query->where('tags', 'ILIKE', "%{$tag}%");
            }
        }

        // Tri par pertinence ou date
        if ($q) {
            // Pour la recherche textuelle, trier par pertinence (date récente en priorité)
            $query->orderBy('created_at', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $documents = $query->limit(100)->get();

        return response()->json([
            'data' => $documents,
            'total' => $documents->count()
        ]);
    }

    // API endpoint to get document details
    public function apiShow(Document $document)
    {
        return response()->json($document->load(['category', 'creator']));
    }
}
