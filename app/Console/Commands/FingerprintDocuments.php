<?php

namespace App\Console\Commands;

use App\Jobs\IndexDocumentText;
use App\Models\Document;
use Illuminate\Console\Command;

/**
 * Calcule l'empreinte de contenu des documents existants (détection des doublons).
 * Les documents dont le texte n'a jamais été lu (ex. scans d'avant l'OCR) sont relus avec --reindex.
 */
class FingerprintDocuments extends Command
{
    protected $signature = 'documents:fingerprint
                            {--reindex : Relire (OCR compris) les documents sans texte}
                            {--force : Recalculer aussi les empreintes déjà présentes}';

    protected $description = 'Calcule l\'empreinte de contenu des documents pour détecter les doublons à l\'import';

    public function handle(): int
    {
        $query = Document::withoutGlobalScope('organization')
            ->when(!$this->option('force'), fn ($q) => $q->whereNull('content_simhash'));

        $fingerprinted = 0;
        $queued = 0;

        $query->chunkById(200, function ($documents) use (&$fingerprinted, &$queued) {
            foreach ($documents as $document) {
                if (filled($document->content_text)) {
                    $document->fillContent($document->content_text)->saveQuietly();
                    $fingerprinted++;
                } elseif ($this->option('reindex')) {
                    IndexDocumentText::dispatch($document->id);
                    $queued++;
                }
            }
        });

        $this->info("{$fingerprinted} document(s) mis à jour.");
        if ($queued) {
            $this->info("{$queued} document(s) sans texte envoyés en relecture (OCR) dans la file d'attente.");
        } elseif (!$this->option('reindex')) {
            $this->line('Astuce : --reindex relit aussi les documents sans texte (scans).');
        }

        return self::SUCCESS;
    }
}
