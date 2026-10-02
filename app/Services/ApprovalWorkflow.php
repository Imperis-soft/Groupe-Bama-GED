<?php

namespace App\Services;

use App\Models\ApprovalStep;
use App\Models\ApprovalTemplate;
use App\Models\Department;
use App\Models\Document;
use App\Models\User;
use App\Support\CategoryTree;
use App\Support\Tenant;
use Illuminate\Support\Facades\DB;

/**
 * Circuit d'approbation séquentiel :
 * - une seule étape active à la fois, notifiée au moment où elle devient active ;
 * - délai propre à chaque étape, compté à partir de son activation ;
 * - délégation automatique au suppléant d'un approbateur absent ;
 * - relances quotidiennes des étapes en retard (au plus MAX_REMINDERS), l'auteur est prévenu du retard.
 */
class ApprovalWorkflow
{
    public const MAX_REMINDERS = 3;

    public function __construct(
        private NotificationService $notifications,
        private DocumentArchivalService $archival,
    ) {}

    /**
     * Lance le circuit sur un brouillon : le contenu est gelé jusqu'à la décision finale.
     * @param array $steps [['user_id' => int, 'due_days' => ?int], …] dans l'ordre de validation
     * Retourne un message d'erreur, ou null si le circuit est lancé.
     */
    public function start(Document $document, array $steps, ?string $templateName = null): ?string
    {
        if ($error = $this->cannotStart($document, $steps)) {
            return $error;
        }

        DB::transaction(function () use ($document, $steps, $templateName) {
            $offset = (int) $document->approvalSteps()->max('step_order');

            foreach (array_values($steps) as $index => $step) {
                ApprovalStep::create([
                    'document_id' => $document->id,
                    'approver_id' => $step['user_id'],
                    'step_order'  => $offset + $index + 1,
                    'status'      => 'pending',
                    'due_days'    => $step['due_days'] ?? null,
                ]);
            }

            $document->update(['status' => 'review']);
            $names = User::whereIn('id', array_column($steps, 'user_id'))->pluck('full_name', 'id');
            $this->archival->logAction($document, 'approval_setup',
                ($templateName ? "Circuit d'approbation « {$templateName} » lancé" : 'Circuit d\'approbation lancé')
                . ' sur la version ' . $document->version . ' : '
                . collect($steps)->map(fn ($s) => $names[$s['user_id']] ?? '?')->join(' → '),
                ['status' => 'draft'], ['status' => 'review', 'version' => $document->version, 'checksum' => $document->currentChecksum()]);
        });

        if ($current = $this->currentStep($document)) {
            $this->activate($current);
        }

        return null;
    }

    private function cannotStart(Document $document, array $steps): ?string
    {
        if ($document->isUnderLegalHold() || $document->isArchived()) {
            return 'Impossible de lancer un circuit sur un document archivé ou sous gel juridique.';
        }
        if ($document->status !== 'draft') {
            return $document->isInWorkflow()
                ? 'Un circuit est déjà en cours. Retirez d\'abord le document du circuit.'
                : 'Seul un brouillon peut être soumis à approbation. Déposez une nouvelle version pour relancer un circuit.';
        }
        if (!$steps) {
            return 'Le circuit doit comporter au moins une étape.';
        }
        if (in_array($document->creator_id, array_column($steps, 'user_id'))) {
            return 'L\'auteur ne peut pas approuver son propre document.';
        }

        return null;
    }

    /**
     * Lance automatiquement le circuit imposé par la catégorie, avec son modèle de circuit.
     * Retourne null si le circuit est lancé (ou non requis), sinon ce qui empêche de le lancer.
     */
    public function autoStart(Document $document): ?string
    {
        if (!$document->workflowRule()['approval'] || $document->status !== 'draft') {
            return null;
        }

        $templates = ApprovalTemplate::orderBy('name')->get();
        $template = $templates->firstWhere('id', $this->suggestedTemplateId($document, $templates));
        if (!$template) {
            return 'Cette catégorie exige une approbation mais n\'a pas de modèle de circuit : choisissez les approbateurs.';
        }

        $resolved = $this->resolveTemplate($template, $document);
        if ($resolved['errors'] || !$resolved['steps']) {
            return "Le circuit « {$template->name} » ne peut pas être lancé automatiquement : "
                . (implode(' ', $resolved['errors']) ?: 'aucun approbateur.');
        }

        return $this->start($document, $resolved['steps'], $template->name);
    }

    /**
     * Décision sur une étape. $forceReason : décision prise par un administrateur à la place de l'approbateur.
     */
    public function decide(ApprovalStep $step, User $user, bool $approved, ?string $comment, ?string $forceReason = null): void
    {
        $document = $step->document;

        DB::transaction(function () use ($step, $user, $approved, $comment, $forceReason, $document) {
            $step->update([
                'status'            => $approved ? 'approved' : 'rejected',
                'comment'           => $comment,
                'decided_at'        => now(),
                'document_version'  => $document->version,
                'document_checksum' => $document->currentChecksum(),
                'forced_by_id'      => $forceReason ? $user->id : null,
                'force_reason'      => $forceReason,
            ]);

            $who = $forceReason
                ? "{$user->full_name} (administrateur, à la place de {$step->approver?->full_name}) — motif : {$forceReason}"
                : $user->full_name;
            $this->archival->logAction($document,
                $forceReason ? 'step_forced' : ($approved ? 'step_approved' : 'step_rejected'),
                "Étape {$step->step_order} " . ($approved ? 'approuvée' : 'rejetée') . " par {$who}"
                    . " (version {$document->version})" . (!$approved && $comment ? " : {$comment}" : ''),
                null, ['step' => $step->step_order, 'version' => $document->version, 'checksum' => $step->document_checksum]);

            if (!$approved) {
                // Les étapes restantes n'ont plus lieu d'être ; le document repasse en brouillon
                $document->approvalSteps()->where('status', 'pending')->update(['status' => 'skipped']);
                $document->update(['status' => 'draft']);
            }
        });

        if ($approved) {
            $this->advance($document);
        } else {
            $this->notifications->notifyRejection($document, (string) $comment, $user);
        }

        // L'approbateur remplacé est prévenu qu'un administrateur a décidé à sa place
        if ($forceReason && $step->approver && $step->approver->id !== $user->id) {
            $this->notifications->notify($step->approver, 'approval_forced', 'Étape décidée par un administrateur',
                "{$user->full_name} a " . ($approved ? 'approuvé' : 'rejeté') . " à votre place « {$document->title} » : {$forceReason}",
                url("/documents/{$document->id}/approval"), $document);
        }
    }

    /**
     * Retire le document du circuit en cours (approbation ou signature) : il repasse en brouillon,
     * les étapes et demandes en attente sont closes. Motif obligatoire, tracé.
     */
    public function withdraw(Document $document, User $user, string $reason): void
    {
        $pendingSteps = $document->approvalSteps()->where('status', 'pending')->with('approver')->get();
        $pendingRequests = $document->signatureRequests()->where('status', 'pending')->with('signer')->get();
        $before = $document->status;

        DB::transaction(function () use ($document, $reason, $before) {
            $document->approvalSteps()->where('status', 'pending')->update(['status' => 'skipped']);
            $document->signatureRequests()->where('status', 'pending')->update(['status' => 'cancelled', 'decided_at' => now()]);
            $status = $before === 'signing' && $document->hasValidApproval() ? 'approved' : 'draft';
            $document->update(['status' => $status]);
            $this->archival->logAction($document, 'workflow_withdrawn', "Retiré du circuit : {$reason}", ['status' => $before], ['status' => $status]);
        });

        // Les personnes qui avaient le document à traiter sont prévenues
        $people = $pendingSteps->filter(fn ($s) => $s->activated_at)->pluck('approver')
            ->merge($pendingRequests->pluck('signer'))->filter()->unique('id')->reject(fn ($u) => $u->id === $user->id);
        foreach ($people as $person) {
            $this->notifications->notify($person, 'workflow_withdrawn', 'Document retiré du circuit',
                "{$user->full_name} a retiré « {$document->title} » du circuit : {$reason}. Plus aucune action n'est attendue de vous.",
                url("/documents/{$document->id}"), $document);
        }
    }

    // Étape en cours : la première en attente
    public function currentStep(Document $document): ?ApprovalStep
    {
        return $document->approvalSteps()->where('status', 'pending')->orderBy('step_order')->first();
    }

    // Rend une étape active : délégation éventuelle, échéance, notification
    public function activate(ApprovalStep $step): void
    {
        $approver = $step->approver;
        if ($approver) {
            $effective = $approver->effectiveApprover();
            // Jamais confié à l'auteur : il ne peut pas approuver son propre document
            if ($effective->id !== $approver->id && $effective->id !== $step->document->creator_id) {
                $step->delegated_from_id = $step->delegated_from_id ?? $approver->id;
                $step->approver_id = $effective->id;
                $step->setRelation('approver', $effective);
            }
        }

        $step->activated_at = now();
        if ($step->due_days) {
            $step->due_at = now()->addDays($step->due_days)->endOfDay();
        }
        $step->save();

        $this->notifications->notifyApprovalNeeded($step);
    }

    // Après une approbation : étape suivante, ou document approuvé
    public function advance(Document $document): void
    {
        if ($next = $this->currentStep($document)) {
            $this->activate($next);
            return;
        }

        $document->update(['status' => 'approved']);
        $this->archival->logAction($document, 'approved', "Document approuvé (toutes les étapes validées, version {$document->version})",
            ['status' => 'review'], ['status' => 'approved', 'version' => $document->version, 'checksum' => $document->currentChecksum()]);

        $needsSignature = $document->workflowRule()['signature'];
        // Officiel dès l'approbation si aucune signature n'est exigée
        if (!$needsSignature) {
            \App\Jobs\CreateOfficialCopy::dispatch($document->id);
        }

        if ($document->creator) {
            $this->notifications->notify(
                $document->creator,
                'document_approved',
                'Document approuvé',
                "Votre document \"{$document->title}\" a été approuvé."
                    . ($needsSignature ? ' Il doit maintenant être signé : envoyez les demandes de signature.' : ''),
                url($needsSignature ? "/documents/{$document->id}/signatures" : "/documents/{$document->id}"),
                $document
            );
        }
    }

    /**
     * L'utilisateur vient de déclarer une absence en cours : ses étapes actives passent à son suppléant.
     * Retourne le nombre d'étapes confiées.
     */
    public function reassignAbsent(User $user): int
    {
        if (!$user->isAbsent() || !$user->delegate_id) {
            return 0;
        }

        $count = 0;
        $steps = ApprovalStep::where('approver_id', $user->id)->where('status', 'pending')->whereNotNull('activated_at')->with('document')->get();
        foreach ($steps as $step) {
            if (!$step->document || $this->currentStep($step->document)?->id !== $step->id) {
                continue;
            }
            $effective = $user->effectiveApprover();
            if ($effective->id === $user->id || $effective->id === $step->document->creator_id) {
                continue;
            }
            $step->update(['delegated_from_id' => $step->delegated_from_id ?? $user->id, 'approver_id' => $effective->id]);
            $step->setRelation('approver', $effective);
            $this->archival->logAction($step->document, 'approval_delegated',
                "Étape {$step->step_order} confiée à {$effective->full_name} (absence de {$user->full_name})");
            $this->notifications->notifyApprovalNeeded($step);
            $count++;
        }

        return $count;
    }

    /**
     * Relance des étapes en retard (tâche planifiée quotidienne et bouton « Relancer »).
     * Retourne le nombre de relances envoyées.
     */
    public function remindOverdue(): int
    {
        $sent = 0;
        $steps = ApprovalStep::query()
            ->where('status', 'pending')
            ->whereNotNull('due_at')->where('due_at', '<', now())
            ->where('reminders_sent', '<', self::MAX_REMINDERS)
            ->where(fn ($q) => $q->whereNull('last_reminded_at')->orWhere('last_reminded_at', '<', now()->subHours(23)))
            ->with(['document' => fn ($q) => $q->withoutGlobalScopes()->whereNull('deleted_at'), 'approver'])
            ->get();

        foreach ($steps as $step) {
            if (!$step->document || !$step->approver) {
                continue;
            }
            Tenant::run($step->document->organization, function () use ($step, &$sent) {
                if ($this->currentStep($step->document)?->id === $step->id && $this->remind($step)) {
                    $sent++;
                }
            });
        }

        return $sent;
    }

    // Relance d'une étape ; au premier retard, l'auteur du document est aussi prévenu
    public function remind(ApprovalStep $step): bool
    {
        if (!$step->isPending()) {
            return false;
        }

        $this->notifications->notifyApprovalNeeded($step, reminder: true);

        if ($step->reminders_sent === 0 && $step->isOverdue() && ($creator = $step->document->creator) && $creator->id !== $step->approver_id) {
            $this->notifications->notify(
                $creator,
                'approval_overdue',
                'Validation en retard',
                "La validation de « {$step->document->title} » par {$step->approver->full_name} a dépassé son échéance du {$step->due_at->format('d/m/Y')}. Une relance a été envoyée.",
                url("/documents/{$step->document_id}/approval"),
                $step->document
            );
        }

        $step->update(['reminders_sent' => $step->reminders_sent + 1, 'last_reminded_at' => now()]);

        return true;
    }

    /**
     * Traduit un modèle de circuit en approbateurs pour un document.
     * @return array{steps: array<int, array{user_id:int, name:string, due_days:?int, label:string, absent:?string}>, errors: string[]}
     */
    public function resolveTemplate(ApprovalTemplate $template, Document $document): array
    {
        $organizationId = $document->organization_id;
        $resolved = [];
        $errors = [];

        foreach ($template->steps ?? [] as $index => $step) {
            $number = $index + 1;
            [$user, $label] = match ($step['type'] ?? null) {
                'user' => [
                    User::where('organization_id', $organizationId)->where('is_active', true)->find($step['user_id'] ?? 0),
                    'Personne désignée',
                ],
                'department_manager' => [
                    ($department = Department::find($step['department_id'] ?? 0)) ? $department->managerFor($organizationId) : null,
                    'Responsable ' . ($department?->name ?? 'd\'un service supprimé'),
                ],
                'creator_manager' => [
                    $this->creatorManager($document),
                    'Responsable du service de l\'auteur',
                ],
                default => [null, 'Étape inconnue'],
            };

            if ($user && $user->id === $document->creator_id) {
                $errors[] = "Étape {$number} ({$label}) : c'est l'auteur du document, qui ne peut pas s'approuver lui-même.";
                continue;
            }
            if (!$user) {
                $errors[] = "Étape {$number} ({$label}) : aucune personne trouvée"
                    . (($step['type'] ?? null) === 'user' ? ' (compte supprimé ou désactivé).' : ' (responsable non défini).');
                continue;
            }
            // Deux étapes consécutives pour la même personne : une seule suffit
            if ($resolved && end($resolved)['user_id'] === $user->id) {
                continue;
            }

            $resolved[] = [
                'user_id'  => $user->id,
                'name'     => $user->full_name,
                'due_days' => isset($step['due_days']) && $step['due_days'] !== '' ? (int) $step['due_days'] : null,
                'label'    => $label,
                'absent'   => $user->isAbsent() ? $user->effectiveApprover()->full_name : null,
            ];
        }

        return ['steps' => $resolved, 'errors' => $errors];
    }

    // Responsable du premier service de l'auteur qui en a un (autre que l'auteur lui-même)
    private function creatorManager(Document $document): ?User
    {
        $creator = $document->creator;
        if (!$creator) {
            return null;
        }
        foreach ($creator->departments()->orderBy('name')->get() as $department) {
            $manager = $department->managerFor($document->organization_id);
            if ($manager && $manager->id !== $creator->id) {
                return $manager;
            }
        }

        return null;
    }

    // Modèle proposé en priorité : celui de la catégorie du document (ou d'une catégorie parente)
    public function suggestedTemplateId(Document $document, iterable $templates): ?int
    {
        if (!$document->category_id) {
            return null;
        }
        $path = array_reverse(array_map(fn ($c) => $c->id, (new CategoryTree(withCounts: false))->path($document->category_id)));
        foreach ($path as $categoryId) {
            foreach ($templates as $template) {
                if ($template->category_id === $categoryId) {
                    return $template->id;
                }
            }
        }

        return null;
    }
}
