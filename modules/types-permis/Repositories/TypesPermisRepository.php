<?php
// fichier : modules/types-permis/Repositories/TypesPermisRepository.php — v0.39
declare(strict_types=1);

namespace Modules\TypesPermis\Repositories;

use App\Repositories\ReferentielRepository;

/**
 * Types de permis — CDC §22 + directives.
 * v0.39 : champ « Parcours prospect » (parcours_type) RETIRÉ du formulaire
 * (directive : il ne sert plus à rien — le questionnaire géré dans la carte
 * Parcours décide seul des questions). La colonne SQL reste (inoffensive).
 */
final class TypesPermisRepository extends ReferentielRepository
{
    protected string $table = 'types_permis';
    protected string $colonneOrdre = 'nom';
    protected bool $suppressionLogique = true;
    protected bool $unicitePrincipale = true;

    /** @var array<string, array<string, mixed>> */
    protected array $champs = [
        'nom'           => ['label' => 'Nom',        'type' => 'text',     'requis' => true,  'max' => 80,  'liste' => true],
        'initiale'      => ['label' => 'Initiale',   'type' => 'text',     'requis' => false, 'max' => 10],
        'descriptif'    => ['label' => 'Descriptif', 'type' => 'textarea', 'requis' => false, 'max' => 255],
        'photographie'  => ['label' => 'Photographie', 'type' => 'photo',  'requis' => false, 'max' => 255, 'dossier' => 'types-permis'],
    ];
}