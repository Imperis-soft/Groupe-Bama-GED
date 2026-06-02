<?php

namespace App\Console\Commands;

use App\Models\License;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ActivateLicense extends Command
{
    protected $signature = 'license:activate {--key= : Clé de licence à activer} {--generate : Générer une nouvelle clé de licence (1 an)}';

    protected $description = 'Activer ou générer une licence pour le système GED Groupe Bama (Imperis SARL uniquement)';

    public function handle(): int
    {
        if ($this->option('generate')) {
            return $this->generateLicense();
        }

        $key = $this->option('key');

        if (! $key) {
            $key = $this->ask('Entrez la clé de licence');
        }

        if (! $key) {
            $this->error('Aucune clé de licence fournie.');
            return self::FAILURE;
        }

        // Valider le format
        if (! preg_match('/^IMPERIS-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-EXP\d{8}$/', $key)) {
            $this->error('Format de clé invalide.');
            $this->info('Format attendu : IMPERIS-XXXX-XXXX-XXXX-EXPYYYYMMDD');
            return self::FAILURE;
        }

        // Extraire la date d'expiration
        preg_match('/EXP(\d{8})$/', $key, $matches);
        $expiresAt = Carbon::createFromFormat('Ymd', $matches[1]);

        if ($expiresAt->isPast()) {
            $this->error('Cette clé de licence est déjà expirée (' . $expiresAt->format('d/m/Y') . ').');
            return self::FAILURE;
        }

        // Désactiver les anciennes licences
        License::where('is_active', true)->update(['is_active' => false]);

        // Créer la nouvelle licence
        $license = License::create([
            'license_key' => $key,
            'licensed_to' => 'Groupe Bama',
            'issued_by'   => 'Imperis SARL',
            'issued_at'   => now(),
            'expires_at'  => $expiresAt,
            'is_active'   => true,
            'notes'       => 'Activée via CLI le ' . now()->format('d/m/Y à H:i'),
        ]);

        $this->info('✅ Licence activée avec succès !');
        $this->table(
            ['Propriété', 'Valeur'],
            [
                ['Clé', $license->license_key],
                ['Client', $license->licensed_to],
                ['Émise par', $license->issued_by],
                ['Expire le', $expiresAt->format('d/m/Y')],
                ['Jours restants', $license->daysRemaining()],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Génère une nouvelle clé de licence valide 1 an.
     */
    private function generateLicense(): int
    {
        $expiresAt = now()->addYear();
        $segments = [
            'IMPERIS',
            strtoupper(Str::random(4)),
            strtoupper(Str::random(4)),
            strtoupper(Str::random(4)),
            'EXP' . $expiresAt->format('Ymd'),
        ];

        $key = implode('-', $segments);

        $this->info('🔑 Nouvelle clé de licence générée :');
        $this->newLine();
        $this->line("   <fg=green;options=bold>{$key}</>");
        $this->newLine();
        $this->info("   Valide jusqu'au : " . $expiresAt->format('d/m/Y'));
        $this->info("   Client : Groupe Bama");
        $this->info("   Émetteur : Imperis SARL");
        $this->newLine();

        if ($this->confirm('Voulez-vous activer cette licence maintenant ?', true)) {
            License::where('is_active', true)->update(['is_active' => false]);

            License::create([
                'license_key' => $key,
                'licensed_to' => 'Groupe Bama',
                'issued_by'   => 'Imperis SARL',
                'issued_at'   => now(),
                'expires_at'  => $expiresAt,
                'is_active'   => true,
                'notes'       => 'Générée et activée via CLI le ' . now()->format('d/m/Y à H:i'),
            ]);

            $this->info('✅ Licence activée !');
        } else {
            $this->warn('⚠️  Licence NON activée. Conservez la clé pour activation ultérieure.');
        }

        return self::SUCCESS;
    }
}
