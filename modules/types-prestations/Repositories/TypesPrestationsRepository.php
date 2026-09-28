<?php
// fichier : modules/types-prestations/Repositories/TypesPrestationsRepository.php
declare(strict_types=1);

namespace Modules\TypesPrestations\Repositories;

use App\Repositories\ReferentielRepository;

/**
 * Référentiel des types de prestations — structure CDC §21 (+ tenant_id).
 * Unicité DÉSACTIVÉE : le CDC liste volontairement un doublon « DIVERS »
 * (point ouvert signalé par le cahier des charges §21).
 */
final class TypesPrestationsRepository extends ReferentielRepository
{
    protected string $table = 'types_prestations';
    protected string $colonneOrdre = 'nom';
    protected bool $suppressionLogique = true;
    protected bool $unicitePrincipale = false;

    /** @var array<string, array{label:string,type:string,requis:bool,max:int}> */
    protected array $champs = [
        'nom'        => ['label' => 'Nom',        'type' => 'text', 'requis' => true, 'max' => 100],
        'descriptif' => ['label' => 'Descriptif', 'type' => 'textarea', 'requis' => false, 'max' => 255],
    ];
}