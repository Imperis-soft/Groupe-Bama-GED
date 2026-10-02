<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * expires_at signifie désormais « date d'échéance » (fin de contrat, d'assurance…) : elle ne sert qu'aux rappels.
     * Jusqu'ici, la création par formulaire et la règle de conservation des catégories y recopiaient
     * la fin de conservation (création + N ans), ce qui aurait conduit à détruire le document à cette date.
     * Ces dates calculées sont retirées ; une échéance saisie par un utilisateur est conservée.
     */
    public function up(): void
    {
        DB::table('documents')->whereNotNull('expires_at')->orderBy('id')
            ->select('id', 'created_at', 'expires_at', 'retention_years')
            ->chunk(500, function ($documents) {
                foreach ($documents as $document) {
                    if (!$document->created_at || !$document->retention_years) {
                        continue;
                    }
                    $computed = Carbon::parse($document->created_at)->addYears($document->retention_years)->toDateString();
                    if (Carbon::parse($document->expires_at)->toDateString() === $computed) {
                        DB::table('documents')->where('id', $document->id)->update(['expires_at' => null]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Rien à restaurer : ces dates étaient déduites de la durée de conservation
    }
};
