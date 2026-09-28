<?php
// fichier : modules/vehicules/Controllers/VehiculesController.php — v0.22
declare(strict_types=1);

namespace Modules\Vehicules\Controllers;

use App\Controllers\ReferentielController;
use App\Repositories\ReferentielRepository;
use Modules\Vehicules\Repositories\VehiculesRepository;

final class VehiculesController extends ReferentielController
{
    protected string $prefixPermission = 'vehicules';
    protected string $routeBase = '/vehicules';
    protected string $titreSingulier = 'véhicule';
    protected string $titrePluriel = 'véhicules';
    protected string $genre = 'm';

    protected function instancierRepository(): ReferentielRepository
    {
        return new VehiculesRepository();
    }

    /* Commentaires conversationnels (CDC §16 : véhicule + km). */
    protected function typeCommentaire(): ?string
    {
        return 'vehicule';
    }

    protected function avecKmCommentaire(): bool
    {
        return true;
    }
}