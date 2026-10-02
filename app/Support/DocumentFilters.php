<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Filtres de la liste des documents (paramètres d'URL).
 *
 * Les valeurs invalides sont ignorées (pas d'erreur sur une page de consultation) :
 * une vue enregistrée reste utilisable même si une catégorie a été supprimée entre-temps.
 */
class DocumentFilters
{
    public const SCOPES = [
        'all'        => ['label' => 'Tous',               'icon' => 'fa-layer-group'],
        'mine'       => ['label' => 'Mes documents',      'icon' => 'fa-user'],
        'shared'     => ['label' => 'Partagés avec moi',  'icon' => 'fa-share-nodes'],
        'department' => ['label' => 'Mes services',       'icon' => 'fa-sitemap'],
        'favorites'  => ['label' => 'Favoris',            'icon' => 'fa-star'],
    ];

    public const STATUSES = ['draft' => 'Brouillon', 'review' => 'En approbation', 'approved' => 'Approuvé', 'signing' => 'En signature', 'signed' => 'Signé', 'archived' => 'Archivé'];

    public const EXPIRY = ['soon' => 'Expire sous 30 jours', 'expired' => 'Expiré', 'none' => 'Sans échéance'];

    public const SORTS = [
        'relevance' => 'Pertinence',
        'recent'    => 'Plus récents',
        'oldest'    => 'Plus anciens',
        'updated'   => 'Modifiés récemment',
        'title'     => 'Titre (A→Z)',
        'version'   => 'Par version',
    ];

    public const PER_PAGE = [15, 30, 50];

    // Paramètres qui constituent une vue enregistrable (le reste : pagination, tri par défaut…)
    public const FILTER_KEYS = ['q', 'scope', 'category', 'subcats', 'status', 'creator', 'type', 'tags', 'confidential', 'date_from', 'date_to', 'expiry'];

    public array $values = [];
    public array $terms = [];

    // Arborescence déjà chargée par l'appelant (évite une requête), sinon chargée à la demande
    public ?CategoryTree $tree = null;

    public function __construct(array $input, private User $user)
    {
        $this->values = $this->sanitize($input);
        $this->terms = DocumentSearch::terms($this->values['q'] ?? null);
    }

    public static function fromRequest(Request $request, User $user): self
    {
        return new self($request->query(), $user);
    }

    private function sanitize(array $input): array
    {
        $v = [];
        $string = fn ($key, $max = 200) => is_string($input[$key] ?? null) && trim($input[$key]) !== '' ? mb_substr(trim($input[$key]), 0, $max) : null;
        $date = function ($key) use ($input) {
            try {
                return is_string($input[$key] ?? null) && $input[$key] !== '' ? Carbon::createFromFormat('Y-m-d', $input[$key])->format('Y-m-d') : null;
            } catch (\Throwable) {
                return null;
            }
        };

        $v['q'] = $string('q', 500);
        $v['scope'] = isset(self::SCOPES[$input['scope'] ?? '']) ? $input['scope'] : null;
        if ($v['scope'] === 'all') {
            $v['scope'] = null;
        }
        $v['category'] = ($input['category'] ?? null) === 'none'
            ? 'none'
            : (ctype_digit((string) ($input['category'] ?? '')) ? (int) $input['category'] : null);
        $v['subcats'] = ($input['subcats'] ?? '1') !== '0';
        $v['status'] = isset(self::STATUSES[$input['status'] ?? '']) ? $input['status'] : null;
        $v['creator'] = ($input['creator'] ?? null) === 'me' ? 'me' : (ctype_digit((string) ($input['creator'] ?? '')) ? (int) $input['creator'] : null);
        $v['type'] = isset(FileType::FAMILIES[$input['type'] ?? '']) ? $input['type'] : null;
        $v['tags'] = $string('tags', 300);
        $v['confidential'] = in_array($input['confidential'] ?? null, ['0', '1'], true) ? $input['confidential'] : null;
        $v['date_from'] = $date('date_from');
        $v['date_to'] = $date('date_to');
        $v['expiry'] = isset(self::EXPIRY[$input['expiry'] ?? '']) ? $input['expiry'] : null;

        $sort = $input['sort'] ?? null;
        $v['sort'] = isset(self::SORTS[$sort]) && ($sort !== 'relevance' || DocumentSearch::terms($v['q'])) ? $sort : null;
        $v['per_page'] = in_array((int) ($input['per_page'] ?? 0), self::PER_PAGE, true) ? (int) $input['per_page'] : self::PER_PAGE[0];

        return $v;
    }

    public function get(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }

    // Tri effectif : pertinence par défaut quand on cherche, sinon les plus récents
    public function sort(): string
    {
        return $this->values['sort'] ?? ($this->terms ? 'relevance' : 'recent');
    }

    public function scope(): string
    {
        return $this->values['scope'] ?? 'all';
    }

    public function apply(Builder $query): Builder
    {
        $v = $this->values;
        $user = $this->user;

        if ($this->terms) {
            $search = app(DocumentSearch::class);
            $search->selectListColumns($query);
            $search->apply($query, $v['q']);
        }

        match ($v['scope']) {
            'mine'       => $query->where('documents.creator_id', $user->id),
            'shared'     => $query->whereHas('shares', fn ($s) => $s->where('shared_with', $user->id)->where('is_active', true)
                                ->where(fn ($d) => $d->whereNull('expires_at')->orWhere('expires_at', '>', now()))),
            'department' => $query->whereIn('documents.category_id', $user->viewableCategoryIds() ?: [0])->where('documents.is_confidential', false),
            'favorites'  => $query->whereHas('favoritedBy', fn ($f) => $f->where('users.id', $user->id)),
            default      => null,
        };

        if ($v['category'] === 'none') {
            $query->whereNull('documents.category_id');
        } elseif ($v['category']) {
            $query->whereIn('documents.category_id', $v['subcats'] ? $this->tree()->descendantIds($v['category']) : [$v['category']]);
        }
        if ($v['status']) {
            $query->where('documents.status', $v['status']);
        }
        if ($v['creator']) {
            $query->where('documents.creator_id', $v['creator'] === 'me' ? $user->id : $v['creator']);
        }
        if ($v['type']) {
            $extensions = array_keys(array_filter(FileType::TYPES, fn ($t) => $t[0] === $v['type']));
            $query->where(function ($w) use ($extensions) {
                foreach ($extensions as $extension) {
                    $w->orWhereRaw('LOWER(documents.file_path) LIKE ?', ['%.' . $extension]);
                }
            });
        }
        foreach ($this->tags() as $tag) {
            $query->whereRaw('documents.tags ' . Document::likeOperator() . " ? ESCAPE '!'", ['%' . DocumentSearch::escapeLike($tag) . '%']);
        }
        if ($v['confidential'] !== null) {
            $query->where('documents.is_confidential', $v['confidential'] === '1');
        }
        if ($v['date_from']) {
            $query->whereDate('documents.created_at', '>=', $v['date_from']);
        }
        if ($v['date_to']) {
            $query->whereDate('documents.created_at', '<=', $v['date_to']);
        }
        match ($v['expiry']) {
            'soon'    => $query->whereBetween('documents.expires_at', [now(), now()->addDays(30)]),
            'expired' => $query->where('documents.expires_at', '<', now()),
            'none'    => $query->whereNull('documents.expires_at'),
            default   => null,
        };

        match ($this->sort()) {
            'relevance' => $query->orderByDesc('search_score')->orderByDesc('documents.created_at'),
            'oldest'    => $query->orderBy('documents.created_at'),
            'updated'   => $query->orderByDesc('documents.updated_at'),
            'title'     => $query->orderBy('documents.title'),
            'version'   => $query->orderByDesc('documents.version'),
            default     => $query->orderByDesc('documents.created_at'),
        };

        return $query;
    }

    public function tags(): array
    {
        return array_values(array_unique(array_filter(array_map('trim', explode(',', $this->values['tags'] ?? '')))));
    }

    public function tree(): CategoryTree
    {
        return $this->tree ??= new CategoryTree(withCounts: false);
    }

    // Paramètres de la vue (pour l'enregistrer ou construire des liens)
    public function filterParams(): array
    {
        $params = [];
        foreach (self::FILTER_KEYS as $key) {
            $value = $this->values[$key] ?? null;
            if ($key === 'subcats') {
                if (!$value && is_int($this->values['category'])) {
                    $params['subcats'] = '0';
                }
                continue;
            }
            if ($value !== null && $value !== '') {
                $params[$key] = (string) $value;
            }
        }
        if ($this->values['sort'] ?? null) {
            $params['sort'] = $this->values['sort'];
        }

        return $params;
    }

    // URL de la liste avec certains paramètres retirés / remplacés
    public function url(array $overrides = []): string
    {
        $params = array_merge($this->filterParams(), ['per_page' => $this->values['per_page'] !== self::PER_PAGE[0] ? $this->values['per_page'] : null], $overrides);

        return route('documents.index', array_filter($params, fn ($v) => $v !== null && $v !== ''));
    }

    /**
     * Filtres actifs sous forme d'étiquettes : [['label' => …, 'remove' => url], …]
     * (la recherche texte et l'onglet ont leur propre affichage).
     */
    public function chips(array $creators): array
    {
        $v = $this->values;
        $chips = [];
        $add = function (string $label, array $remove) use (&$chips) {
            $chips[] = ['label' => $label, 'remove' => $this->url($remove)];
        };

        // Le dossier ouvert est le contexte de navigation (titre de la page) : il n'apparaît comme filtre
        // retirable que si d'autres critères sont appliqués (« chercher partout » en le retirant)
        $others = array_diff_key($this->filterParams(), array_flip(['category', 'subcats', 'sort']));
        if ($v['category'] === 'none') {
            $add('Sans dossier', ['category' => null, 'subcats' => null]);
        } elseif ($v['category'] && $others && ($label = $this->tree()->label($v['category']))) {
            $add('Dossier : ' . $label . ($v['subcats'] ? '' : ' (sans sous-dossiers)'), ['category' => null, 'subcats' => null]);
        }
        if ($v['status']) {
            $add('Statut : ' . self::STATUSES[$v['status']], ['status' => null]);
        }
        if ($v['creator']) {
            $add('Auteur : ' . ($v['creator'] === 'me' ? 'moi' : ($creators[$v['creator']] ?? 'inconnu')), ['creator' => null]);
        }
        if ($v['type']) {
            $add('Type : ' . FileType::FAMILIES[$v['type']]['label'], ['type' => null]);
        }
        foreach ($this->tags() as $tag) {
            $remaining = implode(', ', array_diff($this->tags(), [$tag]));
            $add('Mot-clé : ' . $tag, ['tags' => $remaining ?: null]);
        }
        if ($v['confidential'] !== null) {
            $add($v['confidential'] === '1' ? 'Confidentiels' : 'Non confidentiels', ['confidential' => null]);
        }
        if ($v['date_from'] || $v['date_to']) {
            $format = fn ($d) => Carbon::parse($d)->format('d/m/Y');
            $label = match (true) {
                $v['date_from'] && $v['date_to'] => 'Du ' . $format($v['date_from']) . ' au ' . $format($v['date_to']),
                (bool) $v['date_from']           => 'Depuis le ' . $format($v['date_from']),
                default                          => 'Jusqu\'au ' . $format($v['date_to']),
            };
            $add($label, ['date_from' => null, 'date_to' => null]);
        }
        if ($v['expiry']) {
            $add(self::EXPIRY[$v['expiry']], ['expiry' => null]);
        }

        return $chips;
    }

    // Nombre de filtres du panneau actuellement appliqués (badge du bouton « Filtres »)
    public function panelCount(): int
    {
        $v = $this->values;

        // Le dossier n'est pas compté : il se choisit dans l'arborescence
        return count(array_filter([
            $v['category'] === 'none', !$v['subcats'] && is_int($v['category']), $v['status'], $v['creator'], $v['type'], $v['tags'],
            $v['confidential'] !== null, $v['date_from'] || $v['date_to'], $v['expiry'],
        ]));
    }

    public function isFiltered(): bool
    {
        return $this->filterParams() !== [];
    }
}
