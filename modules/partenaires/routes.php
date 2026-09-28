<?php
// fichier : modules/partenaires/routes.php — CRM Auto-École, Jalon 3
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use Modules\Partenaires\Controllers\PartenairesController;

/** @var \App\Core\Router $router */

 $router->get('/partenaires',                 [PartenairesController::class, 'index'],  [AuthMiddleware::class]);
 $router->get('/partenaires/creer',           [PartenairesController::class, 'create'], [AuthMiddleware::class]);
 $router->post('/partenaires',                [PartenairesController::class, 'store'],  [AuthMiddleware::class]);
 $router->get('/partenaires/{id}/modifier',   [PartenairesController::class, 'edit'],   [AuthMiddleware::class]);
 $router->post('/partenaires/{id}',           [PartenairesController::class, 'update'], [AuthMiddleware::class]);
 $router->post('/partenaires/{id}/supprimer', [PartenairesController::class, 'delete'], [AuthMiddleware::class]);