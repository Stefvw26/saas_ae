<?php
// fichier : modules/modes-paiement/routes.php — CRM Auto-École, Jalon 7
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use Modules\ModesPaiement\Controllers\ModesPaiementController;

/** @var \App\Core\Router $router */

 $router->get('/modes-paiement',                 [ModesPaiementController::class, 'index'],  [AuthMiddleware::class]);
 $router->get('/modes-paiement/creer',           [ModesPaiementController::class, 'create'], [AuthMiddleware::class]);
 $router->post('/modes-paiement',                [ModesPaiementController::class, 'store'],  [AuthMiddleware::class]);
 $router->get('/modes-paiement/{id}/modifier',   [ModesPaiementController::class, 'edit'],   [AuthMiddleware::class]);
 $router->post('/modes-paiement/{id}',           [ModesPaiementController::class, 'update'], [AuthMiddleware::class]);
 $router->post('/modes-paiement/{id}/supprimer', [ModesPaiementController::class, 'delete'], [AuthMiddleware::class]);
