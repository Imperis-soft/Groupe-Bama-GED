<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Document;
use Illuminate\Support\Collection;

/**
 * Arborescence des catégories de l'entreprise courante, chargée en deux requêtes :
 * les catégories, puis le nombre de documents visibles par l'utilisateur dans chacune.
 *
 * Chaque catégorie reçoit : depth, children (Collection), documents_count (directs)
 * et total_count (sous-catégories comprises).
 */
class CategoryTree
{
    /** @var Collection<int, Category> */
    private Collection $nodes;
    private Collection $roots;
    private int $uncategorizedCount = 0;

    public function __construct(bool $withCounts = true)
    {
        $this->nodes = Category::orderBy('name')->get()->keyBy('id');

        $counts = $withCounts
            ? Document::visibleTo()->toBase()->selectRaw('category_id, COUNT(*) as aggregate')->groupBy('category_id')->pluck('aggregate', 'category_id')
            : collect();
        $this->uncategorizedCount = (int) ($counts[''] ?? $counts[null] ?? 0);

        foreach ($this->nodes as $node) {
            $node->setRelation('children', collect());
            $node->documents_count = (int) ($counts[$node->id] ?? 0);
        }
        foreach ($this->nodes as $node) {
            // Parent absent (supprimé, autre entreprise) : la catégorie est traitée comme une racine
            if ($node->parent_id && ($parent = $this->nodes->get($node->parent_id)) && $parent->id !== $node->id) {
                $parent->children->push($node);
            }
        }
        $this->roots = $this->nodes->filter(fn ($n) => !$n->parent_id || !$this->nodes->has($n->parent_id) || $n->parent_id === $n->id)->values();

        $visited = [];
        $walk = function (Category $node, int $depth) use (&$walk, &$visited): int {
            if (isset($visited[$node->id])) {
                return 0; // protection contre une boucle parent ↔ enfant
            }
            $visited[$node->id] = true;
            $node->depth = $depth;
            $total = $node->documents_count;
            // Les enfants déjà parcourus (boucle) sont retirés : l'arbre exposé est toujours acyclique
            $node->setRelation('children', $node->children->reject(fn ($c) => isset($visited[$c->id]))->values());
            foreach ($node->children as $child) {
                $total += $walk($child, $depth + 1);
            }
            $node->total_count = $total;

            return $total;
        };
        foreach ($this->roots as $root) {
            $walk($root, 0);
        }
        // Catégories prises dans une boucle (aucune n'est une racine) : affichées à la racine
        foreach ($this->nodes as $node) {
            if (!isset($visited[$node->id])) {
                $this->roots->push($node);
                $walk($node, 0);
            }
        }
    }

    public function roots(): Collection
    {
        return $this->roots;
    }

    public function get(?int $id): ?Category
    {
        return $id ? $this->nodes->get($id) : null;
    }

    public function isEmpty(): bool
    {
        return $this->nodes->isEmpty();
    }

    public function uncategorizedCount(): int
    {
        return $this->uncategorizedCount;
    }

    // Chemin depuis la racine jusqu'à la catégorie (incluse)
    public function path(?int $id): array
    {
        $path = [];
        $seen = [];
        $node = $this->get($id);
        // Arrêt sur une catégorie déjà rencontrée (boucle parent ↔ enfant)
        while ($node && !isset($seen[$node->id])) {
            $seen[$node->id] = true;
            array_unshift($path, $node);
            $node = $node->parent_id ? $this->nodes->get($node->parent_id) : null;
        }

        return $path;
    }

    // Libellé complet « Parent › Enfant »
    public function label(?int $id): ?string
    {
        $path = $this->path($id);

        return $path ? implode(' › ', array_map(fn ($c) => $c->name, $path)) : null;
    }

    // Catégorie et toutes ses descendantes
    public function descendantIds(int $id): array
    {
        $ids = [];
        $stack = [$this->get($id)];
        while (($node = array_pop($stack)) && count($ids) < 5000) {
            if (in_array($node->id, $ids, true)) {
                continue;
            }
            $ids[] = $node->id;
            foreach ($node->children as $child) {
                $stack[] = $child;
            }
        }

        return $ids ?: [$id];
    }

    // Liste à plat dans l'ordre de l'arbre (pour les listes déroulantes)
    public function flat(): Collection
    {
        $result = collect();
        $walk = function (Collection $nodes) use (&$walk, $result) {
            foreach ($nodes as $node) {
                if ($result->contains('id', $node->id)) {
                    continue;
                }
                $result->push($node);
                $walk($node->children);
            }
        };
        $walk($this->roots);

        return $result;
    }
}
