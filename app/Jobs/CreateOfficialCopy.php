<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\OfficialCopyService;
use App\Support\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Quand un document devient officiel : copie officielle (PDF + certificat de validation avec QR code).
 */
class CreateOfficialCopy implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    public function __construct(public int $documentId) {}

    public function handle(OfficialCopyService $service): void
    {
        $document = Document::withoutGlobalScope('organization')->find($this->documentId);
        if (!$document || !$document->organization) {
            return;
        }

        try {
            Tenant::run($document->organization, fn () => $service->generate($document));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
