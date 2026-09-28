<?php
// fichier : modules/provenances/Repositories/ProvenancesRepository.php
declare(strict_types=1);

namespace Modules\Provenances\Repositories;

use App\Repositories\ReferentielRepository;

/** Référentiel des provenances — structure CDC §23 (+ tenant_id, traçabilité). */
final class ProvenancesRepository extends ReferentielRepository
{
    protected string $table = 'provenances';
    protected string $colonneOrdre = 'provenance';
    protected bool $suppressionLogique = true;
    protected bool $unicitePrincipale = true;

    /** @var array<string, array{label:string,type:string,requis:bool,max:int}> */
    protected array $champs = [
        'provenance' => ['label' => 'Provenance', 'type' => 'text', 'requis' => true, 'max' => 150],
    ];
}