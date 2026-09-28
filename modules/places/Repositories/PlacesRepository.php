<?php
// fichier : modules/places/Repositories/PlacesRepository.php
declare(strict_types=1);

namespace Modules\Places\Repositories;

use App\Repositories\ReferentielRepository;

/**
 * Référentiel des places (CDC §9 + directives utilisateur).
 * Structure validée v0.20 : libellé + couleur.
 * Rattachement « dossier_id » : PRÉVU AU JALON 7 (module dossiers inexistant
 * à ce stade — la colonne et la FK seront ajoutées par une migration dédiée).
 */
final class PlacesRepository extends ReferentielRepository
{
    protected string $table = 'places';
    protected string $colonneOrdre = 'libelle';
    protected bool $suppressionLogique = true;
    protected bool $unicitePrincipale = true;

    /** @var array<string, array<string, mixed>> */
    protected array $champs = [
        'libelle' => ['label' => 'Libellé', 'type' => 'text',  'requis' => true,  'max' => 150, 'groupe' => 'Informations', 'liste' => true],
        'couleur' => ['label' => 'Couleur', 'type' => 'color', 'requis' => false, 'max' => 7,   'groupe' => 'Informations', 'liste' => true],
    ];
}