<?php
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use Modules\Formules\Controllers\FormulesController;

/** @var \App\Core\Router $router */

 $router->get('/formules',                   [FormulesController::class, 'index'],   [AuthMiddleware::class]);
 $router->get('/formules/creer',             [FormulesController::class, 'create'], [AuthMiddleware::class]);
 $router->post('/formules',                  [FormulesController::class, 'store'],  [AuthMiddleware::class]);
 $router->get('/formules/{id}',              [FormulesController::class, 'fiche'],  [AuthMiddleware::class]);
 $router->get('/formules/{id}/modifier',    [FormulesController::class, 'edit'],   [AuthMiddleware::class]);
 $router->post('/formules/{id}',             [FormulesController::class, 'update'], [AuthMiddleware::class]);
 $router->post('/formules/{id}/supprimer',   [FormulesController::class, 'delete'], [AuthMiddleware::class]);
 $router->post('/formules/{id}/prestation',  [FormulesController::class, 'addPrestation'],    [AuthMiddleware::class]);
 $router->post('/formules/prestation/{pid}/retirer', [FormulesController::class, 'removePrestation'], [AuthMiddleware::class]);
 $router->post('/formules/prestation/{pid}/quantite', [FormulesController::class, 'updateQuantite'], [AuthMiddleware::class]);