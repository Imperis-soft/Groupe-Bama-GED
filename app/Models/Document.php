<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Category;
use App\Models\User;

class Document extends Model
{
    use HasFactory;

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
}