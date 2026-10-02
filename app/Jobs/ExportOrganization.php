<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentAuditLog;
use App\Models\EliminationRecord;
use App\Models\Organization;
use App\Models\OrganizationExport;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;
use ZipArchive;

/**
 * Export complet d'une entreprise dans un ZIP :
 *   LISEZMOI.txt
 *   documents.csv                         métadonnées (ouvrable dans Excel)
 *   journal-audit.csv                     journal d'audit complet, avec les empreintes de la chaîne
 *   documents/<catégorie>/<sous-catégorie>/<RÉF> - <titre>.<ext>   version courante
 *   versions/<RÉF>/v<N>.<ext>             versions antérieures
 *   proces-verbaux/<PV>.pdf               procès-verbaux d'élimination
 * Le ZIP est déposé dans l'espace de l'entreprise (exports/) et reste téléchargeable quelques jours.
 */
class ExportOrganization implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;

    public function __construct(public int $exportId) {}

    public function handle(NotificationService $notifications): void
    {
        $export = OrganizationExport::withoutGlobalScopes()->find($this->exportId);
        if (!$export) {
            return;
        }
        $organization = Organization::findOrFail($export->organization_id);
        $export->update(['status' => 'running']);

        $workDir = sys_get_temp_dir() . '/ged_export_' . Str::random(12);
        @mkdir($workDir, 0700, true);
        $zipPath = "{$workDir}/export.zip";

        try {
            $count = Tenant::run($organization, fn () => $this->build($organization, $zipPath, $workDir));

            $path = $organization->exportsDir() . 'export-' . Str::slug($organization->name) . '-' . now()->format('Ymd-His') . '.zip';
            $stream = fopen($zipPath, 'r');
            $organization->disk()->put($path, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            $export->update([
                'status'          => 'done',
                'path'            => $path,
                'size'            => filesize($zipPath),
                'checksum'        => hash_file('sha256', $zipPath),
                'documents_count' => $count,
                'expires_at'      => now()->addDays(config('ged.export_days', 7)),
            ]);

            if ($requester = User::withoutGlobalScopes()->find($export->requested_by)) {
                Tenant::run($organization, fn () => $notifications->notify($requester, 'export_ready', 'Export prêt',
                    "L'export complet de « {$organization->name} » ({$count} document(s)) est prêt à télécharger pendant " . config('ged.export_days', 7) . ' jours.',
                    $requester->isSuperAdmin() ? route('super.organizations.show', $organization) : route('retention.index', ['tab' => 'integrity'])));
            }
        } catch (Throwable $e) {
            report($e);
            $export->update(['status' => 'failed', 'error' => Str::limit($e->getMessage(), 1000)]);
        } finally {
            $this->removeDirectory($workDir);
        }
    }

    private function build(Organization $organization, string $zipPath, string $workDir): int
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Impossible de créer le fichier ZIP.');
        }
        $disk = $organization->disk();

        $categories = Category::withoutGlobalScopes()->where('organization_id', $organization->id)->get()->keyBy('id');
        $folder = function (?int $categoryId) use ($categories) {
            $parts = [];
            for ($id = $categoryId, $depth = 0; $id && $categories->has($id) && $depth < 50; $depth++) {
                array_unshift($parts, $this->safeName($categories[$id]->name));
                $id = $categories[$id]->parent_id;
            }
            return $parts ? implode('/', $parts) : 'Sans catégorie';
        };

        $csv = fopen("{$workDir}/documents.csv", 'w');
        fwrite($csv, "\xEF\xBB\xBF");
        fputcsv($csv, ['Référence', 'Titre', 'Catégorie', 'Statut', 'Confidentiel', 'Déposé le', 'Archivé le', 'Échéance',
            'Conservation (ans)', 'Fin de conservation', 'Version', 'Empreinte SHA-256', 'Fichier dans l\'export', 'Dans la corbeille'], ';');

        $count = 0;
        $documents = Document::withoutGlobalScope('organization')->withTrashed()
            ->where('organization_id', $organization->id)->with('versions')->orderBy('id')->cursor();

        foreach ($documents as $document) {
            $ext  = pathinfo($document->file_path, PATHINFO_EXTENSION) ?: 'bin';
            $name = 'documents/' . $folder($document->category_id) . '/' . $this->safeName("{$document->reference} - {$document->title}") . ".{$ext}";
            $inZip = $this->addFromDisk($zip, $disk, $document->file_path, $name, $workDir) ? $name : 'FICHIER INTROUVABLE';

            if ($document->archival_copy_path) {
                $this->addFromDisk($zip, $disk, $document->archival_copy_path,
                    'documents/' . $folder($document->category_id) . '/' . $this->safeName("{$document->reference} - {$document->title}") . ' (PDF-A).pdf', $workDir);
            }

            foreach ($document->versions as $version) {
                if ($version->file_path && $version->file_path !== $document->file_path) {
                    $vExt = pathinfo($version->file_path, PATHINFO_EXTENSION) ?: 'bin';
                    $this->addFromDisk($zip, $disk, $version->file_path, "versions/{$document->reference}/v{$version->version_number}.{$vExt}", $workDir);
                }
            }

            fputcsv($csv, [
                $document->reference, $document->title, $folder($document->category_id), $document->status,
                $document->is_confidential ? 'oui' : 'non', $document->created_at?->format('d/m/Y'), $document->archived_at?->format('d/m/Y'),
                $document->expires_at?->format('d/m/Y'), $document->retention_years, $document->retention_permanent ? 'définitive' : $document->retention_until?->format('d/m/Y'),
                $document->version, $document->checksum, $inZip, $document->trashed() ? 'oui' : 'non',
            ], ';');
            $count++;
        }
        fclose($csv);
        $zip->addFile("{$workDir}/documents.csv", 'documents.csv');

        $audit = fopen("{$workDir}/journal-audit.csv", 'w');
        fwrite($audit, "\xEF\xBB\xBF");
        fputcsv($audit, ['N°', 'Date', 'Référence', 'Document', 'Utilisateur', 'Action', 'Description', 'Adresse IP', 'Empreinte', 'Empreinte précédente'], ';');
        foreach (DocumentAuditLog::withoutGlobalScopes()->where('organization_id', $organization->id)->orderBy('sequence')->orderBy('id')->cursor() as $log) {
            fputcsv($audit, [$log->sequence, $log->created_at?->format('d/m/Y H:i:s'), $log->document_reference, $log->document_title,
                $log->user_name ?? 'Système', actionLabel($log->action), $log->description, $log->ip_address, $log->hash, $log->previous_hash], ';');
        }
        fclose($audit);
        $zip->addFile("{$workDir}/journal-audit.csv", 'journal-audit.csv');

        foreach (EliminationRecord::withoutGlobalScopes()->where('organization_id', $organization->id)->whereNotNull('pdf_path')->get() as $record) {
            $this->addFromDisk($zip, $disk, $record->pdf_path, "proces-verbaux/{$record->number}.pdf", $workDir);
        }

        $zip->addFromString('LISEZMOI.txt', implode("\r\n", [
            "Export complet — {$organization->name}",
            'Généré le ' . now()->format('d/m/Y à H:i') . ' par ' . config('saas.platform_name') . " ({$count} document(s)).",
            '',
            'documents.csv       : liste des documents et de leurs informations (séparateur « ; », ouvrable dans Excel).',
            'journal-audit.csv   : journal d\'audit complet. Chaque ligne porte l\'empreinte de la précédente (chaîne de preuve).',
            'documents/          : version courante de chaque document, rangée par catégorie.',
            'versions/           : versions antérieures, par référence de document.',
            'proces-verbaux/     : procès-verbaux d\'élimination.',
            '',
            'Les empreintes SHA-256 permettent de vérifier qu\'un fichier n\'a pas été modifié (ex. : certutil -hashfile fichier SHA256).',
        ]));

        if (!$zip->close()) {
            throw new \RuntimeException('Impossible de finaliser le fichier ZIP.');
        }

        return $count;
    }

    // Copie un fichier du stockage dans le dossier de travail puis l'ajoute au ZIP (lu en flux, sans tout charger en mémoire)
    private function addFromDisk(ZipArchive $zip, $disk, string $path, string $name, string $workDir): bool
    {
        try {
            $stream = $disk->readStream($path);
        } catch (Throwable) {
            $stream = null;
        }
        if (!$stream) {
            return false;
        }
        $local = $workDir . '/' . Str::random(20);
        $out = fopen($local, 'w');
        stream_copy_to_stream($stream, $out);
        fclose($out);
        if (is_resource($stream)) {
            fclose($stream);
        }

        return $zip->addFile($local, $name);
    }

    // Nom de fichier ou de dossier sûr sous Windows, macOS et Linux
    private function safeName(string $name): string
    {
        $name = trim(preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/u', '-', $name), " .-");

        return Str::limit($name ?: 'sans-nom', 120, '');
    }

    private function removeDirectory(string $dir): void
    {
        foreach (glob("{$dir}/*") ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($dir);
    }
}
