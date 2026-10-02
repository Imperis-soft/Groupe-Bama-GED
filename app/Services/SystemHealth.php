<?php

namespace App\Services;

use App\Models\IntegrityCheck;
use App\Models\Organization;
use App\Models\SystemEvent;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Contrôles de santé de la plateforme. Chaque contrôle en défaut est inscrit au journal système ;
 * dès qu'il redevient bon, le problème est marqué résolu automatiquement.
 */
class SystemHealth
{
    public const HEARTBEAT_KEY = 'system:scheduler-heartbeat';

    public function __construct(private DocumentConverter $converter, private OrganizationStorageService $storage) {}

    /**
     * @return array<int, array{key: string, label: string, status: string, detail: string, category: string}>
     */
    public function checks(): array
    {
        return array_merge(
            [$this->database(), $this->platformStorage()],
            $this->dedicatedStorages(),
            [$this->queue(), $this->failedJobs(), $this->scheduler(), $this->integrity(), $this->diskSpace(), $this->tools()],
        );
    }

    // Exécute les contrôles et met à jour le journal système
    public function run(): array
    {
        $checks = $this->checks();
        foreach ($checks as $check) {
            $fingerprint = hash('sha256', 'health|' . $check['key']);
            if ($check['status'] === 'ok') {
                SystemEvent::resolveFingerprint($fingerprint);
            } else {
                SystemEvent::record($check['status'] === 'error' ? 'error' : 'warning', $check['category'],
                    "{$check['label']} : {$check['detail']}", [], null, $fingerprint);
            }
        }

        return $checks;
    }

    private function check(string $key, string $label, string $category, string $status, string $detail): array
    {
        return compact('key', 'label', 'category', 'status', 'detail');
    }

    private function database(): array
    {
        try {
            $start = microtime(true);
            DB::select('select 1');
            $ms = (int) round((microtime(true) - $start) * 1000);

            return $this->check('database', 'Base de données', 'database', $ms > 1000 ? 'warning' : 'ok', "Joignable ({$ms} ms)");
        } catch (Throwable $e) {
            return $this->check('database', 'Base de données', 'database', 'error', 'Injoignable : ' . $e->getMessage());
        }
    }

    private function platformStorage(): array
    {
        $label = 'Stockage MinIO de la plateforme';
        try {
            $disk = Storage::disk('s3');
            if (!$disk instanceof AwsS3V3Adapter) {
                return $this->check('storage', $label, 'storage', 'ok', 'Stockage local (développement)');
            }
            $bucket = (string) config('filesystems.disks.s3.bucket');
            $start = microtime(true);
            $exists = $disk->getClient()->doesBucketExistV2($bucket, false);
            $ms = (int) round((microtime(true) - $start) * 1000);

            return $exists
                ? $this->check('storage', $label, 'storage', $ms > 2000 ? 'warning' : 'ok', "Bucket « {$bucket} » joignable ({$ms} ms)")
                : $this->check('storage', $label, 'storage', 'error', "Le bucket « {$bucket} » n'existe pas sur le serveur MinIO");
        } catch (Throwable $e) {
            return $this->check('storage', $label, 'storage', 'error', 'Serveur MinIO injoignable : ' . $e->getMessage());
        }
    }

    // Entreprises qui ont leur propre bucket ou serveur MinIO
    private function dedicatedStorages(): array
    {
        $checks = [];
        foreach (Organization::whereNotNull('storage_bucket')->orWhereNotNull('storage_endpoint')->get() as $organization) {
            $label = "Stockage de « {$organization->name} »";
            try {
                $ok = $this->storage->bucketExists($organization);
                $checks[] = $this->check("storage-{$organization->id}", $label, 'storage', $ok ? 'ok' : 'error',
                    $ok ? "Bucket « {$organization->storageBucket()} » joignable" : "Le bucket « {$organization->storageBucket()} » n'existe pas");
            } catch (Throwable $e) {
                $checks[] = $this->check("storage-{$organization->id}", $label, 'storage', 'error', $e->getMessage());
            }
        }

        return $checks;
    }

    private function queue(): array
    {
        $label = 'Tâches en arrière-plan';
        $connection = config('queue.default');
        if ($connection === 'sync') {
            return $this->check('queue', $label, 'queue', 'ok', 'Exécutées immédiatement (pas de file d\'attente)');
        }
        if ($connection !== 'database') {
            return $this->check('queue', $label, 'queue', 'ok', "File « {$connection} »");
        }

        try {
            $pending = DB::table('jobs')->count();
            $oldest  = DB::table('jobs')->min('available_at');
            $minutes = $oldest ? (int) floor((time() - (int) $oldest) / 60) : 0;

            if ($pending && $minutes >= 15) {
                return $this->check('queue', $label, 'queue', 'error',
                    "{$pending} tâche(s) en attente depuis {$minutes} min : aucun processus ne les traite. Lancez « php artisan queue:work » (ou un service supervisor).");
            }

            return $this->check('queue', $label, 'queue', 'ok', $pending ? "{$pending} tâche(s) en cours de traitement" : 'Aucune tâche en attente');
        } catch (Throwable $e) {
            return $this->check('queue', $label, 'queue', 'error', $e->getMessage());
        }
    }

    private function failedJobs(): array
    {
        try {
            $failed = DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count();

            return $this->check('failed-jobs', 'Tâches en échec (24 h)', 'queue', $failed ? 'warning' : 'ok',
                $failed ? "{$failed} tâche(s) en échec — détail : php artisan queue:failed" : 'Aucune');
        } catch (Throwable) {
            return $this->check('failed-jobs', 'Tâches en échec (24 h)', 'queue', 'ok', 'Non suivies');
        }
    }

    private function scheduler(): array
    {
        $label = 'Planificateur (cron)';
        $last = Cache::get(self::HEARTBEAT_KEY);
        if (!$last) {
            return $this->check('scheduler', $label, 'scheduler', 'warning',
                'Jamais exécuté : les rappels, contrôles d\'intégrité et purges ne tournent pas. Ajoutez la tâche cron « * * * * * php artisan schedule:run ».');
        }
        $minutes = (int) floor((time() - (int) $last) / 60);

        return $minutes > 10
            ? $this->check('scheduler', $label, 'scheduler', 'error', "Arrêté depuis {$minutes} min : vérifiez la tâche cron « php artisan schedule:run ».")
            : $this->check('scheduler', $label, 'scheduler', 'ok', 'Actif (dernier passage il y a ' . max(0, $minutes) . ' min)');
    }

    private function integrity(): array
    {
        $last = IntegrityCheck::withoutGlobalScopes()->latest('id')->first();
        if (!$last) {
            return $this->check('integrity', 'Contrôle d\'intégrité', 'integrity', 'warning', 'Jamais exécuté (php artisan documents:verify-integrity)');
        }
        $days = (int) $last->created_at->diffInDays(now());

        return $days > 8
            ? $this->check('integrity', 'Contrôle d\'intégrité', 'integrity', 'warning', "Dernier contrôle il y a {$days} jours (prévu chaque dimanche)")
            : $this->check('integrity', 'Contrôle d\'intégrité', 'integrity', 'ok', 'Dernier contrôle le ' . $last->created_at->format('d/m/Y H:i'));
    }

    private function diskSpace(): array
    {
        $free  = @disk_free_space(storage_path());
        $total = @disk_total_space(storage_path());
        if (!$free || !$total) {
            return $this->check('disk', 'Espace disque du serveur', 'system', 'ok', 'Non mesurable');
        }
        $percent = (int) round($free / $total * 100);
        $status  = $percent < 3 ? 'error' : ($percent < 10 ? 'warning' : 'ok');

        return $this->check('disk', 'Espace disque du serveur', 'system', $status, formatBytes($free) . " libres ({$percent} %)");
    }

    private function tools(): array
    {
        $tools = [
            'tesseract'   => 'OCR des scans',
            'pdftotext'   => 'texte des PDF',
            'pdftoppm'    => 'OCR des PDF scannés',
            'libreoffice' => 'aperçus et copies PDF/A',
            'gs'          => 'PDF/A des PDF',
        ];
        $missing = array_filter($tools, fn ($_, $binary) => $this->converter->binary($binary) === null, ARRAY_FILTER_USE_BOTH);

        return $missing
            ? $this->check('tools', 'Outils de traitement', 'processing', 'warning',
                'Absents : ' . implode(', ', array_map(fn ($b, $use) => "{$b} ({$use})", array_keys($missing), $missing)))
            : $this->check('tools', 'Outils de traitement', 'processing', 'ok', 'Tous présents');
    }
}
