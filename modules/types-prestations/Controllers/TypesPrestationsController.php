<?php
// fichier : modules/types-prestations/Controllers/TypesPrestationsController.php
declare(strict_types=1);

namespace Modules\TypesPrestations\Controllers;

use App\Controllers\ReferentielController;
use App\Repositories\ReferentielRepository;
use Modules\TypesPrestations\Repositories\TypesPrestationsRepository;

final class TypesPrestationsController extends ReferentielController
{
    protected string $prefixPermission = 'types-prestations';
    protected string $routeBase = '/types-prestations';
    protected string $titreSingulier = 'type de prestation';
    protected string $titrePluriel = 'types de prestations';
    protected string $genre = 'm';

    protected function instancierRepository(): ReferentielRepository
    {
        return new TypesPrestationsRepository();
    }
}