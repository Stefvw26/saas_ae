<?php
// fichier : modules/eleves/routes.php — Jalon 6
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use Modules\Eleves\Controllers\ElevesController;

/** @var \App\Core\Router $router */

 $router->get('/eleves',                 [ElevesController::class, 'index'],  [AuthMiddleware::class]);
 $router->get('/eleves/creer',           [ElevesController::class, 'create'], [AuthMiddleware::class]);
 $router->post('/eleves',                [ElevesController::class, 'store'],  [AuthMiddleware::class]);
 $router->get('/eleves/{id}',            [ElevesController::class, 'fiche'],  [AuthMiddleware::class]);
 $router->get('/eleves/{id}/modifier',   [ElevesController::class, 'edit'],   [AuthMiddleware::class]);
 $router->post('/eleves/{id}',           [ElevesController::class, 'update'], [AuthMiddleware::class]);
 $router->post('/eleves/{id}/archiver',  [ElevesController::class, 'archiver'], [AuthMiddleware::class]);
  $router->post('/eleves/{id}/transferer', [ElevesController::class, 'transferer'], [AuthMiddleware::class]);
 $router->post('/eleves/{id}/supprimer',  [ElevesController::class, 'supprimer'],  [AuthMiddleware::class]);
  $router->post('/eleves/{id}/boite',  [ElevesController::class, 'majBoite'],  [AuthMiddleware::class]);
 $router->post('/eleves/{id}/niveau-b', [ElevesController::class, 'majNiveauB'], [AuthMiddleware::class]);