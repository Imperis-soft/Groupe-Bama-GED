<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;

/**
 * Liste les documents dont la date d'échéance est dépassée.
 * Ne supprime RIEN : une échéance (fin de contrat, d'assurance…) n'est pas une fin de conservation.
 * Commande conservée pour compatibilité ; elle n'est plus planifiée.
 */
class CleanupExpiredDocuments extends Command
{
    protected $signature = 'documents:cleanup-expired {--dry-run : Conservé pour compatibilité (la commande ne supprime jamais rien)}';

    protected $description = 'Liste les documents dont l\'échéance est dépassée (aucune suppression)';

    public function handle(): int
    {
        $documents = Document::withoutGlobalScope('organization')->expired()->with('organization')->orderBy('expires_at')->get();

        $this->info("{$documents->count()} document(s) dont l'échéance est dépassée. Aucun n'est supprimé.");
        foreach ($documents->take(50) as $document) {
            $this->line("- [{$document->organization?->name}] {$document->reference} · {$document->title} (échéance : {$document->expires_at->format('d/m/Y')})");
        }

        return self::SUCCESS;
    }
}
