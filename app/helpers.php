<?php

if (!function_exists('statusLabel')) {
    /**
     * Retourne le libellé français d'un statut de document.
     */
    function statusLabel(?string $status): string
    {
        return match ($status) {
            'draft'    => 'Brouillon',
            'review'   => 'En approbation',
            'approved' => 'Approuvé',
            'signing'  => 'En signature',
            'signed'   => 'Signé',
            'archived' => 'Archivé',
            'pending'  => 'En attente',
            'rejected' => 'Rejeté',
            'skipped'  => 'Annulé',
            default    => ucfirst($status ?? ''),
        };
    }
}

if (!function_exists('actionLabel')) {
    /**
     * Retourne le libellé français d'une action d'audit.
     */
    function actionLabel(?string $action): string
    {
        return match ($action) {
            'created'    => 'Créé',
            'updated'    => 'Modifié',
            'viewed'     => 'Consulté',
            'downloaded' => 'Téléchargé',
            'archived'   => 'Archivé',
            'unarchived' => 'Désarchivé',
            'purged'     => 'Détruit (purge)',
            'restored'   => 'Restauré',
            'legal_hold_enabled'  => 'Gel juridique activé',
            'legal_hold_disabled' => 'Gel juridique levé',
            'retention_applied'   => 'Durée de conservation appliquée',
            'retention_extended'  => 'Conservation prolongée',
            'retention_permanent' => 'Conservation définitive',
            'eliminated'          => 'Éliminé (procès-verbal)',
            'integrity_failed'    => 'Alerte d\'intégrité',
            'qr_regenerated'      => 'QR code régénéré',
            'exported'            => 'Exporté',
            'archival_copy_created' => 'Copie PDF/A créée',
            'archival_copy_skipped' => 'Copie PDF/A non créée',
            'deleted'    => 'Supprimé',
            'approved'   => 'Approuvé',
            'rejected'   => 'Rejeté',
            'signed'     => 'Signé',
            'approval_setup'         => 'Circuit d\'approbation lancé',
            'approval_delegated'     => 'Étape confiée au suppléant',
            'approval_reminded'      => 'Relance d\'approbation',
            'step_approved'          => 'Étape approuvée',
            'step_rejected'          => 'Étape rejetée',
            'step_forced'            => 'Étape forcée par un administrateur',
            'workflow_withdrawn'     => 'Retiré du circuit',
            'workflow_reset'         => 'Repassé en brouillon (nouveau contenu)',
            'signature_requested'    => 'Signature demandée',
            'signature_declined'     => 'Signature refusée',
            'signature_cancelled'    => 'Demande de signature annulée',
            'signature_round_closed' => 'Série de signatures close',
            'fully_signed'           => 'Entièrement signé',
            'official_copy_created'  => 'Copie officielle créée',
            'version_created'        => 'Nouvelle version',
            'version_restored'       => 'Version restaurée',
            'shared'     => 'Partagé',
            'locked'     => 'Verrouillé',
            'unlocked'   => 'Déverrouillé',
            default      => ucfirst($action ?? ''),
        };
    }
}

if (!function_exists('appSettings')) {
    /**
     * Paramètres effectifs d'une entreprise (par défaut : l'entreprise courante).
     *
     * - Les paramètres de la plateforme (organization_id NULL) servent de valeurs par défaut.
     * - L'entreprise utilise son propre SMTP s'il est activé, sinon celui de la plateforme.
     * - Le mot de passe SMTP est déchiffré.
     */
    function appSettings(?int $organizationId = null): array
    {
        $organizationId ??= \App\Support\Tenant::id();

        $load = function (?int $orgId): array {
            $settings = \Illuminate\Support\Facades\DB::table('settings')
                ->where('organization_id', $orgId)
                ->pluck('value', 'key')
                ->toArray();

            if (!empty($settings['mail_password'])) {
                try {
                    $settings['mail_password'] = \Illuminate\Support\Facades\Crypt::decryptString($settings['mail_password']);
                } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
                    // Ancienne valeur stockée en clair : on la garde telle quelle
                }
            }

            return $settings;
        };

        $platform = $load(null);
        if (!$organizationId) {
            return $platform;
        }

        $org      = $load($organizationId);
        $mailKeys = ['mail_enabled', 'mail_host', 'mail_port', 'mail_username', 'mail_password',
                     'mail_encryption', 'mail_from_address', 'mail_from_name'];
        $orgMail  = ($org['mail_enabled'] ?? '0') === '1' && !empty($org['mail_host']);

        // Une valeur vide côté entreprise = reprendre celle de la plateforme
        $orgOverrides = array_filter(array_diff_key($org, array_flip($mailKeys)), fn ($v) => $v !== null && $v !== '');
        $merged = array_merge($platform, $orgOverrides);
        foreach ($mailKeys as $key) {
            $source = $orgMail ? $org : $platform;
            if (array_key_exists($key, $source)) {
                $merged[$key] = $source[$key];
            } else {
                unset($merged[$key]);
            }
        }

        return $merged;
    }
}

if (!function_exists('brandName')) {
    /**
     * Nom affiché : l'entreprise courante, sinon le nom de la plateforme.
     */
    function brandName(): string
    {
        return \App\Support\Tenant::organization()?->name ?? config('saas.platform_name');
    }
}

if (!function_exists('formatBytes')) {
    /**
     * Taille lisible : 1,5 Go, 320 Mo…
     */
    function formatBytes(int|float $bytes): string
    {
        $units = ['o', 'Ko', 'Mo', 'Go', 'To'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return number_format($bytes, $i === 0 ? 0 : 1, ',', ' ') . ' ' . $units[$i];
    }
}

if (!function_exists('formatMoney')) {
    function formatMoney(int|float $amount, string $currency = 'XOF'): string
    {
        return number_format($amount, 0, ',', ' ') . ' ' . $currency;
    }
}

if (!function_exists('initials')) {
    /**
     * Initiales d'un nom pour les avatars : « Awa Traoré » → « AT ».
     */
    function initials(?string $name): string
    {
        $words = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
        $letters = array_map(fn ($w) => mb_substr($w, 0, 1), array_slice($words, 0, 2));
        return mb_strtoupper(implode('', $letters)) ?: '?';
    }
}
