<?php

namespace App\Http\Controllers;

use App\Models\ApprovalTemplate;
use App\Models\Department;
use App\Models\User;
use App\Support\CategoryTree;
use App\Support\Tenant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// Modèles de circuit d'approbation (administrateurs)
class ApprovalTemplateController extends Controller
{
    public function index()
    {
        $templates = ApprovalTemplate::with('category')->orderBy('name')->get();

        return view('approval-templates.index', [
            'templates'   => $templates,
            'users'       => User::pluck('full_name', 'id'),
            'departments' => Department::pluck('name', 'id'),
        ]);
    }

    public function create()
    {
        return view('approval-templates.form', $this->formData(new ApprovalTemplate(['steps' => [['type' => 'creator_manager', 'due_days' => 3]]])));
    }

    public function store(Request $request)
    {
        $template = ApprovalTemplate::create($this->validated($request) + ['created_by' => auth()->id()]);

        return redirect()->route('approval-templates.index')->with('success', "Circuit « {$template->name} » créé.");
    }

    public function edit(ApprovalTemplate $approvalTemplate)
    {
        return view('approval-templates.form', $this->formData($approvalTemplate));
    }

    public function update(Request $request, ApprovalTemplate $approvalTemplate)
    {
        $approvalTemplate->update($this->validated($request, $approvalTemplate));

        return redirect()->route('approval-templates.index')->with('success', "Circuit « {$approvalTemplate->name} » mis à jour.");
    }

    public function destroy(ApprovalTemplate $approvalTemplate)
    {
        $approvalTemplate->delete();

        return redirect()->route('approval-templates.index')->with('success', "Circuit « {$approvalTemplate->name} » supprimé.");
    }

    private function formData(ApprovalTemplate $template): array
    {
        $departments = Department::orderBy('name')->get();

        return [
            'template'    => $template,
            'users'       => User::where('is_active', true)->orderBy('full_name')->get(['id', 'full_name']),
            'departments' => $departments->map(fn ($d) => [
                'id'      => $d->id,
                'name'    => $d->name,
                'manager' => $d->managerFor(Tenant::id())?->full_name,
            ])->values(),
            'categories'  => (new CategoryTree(withCounts: false))->flat(),
        ];
    }

    private function validated(Request $request, ?ApprovalTemplate $template = null): array
    {
        $data = $request->validate([
            'name'                 => ['required', 'string', 'max:80', Rule::unique('approval_templates', 'name')->where('organization_id', Tenant::id())->ignore($template?->id)],
            'description'          => 'nullable|string|max:500',
            'category_id'          => ['nullable', Tenant::exists('categories')],
            'steps'                => 'required|array|min:1|max:' . ApprovalTemplate::MAX_STEPS,
            'steps.*.type'         => ['required', Rule::in(array_keys(ApprovalTemplate::STEP_TYPES))],
            'steps.*.user_id'      => ['nullable', 'required_if:steps.*.type,user', Tenant::exists('users')],
            'steps.*.department_id'=> ['nullable', 'required_if:steps.*.type,department_manager', 'exists:departments,id'],
            'steps.*.due_days'     => 'nullable|integer|min:1|max:365',
        ], [
            'name.unique'                      => 'Un circuit porte déjà ce nom.',
            'steps.required'                   => 'Ajoutez au moins une étape.',
            'steps.*.user_id.required_if'      => 'Choisissez la personne de chaque étape « personne précise ».',
            'steps.*.department_id.required_if'=> 'Choisissez le service de chaque étape « responsable d\'un service ».',
        ]);

        // Seuls les champs utiles à chaque type d'étape sont conservés
        $data['steps'] = collect($data['steps'])->values()->map(fn ($step) => array_filter([
            'type'          => $step['type'],
            'user_id'       => $step['type'] === 'user' ? (int) $step['user_id'] : null,
            'department_id' => $step['type'] === 'department_manager' ? (int) $step['department_id'] : null,
            'due_days'      => isset($step['due_days']) && $step['due_days'] !== '' ? (int) $step['due_days'] : null,
        ], fn ($v) => $v !== null))->all();

        return $data;
    }
}
