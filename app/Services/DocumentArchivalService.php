<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\DocumentAuditLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use ZipArchive;

class DocumentArchivalService
{
    // Créer une nouvelle version de document
    public function createVersion(Document $document, string $filePath, ?string $changeDescription = null): DocumentVersion
    {
        // Dernier rempart : les contrôleurs refusent déjà le dépôt pendant un circuit
        if ($document->isInWorkflow()) {
            throw new \LogicException("Contenu gelé : le document {$document->reference} est dans un circuit d'approbation ou de signature.");
        }

        // Calculer le checksum du fichier
        $checksum = $this->calculateChecksum($document, $filePath);

        // Incrémenter la version
        $nextVersion = max((int) $document->versions()->max('version_number'), (int) $document->version) + 1;

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => $nextVersion,
            'file_path' => $filePath,
            'checksum' => $checksum,
            'change_description' => $changeDescription,
            'created_by' => Auth::id(),
            'metadata' => [
                'original_name' => basename($filePath),
                'size' => $document->disk()->size($filePath),
                'mime_type' => $document->disk()->mimeType($filePath),
            ]
        ]);

        // Mettre à jour le document avec la nouvelle version
        $document->update([
            'version' => $nextVersion,
            'file_path' => $filePath,
            'checksum' => $checksum,
        ]);

        // Logger l'action
        $this->logAction($document, 'version_created', "Nouvelle version {$nextVersion} créée", [
            'version_id' => $version->id,
            'change_description' => $changeDescription
        ]);

        $this->reopenAfterContentChange($document, "la version {$nextVersion}");

        return $version;
    }

    /**
     * Archiver un document : il devient figé (plus de modification, de nouvelle version, de suppression
     * ni de signature). Son empreinte SHA-256 est figée dans le journal au moment de l'archivage.
     * Retourne un message d'erreur, ou null si l'archivage a eu lieu.
     */
    public function archive(Document $document, ?string $reason = null): ?string
    {
        if ($document->isUnderLegalHold()) {
            return 'Ce document est sous gel juridique (Legal Hold) et ne peut pas être archivé.';
        }
        if ($document->isArchived()) {
            return 'Ce document est déjà archivé.';
        }
        if ($document->isLockedByOther()) {
            return 'Ce document est en cours de modification par un autre utilisateur.';
        }
        if ($document->isInWorkflow()) {
            return 'Ce document est dans un circuit ' . ($document->status === 'review' ? 'd\'approbation' : 'de signature') . ' en cours : il ne peut pas être archivé avant la fin du circuit.';
        }
        $rule = $document->workflowRule();
        if ($rule['signature'] && $document->status !== 'signed') {
            return 'Sa catégorie exige des signatures : ce document ne peut être archivé qu\'une fois signé.';
        }
        if ($rule['approval'] && !in_array($document->status, ['approved', 'signed'], true)) {
            return 'Sa catégorie exige une approbation : ce document ne peut être archivé qu\'une fois approuvé.';
        }

        $checksum = $document->checksum ?: $this->calculateChecksum($document, $document->file_path);
        $before   = $document->status;

        $document->update([
            'status'                => 'archived',
            'archived_at'           => now(),
            'archived_by'           => Auth::id(),
            'archive_reason'        => $reason ?: null,
            'status_before_archive' => $before,
            'checksum'              => $checksum,
        ]);

        $this->logAction($document, 'archived', 'Document archivé' . ($reason ? " : {$reason}" : ''),
            ['status' => $before], ['status' => 'archived', 'checksum' => $checksum, 'version' => $document->version]);

        // Copie PDF/A à côté de l'original (format de conservation à long terme)
        \App\Jobs\CreateArchivalCopy::dispatch($document->id);

        return null;
    }

    // Compatibilité : true si l'archivage a eu lieu
    public function archiveDocument(Document $document, ?string $reason = null): bool
    {
        return $this->archive($document, $reason) === null;
    }

    /**
     * Désarchiver (administrateur, motif obligatoire) : le document retrouve son statut d'avant l'archivage.
     */
    public function unarchive(Document $document, string $reason): void
    {
        $status = $document->status_before_archive ?: 'approved';

        $document->update([
            'status'                => $status,
            'archived_at'           => null,
            'archived_by'           => null,
            'archive_reason'        => null,
            'status_before_archive' => null,
        ]);
        // L'ancienne copie PDF/A reste stockée (supprimée avec le document) ; un nouvel archivage en créera une à jour
        $document->forceFill(['archival_copy_path' => null, 'archival_copy_checksum' => null])->saveQuietly();

        $this->logAction($document, 'unarchived', "Document désarchivé : {$reason}", ['status' => 'archived'], ['status' => $status]);
    }

    // Restaurer une version spécifique d'un document
    public function restoreVersion(Document $document, int $versionNumber): bool
    {
        // Vérifier le Legal Hold ; contenu gelé pendant un circuit
        if ($document->isUnderLegalHold() || $document->isInWorkflow() || $document->isArchived()) {
            return false;
        }

        $version = $document->versions()->where('version_number', $versionNumber)->first();

        if (!$version) {
            return false;
        }

        $oldValues = $document->only(['file_path', 'checksum', 'version']);

        $document->update([
            'file_path' => $version->file_path,
            'checksum' => $version->checksum,
            'version' => $versionNumber,
        ]);

        $this->logAction($document, 'version_restored', "Restauré à la version {$versionNumber}", $oldValues, $document->fresh()->only(['file_path', 'checksum', 'version']));
        $this->reopenAfterContentChange($document, "la version {$versionNumber} restaurée");

        return true;
    }

    /**
     * Nouveau contenu sur un document approuvé ou signé : il repasse en brouillon.
     * Les approbations et signatures restent dans l'historique, liées à l'ancienne version, mais ne valent pas pour celle-ci.
     */
    private function reopenAfterContentChange(Document $document, string $what): void
    {
        $before = $document->status;
        if (!in_array($before, ['approved', 'signed'], true)) {
            return;
        }

        $document->update(['status' => 'draft']);
        app(OfficialCopyService::class)->forget($document);
        $this->logAction($document, 'workflow_reset',
            "Nouveau contenu ({$what}) : le document repasse en brouillon. Les approbations et signatures précédentes restent attachées à l'ancienne version ; un nouveau circuit est nécessaire.",
            ['status' => $before], ['status' => 'draft']);

        $creator = $document->creator;
        if ($creator && $creator->id !== Auth::id()) {
            app(NotificationService::class)->notify($creator, 'workflow_reset', 'Document repassé en brouillon',
                "Un nouveau contenu a été déposé sur « {$document->title} » (" . ($before === 'signed' ? 'signé' : 'approuvé') . ") : un nouveau circuit est nécessaire.",
                url("/documents/{$document->id}"), $document);
        }
    }

    // ===========================
    // LEGAL HOLD (Gel d'archive)
    // ===========================

    /**
     * Activer le gel juridique sur un document.
     * Un document sous Legal Hold ne peut être ni modifié, ni supprimé, ni archivé.
     */
    public function enableLegalHold(Document $document, string $reason): bool
    {
        $document->update([
            'legal_hold' => true,
            'legal_hold_at' => now(),
            'legal_hold_by' => Auth::id(),
            'legal_hold_reason' => $reason,
        ]);

        $this->logAction($document, 'legal_hold_enabled', "Gel juridique activé: {$reason}");

        return true;
    }

    /**
     * Désactiver le gel juridique sur un document (admin uniquement).
     */
    public function disableLegalHold(Document $document): bool
    {
        $oldReason = $document->legal_hold_reason;

        $document->update([
            'legal_hold' => false,
            'legal_hold_at' => null,
            'legal_hold_by' => null,
            'legal_hold_reason' => null,
        ]);

        $this->logAction($document, 'legal_hold_disabled', "Gel juridique levé (raison initiale: {$oldReason})");

        return true;
    }

    // ===========================
    // COMPARAISON DE VERSIONS
    // ===========================

    /**
     * Comparer les métadonnées de deux versions d'un document.
     * Retourne un tableau avec les différences (taille, checksum, date, auteur).
     */
    public function compareVersions(Document $document, int $versionA, int $versionB): ?array
    {
        $a = $document->versions()->where('version_number', $versionA)->first();
        $b = $document->versions()->where('version_number', $versionB)->first();

        if (!$a || !$b) {
            return null;
        }

        return [
            'version_a' => [
                'number' => $a->version_number,
                'file_path' => $a->file_path,
                'checksum' => $a->checksum,
                'size' => $a->metadata['size'] ?? null,
                'mime_type' => $a->metadata['mime_type'] ?? null,
                'created_by' => $a->creator?->full_name ?? 'Inconnu',
                'created_at' => $a->created_at->format('d/m/Y H:i'),
                'change_description' => $a->change_description,
            ],
            'version_b' => [
                'number' => $b->version_number,
                'file_path' => $b->file_path,
                'checksum' => $b->checksum,
                'size' => $b->metadata['size'] ?? null,
                'mime_type' => $b->metadata['mime_type'] ?? null,
                'created_by' => $b->creator?->full_name ?? 'Inconnu',
                'created_at' => $b->created_at->format('d/m/Y H:i'),
                'change_description' => $b->change_description,
            ],
            'differences' => [
                'size_diff' => ($b->metadata['size'] ?? 0) - ($a->metadata['size'] ?? 0),
                'checksum_changed' => $a->checksum !== $b->checksum,
                'same_format' => ($a->metadata['mime_type'] ?? '') === ($b->metadata['mime_type'] ?? ''),
            ],
        ];
    }

    // ===========================
    // EXPORT ARCHIVE (ZIP)
    // ===========================

    /**
     * Exporter un document avec toutes ses versions, audit logs et métadonnées dans un ZIP.
     * Retourne le chemin du fichier ZIP temporaire.
     */
    public function exportArchive(Document $document): ?string
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'archive_') . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            return null;
        }

        // 1. Fichier principal (version courante)
        $mainContent = $document->disk()->get($document->file_path);
        if ($mainContent) {
            $ext = pathinfo($document->file_path, PATHINFO_EXTENSION);
            $zip->addFromString("document_courant.{$ext}", $mainContent);
        }

        // 2. Toutes les versions
        $zip->addEmptyDir('versions');
        foreach ($document->versions as $version) {
            try {
                $vContent = $document->disk()->get($version->file_path);
                if ($vContent) {
                    $vExt = pathinfo($version->file_path, PATHINFO_EXTENSION);
                    $zip->addFromString("versions/v{$version->version_number}.{$vExt}", $vContent);
                }
            } catch (\Exception $e) {
                // Version file missing on storage, skip
                continue;
            }
        }

        // 3. Métadonnées du document (JSON)
        $metadata = [
            'reference' => $document->reference,
            'title' => $document->title,
            'status' => $document->status,
            'version_courante' => $document->version,
            'categorie' => $document->category?->name,
            'createur' => $document->creator?->full_name,
            'date_creation' => $document->created_at?->format('d/m/Y H:i'),
            'date_archivage' => $document->archived_at?->format('d/m/Y H:i'),
            'date_expiration' => $document->expires_at?->format('d/m/Y'),
            'retention_years' => $document->retention_years,
            'confidentiel' => $document->is_confidential,
            'legal_hold' => $document->legal_hold,
            'legal_hold_reason' => $document->legal_hold_reason,
            'checksum_sha256' => $document->checksum,
            'tags' => $document->tags,
            'metadata' => $document->metadata,
        ];
        $zip->addFromString('metadata.json', json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 4. Historique des versions (JSON)
        $versionsData = $document->versions->map(fn($v) => [
            'version' => $v->version_number,
            'checksum' => $v->checksum,
            'auteur' => $v->creator?->full_name ?? 'Inconnu',
            'date' => $v->created_at->format('d/m/Y H:i'),
            'description' => $v->change_description,
            'taille' => $v->metadata['size'] ?? null,
        ])->toArray();
        $zip->addFromString('historique_versions.json', json_encode($versionsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 5. Journal d'audit (JSON)
        $auditData = $document->auditLogs->map(fn($log) => [
            'action' => $log->action,
            'description' => $log->description,
            'utilisateur' => $log->user?->full_name ?? 'Système',
            'date' => $log->created_at->format('d/m/Y H:i:s'),
            'ip' => $log->ip_address,
        ])->toArray();
        $zip->addFromString('journal_audit.json', json_encode($auditData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $zip->close();

        // Logger l'export
        $this->logAction($document, 'archive_exported', 'Export ZIP de l\'archive complète');

        return $zipPath;
    }

    // ===========================
    // PURGE PLANIFIÉE CORBEILLE
    // ===========================

    /**
     * Purger les documents dans la corbeille depuis plus de $days jours.
     * Supprime les fichiers sur MinIO et force delete en base.
     */
    public function purgeTrash(int $days = 30): int
    {
        $cutoff = now()->subDays($days);

        $trashedDocuments = Document::onlyTrashed()
            ->where('deleted_at', '<', $cutoff)
            ->get();

        $count = 0;
        foreach ($trashedDocuments as $document) {
            // Ne pas purger les documents sous Legal Hold, ni les documents archivés
            if ($document->isUnderLegalHold() || $document->isArchived()) {
                continue;
            }

            $this->deleteStoredFiles($document);

            // Logger avant suppression définitive
            $this->logAction($document, 'purged', "Purgé de la corbeille (supprimé depuis {$days}+ jours)");

            // Suppression définitive
            $document->forceDelete();
            $count++;
        }

        return $count;
    }

    // ===========================
    // POLITIQUE RÉTENTION PAR CATÉGORIE
    // ===========================

    /**
     * Appliquer la politique de rétention de la catégorie à un document.
     * Si le document n'a pas de retention_years défini, utilise celui de la catégorie.
     */
    public function applyRetentionPolicy(Document $document): void
    {
        if ($document->retention_years > 0 || !$document->category) {
            return; // Le document a déjà sa propre politique
        }

        $categoryRetention = $document->category->default_retention_years;

        if ($categoryRetention && $categoryRetention > 0) {
            // La durée de conservation n'est PAS une date d'échéance : expires_at n'est pas touché
            $document->update(['retention_years' => $categoryRetention]);

            $this->logAction($document, 'retention_applied', "Politique de rétention catégorie appliquée: {$categoryRetention} ans");
        }
    }

    // ===========================
    // MÉTHODES EXISTANTES
    // ===========================

    // Calculer le checksum d'un fichier
    private function calculateChecksum(Document $document, string $filePath): string
    {
        $content = $document->disk()->get($filePath);
        return hash('sha256', $content);
    }

    // Logger une action d'audit
    public function logAction(Document $document, string $action, ?string $description = null, ?array $oldValues = null, ?array $newValues = null): void
    {
        DocumentAuditLog::create([
            'organization_id' => $document->organization_id,
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'user_agent' => app()->runningInConsole() ? 'console' : request()->userAgent(),
        ]);
    }

    // Vérifier l'intégrité d'un document
    public function verifyIntegrity(Document $document): bool
    {
        if (!$document->checksum) {
            return false;
        }

        $currentChecksum = $this->calculateChecksum($document, $document->file_path);
        return hash_equals($document->checksum, $currentChecksum);
    }

    // Supprimer de MinIO le fichier principal et les fichiers de toutes les versions
    public function deleteStoredFiles(Document $document): void
    {
        // Copies PDF/A et copies officielles (actuelles et précédentes)
        $archives = collect(['archives', 'official'])
            ->flatMap(fn ($sub) => $document->disk()->files(trim(dirname($document->file_path) . '/' . $sub, './')))
            ->filter(fn ($path) => str_starts_with(basename($path), $document->reference . '_v'));

        $paths = $document->versions()->pluck('file_path')
            ->push($document->file_path)
            ->push($document->archival_copy_path)
            ->merge($archives)
            // PDF d'aperçu mis en cache par DocumentConverter
            ->merge(app(DocumentConverter::class)->previewPaths($document))
            ->filter()
            ->unique()
            ->all();

        $document->disk()->delete($paths);
    }
}
