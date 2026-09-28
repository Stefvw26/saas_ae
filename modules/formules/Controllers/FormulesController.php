<?php
// fichier : modules/formules/Controllers/FormulesController.php — v0.54
declare(strict_types=1);

namespace Modules\Formules\Controllers;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validate;
use App\Services\Gate;
use App\Services\LogService;
use Modules\Domaines\Repositories\DomainesRepository;
use Modules\Formules\Repositories\FormulesRepository;
use Modules\Prestations\Repositories\PrestationsRepository;

final class FormulesController extends Controller
{
    private FormulesRepository $formules;
    private DomainesRepository $domaines;
    private PrestationsRepository $prestations;

    public function __construct()
    {
        $this->formules   = new FormulesRepository();
        $this->domaines  = new DomainesRepository();
        $this->prestations = new PrestationsRepository();
    }

    public function index(Request $request): void
    {
        $this->authorize('formules.consulter');

        $tid = (int)Gate::user()['tenant_id'];
        $q   = trim((string)$request->get('q', ''));

        $total = $this->formules->compter($tid, $q);
        $pages = max(1, (int)ceil($total / $this->formules->parPage()));
        $page = min(max(1, (int)$request->get('page', 1)), $pages);

        $this->view('@formules/index', [
            'title'   => 'Formules',
            'filtres' => ['q' => $q],
            'liste'   => $this->formules->paginer($tid, $q, $page),
            'total'   => $total,
            'page'    => $page,
            'pages'   => $pages,
        ]);
    }

    public function create(Request $request): void
    {
        $this->authorize('formules.creer');

        $this->view('@formules/form', array_merge($this->options(), [
            'title'              => 'Créer une formule',
            'formule'            => null,
            'prestationsFormule' => [],
            'optionsPrestations' => [],
        ]));
    }

    public function store(Request $request): void
    {
        $this->authorize('formules.creer');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];

        [$erreurs, $d] = Validate::check($_POST, [
            'nom'        => 'required|max:150',
            'descriptif' => 'max:500',
            'domaine_id' => 'int',
        ]);

        if (!isset($erreurs['domaine_id']) && $d['domaine_id'] !== null
            && $this->domaines->trouver((int)$d['domaine_id'], $tid) === null) {
            $erreurs['domaine_id'] = 'Domaine inconnu.';
        }

        if ($erreurs !== []) {
            Session::flash('errors', $erreurs);
            Session::flash('old', $_POST);
            $this->redirect('/formules/creer');
            return;
        }

        $d['actif'] = isset($_POST['actif']) ? 1 : 0;
        $id = $this->formules->creer($d, $tid, (int)$courant['id']);
        LogService::enregistrer('formule.creee', 'formule', $id, 'succes', ['nom' => $d['nom']]);
        $this->flashSuccess('Formule créée — ajoutez maintenant ses prestations.');
        $this->redirect('/formules/' . $id);
    }

    public function fiche(Request $request, string $id): void
    {
        $this->authorize('formules.consulter');

        $formule = $this->trouverOuAbandonner((int)$id);

        $this->view('@formules/form', array_merge($this->options(), $this->optionsPrestations((int)Gate::user()['tenant_id']), [
            'title'              => 'Formule',
            'formule'            => $formule,
            'prestationsFormule' => $this->formules->prestations((int)$formule['id']),
        ]));
    }

    public function edit(Request $request, string $id): void
    {
        $this->authorize('formules.modifier');

        $formule = $this->trouverOuAbandonner((int)$id);

        $this->view('@formules/form', array_merge($this->options(), $this->optionsPrestations((int)Gate::user()['tenant_id']), [
            'title'              => 'Modifier une formule',
            'formule'            => $formule,
            'prestationsFormule' => $this->formules->prestations((int)$formule['id']),
        ]));
    }

    public function update(Request $request, string $id): void
    {
        $this->authorize('formules.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $formule = $this->trouverOuAbandonner((int)$id);

        [$erreurs, $d] = Validate::check($_POST, [
            'nom'        => 'required|max:150',
            'descriptif' => 'max:500',
            'domaine_id' => 'int',
        ]);

        if (!isset($erreurs['domaine_id']) && $d['domaine_id'] !== null
            && $this->domaines->trouver((int)$d['domaine_id'], $tid) === null) {
            $erreurs['domaine_id'] = 'Domaine inconnu.';
        }

        if ($erreurs !== []) {
            Session::flash('errors', $erreurs);
            Session::flash('old', $_POST);
            $this->redirect('/formules/' . (int)$formule['id'] . '/modifier');
            return;
        }

        $d['actif'] = isset($_POST['actif']) ? 1 : 0;
        $this->formules->modifier((int)$formule['id'], $tid, $d);
        LogService::enregistrer('formule.modifiee', 'formule', (int)$formule['id'], 'succes', []);
        $this->flashSuccess('Formule modifiée.');
        $this->redirect('/formules/' . (int)$formule['id']);
    }

    public function delete(Request $request, string $id): void
    {
        $this->authorize('formules.supprimer');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $formule = $this->trouverOuAbandonner((int)$id);

        $this->formules->supprimerLogique((int)$formule['id'], $tid, (int)$courant['id']);
        LogService::enregistrer('formule.supprimee', 'formule', (int)$formule['id'], 'succes', []);
        $this->flashSuccess('Formule supprimée (archivée).');
        $this->redirect('/formules');
    }

    /* ---------- Prestations de la formule (JSON) ---------- */

    public function addPrestation(Request $request, string $id): void
    {
        $this->authorize('formules.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $formule = $this->trouverOuAbandonner((int)$id);

        $prestationId = (int)$request->post('prestation_id', 0);
        $quantite    = max(1, (int)$request->post('quantite', 1));

        $prestation = $this->prestations->trouver($prestationId, $tid);
        if ($prestation === null) {
            Response::json([
                'ok'      => false,
                'erreur'  => 'Prestation inconnue.',
                'message' => 'Prestation inconnue.',
            ], 422);
            return;
        }

        $ajoute = $this->formules->ajouterPrestation((int)$formule['id'], $prestationId, $quantite);
        if (!$ajoute) {
            Response::json([
                'ok'      => false,
                'erreur'  => 'Cette prestation est déjà dans la formule.',
                'message' => 'Cette prestation est déjà dans la formule.',
            ], 422);
            return;
        }

        LogService::enregistrer('formule.prestation_ajoutee', 'formule', (int)$formule['id'], 'succes', ['prestation' => $prestationId]);

        Response::json([
            'ok'      => true,
            'message' => 'Prestation « ' . (string)$prestation['titre'] . ' » ajoutée à la formule.',
        ]);
    }

    public function removePrestation(Request $request, string $pid): void
    {
        $this->authorize('formules.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];

        $formuleId = $this->formules->formuleIdDeLigne((int)$pid, $tid);
        if ($formuleId === null) {
            Response::json([
                'ok'      => false,
                'erreur'  => 'Entrée introuvable.',
                'message' => 'Entrée introuvable.',
            ], 404);
            return;
        }

        $this->formules->retirerPrestationParId((int)$pid);
        LogService::enregistrer('formule.prestation_retiree', 'formule', $formuleId, 'succes', []);

        Response::json([
            'ok'      => true,
            'message' => 'Prestation retirée de la formule.',
        ]);
    }

    public function updateQuantite(Request $request, string $pid): void
    {
        $this->authorize('formules.modifier');

        $tid = (int)Gate::user()['tenant_id'];

        $formuleId = $this->formules->formuleIdDeLigne((int)$pid, $tid);
        if ($formuleId === null) {
            Response::json([
                'ok'      => false,
                'erreur'  => 'Entrée introuvable.',
                'message' => 'Entrée introuvable.',
            ], 404);
            return;
        }

        $quantite = max(1, (int)$request->post('quantite', 1));
        $this->formules->modifierQuantiteParId((int)$pid, $quantite);
        LogService::enregistrer('formule.quantite_modifiee', 'formule', $formuleId, 'succes', ['quantite' => $quantite]);

        Response::json([
            'ok'      => true,
            'quantite' => $quantite,
            'message' => 'Quantité mise à jour.',
        ]);
    }

    /* ---------- Helpers ---------- */

    /** @return array<string, mixed> */
    private function options(): array
    {
        $tid = (int)Gate::user()['tenant_id'];
        $domaines = [];
        foreach ($this->domaines->toutesPourTenant($tid, true) as $d) {
            $domaines[(int)$d['id']] = ['nom' => (string)$d['nom'], 'couleur' => (string)($d['couleur'] ?? '')];
        }
        return ['optionsDomaines' => $domaines];
    }

    /** Prestations actives du tenant (select d'ajout). @return array<string, mixed> */
    private function optionsPrestations(int $tenantId): array
    {
        $liste = [];
        foreach ($this->prestations->toutesPourTenant($tenantId, true) as $p) {
            $liste[] = [
                'id'         => (int)$p['id'],
                'titre'      => (string)$p['titre'],
                'prix_vente' => $p['prix_vente'],
            ];
        }
        return ['optionsPrestations' => $liste];
    }

    /** @return array<string, mixed> */
    private function trouverOuAbandonner(int $id): array
    {
        $formule = $this->formules->trouver($id, (int)Gate::user()['tenant_id']);
        if ($formule === null) {
            abort(404, 'Formule introuvable', 'Cette formule n\'existe pas dans votre périmètre.', '/formules');
        }
        return $formule;
    }
}