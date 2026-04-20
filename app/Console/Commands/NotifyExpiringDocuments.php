<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class NotifyExpiringDocuments extends Command
{
    protected $signature   = 'documents:notify-expiring';
    protected $description = 'Envoyer des rappels pour les documents expirant dans 7 jours ou 1 jour';

    public function handle(): void
    {
        $service = app(NotificationService::class);
        $count   = 0;

        foreach ([7, 1] as $days) {
            $docs = Document::whereNotNull('expires_at')
                ->whereDate('expires_at', now()->addDays($days)->toDateString())
                ->where('status', '!=', 'archived')
                ->with('creator')
                ->get();

            foreach ($docs as $doc) {
                if (!$doc->creator) continue;

                $label = $days === 1 ? 'demain' : "dans {$days} jours";

                $service->notify(
                    $doc->creator,
                    'document_expiring',
                    'Document bientôt expiré',
                    "Le document \"{$doc->title}\" ({$doc->reference}) expire {$label} le {$doc->expires_at->format('d/m/Y')}.",
                    url("/documents/{$doc->id}"),
                    $doc
                );
                $count++;
            }
        }

        $this->info("{$count} rappel(s) d'expiration envoyé(s).");
    }
}
