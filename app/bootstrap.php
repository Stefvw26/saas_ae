<?php
// fichier : app/bootstrap.php — CRM Auto-École (bootstrap v0.2.3)
declare(strict_types=1);

/**
 * Amorçage : constantes, autoloader PSR-4 double préfixe, configuration,
 * fuseau horaire, helpers, stockage, gestion des erreurs PHP.
 *
 * v0.2.3 : repli CamelCase -> kebab-case pour les dossiers de modules
 * (Modules\TypesPermis => /modules/types-permis/). Toute modification de ce
 * fichier DOIT incrémenter AE_BOOTSTRAP_VERSION et aligner public/index.php
 * + public/diagnostic.php (règle permanente).
 *
 * L'autoloader :
 *   App\...              => /app/...
 *   Modules\<Module>\... => /modules/<module>/...
 * Résolution Modules : chemin exact -> segment minuscules -> segment
 * kebab-case -> balayage insensible à la casse. Après chaque require,
 * VÉRIFICATION que la classe est réellement déclarée.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

define('APP_PATH',     BASE_PATH . '/app');
define('CONFIG_PATH',  BASE_PATH . '/config');
define('VIEW_PATH',    BASE_PATH . '/views');
define('MODULES_PATH', BASE_PATH . '/modules');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('APP_VERSION',  '0.2.0-jalon2');
define('AE_BOOTSTRAP_VERSION', '0.2.3');

spl_autoload_register(static function (string $class): void {

    /* Charge le fichier puis vérifie que la classe y est réellement déclarée. */
    $chargerEtVerifier = static function (string $fichier) use ($class): void {
        require $fichier;
        if (!class_exists($class, false) && !interface_exists($class, false) && !trait_exists($class, false)) {
            throw new RuntimeException(
                'Le fichier a été chargé mais ne déclare pas « ' . $class . ' » : ' . $fichier
                . ' — contenu probablement tronqué ou corrompu. Recopiez le fichier complet.'
            );
        }
    };

    /* Préfixes PSR-4 — la longueur est calculée dynamiquement. */
    $prefixes = ['App\\' => APP_PATH, 'Modules\\' => MODULES_PATH];

    foreach ($prefixes as $prefix => $base) {
        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            continue;
        }

        $relative  = str_replace('\\', '/', substr($class, strlen($prefix)));
        $candidats = [$base . '/' . $relative . '.php'];

        /* Modules : replis sur la casse du dossier de module. */
        if ($base === MODULES_PATH) {
            $slash = strpos($relative, '/');
            if ($slash !== false) {
                $segment = substr($relative, 0, $slash);

                /* Dossier de module en minuscules */
                $candidats[] = $base . '/' . strtolower($segment) . substr($relative, $slash) . '.php';

                /* Dossier de module en kebab-case (TypesPermis -> types-permis) */
                $kebab = strtolower((string)preg_replace('#(?<!^)[A-Z]#', '-$0', $segment));
                $candidats[] = $base . '/' . $kebab . substr($relative, $slash) . '.php';

                /* Balayage insensible à la casse de /modules */
                foreach ((array)@scandir($base) as $entree) {
                    if ($entree === '.' || $entree === '..' || !is_dir($base . '/' . $entree)) {
                        continue;
                    }
                    if (strcasecmp($entree, $segment) === 0 || strcasecmp($entree, $kebab) === 0) {
                        $candidats[] = $base . '/' . $entree . substr($relative, $slash) . '.php';
                        break;
                    }
                }
            }
        }

        $candidats = array_values(array_unique($candidats));
        foreach ($candidats as $candidat) {
            if (is_file($candidat)) {
                $chargerEtVerifier($candidat);
                return;
            }
        }

        throw new RuntimeException(
            'Classe introuvable : ' . $class
            . ' — chemins testés : ' . implode(' ; ', $candidats)
            . ' — exécutez public/diagnostic.php.'
        );
    }
});

/* Helpers globaux : e(), url(), asset(), csrf_field(), can(), param(), abort(), … */
require APP_PATH . '/Helpers/functions.php';

/* Configuration (/config/*.php) */
\App\Core\Config::load(CONFIG_PATH);

/* Fuseau horaire applicatif */
date_default_timezone_set((string)\App\Core\Config::get('app.timezone', 'Europe/Paris'));

/* Répertoires de stockage */
foreach ([STORAGE_PATH . '/logs', STORAGE_PATH . '/cache', STORAGE_PATH . '/uploads'] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
}

/* Erreurs : journalisées, jamais affichées directement (Handler gère le debug) */
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . '/logs/php-errors.log');

set_exception_handler([\App\Core\Handler::class, 'exception']);
set_error_handler([\App\Core\Handler::class, 'error']);
register_shutdown_function([\App\Core\Handler::class, 'shutdown']);