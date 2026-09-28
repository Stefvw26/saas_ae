<?php
// fichier : config/app.php — CRM Auto-École, Jalon 1
declare(strict_types=1);

/**
 * Configuration générale.
 * env 'dev'  : debug détaillé activé (migrate.php accessible).
 * env 'prod' : messages d'erreur génériques uniquement.
 */
return [
    'name'     => 'CRM Auto-École',
    'env'      => 'dev',
    'debug'    => true,
    'timezone' => 'Europe/Paris',

    /* Laisser vide pour auto-détection (sous-répertoire WAMP). */
    'url'      => '',

    'session' => [
        'name'     => 'crmae_session',
        'lifetime' => 7200,   /* expiration sur inactivité (secondes) */
        'secure'   => false,  /* passer à true dès que l'app est servie en HTTPS */
        'httponly' => true,
        'samesite' => 'Lax',
    ],

    'security' => [
        'max_login_attempts' => 5,   /* échecs avant verrou */
        'lockout_minutes'    => 15,  /* durée du verrou */
    ],
];