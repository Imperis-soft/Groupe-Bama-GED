<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanupExpiredDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:cleanup-expired {--dry-run : Afficher seulement ce qui serait supprimé}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nettoyer les documents expirés selon leur politique de rétention';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $service = app(\App\Services\DocumentArchivalService::class);
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $expiredCount = \App\Models\Document::expired()->count();
            $this->info("Documents expirés trouvés: {$expiredCount}");

            \App\Models\Document::expired()->take(10)->get()->each(function ($doc) {
                $this->line("- {$doc->title} (expire le {$doc->expires_at->format('d/m/Y')})");
            });

            return;
        }

        $this->info('Début du nettoyage des documents expirés...');

        $deletedCount = $service->cleanupExpiredDocuments();

        $this->info("Nettoyage terminé. {$deletedCount} documents supprimés.");
    }
}
