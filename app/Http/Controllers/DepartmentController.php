<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Http\Request;

/**
 * Services côté entreprise. La liste des services est commune et gérée par le super admin ;
 * l'administrateur de l'entreprise règle, pour SON entreprise : le responsable, les membres
 * et les droits sur ses catégories.
 */
class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with(['users', 'categories'])
            ->withCount('users')
            ->orderBy('name')
            ->get();

        $managers = User::whereIn('id', \DB::table('department_organization')
            ->where('organization_id', Tenant::id())->whereNotNull('manager_id')->pluck('manager_id'))
            ->get()->keyBy('id');
        $managerIds = \DB::table('department_organization')
            ->where('organization_id', Tenant::id())->pluck('manager_id', 'department_id');

        $usersWithoutDepartment = User::whereDoesntHave('departments')->count();

        return view('departments.index', compact('departments', 'managers', 'managerIds', 'usersWithoutDepartment'));
    }

    public function edit(Department $department)
    {
        $department->load('users', 'categories');
        $categories = Category::tree();
        $users = User::with('roles', 'departments')->orderBy('full_name')->get();

        return view('departments.form', [
            'department' => $department,
            'managerId'  => $department->managerFor(Tenant::id())?->id,
            'memberIds'  => $department->users->pluck('id')->all(),
            'access'     => $department->categories->pluck('pivot.access_level', 'id')->all(),
            'suggested'  => $department->suggestedCategoryIds($categories),
            'users'      => $users->map(fn (User $user) => [
                'id'       => $user->id,
                'name'     => $user->full_name,
                'email'    => $user->email,
                'initials' => initials($user->full_name),
                // Un lecteur garde un accès en consultation seulement
                'viewer'   => !$user->hasAnyRole(['admin', 'editor']),
                'inactive' => !$user->is_active,
                'services' => $user->departments->where('id', '!=', $department->id)->pluck('name')->values(),
            ])->values(),
            'categories' => $categories->map(fn (Category $category) => [
                'id'       => $category->id,
                'name'     => $category->name,
                'parentId' => $category->parent_id,
                'depth'    => $category->depth,
                'public'   => (bool) $category->is_public,
            ])->values(),
        ]);
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'manager_id' => ['nullable', Tenant::exists('users')],
            'members'    => 'nullable|array',
            'members.*'  => Tenant::exists('users'),
            'access'     => 'nullable|array',
            'access.*'   => 'nullable|in:view,edit',
        ]);

        // Le responsable fait toujours partie du service
        $members = collect($data['members'] ?? [])->when($data['manager_id'] ?? null, fn ($c, $id) => $c->push($id));

        $department->setManager(Tenant::id(), $data['manager_id'] ?? null);
        $department->syncMembers(Tenant::id(), $members->all());
        $department->syncCategoryAccess(Tenant::id(), array_filter($data['access'] ?? []));

        return redirect()->route('departments.index')->with('success', "Service « {$department->name} » mis à jour.");
    }
}
