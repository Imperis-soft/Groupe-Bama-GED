<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\TextExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;

/**
 * Lit le texte du fichier courant d'un document (OCR compris) pour la recherche plein texte
 * et l'empreinte de contenu utilisée par la détection des doublons.
 */
class IndexDocumentText implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $documentId;

    public $timeout = 600;

    public function __construct(int $documentId)
    {
        $this->documentId = $documentId;
    }

    public function handle(TextExtractor $extractor)
    {
        $doc = Document::withoutGlobalScope('organization')->find($this->documentId);
        if (! $doc) return;

        $path = $doc->file_path;
        $tmp  = tempnam(sys_get_temp_dir(), 'docidx_');

        try {
            $stream = $doc->disk()->readStream($path);
            if (! $stream) {
                throw new Exception('Impossible d ouvrir le flux distant.');
            }
            $out = fopen($tmp, 'w');
            stream_copy_to_stream($stream, $out);
            fclose($out);
            if (is_resource($stream)) fclose($stream);

            $text = $extractor->fromFile($tmp, pathinfo($path, PATHINFO_EXTENSION));

            $doc->fillContent($text ?: null)->saveQuietly();

        } catch (Exception $e) {
            \Log::error('IndexDocumentText failed: ' . $e->getMessage(), ['document_id' => $this->documentId]);
            \App\Models\SystemEvent::record('warning', 'processing', 'Lecture du texte (OCR / indexation) impossible : ' . $e->getMessage(),
                ['document' => $doc->reference ?? $this->documentId], $doc->organization_id ?? null, hash('sha256', 'index|' . get_class($e) . '|' . ($doc->organization_id ?? '')));
        } finally {
            @unlink($tmp);
        }
    }
}
