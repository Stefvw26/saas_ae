<?php
// fichier : modules/modes-paiement/Repositories/ModesPaiementRepository.php
declare(strict_types=1);

namespace Modules\ModesPaiement\Repositories;

use App\Repositories\ReferentielRepository;

/**
 * Référentiel des modes de paiement (échéancier du dossier) — préparation
 * fiche dossier, onglet « Remise et règlements » (directive utilisateur).
 */
final class ModesPaiementRepository extends ReferentielRepository
{
    protected string $table = 'modes_paiement';
    protected string $colonneOrdre = 'nom';
    protected bool $suppressionLogique = true;
    protected bool $unicitePrincipale = true;

    /** @var array<string, array<string, mixed>> */
    protected array $champs = [
        'nom' => ['label' => 'Nom', 'type' => 'text', 'requis' => true, 'max' => 100, 'liste' => true],
    ];
}
