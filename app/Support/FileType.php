<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Registre des formats de fichiers acceptés par la GED.
 *
 * Chaque extension appartient à une famille (word, excel…) qui fixe l'icône,
 * le mode d'aperçu et la compatibilité entre versions d'un même document.
 *
 * Modes d'aperçu :
 * - pdf       : PDF natif du navigateur
 * - docx      : conversion HTML dans le navigateur (Mammoth)
 * - sheet     : tableur rendu dans le navigateur (SheetJS)
 * - image     : affichage direct
 * - text      : texte brut
 * - archive   : liste du contenu (serveur)
 * - convert   : conversion PDF côté serveur (LibreOffice)
 */
class FileType
{
    public const FAMILIES = [
        'word'       => ['label' => 'Word',       'icon' => 'fa-file-word',       'color' => 'text-blue-600'],
        'pdf'        => ['label' => 'PDF',        'icon' => 'fa-file-pdf',        'color' => 'text-red-500'],
        'excel'      => ['label' => 'Excel',      'icon' => 'fa-file-excel',      'color' => 'text-emerald-600'],
        'powerpoint' => ['label' => 'PowerPoint', 'icon' => 'fa-file-powerpoint', 'color' => 'text-orange-500'],
        'image'      => ['label' => 'Image',      'icon' => 'fa-file-image',      'color' => 'text-purple-500'],
        'text'       => ['label' => 'Texte',      'icon' => 'fa-file-lines',      'color' => 'text-slate-500'],
        'archive'    => ['label' => 'Archive',    'icon' => 'fa-file-zipper',     'color' => 'text-amber-600'],
    ];

    // extension => [famille, type MIME servi, mode d'aperçu]
    public const TYPES = [
        'docx' => ['word', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'docx'],
        'doc'  => ['word', 'application/msword', 'convert'],
        'odt'  => ['word', 'application/vnd.oasis.opendocument.text', 'convert'],
        'rtf'  => ['word', 'application/rtf', 'convert'],
        'pdf'  => ['pdf', 'application/pdf', 'pdf'],
        'xlsx' => ['excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'sheet'],
        'xls'  => ['excel', 'application/vnd.ms-excel', 'sheet'],
        'ods'  => ['excel', 'application/vnd.oasis.opendocument.spreadsheet', 'sheet'],
        'csv'  => ['excel', 'text/csv; charset=utf-8', 'sheet'],
        'pptx' => ['powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'convert'],
        'ppt'  => ['powerpoint', 'application/vnd.ms-powerpoint', 'convert'],
        'odp'  => ['powerpoint', 'application/vnd.oasis.opendocument.presentation', 'convert'],
        'jpg'  => ['image', 'image/jpeg', 'image'],
        'jpeg' => ['image', 'image/jpeg', 'image'],
        'png'  => ['image', 'image/png', 'image'],
        'gif'  => ['image', 'image/gif', 'image'],
        'webp' => ['image', 'image/webp', 'image'],
        'tif'  => ['image', 'image/tiff', 'convert'],
        'tiff' => ['image', 'image/tiff', 'convert'],
        'txt'  => ['text', 'text/plain; charset=utf-8', 'text'],
        'zip'  => ['archive', 'application/zip', 'archive'],
    ];

    // Contenus jamais acceptés, quelle que soit l'extension annoncée
    private const BLOCKED_MIME_PATTERNS = [
        '#^text/html#', '#xhtml#', '#svg#', '#php#', '#javascript#', '#ecmascript#',
        '#x-msdownload#', '#x-executable#', '#x-dosexec#', '#portable-executable#', '#x-sh(ellscript)?$#', '#x-mach-binary#', '#x-elf#',
    ];

    // Contrôle du contenu réel pour les familles où il est fiable
    private const EXPECTED_MIME = [
        'pdf'   => '#^application/(pdf|x-pdf)#',
        'image' => '#^image/(jpeg|png|gif|webp|tiff)#',
    ];

    public function __construct(
        public readonly string $extension,
        public readonly string $family,
        public readonly string $mime,
        public readonly string $previewMode,
    ) {}

    public static function fromExtension(?string $extension): self
    {
        $extension = strtolower((string) $extension);
        [$family, $mime, $mode] = self::TYPES[$extension] ?? ['other', 'application/octet-stream', 'none'];

        return new self($extension, $family, $mime, $mode);
    }

    public static function fromPath(?string $path): self
    {
        return self::fromExtension(pathinfo((string) $path, PATHINFO_EXTENSION));
    }

    public static function extensions(): array
    {
        return array_keys(self::TYPES);
    }

    // Attribut accept="" d'un champ fichier (toutes les extensions, ou celles d'une famille)
    public static function acceptAttribute(?string $family = null): string
    {
        $extensions = $family
            ? array_keys(array_filter(self::TYPES, fn ($t) => $t[0] === $family))
            : self::extensions();

        return implode(',', array_map(fn ($e) => '.' . $e, $extensions));
    }

    // Libellé court des formats acceptés, pour les textes d'aide
    public static function summary(): string
    {
        return 'Word, PDF, Excel, CSV, PowerPoint, images (JPG, PNG, TIFF…), texte et ZIP';
    }

    /**
     * Vérifie un fichier envoyé : extension connue et contenu cohérent.
     * Retourne un message d'erreur, ou null si le fichier est accepté.
     */
    public static function uploadError(UploadedFile $file): ?string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (!isset(self::TYPES[$extension])) {
            return "Format .{$extension} non pris en charge. Formats acceptés : " . self::summary() . '.';
        }

        // Type réel déduit des octets du fichier (jamais du nom ni de l'en-tête envoyé par le navigateur)
        $detected = strtolower((string) (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath()));
        foreach (self::BLOCKED_MIME_PATTERNS as $pattern) {
            if (preg_match($pattern, $detected)) {
                return 'Le contenu de ce fichier n\'est pas autorisé.';
            }
        }

        $family = self::TYPES[$extension][0];
        if (isset(self::EXPECTED_MIME[$family]) && !preg_match(self::EXPECTED_MIME[$family], $detected)) {
            return "Le contenu du fichier ne correspond pas à son extension (.{$extension}).";
        }

        return null;
    }

    public function label(): string
    {
        return self::FAMILIES[$this->family]['label'] ?? strtoupper($this->extension ?: 'Fichier');
    }

    public function icon(): string
    {
        return self::FAMILIES[$this->family]['icon'] ?? 'fa-file';
    }

    public function color(): string
    {
        return self::FAMILIES[$this->family]['color'] ?? 'text-slate-400';
    }

    // Deux fichiers peuvent-ils être des versions successives du même document ?
    public function isCompatibleWith(self $other): bool
    {
        return $this->family === $other->family;
    }
}
