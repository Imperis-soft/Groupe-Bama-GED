<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Category;
use App\Models\User;

use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    // Les attributs pouvant être assignés en masse
    protected $fillable = [
        'reference',
        'title',
        'file_path',
        'version',
        'status',
        'archived_at',
        'expires_at',
        'checksum',
        'is_confidential',
        'approval_workflow',
        'retention_years',
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
        'archived_at' => 'datetime',
        'expires_at' => 'datetime',
        'approval_workflow' => 'array',
        'is_confidential' => 'boolean',
    ];

    // Scope pour la recherche en texte intégral
    public function scopeFullTextSearch($query, $term)
    {
        if (! $term) return $query;

        $ts = "to_tsvector('french', coalesce(title,'') || ' ' || coalesce(reference,'') || ' ' || coalesce(content_text,''))";
        return $query->whereRaw("{$ts} @@ plainto_tsquery('french', ?)", [$term]);
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

    // Archiver le document
    public function archive()
    {
        $this->update([
            'status' => 'archived',
            'archived_at' => now(),
        ]);
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

    // Vérifier si le document est verrouillé (et non expiré)
    public function isLocked(): bool
    {
        $lock = $this->lock;
        return $lock && !$lock->isExpired();
    }

    // Vérifier si l'utilisateur courant peut éditer
    public function canEdit(?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();
        if (!$userId) return false;
        // Admin ou créateur = toujours oui
        $user = \App\Models\User::find($userId);
        if ($user?->hasRole('admin') || $this->creator_id === $userId) return true;
        // Sinon vérifier ACL
        $perm = $this->permissions()->where('user_id', $userId)->first();
        return $perm && $perm->can_edit && !$perm->isExpired();
    }

    // Vérifier si l'utilisateur courant peut approuver
    public function canApprove(?int $userId = null): bool
    {
        $userId = $userId ?? auth()->id();
        if (!$userId) return false;
        $user = \App\Models\User::find($userId);
        if ($user?->hasRole('admin')) return true;
        $perm = $this->permissions()->where('user_id', $userId)->first();
        return $perm && $perm->can_approve && !$perm->isExpired();
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
}