<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paramètres d'authentification par défaut
    |--------------------------------------------------------------------------
    |
    | Cette option définit le « gardien » d'authentification et le mot de passe par défaut
    | du « broker » de réinitialisation pour votre application. Vous pouvez modifier ces valeurs
    | selon vos besoins, mais elles constituent un excellent point de départ pour la plupart des applications.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Gardes d'authentification
    |--------------------------------------------------------------------------
    |
    | Ensuite, vous pouvez définir chaque mécanisme d'authentification pour votre application.
    | Bien entendu, une excellente configuration par défaut est déjà définie pour vous
    | qui utilise le stockage de session et le fournisseur d'utilisateurs Eloquent.
    |
    | Tous les systèmes d'authentification possèdent un fournisseur d'utilisateurs, qui définit comment les utilisateurs sont récupérés
    | depuis votre base de données ou tout autre système de stockage
    | utilisé par l'application. Généralement, Eloquent est utilisé.
    |
    | Prise en charge : « session »
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fournisseurs d'utilisateurs
    |--------------------------------------------------------------------------
    |
    | Tous les systèmes d'authentification possèdent un fournisseur d'utilisateurs, qui définit comment les utilisateurs sont récupérés
    | depuis votre base de données ou tout autre système de stockage
    | utilisé par l'application. Généralement, Eloquent est utilisé.
    |
    | Si vous avez plusieurs tables ou modèles d'utilisateurs, vous pouvez configurer plusieurs
    | fournisseurs pour représenter le modèle / la table. Ces fournisseurs peuvent ensuite
    | être affectés à tous les gardes d'authentification supplémentaires que vous avez définis.
    |
    |   Pris en charge : « base de données », « éloquent »
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Réinitialisation des mots de passe
    |--------------------------------------------------------------------------
    |
    | Ces options de configuration spécifient le comportement de la fonctionnalité
    | de réinitialisation du mot de passe de Laravel, y compris la table utilisée
    | pour le stockage des jetons et le fournisseur d'utilisateurs invoqué pour
    | récupérer effectivement les utilisateurs.
    |
    | La durée de validité correspond au nombre de minutes pendant lesquelles chaque jeton de réinitialisation sera
    | considéré comme valide. Cette mesure de sécurité limite la durée de vie des jetons afin
    | de réduire les risques de piratage. Vous pouvez modifier cette durée si nécessaire.
    |
    | Le paramètre de limitation est le nombre de secondes qu'un utilisateur doit attendre avant
    | de générer davantage de jetons de réinitialisation. Cela empêche l'utilisateur de
    | générer rapidement un très grand nombre de jetons de réinitialisation.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Délai d'expiration de la confirmation du mot de passe
    |--------------------------------------------------------------------------
    |
    | Ici, vous pouvez définir le nombre de secondes avant l'expiration de la fenêtre de confirmation du mot de passe.
    | L'utilisateur est alors invité à saisir à nouveau son mot de passe via l'écran de confirmation.
    | Par défaut, le délai d'expiration est de trois heures.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
