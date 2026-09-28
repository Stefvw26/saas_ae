<?php
// fichier : routes/web.php — CRM Auto-École, v0.27
declare(strict_types=1);

use App\Controllers\AccountController;
use App\Controllers\AgenceController;
use App\Controllers\AuthController;
use App\Controllers\CommentairesController;
use App\Controllers\DashboardController;
use App\Controllers\FichiersController;
use App\Controllers\NotificationsController;
use App\Controllers\RechercheController;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;

/** @var \App\Core\Router $router */

/* Accueil / tableau de bord */
 $router->get('/', [DashboardController::class, 'index'], [AuthMiddleware::class]);

/* Authentification */
 $router->get('/connexion',    [AuthController::class, 'showLogin'], [GuestMiddleware::class]);
 $router->post('/connexion',   [AuthController::class, 'login'],     [GuestMiddleware::class]);
 $router->post('/deconnexion', [AuthController::class, 'logout'],    [AuthMiddleware::class]);

/* Mon compte */
 $router->get('/mon-compte',  [AccountController::class, 'show'],           [AuthMiddleware::class]);
 $router->post('/mon-compte', [AccountController::class, 'updatePassword'], [AuthMiddleware::class]);

/* Agence active (multi-agence, CDC §13 — masquée pour les tous droits) */
 $router->post('/agence/activer', [AgenceController::class, 'switch'], [AuthMiddleware::class]);

/* Recherche globale (CDC §12/§28 — toujours disponible) */
 $router->get('/recherche', [RechercheController::class, 'index'], [AuthMiddleware::class]);

/* Notifications (J4) */
 $router->get('/notifications',            [NotificationsController::class, 'index'],       [AuthMiddleware::class]);
 $router->post('/notifications/tout-lu',   [NotificationsController::class, 'markAllRead'], [AuthMiddleware::class]);
 $router->get('/notifications/{id}/lire',  [NotificationsController::class, 'read'],        [AuthMiddleware::class]);

/* Commentaires conversationnels (CDC §33) */
 $router->post('/commentaires', [CommentairesController::class, 'store'], [AuthMiddleware::class]);

/* Fichiers téléversés (streaming sécurisé depuis storage/uploads) */
 $router->get('/fichiers/{dossier}/{fichier}', [FichiersController::class, 'stream'], [AuthMiddleware::class]);

/* Les routes des modules métier (/modules//routes.php) sont chargées automatiquement par App. */