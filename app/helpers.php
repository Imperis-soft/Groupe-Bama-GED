<?php

if (!function_exists('statusLabel')) {
    /**
     * Retourne le libellé français d'un statut de document.
     */
    function statusLabel(?string $status): string
    {
        return match ($status) {
            'draft'    => 'Brouillon',
            'review'   => 'En révision',
            'approved' => 'Approuvé',
            'archived' => 'Archivé',
            'pending'  => 'En attente',
            'rejected' => 'Rejeté',
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
            'deleted'    => 'Supprimé',
            'approved'   => 'Approuvé',
            'rejected'   => 'Rejeté',
            'signed'     => 'Signé',
            'shared'     => 'Partagé',
            'locked'     => 'Verrouillé',
            'unlocked'   => 'Déverrouillé',
            default      => ucfirst($action ?? ''),
        };
    }
}
