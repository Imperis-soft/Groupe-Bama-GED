<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DocumentArchivalService;

class PurgeTrash extends Command
{
    protected $signature = 'documents:purge-trash 
                            {--days=30 : Nombre de jours après suppression avant purge définitive}
                            {--dry-run : Afficher seulement ce qui serait purgé}';

    protected $description = 'Purger définitivement les documents dans la corbeille depuis plus de X jours';

    public function handle(): void
    {
        $days = (int) $this->option('days');
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $cutoff = now()->subDays($days);
            $trashed = \App\Models\Document::onlyTrashed()
                ->where('deleted_at', '<', $cutoff)
                ->where('legal_hold', false)
                ->get();

            $this->info("Documents dans la corbeille depuis plus de {$days} jours : {$trashed->count()}");

            $trashed->each(function ($doc) {
                $deletedSince = $doc->deleted_at->diffInDays(now());
                $hold = $doc->legal_hold ? ' [LEGAL HOLD - protégé]' : '';
                $this->line("  - {$doc->title} ({$doc->reference}) — supprimé il y a {$deletedSince} jours{$hold}");
            });

            return;
        }

        $this->info("Purge des documents dans la corbeille depuis plus de {$days} jours...");

        $service = app(DocumentArchivalService::class);
        $count = $service->purgeTrash($days);

        $this->info("Purge terminée. {$count} document(s) supprimé(s) définitivement.");
    }
}
