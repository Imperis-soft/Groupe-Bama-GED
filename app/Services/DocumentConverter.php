<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * Conversions côté serveur :
 * - en PDF via LibreOffice (aperçu des formats non lisibles par le navigateur) ;
 * - en texte via pdftotext / tesseract (indexation plein texte, OCR des scans).
 *
 * Les PDF d'aperçu sont mis en cache dans le stockage de l'entreprise (previews/…).
 */
class DocumentConverter
{
    // Chemin d'un binaire configuré ou trouvé dans le PATH (null si absent)
    public function binary(string $name): ?string
    {
        $configured = config("ged.{$name}_path");
        if ($configured) {
            return is_executable($configured) ? $configured : null;
        }

        $candidates = match ($name) {
            'libreoffice' => ['soffice', 'libreoffice', '/Applications/LibreOffice.app/Contents/MacOS/soffice'],
            default       => [$name],
        };
        foreach ($candidates as $candidate) {
            if (str_starts_with($candidate, '/')) {
                if (is_executable($candidate)) {
                    return $candidate;
                }
                continue;
            }
            $process = new Process(['which', $candidate]);
            $process->run();
            if ($process->isSuccessful() && ($path = trim($process->getOutput()))) {
                return $path;
            }
        }

        return null;
    }

    public function canConvertToPdf(): bool
    {
        return $this->binary('libreoffice') !== null;
    }

    // Emplacement du PDF d'aperçu en cache (version courante par défaut)
    public function previewPath(Document $document, ?int $version = null): string
    {
        $dir = dirname($document->file_path);

        return ($dir === '.' ? '' : $dir . '/') . 'previews/' . $document->reference . '_v' . ($version ?? $document->version) . '.pdf';
    }

    // Aperçus en cache de toutes les versions (à supprimer avec le document)
    public function previewPaths(Document $document): array
    {
        return array_map(fn ($v) => $this->previewPath($document, $v), range(1, max(1, (int) $document->version)));
    }

    /**
     * PDF d'aperçu du document (converti à la demande puis mis en cache).
     * Retourne le chemin sur le disque de l'entreprise, ou null si la conversion est impossible.
     */
    public function pdfPreview(Document $document): ?string
    {
        $disk = $document->disk();
        $cached = $this->previewPath($document);
        if ($disk->exists($cached)) {
            return $cached;
        }

        $pdf = $this->convertFileToPdf($document);
        if ($pdf === null) {
            return null;
        }

        $disk->put($cached, $pdf);

        return $cached;
    }

    // Convertit le fichier courant du document en PDF (contenu binaire), sans cache
    public function convertFileToPdf(Document $document): ?string
    {
        if (!$this->binary('libreoffice')) {
            return null;
        }

        $extension = strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION));
        $tmp = tempnam(sys_get_temp_dir(), 'ged_src_');

        try {
            $stream = $document->disk()->readStream($document->file_path);
            if (!$stream) {
                return null;
            }
            $out = fopen($tmp, 'w');
            stream_copy_to_stream($stream, $out);
            fclose($out);
            if (is_resource($stream)) {
                fclose($stream);
            }

            return $this->localFileToPdf($tmp, $extension, ['document_id' => $document->id]);
        } catch (\Throwable $e) {
            Log::warning('Conversion PDF échouée: ' . $e->getMessage(), ['document_id' => $document->id]);
            return null;
        } finally {
            @unlink($tmp);
        }
    }

    /**
     * Copie d'archivage au format PDF/A-2 (format conçu pour rester lisible à long terme), contenu binaire.
     * - PDF : réécrit par Ghostscript ;
     * - bureautique et images : exportés par LibreOffice avec l'option PDF/A.
     * null si l'outil nécessaire est absent ou si le format ne s'y prête pas (archive ZIP, texte brut…).
     */
    public function toPdfA(string $file, string $extension): ?string
    {
        $extension = strtolower($extension);
        $family = \App\Support\FileType::fromExtension($extension)->family;

        if ($family === 'pdf') {
            return $this->pdfToPdfA($file);
        }

        $filter = match ($family) {
            'word', 'text' => 'writer_pdf_Export',
            'excel'        => 'calc_pdf_Export',
            'powerpoint'   => 'impress_pdf_Export',
            'image'        => 'draw_pdf_Export',
            default        => null,
        };

        return $filter ? $this->localFileToPdf($file, $extension, [], $filter . ':{"SelectPdfVersion":{"type":"long","value":"2"}}') : null;
    }

    // Fichier local en PDF (contenu binaire) : tel quel si c'est déjà un PDF, sinon via LibreOffice
    public function anyFileToPdf(string $file, string $extension): ?string
    {
        if (\App\Support\FileType::fromExtension(strtolower($extension))->family === 'pdf') {
            return file_get_contents($file) ?: null;
        }

        return $this->localFileToPdf($file, strtolower($extension));
    }

    // Assemble plusieurs PDF en un seul via Ghostscript (contenu binaire), null si l'outil est absent
    public function mergePdfs(array $files): ?string
    {
        $gs = $this->binary('gs');
        if (!$gs) {
            return null;
        }
        $output = tempnam(sys_get_temp_dir(), 'ged_merge_');
        try {
            $process = new Process(array_merge([$gs, '-dBATCH', '-dNOPAUSE', '-dQUIET', '-sDEVICE=pdfwrite', '-sOutputFile=' . $output], $files));
            $process->setTimeout(config('ged.conversion_timeout', 120));
            $process->run();

            return $process->isSuccessful() && filesize($output) > 0 ? file_get_contents($output) : null;
        } finally {
            @unlink($output);
        }
    }

    private function pdfToPdfA(string $file): ?string
    {
        $gs = $this->binary('gs');
        if (!$gs) {
            return null;
        }
        $output = tempnam(sys_get_temp_dir(), 'ged_pdfa_');
        try {
            $process = new Process([$gs, '-dPDFA=2', '-dBATCH', '-dNOPAUSE', '-dNOOUTERSAVE', '-dQUIET',
                '-sColorConversionStrategy=RGB', '-dPDFACompatibilityPolicy=1', '-sDEVICE=pdfwrite',
                '-sOutputFile=' . $output, $file]);
            $process->setTimeout(config('ged.conversion_timeout', 120));
            $process->run();

            return $process->isSuccessful() && filesize($output) > 0 ? file_get_contents($output) : null;
        } finally {
            @unlink($output);
        }
    }

    // Convertit un fichier local (doc, xls, ppt, rtf…) en PDF via LibreOffice (contenu binaire)
    public function localFileToPdf(string $file, string $extension, array $context = [], string $pdfFilter = ''): ?string
    {
        $soffice = $this->binary('libreoffice');
        if (!$soffice) {
            return null;
        }

        $workDir = sys_get_temp_dir() . '/ged_convert_' . Str::random(12);
        @mkdir($workDir, 0700, true);
        $input = "{$workDir}/source.{$extension}";

        try {
            copy($file, $input);

            // Profil LibreOffice isolé : évite les blocages entre conversions simultanées
            $process = new Process([
                $soffice,
                '-env:UserInstallation=file://' . $workDir . '/profile',
                '--headless', '--norestore', '--convert-to', $pdfFilter ? 'pdf:' . $pdfFilter : 'pdf', '--outdir', $workDir, $input,
            ]);
            $process->setTimeout(config('ged.conversion_timeout', 120));
            $process->run();

            $output = "{$workDir}/source.pdf";
            if (!$process->isSuccessful() || !is_file($output)) {
                Log::warning('Conversion PDF échouée', $context + ['error' => $process->getErrorOutput()]);
                \App\Models\SystemEvent::record('warning', 'processing', 'Conversion LibreOffice échouée (' . $extension . ')',
                    $context + ['error' => \Illuminate\Support\Str::limit($process->getErrorOutput(), 500)], null, hash('sha256', 'convert|' . $extension));
                return null;
            }

            return file_get_contents($output);
        } catch (\Throwable $e) {
            Log::warning('Conversion PDF échouée: ' . $e->getMessage(), $context);
            return null;
        } finally {
            $this->removeDirectory($workDir);
        }
    }

    // OCR d'un PDF scanné : pages rendues en images (pdftoppm) puis lues par tesseract
    public function ocrPdf(string $pdfFile, ?int $maxPages = null): string
    {
        $pdftoppm = $this->binary('pdftoppm');
        if (!$pdftoppm || !$this->binary('tesseract')) {
            return '';
        }

        $workDir = sys_get_temp_dir() . '/ged_ocr_' . Str::random(12);
        @mkdir($workDir, 0700, true);

        try {
            $process = new Process([
                $pdftoppm, '-r', '200', '-gray', '-png',
                '-f', '1', '-l', (string) ($maxPages ?? config('ged.ocr_max_pages', 30)),
                $pdfFile, "{$workDir}/page",
            ]);
            $process->setTimeout(config('ged.conversion_timeout', 120));
            $process->run();
            if (!$process->isSuccessful()) {
                return '';
            }

            $pages = glob("{$workDir}/page*.png") ?: [];
            natsort($pages);

            return implode("\n", array_map(fn ($page) => $this->imageToText($page), $pages));
        } finally {
            $this->removeDirectory($workDir);
        }
    }

    // Texte d'un fichier PDF local (pdftotext), chaîne vide si indisponible
    public function pdfToText(string $pdfFile): string
    {
        $pdftotext = $this->binary('pdftotext');
        if (!$pdftotext) {
            return '';
        }
        $process = new Process([$pdftotext, '-enc', 'UTF-8', $pdfFile, '-']);
        $process->setTimeout(120);
        $process->run();

        return $process->isSuccessful() ? $process->getOutput() : '';
    }

    // Texte d'une image locale par OCR (tesseract, français + anglais si disponibles)
    public function imageToText(string $imageFile): string
    {
        $tesseract = $this->binary('tesseract');
        if (!$tesseract) {
            return '';
        }
        foreach (['fra+eng', 'eng'] as $languages) {
            $process = new Process([$tesseract, $imageFile, 'stdout', '-l', $languages]);
            $process->setTimeout(180);
            $process->run();
            if ($process->isSuccessful()) {
                return $process->getOutput();
            }
        }

        return '';
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }
}
