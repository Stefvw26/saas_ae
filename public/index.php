<?php
// fichier : public/index.php — CRM Auto-École (index v0.2.3)
declare(strict_types=1);

/**
 * CRM Auto-École — Point d'entrée unique (front controller).
 *
 * Garde-fou anti-bootstrap-périmé : si le bootstrap réellement exécuté
 * n'est pas au moins la version 0.2.3 (repli kebab-case des modules),
 * un message explicite remplace l'erreur de classe.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/bootstrap.php';

if (!defined('AE_BOOTSTRAP_VERSION') || version_compare(AE_BOOTSTRAP_VERSION, '0.2.3', '<')) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit(
        "BOOTSTRAP PAS A JOUR\n\n" .
        "Le app/bootstrap.php reellement execute est anterieur a la version 0.2.3\n" .
        "(autoloader avec repli kebab-case : Modules\\TypesPermis => modules/types-permis).\n" .
        "Symptome : erreurs du type « Class Modules\\TypesPermis\\... not found ».\n\n" .
        "1. Recopiez le fichier complet : C:\\wamp64\\www\\saas_ae\\app\\bootstrap.php\n" .
        "2. Redemarrez TOUS les services WAMP (Restart all services) pour vider OPcache.\n" .
        "3. Verifiez : http://localhost/saas_ae/public/diagnostic.php\n" .
        "   -> « Bootstrap exécuté » doit afficher : 0.2.3\n"
    );
}

(new \App\Core\App())->run();