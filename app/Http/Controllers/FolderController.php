<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Document;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Dossiers créés depuis l'explorateur de documents.
 * Un dossier est une catégorie : il hérite des droits par service et de la règle de conservation
 * de son parent. Le paramétrage complet (droits, conservation) reste dans la gestion des catégories.
 */
class FolderController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validateWithBag('folder', [
            'name'      => 'required|string|max:120',
            'parent_id' => ['nullable', Tenant::exists('categories')],
        ]);
        $parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : null;

        if (!auth()->user()->canCreateFolderIn($parentId)) {
            abort(403, $parentId
                ? 'Vous n\'avez pas le droit de créer un dossier ici.'
                : 'Seul un administrateur peut créer un dossier à la racine.');
        }

        $name = $this->cleanName($data['name']);
        $this->ensureNameFree($name, $parentId);

        $folder = Category::create([
            'name'       => $name,
            'slug'       => Category::uniqueSlug($name),
            'parent_id'  => $parentId,
            'created_by' => auth()->id(),
        ]);
        auth()->user()->forgetCategoryAccess();

        return redirect()->route('documents.index', ['category' => $folder->id])
            ->with('success', "Dossier « {$folder->name} » créé.");
    }

    public function update(Request $request, Category $category)
    {
        abort_unless(auth()->user()->canManageFolder($category), 403, 'Vous ne pouvez pas renommer ce dossier.');

        $data = $request->validateWithBag('folder', ['name' => 'required|string|max:120']);
        $name = $this->cleanName($data['name']);
        $this->ensureNameFree($name, $category->parent_id, $category->id);

        $category->update(['name' => $name]);

        return redirect()->route('documents.index', ['category' => $category->id])
            ->with('success', 'Dossier renommé.');
    }

    public function destroy(Category $category)
    {
        abort_unless(auth()->user()->canManageFolder($category), 403, 'Vous ne pouvez pas supprimer ce dossier.');

        // Corbeille comprise : un document supprimé doit pouvoir être restauré dans son dossier
        if ($category->children()->exists() || Document::withTrashed()->where('category_id', $category->id)->exists()) {
            return back()->with('error', 'Seul un dossier vide (sans document ni sous-dossier, corbeille comprise) peut être supprimé.');
        }

        $parentId = $category->parent_id;
        $category->delete();

        return redirect()->route('documents.index', array_filter(['category' => $parentId]))
            ->with('success', 'Dossier supprimé.');
    }

    private function cleanName(string $name): string
    {
        return trim(preg_replace('/\s+/u', ' ', $name));
    }

    private function ensureNameFree(string $name, ?int $parentId, ?int $ignoreId = null): void
    {
        $taken = Category::where('parent_id', $parentId)
            ->where('name', $name)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages(['name' => 'Un dossier porte déjà ce nom à cet endroit.'])
                ->errorBag('folder');
        }
    }
}
