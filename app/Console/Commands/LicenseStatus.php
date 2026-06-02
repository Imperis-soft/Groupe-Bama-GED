<?php

namespace App\Console\Commands;

use App\Models\License;
use Illuminate\Console\Command;

class LicenseStatus extends Command
{
    protected $signature = 'license:status';

    protected $description = 'Afficher le statut actuel de la licence du système GED';

    public function handle(): int
    {
        $license = License::current();

        if (! $license) {
            $this->error('❌ Aucune licence trouvée dans le système.');
            $this->newLine();
            $this->info('Utilisez "php artisan license:activate --generate" pour générer une licence.');
            return self::FAILURE;
        }

        $isValid = $license->isValid();
        $days = $license->daysRemaining();

        $this->newLine();
        $this->line($isValid
            ? '  <fg=green;options=bold>✅ LICENCE VALIDE</>'
            : '  <fg=red;options=bold>❌ LICENCE EXPIRÉE</>');
        $this->newLine();

        $this->table(
            ['Propriété', 'Valeur'],
            [
                ['Clé', $license->license_key],
                ['Client', $license->licensed_to],
                ['Émise par', $license->issued_by],
                ['Date d\'émission', $license->issued_at->format('d/m/Y')],
                ['Date d\'expiration', $license->expires_at->format('d/m/Y')],
                ['Statut', $isValid ? '🟢 Active' : '🔴 Expirée'],
                ['Jours restants', $isValid ? "{$days} jours" : "Expirée depuis " . abs($days) . " jours"],
            ]
        );

        if (! $isValid) {
            $this->newLine();
            $this->warn('⚠️  Le système est bloqué. Les utilisateurs ne peuvent plus se connecter.');
            $this->info('Contactez Imperis SARL pour renouveler la licence.');
            $this->info('Ou utilisez : php artisan license:activate --key=VOTRE_CLE');
        } elseif ($days <= 30) {
            $this->newLine();
            $this->warn("⚠️  Attention : la licence expire dans {$days} jours !");
            $this->info('Contactez Imperis SARL pour le renouvellement.');
        }

        return self::SUCCESS;
    }
}
