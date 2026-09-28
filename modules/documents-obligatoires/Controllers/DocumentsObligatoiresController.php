<?php
// fichier : modules/documents-obligatoires/Controllers/DocumentsObligatoiresController.php
declare(strict_types=1);

namespace Modules\DocumentsObligatoires\Controllers;

use App\Controllers\ReferentielController;
use App\Repositories\ReferentielRepository;
use Modules\DocumentsObligatoires\Repositories\DocumentsObligatoiresRepository;

final class DocumentsObligatoiresController extends ReferentielController
{
    protected string $prefixPermission = 'documents-obligatoires';
    protected string $routeBase = '/documents-obligatoires';
    protected string $titreSingulier = 'document obligatoire';
    protected string $titrePluriel = 'documents obligatoires';
    protected string $genre = 'm';

    protected function instancierRepository(): ReferentielRepository
    {
        return new DocumentsObligatoiresRepository();
    }
}
