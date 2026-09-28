<?php
// fichier : modules/centres/Repositories/CentresRepository.php — v0.20
declare(strict_types=1);

namespace Modules\Centres\Repositories;

use App\Repositories\ReferentielRepository;

/** Référentiel des centres — CDC §17 (+ tenant_id, traçabilité, groupes v0.20). */
final class CentresRepository extends ReferentielRepository
{
    protected string $table = 'centres';
    protected string $colonneOrdre = 'nom';
    protected bool $suppressionLogique = true;
    protected bool $unicitePrincipale = true;

    /** @var array<string, array<string, mixed>> */
    protected array $champs = [
        'nom'          => ['label' => 'Nom',         'type' => 'text',     'requis' => true,  'max' => 80,  'groupe' => 'Identité',      'liste' => true],
        'url'          => ['label' => 'URL',         'type' => 'text',     'requis' => false, 'max' => 255, 'groupe' => 'Identité'],
        'ville'        => ['label' => 'Ville',       'type' => 'text',     'requis' => false, 'max' => 80,  'groupe' => 'Localisation',  'liste' => true],
        'code_postal'  => ['label' => 'Code postal', 'type' => 'text',     'requis' => false, 'max' => 10,  'groupe' => 'Localisation',  'liste' => true],
        'departement'  => ['label' => 'Département', 'type' => 'text',     'requis' => false, 'max' => 80,  'groupe' => 'Localisation',  'liste' => true],
        'pays'         => ['label' => 'Pays',        'type' => 'text',     'requis' => false, 'max' => 80,  'groupe' => 'Localisation'],
        'latitude'     => ['label' => 'Latitude',    'type' => 'decimal',  'requis' => false, 'max' => 20,  'groupe' => 'Localisation'],
        'longitude'    => ['label' => 'Longitude',   'type' => 'decimal',  'requis' => false, 'max' => 20,  'groupe' => 'Localisation'],
        'descriptif'   => ['label' => 'Descriptif',  'type' => 'textarea', 'requis' => false, 'max' => 255, 'groupe' => 'Présentation'],
        'photographie' => ['label' => 'Photographie','type' => 'photo',    'requis' => false, 'max' => 255, 'groupe' => 'Présentation', 'dossier' => 'centres'],
    ];
}