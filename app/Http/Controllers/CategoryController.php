<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{

// Afficher la liste des catégories
    public function index()
    {
        $categories = Category::withCount('documents')
            ->with('parent', 'children')
            ->whereNull('parent_id')
            ->orderBy('name')
            ->paginate(12);

        $allCategories = Category::orderBy('name')->get();

        return view('categories.index', compact('categories', 'allCategories'));
    }


    public function show(Category $category)
    {
        $category->load(['parent', 'children' => function($q) {
            $q->withCount('documents');
        }]);
        $documents = $category->documents()->with('creator')->latest()->paginate(12);
        return view('categories.show', compact('category', 'documents'));
    }


    // Afficher le formulaire de création et stocker une nouvelle catégorie
    public function create()
    {
        $allCategories = Category::orderBy('name')->get();
        return view('categories.create', compact('allCategories'));
    }


    // Stocker une nouvelle catégorie
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255|unique:categories,name',
            'slug'        => 'required|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'parent_id'   => 'nullable|exists:categories,id',
        ]);

        Category::create($data);

        return redirect()->route('categories.index')->with('success', 'Catégorie créée avec succès.');
    }


    // Afficher le formulaire d'édition et mettre à jour une catégorie existante
    public function edit(Category $category)
    {
        $allCategories = Category::where('id', '!=', $category->id)
            ->whereNotIn('id', $category->allChildren()->pluck('id'))
            ->orderBy('name')
            ->get();
        return view('categories.edit', compact('category', 'allCategories'));
    }


    // Mettre à jour une catégorie existante
    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255|unique:categories,name,' . $category->id,
            'slug'        => 'required|string|max:255|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'parent_id'   => 'nullable|exists:categories,id',
        ]);

        $category->update($data);

        return redirect()->route('categories.index')->with('success', 'Catégorie mise à jour.');
    }


    // Supprimer une catégorie existante
    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Catégorie supprimée.');
    }
}
