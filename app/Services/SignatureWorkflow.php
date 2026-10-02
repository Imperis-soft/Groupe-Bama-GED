<?php

namespace App\Services;

use App\Models\Document;
use App\Models\SignatureRequest;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Circuit de signature :
 * - seules les personnes sollicitées signent, et seulement un document approuvé si sa catégorie l'exige ;
 * - pendant la signature, le contenu est gelé (statut « signing ») ;
 * - le document est « Signé » quand plus aucune demande n'est en attente et qu'au moins une signature porte sur le contenu actuel ;
 * - un refus clôt la série : les autres demandes sont annulées, le document revient à l'étape précédente ;
 * - relances quotidiennes des demandes en retard (au plus MAX_REMINDERS).
 */
class SignatureWorkflow
{
    public const MAX_REMINDERS = 3;

    public function __construct(
        private NotificationService $notifications,
        private DocumentArchivalService $archival,
    ) {}

    /**
     * Envoie les demandes de signature. Retourne les signataires sollicités (sans ceux déjà en attente).
     * @throws \DomainException si le document ne peut pas être envoyé en signature
     */
    public function request(Document $document, User $requester, array $signerIds, ?string $message, ?int $dueDays): Collection
    {
        if ($document->isArchived() || $document->isUnderLegalHold()) {
            throw new \DomainException('Impossible de demander une signature sur un document archivé ou sous gel juridique.');
        }
        if (!$document->canBeSentForSignature()) {
            throw new \DomainException($document->status === 'review'
                ? 'Le circuit d\'approbation doit être terminé avant de demander des signatures.'
                : 'Cette catégorie exige une approbation avant signature : lancez d\'abord le circuit d\'approbation.');
        }

        $alreadyPending = $document->signatureRequests()->where('status', 'pending')->pluck('user_id')->all();
        $signers = User::whereIn('id', $signerIds)->whereNotIn('id', $alreadyPending)->get();
        if ($signers->isEmpty()) {
            return $signers;
        }

        $before = $document->status;
        DB::transaction(function () use ($document, $requester, $signers, $message, $dueDays, $before) {
            foreach ($signers as $signer) {
                SignatureRequest::create([
                    'document_id'  => $document->id,
                    'requested_by' => $requester->id,
                    'user_id'      => $signer->id,
                    'message'      => $message,
                    'due_at'       => $dueDays ? now()->addDays($dueDays)->endOfDay() : null,
                ]);
            }
            $document->update(['status' => 'signing']);
            $this->archival->logAction($document, 'signature_requested',
                'Signature demandée à ' . $signers->pluck('full_name')->join(', ') . " (version {$document->version})",
                ['status' => $before], ['status' => 'signing', 'version' => $document->version, 'checksum' => $document->currentChecksum()]);
        });

        foreach ($signers as $signer) {
            $this->notifications->notify($signer, 'signature_requested', 'Signature demandée',
                "{$requester->full_name} vous demande de signer « {$document->title} »." . ($message ? "\n\n« {$message} »" : ''),
                route('documents.signatures', $document), $document);
        }

        return $signers;
    }

    // Le signataire refuse : la série est close, le document revient à l'étape précédente
    public function decline(SignatureRequest $signatureRequest, User $signer, string $reason): void
    {
        $document = $signatureRequest->document;

        DB::transaction(function () use ($signatureRequest, $signer, $reason, $document) {
            $signatureRequest->update(['status' => 'declined', 'decline_reason' => $reason, 'decided_at' => now()]);
            $others = $document->signatureRequests()->where('status', 'pending')->count();
            $document->signatureRequests()->where('status', 'pending')->update(['status' => 'cancelled', 'decided_at' => now()]);
            $status = $this->statusWithoutSignature($document);
            $document->update(['status' => $status]);
            $this->archival->logAction($document, 'signature_declined',
                "Signature refusée par {$signer->full_name} : {$reason}" . ($others ? " ({$others} autre(s) demande(s) annulée(s))" : ''),
                ['status' => 'signing'], ['status' => $status]);
        });

        $notify = collect([$signatureRequest->requester, $document->creator])->filter()->unique('id')->reject(fn ($u) => $u->id === $signer->id);
        foreach ($notify as $user) {
            $this->notifications->notify($user, 'signature_declined', 'Signature refusée',
                "{$signer->full_name} a refusé de signer « {$document->title} » : {$reason}",
                route('documents.signatures', $document), $document);
        }
    }

    // Annulation d'une demande par le demandeur ou le gestionnaire du document
    public function cancel(SignatureRequest $signatureRequest, User $user): void
    {
        $signatureRequest->update(['status' => 'cancelled', 'decided_at' => now()]);
        $this->archival->logAction($signatureRequest->document, 'signature_cancelled',
            "Demande de signature à {$signatureRequest->signer?->full_name} annulée par {$user->full_name}");

        if ($signatureRequest->signer && $signatureRequest->signer->id !== $user->id) {
            $this->notifications->notify($signatureRequest->signer, 'signature_cancelled', 'Demande de signature annulée',
                "La demande de signature de « {$signatureRequest->document->title} » a été annulée. Plus aucune action n'est attendue de vous.",
                url("/documents/{$signatureRequest->document_id}"), $signatureRequest->document);
        }

        $this->settle($signatureRequest->document);
    }

    /**
     * Clôt la série quand plus aucune signature n'est attendue :
     * « Signé » si au moins une signature porte sur le contenu actuel, sinon retour à l'étape précédente.
     */
    public function settle(Document $document): void
    {
        $document->refresh();
        if ($document->status !== 'signing' || $document->signatureRequests()->where('status', 'pending')->exists()) {
            return;
        }

        $checksum = $document->currentChecksum();
        $signed = $document->signatures()->where('status', 'signed')->where('document_checksum', $checksum)->with('user')->get();

        if ($signed->isEmpty()) {
            $status = $this->statusWithoutSignature($document);
            $document->update(['status' => $status]);
            $this->archival->logAction($document, 'signature_round_closed', 'Plus aucune signature attendue, aucune signature obtenue',
                ['status' => 'signing'], ['status' => $status]);
            return;
        }

        $document->update(['status' => 'signed']);
        \App\Jobs\CreateOfficialCopy::dispatch($document->id);
        $this->archival->logAction($document, 'fully_signed',
            "Document entièrement signé (version {$document->version}) par " . $signed->pluck('user.full_name')->unique()->join(', '),
            ['status' => 'signing'], ['status' => 'signed', 'version' => $document->version, 'checksum' => $checksum]);

        if ($document->creator) {
            $this->notifications->notify($document->creator, 'document_signed', 'Document signé',
                "Toutes les signatures de « {$document->title} » ont été recueillies.",
                route('documents.signatures', $document), $document);
        }
    }

    // Statut quand la signature n'aboutit pas : approuvé si l'approbation porte toujours sur ce contenu
    private function statusWithoutSignature(Document $document): string
    {
        return $document->hasValidApproval() ? 'approved' : 'draft';
    }

    // Relance quotidienne des demandes en retard. Retourne le nombre de relances envoyées.
    public function remindOverdue(): int
    {
        $sent = 0;
        $requests = SignatureRequest::query()
            ->where('status', 'pending')
            ->whereNotNull('due_at')->where('due_at', '<', now())
            ->where('reminders_sent', '<', self::MAX_REMINDERS)
            ->where(fn ($q) => $q->whereNull('last_reminded_at')->orWhere('last_reminded_at', '<', now()->subHours(23)))
            ->with(['document' => fn ($q) => $q->withoutGlobalScopes()->whereNull('deleted_at'), 'signer', 'requester'])
            ->get();

        foreach ($requests as $signatureRequest) {
            if (!$signatureRequest->document || !$signatureRequest->signer) {
                continue;
            }
            Tenant::run($signatureRequest->document->organization, function () use ($signatureRequest, &$sent) {
                $document = $signatureRequest->document;
                $this->notifications->notify($signatureRequest->signer, 'signature_requested', 'Rappel : signature en retard',
                    "La signature de « {$document->title} » était attendue pour le {$signatureRequest->due_at->format('d/m/Y')}.",
                    route('documents.signatures', $document), $document);

                // Au premier retard, le demandeur est prévenu
                if ($signatureRequest->reminders_sent === 0 && $signatureRequest->requester) {
                    $this->notifications->notify($signatureRequest->requester, 'signature_overdue', 'Signature en retard',
                        "La signature de « {$document->title} » par {$signatureRequest->signer->full_name} a dépassé son échéance. Une relance a été envoyée.",
                        route('documents.signatures', $document), $document);
                }

                $signatureRequest->update(['reminders_sent' => $signatureRequest->reminders_sent + 1, 'last_reminded_at' => now()]);
                $sent++;
            });
        }

        return $sent;
    }
}
