<?php
// fichier : modules/centres/Controllers/CentresController.php — v0.22
declare(strict_types=1);

namespace Modules\Centres\Controllers;

use App\Controllers\ReferentielController;
use App\Repositories\ReferentielRepository;
use App\Services\Gate;
use Modules\Centres\Repositories\CentresRepository;

final class CentresController extends ReferentielController
{
    protected string $prefixPermission = 'centres';
    protected string $routeBase = '/centres';
    protected string $titreSingulier = 'centre';
    protected string $titrePluriel = 'centres';
    protected string $genre = 'm';

    protected function instancierRepository(): ReferentielRepository
    {
        return new CentresRepository();
    }

    /* Commentaires conversationnels (CDC §18 : centre + partenaire). */
    protected function typeCommentaire(): ?string
    {
        return 'centre';
    }

    protected function avecPartenaireCommentaire(): bool
    {
        return true;
    }

    /**
     * Points géolocalisés de tous les centres actifs du tenant (carte OSM).
     *
     * @return array<string, mixed>
     */
    protected function donneesSupplementaires(): array
    {
        $points = [];
        foreach ($this->repository->toutesPourTenant((int)Gate::user()['tenant_id'], true) as $centre) {
            if ($centre['latitude'] !== null && $centre['longitude'] !== null) {
                $sous = trim((string)$centre['ville']);
                if ($centre['code_postal'] !== null && $centre['code_postal'] !== '') {
                    $sous = trim($sous . ' ' . (string)$centre['code_postal']);
                }
                $points[] = [
                    'lat'   => (float)$centre['latitude'],
                    'lon'   => (float)$centre['longitude'],
                    'titre' => (string)$centre['nom'],
                    'sous'  => $sous,
                ];
            }
        }
        return ['cartePoints' => $points];
    }
}