<?php

return [
    // Outils externes utilisés pour les aperçus et l'indexation du texte.
    // Laisser vide pour une détection automatique dans le PATH.
    'libreoffice_path' => env('GED_LIBREOFFICE_PATH', ''),
    'pdftotext_path'   => env('GED_PDFTOTEXT_PATH', ''),
    'tesseract_path'   => env('GED_TESSERACT_PATH', ''),
    'pdftoppm_path'    => env('GED_PDFTOPPM_PATH', ''),

    // Durée maximale d'une conversion LibreOffice (secondes)
    'conversion_timeout' => (int) env('GED_CONVERSION_TIMEOUT', 120),

    // Taille maximale d'un fichier importé (Ko) — aligner avec upload_max_filesize de PHP
    'max_upload_kb' => (int) env('GED_MAX_UPLOAD_KB', 153600),

    // Texte indexé conservé par document (caractères)
    'max_indexed_chars' => (int) env('GED_MAX_INDEXED_CHARS', 1000000),

    // Durée de conservation par défaut (années) quand ni le document ni sa catégorie n'en précisent
    'default_retention_years' => (int) env('GED_DEFAULT_RETENTION_YEARS', 5),

    // Nombre de jours pendant lesquels un export complet reste téléchargeable
    'export_days' => (int) env('GED_EXPORT_DAYS', 7),

    // OCR : nombre maximal de pages lues dans un PDF scanné
    'ocr_max_pages' => (int) env('GED_OCR_MAX_PAGES', 30),

    // Détection des doublons par le contenu (texte, OCR compris)
    'duplicates' => [
        // Part de texte commun (paires de mots) à partir de laquelle l'import est bloqué,
        // si les nombres (montants, dates, références) concordent aussi : même document rescanné / autre format.
        // Un rescan perd souvent 10 à 20 % des paires à cause des erreurs d'OCR.
        'block_threshold'   => (float) env('GED_DUPLICATE_BLOCK', 0.75),
        // Part de nombres communs exigée pour bloquer (sinon : même modèle, autres valeurs → avertissement)
        'numbers_threshold' => (float) env('GED_DUPLICATE_NUMBERS', 0.80),
        // Part de texte commun à partir de laquelle on avertit (document très proche, ex. même modèle)
        'warn_threshold'    => (float) env('GED_DUPLICATE_WARN', 0.60),
        // En dessous de ce nombre de mots, le texte est trop court pour comparer
        'min_words'       => (int) env('GED_DUPLICATE_MIN_WORDS', 25),
    ],
];
