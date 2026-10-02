<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Document;
use App\Services\DocumentSearch;
use App\Support\Tenant;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public const SORTS = ['relevance', 'recent', 'oldest', 'title', 'updated'];

    public function __construct(private DocumentSearch $search) {}

    // Recherche rapide (palette Ctrl+K) : quelques documents et catégories
    public function quick(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:200']);
        $q = trim((string) $request->input('q'));
        $terms = DocumentSearch::terms($q);

        if (!$terms) {
            return response()->json(['documents' => [], 'categories' => [], 'total' => 0]);
        }

        $query = Document::visibleTo()->with('category:id,name');
        $this->search->selectListColumns($query);
        $this->search->apply($query, $q);

        $total = (clone $query)->toBase()->getCountForPagination();
        $documents = $query->orderByDesc('search_score')->latest('documents.created_at')->limit(8)->get();

        $categories = Category::query()
            ->where(function ($w) use ($terms) {
                foreach ($terms as $term) {
                    $w->whereRaw('name ' . Document::likeOperator() . " ? ESCAPE '!'", ['%' . DocumentSearch::escapeLike($term) . '%']);
                }
            })
            ->orderBy('name')->limit(4)->get(['id', 'name']);

        return response()->json([
            'documents'  => $documents->map(fn ($d) => $this->present($d, $terms))->values(),
            'categories' => $categories->map(fn ($c) => [
                'id'    => $c->id,
                'name'  => DocumentSearch::highlight($c->name, $terms),
                'url'   => route('categories.show', $c),
            ])->values(),
            'total'      => $total,
            'more_url'   => route('documents.advanced-search', ['q' => $q]),
        ]);
    }

    // Recherche avancée : filtres, tri et pagination
    public function documents(Request $request)
    {
        $data = $request->validate([
            'q'            => 'nullable|string|max:500',
            'category'     => ['nullable', Tenant::exists('categories')],
            'status'       => 'nullable|in:draft,review,approved,signing,signed,archived',
            'creator'      => ['nullable', Tenant::exists('users')],
            'confidential' => 'nullable|in:0,1',
            'date_from'    => 'nullable|date',
            'date_to'      => 'nullable|date',
            'tags'         => 'nullable|string|max:500',
            'sort'         => 'nullable|in:' . implode(',', self::SORTS),
            'page'         => 'nullable|integer|min:1',
        ]);

        $q = trim((string) ($data['q'] ?? ''));
        $terms = DocumentSearch::terms($q);

        $query = Document::visibleTo()->with(['category:id,name', 'creator:id,full_name']);
        $this->search->selectListColumns($query);
        $this->search->apply($query, $q);

        $query->when($data['category'] ?? null, fn ($w, $v) => $w->where('documents.category_id', $v))
              ->when($data['status'] ?? null, fn ($w, $v) => $w->where('documents.status', $v))
              ->when($data['creator'] ?? null, fn ($w, $v) => $w->where('documents.creator_id', $v))
              ->when(isset($data['confidential']), fn ($w) => $w->where('documents.is_confidential', $data['confidential'] === '1'))
              ->when($data['date_from'] ?? null, fn ($w, $v) => $w->whereDate('documents.created_at', '>=', $v))
              ->when($data['date_to'] ?? null, fn ($w, $v) => $w->whereDate('documents.created_at', '<=', $v));

        // Mots-clés : chaque tag doit figurer parmi ceux du document
        foreach (array_filter(array_map('trim', explode(',', $data['tags'] ?? ''))) as $tag) {
            $query->whereRaw('documents.tags ' . Document::likeOperator() . " ? ESCAPE '!'", ['%' . DocumentSearch::escapeLike($tag) . '%']);
        }

        $sort = $data['sort'] ?? ($terms ? 'relevance' : 'recent');
        match ($sort) {
            'relevance' => $terms ? $query->orderByDesc('search_score')->latest('documents.created_at') : $query->latest('documents.created_at'),
            'oldest'    => $query->oldest('documents.created_at'),
            'title'     => $query->orderBy('documents.title'),
            'updated'   => $query->latest('documents.updated_at'),
            default     => $query->latest('documents.created_at'),
        };

        $page = $query->paginate(25);

        return response()->json([
            'data'         => collect($page->items())->map(fn ($d) => $this->present($d, $terms))->values(),
            'total'        => $page->total(),
            'current_page' => $page->currentPage(),
            'last_page'    => $page->lastPage(),
            'sort'         => $sort,
            'terms'        => $terms,
        ]);
    }

    // Résultat allégé : jamais le texte intégral du document
    private function present(Document $document, array $terms): array
    {
        return [
            'id'              => $document->id,
            'reference'       => $document->reference,
            'title'           => $document->title,
            'title_segments'  => DocumentSearch::highlight($document->title, $terms),
            'snippet'         => $terms ? DocumentSearch::snippet($document->search_excerpt_source, $terms) : null,
            'status'          => $document->status,
            'is_confidential' => (bool) $document->is_confidential,
            'version'         => $document->version,
            'file_path'       => $document->file_path,
            'tags'            => $document->tags,
            'category'        => $document->category ? ['id' => $document->category->id, 'name' => $document->category->name] : null,
            'creator'         => $document->relationLoaded('creator') && $document->creator ? ['id' => $document->creator->id, 'full_name' => $document->creator->full_name] : null,
            'created_at'      => $document->created_at?->toIso8601String(),
            'updated_at'      => $document->updated_at?->toIso8601String(),
            'url'             => route('documents.show', $document),
            'edit_url'        => route('documents.edit', $document),
            'can_edit'        => $document->canEdit(),
        ];
    }
}
