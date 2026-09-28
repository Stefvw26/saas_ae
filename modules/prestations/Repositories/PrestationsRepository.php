<?php
// fichier : modules/prestations/Repositories/PrestationsRepository.php — v0.28
declare(strict_types=1);

namespace Modules\Prestations\Repositories;

use App\Repositories\ReferentielRepository;

/**
 * Catalogue des prestations — CDC §20 + directives v0.20/21/22/25/28.
 * v0.28 (directive) : la carte « Achats & séjour » (fournisseur_id,
 * sejour_type) n'apparaît QUE si « Frais d'organisation de séjour » OU
 * « J'achète ce produit/service auprès d'un fournisseur » est coché —
 * condition de CARTE, plus forte que les conditions de champs.
 */
final class PrestationsRepository extends ReferentielRepository
{
    protected string $table = 'prestations';
    protected string $colonneOrdre = 'titre';
    protected bool $suppressionLogique = true;
    protected bool $unicitePrincipale = false;

    /** Conditions d'affichage PAR CARTE (v0.28) : groupe => champs déclencheurs (OR). */
    protected array $conditionCartes = [
        'Achats & séjour' => ['champs' => ['sejour', 'fournisseur']],
    ];

    /** @var array<string, array<string, mixed>> */
    protected array $champs = [
        'titre'          => ['label' => 'Titre',         'type' => 'text',     'requis' => true,  'max' => 150, 'groupe' => 'Identification', 'liste' => true],
        'type_id'        => ['label' => 'Type',          'type' => 'referer',  'requis' => false, 'max' => 11,  'groupe' => 'Identification', 'liste' => true, 'source' => 'types-prestations'],
        'sku'            => ['label' => 'SKU',           'type' => 'text',     'requis' => false, 'max' => 50,  'groupe' => 'Identification', 'liste' => true],

        'prix_vente'     => ['label' => 'Prix de vente', 'type' => 'decimal',  'requis' => false, 'max' => 12,  'groupe' => 'Tarifs',         'liste' => true],
        'prix_achete'    => ['label' => 'Prix d\'achat', 'type' => 'decimal',  'requis' => false, 'max' => 12,  'groupe' => 'Tarifs',         'liste' => true],

        'administratif'  => ['label' => 'Administratif',                       'type' => 'booleen', 'requis' => false, 'max' => 1, 'groupe' => 'Options'],
        'sejour'         => ['label' => 'Frais d\'organisation de séjour',     'type' => 'booleen', 'requis' => false, 'max' => 1, 'groupe' => 'Options'],
        'assurance'      => ['label' => 'Assurance annulation de séjour',      'type' => 'booleen', 'requis' => false, 'max' => 1, 'groupe' => 'Options'],
        'calendrier'     => ['label' => 'Plaçable sur calendrier',             'type' => 'booleen', 'requis' => false, 'max' => 1, 'groupe' => 'Options'],
        'fournisseur'    => ['label' => 'J\'achète ce produit/service auprès d\'un fournisseur', 'type' => 'booleen', 'requis' => false, 'max' => 1, 'groupe' => 'Options'],
        'dans_contrat'   => ['label' => 'Dans contrat',  'type' => 'booleen',   'requis' => false, 'max' => 1,   'groupe' => 'Options', 'liste' => true],

        'fournisseur_id' => ['label' => 'Fournisseur (partenaire)', 'type' => 'referer', 'requis' => false, 'max' => 11, 'groupe' => 'Achats & séjour', 'liste' => true, 'source' => 'partenaires'],
        'sejour_type'    => [
            'label' => 'Type de séjour', 'type' => 'select', 'requis' => false, 'max' => 40,
            'groupe' => 'Achats & séjour', 'liste' => true,
            'options' => [
                '1er séjour'            => '1er séjour',
                '2ème séjour'           => '2ème séjour',
                'Séjour permis'         => 'Séjour permis',
                'Séjour complémentaire' => 'Séjour complémentaire',
            ],
        ],

        'description'    => ['label' => 'Description',   'type' => 'textarea', 'requis' => false, 'max' => 1000, 'groupe' => 'Description'],
    ];
}