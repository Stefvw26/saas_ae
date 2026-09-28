<?php
// fichier : modules/documents-obligatoires/routes.php — CRM Auto-École, Jalon 7
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use Modules\DocumentsObligatoires\Controllers\DocumentsObligatoiresController;

/** @var \App\Core\Router $router */

 $router->get('/documents-obligatoires',                 [DocumentsObligatoiresController::class, 'index'],  [AuthMiddleware::class]);
 $router->get('/documents-obligatoires/creer',           [DocumentsObligatoiresController::class, 'create'], [AuthMiddleware::class]);
 $router->post('/documents-obligatoires',                [DocumentsObligatoiresController::class, 'store'],  [AuthMiddleware::class]);
 $router->get('/documents-obligatoires/{id}/modifier',   [DocumentsObligatoiresController::class, 'edit'],   [AuthMiddleware::class]);
 $router->post('/documents-obligatoires/{id}',           [DocumentsObligatoiresController::class, 'update'], [AuthMiddleware::class]);
 $router->post('/documents-obligatoires/{id}/supprimer', [DocumentsObligatoiresController::class, 'delete'], [AuthMiddleware::class]);
