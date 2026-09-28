<?php
// fichier : modules/prospects/routes.php — CRM Auto-École, Jalon 5
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use Modules\Prospects\Controllers\ProspectsController;

/** @var \App\Core\Router $router */

 $router->get('/prospects',                  [ProspectsController::class, 'index'],    [AuthMiddleware::class]);
 $router->get('/prospects/creer',            [ProspectsController::class, 'create'],   [AuthMiddleware::class]);
 $router->post('/prospects',                 [ProspectsController::class, 'store'],    [AuthMiddleware::class]);
 $router->get('/prospects/{id}',             [ProspectsController::class, 'fiche'],    [AuthMiddleware::class]);
 $router->get('/prospects/{id}/modifier',    [ProspectsController::class, 'edit'],     [AuthMiddleware::class]);
 $router->post('/prospects/{id}',            [ProspectsController::class, 'update'],   [AuthMiddleware::class]);
 $router->post('/prospects/{id}/traiter',    [ProspectsController::class, 'traiter'],  [AuthMiddleware::class]);
 $router->post('/prospects/{id}/archiver',   [ProspectsController::class, 'archiver'], [AuthMiddleware::class]);
 $router->post('/prospects/{id}/convertir',  [ProspectsController::class, 'convertir'], [AuthMiddleware::class]);