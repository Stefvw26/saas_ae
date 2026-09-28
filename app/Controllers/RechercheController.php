<?php
// fichier : app/Controllers/RechercheController.php — CRM Auto-École, J4
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\RechercheService;

/**
 * Recherche globale (CDC §12/§28) — accessible à tout utilisateur
 * authentifié ; chaque section ne renvoie que ce que l'utilisateur a le
 * droit de consulter (permissions + tenant + périmètre d'agence).
 */
final class RechercheController extends Controller
{
    public function index(Request $request): void
    {
        $q = trim((string)$request->get('q', ''));

        $this->view('recherche/index', [
            'title'    => 'Recherche',
            'q'        => $q,
            'sections' => (new RechercheService())->resultats($q),
        ]);
    }
}