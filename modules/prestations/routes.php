<?php
// fichier : modules/prestations/routes.php — CRM Auto-École, Jalon 3
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use Modules\Prestations\Controllers\PrestationsController;

/** @var \App\Core\Router $router */

 $router->get('/prestations',                 [PrestationsController::class, 'index'],  [AuthMiddleware::class]);
 $router->get('/prestations/creer',           [PrestationsController::class, 'create'], [AuthMiddleware::class]);
 $router->post('/prestations',                [PrestationsController::class, 'store'],  [AuthMiddleware::class]);
 $router->get('/prestations/{id}/modifier',   [PrestationsController::class, 'edit'],   [AuthMiddleware::class]);
 $router->post('/prestations/{id}',           [PrestationsController::class, 'update'], [AuthMiddleware::class]);
 $router->post('/prestations/{id}/supprimer', [PrestationsController::class, 'delete'], [AuthMiddleware::class]);