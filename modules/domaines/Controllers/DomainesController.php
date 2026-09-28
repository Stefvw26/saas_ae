<?php
// fichier : modules/domaines/Controllers/DomainesController.php
declare(strict_types=1);

namespace Modules\Domaines\Controllers;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validate;
use App\Services\Gate;
use App\Services\LogService;
use Modules\Domaines\Repositories\DomainesRepository;

final class DomainesController extends Controller
{
    private const PAR_PAGE = 20;

    private DomainesRepository $domaines;

    public function __construct()
    {
        $this->domaines = new DomainesRepository();
    }

    public function index(Request $request): void
    {
        $this->authorize('domaines.consulter');

        $tid   = (int)Gate::user()['tenant_id'];
        $total = $this->domaines->compter($tid);
        $pages = max(1, (int)ceil($total / self::PAR_PAGE));
        $page  = min(max(1, (int)$request->get('page', 1)), $pages);

        $this->view('@domaines/index', [
            'title'   => 'Domaines',
            'liste'   => $this->domaines->paginer($tid, $page, self::PAR_PAGE),
            'total'   => $total,
            'page'    => $page,
            'pages'   => $pages,
            'baseUrl' => '/domaines',
        ]);
    }

    public function create(Request $request): void
    {
        $this->authorize('domaines.creer');
        $this->view('@domaines/form', ['title' => 'Créer un domaine', 'domaine' => null]);
    }

    public function store(Request $request): void
    {
        $this->authorize('domaines.creer');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];

        [$erreurs, $d] = Validate::check($_POST, [
            'nom'              => 'required|max:150',
            'initiale'         => 'max:10',
            'descriptif'       => 'max:255',
            'couleur'          => 'hex',
            'nb_echeances_max' => 'required|int',
        ]);

        if ($erreurs !== []) {
            Session::flash('errors', $erreurs);
            Session::flash('old', $_POST);
            $this->redirect('/domaines/creer');
            return;
        }

        $id = $this->domaines->creer($this->donnees($d), $tid, (int)$courant['id']);
        LogService::enregistrer('domaine.cree', 'domaine', $id, 'succes', ['nom' => $d['nom']]);
        $this->flashSuccess('Domaine « ' . (string)$d['nom'] . ' » créé.');
        $this->redirect('/domaines');
    }

    public function edit(Request $request, string $id): void
    {
        $this->authorize('domaines.modifier');
        $cible = $this->trouverOuAbandonner((int)$id);
        $this->view('@domaines/form', ['title' => 'Modifier un domaine', 'domaine' => $cible]);
    }

    public function update(Request $request, string $id): void
    {
        $this->authorize('domaines.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $cible   = $this->trouverOuAbandonner((int)$id);

        [$erreurs, $d] = Validate::check($_POST, [
            'nom'              => 'required|max:150',
            'initiale'         => 'max:10',
            'descriptif'       => 'max:255',
            'couleur'          => 'hex',
            'nb_echeances_max' => 'required|int',
        ]);

        if ($erreurs !== []) {
            Session::flash('errors', $erreurs);
            Session::flash('old', $_POST);
            $this->redirect('/domaines/' . (int)$cible['id'] . '/modifier');
            return;
        }

        $this->domaines->modifier((int)$cible['id'], $tid, $this->donnees($d));
        LogService::enregistrer('domaine.modifie', 'domaine', (int)$cible['id'], 'succes', ['nom' => $d['nom']]);
        $this->flashSuccess('Domaine « ' . (string)$d['nom'] . ' » modifié.');
        $this->redirect('/domaines');
    }

    public function delete(Request $request, string $id): void
    {
        $this->authorize('domaines.supprimer');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $cible   = $this->trouverOuAbandonner((int)$id);

        if ($this->domaines->compterUtilisateurs((int)$cible['id']) > 0) {
            $this->flashError('Des utilisateurs actifs sont rattachés à ce domaine : détachez-les avant suppression.');
            $this->redirect('/domaines');
            return;
        }

        $this->domaines->supprimerLogique((int)$cible['id'], $tid, (int)$courant['id']);
        LogService::enregistrer('domaine.supprime', 'domaine', (int)$cible['id'], 'succes', ['nom' => $cible['nom']]);
        $this->flashSuccess('Domaine « ' . (string)$cible['nom'] . ' » supprimé (archivé).');
        $this->redirect('/domaines');
    }

    /* ---------- Helpers ---------- */

    /** @return array<string, mixed> */
    private function trouverOuAbandonner(int $id): array
    {
        $cible = $this->domaines->trouver($id, (int)Gate::user()['tenant_id']);
        if ($cible === null) {
            abort(404, 'Domaine introuvable', 'Ce domaine n\'existe pas dans votre périmètre.', '/domaines');
        }
        return $cible;
    }

    /** @param array<string, ?string> $d */
    private function donnees(array $d): array
    {
        return [
            'nom'              => $d['nom'],
            'initiale'         => $d['initiale'],
            'descriptif'       => $d['descriptif'],
            'couleur'          => $d['couleur'],
            'nb_echeances_max' => max(1, min(5, (int)$d['nb_echeances_max'])),
            'actif'            => isset($_POST['actif']) ? 1 : 0,
        ];
    }
}