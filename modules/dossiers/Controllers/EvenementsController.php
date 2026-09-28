<?php
// fichier : modules/dossiers/Controllers/EvenementsController.php — Jalon 7
declare(strict_types=1);

namespace Modules\Dossiers\Controllers;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Validate;
use App\Services\Gate;
use App\Services\LogService;
use Modules\Dossiers\Repositories\DossiersRepository;
use Modules\Dossiers\Repositories\EvenementsRepository;

final class EvenementsController extends Controller
{
    private EvenementsRepository $evenements;
    private DossiersRepository $dossiers;

    public function __construct()
    {
        $this->evenements = new EvenementsRepository();
        $this->dossiers   = new DossiersRepository();
    }

    public function store(Request $request, string $id): void
    {
        $this->authorize('dossiers.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $dossier = $this->dossiers->trouver((int)$id, $tid, Gate::scopeAgence());
        if ($dossier === null) {
            abort(404, 'Dossier introuvable', 'Ce dossier n\'existe pas dans votre périmètre.', '/eleves');
        }

        [$erreurs, $e] = Validate::check($_POST, [
            'titre'        => 'required|max:255',
            'date_heure'  => 'required|max:19',
            'duree_minutes' => 'int',
        ]);

        /* Format date/heure : datetime-local (YYYY-MM-DDTHH:MM) → SQL. */
        $dateHeure = str_replace('T', ' ', (string)($_POST['date_heure'] ?? ''));
        if ($dateHeure !== '' && !preg_match('#^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$#', $dateHeure)) {
            $erreurs['date_heure'] = 'Date/heure invalide.';
        }

        if ($erreurs !== []) {
            Session::flash('errors', $erreurs);
            Session::flash('old', $_POST);
            $this->redirect('/dossiers/' . (int)$dossier['id']);
            return;
        }

        $e['dossier_id']     = (int)$dossier['id'];
        $e['date_heure']    = $dateHeure . (strlen($dateHeure) === 16 ? ':00' : '');
        $e['type']          = trim((string)$request->post('type', '')) ?: null;
        $e['duree_minutes']= $e['duree_minutes'] !== null ? (int)$e['duree_minutes'] : null;
        $e['moniteur_id']  = !empty($_POST['moniteur_id']) ? (int)$_POST['moniteur_id'] : null;
        $e['vehicule_id']  = !empty($_POST['vehicule_id']) ? (int)$_POST['vehicule_id'] : null;
        $e['notes']        = trim((string)$request->post('notes', '')) ?: null;

        $id = $this->evenements->creer($e, $tid, (int)$courant['id']);
        LogService::enregistrer('evenement.cree', 'evenement', $id, 'succes', ['dossier' => (int)$dossier['id']]);

        $this->flashSuccess('Événement ajouté au dossier.');
        $this->redirect('/dossiers/' . (int)$dossier['id']);
    }

    public function delete(Request $request, string $id): void
    {
        $this->authorize('dossiers.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];

        $evenement = $this->evenements->trouver((int)$id, $tid);
        if ($evenement === null) {
            abort(404, 'Événement introuvable', 'Cet événement n\'existe pas ou n\'est pas dans votre périmètre.', '/eleves');
        }
        $dossier = $this->dossiers->trouver((int)$evenement['dossier_id'], $tid, Gate::scopeAgence());

        $this->evenements->supprimerLogique((int)$id, $tid, (int)$courant['id']);
        LogService::enregistrer('evenement.supprime', 'evenement', (int)$id, 'succes', []);
        $this->flashSuccess('Événement supprimé (archivé).');
        $this->redirect($dossier !== null ? '/dossiers/' . (int)$dossier['id'] : '/eleves');
    }
}