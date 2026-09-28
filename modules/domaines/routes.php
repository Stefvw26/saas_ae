<?php
// fichier : modules/domaines/routes.php — CRM Auto-École, Jalon 3
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use Modules\Domaines\Controllers\DomainesController;

/** @var \App\Core\Router $router */

 $router->get('/domaines',                 [DomainesController::class, 'index'],  [AuthMiddleware::class]);
 $router->get('/domaines/creer',           [DomainesController::class, 'create'], [AuthMiddleware::class]);
 $router->post('/domaines',                [DomainesController::class, 'store'],  [AuthMiddleware::class]);
 $router->get('/domaines/{id}/modifier',   [DomainesController::class, 'edit'],   [AuthMiddleware::class]);
 $router->post('/domaines/{id}',           [DomainesController::class, 'update'], [AuthMiddleware::class]);
 $router->post('/domaines/{id}/supprimer', [DomainesController::class, 'delete'], [AuthMiddleware::class]);