<?php

namespace App\Http\Controllers;

use App\Support\Tenant;
use App\Models\Document;
use App\Models\ApprovalStep;
use App\Models\User;
use App\Models\ApprovalTemplate;
use App\Services\ApprovalWorkflow;
use App\Services\DocumentArchivalService;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function index(Document $document)
    {
        if (!$document->canView()) {
            abort(403, 'Accès refusé à ce document.');
        }
        $steps = $document->approvalSteps()->with(['approver', 'delegatedFrom', 'forcedBy'])->get();
        // L'auteur ne peut pas approuver son propre document
        $users = User::where('is_active', true)->whereKeyNot($document->creator_id)->with('delegate')->orderBy('full_name')->get();

        // Modèles de circuit traduits pour ce document (approbateurs réels, étapes impossibles)
        $workflow = app(ApprovalWorkflow::class);
        $templates = ApprovalTemplate::orderBy('name')->get();
        $resolvedTemplates = $templates->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'description' => $t->description]
            + $workflow->resolveTemplate($t, $document))->values();
        $suggestedTemplateId = $workflow->suggestedTemplateId($document, $templates);
        $currentStep = $workflow->currentStep($document);

        return view('documents.approval', compact('document', 'steps', 'users', 'resolvedTemplates', 'suggestedTemplateId', 'currentStep'));
    }

    // Configurer le workflow d'approbation
    public function setup(Request $request, Document $document)
    {
        if (!$document->canManage()) {
            abort(403, 'Seul le créateur ou un administrateur peut configurer le workflow.');
        }
        $data = $request->validate([
            'approvers'       => 'required|array|min:1|max:' . ApprovalTemplate::MAX_STEPS,
            'approvers.*'     => ['distinct', Tenant::exists('users')],
            'step_due_days'   => 'nullable|array',
            'step_due_days.*' => 'nullable|integer|min:1|max:365',
            'due_days'        => 'nullable|integer|min:1|max:365', // délai par défaut de chaque étape
            'template_id'     => ['nullable', Tenant::exists('approval_templates')],
        ]);

        $steps = collect($data['approvers'])->values()->map(fn ($userId, $index) => [
            'user_id'  => (int) $userId,
            'due_days' => $data['step_due_days'][$index] ?? $data['due_days'] ?? null,
        ])->all();
        $template = isset($data['template_id']) ? ApprovalTemplate::find($data['template_id']) : null;

        if ($error = app(ApprovalWorkflow::class)->start($document, $steps, $template?->name)) {
            return back()->with('error', $error);
        }

        return back()->with('success', 'Circuit d\'approbation lancé. Le premier validateur a été notifié.');
    }

    // Relance manuelle de l'étape en cours (créateur ou administrateur, au plus une fois par heure)
    public function remind(Document $document, ApprovalStep $step)
    {
        if ($step->document_id !== $document->id) {
            abort(404);
        }
        if (!$document->canManage()) {
            abort(403);
        }
        $workflow = app(ApprovalWorkflow::class);
        if (!$step->isPending() || $workflow->currentStep($document)?->id !== $step->id) {
            return back()->with('error', 'Seule l\'étape en cours peut être relancée.');
        }
        if ($step->last_reminded_at && $step->last_reminded_at->gt(now()->subHour())) {
            return back()->with('error', 'Une relance a déjà été envoyée il y a moins d\'une heure.');
        }

        $workflow->remind($step);
        app(DocumentArchivalService::class)->logAction($document, 'approval_reminded', "Relance envoyée à {$step->approver->full_name}");

        return back()->with('success', "Relance envoyée à {$step->approver->full_name}.");
    }

    // Retirer le document du circuit en cours (approbation ou signature), motif obligatoire
    public function withdraw(Request $request, Document $document)
    {
        if (!$document->canManage()) {
            abort(403, 'Seul le créateur ou un administrateur peut retirer le document du circuit.');
        }
        if (!$document->isInWorkflow()) {
            return back()->with('error', 'Ce document n\'est dans aucun circuit en cours.');
        }
        $data = $request->validate(['reason' => 'required|string|min:5|max:1000'], [
            'reason.required' => 'Indiquez pourquoi le document est retiré du circuit.',
            'reason.min'      => 'Indiquez pourquoi le document est retiré du circuit.',
        ]);

        app(ApprovalWorkflow::class)->withdraw($document, auth()->user(), $data['reason']);

        return back()->with('success', 'Document retiré du circuit : il est de nouveau modifiable. Les personnes concernées ont été prévenues.');
    }

    /**
     * Vérifie qu'une étape peut être décidée par l'utilisateur courant.
     * Retourne [redirection d'erreur, motif de forçage] : un administrateur qui n'est pas l'approbateur doit motiver sa décision.
     */
    private function guardStep(Request $request, Document $document, ApprovalStep $step): array
    {
        if ($step->document_id !== $document->id) {
            abort(404);
        }
        $user = auth()->user();
        $isApprover = $step->approver_id === $user->id || $step->delegated_from_id === $user->id;
        if (!$isApprover && !$user->hasRole('admin')) {
            abort(403);
        }
        if (!$step->isPending() || $document->status !== 'review') {
            return [back()->with('error', 'Cette étape a déjà été traitée.'), null];
        }
        // Les étapes se valident dans l'ordre
        if (app(ApprovalWorkflow::class)->currentStep($document)?->id !== $step->id) {
            return [back()->with('error', 'Les étapes précédentes doivent être validées avant celle-ci.'), null];
        }
        if ($isApprover) {
            return [null, null];
        }

        // Forçage par un administrateur : jamais sur son propre document, toujours motivé
        if ($document->creator_id === $user->id) {
            return [back()->with('error', 'Vous ne pouvez pas décider à la place de l\'approbateur sur votre propre document.'), null];
        }
        $reason = trim((string) $request->input('force_reason'));
        if (mb_strlen($reason) < 5) {
            return [back()->with('error', 'Vous décidez à la place de ' . $step->approver?->full_name . ' : indiquez le motif (il sera inscrit au journal).'), null];
        }

        return [null, $reason];
    }

    // Approuver une étape
    public function approve(Request $request, Document $document, ApprovalStep $step)
    {
        [$redirect, $forceReason] = $this->guardStep($request, $document, $step);
        if ($redirect) {
            return $redirect;
        }

        $request->validate(['comment' => 'nullable|string|max:1000']);
        app(ApprovalWorkflow::class)->decide($step, auth()->user(), true, $request->input('comment'), $forceReason);

        return back()->with('success', $forceReason ? 'Étape approuvée par forçage administrateur (inscrit au journal).' : 'Étape approuvée.');
    }

    // Rejeter une étape
    public function reject(Request $request, Document $document, ApprovalStep $step)
    {
        [$redirect, $forceReason] = $this->guardStep($request, $document, $step);
        if ($redirect) {
            return $redirect;
        }

        $request->validate(['reason' => 'required|string|max:1000']);
        app(ApprovalWorkflow::class)->decide($step, auth()->user(), false, $request->input('reason'), $forceReason);

        return back()->with('success', 'Étape rejetée. Le créateur a été notifié.');
    }
}
