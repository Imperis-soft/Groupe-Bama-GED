<?php

namespace App\Services;

use App\Models\ApprovalStep;
use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\SignatureRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Boîte « À traiter » : ce qui attend une action de l'utilisateur,
 * et le suivi des demandes qu'il a lui-même lancées.
 */
class InboxService
{
    // Partages affichés comme « récents »
    public const RECENT_SHARE_DAYS = 30;

    // Étapes d'approbation dont c'est le tour de l'utilisateur (aucune étape précédente en attente)
    public function approvalsQuery(User $user): Builder
    {
        return ApprovalStep::query()
            ->where('approver_id', $user->id)
            ->where('status', 'pending')
            ->whereNotExists(function ($q) {
                $q->selectRaw(1)
                  ->from('approval_steps as previous')
                  ->whereColumn('previous.document_id', 'approval_steps.document_id')
                  ->where('previous.status', 'pending')
                  ->whereColumn('previous.step_order', '<', 'approval_steps.step_order');
            })
            ->whereHas('document');
    }

    // Étapes assignées à l'utilisateur mais qui attendent un approbateur précédent
    public function upcomingApprovalsCount(User $user): int
    {
        return ApprovalStep::where('approver_id', $user->id)
            ->where('status', 'pending')
            ->whereHas('document')
            ->count() - $this->approvalsQuery($user)->count();
    }

    public function signaturesQuery(User $user): Builder
    {
        return SignatureRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->whereHas('document');
    }

    // Documents de l'utilisateur rejetés lors de l'approbation et pas encore resoumis
    public function toFixQuery(User $user): Builder
    {
        return Document::query()
            ->where('creator_id', $user->id)
            ->where('status', 'draft')
            ->whereHas('approvalSteps', fn ($q) => $q->where('status', 'rejected'));
    }

    public function sharesQuery(User $user): Builder
    {
        return DocumentShare::query()
            ->where('shared_with', $user->id)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where('created_at', '>=', now()->subDays(self::RECENT_SHARE_DAYS))
            ->whereHas('document');
    }

    // Nombre d'éléments demandant une action (badge du menu)
    public function count(User $user): int
    {
        return $this->approvalsQuery($user)->count()
            + $this->signaturesQuery($user)->count()
            + $this->toFixQuery($user)->count()
            + $this->sharesQuery($user)->whereNull('accessed_at')->count();
    }

    public function approvals(User $user): Collection
    {
        return $this->approvalsQuery($user)
            ->with(['document.creator', 'document.category', 'document.approvalSteps', 'delegatedFrom'])
            ->orderByRaw('due_at is null, due_at')
            ->oldest()
            ->get();
    }

    public function signatures(User $user): Collection
    {
        return $this->signaturesQuery($user)
            ->with(['document.category', 'requester'])
            ->orderByRaw('due_at is null, due_at')
            ->oldest()
            ->get();
    }

    public function toFix(User $user): Collection
    {
        return $this->toFixQuery($user)
            ->with(['category', 'approvalSteps.approver'])
            ->latest('updated_at')
            ->get()
            ->each(function (Document $document) {
                $document->rejection = $document->approvalSteps
                    ->where('status', 'rejected')
                    ->sortByDesc('decided_at')
                    ->first();
            });
    }

    public function shares(User $user): Collection
    {
        return $this->sharesQuery($user)
            ->with(['document.category', 'sharedBy'])
            ->latest()
            ->limit(50)
            ->get();
    }

    // Suivi : documents de l'utilisateur en cours d'approbation
    public function myApprovalsInProgress(User $user): Collection
    {
        return Document::query()
            ->where('creator_id', $user->id)
            ->where('status', 'review')
            ->whereHas('approvalSteps', fn ($q) => $q->where('status', 'pending'))
            ->with(['approvalSteps.approver'])
            ->latest('updated_at')
            ->get();
    }

    // Suivi : demandes de signature envoyées par l'utilisateur (en attente ou récemment traitées)
    public function mySignatureRequests(User $user): Collection
    {
        return SignatureRequest::query()
            ->where('requested_by', $user->id)
            ->where(fn ($q) => $q->where('status', 'pending')->orWhere('decided_at', '>=', now()->subDays(self::RECENT_SHARE_DAYS)))
            ->whereHas('document')
            ->with(['document', 'signer'])
            ->latest()
            ->limit(50)
            ->get();
    }
}
