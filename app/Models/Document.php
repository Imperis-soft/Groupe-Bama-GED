<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Category;
use App\Models\User;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToOrganization;

class Document extends Model
{
    use HasFactory, SoftDeletes, BelongsToOrganization;

    protected static function booted(): void
    {
        // Fin de conservation recalculée dès qu'un élément qui la détermine change
        static::saving(function (Document $document) {
            if (!$document->exists || $document->isDirty(['retention_years', 'category_id', 'expires_at', 'archived_at', 'retention_permanent'])) {
                $document->retention_until = $document->computeRetentionUntil();
            }
        });

        // Sécurité : un fichier doit toujours se trouver dans le dossier de son entreprise
        static::saving(function (Document $document) {
            if (!$document->isDirty('file_path') || blank($document->file_path)) {
                return;
            }
            $organization = $document->organization_id ? Organization::find($document->organization_id) : null;
            if ($organization && !$organization->ownsPath($document->file_path)) {
                throw new \RuntimeException("Chemin de fichier hors de l'espace de l'entreprise : {$document->file_path}");
            }
        });
    }

    // Les attributs pouvant être assignés en masse
    protected $fillable = [
        'organization_id',
        'reference',
        'title',
        'file_path',
        'minio_url',
        'version',
        'status',
        'archived_at',
        'archived_by',
        'archive_reason',
        'status_before_archive',
        'expires_at',
        'checksum',
        'is_confidential',
        'legal_hold',
        'legal_hold_at',
        'legal_hold_by',
        'legal_hold_reason',
        'approval_workflow',
        'retention_years',
        'retention_permanent',
        'metadata',
        'tags',
        'content_text',
        'category_id',
        'creator_id',
    ];

    // Les attributs à caster
    protected $casts = [
        'metadata' => 'array',
        'tags' => 'array',
        'content_text' => 'string',
        'content_simhash' => 'integer',
        'archived_at' => 'datetime',
        'expires_at' => 'datetime',
        'approval_workflow' => 'array',
        'is_confidential' => 'boolean',
        'legal_hold' => 'boolean',
        'legal_hold_at' => 'datetime',
        'retention_until' => 'date',
        'integrity_checked_at' => 'datetime',
        'retention_permanent' => 'boolean',
        'official_copy_at' => 'datetime',
    ];

    // Scope pour la recherche en texte intégral
    public function scopeFullTextSearch($query, $term)
    {
        // Tous les mots doivent être présents ; moteur adapté à la base (voir App\Services\DocumentSearch)
        return app(\App\Services\DocumentSearch::class)->apply($query, $term, withScore: false);
    }

    // Opérateur LIKE insensible à la casse selon le driver
    public static function likeOperator(): string
    {
        return (new static)->getConnection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';
    }

    
    // Relation avec la catégorie
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relation avec le créateur

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    // Relation avec les versions
    public function versions()
    {
        return $this->hasMany(DocumentVersion::class)->orderBy('version_number', 'desc');
    }


    // Relation avec les logs d'audit
    public function auditLogs()
    {
        return $this->hasMany(DocumentAuditLog::class)->orderBy('created_at', 'desc');
    }

    // Scopes pour filtrer par statut
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Scope pour documents expirés
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    // Scope pour documents confidentiels
    public function scopeConfidential($query)
    {
        return $query->where('is_confidential', true);
    }

    // Vérifier si le document est archivé
    public function isArchived()
    {
        return $this->status === 'archived';
    }

    // Circuit en cours (approbation ou signature) : le contenu est gelé
    public function isInWorkflow(): bool
    {
        return in_array($this->status, ['review', 'signing'], true);
    }

    // Circuit imposé par la catégorie du document (ou une catégorie parente)
    public function workflowRule(): array
    {
        $category = $this->category_id ? Category::withoutGlobalScopes()->find($this->category_id) : null;

        return $category?->workflowRule() ?? ['approval' => false, 'signature' => false];
    }

    // Empreinte SHA-256 du fichier courant
    public function currentChecksum(): ?string
    {
        return $this->checksum ?: $this->versions()->where('version_number', $this->version)->value('checksum')
            ?: ($this->file_path && $this->disk()->exists($this->file_path) ? hash('sha256', $this->disk()->get($this->file_path)) : null);
    }

    // Le dernier circuit est allé au bout, sur le contenu actuel (retiré ou rejeté : non)
    public function hasValidApproval(): bool
    {
        $last = $this->approvalSteps()->reorder('step_order', 'desc')->first();
        $checksum = $this->currentChecksum();

        return $last && $last->isApproved() && $last->document_checksum && $checksum
            && hash_equals($last->document_checksum, $checksum);
    }

    // Des signatures peuvent être demandées : après l'approbation si la catégorie l'exige
    public function canBeSentForSignature(): bool
    {
        if (in_array($this->status, ['approved', 'signing', 'signed'], true)) {
            return true;
        }

        return $this->status === 'draft' && !$this->workflowRule()['approval'];
    }

    // Vérifier si le document est sous gel juridique (Legal Hold)
    public function isUnderLegalHold(): bool
    {
        return (bool) $this->legal_hold;
    }

    public function archivedBy()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    // Relation avec les vérifications de document
    public function verifications()
    {
        return $this->hasMany(DocumentVerification::class);
    }

    // Relation avec les permissions ACL
    public function permissions()
    {
        return $this->hasMany(DocumentPermission::class);
    }

    // Relation avec les partages
    public function shares()
    {
        return $this->hasMany(DocumentShare::class);
    }

    // Relation avec les commentaires (racine seulement)
    public function comments()
    {
        return $this->hasMany(DocumentComment::class)->whereNull('parent_id')->with('user', 'replies');
    }

    // Relation avec le verrou
    public function lock()
    {
        return $this->hasOne(DocumentLock::class);
    }

    // Relation avec les étapes d'approbation
    public function approvalSteps()
    {
        return $this->hasMany(ApprovalStep::class)->orderBy('step_order');
    }

    // Relation avec les signatures
    public function signatures()
    {
        return $this->hasMany(DocumentSignature::class);
    }

    // Relation avec les demandes de signature
    public function signatureRequests()
    {
        return $this->hasMany(SignatureRequest::class)->latest();
    }

    // Vérifier si le document est verrouillé (et non expiré)
    public function isLocked(): bool
    {
        $lock = $this->lock;
        return $lock && !$lock->isExpired();
    }

    // Vérifier si le document est verrouillé par un autre utilisateur que $userId
    public function isLockedByOther(?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();
        return $this->isLocked() && $this->lock->locked_by !== $userId;
    }

    // Résoudre l'utilisateur (évite une requête si c'est l'utilisateur connecté)
    protected static function resolveUser(?int $userId): ?User
    {
        if ($userId === null || $userId === auth()->id()) {
            return auth()->user();
        }
        return User::find($userId);
    }

    // Partage actif (non révoqué, non expiré) pour un utilisateur
    protected function activeShareFor(int $userId)
    {
        return $this->shares()
            ->where('shared_with', $userId)
            ->where('is_active', true)
            ->where(function ($q) { $q->whereNull('expires_at')->orWhere('expires_at', '>', now()); });
    }

    // Admin ou créateur : peut partager, archiver, configurer le workflow
    public function canManage(?int $userId = null): bool
    {
        $user = static::resolveUser($userId);
        if (!$user) return false;
        return $user->hasRole('admin') || $this->creator_id === $user->id;
    }

    // Vérifier si l'utilisateur courant peut éditer
    // Un document archivé est figé : personne ne le modifie (un admin doit d'abord le désarchiver)
    public function canEdit(?int $userId = null): bool
    {
        if ($this->isArchived()) return false;
        $user = static::resolveUser($userId);
        if (!$user) return false;
        if ($this->canManage($user->id)) return true;
        if ($this->categoryAccessFor($user) === 'edit') return true;
        // Sinon vérifier les partages actifs avec access_level edit
        return $this->activeShareFor($user->id)->where('access_level', 'edit')->exists();
    }

    // Vérifier si l'utilisateur courant peut approuver
    public function canApprove(?int $userId = null): bool
    {
        $user = static::resolveUser($userId);
        if (!$user) return false;
        if ($user->hasRole('admin')) return true;
        $perm = $this->permissions()->where('user_id', $user->id)->first();
        return $perm && $perm->can_approve && !$perm->isExpired();
    }

    // Niveau d'accès donné par les services de l'utilisateur sur la catégorie du document.
    // Les documents confidentiels ne sont jamais ouverts par service.
    protected function categoryAccessFor(User $user): ?string
    {
        return $this->is_confidential ? null : $user->categoryAccessLevel($this->category_id);
    }

    // Scope : documents visibles par un utilisateur donné
    // - Admin : tous les documents
    // - Autres : ses propres documents + ceux partagés avec lui + ceux dont il est approbateur
    //   + les documents non confidentiels des catégories ouvertes à ses services (ou publiques)
    public function scopeVisibleTo($query, ?int $userId = null)
    {
        $user = static::resolveUser($userId);
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('admin')) {
            return $query; // admin voit tout
        }

        $userId = $user->id;
        $categoryIds = $user->viewableCategoryIds();
        return $query->where(function ($q) use ($userId, $categoryIds) {
            // Auteur du document
            $q->where('creator_id', $userId)
              // OU partagé avec lui (actif, non expiré)
              ->orWhereHas('shares', function ($s) use ($userId) {
                  $s->where('shared_with', $userId)
                    ->where('is_active', true)
                    ->where(function ($d) {
                        $d->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
              })
              // OU il fait partie du workflow d'approbation
              ->orWhereHas('approvalSteps', fn ($a) => $a->where('approver_id', $userId))
              // OU on lui a demandé de le signer
              ->orWhereHas('signatureRequests', fn ($r) => $r->where('user_id', $userId)->where('status', '!=', 'cancelled'));

            // OU rangé dans une catégorie ouverte à ses services
            if ($categoryIds) {
                $q->orWhere(fn ($c) => $c->whereIn('category_id', $categoryIds)->where('is_confidential', false));
            }
        });
    }

    // Vérifier si l'utilisateur courant peut voir ce document
    public function canView(?int $userId = null): bool
    {
        $user = static::resolveUser($userId);
        if (!$user) return false;
        if ($this->canManage($user->id)) return true;
        if ($this->categoryAccessFor($user)) return true;
        return $this->activeShareFor($user->id)->exists()
            || $this->approvalSteps()->where('approver_id', $user->id)->exists()
            || $this->signatureRequests()->where('user_id', $user->id)->where('status', '!=', 'cancelled')->exists();
    }

    // Niveau d'accès maximal qu'un utilisateur peut déléguer via un partage
    public function maxShareLevel(?int $userId = null): ?string
    {
        $user = static::resolveUser($userId);
        if (!$user) return null;
        if ($this->canManage($user->id)) return 'edit';
        $shared = $this->activeShareFor($user->id)->value('access_level');
        $viaCategory = $this->categoryAccessFor($user);
        return ($shared === 'edit' || $viaCategory === 'edit') ? 'edit' : ($shared ?? $viaCategory);
    }

    // Favoris
    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'document_favorites');
    }

    public function isFavoritedBy(?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();
        return $this->favoritedBy()->where('user_id', $userId)->exists();
    }

    // Vérification unique (relation)
    public function verification()
    {
        return $this->hasOne(DocumentVerification::class)->latest();
    }

    // Format du fichier courant (famille, icône, mode d'aperçu)
    public function fileType(): \App\Support\FileType
    {
        return \App\Support\FileType::fromPath($this->file_path);
    }

    // Texte du document + empreinte de contenu (détection des doublons)
    public function fillContent(?string $text): static
    {
        $tokens = \App\Support\ContentFingerprint::tokens((string) $text);
        $comparable = \App\Support\ContentFingerprint::isComparable($tokens);

        $this->content_text    = filled($text) ? $text : null;
        $this->content_hash    = $comparable ? \App\Support\ContentFingerprint::hash($tokens) : null;
        $this->content_simhash = $comparable ? \App\Support\ContentFingerprint::simhash($tokens) : null;

        return $this;
    }

    // Règle de conservation de la catégorie du document (point de départ, sort final)
    public function retentionRule(): array
    {
        $category = $this->category_id ? Category::withoutGlobalScopes()->find($this->category_id) : null;

        return $category?->retentionRule() ?? ['years' => null, 'trigger' => 'created', 'disposition' => 'review'];
    }

    /**
     * Date de fin de conservation : point de départ (selon la catégorie) + durée de conservation du document.
     * null : conservation définitive, durée nulle, ou délai pas encore commencé (dossier non archivé, échéance inconnue).
     */
    public function computeRetentionUntil(): ?\Carbon\Carbon
    {
        if ($this->retention_permanent || !$this->retention_years) {
            return null;
        }

        $created = $this->created_at ?? now();
        $start = match ($this->retentionRule()['trigger']) {
            'archived'        => $this->archived_at,
            'due_date'        => $this->expires_at,
            'fiscal_year_end' => $created->copy()->endOfYear(),
            default           => $created,
        };

        return $start ? \Carbon\Carbon::parse($start)->startOfDay()->addYears((int) $this->retention_years) : null;
    }

    // Sort final applicable à ce document
    public function finalDisposition(): string
    {
        return $this->retention_permanent ? 'keep' : $this->retentionRule()['disposition'];
    }

    // Fin de conservation atteinte et élimination possible (jamais pour un document gelé ou à conserver)
    public function isEliminable(): bool
    {
        return $this->retention_until && $this->retention_until->lte(today())
            && !$this->retention_permanent && !$this->isUnderLegalHold()
            && $this->finalDisposition() !== 'keep' && !$this->trashed();
    }

    // Documents arrivés en fin de conservation, à décider (détruire / prolonger / conserver)
    public function scopeRetentionDue($query, ?\Carbon\Carbon $until = null)
    {
        return $query->whereNotNull('retention_until')
            ->whereDate('retention_until', '<=', ($until ?? today())->toDateString())
            ->where('retention_permanent', false);
    }

    // Disque MinIO de l'entreprise propriétaire du document
    public function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        $organization = $this->organization ?? \App\Support\Tenant::organization();

        return $organization ? $organization->disk() : \Illuminate\Support\Facades\Storage::disk('s3');
    }
}
