<?php
// fichier : modules/partenaires/Controllers/PartenairesController.php — v0.31
declare(strict_types=1);

namespace Modules\Partenaires\Controllers;

use App\Controllers\ReferentielController;
use App\Repositories\ReferentielRepository;
use App\Services\Gate;
use Modules\Centres\Repositories\CentresRepository;
use Modules\Partenaires\Repositories\PartenairesRepository;
use Modules\Partenaires\Repositories\PartenairesTypesRepository;

final class PartenairesController extends ReferentielController
{
    protected string $prefixPermission = 'partenaires';
    protected string $routeBase = '/partenaires';
    protected string $titreSingulier = 'partenaire';
    protected string $titrePluriel = 'partenaires';
    protected string $genre = 'm';

    protected function instancierRepository(): ReferentielRepository
    {
        return new PartenairesRepository();
    }

    /* v0.27 (directive) : commentaires conversationnels sur les partenaires. */
    protected function typeCommentaire(): ?string
    {
        return 'partenaire';
    }

    /** Options des champs referer : types de partenaires + centres. */
    protected function optionsDynamiques(): array
    {
        $tid = (int)Gate::user()['tenant_id'];
        $options = [];

        $types = [];
        foreach ((new PartenairesTypesRepository())->toutesPourTenant($tid, true) as $type) {
            $types[(int)$type['id']] = (string)$type['nom'];
        }
        $options['type_id'] = $types;

        $centres = [];
        foreach ((new CentresRepository())->toutesPourTenant($tid, true) as $centre) {
            $centres[(int)$centre['id']] = (string)$centre['nom'];
        }
        $options['centre_id'] = $centres;

        return $options;
    }

    /**
     * Icônes des types + colonne « Prestations » + carte OSM (v0.21/25)
     * + auto-complétion d'adresse (CDC §34 — v0.31).
     *
     * @return array<string, mixed>
     */
    protected function donneesSupplementaires(): array
    {
        $icones = [];
        $points = [];
        foreach ((new PartenairesTypesRepository())->toutesPourTenant((int)Gate::user()['tenant_id'], false) as $type) {
            if (!empty($type['icone'])) {
                $icones[(int)$type['id']] = (string)$type['icone'];
            }
        }
        foreach ($this->repository->toutesPourTenant((int)Gate::user()['tenant_id'], true) as $partenaire) {
            if ($partenaire['latitude'] !== null && $partenaire['longitude'] !== null) {
                $points[] = [
                    'lat'   => (float)$partenaire['latitude'],
                    'lon'   => (float)$partenaire['longitude'],
                    'titre' => (string)$partenaire['nom'],
                    'sous'  => trim((string)$partenaire['ville']),
                ];
            }
        }

        return [
            'icones' => ['type_id' => $icones],
            'colonnesCalculees' => [
                ['cle' => 'nb_prestations', 'label' => 'Prestations', 'type' => 'entier'],
            ],
            'cartePoints' => $points,
            'autocompleteAdresse' => [
                'adresse'     => 'adresse',
                'code_postal' => 'code_postal',
                'ville'       => 'ville',
                'pays'        => 'pays',
                'latitude'    => 'latitude',
                'longitude'   => 'longitude',
            ],
        ];
    }

    /** FK externes : type et centre doivent exister dans le tenant. */
    protected function validerSupplement(array $erreurs, array $valeurs, ?int $horsId): array
    {
        $tid = (int)Gate::user()['tenant_id'];

        if (!isset($erreurs['type_id']) && ($valeurs['type_id'] ?? null) !== null) {
            if ((new PartenairesTypesRepository())->trouver((int)$valeurs['type_id'], $tid) === null) {
                $erreurs['type_id'] = 'Type de partenaire inconnu.';
            }
        }
        if (!isset($erreurs['centre_id']) && ($valeurs['centre_id'] ?? null) !== null) {
            if ((new CentresRepository())->trouver((int)$valeurs['centre_id'], $tid) === null) {
                $erreurs['centre_id'] = 'Centre inconnu.';
            }
        }
        return $erreurs;
    }
}