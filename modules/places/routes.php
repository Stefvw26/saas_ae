<?php
// fichier : modules/places/routes.php — CRM Auto-École, Jalon 3
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use Modules\Places\Controllers\PlacesController;

/** @var \App\Core\Router $router */

 $router->get('/places',                 [PlacesController::class, 'index'],  [AuthMiddleware::class]);
 $router->get('/places/creer',           [PlacesController::class, 'create'], [AuthMiddleware::class]);
 $router->post('/places',                [PlacesController::class, 'store'],  [AuthMiddleware::class]);
 $router->get('/places/{id}/modifier',   [PlacesController::class, 'edit'],   [AuthMiddleware::class]);
 $router->post('/places/{id}',           [PlacesController::class, 'update'], [AuthMiddleware::class]);
 $router->post('/places/{id}/supprimer', [PlacesController::class, 'delete'], [AuthMiddleware::class]);