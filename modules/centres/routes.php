<?php
// fichier : modules/centres/routes.php — CRM Auto-École, Jalon 3
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use Modules\Centres\Controllers\CentresController;

/** @var \App\Core\Router $router */

 $router->get('/centres',                 [CentresController::class, 'index'],  [AuthMiddleware::class]);
 $router->get('/centres/creer',           [CentresController::class, 'create'], [AuthMiddleware::class]);
 $router->post('/centres',                [CentresController::class, 'store'],  [AuthMiddleware::class]);
 $router->get('/centres/{id}/modifier',   [CentresController::class, 'edit'],   [AuthMiddleware::class]);
 $router->post('/centres/{id}',           [CentresController::class, 'update'], [AuthMiddleware::class]);
 $router->post('/centres/{id}/supprimer', [CentresController::class, 'delete'], [AuthMiddleware::class]);