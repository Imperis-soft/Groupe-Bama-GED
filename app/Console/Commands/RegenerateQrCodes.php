<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\DocumentVerification;
use Endroid\QrCode\QrCode as EndroidQrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;

class RegenerateQrCodes extends Command
{
    protected $signature   = 'documents:regenerate-qrcodes {--id= : ID d\'un document spécifique}';
    protected $description = 'Régénère les QR codes de vérification dans les documents DOCX';

    public function handle(): int
    {
        $query = Document::with('verification');

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        $documents = $query->get();
        $appUrl    = rtrim(config('app.url'), '/');

        $this->info("APP_URL utilisée : {$appUrl}");
        $this->info("Traitement de {$documents->count()} document(s)...");

        $bar = $this->output->createProgressBar($documents->count());
        $bar->start();

        $success = 0;
        $errors  = 0;

        foreach ($documents as $document) {
            try {
                // Récupérer ou créer le code de vérification
                $verification = $document->verification;
                if (!$verification) {
                    $verification = DocumentVerification::create([
                        'document_id'       => $document->id,
                        'verification_code' => Str::random(32),
                    ]);
                }

                $verificationUrl = $appUrl . '/verify/' . $verification->verification_code;

                // Télécharger le DOCX depuis MinIO (pour vérifier qu'il existe)
                if (!Storage::disk('s3')->exists($document->file_path)) {
                    $this->newLine();
                    $this->warn("Fichier introuvable : {$document->reference}");
                    $errors++;
                    $bar->advance();
                    continue;
                }

                // Générer le nouveau QR code
                $qrCode   = EndroidQrCode::create($verificationUrl)->setSize(100);
                $qrWriter = new PngWriter();
                $qrResult = $qrWriter->write($qrCode);
                $qrPath   = tempnam(sys_get_temp_dir(), 'qr_img_') . '.png';
                file_put_contents($qrPath, $qrResult->getString());

                // Recréer un DOCX propre avec le bon QR code
                $phpWord = new \PhpOffice\PhpWord\PhpWord();
                $phpWord->setDefaultFontName('Arial');
                $phpWord->setDefaultFontSize(11);
                $section = $phpWord->addSection();

                // En-tête
                $header = $section->addHeader();
                $header->addText("GROUPE BAMA", ['bold' => true, 'size' => 18, 'color' => 'FF6600'], ['alignment' => 'center']);
                $header->addText("Système de Gestion Documentaire", ['size' => 10], ['alignment' => 'center']);

                // Infos doc
                $table = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 80]);
                $table->addRow();
                $table->addCell(4000)->addText("Référence:", ['bold' => true]);
                $table->addCell(6000)->addText($document->reference, ['bold' => true, 'size' => 12]);
                $table->addCell(3000)->addText("Version: " . $document->version, ['bold' => true]);
                $table->addRow();
                $table->addCell(4000)->addText("Titre:", ['bold' => true]);
                $table->addCell(6000)->addText($document->title);
                $table->addCell(3000)->addText("Statut: " . ucfirst($document->status), ['bold' => true]);

                $section->addTextBreak(1);
                $section->addText("Contenu du document :", ['bold' => true, 'size' => 12]);
                $section->addTextBreak(1);
                $section->addText("Document mis à jour — QR code régénéré.", ['italic' => true]);

                // Footer avec nouveau QR code
                $footer = $section->addFooter();
                $footer->addImage($qrPath, ['width' => 30, 'height' => 30, 'alignment' => 'center']);

                // Sauvegarder
                $tempOut = tempnam(sys_get_temp_dir(), 'qr_out_') . '.docx';
                $writer  = IOFactory::createWriter($phpWord, 'Word2007');
                $writer->save($tempOut);

                // Uploader vers MinIO
                $stream = fopen($tempOut, 'r');
                Storage::disk('s3')->put($document->file_path, $stream);
                if (is_resource($stream)) fclose($stream);

                // Nettoyage
                @unlink($tempOut);
                @unlink($qrPath);

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
