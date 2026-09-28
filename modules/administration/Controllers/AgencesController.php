<?php
// fichier : modules/administration/Controllers/AgencesController.php — v0.16
declare(strict_types=1);

namespace Modules\Administration\Controllers;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validate;
use App\Services\Gate;
use App\Services\LogService;
use Modules\Administration\Repositories\AgencesRepository;
use Modules\Administration\Repositories\UtilisateursRepository;

final class AgencesController extends Controller
{
    private const PAR_PAGE = 20;

    private AgencesRepository $agences;
    private UtilisateursRepository $utilisateurs;

    public function __construct()
    {
        $this->agences      = new AgencesRepository();
        $this->utilisateurs = new UtilisateursRepository();
    }

    public function index(Request $request): void
    {
        $this->authorize('agences.consulter');

        $tid   = (int)Gate::user()['tenant_id'];
        $scope = Gate::scopeAgence();

        $total = $this->agences->compter($tid, $scope);
        $pages = max(1, (int)ceil($total / self::PAR_PAGE));
        $page  = min(max(1, (int)$request->get('page', 1)), $pages);

        $this->view('@administration/agences/index', [
            'title'   => 'Agences',
            'liste'   => $this->agences->paginer($tid, $scope, $page, self::PAR_PAGE),
            'total'   => $total,
            'page'    => $page,
            'pages'   => $pages,
            'baseUrl' => '/administration/agences',
        ]);
    }

    public function create(Request $request): void
    {
        $this->authorize('agences.creer');
        $this->view('@administration/agences/form', ['title' => 'Créer une agence', 'agence' => null]);
    }

    public function store(Request $request): void
    {
        $this->authorize('agences.creer');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];

        [$erreurs, $d] = Validate::check($_POST, [
            'agence_nom'                  => 'required|max:150',
            'agence_initiale'             => 'max:10',
            'agence_adresse'              => 'max:255',
            'agence_telephone'            => 'max:30',
            'agence_agreement'            => 'max:100',
            'agence_agreement_date'       => 'date',
            'agence_agreement_exploitant' => 'max:150',
            'agence_assurance'            => 'max:150',
            'agence_couleur'              => 'hex',
            'latitude'                    => 'decimal',
            'longitude'                   => 'decimal',
        ]);

        if ($erreurs !== []) {
            Session::flash('errors', $erreurs);
            Session::flash('old', $_POST);
            $this->redirect('/administration/agences/creer');
            return;
        }

        $id = $this->agences->creer($this->donnees($d), $tid, (int)$courant['id']);
        LogService::enregistrer('agence.creee', 'agence', $id, 'succes', ['nom' => $d['agence_nom']]);
        $this->flashSuccess('Agence « ' . (string)$d['agence_nom'] . ' » créée.');
        $this->redirect('/administration/agences');
    }

    public function edit(Request $request, string $id): void
    {
        $this->authorize('agences.modifier');
        $cible = $this->trouverOuAbandonner((int)$id);
        $this->view('@administration/agences/form', ['title' => 'Modifier une agence', 'agence' => $cible]);
    }

    public function update(Request $request, string $id): void
    {
        $this->authorize('agences.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $cible   = $this->trouverOuAbandonner((int)$id);

        /* Le directeur modifie uniquement SON agence (CDC §13). */
        if (!Gate::tousDroit() && (int)$cible['id'] !== (int)$courant['agence']) {
            LogService::enregistrer('agence.modifiee', 'agence', (int)$cible['id'], 'refuse');
            abort(403, 'Accès refusé', 'Vous ne pouvez modifier que votre propre agence.', '/administration/agences');
        }

        [$erreurs, $d] = Validate::check($_POST, [
            'agence_nom'                  => 'required|max:150',
            'agence_initiale'             => 'max:10',
            'agence_adresse'              => 'max:255',
            'agence_telephone'            => 'max:30',
            'agence_agreement'            => 'max:100',
            'agence_agreement_date'       => 'date',
            'agence_agreement_exploitant' => 'max:150',
            'agence_assurance'            => 'max:150',
            'agence_couleur'              => 'hex',
            'latitude'                    => 'decimal',
            'longitude'                   => 'decimal',
        ]);

        if ($erreurs !== []) {
            Session::flash('errors', $erreurs);
            Session::flash('old', $_POST);
            $this->redirect('/administration/agences/' . (int)$cible['id'] . '/modifier');
            return;
        }

        $this->agences->modifier((int)$cible['id'], $tid, $this->donnees($d));
        LogService::enregistrer('agence.modifiee', 'agence', (int)$cible['id'], 'succes', ['nom' => $d['agence_nom']]);
        $this->flashSuccess('Agence « ' . (string)$d['agence_nom'] . ' » modifiée.');
        $this->redirect('/administration/agences');
    }

    public function delete(Request $request, string $id): void
    {
        $this->authorize('agences.supprimer');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $cible   = $this->trouverOuAbandonner((int)$id);

        /* Garde d'intégrité : refuser si des utilisateurs actifs y sont rattachés. */
        if ($this->utilisateurs->compterPourAgence((int)$cible['id']) > 0) {
            $this->flashError('Des utilisateurs actifs sont rattachés à cette agence : reprenez-les avant suppression.');
            $this->redirect('/administration/agences');
            return;
        }

        $this->agences->supprimerLogique((int)$cible['id'], $tid, (int)$courant['id']);
        LogService::enregistrer('agence.supprimee', 'agence', (int)$cible['id'], 'succes', ['nom' => $cible['agence_nom']]);
        $this->flashSuccess('Agence « ' . (string)$cible['agence_nom'] . ' » supprimée (archivée).');
        $this->redirect('/administration/agences');
    }

    /* ---------- Helpers ---------- */

    /** @return array<string, mixed> */
    private function trouverOuAbandonner(int $id): array
    {
        $cible = $this->agences->trouver($id, (int)Gate::user()['tenant_id']);
        if ($cible === null) {
            abort(404, 'Agence introuvable', 'Cette agence n\'existe pas dans votre périmètre.', '/administration/agences');
        }
        return $cible;
    }

    /** @param array<string, ?string> $d */
    private function donnees(array $d): array
    {
        return [
            'agence_nom'                  => $d['agence_nom'],
            'agence_initiale'             => $d['agence_initiale'],
            'agence_adresse'              => $d['agence_adresse'],
            'agence_telephone'            => $d['agence_telephone'],
            'agence_agreement'            => $d['agence_agreement'],
            'agence_agreement_date'       => $d['agence_agreement_date'],
            'agence_agreement_exploitant' => $d['agence_agreement_exploitant'],
            'agence_assurance'            => $d['agence_assurance'],
            'agence_couleur'              => $d['agence_couleur'] ?? '#2563eb',
            'latitude'                    => $d['latitude'],
            'longitude'                   => $d['longitude'],
            'actif'                       => isset($_POST['actif']) ? 1 : 0,
        ];
    }
}