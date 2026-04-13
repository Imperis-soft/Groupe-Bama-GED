<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\DocumentAuditLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

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