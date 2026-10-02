<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\EliminationRecord;
use App\Models\IntegrityCheck;
use App\Models\Organization;
use App\Support\AuditChain;
use Throwable;

/**
 * Contrôle d'intégrité d'une entreprise :
 * - chaque fichier (version courante et anciennes versions) est relu et comparé à son empreinte SHA-256 ;
 * - chaque procès-verbal d'élimination aussi ;
 * - la chaîne du journal d'audit est vérifiée.
 * Un fichier sans empreinte reçoit la sienne (« référence ») et sera contrôlé ensuite.
 */
class IntegrityService
{
    public function __construct(private DocumentArchivalService $archival) {}

    public function check(Organization $organization): IntegrityCheck
    {
        $disk = $organization->disk();
        $problems = [];
        $checked = $failed = $baselines = 0;

        $documents = Document::withoutGlobalScope('organization')->withTrashed()
            ->where('organization_id', $organization->id)->with('versions')->cursor();

        foreach ($documents as $document) {
            // Fichiers du document : [chemin => [empreinte attendue, version éventuelle]]
            $files = [$document->file_path => [$document->checksum, null]];
            if ($document->archival_copy_path) {
                $files[$document->archival_copy_path] = [$document->archival_copy_checksum, null];
            }
            foreach ($document->versions as $version) {
                $files[$version->file_path] ??= [$version->checksum, $version];
                if ($version->file_path === $document->file_path && !$files[$version->file_path][0]) {
                    $files[$version->file_path] = [$version->checksum, $version];
                }
            }

            $status = 'ok';
            foreach ($files as $path => [$expected, $version]) {
                if (!$path) {
                    continue;
                }
                $checked++;
                $actual = $this->hashFile($disk, $path);

                if ($actual === null) {
                    $status = 'missing';
                    $failed++;
                    $problems[] = "{$document->reference} : fichier introuvable ({$path}).";
                    continue;
                }
                if (!$expected) {
                    // Pas encore d'empreinte : on enregistre celle d'aujourd'hui comme référence
                    $baselines++;
                    if ($path === $document->file_path) {
                        $document->checksum = $actual;
                    }
                    $version?->forceFill(['checksum' => $actual])->saveQuietly();
                    continue;
                }
                if (!hash_equals($expected, $actual)) {
                    $status = $status === 'missing' ? 'missing' : 'altered';
                    $failed++;
                    $problems[] = "{$document->reference} : fichier modifié hors de la plateforme ({$path}).";
                }
            }

            if ($status !== 'ok' && $document->integrity_status !== $status) {
                $this->archival->logAction($document, 'integrity_failed',
                    $status === 'missing' ? 'Contrôle d\'intégrité : fichier introuvable' : 'Contrôle d\'intégrité : fichier modifié hors de la plateforme');
            }
            $document->integrity_status = $status;
            $document->integrity_checked_at = now();
            $document->saveQuietly();
        }

        foreach (EliminationRecord::withoutGlobalScopes()->where('organization_id', $organization->id)->whereNotNull('pdf_path')->get() as $record) {
            $checked++;
            $actual = $this->hashFile($disk, $record->pdf_path);
            if ($actual === null || !hash_equals((string) $record->pdf_checksum, $actual)) {
                $failed++;
                $problems[] = "Procès-verbal {$record->number} : " . ($actual === null ? 'fichier introuvable.' : 'fichier modifié.');
            }
        }

        $chain = AuditChain::verify($organization->id);
        foreach ($chain as $problem) {
            $problems[] = "Journal d'audit — {$problem}";
        }

        // Journal système : alerte tant que des anomalies subsistent, résolue automatiquement au contrôle suivant s'il est bon
        $fingerprint = hash('sha256', "integrity|{$organization->id}");
        if ($failed || $chain) {
            \App\Models\SystemEvent::record('critical', 'integrity',
                "Intégrité — {$organization->name} : {$failed} fichier(s) en défaut" . ($chain ? ', journal d\'audit altéré' : ''),
                ['anomalies' => array_slice($problems, 0, 20)], $organization->id, $fingerprint);
        } else {
            \App\Models\SystemEvent::resolveFingerprint($fingerprint);
        }

        return IntegrityCheck::create([
            'organization_id' => $organization->id,
            'files_checked'   => $checked,
            'files_failed'    => $failed,
            'baselines'       => $baselines,
            'audit_chain_ok'  => $chain === [],
            'problems'        => array_slice($problems, 0, 500),
        ]);
    }

    // Empreinte SHA-256 d'un fichier du stockage, lue en flux (null si absent ou illisible)
    private function hashFile($disk, string $path): ?string
    {
        try {
            if (!$disk->exists($path)) {
                return null;
            }
            $stream = $disk->readStream($path);
            if (!$stream) {
                return null;
            }
            $context = hash_init('sha256');
            hash_update_stream($context, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            return hash_final($context);
        } catch (Throwable) {
            return null;
        }
    }
}
