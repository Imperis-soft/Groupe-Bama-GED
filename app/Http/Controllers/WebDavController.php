<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Sabre\DAV\Server;
use Sabre\DAV\FS\File;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WebDavController extends Controller
{
    // Headers WebDAV communs requis par Microsoft Office
    private function davHeaders(): array
    {
        return [
            'DAV'            => '1, 2',
            'MS-Author-Via'  => 'DAV',
            'Allow'          => 'OPTIONS, GET, PUT, PROPFIND, HEAD',
        ];
    }

    // Gérer les requêtes WebDAV de Microsoft Word
    public function handle(Request $request, $id)
    {
        $document = Document::findOrFail($id);
        $disk = Storage::disk('s3');

        // OPTIONS — Word vérifie les capacités du serveur
        if ($request->isMethod('OPTIONS')) {
            return response('', 200, $this->davHeaders());
        }

        // HEAD — Word vérifie l'existence du fichier
        if ($request->isMethod('HEAD')) {
            $size = $disk->size($document->file_path);
            return response('', 200, array_merge($this->davHeaders(), [
                'Content-Type'   => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Content-Length' => $size,
                'ETag'           => '"' . md5($document->updated_at) . '"',
            ]));
        }

        // PROPFIND — Word demande les propriétés WebDAV du fichier
        if ($request->isMethod('PROPFIND')) {
            $size     = $disk->size($document->file_path);
            $modified = $document->updated_at->format('D, d M Y H:i:s') . ' GMT';
            $href     = url('/webdav/' . $document->id);
            $name     = $document->reference . '.docx';

            $xml = '<?xml version="1.0" encoding="utf-8"?>'
                . '<D:multistatus xmlns:D="DAV:">'
                . '<D:response>'
                . '<D:href>' . htmlspecialchars($href) . '</D:href>'
                . '<D:propstat>'
                . '<D:prop>'
                . '<D:displayname>' . htmlspecialchars($name) . '</D:displayname>'
                . '<D:getcontenttype>application/vnd.openxmlformats-officedocument.wordprocessingml.document</D:getcontenttype>'
                . '<D:getcontentlength>' . $size . '</D:getcontentlength>'
                . '<D:getlastmodified>' . $modified . '</D:getlastmodified>'
                . '<D:resourcetype/>'
                . '</D:prop>'
                . '<D:status>HTTP/1.1 200 OK</D:status>'
                . '</D:propstat>'
                . '</D:response>'
                . '</D:multistatus>';

            return response($xml, 207, array_merge($this->davHeaders(), [
                'Content-Type' => 'application/xml; charset=utf-8',
            ]));
        }

        // GET — Word télécharge le fichier pour l'ouvrir
        if ($request->isMethod('GET')) {
            $size = $disk->size($document->file_path);
            return new StreamedResponse(function () use ($disk, $document) {
                $stream = $disk->readStream($document->file_path);
                fpassthru($stream);
            }, 200, array_merge($this->davHeaders(), [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Content-Disposition' => 'inline; filename="' . $document->reference . '.docx"',
                'Content-Length'      => $size,
                'ETag'                => '"' . md5($document->updated_at) . '"',
            ]));
        }

        // PUT — Word sauvegarde le fichier modifié
        if ($request->isMethod('PUT')) {
            $content = $request->getContent();
            $disk->put($document->file_path, $content);
            $document->increment('version');

            return response('', 204, $this->davHeaders());
        }

        return response('Method Not Allowed', 405, $this->davHeaders());
    }
}