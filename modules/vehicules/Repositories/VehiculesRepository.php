<?php
// fichier : modules/vehicules/Repositories/VehiculesRepository.php — v0.22 (PNG)
declare(strict_types=1);

namespace Modules\Vehicules\Repositories;

use App\Repositories\ReferentielRepository;

/**
 * Référentiel des véhicules — CDC §15 + directives v0.20/21/22.
 * Images PNG fournies par l'utilisateur (plus aucun SVG) :
 * identification (boite-*) et colonne Mécanique (gear_*).
 */
final class VehiculesRepository extends ReferentielRepository
{
    protected string $table = 'vehicules';
    protected string $colonneOrdre = 'immatriculation';
    protected bool $suppressionLogique = true;
    protected bool $unicitePrincipale = true;
    protected bool $scoperAgence = true;

    /** Colonne image d'identification : valeur de mécanique -> PNG. */
    protected array $colonneIdentite = [
        'champ'  => 'mecanique',
        'images' => ['Manuelle' => 'boite-manu.png', 'Automatique' => 'boite-auto.png'],
    ];

    /** @var array<string, array<string, mixed>> */
    protected array $champs = [
        'immatriculation' => ['label' => 'Immatriculation', 'type' => 'text',   'requis' => true,  'max' => 20,  'groupe' => 'Identité',        'liste' => true, 'position' => 2],
        'marque'          => ['label' => 'Marque',           'type' => 'text',   'requis' => true,  'max' => 80,  'groupe' => 'Identité',        'liste' => true],
        'model'           => ['label' => 'Modèle',           'type' => 'text',   'requis' => false, 'max' => 80,  'groupe' => 'Identité',        'liste' => true],
        'mecanique'       => [
            'label' => 'Mécanique', 'type' => 'select', 'requis' => true, 'max' => 20,
            'groupe' => 'Caractéristiques', 'liste' => true,
            'options' => ['Manuelle' => 'Manuelle', 'Automatique' => 'Automatique'],
            'images'  => ['Manuelle' => 'gear_manu.png', 'Automatique' => 'gear_auto.png'],
        ],
        'couleur'         => ['label' => 'Couleur',          'type' => 'text',   'requis' => false, 'max' => 80,  'groupe' => 'Caractéristiques', 'liste' => true],
        'etat'            => [
            'label' => 'État', 'type' => 'select', 'requis' => false, 'max' => 30,
            'groupe' => 'Caractéristiques', 'liste' => true,
            'options' => [
                'Très bon état'    => 'Très bon état',
                'Bon état'         => 'Bon état',
                'Moyen état'       => 'Moyen état',
                'Mauvais état'     => 'Mauvais état',
                'Très mauvais état'=> 'Très mauvais état',
            ],
        ],
        'agence'          => ['label' => 'Agence',           'type' => 'agence', 'requis' => true,  'max' => 11,  'groupe' => 'Rattachement',    'liste' => true],
    ];
}