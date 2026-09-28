<?php
// fichier : modules/prestations/Controllers/PrestationsController.php — v0.29
declare(strict_types=1);

namespace Modules\Prestations\Controllers;

use App\Controllers\ReferentielController;
use App\Repositories\ReferentielRepository;
use App\Services\Gate;
use Modules\Centres\Repositories\CentresRepository;
use Modules\Partenaires\Repositories\PartenairesRepository;
use Modules\Prestations\Repositories\PrestationsRepository;
use Modules\TypesPrestations\Repositories\TypesPrestationsRepository;

final class PrestationsController extends ReferentielController
{
    protected string $prefixPermission = 'prestations';
    protected string $routeBase = '/prestations';
    protected string $titreSingulier = 'prestation';
    protected string $titrePluriel = 'prestations';
    protected string $genre = 'f';

    protected function instancierRepository(): ReferentielRepository
    {
        return new PrestationsRepository();
    }

    /**
     * v0.27 (directive) : libellés fournisseurs « Nom — Centre de référence ».
     *
     * @return array<int, string>
     */
    private function libellesFournisseurs(int $tenantId): array
    {
        $centres = [];
        foreach ((new CentresRepository())->toutesPourTenant($tenantId, false) as $centre) {
            $centres[(int)$centre['id']] = (string)$centre['nom'];
        }

        $libelles = [];
        foreach ((new PartenairesRepository())->toutesPourTenant($tenantId, true) as $partenaire) {
            $label = (string)$partenaire['nom'];
            if ($partenaire['centre_id'] !== null && isset($centres[(int)$partenaire['centre_id']])) {
                $label .= ' — ' . $centres[(int)$partenaire['centre_id']];
            }
            $libelles[(int)$partenaire['id']] = $label;
        }
        return $libelles;
    }

    /** Options des champs referer : types de prestations + fournisseurs (avec centre). */
    protected function optionsDynamiques(): array
    {
        $tid = (int)Gate::user()['tenant_id'];
        $options = [];

        $types = [];
        foreach ((new TypesPrestationsRepository())->toutesPourTenant($tid, true) as $type) {
            $types[(int)$type['id']] = (string)$type['nom'];
        }
        $options['type_id'] = $types;

        $options['fournisseur_id'] = $this->libellesFournisseurs($tid);

        return $options;
    }

    /**
     * v0.29 : modale fournisseur (options avec centre) + condition de section
     * « Achats & séjour » (directive v0.28 : affichée si « séjour » OU
     * « fournisseur » coché) — transmise via donneesSupplementaires (aucune
     * dépendance au socle de base).
     *
     * @return array<string, mixed>
     */
    protected function donneesSupplementaires(): array
    {
        return [
            'modalFournisseur' => [
                'options' => $this->libellesFournisseurs((int)Gate::user()['tenant_id']),
            ],
            'conditionCartes'  => [
                'Achats & séjour' => ['champs' => ['sejour', 'fournisseur']],
            ],
        ];
    }

    /** FK externes : type et fournisseur doivent exister dans le tenant. */
    protected function validerSupplement(array $erreurs, array $valeurs, ?int $horsId): array
    {
        $tid = (int)Gate::user()['tenant_id'];

        if (!isset($erreurs['type_id']) && ($valeurs['type_id'] ?? null) !== null) {
            if ((new TypesPrestationsRepository())->trouver((int)$valeurs['type_id'], $tid) === null) {
                $erreurs['type_id'] = 'Type de prestation inconnu.';
            }
        }
        if (!isset($erreurs['fournisseur_id']) && ($valeurs['fournisseur_id'] ?? null) !== null) {
            if ((new PartenairesRepository())->trouver((int)$valeurs['fournisseur_id'], $tid) === null) {
                $erreurs['fournisseur_id'] = 'Fournisseur inconnu.';
            }
        }
        return $erreurs;
    }

    /**
     * Champs dépendants des options (directive v0.25) :
     * fournisseur décochée => plus de partenaire ; séjour décoché => plus de type.
     *
     * @param array<string, ?string|int> $valeurs
     * @return array<string, ?string|int>
     */
    protected function filtrerValeurs(array $valeurs): array
    {
        if ((int)($valeurs['fournisseur'] ?? 0) === 0) {
            $valeurs['fournisseur_id'] = null;
        }
        if ((int)($valeurs['sejour'] ?? 0) === 0) {
            $valeurs['sejour_type'] = null;
        }
        return $valeurs;
    }
}