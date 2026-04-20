<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\ApprovalStep;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\DocumentArchivalService;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function index(Document $document)
    {
        if (!$document->canView()) {
            abort(403, 'Accès refusé à ce document.');
        }
        $steps = $document->approvalSteps()->with('approver')->get();
        $users = User::orderBy('full_name')->get();
        return view('documents.approval', compact('document', 'steps', 'users'));
    }

    // Configurer le workflow d'approbation
    public function setup(Request $request, Document $document)
    {
        $data = $request->validate([
            'approvers'   => 'required|array|min:1',
            'approvers.*' => 'exists:users,id',
            'due_days'    => 'nullable|integer|min:1|max:365',
        ]);

        // Supprimer les étapes pending existantes
        $document->approvalSteps()->where('status', 'pending')->delete();

        foreach ($data['approvers'] as $order => $userId) {
            ApprovalStep::create([
                'document_id' => $document->id,
                'approver_id' => $userId,
                'step_order'  => $order + 1,
                'status'      => 'pending',
                'due_at'      => isset($data['due_days'])
                    ? now()->addDays($data['due_days'])
                    : null,
            ]);
        }

        // Passer le doc en "review"
        $document->update(['status' => 'review']);

        app(NotificationService::class)->notifyApprovers($document);
        app(DocumentArchivalService::class)->logAction($document, 'approval_setup', 'Workflow d\'approbation configuré');

        return back()->with('success', 'Workflow d\'approbation configuré. Les approbateurs ont été notifiés.');
    }

    // Approuver une étape
    public function approve(Request $request, Document $document, ApprovalStep $step)
    {
        if ($step->approver_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $step->update([
            'status'      => 'approved',
            'comment'     => $request->input('comment'),
            'decided_at'  => now(),
        ]);

        app(DocumentArchivalService::class)->logAction(
            $document, 'step_approved',
            "Étape {$step->step_order} approuvée par " . auth()->user()->full_name
        );

        // Vérifier si toutes les étapes sont approuvées
        $pending = $document->approvalSteps()->where('status', 'pending')->count();
        if ($pending === 0) {
            $document->update(['status' => 'approved']);
            app(DocumentArchivalService::class)->logAction($document, 'approved', 'Document approuvé (toutes les étapes validées)');

            // Notifier le créateur
            if ($document->creator) {
                app(NotificationService::class)->notify(
                    $document->creator,
                    'document_approved',
                    'Document approuvé',
                    "Votre document \"{$document->title}\" a été approuvé.",
                    url("/documents/{$document->id}"),
                    $document
                );
            }
        }

        return back()->with('success', 'Étape approuvée.');
    }

    // Rejeter une étape
    public function reject(Request $request, Document $document, ApprovalStep $step)
    {
        if ($step->approver_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            abort(403);
        }

        $request->validate(['reason' => 'required|string|max:1000']);

        $step->update([
            'status'     => 'rejected',
            'comment'    => $request->input('reason'),
            'decided_at' => now(),
        ]);

        // Repasser le doc en draft
        $document->update(['status' => 'draft']);

        app(DocumentArchivalService::class)->logAction(
            $document, 'step_rejected',
            "Étape {$step->step_order} rejetée : " . $request->input('reason')
        );

        app(NotificationService::class)->notifyRejection($document, $request->input('reason'), auth()->user());

        return back()->with('success', 'Étape rejetée. Le créateur a été notifié.');
    }
}
