<?php

namespace App\Http\Controllers;

use App\Support\Tenant;
use Illuminate\Validation\Rule;
use App\Models\Category;
use App\Models\Department;
use App\Models\Document;
use Illuminate\Http\Request;

class CategoryController extends Controller
{

// Afficher la liste des catégories
    public function index()
    {
        $categories = Category::withCount(['documents' => fn ($q) => $q->visibleTo()])
            ->with('parent', 'children', 'departments')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->paginate(12);

        $allCategories = Category::orderBy('name')->get();

        return view('categories.index', compact('categories', 'allCategories'));
    }


    public function show(Category $category)
    {
        // Sous-catégories et compteurs limités aux documents visibles par l'utilisateur
        $tree = new \App\Support\CategoryTree();
        $node = $tree->get($category->id) ?? $category;
        $path = $tree->path($category->id);
        $category->setRelation('parent', count($path) > 1 ? $path[count($path) - 2] : null);
        $category->setRelation('children', $node->children ?? collect());

        // Derniers documents visibles par l'utilisateur (propres, partagés, ouverts à son service), sous-dossiers compris
        $recentDocuments = Document::visibleTo()->whereIn('category_id', $tree->descendantIds($category->id))
            ->with('creator', 'category')->latest()->limit(8)->get();
        $info = new \App\Support\FolderInfo($path ?: [$node]);
        $category->loadMissing('creator');

        return view('categories.show', compact('category', 'recentDocuments', 'path', 'node', 'tree', 'info'));
    }


    // Afficher le formulaire de création et stocker une nouvelle catégorie
    public function create()
    {
        $allCategories = Category::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        return view('categories.create', compact('allCategories', 'departments'));
    }


    // Stocker une nouvelle catégorie
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->where('organization_id', Tenant::id())->where('parent_id', $request->input('parent_id') ?: null)],
            'slug'        => ['required', 'string', 'max:255', Rule::unique('categories', 'slug')->where('organization_id', Tenant::id())],
            'description' => 'nullable|string',
            'parent_id'   => ['nullable', Tenant::exists('categories')],
            'default_retention_years' => 'nullable|integer|min:0|max:100',
            'retention_trigger'       => ['sometimes', Rule::in(array_keys(Category::RETENTION_TRIGGERS))],
            'final_disposition'       => ['sometimes', Rule::in(array_keys(Category::FINAL_DISPOSITIONS))],
            'requires_approval'       => 'sometimes|nullable|boolean',
            'requires_signature'      => 'sometimes|nullable|boolean',
        ], ['name.unique' => 'Une catégorie porte déjà ce nom à cet endroit.']);

        $category = Category::create($data + ['created_by' => auth()->id()]);
        $this->syncAccess($category, $request);

        return redirect()->route('categories.index')->with('success', 'Catégorie créée avec succès.');
    }


    // Afficher le formulaire d'édition et mettre à jour une catégorie existante
    public function edit(Category $category)
    {
        $allCategories = Category::where('id', '!=', $category->id)
            ->whereNotIn('id', $category->allChildren()->pluck('id'))
            ->orderBy('name')
            ->get();
        $departments = Department::orderBy('name')->get();
        $category->load('departments');
        return view('categories.edit', compact('category', 'allCategories', 'departments'));
    }


    // Mettre à jour une catégorie existante
    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->where('organization_id', Tenant::id())->where('parent_id', $request->input('parent_id') ?: null)->ignore($category->id)],
            'slug'        => ['required', 'string', 'max:255', Rule::unique('categories', 'slug')->where('organization_id', Tenant::id())->ignore($category->id)],
            'description' => 'nullable|string',
            'parent_id'   => ['nullable', Tenant::exists('categories'),
                // Une catégorie ne peut pas être rangée dans elle-même ni dans une de ses sous-catégories
                Rule::notIn((new \App\Support\CategoryTree(withCounts: false))->descendantIds($category->id))],
            'default_retention_years' => 'nullable|integer|min:0|max:100',
            'retention_trigger'       => ['sometimes', Rule::in(array_keys(Category::RETENTION_TRIGGERS))],
            'final_disposition'       => ['sometimes', Rule::in(array_keys(Category::FINAL_DISPOSITIONS))],
            'requires_approval'       => 'sometimes|nullable|boolean',
            'requires_signature'      => 'sometimes|nullable|boolean',
        ], ['parent_id.not_in' => 'Une catégorie ne peut pas être placée dans elle-même ou dans une de ses sous-catégories.']);

        $category->update($data);
        $this->syncAccess($category, $request);

        // Circuit imposé modifié : tracé (il conditionne l'approbation, la signature et l'archivage des documents)
        if ($category->wasChanged(['requires_approval', 'requires_signature'])) {
            $label = fn ($v) => $v === null ? 'hérité' : ($v ? 'obligatoire' : 'facultatif');
            \Illuminate\Support\Facades\Log::info('Circuit de catégorie modifié', [
                'category_id' => $category->id, 'user_id' => auth()->id(),
                'approval' => $label($category->requires_approval), 'signature' => $label($category->requires_signature),
            ]);
        }

        // Règle de conservation modifiée : fin de conservation recalculée pour la catégorie et ses sous-catégories
        if ($category->wasChanged(['default_retention_years', 'retention_trigger', 'final_disposition', 'parent_id'])
            || $request->boolean('apply_retention_to_existing')) {
            if ($request->boolean('apply_retention_to_existing')) {
                $ids = collect([$category->id])->merge($category->allChildren()->pluck('id'));
                $years = $category->retentionRule()['years'];
                if ($years) {
                    Document::withTrashed()->whereIn('category_id', $ids)->where('retention_permanent', false)
                        ->update(['retention_years' => $years]);
                }
            }
            app(\App\Services\RetentionService::class)->recomputeCategory($category);
        }

        return redirect()->route('categories.index')->with('success', 'Catégorie mise à jour.');
    }


    // Accès par service et visibilité publique.
    // Uniquement depuis les formulaires qui affichent cette section (champ manage_access) :
    // la modale rapide de la liste ne doit pas effacer les droits existants.
    private function syncAccess(Category $category, Request $request): void
    {
        if (!$request->boolean('manage_access')) {
            return;
        }

        $request->validate([
            'access'   => 'nullable|array',
            'access.*' => 'nullable|in:view,edit',
        ]);

        $category->update(['is_public' => $request->boolean('is_public')]);

        $validDepartments = Department::pluck('id')->flip();
        $access = collect($request->input('access', []))
            ->filter(fn ($level, $departmentId) => $level && isset($validDepartments[(int) $departmentId]))
            ->mapWithKeys(fn ($level, $departmentId) => [(int) $departmentId => ['access_level' => $level]]);
        $category->departments()->sync($access);
    }

    // Supprimer une catégorie existante
    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Catégorie supprimée.');
    }
}
