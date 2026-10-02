<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVerification;
use App\Models\Organization;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Copie officielle d'un document devenu officiel (approuvé, ou signé si sa catégorie l'exige) :
 * le document converti en PDF, suivi d'un certificat de validation portant le QR code de vérification,
 * chaque approbation et chaque signature, et l'empreinte SHA-256 du fichier validé.
 * Le fichier d'origine n'est jamais modifié. Sans LibreOffice / Ghostscript, seul le certificat est produit.
 */
class OfficialCopyService
{
    public function __construct(
        private DocumentConverter $converter,
        private DocumentArchivalService $archival,
        private WordTemplate $word,
    ) {}

    public function generate(Document $document): ?string
    {
        if (!in_array($document->status, ['approved', 'signed'], true)) {
            return null;
        }

        $checksum = $document->currentChecksum();
        $certificate = $this->certificate($document, $checksum);
        [$pdf, $complete] = $this->assemble($document, $certificate);

        $dir  = dirname($document->file_path);
        $path = ($dir === '.' ? '' : $dir . '/') . "official/{$document->reference}_v{$document->version}_{$document->status}.pdf";
        $document->disk()->put($path, $pdf);

        $document->forceFill([
            'official_copy_path'     => $path,
            'official_copy_checksum' => hash('sha256', $pdf),
            'official_copy_version'  => $document->version,
            'official_copy_at'       => now(),
        ])->saveQuietly();

        $this->archival->logAction($document, 'official_copy_created',
            ($complete ? 'Copie officielle créée (document en PDF + certificat de validation)'
                       : 'Certificat de validation créé (conversion du document en PDF indisponible : le certificat accompagne le fichier d\'origine)')
                . " — version {$document->version}",
            null, ['checksum' => $document->official_copy_checksum, 'document_checksum' => $checksum, 'complete' => $complete]);

        return $path;
    }

    // Retire la copie officielle quand le contenu change (le fichier reste dans le stockage, pour l'historique)
    public function forget(Document $document): void
    {
        if ($document->official_copy_path) {
            $document->forceFill(['official_copy_path' => null, 'official_copy_checksum' => null, 'official_copy_version' => null, 'official_copy_at' => null])->saveQuietly();
        }
    }

    // [contenu PDF, document inclus ?]
    private function assemble(Document $document, string $certificate): array
    {
        $workDir = sys_get_temp_dir() . '/ged_official_' . bin2hex(random_bytes(6));
        @mkdir($workDir, 0700, true);
        $extension = strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION));

        try {
            file_put_contents("{$workDir}/source.{$extension}", $document->disk()->get($document->file_path));
            file_put_contents("{$workDir}/certificate.pdf", $certificate);

            $body = $this->converter->anyFileToPdf("{$workDir}/source.{$extension}", $extension);
            if ($body !== null) {
                file_put_contents("{$workDir}/body.pdf", $body);
                $merged = $this->converter->mergePdfs(["{$workDir}/body.pdf", "{$workDir}/certificate.pdf"]);
                if ($merged !== null) {
                    return [$merged, true];
                }
            }

            return [$certificate, false];
        } finally {
            foreach (glob("{$workDir}/*") ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($workDir);
        }
    }

    private function certificate(Document $document, ?string $checksum): string
    {
        $organization = $document->organization ?? Organization::find($document->organization_id);
        $code = $document->verification?->verification_code
            ?? DocumentVerification::create(['document_id' => $document->id, 'verification_code' => DocumentVerification::generateCode()])->verification_code;

        $qrFile = $this->word->qrPng($this->word->verificationUrl($code));
        $qr = 'data:image/png;base64,' . base64_encode((string) file_get_contents($qrFile));
        @unlink($qrFile);

        // Seules les décisions et signatures portant sur le contenu actuel figurent au certificat
        $steps = $document->approvalSteps()->where('status', 'approved')->where('document_checksum', $checksum)
            ->with(['approver', 'delegatedFrom', 'forcedBy'])->get();
        $signatures = $document->signatures()->where('status', 'signed')->where('document_checksum', $checksum)
            ->with('user')->orderBy('signed_at')->get();

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('pdf.validation-certificate', [
            'document'     => $document,
            'organization' => $organization,
            'checksum'     => $checksum,
            'code'         => DocumentVerification::displayCode($code),
            'qr'           => $qr,
            'steps'        => $steps,
            'signatures'   => $signatures,
            'logo'         => 'data:image/png;base64,' . base64_encode((string) @file_get_contents(public_path('images/logo-ged-96.png'))),
        ])->render());
        $dompdf->setPaper('A4');
        $dompdf->render();

        return $dompdf->output();
    }
}
