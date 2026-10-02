<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\PlatformActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Catalogue des services communs à toutes les entreprises (création, renommage, suppression).
 * Membres, responsable et droits restent réglés par l'administrateur de chaque entreprise.
 */
class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::orderBy('name')->get();

        // Utilisation par service, toutes entreprises confondues
        $members = DB::table('department_user')
            ->join('users', 'users.id', '=', 'department_user.user_id')
            ->groupBy('department_id')
            ->select('department_id', DB::raw('count(*) as members'), DB::raw('count(distinct users.organization_id) as organizations'))
            ->get()->keyBy('department_id');

        return view('super.departments.index', compact('departments', 'members'));
    }

    public function store(Request $request)
    {
        $department = Department::create($this->validated($request));
        PlatformActivityLog::record('department_created', "Service « {$department->name} » créé");

        return back()->with('success', "Service « {$department->name} » créé : il est disponible dans toutes les entreprises.");
    }

    public function update(Request $request, Department $department)
    {
        $old = $department->name;
        $department->update($this->validated($request, $department));
        PlatformActivityLog::record('department_updated', $old === $department->name
            ? "Service « {$department->name} » modifié"
            : "Service « {$old} » renommé en « {$department->name} »");

        return back()->with('success', "Service « {$department->name} » mis à jour.");
    }

    /**
     * Suppression. S'il a des membres, ils sont transférés (avec les droits et responsables)
     * vers un service de remplacement : aucun utilisateur ne doit se retrouver sans service.
     */
    public function destroy(Request $request, Department $department)
    {
        $hasMembers = DB::table('department_user')->where('department_id', $department->id)->exists();

        $data = $request->validate([
            'merge_into' => [
                $hasMembers ? 'required' : 'nullable',
                Rule::exists('departments', 'id'), Rule::notIn([$department->id]),
            ],
        ], [
            'merge_into.required' => "Ce service a des membres : choisissez le service qui les accueillera.",
        ]);

        $target = isset($data['merge_into']) ? Department::find($data['merge_into']) : null;

        DB::transaction(function () use ($department, $target) {
            if ($target) {
                foreach (DB::table('department_user')->where('department_id', $department->id)->pluck('user_id') as $userId) {
                    DB::table('department_user')->insertOrIgnore(['department_id' => $target->id, 'user_id' => $userId]);
                }
                // Droits : on garde le plus large des deux services
                foreach (DB::table('category_department')->where('department_id', $department->id)->get() as $rule) {
                    $existing = DB::table('category_department')
                        ->where('department_id', $target->id)->where('category_id', $rule->category_id)->value('access_level');
                    if (!$existing) {
                        DB::table('category_department')->insert(['department_id' => $target->id, 'category_id' => $rule->category_id, 'access_level' => $rule->access_level]);
                    } elseif ($existing === 'view' && $rule->access_level === 'edit') {
                        DB::table('category_department')->where('department_id', $target->id)->where('category_id', $rule->category_id)->update(['access_level' => 'edit']);
                    }
                }
                foreach (DB::table('department_organization')->where('department_id', $department->id)->whereNotNull('manager_id')->get() as $setting) {
                    $hasManager = DB::table('department_organization')
                        ->where('department_id', $target->id)->where('organization_id', $setting->organization_id)->whereNotNull('manager_id')->exists();
                    if (!$hasManager) {
                        $target->setManager($setting->organization_id, $setting->manager_id);
                    }
                }
            }
            $department->delete();
        });

        PlatformActivityLog::record('department_deleted', "Service « {$department->name} » supprimé" . ($target ? ", membres transférés vers « {$target->name} »" : ''));

        return back()->with('success', "Service « {$department->name} » supprimé" . ($target ? ", ses membres ont rejoint « {$target->name} »." : '.'));
    }

    private function validated(Request $request, ?Department $department = null): array
    {
        return $request->validate([
            'name'        => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($department?->id)],
            'description' => 'nullable|string|max:2000',
        ], [
            'name.unique' => 'Ce service existe déjà.',
        ]);
    }
}
