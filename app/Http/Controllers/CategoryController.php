<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{

// Afficher la liste des catégories
    public function index()
    {
        $categories = Category::withCount('documents')->orderBy('name')->get();
        return view('categories.index', compact('categories'));
    }


    // Afficher une catégorie spécifique
    public function show(Category $category)
    {
        return view('categories.show', compact('category'));
    }


    // Afficher le formulaire de création et stocker une nouvelle catégorie
    public function create()
    {
        return view('categories.create');
    }


    // Stocker une nouvelle catégorie
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'slug' => 'required|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
        ]);

        Category::create($data);

        return redirect()->route('categories.index')->with('success', 'Category created');
    }


    // Afficher le formulaire d'édition et mettre à jour une catégorie existante
    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }


    // Mettre à jour une catégorie existante
    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'slug' => 'required|string|max:255|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string',
        ]);

        $category->update($data);

        return redirect()->route('categories.index')->with('success', 'Category updated');
    }


    // Supprimer une catégorie existante
    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Category deleted');
    }
}
