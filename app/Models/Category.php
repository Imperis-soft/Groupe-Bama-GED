<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToOrganization;

class Category extends Model
{
    use HasFactory, BelongsToOrganization;

    // Les attributs pouvant être assignés en masse
    protected $fillable = ['organization_id', 'name', 'slug', 'description', 'parent_id', 'created_by', 'default_retention_years', 'retention_trigger', 'final_disposition', 'is_public', 'requires_approval', 'requires_signature'];

    // Point de départ du délai de conservation
    public const RETENTION_TRIGGERS = [
        'created'         => 'Date de dépôt du document',
        'fiscal_year_end' => 'Fin de l\'exercice (31/12 de l\'année de dépôt)',
        'due_date'        => 'Date d\'échéance du document (fin de contrat…)',
        'archived'        => 'Date d\'archivage (dossier clos)',
    ];

    // Libellés courts (procès-verbal, listes)
    public const RETENTION_TRIGGERS_SHORT = [
        'created' => 'dépôt', 'fiscal_year_end' => 'fin d\'exercice', 'due_date' => 'échéance', 'archived' => 'archivage',
    ];

    // Sort final, à la fin du délai de conservation
    public const FINAL_DISPOSITIONS = [
        'review'  => 'Réexaminer (décision au cas par cas)',
        'destroy' => 'Détruire (après validation)',
        'keep'    => 'Conserver définitivement',
    ];

    /**
     * Règle de conservation applicable : celle de la catégorie, sinon de la catégorie parente la plus proche
     * qui en définit une. ['years' => ?int, 'trigger' => string, 'disposition' => string]
     */
    public function retentionRule(): array
    {
        $category = $this;
        for ($depth = 0; $category && $depth < 50; $depth++) {
            if ($category->default_retention_years) {
                return [
                    'years'       => (int) $category->default_retention_years,
                    'trigger'     => $category->retention_trigger ?: 'created',
                    'disposition' => $category->final_disposition ?: 'review',
                ];
            }
            $category = $category->parent;
        }

        return ['years' => null, 'trigger' => 'created', 'disposition' => 'review'];
    }

    /**
     * Circuit imposé aux documents de la catégorie : réglage de la catégorie, sinon de la catégorie parente
     * la plus proche qui en définit un. ['approval' => bool, 'signature' => bool]
     */
    public function workflowRule(): array
    {
        $rule = ['approval' => null, 'signature' => null];
        $category = $this;
        for ($depth = 0; $category && $depth < 50 && in_array(null, $rule, true); $depth++) {
            $rule['approval'] ??= $category->requires_approval;
            $rule['signature'] ??= $category->requires_signature;
            $category = $category->parent;
        }

        return ['approval' => (bool) $rule['approval'], 'signature' => (bool) $rule['signature']];
    }

    protected $casts = ['is_public' => 'boolean', 'requires_approval' => 'boolean', 'requires_signature' => 'boolean'];

    public function documents()  { return $this->hasMany(Document::class); }
    public function parent()     { return $this->belongsTo(Category::class, 'parent_id'); }
    public function children()   { return $this->hasMany(Category::class, 'parent_id'); }
    public function creator()    { return $this->belongsTo(User::class, 'created_by'); }
    public function departments()
    {
        return $this->belongsToMany(Department::class)->withPivot('access_level');
    }

    // Slug libre pour l'entreprise courante : « factures », « factures-2 »…
    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = \Illuminate\Support\Str::slug($name) ?: 'dossier';
        $slug = $base;
        for ($i = 2; static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists(); $i++) {
            $slug = $base . '-' . $i;
        }

        return $slug;
    }

    public function allChildren(): \Illuminate\Support\Collection
    {
        return $this->children->flatMap(fn($c) => collect([$c])->merge($c->allChildren()));
    }

    /**
     * Toutes les catégories de l'entreprise courante, triées en arbre (parent puis enfants).
     * Chaque élément reçoit un attribut "depth" (0 = racine) pour l'indentation.
     */
    public static function tree(): \Illuminate\Support\Collection
    {
        $byParent = static::orderBy('name')->get()->groupBy(fn ($c) => $c->parent_id ?? 0);
        $result = collect();
        $walk = function ($parentId, int $depth) use (&$walk, $byParent, $result) {
            foreach ($byParent->get($parentId, []) as $category) {
                $category->depth = $depth;
                $result->push($category);
                if ($depth < 50) {
                    $walk($category->id, $depth + 1);
                }
            }
        };
        $walk(0, 0);

        return $result;
    }
}
