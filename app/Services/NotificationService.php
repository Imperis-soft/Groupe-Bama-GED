<?php

namespace App\Services;

use App\Models\GedNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    // Paramètres chargés par entreprise (le service peut servir plusieurs entreprises en console)
    private array $settingsCache = [];

    private function settings(): array
    {
        $key = \App\Support\Tenant::id() ?? 0;
        return $this->settingsCache[$key] ??= appSettings();
    }

    private function isMailEnabled(): bool
    {
        return ($this->settings()['mail_enabled'] ?? '0') === '1'
            && !empty($this->settings()['mail_host'] ?? '');
    }

    private function isNotifEnabled(string $type): bool
    {
        return ($this->settings()["notif_{$type}"] ?? '1') === '1';
    }

    public function notify(User $user, string $type, string $title, string $message, string $link = null, $notifiable = null): GedNotification
    {
        $notif = GedNotification::create([
            'user_id'         => $user->id,
            'type'            => $type,
            'title'           => $title,
            'message'         => $message,
            'link'            => $link,
            'notifiable_type' => $notifiable ? get_class($notifiable) : null,
            'notifiable_id'   => $notifiable?->id,
            'is_read'         => false,
            'email_sent'      => false,
        ]);

        if ($this->isMailEnabled() && $this->isNotifEnabled($this->mapType($type))) {
            // Template générique fallback
            $this->sendEmail($user, $title, $message, $link, $notif, 'emails.generic', [
                'recipientName' => $user->full_name,
                'bodyMessage'   => $message,
            ]);
        }

        return $notif;
    }

    private function mapType(string $type): string
    {
        return match(true) {
            str_contains($type, 'approval') => 'approval',
            str_contains($type, 'share')    => 'share',
            str_contains($type, 'expir')    => 'expiry',
            str_contains($type, 'retention') => 'expiry',
            str_contains($type, 'integrity') => 'approval',
            str_contains($type, 'comment')  => 'comment',
            default                         => 'approval',
        };
    }

    private function sendEmail(User $user, string $title, string $message, ?string $link, GedNotification $notif, string $template = 'emails.generic', array $data = []): void
    {
        try {
            config([
                'mail.mailers.smtp.host'       => $this->settings()['mail_host'] ?? '',
                'mail.mailers.smtp.port'       => $this->settings()['mail_port'] ?? 587,
                'mail.mailers.smtp.username'   => $this->settings()['mail_username'] ?? '',
                'mail.mailers.smtp.password'   => $this->settings()['mail_password'] ?? '',
                'mail.mailers.smtp.encryption' => $this->settings()['mail_encryption'] ?? 'tls',
                'mail.from.address'            => $this->settings()['mail_from_address'] ?? '',
                'mail.from.name'               => $this->settings()['mail_from_name'] ?? 'GED',
            ]);

            $viewData = array_merge(['subject' => $title, 'link' => $link], $data);

            Mail::send($template, $viewData, function ($mail) use ($user, $title) {
                $mail->to($user->email, $user->full_name)
                     ->subject('[' . brandName() . "] {$title}");
            });

            $notif->update(['email_sent' => true]);
        } catch (\Exception $e) {
            Log::warning('GED notification email failed: ' . $e->getMessage());
            // Souvent un SMTP mal configuré dans les paramètres de l'entreprise
            \App\Models\SystemEvent::record('warning', 'mail', 'Envoi d\'email impossible : ' . $e->getMessage(),
                ['to' => $user->email, 'smtp' => $this->settings()['mail_host'] ?? null],
                \App\Support\Tenant::check() ? \App\Support\Tenant::id() : null,
                hash('sha256', 'mail|' . (\App\Support\Tenant::check() ? \App\Support\Tenant::id() : 0) . '|' . get_class($e)));
        }
    }

    // Notifier l'approbateur de l'étape en cours (les suivants le seront à leur tour)
    public function notifyApprovers(\App\Models\Document $document): void
    {
        $step = $document->approvalSteps()->where('status', 'pending')->orderBy('step_order')->first();
        if ($step) {
            $this->notifyApprovalNeeded($step);
        }
    }

    // Validation demandée à l'approbateur d'une étape (ou relance si elle est en retard)
    public function notifyApprovalNeeded(\App\Models\ApprovalStep $step, bool $reminder = false): void
    {
        if (!$this->isNotifEnabled('approval') || !$step->approver) return;

        $document = $step->document;
        $approver = $step->approver;
        $link = url("/documents/{$document->id}/approval");
        $title = $reminder ? 'Rappel : validation en attente' : 'Validation requise';
        $message = $reminder
            ? "Le document \"{$document->title}\" attend toujours votre validation" . ($step->due_at ? ' (échéance : ' . $step->due_at->format('d/m/Y') . ')' : '') . '.'
            : "Le document \"{$document->title}\" attend votre validation.";
        if ($step->delegated_from_id && $step->delegatedFrom) {
            $message .= " Vous remplacez {$step->delegatedFrom->full_name}, absent(e).";
        }

        $notif = GedNotification::create([
            'user_id'         => $approver->id,
            'type'            => $reminder ? 'approval_reminder' : 'approval_needed',
            'title'           => $title,
            'message'         => $message,
            'link'            => $link,
            'notifiable_type' => get_class($document),
            'notifiable_id'   => $document->id,
            'is_read'         => false,
            'email_sent'      => false,
        ]);

        if ($this->isMailEnabled()) {
            $this->sendEmail($approver, $title, '', $link, $notif, 'emails.approval-needed', [
                'recipientName' => $approver->full_name,
                'documentTitle' => $document->title,
                'documentRef'   => $document->reference,
                'category'      => $document->category?->name,
                'stepOrder'     => 'Étape ' . $step->step_order . ($reminder ? ' — rappel' : ''),
                'dueDate'       => $step->due_at?->format('d/m/Y'),
            ]);
        }
    }

    // Notifier le créateur d'un rejet
    public function notifyRejection(\App\Models\Document $document, string $reason, User $rejectedBy): void
    {
        if (!$document->creator) return;

        $notif = GedNotification::create([
            'user_id'         => $document->creator->id,
            'type'            => 'approval_rejected',
            'title'           => 'Document refusé',
            'message'         => "Votre document \"{$document->title}\" a été refusé par {$rejectedBy->full_name}. Raison : {$reason}",
            'link'            => url("/documents/{$document->id}"),
            'notifiable_type' => get_class($document),
            'notifiable_id'   => $document->id,
            'is_read'         => false,
            'email_sent'      => false,
        ]);

        if ($this->isMailEnabled() && $this->isNotifEnabled('approval')) {
            $this->sendEmail($document->creator, 'Document refusé', '', url("/documents/{$document->id}"), $notif, 'emails.document-rejected', [
                'recipientName' => $document->creator->full_name,
                'documentTitle' => $document->title,
                'documentRef'   => $document->reference,
                'rejectedBy'    => $rejectedBy->full_name,
                'reason'        => $reason,
            ]);
        }
    }

    // Notifier un partage
    public function notifyShare(\App\Models\DocumentShare $share): void
    {
        if (!$this->isNotifEnabled('share') || !$share->sharedWith) return;

        $notif = GedNotification::create([
            'user_id'         => $share->sharedWith->id,
            'type'            => 'document_shared',
            'title'           => 'Document partagé avec vous',
            'message'         => "{$share->sharedBy->full_name} a partagé \"{$share->document->title}\" avec vous.",
            'link'            => url("/documents/{$share->document_id}"),
            'notifiable_type' => get_class($share->document),
            'notifiable_id'   => $share->document_id,
            'is_read'         => false,
            'email_sent'      => false,
        ]);

        if ($this->isMailEnabled()) {
            $this->sendEmail($share->sharedWith, 'Document partagé avec vous', '', url("/documents/{$share->document_id}"), $notif, 'emails.document-shared', [
                'recipientName' => $share->sharedWith->full_name,
                'documentTitle' => $share->document->title,
                'documentRef'   => $share->document->reference,
                'sharedBy'      => $share->sharedBy->full_name,
                'accessLevel'   => $share->access_level,
                'expiresAt'     => $share->expires_at?->format('d/m/Y'),
                'message'       => $share->message,
            ]);
        }
    }

    // Notifier un nouveau commentaire
    public function notifyComment(\App\Models\DocumentComment $comment): void
    {
        if (!$this->isNotifEnabled('comment')) return;

        $doc = $comment->document;
        if (!$doc->creator || $doc->creator_id === $comment->user_id) return;

        $notif = GedNotification::create([
            'user_id'         => $doc->creator->id,
            'type'            => 'comment_added',
            'title'           => 'Nouveau commentaire',
            'message'         => "{$comment->user->full_name} a commenté \"{$doc->title}\".",
            'link'            => url("/documents/{$doc->id}"),
            'notifiable_type' => get_class($doc),
            'notifiable_id'   => $doc->id,
            'is_read'         => false,
            'email_sent'      => false,
        ]);

        if ($this->isMailEnabled()) {
            $this->sendEmail($doc->creator, 'Nouveau commentaire', '', url("/documents/{$doc->id}"), $notif, 'emails.new-comment', [
                'recipientName'  => $doc->creator->full_name,
                'documentTitle'  => $doc->title,
                'documentRef'    => $doc->reference,
                'commentBy'      => $comment->user->full_name,
                'commentContent' => $comment->content,
            ]);
        }
    }
}
