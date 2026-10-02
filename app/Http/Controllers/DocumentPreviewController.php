<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentConverter;

/**
 * Aperçus calculés côté serveur : PDF converti (LibreOffice) et contenu d'une archive ZIP.
 */
class DocumentPreviewController extends Controller
{
    // PDF d'aperçu pour les formats que le navigateur ne sait pas afficher (pptx, doc, odt, tiff…)
    public function pdf(Document $document, DocumentConverter $converter)
    {
        if (!$document->canView()) {
            abort(403, 'Accès refusé à ce document.');
        }
        if ($document->fileType()->previewMode !== 'convert') {
            abort(404);
        }

        set_time_limit(max(60, config('ged.conversion_timeout', 120) + 30));
        $path = $converter->pdfPreview($document);
        if (!$path) {
            abort(503, 'Aperçu indisponible : la conversion du document a échoué ou LibreOffice n\'est pas installé sur le serveur.');
        }

        $stream = $document->disk()->readStream($path);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type'           => 'application/pdf',
            'Content-Disposition'    => 'inline; filename="' . $document->reference . '.pdf"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'private, max-age=3600',
        ]);
    }

    // Liste des fichiers d'une archive ZIP (sans extraction)
    public function archive(Document $document)
    {
        if (!$document->canView()) {
            abort(403, 'Accès refusé à ce document.');
        }
        if ($document->fileType()->family !== 'archive') {
            abort(404);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'ged_zip_');
        try {
            $stream = $document->disk()->readStream($document->file_path);
            $out = fopen($tmp, 'w');
            stream_copy_to_stream($stream, $out);
            fclose($out);
            if (is_resource($stream)) {
                fclose($stream);
            }

            $zip = new \ZipArchive();
            if ($zip->open($tmp) !== true) {
                return response()->json(['message' => 'Archive illisible ou corrompue.'], 422);
            }

            $entries = [];
            $total = $zip->numFiles;
            for ($i = 0; $i < min($total, 2000); $i++) {
                $stat = $zip->statIndex($i);
                $entries[] = [
                    'name'       => $stat['name'],
                    'is_dir'     => str_ends_with($stat['name'], '/'),
                    'size'       => $stat['size'],
                    'compressed' => $stat['comp_size'],
                    'modified'   => $stat['mtime'] ? date('d/m/Y H:i', $stat['mtime']) : null,
                ];
            }
            $zip->close();

            return response()->json(['total' => $total, 'entries' => $entries]);
        } finally {
            @unlink($tmp);
        }
    }
}
