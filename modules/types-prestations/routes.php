<?php
// fichier : modules/types-prestations/routes.php — CRM Auto-École, Jalon 3
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use Modules\TypesPrestations\Controllers\TypesPrestationsController;

/** @var \App\Core\Router $router */

 $router->get('/types-prestations',                 [TypesPrestationsController::class, 'index'],  [AuthMiddleware::class]);
 $router->get('/types-prestations/creer',           [TypesPrestationsController::class, 'create'], [AuthMiddleware::class]);
 $router->post('/types-prestations',                [TypesPrestationsController::class, 'store'],  [AuthMiddleware::class]);
 $router->get('/types-prestations/{id}/modifier',   [TypesPrestationsController::class, 'edit'],   [AuthMiddleware::class]);
 $router->post('/types-prestations/{id}',           [TypesPrestationsController::class, 'update'], [AuthMiddleware::class]);
 $router->post('/types-prestations/{id}/supprimer', [TypesPrestationsController::class, 'delete'], [AuthMiddleware::class]);