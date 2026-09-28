<?php
// fichier : public/diagnostic.php — CRM Auto-École (diagnostic v2.9)
declare(strict_types=1);

/**
 * Outil de diagnostic AUTONOME — v2.9.
 * v2.9 : coquille « App\core\App » corrigée (faux positif §6 v2.8) ;
 * inventaire/classes/sentinelles étendus à la v0.25 (migration 011,
 * recherche globale, modale fournisseur, matrice repliable).
 * Sections : bootstrap exécuté, autoload réel, helpers, fichiers,
 * complétude anti-troncature, journal d'erreurs, lint php -l,
 * test de rendu réel, base de données.
 *
 * Navigation : http://localhost/saas_ae/public/diagnostic.php (debug only)
 */

define('BASE_PATH', dirname(__DIR__));
define('BOOTSTRAP_VERSION_ATTENDUE', '0.2.3');

/* ---------- Périmètre : uniquement en mode debug ---------- */
 $appConfig = is_file(BASE_PATH . '/config/app.php') ? include BASE_PATH . '/config/app.php' : [];
if (!is_array($appConfig)) {
    $appConfig = [];
}
if (!(bool)($appConfig['debug'] ?? true)) {
    http_response_code(403);
    exit('Diagnostic refusé : activez le mode debug (config/app.php).');
}

/* ---------- Inventaire complet attendu ---------- */
 $FICHIERS = [
    'Noyau (app/Core)' => [
        'app/bootstrap.php',
        'app/Core/App.php', 'app/Core/Config.php', 'app/Core/Request.php',
        'app/Core/Response.php', 'app/Core/Database.php', 'app/Core/Session.php',
        'app/Core/View.php', 'app/Core/Csrf.php', 'app/Core/Logger.php',
        'app/Core/Router.php', 'app/Core/Handler.php', 'app/Core/Validate.php',
    ],
    'Contrôleurs (app)' => [
        'app/Controllers/Controller.php', 'app/Controllers/AuthController.php',
        'app/Controllers/DashboardController.php', 'app/Controllers/AccountController.php',
        'app/Controllers/AgenceController.php', 'app/Controllers/FichiersController.php',
        'app/Controllers/ReferentielController.php', 'app/Controllers/CommentairesController.php',
        'app/Controllers/RechercheController.php',
    ],
    'Données & services (app)' => [
        'app/Repositories/UserRepository.php', 'app/Repositories/LoginAttemptRepository.php',
        'app/Repositories/ReferentielRepository.php', 'app/Repositories/CommentairesRepository.php',
        'app/Services/AuthService.php', 'app/Services/SystemService.php',
        'app/Services/Gate.php', 'app/Services/LogService.php',
        'app/Services/PolitiqueMotDePasse.php', 'app/Services/RechercheService.php',
    ],
    'Middleware & helpers (app)' => [
        'app/Middleware/MiddlewareInterface.php', 'app/Middleware/CsrfMiddleware.php',
        'app/Middleware/AuthMiddleware.php', 'app/Middleware/GuestMiddleware.php',
        'app/Helpers/functions.php',
    ],
    'Configuration & routes' => [
        'config/app.php', 'config/database.php', 'routes/web.php',
        'database/migrations/001_socle_initial.sql',
        'database/migrations/002_jalon2_administration.sql',
        'database/migrations/003_domaines_et_utilisateurs.sql',
        'database/migrations/004_couleur_domaines_et_droits_perso.sql',
        'database/migrations/005_roles_perso_et_nettoyage.sql',
        'database/migrations/006_referentiels_j3.sql',
        'database/migrations/007_j3_vague2.sql',
        'database/migrations/008_places_types_adresses.sql',
        'database/migrations/009_icons_fournisseur.sql',
        'database/migrations/010_admin_places_commentaires.sql',
        'database/migrations/011_prestations_sejour_type.sql',
        '.htaccess', 'storage/.htaccess', 'modules/README.md',
    ],
    'Public' => [
        'public/index.php', 'public/.htaccess', 'public/migrate.php',
        'public/diagnostic.php',
        'public/assets/css/app.css', 'public/assets/js/app.js',
        'public/assets/images/boite-manu.png', 'public/assets/images/boite-auto.png',
        'public/assets/images/gear_manu.png', 'public/assets/images/gear_auto.png',
        'public/assets/images/partenaire-auto-ecole.png', 'public/assets/images/partenaire-hotel.png',
    ],
    'Vues (views)' => [
        'views/layouts/app.php', 'views/layouts/auth.php', 'views/layouts/error.php',
        'views/partials/head.php', 'views/partials/sidebar.php', 'views/partials/topbar.php',
        'views/partials/footer.php', 'views/partials/flash.php', 'views/partials/pagination.php',
        'views/partials/commentaires.php',
        'views/auth/login.php', 'views/dashboard/index.php', 'views/account/password.php',
        'views/errors/generic.php', 'views/errors/500.php', 'views/errors/500_debug.php',
        'views/referentiel/index.php', 'views/referentiel/form.php',
        'views/recherche/index.php',
    ],
    'Module administration' => [
        'modules/administration/manifest.php', 'modules/administration/routes.php',
        'modules/administration/Controllers/AdminController.php',
        'modules/administration/Controllers/UtilisateursController.php',
        'modules/administration/Controllers/AgencesController.php',
        'modules/administration/Controllers/DroitsController.php',
        'modules/administration/Controllers/ParametresController.php',
        'modules/administration/Controllers/LogsController.php',
        'modules/administration/Repositories/UtilisateursRepository.php',
        'modules/administration/Repositories/AgencesRepository.php',
        'modules/administration/Repositories/DroitsRepository.php',
        'modules/administration/Repositories/LogsRepository.php',
        'modules/administration/Services/ParametreService.php',
        'modules/administration/views/admin/index.php',
        'modules/administration/views/utilisateurs/index.php',
        'modules/administration/views/utilisateurs/form.php',
        'modules/administration/views/agences/index.php',
        'modules/administration/views/agences/form.php',
        'modules/administration/views/droits/index.php',
        'modules/administration/views/parametres/index.php',
        'modules/administration/views/logs/index.php',
    ],
    'Modules référentiels' => [
        'modules/domaines/manifest.php', 'modules/domaines/routes.php',
        'modules/domaines/Controllers/DomainesController.php',
        'modules/domaines/Repositories/DomainesRepository.php',
        'modules/domaines/views/index.php', 'modules/domaines/views/form.php',
        'modules/provenances/manifest.php', 'modules/provenances/routes.php',
        'modules/provenances/Controllers/ProvenancesController.php',
        'modules/provenances/Repositories/ProvenancesRepository.php',
        'modules/types-permis/manifest.php', 'modules/types-permis/routes.php',
        'modules/types-permis/Controllers/TypesPermisController.php',
        'modules/types-permis/Repositories/TypesPermisRepository.php',
        'modules/types-prestations/manifest.php', 'modules/types-prestations/routes.php',
        'modules/types-prestations/Controllers/TypesPrestationsController.php',
        'modules/types-prestations/Repositories/TypesPrestationsRepository.php',
        'modules/phrases-ouverture/manifest.php', 'modules/phrases-ouverture/routes.php',
        'modules/phrases-ouverture/Controllers/PhrasesOuvertureController.php',
        'modules/phrases-ouverture/Repositories/PhrasesOuvertureRepository.php',
        'modules/vehicules/manifest.php', 'modules/vehicules/routes.php',
        'modules/vehicules/Controllers/VehiculesController.php',
        'modules/vehicules/Repositories/VehiculesRepository.php',
        'modules/centres/manifest.php', 'modules/centres/routes.php',
        'modules/centres/Controllers/CentresController.php',
        'modules/centres/Repositories/CentresRepository.php',
        'modules/partenaires/manifest.php', 'modules/partenaires/routes.php',
        'modules/partenaires/Controllers/PartenairesController.php',
        'modules/partenaires/Repositories/PartenairesRepository.php',
        'modules/partenaires/Repositories/PartenairesTypesRepository.php',
        'modules/prestations/manifest.php', 'modules/prestations/routes.php',
        'modules/prestations/Controllers/PrestationsController.php',
        'modules/prestations/Repositories/PrestationsRepository.php',
    ],
];

/* [chemin relatif, classe complète attendue, type class|interface] */
 $CLASSES = [
    ['app/Core/App.php',                                   'App\\Core\\App',                                   'class'],
    ['app/Core/Config.php',                                'App\\Core\\Config',                                'class'],
    ['app/Core/Request.php',                               'App\\Core\\Request',                               'class'],
    ['app/Core/Response.php',                              'App\\Core\\Response',                              'class'],
    ['app/Core/Database.php',                              'App\\Core\\Database',                              'class'],
    ['app/Core/Session.php',                               'App\\Core\\Session',                               'class'],
    ['app/Core/View.php',                                  'App\\Core\\View',                                  'class'],
    ['app/Core/Csrf.php',                                  'App\\Core\\Csrf',                                  'class'],
    ['app/Core/Logger.php',                                'App\\Core\\Logger',                                'class'],
    ['app/Core/Router.php',                                'App\\Core\\Router',                                'class'],
    ['app/Core/Handler.php',                               'App\\Core\\Handler',                               'class'],
    ['app/Core/Validate.php',                              'App\\Core\\Validate',                              'class'],
    ['app/Controllers/Controller.php',                     'App\\Controllers\\Controller',                     'class'],
    ['app/Controllers/AuthController.php',                 'App\\Controllers\\AuthController',                 'class'],
    ['app/Controllers/DashboardController.php',           'App\\Controllers\\DashboardController',           'class'],
    ['app/Controllers/AccountController.php',             'App\\Controllers\\AccountController',             'class'],
    ['app/Controllers/AgenceController.php',              'App\\Controllers\\AgenceController',              'class'],
    ['app/Controllers/FichiersController.php',            'App\\Controllers\\FichiersController',            'class'],
    ['app/Controllers/ReferentielController.php',         'App\\Controllers\\ReferentielController',         'class'],
    ['app/Controllers/CommentairesController.php',        'App\\Controllers\\CommentairesController',        'class'],
    ['app/Controllers/RechercheController.php',           'App\\Controllers\\RechercheController',           'class'],
    ['app/Repositories/UserRepository.php',               'App\\Repositories\\UserRepository',               'class'],
    ['app/Repositories/LoginAttemptRepository.php',       'App\\Repositories\\LoginAttemptRepository',       'class'],
    ['app/Repositories/ReferentielRepository.php',        'App\\Repositories\\ReferentielRepository',        'class'],
    ['app/Repositories/CommentairesRepository.php',       'App\\Repositories\\CommentairesRepository',       'class'],
    ['app/Services/AuthService.php',                      'App\\Services\\AuthService',                      'class'],
    ['app/Services/SystemService.php',                    'App\\Services\\SystemService',                    'class'],
    ['app/Services/Gate.php',                              'App\\Services\\Gate',                              'class'],
    ['app/Services/LogService.php',                       'App\\Services\\LogService',                       'class'],
    ['app/Services/PolitiqueMotDePasse.php',              'App\\Services\\PolitiqueMotDePasse',              'class'],
    ['app/Services/RechercheService.php',                 'App\\Services\\RechercheService',                 'class'],
    ['app/Middleware/MiddlewareInterface.php',            'App\\Middleware\\MiddlewareInterface',            'interface'],
    ['app/Middleware/CsrfMiddleware.php',                 'App\\Middleware\\CsrfMiddleware',                 'class'],
    ['app/Middleware/AuthMiddleware.php',                 'App\\Middleware\\AuthMiddleware',                 'class'],
    ['app/Middleware/GuestMiddleware.php',                'App\\Middleware\\GuestMiddleware',                'class'],
    ['modules/administration/Controllers/AdminController.php',        'Modules\\Administration\\Controllers\\AdminController',        'class'],
    ['modules/administration/Controllers/UtilisateursController.php', 'Modules\\Administration\\Controllers\\UtilisateursController', 'class'],
    ['modules/administration/Controllers/AgencesController.php',      'Modules\\Administration\\Controllers\\AgencesController',      'class'],
    ['modules/administration/Controllers/DroitsController.php',       'Modules\\Administration\\Controllers\\DroitsController',       'class'],
    ['modules/administration/Controllers/ParametresController.php',   'Modules\\Administration\\Controllers\\ParametresController',   'class'],
    ['modules/administration/Controllers/LogsController.php',         'Modules\\Administration\\Controllers\\LogsController',         'class'],
    ['modules/administration/Repositories/UtilisateursRepository.php', 'Modules\\Administration\\Repositories\\UtilisateursRepository', 'class'],
    ['modules/administration/Repositories/AgencesRepository.php',     'Modules\\Administration\\Repositories\\AgencesRepository',     'class'],
    ['modules/administration/Repositories/DroitsRepository.php',      'Modules\\Administration\\Repositories\\DroitsRepository',      'class'],
    ['modules/administration/Repositories/LogsRepository.php',        'Modules\\Administration\\Repositories\\LogsRepository',        'class'],
    ['modules/administration/Services/ParametreService.php',          'Modules\\Administration\\Services\\ParametreService',          'class'],
    ['modules/domaines/Controllers/DomainesController.php',           'Modules\\Domaines\\Controllers\\DomainesController',           'class'],
    ['modules/domaines/Repositories/DomainesRepository.php',          'Modules\\Domaines\\Repositories\\DomainesRepository',          'class'],
    ['modules/provenances/Controllers/ProvenancesController.php',     'Modules\\Provenances\\Controllers\\ProvenancesController',     'class'],
    ['modules/provenances/Repositories/ProvenancesRepository.php',    'Modules\\Provenances\\Repositories\\ProvenancesRepository',    'class'],
    ['modules/types-permis/Controllers/TypesPermisController.php',    'Modules\\TypesPermis\\Controllers\\TypesPermisController',    'class'],
    ['modules/types-permis/Repositories/TypesPermisRepository.php',   'Modules\\TypesPermis\\Repositories\\TypesPermisRepository',   'class'],
    ['modules/types-prestations/Controllers/TypesPrestationsController.php',  'Modules\\TypesPrestations\\Controllers\\TypesPrestationsController',  'class'],
    ['modules/types-prestations/Repositories/TypesPrestationsRepository.php', 'Modules\\TypesPrestations\\Repositories\\TypesPrestationsRepository', 'class'],
    ['modules/phrases-ouverture/Controllers/PhrasesOuvertureController.php',  'Modules\\PhrasesOuverture\\Controllers\\PhrasesOuvertureController',  'class'],
    ['modules/phrases-ouverture/Repositories/PhrasesOuvertureRepository.php', 'Modules\\PhrasesOuverture\\Repositories\\PhrasesOuvertureRepository', 'class'],
    ['modules/vehicules/Controllers/VehiculesController.php',         'Modules\\Vehicules\\Controllers\\VehiculesController',         'class'],
    ['modules/vehicules/Repositories/VehiculesRepository.php',        'Modules\\Vehicules\\Repositories\\VehiculesRepository',        'class'],
    ['modules/centres/Controllers/CentresController.php',             'Modules\\Centres\\Controllers\\CentresController',             'class'],
    ['modules/centres/Repositories/CentresRepository.php',            'Modules\\Centres\\Repositories\\CentresRepository',            'class'],
    ['modules/partenaires/Controllers/PartenairesController.php',     'Modules\\Partenaires\\Controllers\\PartenairesController',     'class'],
    ['modules/partenaires/Repositories/PartenairesRepository.php',    'Modules\\Partenaires\\Repositories\\PartenairesRepository',    'class'],
    ['modules/partenaires/Repositories/PartenairesTypesRepository.php', 'Modules\\Partenaires\\Repositories\\PartenairesTypesRepository', 'class'],
    ['modules/prestations/Controllers/PrestationsController.php',     'Modules\\Prestations\\Controllers\\PrestationsController',     'class'],
    ['modules/prestations/Repositories/PrestationsRepository.php',    'Modules\\Prestations\\Repositories\\PrestationsRepository',    'class'],
];

/* Helpers globaux attendus */
 $HELPERS = [
    'e', 'url', 'asset', 'icone_url', 'csrf_field', 'current_path',
    'can', 'tous_droit', 'agence_active', 'agences_accessibles', 'agence_scope',
    'param', 'old_values', 'old', 'form_errors', 'form_error',
    'abort', 'user_initials', 'role_label',
];

/* ---------- Marqueurs de FIN de fichier (fenêtre 4096 octets) ---------- */
 $SENTINELLES = [
    'app/bootstrap.php'                                   => 'register_shutdown_function',
    'public/index.php'                                    => 'App())->run();',
    'public/migrate.php'                                  => '</html>',
    'public/assets/js/app.js'                             => 'data-images',
    'public/assets/css/app.css'                           => 'etoiles',
    'views/layouts/app.php'                               => 'AE-EOF',
    'views/partials/head.php'                             => 'AE-EOF',
    'views/partials/sidebar.php'                          => 'AE-EOF',
    'views/partials/topbar.php'                           => 'AE-EOF',
    'views/partials/flash.php'                            => 'AE-EOF',
    'views/partials/footer.php'                           => 'AE-EOF',
    'views/partials/pagination.php'                       => '</nav>',
    'views/partials/commentaires.php'                     => 'droit',
    'views/auth/login.php'                                => '</form>',
    'views/dashboard/index.php'                           => 'AE-EOF',
    'views/account/password.php'                          => 'Modifier le mot de passe',
    'views/referentiel/index.php'                         => 'partials/pagination',
    'views/referentiel/form.php'                          => 'irréversible',
    'views/recherche/index.php'                           => 'AE-EOF',
    'modules/administration/views/admin/index.php'        => 'AE-EOF',
    'modules/administration/views/utilisateurs/index.php' => 'AE-EOF',
    'modules/administration/views/utilisateurs/form.php'  => 'AE-EOF',
    'modules/administration/views/agences/index.php'      => 'partials/pagination',
    'modules/administration/views/agences/form.php'       => 'Supprimer (archiver) cette agence',
    'modules/administration/views/droits/index.php'       => 'AE-EOF',
    'modules/administration/views/parametres/index.php'   => 'Enregistrer les paramètres',
    'modules/administration/views/logs/index.php'         => 'partials/pagination',
    'modules/domaines/views/index.php'                    => 'partials/pagination',
    'modules/domaines/views/form.php'                     => 'Supprimer (archiver) ce domaine',
];

/* ---------- 1. Présence des fichiers ---------- */
 $manquants = [];
foreach ($FICHIERS as $groupe => $chemins) {
    foreach ($chemins as $chemin) {
        if (!is_file(BASE_PATH . '/' . $chemin)) {
            $manquants[] = $chemin;
        }
    }
}

/* ---------- 2. Complétude (fenêtre 4096) ---------- */
 $tronques = [];
foreach ($SENTINELLES as $chemin => $marqueur) {
    $fichier = BASE_PATH . '/' . $chemin;
    if (!is_file($fichier)) {
        continue;
    }
    $taille = (int)filesize($fichier);
    $fenetre = $taille > 4096 ? 4096 : $taille;
    if ($fenetre === 0) {
        $tronques[] = $chemin . ' (fichier vide)';
        continue;
    }
    $fin = (string)@file_get_contents($fichier, false, null, $taille - $fenetre, $fenetre);
    if (strpos($fin, $marqueur) === false) {
        $tronques[] = $chemin . ' (terminaison attendue absente — copie tronquée ?)';
    }
}

/* ---------- 3. Cohérence du contenu ---------- */
 $classesKo = [];
foreach ($CLASSES as [$chemin, $fqcn, $type]) {
    $fichier = BASE_PATH . '/' . $chemin;
    if (!is_file($fichier)) {
        continue;
    }
    $contenu = (string)@file_get_contents($fichier);

    $pos = (int)strrpos($fqcn, '\\');
    $nsAttendu = substr($fqcn, 0, $pos);
    $nomCourt  = substr($fqcn, $pos + 1);

    $nsOk = preg_match('#^namespace\s+' . preg_quote($nsAttendu, '#') . '\s*;#m', $contenu) === 1;
    $classeOk = preg_match(
        '#\b(?:final\s+|abstract\s+)?' . $type . '\s+' . preg_quote($nomCourt, '#') . '\b#',
        $contenu
    ) === 1;

    if (!$nsOk || !$classeOk) {
        $classesKo[] = $chemin . ($nsOk ? '' : ' (namespace déclaré ≠ ' . $nsAttendu . ')')
            . ($classeOk ? '' : ' (' . $type . ' ' . $nomCourt . ' non déclarée)');
    }
}

/* ---------- 4. Bootstrap + TEST RÉEL D'AUTOLOAD ---------- */
 $bootstrapErreur = null;
 $bootstrapVersion = null;
try {
    require BASE_PATH . '/app/bootstrap.php';
    $bootstrapVersion = defined('AE_BOOTSTRAP_VERSION') ? (string)AE_BOOTSTRAP_VERSION : null;
} catch (Throwable $e) {
    $bootstrapErreur = $e->getMessage();
}

 $chargementKo = [];
if ($bootstrapErreur === null) {
    foreach ($CLASSES as [$chemin, $fqcn, $type]) {
        if (!is_file(BASE_PATH . '/' . $chemin)) {
            continue;
        }
        try {
            $charge = $type === 'interface'
                ? interface_exists($fqcn, true)
                : class_exists($fqcn, true);
            if (!$charge) {
                $chargementKo[] = $fqcn . ' — l\'autoloader exécuté n\'a pas déclaré cette classe. Fichier : ' . $chemin;
            }
        } catch (Throwable $e) {
            $chargementKo[] = $fqcn . ' — ' . $e->getMessage();
        }
    }
}

/* ---------- 5. Helpers ---------- */
 $helpersKo = [];
if ($bootstrapErreur === null) {
    foreach ($HELPERS as $helper) {
        if (!function_exists($helper)) {
            $helpersKo[] = $helper . '()';
        }
    }
}

/* ---------- 6. OPcache ---------- */
 $opcacheActive = false;
if (function_exists('opcache_get_status')) {
    $statut = @opcache_get_status(false);
    if (is_array($statut)) {
        $opcacheActive = (bool)($statut['opcache_enabled'] ?? false);
    }
}

/* ---------- 7. Base de données ---------- */
 $tablesAttendues = ['tenants', 'agences', 'utilisateurs', 'login_attempts', 'migrations',
                    'roles', 'permissions', 'role_permissions', 'parametres', 'logs',
                    'domaines', 'provenances', 'types_permis', 'types_prestations', 'phrase_ouverture',
                    'vehicules', 'centres', 'partenaires', 'partenaires_type', 'prestations', 'commentaires'];
 $dbOk = false;
 $dbErreur = '';
 $tablesPresentes = [];
 $migrationsAppliquees = [];
 $dbConfig = is_file(BASE_PATH . '/config/database.php') ? include BASE_PATH . '/config/database.php' : null;

if (is_array($dbConfig)) {
    try {
        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $dbConfig['host'] ?? '127.0.0.1', $dbConfig['port'] ?? 3306, $dbConfig['database'] ?? ''),
            (string)($dbConfig['username'] ?? ''),
            (string)($dbConfig['password'] ?? ''),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $dbOk = true;
        foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $table) {
            $tablesPresentes[] = (string)$table;
        }
        if (in_array('migrations', $tablesPresentes, true)) {
            foreach ($pdo->query('SELECT fichier FROM migrations ORDER BY fichier')->fetchAll(PDO::FETCH_COLUMN) as $f) {
                $migrationsAppliquees[] = (string)$f;
            }
        }
    } catch (Throwable $e) {
        $dbErreur = $e->getMessage();
    }
}
 $tablesManquantes = array_values(array_diff($tablesAttendues, $tablesPresentes));

/* ---------- 8. Stockage & PHP ---------- */
 $storageOk = is_dir(BASE_PATH . '/storage/logs') && is_writable(BASE_PATH . '/storage/logs');
 $phpOk = PHP_VERSION_ID >= 80000;
 $extPdo = extension_loaded('pdo_mysql');
 $extMb  = extension_loaded('mbstring');

/* ---------- 9. Queue du journal d'erreurs PHP ---------- */
 $lireQueue = static function (string $fichier, int $n = 40): array {
    if (!is_file($fichier)) {
        return [];
    }
    $taille = (int)@filesize($fichier);
    $depart = $taille > 262144 ? $taille - 262144 : 0;
    $contenu = (string)@file_get_contents($fichier, false, null, $depart);
    if (trim($contenu) === '') {
        return [];
    }
    $lignes = explode("\n", trim($contenu));
    return array_slice($lignes, -$n);
};
 $journalPhp = $lireQueue(BASE_PATH . '/storage/logs/php-errors.log', 40);
 $journalApp = $lireQueue(BASE_PATH . '/storage/logs/app-' . date('Y-m-d') . '.log', 15);

/* ---------- 10. Lint « php -l » de TOUS les fichiers PHP ---------- */
 $phpExe = null;
foreach ([
    'C:/wamp64/bin/php/php*/php.exe',
    'C:/wamp/bin/php/php*/php.exe',
    'D:/wamp64/bin/php/php*/php.exe',
    'C:/wamp64/apps/php/php*/php.exe',
] as $motif) {
    $trouves = glob($motif) ?: [];
    if ($trouves !== []) {
        sort($trouves);
        $phpExe = (string)end($trouves);
        break;
    }
}

 $lintDisponible = ($phpExe !== null && function_exists('shell_exec'));
 $lintKo = [];
 $lintTotal = 0;
if ($lintDisponible) {
    $cibles = [];
    foreach (['app', 'modules', 'views', 'config', 'routes'] as $rep) {
        $racine = BASE_PATH . '/' . $rep;
        if (!is_dir($racine)) {
            continue;
        }
        $ite = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($racine, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($ite as $f) {
            if ($f->isFile() && strtolower($f->getExtension()) === 'php') {
                $cibles[] = (string)$f->getPathname();
            }
        }
    }
    foreach (['public/index.php', 'public/migrate.php'] as $p) {
        if (is_file(BASE_PATH . '/' . $p)) {
            $cibles[] = BASE_PATH . '/' . $p;
        }
    }
    sort($cibles);
    $lintTotal = count($cibles);
    foreach ($cibles as $f) {
        $cmd = '"' . $phpExe . '" -l ' . escapeshellarg($f) . ' 2>&1';
        $sortie = @shell_exec($cmd);
        if ($sortie === null || trim((string)$sortie) === '') {
            $lintDisponible = false;
            break;
        }
        if (strpos((string)$sortie, 'No syntax errors') === false) {
            $resume = trim((string)preg_replace('#\s+#', ' ', (string)$sortie));
            $lintKo[] = str_replace('\\', '/', str_replace(BASE_PATH . '\\', '', str_replace(BASE_PATH . '/', '', $f))) . ' — ' . $resume;
        }
    }
}

/* ---------- 11. Test de rendu réel (chaîne complète) ---------- */
 $renduOk = null;
 $renduMessage = '';
 $renduLongueur = 0;
if ($bootstrapErreur === null) {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    ob_start();
    try {
        \App\Core\View::render('dashboard/index', [
            'title'         => 'Test de rendu',
            'user'          => [],
            'phpVersion'    => PHP_VERSION,
            'appName'       => 'Test',
            'appVersion'    => 'test',
            'anniversaires' => [],
            'system'        => ['dbOk' => true, 'database' => 'test', 'userCount' => 0, 'migrationCount' => 0],
            'modules'       => [],
            'roadmap'       => [],
        ], 'app');
        $html = (string)ob_get_clean();
        $renduLongueur = strlen($html);
        if (strpos($html, '</html>') !== false && strpos($html, 'main-area') !== false) {
            $renduOk = true;
        } else {
            $renduOk = false;
            $renduMessage = 'Le rendu s\'interrompt avant la fin (' . $renduLongueur . ' octets produits, </html> absent) : '
                . 'un fichier de la chaîne layout/partials/vue est cassé sur le disque — voir §9 (journal) et §10 (lint).';
        }
    } catch (Throwable $e) {
        ob_end_clean();
        $renduOk = false;
        $renduMessage = get_class($e) . ' : ' . $e->getMessage() . ' — ' . $e->getFile() . ' ligne ' . $e->getLine();
    }
}

/* ---------- Verdict ---------- */
 $bootstrapObsolete = ($bootstrapErreur === null && $bootstrapVersion !== BOOTSTRAP_VERSION_ATTENDUE);
 $toutVert = $manquants === [] && $tronques === [] && $classesKo === [] && $chargementKo === [] && $helpersKo === []
    && $bootstrapErreur === null && !$bootstrapObsolete
    && $dbOk && $tablesManquantes === [] && $phpOk && $extPdo && $extMb && $storageOk
    && (!$lintDisponible || $lintKo === [])
    && $renduOk === true;
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Diagnostic — CRM Auto-École</title>
    <style>
        body{font-family:"Segoe UI",system-ui,Arial,sans-serif;background:#f1f5f9;color:#0f172a;margin:0;padding:32px 16px;font-size:14px}
        .wrap{max-width:1000px;margin:0 auto}
        h1{font-size:22px;margin:0 0 4px}h2{font-size:16px;margin:28px 0 10px}
        p.sub{color:#64748b;margin:0 0 18px}
        .card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 18px;margin-bottom:14px}
        table{width:100%;border-collapse:collapse;font-size:13px}
        td{padding:7px 10px;border-bottom:1px solid #eef2f7;vertical-align:top}
        tr:last-child td{border-bottom:0}
        .ok{color:#14532d;font-weight:700}.ko{color:#991b1b;font-weight:700}.warn{color:#854d0e;font-weight:700}
        .badge{display:inline-block;padding:2px 10px;border-radius:99px;font-size:11px;font-weight:700;margin-right:6px}
        .b-ok{background:#e7f6ec;color:#14532d}.b-ko{background:#fdeaea;color:#991b1b}
        .b-info{background:#e8effd;color:#1d4ed8}.b-warn{background:#fff6e0;color:#854d0e}
        code{background:#eef2f7;border-radius:4px;padding:1px 5px;font-size:12px}
        ul.plain{margin:6px 0 0;padding-left:18px}
        .resume{font-size:15px}
        .big{font-size:16px;padding:18px 20px}
        pre.msg{background:#0f172a;color:#fecaca;padding:12px 14px;border-radius:8px;white-space:pre-wrap;font-size:12.5px;margin:10px 0 0}
        pre.log{background:#0f172a;color:#e2e8f0;padding:12px 14px;border-radius:8px;white-space:pre-wrap;font-size:12px;margin:10px 0 0;max-height:340px;overflow-y:auto}
    </style>
</head>
<body>
<div class="wrap">
    <h1>Diagnostic — CRM Auto-École (J1-J4) · v2.9</h1>
    <p class="sub">Bootstrap exécuté · autoload réel · helpers · fichiers · complétude · journal d'erreurs · lint PHP · test de rendu · base.</p>

    <?php if ($tronques !== []): ?>
        <div class="card big" style="border-color:#f6cfcf;background:#fff5f5">
            <span class="badge b-ko"><?= count($tronques) ?> FICHIER(S) TRONQUÉ(S)</span>
            <ul class="plain">
                <?php foreach ($tronques as $probleme): ?><li><code><?= htmlspecialchars($probleme) ?></code></li><?php endforeach; ?>
            </ul>
            <p style="margin:8px 0 0">→ Recopiez chaque fichier <strong>intégralement</strong> (il doit se terminer par son marqueur <code>AE-EOF</code> quand il en possède un).</p>
        </div>
    <?php endif; ?>

    <?php if ($lintKo !== []): ?>
        <div class="card big" style="border-color:#f6cfcf;background:#fff5f5">
            <span class="badge b-ko"><?= count($lintKo) ?> ERREUR(S) DE SYNTAXE PHP (lint)</span>
            <ul class="plain">
                <?php foreach ($lintKo as $probleme): ?><li><code><?= htmlspecialchars($probleme) ?></code></li><?php endforeach; ?>
            </ul>
            <p style="margin:8px 0 0">→ Ces fichiers sont inutilisables en l'état (copies coupées) : recopiez-les complets.</p>
        </div>
    <?php endif; ?>

    <?php if ($renduOk === false): ?>
        <div class="card big" style="border-color:#f6cfcf;background:#fff5f5">
            <span class="badge b-ko">TEST DE RENDU ÉCHOUÉ — reproduction de la page blanche</span>
            <pre class="msg"><?= htmlspecialchars($renduMessage) ?></pre>
        </div>
    <?php endif; ?>

    <?php if ($bootstrapObsolete): ?>
        <div class="card big" style="border-color:#f3e3b3;background:#fffbe9">
            <span class="badge b-ko">BOOTSTRAP OBSOLÈTE</span>
            <p style="margin:10px 0 6px"><strong>Le bootstrap réellement exécuté n'est pas la version <?= htmlspecialchars(BOOTSTRAP_VERSION_ATTENDUE) ?>.</strong></p>
            <p style="margin:0">Version chargée : <code><?= $bootstrapVersion === null ? '(aucune — version antérieure)' : htmlspecialchars($bootstrapVersion) ?></code>.</p>
            <pre class="msg">1. Recopiez le fichier complet : C:\wamp64\www\saas_ae\app\bootstrap.php
2. Redémarrez TOUS les services WAMP (Restart all services).
3. Rechargez cette page : « Bootstrap exécuté » doit afficher <?= htmlspecialchars(BOOTSTRAP_VERSION_ATTENDUE) ?>.</pre>
        </div>
    <?php endif; ?>

    <?php if ($bootstrapErreur !== null): ?>
        <div class="card big" style="border-color:#f6cfcf;background:#fff5f5">
            <span class="badge b-ko">BOOTSTRAP INCHARGEABLE</span>
            <pre class="msg"><?= htmlspecialchars($bootstrapErreur) ?></pre>
        </div>
    <?php endif; ?>

    <?php if ($classesKo !== []): ?>
        <div class="card big" style="border-color:#f3e3b3;background:#fffbe9">
            <span class="badge b-warn"><?= count($classesKo) ?> CONTENU(S) INCOHÉRENT(S)</span>
            <ul class="plain">
                <?php foreach ($classesKo as $probleme): ?><li><code><?= htmlspecialchars($probleme) ?></code></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card resume">
        <?php if ($toutVert): ?>
            <span class="badge b-ok">TOUT EST EN ORDRE</span>
            Fichiers, complétude, lint (<?= $lintTotal ?> fichiers), rendu réel (<?= $renduLongueur ?> octets), bootstrap, autoload, helpers, base et environnement : OK.
        <?php else: ?>
            <span class="badge b-ko"><?= count($manquants) ?> manquant(s)</span>
            <span class="badge <?= $tronques === [] ? 'b-ok' : 'b-ko' ?>"><?= count($tronques) ?> tronqué(s)</span>
            <?php if ($lintDisponible): ?>
                <span class="badge <?= $lintKo === [] ? 'b-ok' : 'b-ko' ?>">lint <?= $lintKo === [] ? 'OK (' . $lintTotal . ')' : count($lintKo) . ' erreur(s)' ?></span>
            <?php endif; ?>
            <span class="badge <?= $renduOk === true ? 'b-ok' : 'b-ko' ?>">rendu <?= $renduOk === true ? 'OK' : ($renduOk === false ? 'ÉCHOUÉ' : 'n/a') ?></span>
            <span class="badge <?= $classesKo === [] ? 'b-ok' : 'b-ko' ?>"><?= count($classesKo) ?> contenu(s) incohérent(s)</span>
            <span class="badge <?= $chargementKo === [] ? 'b-ok' : 'b-ko' ?>"><?= count($chargementKo) ?> classe(s) non chargeable(s)</span>
            <span class="badge <?= $helpersKo === [] ? 'b-ok' : 'b-ko' ?>"><?= count($helpersKo) ?> helper(s) manquant(s)</span>
            <span class="badge <?= $bootstrapErreur === null && !$bootstrapObsolete ? 'b-ok' : 'b-ko' ?>">bootstrap <?= $bootstrapErreur !== null ? 'erreur' : ($bootstrapObsolete ? 'obsolète' : 'ok') ?></span>
            <span class="badge <?= $dbOk && $tablesManquantes === [] ? 'b-ok' : 'b-ko' ?>">base <?= $dbOk ? 'connectée' : 'inaccessible' ?></span>
            <?php if ($dbOk && $tablesManquantes !== []): ?>
                <span class="badge b-ko"><?= count($tablesManquantes) ?> table(s) manquante(s) → migrate.php</span>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <h2>1 · Bootstrap réellement exécuté</h2>
    <div class="card"><table>
        <tr><td>Version attendue</td><td><code><?= htmlspecialchars(BOOTSTRAP_VERSION_ATTENDUE) ?></code></td></tr>
        <tr>
            <td>Version exécutée</td>
            <td><?php if ($bootstrapErreur !== null): ?><span class="ko">bootstrap inchargeable</span>
                <?php elseif ($bootstrapVersion === null): ?><span class="ko">AUCUNE (fichier antérieur ou OPcache périmé)</span>
                <?php elseif ($bootstrapObsolete): ?><span class="ko"><?= htmlspecialchars($bootstrapVersion) ?> — OBSOLÈTE</span>
                <?php else: ?><span class="ok"><?= htmlspecialchars($bootstrapVersion) ?></span><?php endif; ?></td>
        </tr>
        <tr>
            <td>OPcache</td>
            <td><?= $opcacheActive
                ? '<span class="warn">ACTIF</span> — après toute recopie de fichier PHP, redémarrez TOUS les services WAMP'
                : '<span class="ok">inactif</span>' ?></td>
        </tr>
    </table></div>

    <h2>2 · Test réel d'autoload (<?= count($CLASSES) ?> classes)</h2>
    <div class="card">
        <?php if ($bootstrapErreur !== null): ?>
            <span class="badge b-ko">Test impossible : bootstrap inchargeable</span>
        <?php elseif ($chargementKo === []): ?>
            <span class="badge b-ok">Toutes les classes se chargent correctement</span>
        <?php else: ?>
            <table>
                <?php foreach ($chargementKo as $probleme): ?>
                    <tr><td><?= htmlspecialchars($probleme) ?></td><td style="width:40px;text-align:right"><span class="ko">KO</span></td></tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <h2>3 · Helpers globaux</h2>
    <div class="card">
        <?php if ($bootstrapErreur !== null): ?>
            <span class="badge b-ko">Test impossible</span>
        <?php elseif ($helpersKo === []): ?>
            <span class="badge b-ok">Les <?= count($HELPERS) ?> helpers attendus sont définis</span>
        <?php else: ?>
            <table>
                <?php foreach ($helpersKo as $helper): ?>
                    <tr><td><code><?= htmlspecialchars($helper) ?></code></td><td style="width:60px;text-align:right"><span class="ko">ABSENT</span></td></tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <h2>4 · Environnement PHP</h2>
    <div class="card"><table>
        <tr><td>Version PHP (≥ 8.0)</td><td><?= $phpOk ? '<span class="ok">OK</span>' : '<span class="ko">KO</span>' ?> — <?= htmlspecialchars(PHP_VERSION) ?></td></tr>
        <tr><td>Extension pdo_mysql</td><td><?= $extPdo ? '<span class="ok">OK</span>' : '<span class="ko">KO</span>' ?></td></tr>
        <tr><td>Extension mbstring</td><td><?= $extMb ? '<span class="ok">OK</span>' : '<span class="ko">KO</span>' ?></td></tr>
        <tr><td>storage/logs inscriptible</td><td><?= $storageOk ? '<span class="ok">OK</span>' : '<span class="ko">KO</span>' ?></td></tr>
    </table></div>

    <h2>5 · Fichiers attendus</h2>
    <?php foreach ($FICHIERS as $groupe => $chemins): ?>
        <div class="card">
            <strong><?= htmlspecialchars($groupe) ?></strong>
            <table>
                <?php foreach ($chemins as $chemin): $present = is_file(BASE_PATH . '/' . $chemin); ?>
                    <tr>
                        <td><code><?= htmlspecialchars($chemin) ?></code></td>
                        <td style="width:90px;text-align:right"><?= $present ? '<span class="ok">OK</span>' : '<span class="ko">MANQUANT</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endforeach; ?>

    <h2>6 · Cohérence du contenu (namespace + classe)</h2>
    <div class="card">
        <?php if ($classesKo === []): ?>
            <span class="badge b-ok">Toutes les classes déclarées sont cohérentes</span>
        <?php else: ?>
            <table>
                <?php foreach ($classesKo as $probleme): ?>
                    <tr><td><code><?= htmlspecialchars($probleme) ?></code></td><td style="width:40px;text-align:right"><span class="ko">KO</span></td></tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <h2>7 · Base de données</h2>
    <div class="card">
        <?php if (!is_array($dbConfig)): ?>
            <span class="badge b-ko">config/database.php illisible</span>
        <?php elseif (!$dbOk): ?>
            <span class="badge b-ko">Connexion impossible</span>
            <p><?= htmlspecialchars($dbErreur) ?></p>
        <?php else: ?>
            <span class="badge b-ok">Connectée</span>
            <table>
                <?php foreach ($tablesAttendues as $table): $present = in_array($table, $tablesPresentes, true); ?>
                    <tr>
                        <td><code><?= htmlspecialchars($table) ?></code></td>
                        <td style="width:90px;text-align:right"><?= $present ? '<span class="ok">OK</span>' : '<span class="ko">MANQUANTE</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php if ($migrationsAppliquees !== []): ?>
                <p style="margin-bottom:0">Migrations appliquées : <span class="badge b-info"><?= count($migrationsAppliquees) ?></span>
                <?php foreach ($migrationsAppliquees as $m): ?><code><?= htmlspecialchars($m) ?></code> <?php endforeach; ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <h2>8 · Complétude des fichiers (anti-troncature, fenêtre 4096)</h2>
    <div class="card">
        <?php if ($tronques === []): ?>
            <span class="badge b-ok">Tous les fichiers clés ont leur terminaison attendue</span>
        <?php else: ?>
            <table>
                <?php foreach ($tronques as $probleme): ?>
                    <tr><td><code><?= htmlspecialchars($probleme) ?></code></td><td style="width:60px;text-align:right"><span class="ko">TRONQUÉ</span></td></tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <h2>9 · Journal d'erreurs PHP — dernières lignes</h2>
    <div class="card">
        <?php if ($journalPhp === []): ?>
            <span class="badge b-ok">php-errors.log vide ou absent — aucune erreur PHP enregistrée</span>
        <?php else: ?>
            <p style="margin:0 0 4px">Les dernières lignes correspondent à l'incident en cours (fichier + ligne) :</p>
            <pre class="log"><?= htmlspecialchars(implode("\n", $journalPhp)) ?></pre>
        <?php endif; ?>
        <?php if ($journalApp !== []): ?>
            <p style="margin:14px 0 4px">Journal applicatif du jour :</p>
            <pre class="log"><?= htmlspecialchars(implode("\n", $journalApp)) ?></pre>
        <?php endif; ?>
    </div>

    <h2>10 · Lint PHP (php -l sur tous les fichiers)</h2>
    <div class="card">
        <?php if (!$lintDisponible): ?>
            <span class="badge b-warn">LINT INDISPONIBLE</span>
            <p style="margin:8px 0 0">php.exe introuvable via les chemins WAMP standards ou shell_exec désactivé. Les contrôles §8 et §11 restent actifs.</p>
        <?php elseif ($lintKo === []): ?>
            <span class="badge b-ok"><?= $lintTotal ?> fichiers vérifiés — aucune erreur de syntaxe</span>
        <?php else: ?>
            <span class="badge b-ko"><?= count($lintKo) ?> erreur(s) de syntaxe</span>
            <table>
                <?php foreach ($lintKo as $probleme): ?>
                    <tr><td><code><?= htmlspecialchars($probleme) ?></code></td><td style="width:40px;text-align:right"><span class="ko">KO</span></td></tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <h2>11 · Test de rendu réel (chaîne layout + partials + vue)</h2>
    <div class="card">
        <?php if ($renduOk === true): ?>
            <span class="badge b-ok">Rendu complet (<?= $renduLongueur ?> octets, </html> présent) — la chaîne de rendu est saine sur le disque</span>
            <p style="margin:8px 0 0">Si l'application affiche malgré tout une page blanche alors que ce test réussit : <strong>redémarrez tous les services WAMP</strong> (OPcache périmé), puis retestez.</p>
        <?php elseif ($renduOk === false): ?>
            <span class="badge b-ko">Rendu interrompu</span>
            <pre class="msg"><?= htmlspecialchars($renduMessage) ?></pre>
        <?php else: ?>
            <span class="badge b-warn">Test impossible (bootstrap inchargeable)</span>
        <?php endif; ?>
    </div>
</div>
</body>
</html>