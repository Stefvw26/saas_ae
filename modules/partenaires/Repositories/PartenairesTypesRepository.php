<?php
// fichier : modules/partenaires/Repositories/PartenairesTypesRepository.php
declare(strict_types=1);

namespace Modules\Partenaires\Repositories;

use App\Repositories\ReferentielRepository;

/**
 * Types de partenaires (directive v0.20) — structure demandée :
 * id, nom, descriptif, ajout_par, ajout_date, actif, supprimer,
 * supprimer_par, supprimer_date (+ tenant_id : isolation SaaS).
 * Seeds : Auto-école, Hôtel (migrate.php).
 * Utilisé comme source du menu déroulant des partenaires ; CRUD d'administration
 * non exposé à ce stade (point ouvert — à valider).
 */
final class PartenairesTypesRepository extends ReferentielRepository
{
    protected string $table = 'partenaires_type';
    protected string $colonneOrdre = 'nom';
    protected string $colonneDate = 'ajout_date';
    protected bool $suppressionLogique = true;
    protected bool $unicitePrincipale = true;

    /** @var array<string, array<string, mixed>> */
    protected array $champs = [
        'nom'        => ['label' => 'Nom',        'type' => 'text', 'requis' => true,  'max' => 150],
        'descriptif' => ['label' => 'Descriptif', 'type' => 'text', 'requis' => false, 'max' => 255],
    ];
}