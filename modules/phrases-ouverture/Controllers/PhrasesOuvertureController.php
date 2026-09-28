<?php
// fichier : modules/phrases-ouverture/Controllers/PhrasesOuvertureController.php
declare(strict_types=1);

namespace Modules\PhrasesOuverture\Controllers;

use App\Controllers\ReferentielController;
use App\Repositories\ReferentielRepository;
use Modules\PhrasesOuverture\Repositories\PhrasesOuvertureRepository;

final class PhrasesOuvertureController extends ReferentielController
{
    protected string $prefixPermission = 'phrases-ouverture';
    protected string $routeBase = '/phrases-ouverture';
    protected string $titreSingulier = 'phrase d\'ouverture';
    protected string $titrePluriel = 'phrases d\'ouverture';
    protected string $genre = 'f';

    protected function instancierRepository(): ReferentielRepository
    {
        return new PhrasesOuvertureRepository();
    }
}