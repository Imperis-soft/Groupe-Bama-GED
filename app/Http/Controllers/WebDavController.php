<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentArchivalService;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    private function unauthorized()
    {
        return response('Unauthorized', 401, [
            'WWW-Authenticate' => 'Basic realm="' . config('saas.platform_name') . '"',
            'DAV'              => '1, 2',
        ]);
    }

    // Type MIME selon l'extension réelle du fichier
    private function contentType(Document $document): string
    {
        return $document->fileType()->mime;
    }

    private function fileName(Document $document): string
    {
        return $document->reference . '.' . (pathinfo($document->file_path, PATHINFO_EXTENSION) ?: 'docx');
    }

    // Gérer les requêtes WebDAV de Microsoft Word
    public function handle(Request $request, $id)
    {
        // Authentification Basic pour WebDAV (Word l'exige) — sans session
        $user = $request->getUser();
        $pass = $request->getPassword();

        if (empty($user) || empty($pass) || !Auth::once(['email' => $user, 'password' => $pass])) {
            return $this->unauthorized();
        }

        // Cloisonnement : uniquement les documents de l'entreprise de l'utilisateur, si elle a accès
        $organization = Auth::user()->is_active ? Auth::user()->organization : null;
        if (!$organization || !$organization->canAccess()) {
            return response('Forbidden', 403, $this->davHeaders());
        }

        return Tenant::run($organization, fn () => $this->serve($request, $id));
    }

    private function serve(Request $request, $id)
    {
        $document = Document::findOrFail($id);

        // Même règle d'accès que l'interface web
        if (!$document->canView()) {
            return response('Forbidden', 403, $this->davHeaders());
        }

        $disk = Tenant::organization()->disk();

        // OPTIONS — Word vérifie les capacités du serveur
        if ($request->isMethod('OPTIONS')) {
            return response('', 200, $this->davHeaders());
        }

        // HEAD — Word vérifie l'existence du fichier
        if ($request->isMethod('HEAD')) {
            $size = $disk->size($document->file_path);
            return response('', 200, array_merge($this->davHeaders(), [
                'Content-Type'   => $this->contentType($document),
                'Content-Length' => $size,
                'ETag'           => '"' . md5($document->updated_at) . '"',
            ]));
        }

        // PROPFIND — Word demande les propriétés WebDAV du fichier
        if ($request->isMethod('PROPFIND')) {
            $size     = $disk->size($document->file_path);
            $modified = $document->updated_at->copy()->utc()->format('D, d M Y H:i:s') . ' GMT';
            $href     = url('/webdav/' . $document->id);
            $name     = $this->fileName($document);

            $xml = '<?xml version="1.0" encoding="utf-8"?>'
                . '<D:multistatus xmlns:D="DAV:">'
                . '<D:response>'
                . '<D:href>' . htmlspecialchars($href) . '</D:href>'
                . '<D:propstat>'
                . '<D:prop>'
                . '<D:displayname>' . htmlspecialchars($name) . '</D:displayname>'
                . '<D:getcontenttype>' . $this->contentType($document) . '</D:getcontenttype>'
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
                'Content-Type'        => $this->contentType($document),
                'Content-Disposition' => 'inline; filename="' . $this->fileName($document) . '"',
                'Content-Length'      => $size,
                'ETag'                => '"' . md5($document->updated_at) . '"',
            ]));
        }

        // PUT — Word sauvegarde le fichier modifié : nouvelle version tracée
        if ($request->isMethod('PUT')) {
            if (!$document->canEdit()) {
                return response('Forbidden', 403, $this->davHeaders());
            }
            // Contenu gelé : archivé, gel juridique, verrou, ou circuit d'approbation / de signature en cours
            if ($document->isUnderLegalHold() || $document->isArchived() || $document->isLockedByOther() || $document->isInWorkflow()) {
                return response('Locked', 423, $this->davHeaders());
            }

            $content = $request->getContent();
            if ($content === '') {
                return response('Empty body', 400, $this->davHeaders());
            }

            if (!Tenant::organization()->canStore(strlen($content))) {
                return response('Insufficient Storage', 507, $this->davHeaders());
            }

            $ext  = pathinfo($document->file_path, PATHINFO_EXTENSION) ?: 'docx';
            $path = Tenant::organization()->documentsDir() . $document->reference . '_v' . ((int) $document->version + 1) . '.' . $ext;
            $disk->put($path, $content);

            app(DocumentArchivalService::class)->createVersion($document, $path, 'Enregistrement depuis Word (WebDAV)');
            \App\Jobs\IndexDocumentText::dispatch($document->id);

            return response('', 204, $this->davHeaders());
        }

        return response('Method Not Allowed', 405, $this->davHeaders());
    }
}
