<?php

namespace App\Services;

use App\Models\GedNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    private array $settings;

    public function __construct()
    {
        $this->settings = DB::table('settings')->pluck('value', 'key')->toArray();
    }

    private function isMailEnabled(): bool
    {
        return ($this->settings['mail_enabled'] ?? '0') === '1'
            && !empty($this->settings['mail_host'] ?? '');
    }

    private function isNotifEnabled(string $type): bool
    {
        return ($this->settings["notif_{$type}"] ?? '1') === '1';
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

        // Envoyer email si config présente et type activé
        if ($this->isMailEnabled() && $this->isNotifEnabled($this->mapType($type))) {
            $this->sendEmail($user, $title, $message, $link, $notif);
        }

        return $notif;
    }

    private function mapType(string $type): string
    {
        return match(true) {
            str_contains($type, 'approval') => 'approval',
            str_contains($type, 'share')    => 'share',
            str_contains($type, 'expir')    => 'expiry',
            str_contains($type, 'comment')  => 'comment',
            default                         => 'approval',
        };
    }

    private function sendEmail(User $user, string $title, string $message, ?string $link, GedNotification $notif): void
    {
        try {
            // Configurer le mailer dynamiquement depuis les settings
            config([
                'mail.mailers.smtp.host'       => $this->settings['mail_host'] ?? '',
                'mail.mailers.smtp.port'       => $this->settings['mail_port'] ?? 587,
                'mail.mailers.smtp.username'   => $this->settings['mail_username'] ?? '',
                'mail.mailers.smtp.password'   => $this->settings['mail_password'] ?? '',
                'mail.mailers.smtp.encryption' => $this->settings['mail_encryption'] ?? 'tls',
                'mail.from.address'            => $this->settings['mail_from_address'] ?? '',
                'mail.from.name'               => $this->settings['mail_from_name'] ?? 'GED',
            ]);

            Mail::send([], [], function ($mail) use ($user, $title, $message, $link) {
                $body = "<h2>{$title}</h2><p>{$message}</p>";
                if ($link) $body .= "<p><a href='{$link}'>Voir le document</a></p>";

                $mail->to($user->email, $user->full_name)
                     ->subject("[GED] {$title}")
                     ->html($body);
            });

            $notif->update(['email_sent' => true]);
        } catch (\Exception $e) {
            Log::warning('GED notification email failed: ' . $e->getMessage());
        }
    }

    // Notifier tous les approbateurs d'un document
    public function notifyApprovers(\App\Models\Document $document): void
    {
        if (!$this->isNotifEnabled('approval')) return;

        $steps = $document->approvalSteps()->where('status', 'pending')->with('approver')->get();
        foreach ($steps as $step) {
            $this->notify(
                $step->approver,
                'approval_needed',
                'Approbation requise',
                "Le document \"{$document->title}\" attend votre approbation.",
                url("/documents/{$document->id}"),
                $document
            );
        }
    }

    // Notifier le créateur d'un rejet
    public function notifyRejection(\App\Models\Document $document, string $reason, User $rejectedBy): void
    {
        if (!$document->creator) return;
        $this->notify(
            $document->creator,
            'approval_rejected',
            'Document rejeté',
            "Votre document \"{$document->title}\" a été rejeté par {$rejectedBy->full_name}. Raison : {$reason}",
            url("/documents/{$document->id}"),
            $document
        );
    }

    // Notifier un partage
    public function notifyShare(\App\Models\DocumentShare $share): void
    {
        if (!$this->isNotifEnabled('share') || !$share->sharedWith) return;
        $this->notify(
            $share->sharedWith,
            'document_shared',
            'Document partagé avec vous',
            "{$share->sharedBy->full_name} a partagé \"{$share->document->title}\" avec vous.",
            url("/documents/{$share->document_id}"),
            $share->document
        );
    }

    // Notifier un nouveau commentaire
    public function notifyComment(\App\Models\DocumentComment $comment): void
    {
        if (!$this->isNotifEnabled('comment')) return;
        $doc = $comment->document;
        // Notifier le créateur du doc (sauf si c'est lui qui commente)
        if ($doc->creator && $doc->creator_id !== $comment->user_id) {
            $this->notify(
                $doc->creator,
                'comment_added',
                'Nouveau commentaire',
                "{$comment->user->full_name} a commenté \"{$doc->title}\".",
                url("/documents/{$doc->id}"),
                $doc
            );
        }
    }
}
