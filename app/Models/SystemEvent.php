<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

/**
 * Événement du journal système (console super admin).
 * SystemEvent::record() ne lève jamais d'exception : signaler un problème ne doit pas en créer un autre.
 */
class SystemEvent extends Model
{
    public const LEVELS = [
        'critical' => 'Critique',
        'error'    => 'Erreur',
        'warning'  => 'Avertissement',
        'info'     => 'Information',
    ];

    public const CATEGORIES = [
        'application' => 'Application',
        'storage'     => 'Stockage (MinIO)',
        'database'    => 'Base de données',
        'queue'       => 'Tâches en arrière-plan',
        'mail'        => 'Emails',
        'integrity'   => 'Intégrité',
        'scheduler'   => 'Planificateur',
        'processing'  => 'Traitement des fichiers',
        'system'      => 'Serveur',
    ];

    protected $fillable = [
        'level', 'category', 'message', 'context', 'organization_id', 'fingerprint',
        'occurrences', 'first_seen_at', 'last_seen_at', 'resolved_at', 'resolved_by',
    ];

    protected $casts = [
        'context'       => 'array',
        'first_seen_at' => 'datetime',
        'last_seen_at'  => 'datetime',
        'resolved_at'   => 'datetime',
    ];

    // Évite de s'auto-signaler en boucle si l'enregistrement lui-même échoue
    private static bool $recording = false;

    public function organization() { return $this->belongsTo(Organization::class); }
    public function resolver()     { return $this->belongsTo(User::class, 'resolved_by'); }

    public function scopeOpen($query) { return $query->whereNull('resolved_at'); }

    /**
     * Enregistre un problème. Le même problème encore ouvert (même empreinte) est compté, pas dupliqué ;
     * s'il avait été marqué résolu et revient, il est rouvert.
     */
    public static function record(string $level, string $category, string $message, array $context = [], ?int $organizationId = null, ?string $fingerprint = null): ?self
    {
        if (self::$recording) {
            return null;
        }
        self::$recording = true;

        try {
            $fingerprint ??= hash('sha256', $category . '|' . $organizationId . '|' . $message);
            $now = now();

            $event = self::where('fingerprint', $fingerprint)->latest('id')->first();
            if ($event && (!$event->resolved_at || $event->resolved_at->gt($now->copy()->subDays(30)))) {
                $reopened = $event->resolved_at !== null;
                $event->forceFill([
                    'level'        => $level,
                    'message'      => Str::limit($message, 1000, ''),
                    'context'      => $context ?: $event->context,
                    'occurrences'  => $event->occurrences + 1,
                    'last_seen_at' => $now,
                    'resolved_at'  => null,
                    'resolved_by'  => null,
                ])->save();
                if ($reopened) {
                    self::alertSuperAdmins($event);
                }
                return $event;
            }

            $event = self::create([
                'level' => $level, 'category' => $category, 'message' => Str::limit($message, 1000, ''),
                'context' => $context ?: null, 'organization_id' => $organizationId, 'fingerprint' => $fingerprint,
                'first_seen_at' => $now, 'last_seen_at' => $now,
            ]);
            self::alertSuperAdmins($event);

            return $event;
        } catch (Throwable) {
            return null;
        } finally {
            self::$recording = false;
        }
    }

    // Problème disparu (contrôle de santé de nouveau bon) : résolu automatiquement
    public static function resolveFingerprint(string $fingerprint): void
    {
        try {
            self::where('fingerprint', $fingerprint)->whereNull('resolved_at')->update(['resolved_at' => now()]);
        } catch (Throwable) {
        }
    }

    // Exception non gérée : catégorie déduite de son origine, regroupée par type + emplacement
    public static function fromException(Throwable $e): ?self
    {
        $class = get_class($e);
        $category = match (true) {
            str_starts_with($class, 'Aws\\') || str_starts_with($class, 'League\\Flysystem\\') => 'storage',
            $e instanceof \Illuminate\Database\QueryException || $e instanceof \PDOException => 'database',
            str_starts_with($class, 'Symfony\\Component\\Mailer\\')                          => 'mail',
            default                                                                          => 'application',
        };

        $request = app()->runningInConsole() ? null : request();
        $context = array_filter([
            'exception' => $class,
            'file'      => str_replace(base_path() . '/', '', $e->getFile()) . ':' . $e->getLine(),
            'url'       => $request ? $request->method() . ' ' . $request->fullUrl() : null,
            'user'      => $request?->user()?->email,
            'command'   => app()->runningInConsole() ? implode(' ', array_slice($_SERVER['argv'] ?? [], 1)) : null,
            'trace'     => collect($e->getTrace())->take(6)->map(fn ($f) => ($f['class'] ?? '') . ($f['type'] ?? '') . ($f['function'] ?? '')
                . (isset($f['file']) ? ' (' . str_replace(base_path() . '/', '', $f['file']) . ':' . ($f['line'] ?? '?') . ')' : ''))->all(),
        ]);

        return self::record($category === 'application' ? 'error' : 'critical', $category,
            class_basename($class) . ' : ' . ($e->getMessage() ?: 'sans message'), $context,
            \App\Support\Tenant::check() ? \App\Support\Tenant::id() : null,
            hash('sha256', $class . '|' . $e->getFile() . ':' . $e->getLine()));
    }

    // Première apparition (ou retour) d'un problème sérieux : notification aux super admins
    private static function alertSuperAdmins(self $event): void
    {
        if (!in_array($event->level, ['error', 'critical'], true)) {
            return;
        }
        foreach (User::withoutGlobalScopes()->where('is_super_admin', true)->get() as $superAdmin) {
            GedNotification::create([
                'user_id' => $superAdmin->id,
                'type'    => 'system_event',
                'title'   => (self::LEVELS[$event->level] ?? 'Erreur') . ' — ' . (self::CATEGORIES[$event->category] ?? $event->category),
                'message' => Str::limit($event->message, 250),
                'link'    => route('super.system.index', ['event' => $event->id]),
                'is_read' => false,
                'email_sent' => false,
            ]);
        }
    }
}
