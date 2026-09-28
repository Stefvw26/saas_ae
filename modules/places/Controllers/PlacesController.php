<?php
// fichier : modules/places/Controllers/PlacesController.php
declare(strict_types=1);

namespace Modules\Places\Controllers;

use App\Controllers\ReferentielController;
use App\Repositories\ReferentielRepository;
use Modules\Places\Repositories\PlacesRepository;

final class PlacesController extends ReferentielController
{
    protected string $prefixPermission = 'places';
    protected string $routeBase = '/places';
    protected string $titreSingulier = 'place';
    protected string $titrePluriel = 'places';
    protected string $genre = 'f';

    protected function instancierRepository(): ReferentielRepository
    {
        return new PlacesRepository();
    }
}