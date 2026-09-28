<?php
// fichier : modules/vehicules/routes.php — CRM Auto-École, Jalon 3
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use Modules\Vehicules\Controllers\VehiculesController;

/** @var \App\Core\Router $router */

 $router->get('/vehicules',                 [VehiculesController::class, 'index'],  [AuthMiddleware::class]);
 $router->get('/vehicules/creer',           [VehiculesController::class, 'create'], [AuthMiddleware::class]);
 $router->post('/vehicules',                [VehiculesController::class, 'store'],  [AuthMiddleware::class]);
 $router->get('/vehicules/{id}/modifier',   [VehiculesController::class, 'edit'],   [AuthMiddleware::class]);
 $router->post('/vehicules/{id}',           [VehiculesController::class, 'update'], [AuthMiddleware::class]);
 $router->post('/vehicules/{id}/supprimer', [VehiculesController::class, 'delete'], [AuthMiddleware::class]);