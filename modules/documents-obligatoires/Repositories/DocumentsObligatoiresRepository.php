<?php
// fichier : modules/documents-obligatoires/Repositories/DocumentsObligatoiresRepository.php
declare(strict_types=1);

namespace Modules\DocumentsObligatoires\Repositories;

use App\Repositories\ReferentielRepository;

/**
 * Référentiel des documents obligatoires (pièces à fournir par l'élève :
 * photo/e-photo, CIN, etc.) — préparation fiche dossier, onglet
 * « Documents obligatoires » (directive utilisateur).
 */
final class DocumentsObligatoiresRepository extends ReferentielRepository
{
    protected string $table = 'documents_obligatoires';
    protected string $colonneOrdre = 'nom';
    protected bool $suppressionLogique = true;
    protected bool $unicitePrincipale = true;

    /** @var array<string, array<string, mixed>> */
    protected array $champs = [
        'nom'        => ['label' => 'Nom',        'type' => 'text',     'requis' => true,  'max' => 100, 'liste' => true],
        'initiale'   => ['label' => 'Initiale',   'type' => 'text',     'requis' => false, 'max' => 10],
        'descriptif' => ['label' => 'Descriptif', 'type' => 'textarea', 'requis' => false, 'max' => 255],
    ];
}
