<?php

namespace App\Models;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\AwsS3V3Adapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Organization extends Model
{
    protected $fillable = [
        'name', 'slug', 'reference_prefix', 'email', 'phone', 'address',
        'status', 'suspended_reason', 'notes',
        'storage_bucket', 'storage_endpoint', 'storage_url', 'storage_region', 'storage_key', 'storage_secret',
    ];

    protected $hidden = ['storage_secret'];

    protected $casts = [
        'storage_secret' => 'encrypted',
    ];

    // Champs de connexion MinIO : un changement invalide le disque en cache
    public const STORAGE_FIELDS = ['storage_bucket', 'storage_endpoint', 'storage_url', 'storage_region', 'storage_key', 'storage_secret'];

    protected static function booted(): void
    {
        static::creating(function (Organization $org) {
            $org->slug ??= static::uniqueSlug($org->name);
            // Dossier de l'entreprise dans le bucket : fixé une fois pour toutes (non modifiable via fillable)
            $org->storage_folder ??= static::uniqueStorageFolder($org->slug);
        });
    }

    // Nom de dossier S3 sûr et unique : minuscules, chiffres et tirets uniquement
    public static function uniqueStorageFolder(string $slug): string
    {
        $base = substr(trim(preg_replace('/[^a-z0-9-]+/', '-', strtolower($slug)), '-'), 0, 90) ?: 'entreprise';
        $folder = $base;
        $i = 2;
        while (static::where('storage_folder', $folder)->exists()) {
            $folder = $base . '-' . $i++;
        }
        return $folder;
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'entreprise';
        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    public function users()         { return $this->hasMany(User::class); }
    public function documents()     { return $this->hasMany(Document::class); }
    public function categories()    { return $this->hasMany(Category::class); }
    public function subscriptions() { return $this->hasMany(Subscription::class)->latest('ends_at'); }

    // Cache mémoire de l'abonnement en cours (false = pas encore chargé)
    protected Subscription|null|false $activeSubscriptionCache = false;

    // Abonnement en cours (non annulé, période couvrant aujourd'hui)
    public function activeSubscription(): ?Subscription
    {
        if ($this->activeSubscriptionCache !== false) {
            return $this->activeSubscriptionCache;
        }

        return $this->activeSubscriptionCache = $this->subscriptions()
            ->with('plan')
            ->where('status', '!=', 'cancelled')
            ->whereDate('starts_at', '<=', today())
            ->whereDate('ends_at', '>=', today())
            ->first();
    }

    public function forgetSubscriptionCache(): void
    {
        $this->activeSubscriptionCache = false;
    }

    // Dernier abonnement non annulé (pour calculer la date de renouvellement)
    public function latestSubscription(): ?Subscription
    {
        return $this->subscriptions()->with('plan')->where('status', '!=', 'cancelled')->first();
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    // L'entreprise peut-elle utiliser l'application ?
    public function canAccess(): bool
    {
        return !$this->isSuspended() && $this->activeSubscription() !== null;
    }

    public function currentPlan(): ?Plan
    {
        return $this->activeSubscription()?->plan;
    }

    // Espace occupé par tous les fichiers (toutes versions), en octets
    public function storageUsedBytes(): int
    {
        return (int) DocumentVersion::whereIn(
            'document_id',
            Document::withoutGlobalScopes()->where('organization_id', $this->id)->select('id')
        )->sum('metadata->size');
    }

    public function usersCount(): int
    {
        return User::withoutGlobalScopes()->where('organization_id', $this->id)->count();
    }

    // Peut-on ajouter $count utilisateur(s) selon l'offre ?
    public function canAddUsers(int $count = 1): bool
    {
        $max = $this->currentPlan()?->max_users;
        return $max === null || $this->usersCount() + $count <= $max;
    }

    // Peut-on stocker $bytes supplémentaires selon l'offre ?
    public function canStore(int $bytes): bool
    {
        $maxMb = $this->currentPlan()?->max_storage_mb;
        return $maxMb === null || $this->storageUsedBytes() + $bytes <= $maxMb * 1024 * 1024;
    }

    // ---------------------------------------------------------------
    // Stockage MinIO
    // ---------------------------------------------------------------

    protected ?Filesystem $diskCache = null;

    // Bucket dédié à l'entreprise ? (sinon : bucket partagé de la plateforme)
    public function hasDedicatedBucket(): bool
    {
        return filled($this->storage_bucket);
    }

    public function hasDedicatedServer(): bool
    {
        return filled($this->storage_endpoint);
    }

    public function storageBucket(): string
    {
        return $this->storage_bucket ?: (string) config('filesystems.disks.s3.bucket');
    }

    // Configuration du disque S3 : valeurs de l'entreprise, sinon celles de la plateforme
    public function storageConfig(): array
    {
        $config = config('filesystems.disks.s3');

        if ($this->hasDedicatedServer()) {
            $config['endpoint'] = $this->storage_endpoint;
            $config['url']      = $this->storage_url ?: $this->storage_endpoint;
            $config['key']      = $this->storage_key;
            $config['secret']   = $this->storage_secret;
        } elseif (filled($this->storage_url)) {
            $config['url'] = $this->storage_url;
        }

        $config['region'] = $this->storage_region ?: ($config['region'] ?? 'us-east-1');
        $config['bucket'] = $this->storageBucket();

        return $config;
    }

    // Disque MinIO de l'entreprise (à utiliser à la place de Storage::disk('s3'))
    public function disk(): Filesystem
    {
        if ($this->diskCache && !$this->isDirty(self::STORAGE_FIELDS)) {
            return $this->diskCache;
        }

        return $this->diskCache = $this->buildDisk();
    }

    private function buildDisk(): Filesystem
    {
        $platform = Storage::disk('s3');

        // Disque de la plateforme simulé (tests) ou local : un sous-dossier par bucket
        if (!$platform instanceof AwsS3V3Adapter) {
            return $this->hasDedicatedBucket()
                ? Storage::build(['driver' => 'local', 'root' => $platform->path($this->storageBucket()), 'throw' => false])
                : $platform;
        }

        // Aucun réglage propre : bucket partagé de la plateforme
        if (!$this->hasDedicatedBucket() && !$this->hasDedicatedServer() && blank($this->storage_url) && blank($this->storage_region)) {
            return $platform;
        }

        return Storage::build($this->storageConfig());
    }

    // Dossier des documents dans le bucket
    public function documentsDir(): string
    {
        return $this->hasDedicatedBucket() ? 'documents/' : $this->storagePrefix() . '/documents/';
    }

    // Dossier des exports complets dans le bucket
    public function exportsDir(): string
    {
        return $this->hasDedicatedBucket() ? 'exports/' : $this->storagePrefix() . '/exports/';
    }

    // Dossier des procès-verbaux d'élimination dans le bucket
    public function eliminationRecordsDir(): string
    {
        return $this->hasDedicatedBucket() ? 'proces-verbaux/' : $this->storagePrefix() . '/proces-verbaux/';
    }

    // Dossier de l'entreprise dans le bucket partagé (ex. groupe-bama)
    public function storagePrefix(): string
    {
        return $this->storage_folder ?: 'org-' . $this->id;
    }

    // Le chemin appartient-il bien à l'espace de cette entreprise ? (garde-fou contre tout accès croisé)
    public function ownsPath(?string $path): bool
    {
        if (blank($path) || str_contains($path, '..') || str_starts_with($path, '/')) {
            return false;
        }

        return $this->hasDedicatedBucket() || str_starts_with($path, $this->storagePrefix() . '/');
    }

    // URL publique MinIO d'un fichier
    public function fileUrl(string $path): string
    {
        $config = $this->storageConfig();
        $base   = rtrim((string) ($config['url'] ?: $config['endpoint']), '/');

        return $base . '/' . $config['bucket'] . '/' . ltrim($path, '/');
    }
}
