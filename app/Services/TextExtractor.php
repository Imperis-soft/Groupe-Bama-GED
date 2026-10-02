<?php

namespace App\Services;

use App\Support\FileType;

/**
 * Texte d'un fichier local, quel que soit son format :
 * Office / OpenDocument (XML), texte brut, PDF (pdftotext, puis OCR si c'est un scan),
 * images (OCR tesseract), anciens formats binaires (via un PDF LibreOffice).
 */
class TextExtractor
{
    // En dessous de ce nombre de caractères utiles par page, un PDF est considéré comme scanné
    private const SCANNED_CHARS_PER_PAGE = 40;

    public function __construct(private DocumentConverter $converter) {}

    public function fromFile(string $file, string $extension): string
    {
        $extension = strtolower($extension);

        $text = match ($extension) {
            'docx'              => $this->fromZipXml($file, ['word/document.xml', 'word/header*.xml', 'word/footer*.xml']),
            'xlsx'              => $this->fromZipXml($file, ['xl/sharedStrings.xml', 'xl/worksheets/sheet*.xml']),
            'pptx'              => $this->fromZipXml($file, ['ppt/slides/slide*.xml', 'ppt/notesSlides/notesSlide*.xml']),
            'odt', 'ods', 'odp' => $this->fromZipXml($file, ['content.xml']),
            'csv', 'txt'        => $this->plainText($file),
            'zip'               => $this->zipEntries($file),
            'pdf'               => $this->fromPdf($file),
            default             => FileType::fromExtension($extension)->family === 'image'
                ? $this->converter->imageToText($file)
                : '',
        };

        // Formats binaires (doc, xls, ppt, rtf…) : passage par un PDF LibreOffice
        if (trim($text) === '' && in_array($extension, ['doc', 'xls', 'ppt', 'rtf'], true)) {
            $text = $this->viaPdf($file, $extension);
        }

        return $this->normalize($text);
    }

    // PDF : couche texte si elle existe, sinon OCR des pages (document scanné)
    public function fromPdf(string $file): string
    {
        $text = $this->converter->pdfToText($file);

        $pages = max(1, substr_count($text, "\f"));
        $useful = mb_strlen(preg_replace('/\s+/u', '', $text) ?? '');
        if ($useful < $pages * self::SCANNED_CHARS_PER_PAGE) {
            $ocr = $this->converter->ocrPdf($file);
            if (mb_strlen(trim($ocr)) > $useful) {
                return $ocr;
            }
        }

        return $text;
    }

    // Texte des fichiers XML d'une archive Office / OpenDocument (motifs de noms acceptés)
    private function fromZipXml(string $file, array $patterns): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($file) !== true) {
            return '';
        }

        $parts = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            foreach ($patterns as $pattern) {
                if (fnmatch($pattern, $name)) {
                    // Les fins de paragraphe / cellule / ligne deviennent des espaces
                    $xml = preg_replace('#</(w:p|a:p|text:p|text:h|si|c|row)>#', "$0 ", (string) $zip->getFromIndex($i));
                    $parts[] = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
                    break;
                }
            }
        }
        $zip->close();

        return implode(' ', $parts);
    }

    private function plainText(string $file): string
    {
        $text = (string) file_get_contents($file, false, null, 0, config('ged.max_indexed_chars', 1000000) * 2);
        if (!mb_check_encoding($text, 'UTF-8')) {
            $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
        }

        return $text;
    }

    // Noms des fichiers contenus dans une archive ZIP
    private function zipEntries(string $file): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($file) !== true) {
            return '';
        }
        $names = [];
        for ($i = 0; $i < min($zip->numFiles, 5000); $i++) {
            $names[] = str_replace(['/', '_', '-', '.'], ' ', (string) $zip->getNameIndex($i));
        }
        $zip->close();

        return implode(' ', $names);
    }

    private function viaPdf(string $file, string $extension): string
    {
        $pdf = $this->converter->localFileToPdf($file, $extension);
        if ($pdf === null) {
            return '';
        }
        $tmp = tempnam(sys_get_temp_dir(), 'ged_txt_');
        try {
            file_put_contents($tmp, $pdf);
            return $this->fromPdf($tmp);
        } finally {
            @unlink($tmp);
        }
    }

    // Espaces compactés, UTF-8 valide, longueur bornée
    private function normalize(string $text): string
    {
        $text = mb_scrub($text, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        return mb_substr($text, 0, config('ged.max_indexed_chars', 1000000));
    }
}
