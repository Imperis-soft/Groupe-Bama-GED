<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;

class UserController extends Controller
{

    // Afficher la liste des utilisateurs
    public function index()
    {
        $users = User::orderBy('full_name')->paginate(20);
        return view('users.index', compact('users'));
    }

    // Afficher le formulaire de création d'un utilisateur

    public function create()
    {
        $auth = auth()->user();
        $allowed = ['admin@bama.com', 'contact@imperis.com'];
        if (! $auth || ! in_array($auth->email, $allowed)) {
            abort(403);
        }

        return view('users.create');
    }

    // Stocker un nouvel utilisateur

    public function store(Request $request)
    {
        $auth = auth()->user();
        $allowed = ['admin@bama.com', 'contact@imperis.com'];
        if (! $auth || ! in_array($auth->email, $allowed)) {
            abort(403);
        }

        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        $user = User::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        return redirect()->route('users.index')->with('success', 'Utilisateur créé: ' . $user->email);
    }

    // Afficher le formulaire d'édition d'un utilisateur
    public function edit(User $user)
    {
        $auth = auth()->user();
        $allowed = ['admin@bama.com', 'contact@imperis.com'];
        if (! $auth || ! in_array($auth->email, $allowed)) {
            abort(403);
        }

        return view('users.edit', compact('user'));
    }

    // Mettre à jour un utilisateur existant
    public function update(Request $request, User $user)
    {
        $auth = auth()->user();
        $allowed = ['admin@bama.com', 'contact@imperis.com'];
        if (! $auth || ! in_array($auth->email, $allowed)) {
            abort(403);
        }

        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Utilisateur mis à jour: ' . $user->email);
    }

    // Supprimer un utilisateur
    public function destroy(User $user)
    {
        $auth = auth()->user();
        $allowed = ['admin@bama.com', 'contact@imperis.com'];
        if (! $auth || ! in_array($auth->email, $allowed)) {
            abort(403);
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Utilisateur supprimé: ' . $user->email);
    }

    // Edit user's roles
    public function editRoles(User $user)
    {
        $roles = Role::orderBy('name')->get();
        return view('users.roles', compact('user', 'roles'));
    }

    // Update user's roles
    public function updateRoles(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id'
        ]);

        $roles = $request->input('roles', []);
        $user->roles()->sync($roles);

        return redirect()->route('users.index')->with('success', 'Rôles mis à jour pour ' . $user->email);
    }
}
