<?php

namespace App\Support;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Règles effectives d'un dossier (catégorie) et leur origine, pour l'affichage :
 * conservation (la plus proche définie en remontant) et accès par service (hérités des parents,
 * le niveau le plus élevé l'emporte) — même logique que Category::retentionRule() et User::categoryAccess().
 */
class FolderInfo
{
    /** @param Category[] $path Chemin depuis la racine jusqu'au dossier (inclus) */
    public function __construct(private array $path)
    {
    }

    public static function for(Category $folder, CategoryTree $tree): self
    {
        return new self($tree->path($folder->id) ?: [$folder]);
    }

    public function folder(): Category
    {
        return end($this->path);
    }

    /** ['years' => ?int, 'trigger' => string, 'disposition' => string, 'source' => ?Category, 'inherited' => bool] */
    public function retention(): array
    {
        foreach (array_reverse($this->path) as $category) {
            if ($category->default_retention_years) {
                return [
                    'years'       => (int) $category->default_retention_years,
                    'trigger'     => $category->retention_trigger ?: 'created',
                    'disposition' => $category->final_disposition ?: 'review',
                    'source'      => $category,
                    'inherited'   => $category->id !== $this->folder()->id,
                ];
            }
        }

        return ['years' => null, 'trigger' => 'created', 'disposition' => 'review', 'source' => null, 'inherited' => false];
    }

    /**
     * Services ayant accès : [['name', 'level' => view|edit, 'source' => Category, 'inherited' => bool], …]
     */
    public function departments(): Collection
    {
        $ids = array_map(fn ($c) => $c->id, $this->path);
        $byId = collect($this->path)->keyBy('id');
        $rank = ['view' => 1, 'edit' => 2];

        $rules = DB::table('category_department')
            ->join('departments', 'departments.id', '=', 'category_department.department_id')
            ->whereIn('category_department.category_id', $ids)
            ->get(['category_department.category_id', 'category_department.access_level', 'departments.id as department_id', 'departments.name']);

        $result = [];
        // Du dossier vers la racine : à niveau égal, la règle la plus proche est retenue comme origine
        foreach (array_reverse($ids) as $categoryId) {
            foreach ($rules->where('category_id', $categoryId) as $rule) {
                $current = $result[$rule->department_id] ?? null;
                if (!$current || $rank[$rule->access_level] > $rank[$current['level']]) {
                    $result[$rule->department_id] = [
                        'name'      => $rule->name,
                        'level'     => $rule->access_level,
                        'source'    => $byId[$categoryId],
                        'inherited' => $categoryId !== $this->folder()->id,
                    ];
                }
            }
        }

        return collect($result)->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();
    }

    // Dossier public (consultable par tous) : lui-même ou un parent ; renvoie le dossier qui l'ouvre
    public function publicSource(): ?Category
    {
        foreach (array_reverse($this->path) as $category) {
            if ($category->is_public) {
                return $category;
            }
        }

        return null;
    }
}
