<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\DocumentVerification;
use App\Services\WordTemplate;
use Illuminate\Console\Command;

class RegenerateQrCodes extends Command
{
    protected $signature   = 'documents:regenerate-qrcodes {--id= : ID d\'un document spécifique}';
    protected $description = 'Régénère le QR code de vérification des documents DOCX (contenu conservé)';

    public function handle(): int
    {
        $query = Document::with('verification');

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        $documents = $query->get();
        $template  = app(WordTemplate::class);

        $this->info('APP_URL utilisée : ' . config('app.url'));
        $this->info("Traitement de {$documents->count()} document(s)...");

        $bar = $this->output->createProgressBar($documents->count());
        $bar->start();

        $success = 0;
        $errors  = 0;

        foreach ($documents as $document) {
            // Document figé (archivé, gel juridique, en circuit, approuvé ou signé) : son fichier ne doit plus changer,
            // sinon l'empreinte approuvée ou signée ne correspondrait plus
            if ($document->isArchived() || $document->isUnderLegalHold()
                || in_array($document->status, ['review', 'signing', 'approved', 'signed'], true)) {
                $bar->advance();
                continue;
            }
            try {
                if (strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION)) !== 'docx') {
                    $bar->advance();
                    continue;
                }

                if (!$document->disk()->exists($document->file_path)) {
                    $this->newLine();
                    $this->warn("Fichier introuvable : {$document->reference}");
                    $errors++;
                    $bar->advance();
                    continue;
                }

                // Récupérer ou créer le code de vérification
                $verification = $document->verification ?? DocumentVerification::create([
                    'document_id'       => $document->id,
                    'verification_code' => DocumentVerification::generateCode(),
                ]);

                // Remplacer uniquement l'image du QR code : le contenu rédigé reste intact
                $tempOut = tempnam(sys_get_temp_dir(), 'qr_out_') . '.docx';
                file_put_contents($tempOut, $document->disk()->get($document->file_path));
                if ($template->replaceQrCode($tempOut, $verification->verification_code) === 0) {
                    @unlink($tempOut);
                    $this->newLine();
                    $this->warn("Aucun QR code en pied de page : {$document->reference} (ignoré)");
                    $bar->advance();
                    continue;
                }

                $checksum = hash_file('sha256', $tempOut);
                $stream = fopen($tempOut, 'r');
                $document->disk()->put($document->file_path, $stream);
                if (is_resource($stream)) fclose($stream);

                // Le fichier a changé : empreinte mise à jour (sinon le contrôle d'intégrité le signalerait) et tracée
                $old = $document->checksum;
                $document->update(['checksum' => $checksum]);
                \App\Models\DocumentVersion::where('document_id', $document->id)->where('file_path', $document->file_path)->update(['checksum' => $checksum]);
                app(\App\Services\DocumentArchivalService::class)->logAction($document, 'qr_regenerated', 'QR code de vérification régénéré dans le fichier',
                    ['checksum' => $old], ['checksum' => $checksum]);

                @unlink($tempOut);

                $success++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Erreur {$document->reference}: " . $e->getMessage());
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✓ {$success} document(s) mis à jour, {$errors} erreur(s).");

        return self::SUCCESS;
    }
}
