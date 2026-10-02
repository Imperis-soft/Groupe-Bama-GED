<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\RetentionService;
use Illuminate\Console\Command;

/**
 * Calcule la date de fin de conservation de tous les documents (après installation, ou changement de règles).
 */
class RecomputeRetention extends Command
{
    protected $signature = 'documents:retention-recompute';

    protected $description = 'Calcule la fin de conservation de tous les documents selon les règles de leur catégorie';

    public function handle(RetentionService $service): int
    {
        $count = $service->recompute(Document::withoutGlobalScope('organization')->withTrashed()->cursor());
        $due   = Document::withoutGlobalScope('organization')->retentionDue()->count();

        $this->info("{$count} date(s) de fin de conservation mise(s) à jour. {$due} document(s) attendent une décision.");

        return self::SUCCESS;
    }
}
