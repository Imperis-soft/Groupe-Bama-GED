<?php

return [
    // Nom commercial de la plateforme (pages publiques, emails système)
    'platform_name' => env('SAAS_PLATFORM_NAME', 'GED'),

    // Éditeur et support
    'vendor_name'   => env('SAAS_VENDOR_NAME', 'Imperis Group'),
    'vendor_url'    => env('SAAS_VENDOR_URL', 'https://imperis.com'),
    'vendor_city'   => env('SAAS_VENDOR_CITY', 'Bamako, Mali'),
    'support_email' => env('SAAS_SUPPORT_EMAIL', 'contact@imperis.com'),
    'support_phone' => env('SAAS_SUPPORT_PHONE', '+223 66 75 66 42'),

    // Compte super administrateur de la plateforme
    'super_admin_email' => env('SAAS_SUPER_ADMIN_EMAIL', 'contact@imperis.com'),

    // Durée de l'essai gratuit proposé à la création d'une entreprise (jours)
    'trial_days' => (int) env('SAAS_TRIAL_DAYS', 14),
];
