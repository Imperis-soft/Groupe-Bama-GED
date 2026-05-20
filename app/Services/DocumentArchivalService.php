<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\DocumentAuditLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use ZipArchive;

class DocumentArchivalService
{
    // Créer une nouvelle version de document
    public function createVersion(Document $document, string $filePath, string $changeDescription = null): DocumentVersion
    {
        // Calculer le checksum du fichier
        $checksum = $this->calculateChecksum($filePath);

        // Incrémenter la version
        $nextVersion = $document->versions()->max('version_number') + 1 ?? 1;

        $version = DocumentVersion::create([
            'document_id' => $document->id,
            'version_number' => $nextVersion,
            'file_path' => $filePath,
            'checksum' => $checksum,
            'change_description' => $changeDescription,
            'created_by' => Auth::id(),
            'metadata' => [
                'original_name' => basename($filePath),
                'size' => Storage::disk('s3')->size($filePath),
                'mime_type' => Storage::disk('s3')->mimeType($filePath),
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

        return $version;
    }

    // Archiver un document
    public function archiveDocument(Document $document, string $reason = null): bool
    {
        // Vérifier le Legal Hold
        if ($document->isUnderLegalHold()) {
            return false;
        }

        $document->update([
            'status' => 'archived',
            'archived_at' => now(),
        ]);

        $this->logAction($document, 'archived', "Document archivé: {$reason}");

        return true;
    }

    // Restaurer une version spécifique d'un document
    public function restoreVersion(Document $document, int $versionNumber): bool
    {
        // Vérifier le Legal Hold
        if ($document->isUnderLegalHold()) {
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

        return true;
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
        $mainContent = Storage::disk('s3')->get($document->file_path);
        if ($mainContent) {
            $ext = pathinfo($document->file_path, PATHINFO_EXTENSION);
            $zip->addFromString("document_courant.{$ext}", $mainContent);
        }

        // 2. Toutes les versions
        $zip->addEmptyDir('versions');
        foreach ($document->versions as $version) {
            try {
                $vContent = Storage::disk('s3')->get($version->file_path);
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
            // Ne pas purger les documents sous Legal Hold
            if ($document->isUnderLegalHold()) {
                continue;
            }

            // Supprimer le fichier principal
            Storage::disk('s3')->delete($document->file_path);

            // Supprimer les fichiers de toutes les versions
            foreach ($document->versions as $version) {
                Storage::disk('s3')->delete($version->file_path);
            }

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
            $expiresAt = $document->created_at->addYears($categoryRetention);

            $document->update([
                'retention_years' => $categoryRetention,
                'expires_at' => $expiresAt,
            ]);

            $this->logAction($document, 'retention_applied', "Politique de rétention catégorie appliquée: {$categoryRetention} ans");
        }
    }

    // ===========================
    // MÉTHODES EXISTANTES
    // ===========================

    // Calculer le checksum d'un fichier
    private function calculateChecksum(string $filePath): string
    {
        $content = Storage::disk('s3')->get($filePath);
        return hash('sha256', $content);
    }

    // Logger une action d'audit
    public function logAction(Document $document, string $action, string $description = null, array $oldValues = null, array $newValues = null): void
    {
        DocumentAuditLog::create([
            'document_id' => $document->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    // Vérifier l'intégrité d'un document
    public function verifyIntegrity(Document $document): bool
    {
        if (!$document->checksum) {
            return false;
        }

        $currentChecksum = $this->calculateChecksum($document->file_path);
        return hash_equals($document->checksum, $currentChecksum);
    }

    // Nettoyer les documents expirés
    public function cleanupExpiredDocuments(): int
    {
        $expiredDocuments = Document::where('expires_at', '<', now())
            ->where('status', '!=', 'deleted')
            ->where('legal_hold', false) // Ne pas toucher aux documents sous Legal Hold
            ->get();

        $count = 0;
        foreach ($expiredDocuments as $document) {
            // Supprimer physiquement le fichier
            Storage::disk('s3')->delete($document->file_path);

            // Marquer comme supprimé
            $document->update(['status' => 'deleted']);

            $this->logAction($document, 'auto_deleted', 'Supprimé automatiquement (expiration)');

            $count++;
        }

        return $count;
    }
}
