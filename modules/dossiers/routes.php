<?php
// fichier : modules/dossiers/routes.php — Jalon 7
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use Modules\Dossiers\Controllers\DossiersController;
use Modules\Dossiers\Controllers\EvenementsController;

/** @var \App\Core\Router $router */

 $router->get('/dossiers/creer',              [DossiersController::class, 'create'], [AuthMiddleware::class]);
 $router->get('/dossiers/{id}',               [DossiersController::class, 'fiche'],  [AuthMiddleware::class]);
 $router->get('/dossiers/{id}/modifier',      [DossiersController::class, 'edit'],   [AuthMiddleware::class]);
 $router->post('/dossiers/{id}',              [DossiersController::class, 'update'], [AuthMiddleware::class]);
 $router->post('/dossiers/{id}/archiver',     [DossiersController::class, 'archiver'], [AuthMiddleware::class]);
 $router->post('/dossiers/{id}/supprimer',    [DossiersController::class, 'supprimer'], [AuthMiddleware::class]);

/* État du dossier. */
 $router->post('/dossiers/{id}/panier/valider',    [DossiersController::class, 'panierValider'],  [AuthMiddleware::class]);
 $router->post('/dossiers/{id}/contrat/generer',   [DossiersController::class, 'contratGenerer'], [AuthMiddleware::class]);
 $router->post('/dossiers/{id}/devis',             [DossiersController::class, 'devisAjouter'],   [AuthMiddleware::class]);

/* Remise. */
 $router->post('/dossiers/{id}/remise',            [DossiersController::class, 'remiseEnregistrer'], [AuthMiddleware::class]);
 $router->post('/dossiers/{id}/remise/supprimer',  [DossiersController::class, 'remiseSupprimer'],   [AuthMiddleware::class]);

/* Déclarations CERFA / EDISER / NEPH (unifié). */
 $router->post('/dossiers/{id}/declaration', [DossiersController::class, 'declarationEnregistrer'], [AuthMiddleware::class]);

/* Échéances : définition groupée (nombre choisi puis saisie de toutes les lignes) tant que le
   contrat n'est pas généré ; mise à jour ligne par ligne après génération. */
 $router->post('/dossiers/{id}/echeances/definir',                [DossiersController::class, 'echeancesDefinir'],  [AuthMiddleware::class]);
 $router->post('/dossiers/{id}/echeances/{echeanceId}',           [DossiersController::class, 'echeanceModifier'],  [AuthMiddleware::class]);
 $router->post('/dossiers/{id}/echeances/{echeanceId}/supprimer', [DossiersController::class, 'echeanceSupprimer'], [AuthMiddleware::class]);

/* Documents (devis/contrat/cerfa gérés par les actions ci-dessus ; ici : documents libres). */
 $router->post('/dossiers/{id}/documents',                       [DossiersController::class, 'documentAjouter'],   [AuthMiddleware::class]);
 $router->post('/dossiers/{id}/documents/{documentId}/supprimer', [DossiersController::class, 'documentSupprimer'], [AuthMiddleware::class]);

/* Checklist des documents obligatoires. */
 $router->post('/dossiers/{id}/obligatoires/{documentObligatoireId}', [DossiersController::class, 'obligatoireBasculer'], [AuthMiddleware::class]);

/* Événements (créés depuis un dossier). */
 $router->post('/dossiers/{id}/evenements',               [EvenementsController::class, 'store'],   [AuthMiddleware::class]);
 $router->post('/evenements/{id}/supprimer',              [EvenementsController::class, 'delete'],  [AuthMiddleware::class]);
  $router->post('/dossiers/{id}/panier/ajouter',         [DossiersController::class, 'panierAjouter'],        [AuthMiddleware::class]);
 $router->post('/dossiers/{id}/panier/ajouter-formule', [DossiersController::class, 'panierAjouterFormule'], [AuthMiddleware::class]);
 $router->post('/dossiers/{id}/panier/retirer',         [DossiersController::class, 'panierRetirer'],       [AuthMiddleware::class]);
 $router->post('/dossiers/{id}/panier/modifier',        [DossiersController::class, 'panierModifier'],      [AuthMiddleware::class]);
 $router->post('/dossiers/{id}/panier/vider',           [DossiersController::class, 'panierVider'],         [AuthMiddleware::class]);