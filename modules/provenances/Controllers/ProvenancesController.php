<?php
// fichier : modules/provenances/Controllers/ProvenancesController.php
declare(strict_types=1);

namespace Modules\Provenances\Controllers;

use App\Controllers\ReferentielController;
use App\Repositories\ReferentielRepository;
use Modules\Provenances\Repositories\ProvenancesRepository;

final class ProvenancesController extends ReferentielController
{
    protected string $prefixPermission = 'provenances';
    protected string $routeBase = '/provenances';
    protected string $titreSingulier = 'provenance';
    protected string $titrePluriel = 'provenances';
    protected string $genre = 'f';

    protected function instancierRepository(): ReferentielRepository
    {
        return new ProvenancesRepository();
    }
}