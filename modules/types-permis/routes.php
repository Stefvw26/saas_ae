<?php
// fichier : modules/types-permis/routes.php — v0.41
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use Modules\TypesPermis\Controllers\ParcoursController;
use Modules\TypesPermis\Controllers\TypesPermisController;

/** @var \App\Core\Router $router */

 $router->get('/types-permis',                 [TypesPermisController::class, 'index'],  [AuthMiddleware::class]);
 $router->get('/types-permis/creer',           [TypesPermisController::class, 'create'], [AuthMiddleware::class]);
 $router->post('/types-permis',                [TypesPermisController::class, 'store'],  [AuthMiddleware::class]);
 $router->get('/types-permis/{id}/modifier',   [TypesPermisController::class, 'edit'],   [AuthMiddleware::class]);
 $router->post('/types-permis/{id}',           [TypesPermisController::class, 'update'], [AuthMiddleware::class]);
 $router->post('/types-permis/{id}/supprimer', [TypesPermisController::class, 'delete'], [AuthMiddleware::class]);

/* Générateur de parcours — embarqué dans le formulaire du type (v0.41). */
 $router->post('/parcours/question',                 [ParcoursController::class, 'storeQuestion'],  [AuthMiddleware::class]);
 $router->post('/parcours/question/{id}',            [ParcoursController::class, 'updateQuestion'], [AuthMiddleware::class]);
 $router->post('/parcours/question/{id}/supprimer',  [ParcoursController::class, 'deleteQuestion'], [AuthMiddleware::class]);
 $router->post('/parcours/question/{id}/option',     [ParcoursController::class, 'storeOption'],    [AuthMiddleware::class]);
 $router->post('/parcours/option/{id}/supprimer',    [ParcoursController::class, 'deleteOption'],   [AuthMiddleware::class]);

/* Règles « On affiche… si la réponse est égale à » (v0.41). */
 $router->post('/parcours/regle',              [ParcoursController::class, 'storeRegle'], [AuthMiddleware::class]);
 $router->post('/parcours/regle/{id}/retirer', [ParcoursController::class, 'removeRegle'], [AuthMiddleware::class]);