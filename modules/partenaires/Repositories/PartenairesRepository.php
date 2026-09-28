<?php
// fichier : modules/partenaires/Repositories/PartenairesRepository.php — v0.29
declare(strict_types=1);

namespace Modules\Partenaires\Repositories;

use App\Repositories\ReferentielRepository;

/**
 * Référentiel des partenaires — CDC §19 + directives v0.20/21/22/25/29.
 * v0.29 : téléphone = composant indicatif + drapeau (CDC §35) via le type
 * 'telephone' du moteur générique.
 */
final class PartenairesRepository extends ReferentielRepository
{
    protected string $table = 'partenaires';
    protected string $colonneOrdre = 'nom';
    protected bool $suppressionLogique = true;
    protected bool $unicitePrincipale = true;
    protected bool $listeStatut = false;
    protected bool $listeDate = false;

    /** Compteur de prestations associées (fournisseur_id). */
    protected function colonnesSelect(): string
    {
        return '*, (SELECT COUNT(*) FROM prestations p
                    WHERE p.fournisseur_id = partenaires.id AND p.supprimer = 0) AS nb_prestations';
    }

    /** @var array<string, array<string, mixed>> */
    protected array $champs = [
        'nom'                 => ['label' => 'Nom',           'type' => 'text',      'requis' => true,  'max' => 150, 'groupe' => 'Identité',      'liste' => true, 'position' => 2],
        'type_id'             => ['label' => 'Type',          'type' => 'referer',   'requis' => true,  'max' => 11,  'groupe' => 'Identité',      'liste' => true, 'position' => 1, 'source' => 'partenaires_type'],
        'hotel_etoile'        => [
            'label' => 'Étoiles', 'type' => 'etoiles', 'requis' => false, 'max' => 5,
            'groupe' => 'Identité', 'liste' => true, 'position' => 6,
            'condition' => ['champ' => 'type_id', 'contient' => 'hôtel'],
        ],
        'telephone'           => ['label' => 'Téléphone',     'type' => 'telephone', 'requis' => false, 'max' => 30,  'groupe' => 'Identité'],
        'url'                 => ['label' => 'URL',           'type' => 'text',      'requis' => false, 'max' => 255, 'groupe' => 'Identité'],

        'adresse'             => ['label' => 'Adresse',       'type' => 'text',      'requis' => false, 'max' => 255, 'groupe' => 'Localisation',  'liste' => true, 'position' => 3],
        'code_postal'         => ['label' => 'Code postal',   'type' => 'text',      'requis' => false, 'max' => 10,  'groupe' => 'Localisation'],
        'ville'               => ['label' => 'Ville',         'type' => 'text',      'requis' => false, 'max' => 80,  'groupe' => 'Localisation'],
        'pays'                => ['label' => 'Pays',          'type' => 'text',      'requis' => false, 'max' => 80,  'groupe' => 'Localisation'],
        'latitude'            => ['label' => 'Latitude',      'type' => 'decimal',   'requis' => false, 'max' => 20,  'groupe' => 'Localisation'],
        'longitude'           => ['label' => 'Longitude',     'type' => 'decimal',   'requis' => false, 'max' => 20,  'groupe' => 'Localisation'],

        'centre_id'           => ['label' => 'Centre',        'type' => 'referer',   'requis' => false, 'max' => 11,  'groupe' => 'Rattachement',  'liste' => true, 'position' => 4, 'source' => 'centres'],

        'agreement'           => ['label' => 'Agrément',             'type' => 'text',     'requis' => false, 'max' => 100, 'groupe' => 'Agrément et assurance'],
        'date_agreement'      => ['label' => 'Date d\'agrément',     'type' => 'date',     'requis' => false, 'max' => 10,  'groupe' => 'Agrément et assurance'],
        'agrement_exploitant' => ['label' => 'Agrément exploitant',  'type' => 'text',     'requis' => false, 'max' => 150, 'groupe' => 'Agrément et assurance'],
        'assurance'           => ['label' => 'Assurance',            'type' => 'text',     'requis' => false, 'max' => 150, 'groupe' => 'Agrément et assurance'],

        'og'                  => ['label' => 'OG',                   'type' => 'text',     'requis' => false, 'max' => 255, 'groupe' => 'Présentation'],
        'descriptif'          => ['label' => 'Descriptif',           'type' => 'textarea', 'requis' => false, 'max' => 255, 'groupe' => 'Présentation'],
        'photographie'        => ['label' => 'Photographie principale', 'type' => 'photo',  'requis' => false, 'max' => 255, 'groupe' => 'Présentation', 'dossier' => 'partenaires'],
    ];
}