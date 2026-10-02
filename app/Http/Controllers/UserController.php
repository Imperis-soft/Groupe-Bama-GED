<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use App\Models\Role;
use App\Support\Tenant;

class UserController extends Controller
{

    // Afficher la liste des utilisateurs
    public function index()
    {
        $users = User::with('roles', 'departments')->orderBy('full_name')->paginate(20);
        return view('users.index', compact('users'));
    }

    // Afficher le formulaire de création d'un utilisateur
    public function create()
    {
        if ($error = $this->userLimitError()) {
            return redirect()->route('users.index')->with('error', $error);
        }
        $departments = Department::orderBy('name')->get();
        return view('users.create', compact('departments'));
    }

    // Stocker un nouvel utilisateur
    public function store(Request $request)
    {
        if ($error = $this->userLimitError()) {
            return redirect()->route('users.index')->with('error', $error);
        }

        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            // Chaque utilisateur est affecté à au moins un service dès sa création
            'departments' => 'required|array|min:1',
            'departments.*' => 'exists:departments,id',
        ], $this->departmentMessages());

        $user = User::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        // Rôle par défaut : lecteur (modifiable ensuite)
        if ($viewer = Role::where('name', 'viewer')->first()) {
            $user->roles()->attach($viewer->id);
        }
        $user->departments()->sync($data['departments'] ?? []);

        return redirect()->route('users.index')->with('success', 'Utilisateur créé: ' . $user->email);
    }

    private function departmentMessages(): array
    {
        return [
            'departments.required' => 'Affectez l\'utilisateur à au moins un service.',
            'departments.min'      => 'Affectez l\'utilisateur à au moins un service.',
        ];
    }

    // Message d'erreur si l'offre ne permet plus d'ajouter d'utilisateur, sinon null
    private function userLimitError(): ?string
    {
        $organization = Tenant::organization();
        if ($organization->canAddUsers()) {
            return null;
        }
        $max = $organization->currentPlan()?->max_users;
        return "Votre offre est limitée à {$max} utilisateurs. Contactez " . config('saas.vendor_name') . ' pour passer à une offre supérieure.';
    }

    // Afficher le formulaire d'édition d'un utilisateur
    public function edit(User $user)
    {
        $departments = Department::orderBy('name')->get();
        $user->load('departments');
        return view('users.edit', compact('user', 'departments'));
    }

    // Mettre à jour un utilisateur existant
    public function update(Request $request, User $user)
    {        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'departments' => [Rule::requiredIf($request->boolean('manage_departments')), 'array', 'min:1'],
            'departments.*' => 'exists:departments,id',
        ], $this->departmentMessages());

        $user->update(collect($data)->except('departments')->all());
        if ($request->boolean('manage_departments')) {
            $user->departments()->sync($data['departments'] ?? []);
        }

        return redirect()->route('users.index')->with('success', 'Utilisateur mis à jour: ' . $user->email);
    }

    // Supprimer un utilisateur
    public function destroy(User $user)
    {
        // Empêcher l'auto-suppression
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
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
