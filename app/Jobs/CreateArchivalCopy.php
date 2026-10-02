<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\DocumentArchivalService;
use App\Services\DocumentConverter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * À l'archivage : crée une copie PDF/A du document à côté de l'original (qui reste inchangé).
 * Sans l'outil nécessaire (LibreOffice / Ghostscript), le document reste archivé dans son format d'origine.
 */
class CreateArchivalCopy implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    public function __construct(public int $documentId) {}

    public function handle(DocumentConverter $converter, DocumentArchivalService $archival): void
    {
        $document = Document::withoutGlobalScope('organization')->find($this->documentId);
        if (!$document || !$document->isArchived() || $document->archival_copy_path) {
            return;
        }

        $extension = strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION));
        $tmp = tempnam(sys_get_temp_dir(), 'ged_arch_');
        try {
            $stream = $document->disk()->readStream($document->file_path);
            if (!$stream) {
                return;
            }
            $out = fopen($tmp, 'w');
            stream_copy_to_stream($stream, $out);
            fclose($out);
            if (is_resource($stream)) {
                fclose($stream);
            }

            $pdf = $converter->toPdfA($tmp, $extension);
            if ($pdf === null) {
                $archival->logAction($document, 'archival_copy_skipped', "Copie PDF/A non créée (format « {$extension} » ou outil de conversion indisponible) : l'original est archivé tel quel.");
                return;
            }

            $dir  = dirname($document->file_path);
            $path = ($dir === '.' ? '' : $dir . '/') . "archives/{$document->reference}_v{$document->version}_pdfa.pdf";
            $document->disk()->put($path, $pdf);

            $document->forceFill(['archival_copy_path' => $path, 'archival_copy_checksum' => hash('sha256', $pdf)])->saveQuietly();
            $archival->logAction($document, 'archival_copy_created', 'Copie d\'archivage PDF/A créée', null, ['checksum' => $document->archival_copy_checksum]);
        } catch (Throwable $e) {
            report($e);
        } finally {
            @unlink($tmp);
        }
    }
}
