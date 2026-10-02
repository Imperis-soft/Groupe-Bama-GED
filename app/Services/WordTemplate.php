<?php

namespace App\Services;

use App\Models\DocumentVerification;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use RuntimeException;
use ZipArchive;

/**
 * Modèle Word des documents créés dans la GED : en-tête de l'entreprise, titre, zone de rédaction
 * libre et pied de page de vérification (QR code + code manuel).
 *
 * Le QR code est la seule image des pieds de page : la régénération le remplace sans toucher au contenu.
 */
class WordTemplate
{
    private const FONT    = 'Arial';
    private const INK     = '1F2937';
    private const MUTED   = '6B7280';
    private const ACCENT  = 'EA580C';
    private const LINE    = 'E5E7EB';
    private const DANGER  = 'B91C1C';

    // Largeur utile d'une page A4 avec 2 cm de marges
    private const WIDTH_CM = 17.0;

    /**
     * Génère le fichier Word et retourne son chemin temporaire (à supprimer par l'appelant).
     *
     * @param array{
     *   organization: ?\App\Models\Organization, reference: string, title: string,
     *   category: ?string, author: string, confidential: bool, verification_code: string
     * } $data
     */
    public function create(array $data): string
    {
        $org     = $data['organization'];
        $orgName = $org?->name ?? config('saas.platform_name');

        $word = new PhpWord();
        $word->setDefaultFontName(self::FONT);
        $word->setDefaultFontSize(10.5);
        $word->setDefaultParagraphStyle(['spaceAfter' => Converter::pointToTwip(6), 'spacing' => Converter::pointToTwip(1.5), 'lineHeight' => 1.2]);
        $word->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language('fr-FR'));

        // Styles de titres proposés dans Word pour la rédaction
        $word->addTitleStyle(1, ['size' => 14, 'bold' => true, 'color' => self::INK], ['spaceBefore' => Converter::pointToTwip(14), 'spaceAfter' => Converter::pointToTwip(6), 'keepNext' => true]);
        $word->addTitleStyle(2, ['size' => 12, 'bold' => true, 'color' => self::ACCENT], ['spaceBefore' => Converter::pointToTwip(10), 'spaceAfter' => Converter::pointToTwip(4), 'keepNext' => true]);
        $word->addTitleStyle(3, ['size' => 11, 'bold' => true, 'color' => self::INK], ['spaceBefore' => Converter::pointToTwip(8), 'spaceAfter' => Converter::pointToTwip(3), 'keepNext' => true]);

        $info = $word->getDocInfo();
        $info->setCreator($data['author']);
        $info->setLastModifiedBy($data['author']);
        $info->setCompany($orgName);
        $info->setTitle($data['title']);
        $info->setSubject($data['reference']);
        $info->setCategory($data['category'] ?? '');
        $info->setKeywords($data['reference'] . ($data['confidential'] ? ', confidentiel' : ''));
        $info->setCustomProperty('Référence GED', $data['reference']);

        $section = $word->addSection([
            'paperSize'    => 'A4',
            'marginTop'    => $this->cm(2.6),
            'marginBottom' => $this->cm(3.6),
            'marginLeft'   => $this->cm(2),
            'marginRight'  => $this->cm(2),
            'headerHeight' => $this->cm(1),
            'footerHeight' => $this->cm(0.8),
        ]);

        $this->header($section, $org, $orgName, $data);
        $this->titleBlock($section, $data);
        $this->body($section);

        $qrPath = $this->qrPng($this->verificationUrl($data['verification_code']));
        try {
            $this->footer($section, $qrPath, $data);

            $out = tempnam(sys_get_temp_dir(), 'ged_docx_');
            IOFactory::createWriter($word, 'Word2007')->save($out);
            return $out;
        } finally {
            @unlink($qrPath);
        }
    }

    /**
     * Remplace le QR code des pieds de page d'un fichier Word existant (contenu intact).
     * Retourne le nombre d'images remplacées.
     */
    public function replaceQrCode(string $docxPath, string $verificationCode): int
    {
        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) {
            throw new RuntimeException('Fichier Word illisible.');
        }

        $png      = file_get_contents($qrPath = $this->qrPng($this->verificationUrl($verificationCode)));
        @unlink($qrPath);
        $replaced = 0;

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (!preg_match('#^word/_rels/footer\d*\.xml\.rels$#', $name)) {
                    continue;
                }
                preg_match_all('#Type="[^"]+/image"[^>]*Target="([^"]+)"|Target="([^"]+)"[^>]*Type="[^"]+/image"#', $zip->getFromName($name), $m, PREG_SET_ORDER);
                foreach ($m as $match) {
                    $target = 'word/' . ltrim($match[1] ?: $match[2], '/');
                    if (str_ends_with(strtolower($target), '.png') && $zip->locateName($target) !== false) {
                        $zip->addFromString($target, $png);
                        $replaced++;
                    }
                }
            }
        } finally {
            $zip->close();
        }

        return $replaced;
    }

    /**
     * Ajoute une mention (ex. « CONFIDENTIEL — Nom — Date ») en tête du document et dans chaque
     * en-tête / pied de page, directement dans le XML : la mise en page d'origine est conservée.
     */
    public function watermark(string $docxPath, string $text): void
    {
        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) {
            throw new RuntimeException('Fichier Word illisible.');
        }

        $p = '<w:p><w:pPr><w:jc w:val="center"/><w:spacing w:after="0"/></w:pPr><w:r><w:rPr><w:b/><w:color w:val="CC0000"/><w:sz w:val="18"/></w:rPr>'
           . '<w:t xml:space="preserve">' . htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</w:t></w:r></w:p>';

        try {
            $hasHeader = false;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (preg_match('#^word/(header|footer)\d*\.xml$#', $name, $m)) {
                    $tag = $m[1] === 'header' ? '</w:hdr>' : '</w:ftr>';
                    $xml = $zip->getFromName($name);
                    if (($pos = strrpos($xml, $tag)) !== false) {
                        $zip->addFromString($name, substr_replace($xml, $p, $pos, 0));
                        $hasHeader = $hasHeader || $m[1] === 'header';
                    }
                }
            }

            // Sans en-tête (fichier importé), la mention est placée en haut de la première page
            if ($hasHeader) {
                return;
            }
            $body = $zip->getFromName('word/document.xml');
            if ($body === false || !preg_match('#<w:body(\s[^>]*)?>#', $body, $m, PREG_OFFSET_CAPTURE)) {
                throw new RuntimeException('Corps du document Word introuvable.');
            }
            $zip->addFromString('word/document.xml', substr_replace($body, $p, $m[0][1] + strlen($m[0][0]), 0));
        } finally {
            $zip->close();
        }
    }

    public function verificationUrl(string $code): string
    {
        return route('verification.show', $code);
    }

    /**
     * QR code haute définition (impression nette) avec correction d'erreur maximale :
     * reste lisible même taché, plié ou partiellement abîmé.
     */
    public function qrPng(string $url): string
    {
        $qr = new QrCode(
            data: $url,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 600,
            margin: 0,
            roundBlockSizeMode: RoundBlockSizeMode::Enlarge,
        );

        $path = tempnam(sys_get_temp_dir(), 'ged_qr_') . '.png';
        file_put_contents($path, (new PngWriter())->write($qr)->getString());
        return $path;
    }

    private function header($section, $org, string $orgName, array $data): void
    {
        $header = $section->addHeader();
        $table  = $header->addTable(['width' => $this->fullWidth(), 'unit' => 'dxa', 'layout' => 'fixed', 'cellMargin' => 0,
                                     'borderBottomSize' => 12, 'borderBottomColor' => self::ACCENT]);
        $table->addRow();

        $left = $table->addCell($this->cm(11), ['valign' => 'bottom']);
        $left->addText(mb_strtoupper($orgName), ['bold' => true, 'size' => 15, 'color' => self::INK], ['spaceAfter' => 0]);
        $contact = collect([$org?->address, $org?->phone, $org?->email])->filter()->implode('  ·  ');
        $left->addText($contact ?: 'Système de Gestion Électronique des Documents', ['size' => 8, 'color' => self::MUTED], ['spaceAfter' => Converter::pointToTwip(4)]);

        $right = $table->addCell($this->cm(self::WIDTH_CM - 11), ['valign' => 'bottom']);
        $right->addText($data['reference'], ['bold' => true, 'size' => 10, 'color' => self::ACCENT], ['alignment' => Jc::END, 'spaceAfter' => 0]);
    }

    private function titleBlock($section, array $data): void
    {
        if ($data['confidential']) {
            $banner = $section->addTable(['width' => $this->fullWidth(), 'unit' => 'dxa', 'layout' => 'fixed', 'alignment' => JcTable::CENTER,
                                          'borderSize' => 6, 'borderColor' => self::DANGER, 'cellMargin' => 80]);
            $banner->addRow();
            $banner->addCell($this->cm(self::WIDTH_CM), ['bgColor' => 'FEF2F2'])
                ->addText('DOCUMENT CONFIDENTIEL — DIFFUSION RESTREINTE', ['bold' => true, 'size' => 9, 'color' => self::DANGER],
                          ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
            $section->addTextBreak(1, ['size' => 6]);
        }

        $section->addText($data['title'], ['bold' => true, 'size' => 20, 'color' => self::INK],
                          ['alignment' => Jc::CENTER, 'spaceBefore' => Converter::pointToTwip(6), 'spaceAfter' => Converter::pointToTwip(10)]);
    }

    // Zone de rédaction libre sous le titre
    private function body($section): void
    {
        $section->addText('');
    }

    private function footer($section, string $qrPath, array $data): void
    {
        $footer = $section->addFooter();
        $table  = $footer->addTable(['width' => $this->fullWidth(), 'unit' => 'dxa', 'layout' => 'fixed', 'cellMargin' => 0,
                                     'borderTopSize' => 6, 'borderTopColor' => self::LINE]);
        $table->addRow();

        $qrCell = $table->addCell($this->cm(2.6), ['valign' => 'center']);
        $qrCell->addImage($qrPath, ['width' => Converter::cmToPoint(2.2), 'height' => Converter::cmToPoint(2.2),
                                    'alignment' => Jc::START, 'spaceBefore' => Converter::pointToTwip(4)]);

        $small = ['size' => 7.5, 'color' => self::MUTED];
        $tight = ['spaceAfter' => 0, 'lineHeight' => 1.1];
        $text  = $table->addCell($this->cm(self::WIDTH_CM - 2.6 - 2.4), ['valign' => 'center']);
        $text->addText('Document authentifié par QR code', ['size' => 8.5, 'bold' => true, 'color' => self::INK], $tight + ['spaceBefore' => Converter::pointToTwip(4)]);

        $run = $text->addTextRun($tight);
        $run->addText('Vérification manuelle : ', $small);
        $run->addText(parse_url($this->lookupUrl(), PHP_URL_HOST) . parse_url($this->lookupUrl(), PHP_URL_PATH), $small + ['bold' => true, 'color' => self::INK]);
        $run->addText('  —  code ', $small);
        $run->addText(DocumentVerification::displayCode($data['verification_code']), $small + ['bold' => true, 'color' => self::ACCENT, 'spacing' => 10]);

        $text->addText('Toute modification de ce document en dehors de la GED le rend non conforme à l\'original enregistré.', $small + ['italic' => true], $tight);

        $page = $table->addCell($this->cm(2.4), ['valign' => 'center']);
        $page->addText($data['reference'], ['size' => 7.5, 'bold' => true, 'color' => self::INK], ['alignment' => Jc::END, 'spaceAfter' => 0]);
        $page->addPreserveText('Page {PAGE} / {NUMPAGES}', $small, ['alignment' => Jc::END, 'spaceAfter' => 0]);
    }

    private function fullWidth(): int
    {
        return $this->cm(self::WIDTH_CM);
    }

    // Les mesures OOXML doivent être des entiers (en twips), sinon Word et LibreOffice les ignorent
    private function cm(float $cm): int
    {
        return (int) round(Converter::cmToTwip($cm));
    }

    private function lookupUrl(): string
    {
        return route('verification.lookup');
    }
}
