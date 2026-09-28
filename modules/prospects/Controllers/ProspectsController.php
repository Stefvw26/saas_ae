<?php
// fichier : modules/prospects/Controllers/ProspectsController.php — v0.47
declare(strict_types=1);

namespace Modules\Prospects\Controllers;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validate;
use App\Repositories\CommentairesRepository;
use App\Services\Gate;
use App\Services\LogService;
use App\Services\NotificationService;
use Modules\Administration\Repositories\AgencesRepository;
use Modules\Domaines\Repositories\DomainesRepository;
use Modules\Eleves\Repositories\ElevesRepository;
use Modules\Prospects\Repositories\ProspectsRepository;
use Modules\Prospects\Services\ParcoursService;
use Modules\TypesPermis\Repositories\ParcoursRepository;
use Modules\TypesPermis\Repositories\TypesPermisRepository;

final class ProspectsController extends Controller
{
    private ProspectsRepository $prospects;
    private AgencesRepository $agences;
    private DomainesRepository $domaines;
    private TypesPermisRepository $typesPermis;
    private ParcoursRepository $parcours;

    public function __construct()
    {
        $this->prospects   = new ProspectsRepository();
        $this->agences     = new AgencesRepository();
        $this->domaines    = new DomainesRepository();
        $this->typesPermis = new TypesPermisRepository();
        $this->parcours    = new ParcoursRepository();
    }

    public function index(Request $request): void
    {
        $this->authorize('prospects.consulter');

        $tid   = (int)Gate::user()['tenant_id'];
        $scope = Gate::scopeAgence();

        $filtres = [
            'statut' => (string)$request->get('statut', ''),
            'q'      => trim((string)$request->get('q', '')),
        ];
        if (!in_array($filtres['statut'], ['', 'nouveau', 'traite', 'archive'], true)) {
            $filtres['statut'] = '';
        }

        $total = $this->prospects->compter($tid, $scope, $filtres);
        $pages = max(1, (int)ceil($total / $this->prospects->parPage()));
        $page  = min(max(1, (int)$request->get('page', 1)), $pages);

        $liste = $this->prospects->paginer($tid, $scope, $filtres, $page);
        $ids = array_map(static fn (array $p): int => (int)$p['id'], $liste);
        $commentaires = (new CommentairesRepository())->pourObjets('prospect', $ids, $tid);

        $this->view('@prospects/index', [
            'title'        => 'Prospects',
            'filtres'      => $filtres,
            'stats'        => $this->prospects->stats($tid, $scope),
            'liste'        => $liste,
            'commentaires' => $commentaires,
            'total'        => $total,
            'page'         => $page,
            'pages'        => $pages,
        ]);
    }

    public function create(Request $request): void
    {
        $this->authorize('prospects.creer');

        $this->view('@prospects/form', array_merge($this->options(), [
            'title'           => 'Créer un prospect',
            'prospect'        => null,
            'parcoursValeurs' => [],
        ]));
    }

    public function store(Request $request): void
    {
        $this->authorize('prospects.creer');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];

        [$erreurs, $d] = $this->valider($tid);

        if ($erreurs !== []) {
            Session::flash('errors', $erreurs);
            Session::flash('old', $_POST);
            $this->redirect('/prospects/creer');
            return;
        }

        $d['provenance_id'] = $this->prospects->provenancePassant($tid);
        $d['parcours']      = $this->parcoursEncode($d, $tid);

        $id = $this->prospects->creer($d, $tid, (int)$courant['id']);
        LogService::enregistrer('prospect.cree', 'prospect', $id, 'succes', ['nom' => $d['nom']]);
        $this->flashSuccess('Prospect « ' . (string)$d['nom'] . ' » créé (provenance : Passant).');
        $this->redirect('/prospects/' . $id);
    }

    public function fiche(Request $request, string $id): void
    {
        $this->authorize('prospects.consulter');

        $prospect = $this->trouverOuAbandonner((int)$id);
        $tid      = (int)Gate::user()['tenant_id'];

        $parcoursReponses = json_decode((string)($prospect['parcours'] ?? ''), true);
        $parcoursReponses = is_array($parcoursReponses) ? $parcoursReponses : [];

        $this->view('@prospects/fiche', array_merge($this->options(), [
            'title'            => 'Fiche prospect',
            'prospect'         => $prospect,
            'parcoursLibelles' => ParcoursService::libelles(
                $prospect['type_permis_id'] !== null ? (int)$prospect['type_permis_id'] : null,
                $tid,
                $parcoursReponses
            ),
            'commentaires'     => (new CommentairesRepository())->pourObjet('prospect', (int)$prospect['id'], $tid),
            'objetType'        => 'prospect',
            'objetId'          => (int)$prospect['id'],
        ]));
    }

    public function edit(Request $request, string $id): void
    {
        $this->authorize('prospects.modifier');

        $prospect = $this->trouverOuAbandonner((int)$id);
        if ((string)$prospect['statut'] === 'archive') {
            $this->flashError('Ce prospect est archivé : désarchivage non prévu (statut conservé).');
            $this->redirect('/prospects/' . (int)$prospect['id']);
            return;
        }

        $parcoursValeurs = json_decode((string)($prospect['parcours'] ?? ''), true);
        $parcoursValeurs = is_array($parcoursValeurs) ? $parcoursValeurs : [];

        $this->view('@prospects/form', array_merge($this->options(), [
            'title'           => 'Modifier un prospect',
            'prospect'        => $prospect,
            'parcoursValeurs' => $parcoursValeurs,
        ]));
    }

    public function update(Request $request, string $id): void
    {
        $this->authorize('prospects.modifier');

        $courant  = Gate::user();
        $tid      = (int)$courant['tenant_id'];
        $prospect = $this->trouverOuAbandonner((int)$id);

        [$erreurs, $d] = $this->valider($tid);

        if ($erreurs !== []) {
            Session::flash('errors', $erreurs);
            Session::flash('old', $_POST);
            $this->redirect('/prospects/' . (int)$prospect['id'] . '/modifier');
            return;
        }

        $d['parcours'] = $this->parcoursEncode($d, $tid);

        $this->prospects->modifier((int)$prospect['id'], $tid, $d);
        LogService::enregistrer('prospect.modifie', 'prospect', (int)$prospect['id'], 'succes', ['nom' => $d['nom']]);
        $this->flashSuccess('Prospect « ' . (string)$d['nom'] . ' » modifié.');
        $this->redirect('/prospects/' . (int)$prospect['id']);
    }

    public function traiter(Request $request, string $id): void
    {
        $this->authorize('prospects.traiter');

        $courant  = Gate::user();
        $tid      = (int)$courant['tenant_id'];
        $prospect = $this->trouverOuAbandonner((int)$id);

        if ((string)$prospect['statut'] === 'archive') {
            $this->flashError('Un prospect archivé ne peut pas être traité.');
            $this->redirect('/prospects/' . (int)$prospect['id']);
            return;
        }

        $this->prospects->traiter((int)$prospect['id'], $tid);

        $commentaire = trim((string)$request->post('commentaire_traitement', ''));
        if ($commentaire !== '') {
            (new CommentairesRepository())->ajouter([
                'tenant_id'   => $tid,
                'objet_type'  => 'prospect',
                'objet_id'    => (int)$prospect['id'],
                'commentaire' => $commentaire,
            ], (int)$courant['id']);
            NotificationService::notifierConversation(
                'prospect',
                (int)$prospect['id'],
                'le prospect',
                trim((string)($prospect['prenom'] ?? '') . ' ' . (string)$prospect['nom']),
                '/prospects/' . (int)$prospect['id'],
                (int)$courant['id']
            );
        }

        LogService::enregistrer('prospect.traite', 'prospect', (int)$prospect['id'], 'succes', []);
        $this->flashSuccess('Prospect marqué comme traité.');
        $this->redirect('/prospects/' . (int)$prospect['id']);
    }

    public function archiver(Request $request, string $id): void
    {
        $this->authorize('prospects.archiver');

        $courant  = Gate::user();
        $tid      = (int)$courant['tenant_id'];
        $prospect = $this->trouverOuAbandonner((int)$id);

        $motif = trim((string)$request->post('motif_archivage', ''));
        if ($motif === '' || mb_strlen($motif) > 255) {
            $this->flashError('Un commentaire d\'archivage est obligatoire (255 caractères maximum).');
            $this->redirect('/prospects/' . (int)$prospect['id']);
            return;
        }

        $this->prospects->archiver((int)$prospect['id'], $tid, $motif);
        (new CommentairesRepository())->ajouter([
            'tenant_id'   => $tid,
            'objet_type'  => 'prospect',
            'objet_id'    => (int)$prospect['id'],
            'commentaire' => $motif,
        ], (int)$courant['id']);

        LogService::enregistrer('prospect.archive', 'prospect', (int)$prospect['id'], 'succes', ['motif' => $motif]);
        $this->flashSuccess('Prospect archivé.');
        $this->redirect('/prospects/' . (int)$prospect['id']);
    }

    public function convertir(Request $request, string $id): void
    {
        $this->authorize('prospects.convertir');

        $courant  = Gate::user();
        $tid      = (int)$courant['tenant_id'];
        $prospect = $this->trouverOuAbandonner((int)$id);

        if ((int)$prospect['convertis'] === 1) {
            $this->flashError('Ce prospect est déjà marqué comme converti.');
            $this->redirect('/prospects/' . (int)$prospect['id']);
            return;
        }

        $eleveId = (new ElevesRepository())->creer([
            'agence_id'      => $prospect['agence_id'] !== null ? (int)$prospect['agence_id'] : null,
            'domaine_id'     => $prospect['domaine_id'] !== null ? (int)$prospect['domaine_id'] : null,
            'provenance_id'  => $prospect['provenance_id'] !== null ? (int)$prospect['provenance_id'] : null,
            'type_permis_id' => $prospect['type_permis_id'] !== null ? (int)$prospect['type_permis_id'] : null,
            'civilite'       => null,
            'nom'            => (string)$prospect['nom'],
            'prenom'         => $prospect['prenom'],
            'date_naissance' => $prospect['date_naissance'],
            'lieu_naissance' => $prospect['lieu_naissance'],
            'email'          => $prospect['email'],
            'telephone'      => $prospect['telephone'],
            'adresse'        => $prospect['adresse'],
            'code_postal'    => $prospect['code_postal'],
            'ville'          => $prospect['ville'],
            'pays'           => $prospect['pays'],
            'type_boite'     => null,
            'type_b'         => null,
            'actif'          => 1,
        ], $tid, (int)$courant['id']);

        $convention = 'Prospect converti en élève — fiche élève n°' . $eleveId . '.';
        $this->prospects->convertirEtArchiver((int)$prospect['id'], $tid, (int)$courant['id'], $convention);

        (new CommentairesRepository())->ajouter([
            'tenant_id'   => $tid,
            'objet_type'  => 'prospect',
            'objet_id'    => (int)$prospect['id'],
            'commentaire' => $convention,
        ], (int)$courant['id']);

        NotificationService::notifierConversation(
            'prospect',
            (int)$prospect['id'],
            'le prospect',
            trim((string)($prospect['prenom'] ?? '') . ' ' . (string)$prospect['nom']),
            '/prospects/' . (int)$prospect['id'],
            (int)$courant['id']
        );

        LogService::enregistrer('prospect.converti', 'prospect', (int)$prospect['id'], 'succes', ['eleve_id' => $eleveId]);
        LogService::enregistrer('eleve.cree_conversion', 'eleve', $eleveId, 'succes', ['prospect' => (int)$prospect['id']]);

        $this->flashSuccess('Prospect converti — fiche élève n°' . $eleveId . ' créée (le prospect est archivé et n\'apparaît plus dans la liste).');
        $this->redirect('/eleves/' . $eleveId);
    }

    /* ---------- Helpers ---------- */

    /** @return array<string, mixed> options + questions de parcours PAR TYPE (pré-rendu). */
    private function options(): array
    {
        $tid = (int)Gate::user()['tenant_id'];

        $agences = [];
        foreach (Gate::agencesAccessibles() as $a) {
            $agences[(int)$a['id']] = (string)$a['agence_nom'];
        }

        $domaines = [];
        foreach ($this->domaines->toutesPourTenant($tid, true) as $d) {
            $domaines[(int)$d['id']] = ['nom' => (string)$d['nom'], 'couleur' => (string)($d['couleur'] ?? '')];
        }

        $typesRows = $this->typesPermis->toutesPourTenant($tid, false);
        $typesPermis = [];
        foreach ($typesRows as $tp) {
            $typesPermis[] = [
                'id'         => (int)$tp['id'],
                'nom'        => (string)$tp['nom'],
                'initiale'   => (string)($tp['initiale'] ?? ''),
                'descriptif' => (string)($tp['descriptif'] ?? ''),
            ];
        }

        $parcoursParType = $this->parcours->toutesPourTenant(
            $tid,
            array_map(static fn (array $tp): int => (int)$tp['id'], $typesRows)
        );

        return [
            'optionsAgences'     => $agences,
            'optionsDomaines'    => $domaines,
            'optionsTypesPermis' => $typesPermis,
            'parcoursParType'    => $parcoursParType,
        ];
    }

    private function parcoursEncode(array $d, int $tenantId): ?string
    {
        $typePermisId = $d['type_permis_id'] !== null ? (int)$d['type_permis_id'] : null;
        $brut = is_array($_POST['parcours'] ?? null) ? $_POST['parcours'] : [];
        $nettoye = ParcoursService::nettoyer($brut, $typePermisId, $tenantId);
        return $nettoye !== null ? json_encode($nettoye, JSON_UNESCAPED_UNICODE) : null;
    }

    /** @return array{0: array<string, string>, 1: array<string, ?string>} */
    private function valider(int $tenantId): array
    {
        [$erreurs, $d] = Validate::check($_POST, [
            'nom'            => 'required|max:80',
            'prenom'         => 'required|max:80',
            'date_naissance' => 'date',
            'lieu_naissance' => 'max:120',
            'email'          => 'required|email|max:190',
            'telephone'      => 'required|max:30',
            'adresse'        => 'max:255',
            'code_postal'    => 'max:10',
            'ville'          => 'max:80',
            'pays'           => 'max:80',
            'domaine_id'     => 'int',
            'type_permis_id' => 'int',
            'commentaire'    => 'max:2000',
        ]);

        if (!isset($erreurs['domaine_id']) && $d['domaine_id'] !== null
            && $this->domaines->trouver((int)$d['domaine_id'], $tenantId) === null) {
            $erreurs['domaine_id'] = 'Lieu de préférence inconnu.';
        }
        if (!isset($erreurs['type_permis_id']) && $d['type_permis_id'] !== null) {
            $typeConnu = false;
            foreach ($this->typesPermis->toutesPourTenant($tenantId, false) as $tp) {
                if ((int)$tp['id'] === (int)$d['type_permis_id']) {
                    $typeConnu = true;
                    break;
                }
            }
            if (!$typeConnu) {
                $erreurs['type_permis_id'] = 'Type de permis inconnu.';
            }
        }

        $d['agence_id'] = null;
        $choisie = trim((string)($_POST['agence_id'] ?? ''));
        if ($choisie !== '') {
            $idsAgences = array_map('strval', array_keys(Gate::agencesAccessibles()));
            if (in_array($choisie, $idsAgences, true)) {
                $d['agence_id'] = (int)$choisie;
            } else {
                $erreurs['agence_id'] = 'Agence non autorisée.';
            }
        }

        $d['commentaire'] = $d['commentaire'] !== null ? $d['commentaire'] : null;
        return [$erreurs, $d];
    }

    /** @return array<string, mixed> */
    private function trouverOuAbandonner(int $id): array
    {
        $prospect = $this->prospects->trouver($id, (int)Gate::user()['tenant_id'], Gate::scopeAgence());
        if ($prospect === null) {
            abort(404, 'Prospect introuvable', 'Ce prospect n\'existe pas dans votre périmètre.', '/prospects');
        }
        return $prospect;
    }
}