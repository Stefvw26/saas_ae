<?php
// fichier : modules/provenances/routes.php — CRM Auto-École, Jalon 3
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use Modules\Provenances\Controllers\ProvenancesController;

/** @var \App\Core\Router $router */

 $router->get('/provenances',                 [ProvenancesController::class, 'index'],  [AuthMiddleware::class]);
 $router->get('/provenances/creer',           [ProvenancesController::class, 'create'], [AuthMiddleware::class]);
 $router->post('/provenances',                [ProvenancesController::class, 'store'],  [AuthMiddleware::class]);
 $router->get('/provenances/{id}/modifier',   [ProvenancesController::class, 'edit'],   [AuthMiddleware::class]);
 $router->post('/provenances/{id}',           [ProvenancesController::class, 'update'], [AuthMiddleware::class]);
 $router->post('/provenances/{id}/supprimer', [ProvenancesController::class, 'delete'], [AuthMiddleware::class]);