<?php
// fichier : modules/phrases-ouverture/routes.php — CRM Auto-École, Jalon 3
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use Modules\PhrasesOuverture\Controllers\PhrasesOuvertureController;

/** @var \App\Core\Router $router */

 $router->get('/phrases-ouverture',                 [PhrasesOuvertureController::class, 'index'],  [AuthMiddleware::class]);
 $router->get('/phrases-ouverture/creer',           [PhrasesOuvertureController::class, 'create'], [AuthMiddleware::class]);
 $router->post('/phrases-ouverture',                [PhrasesOuvertureController::class, 'store'],  [AuthMiddleware::class]);
 $router->get('/phrases-ouverture/{id}/modifier',   [PhrasesOuvertureController::class, 'edit'],   [AuthMiddleware::class]);
 $router->post('/phrases-ouverture/{id}',           [PhrasesOuvertureController::class, 'update'], [AuthMiddleware::class]);
 $router->post('/phrases-ouverture/{id}/supprimer', [PhrasesOuvertureController::class, 'delete'], [AuthMiddleware::class]);