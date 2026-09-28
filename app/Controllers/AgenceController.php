<?php
// fichier : app/Controllers/AgenceController.php — CRM Auto-École, Jalon 2
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Services\Gate;
use App\Services\LogService;

/**
 * Changement d'agence active (CDC §13 : le directeur peut changer d'agence
 * lorsque ses droits l'autorisent — étendu à tout porteur de la permission
 * « agences.changer », y compris les comptes « tous droits »).
 */
final class AgenceController extends Controller
{
    public function switch(Request $request): void
    {
        $this->authorize('agences.changer');

        $demandee = (int)$request->post('agence_id', -1);

        if ($demandee === 0) {
            if (!Gate::tousDroit()) {
                LogService::enregistrer('agence.changee', 'agence', 0, 'refuse');
                $this->flashError('Sélection non autorisée.');
                $this->retour();
                return;
            }
            Session::set('agence_active_id', 0);
            LogService::enregistrer('agence.changee', 'agence', null, 'succes', ['selection' => 'toutes']);
            $this->flashSuccess('Toutes les agences sont désormais actives.');
            $this->retour();
            return;
        }

        foreach (Gate::agencesAccessibles() as $agence) {
            if ((int)$agence['id'] === $demandee) {
                Session::set('agence_active_id', $demandee);
                LogService::enregistrer('agence.changee', 'agence', $demandee, 'succes', ['nom' => $agence['agence_nom']]);
                $this->flashSuccess('Agence active : ' . (string)$agence['agence_nom'] . '.');
                $this->retour();
                return;
            }
        }

        LogService::enregistrer('agence.changee', 'agence', $demandee, 'refuse');
        $this->flashError('Agence non accessible.');
        $this->retour();
    }

    /** Retour à la page d'origine (referer interne validé), sinon accueil. */
    private function retour(): void
    {
        $retour = '/';
        $referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
        if ($referer !== '') {
            $chemin = (string)(parse_url($referer, PHP_URL_PATH) ?: '');
            $base = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
            if ($chemin !== '' && ($base === '' || strpos($chemin, $base . '/') === 0)) {
                $relatif = $base !== '' && strpos($chemin, $base) === 0 ? substr($chemin, strlen($base)) : $chemin;
                $retour = ($relatif === '' || $relatif === false) ? '/' : $relatif;
            }
        }
        $this->redirect($retour);
    }
}