<?php
// fichier : modules/modes-paiement/Controllers/ModesPaiementController.php
declare(strict_types=1);

namespace Modules\ModesPaiement\Controllers;

use App\Controllers\ReferentielController;
use App\Repositories\ReferentielRepository;
use Modules\ModesPaiement\Repositories\ModesPaiementRepository;

final class ModesPaiementController extends ReferentielController
{
    protected string $prefixPermission = 'modes-paiement';
    protected string $routeBase = '/modes-paiement';
    protected string $titreSingulier = 'mode de paiement';
    protected string $titrePluriel = 'modes de paiement';
    protected string $genre = 'm';

    protected function instancierRepository(): ReferentielRepository
    {
        return new ModesPaiementRepository();
    }
}
