<?php
// fichier : modules/administration/routes.php — CRM Auto-École, v0.16
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use Modules\Administration\Controllers\AdminController;
use Modules\Administration\Controllers\AgencesController;
use Modules\Administration\Controllers\DroitsController;
use Modules\Administration\Controllers\LogsController;
use Modules\Administration\Controllers\ParametresController;
use Modules\Administration\Controllers\UtilisateursController;

/** @var \App\Core\Router $router */

 $router->get('/administration', [AdminController::class, 'index'], [AuthMiddleware::class]);

/* Utilisateurs */
 $router->get('/administration/utilisateurs',                      [UtilisateursController::class, 'index'],         [AuthMiddleware::class]);
 $router->get('/administration/utilisateurs/creer',                [UtilisateursController::class, 'create'],        [AuthMiddleware::class]);
 $router->post('/administration/utilisateurs',                     [UtilisateursController::class, 'store'],         [AuthMiddleware::class]);
 $router->get('/administration/utilisateurs/{id}/modifier',        [UtilisateursController::class, 'edit'],          [AuthMiddleware::class]);
 $router->post('/administration/utilisateurs/{id}',                [UtilisateursController::class, 'update'],        [AuthMiddleware::class]);
 $router->post('/administration/utilisateurs/{id}/supprimer',      [UtilisateursController::class, 'delete'],        [AuthMiddleware::class]);
 $router->post('/administration/utilisateurs/{id}/mot-de-passe',   [UtilisateursController::class, 'passwordUpdate'], [AuthMiddleware::class]);
 $router->post('/administration/utilisateurs/{id}/basculer-actif', [UtilisateursController::class, 'toggleActive'],  [AuthMiddleware::class]);

/* Agences */
 $router->get('/administration/agences',                 [AgencesController::class, 'index'],  [AuthMiddleware::class]);
 $router->get('/administration/agences/creer',           [AgencesController::class, 'create'], [AuthMiddleware::class]);
 $router->post('/administration/agences',                [AgencesController::class, 'store'],  [AuthMiddleware::class]);
 $router->get('/administration/agences/{id}/modifier',   [AgencesController::class, 'edit'],   [AuthMiddleware::class]);
 $router->post('/administration/agences/{id}',           [AgencesController::class, 'update'], [AuthMiddleware::class]);
 $router->post('/administration/agences/{id}/supprimer', [AgencesController::class, 'delete'], [AuthMiddleware::class]);

/* Rôles et droits */
 $router->get('/administration/droits',         [DroitsController::class, 'index'],     [AuthMiddleware::class]);
 $router->post('/administration/droits',        [DroitsController::class, 'save'],      [AuthMiddleware::class]);
 $router->post('/administration/droits/role',   [DroitsController::class, 'storeRole'], [AuthMiddleware::class]);

/* Paramètres */
 $router->get('/administration/parametres',  [ParametresController::class, 'index'], [AuthMiddleware::class]);
 $router->post('/administration/parametres', [ParametresController::class, 'save'],  [AuthMiddleware::class]);

/* Journaux */
 $router->get('/administration/journaux', [LogsController::class, 'index'], [AuthMiddleware::class]);