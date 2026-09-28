<?php
// fichier : modules/eleves/Controllers/ElevesController.php — v0.46
declare(strict_types=1);

namespace Modules\Eleves\Controllers;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validate;
use App\Repositories\CommentairesRepository;
use App\Services\Gate;
use App\Services\LogService;
use Modules\Domaines\Repositories\DomainesRepository;
use Modules\Eleves\Repositories\ElevesRepository;
use Modules\TypesPermis\Repositories\TypesPermisRepository;

final class ElevesController extends Controller
{
    private ElevesRepository $eleves;
    private DomainesRepository $domaines;
    private TypesPermisRepository $typesPermis;

    public function __construct()
    {
        $this->eleves     = new ElevesRepository();
        $this->domaines   = new DomainesRepository();
        $this->typesPermis = new TypesPermisRepository();
    }

    /* ---------- Liste (§56) ---------- */

    public function index(Request $request): void
    {
        $this->authorize('eleves.consulter');

        $tid   = (int)Gate::user()['tenant_id'];
        $scope = Gate::scopeAgence();

               $statutsValides = ['actifs', 'archives', 'sans_dossier', 'en_cours', 'valide', 'confirme'];
        $filtres = [
            'q'          => trim((string)$request->get('q', '')),
            'domaine_id' => (string)$request->get('domaine_id', ''),
            'agence_id'  => (string)$request->get('agence_id', ''),
            'statut'     => in_array((string)$request->get('statut', 'actifs'), $statutsValides, true)
                ? (string)$request->get('statut', 'actifs') : 'actifs',
        ];

        /* J7 : les filtres dossier-dépendants utilisent les données réelles. */
        if (in_array($filtres['statut'], ['en_cours', 'valide', 'confirme', 'sans_dossier'], true)) {
            $filtres['statut_dossier'] = $filtres['statut'];
            $filtres['statut'] = 'actifs';
        }

        if ((string)$request->get('export', '') === 'excel') {
            $this->exporterExcel($tid, $scope, $filtres['q']);
            return;
        }

        $total = $this->eleves->compter($tid, $scope, $filtres);
        $pages = max(1, (int)ceil($total / $this->eleves->parPage()));
        $page  = min(max(1, (int)$request->get('page', 1)), $pages);

        $domaines = [];
        foreach ($this->domaines->toutesPourTenant($tid, true) as $d) {
            $domaines[(int)$d['id']] = ['nom' => (string)$d['nom'], 'couleur' => (string)($d['couleur'] ?? '')];
        }

        $this->view('@eleves/index', [
            'title'           => 'Élèves',
            'filtres'         => $filtres,
            'stats'           => $this->eleves->stats($tid, $scope),
            'liste'           => $this->eleves->paginer($tid, $scope, $filtres, $page),
            'optionsDomaines' => $domaines,
            'optionsAgences'  => $scope === null ? Gate::agencesAccessibles() : [],
            'total'           => $total,
            'page'            => $page,
            'pages'           => $pages,
        ]);
    }

    /* ---------- Création ---------- */

    public function create(Request $request): void
    {
        $this->authorize('eleves.creer');
        $this->view('@eleves/form', array_merge($this->options(), [
            'title' => 'Créer un élève',
            'eleve' => null,
        ]));
    }

    public function store(Request $request): void
    {
        $this->authorize('eleves.creer');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];

        [$erreurs, $e] = $this->valider($tid);
        if ($erreurs !== []) {
            Session::flash('errors', $erreurs);
            Session::flash('old', $_POST);
            $this->redirect('/eleves/creer');
            return;
        }

        $id = $this->eleves->creer($e, $tid, (int)$courant['id']);
        LogService::enregistrer('eleve.cree', 'eleve', $id, 'succes', ['nom' => $e['nom']]);
        $this->flashSuccess('Élève « ' . (string)$e['nom'] . ' » créé.');
        $this->redirect('/eleves/' . $id);
    }

       public function fiche(Request $request, string $id): void
    {
        $this->authorize('eleves.consulter');

        $eleve = $this->trouverOuAbandonner((int)$id);
        $tid   = (int)Gate::user()['tenant_id'];

        /* J7 : dossiers + événements réels de l'élève. */
        $dossiers = [];
        $evenements = [];
        if (class_exists(\Modules\Dossiers\Repositories\DossiersRepository::class)) {
            $repoDossiers = new \Modules\Dossiers\Repositories\DossiersRepository();
            $dossiers = $repoDossiers->pourEleve((int)$eleve['id'], $tid);
            $evenements = $repoDossiers->evenementsPourEleve((int)$eleve['id'], $tid);
        }

        $this->view('@eleves/fiche', array_merge($this->options(), [
            'title'        => 'Fiche élève',
            'eleve'        => $eleve,
            'dossiers'     => $dossiers,
            'evenements'   => $evenements,
            'commentaires' => (new CommentairesRepository())->pourObjet('eleve', (int)$eleve['id'], $tid),
            'objetType'    => 'eleve',
            'objetId'      => (int)$eleve['id'],
        ]));
    }

    /* ---------- Modification ---------- */

    public function edit(Request $request, string $id): void
    {
        $this->authorize('eleves.modifier');
        $eleve = $this->trouverOuAbandonner((int)$id);
        $this->view('@eleves/form', array_merge($this->options(), [
            'title' => 'Modifier un élève',
            'eleve' => $eleve,
        ]));
    }

    public function update(Request $request, string $id): void
    {
        $this->authorize('eleves.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $eleve   = $this->trouverOuAbandonner((int)$id);

        [$erreurs, $e] = $this->valider($tid);
        if ($erreurs !== []) {
            Session::flash('errors', $erreurs);
            Session::flash('old', $_POST);
            $this->redirect('/eleves/' . (int)$eleve['id'] . '/modifier');
            return;
        }

        $ancienneAgence = (int)$eleve['agence_id'];
        $this->eleves->modifier((int)$eleve['id'], $tid, $e);

        if ($ancienneAgence !== (int)($e['agence_id'] ?? 0)) {
            LogService::enregistrer('eleve.agence_changee', 'eleve', (int)$eleve['id'], 'succes', [
                'avant' => $ancienneAgence, 'apres' => (int)($e['agence_id'] ?? 0),
            ]);
        }
        LogService::enregistrer('eleve.modifie', 'eleve', (int)$eleve['id'], 'succes', ['nom' => $e['nom']]);
        $this->flashSuccess('Élève « ' . (string)$e['nom'] . ' » modifié.');
        $this->redirect('/eleves/' . (int)$eleve['id']);
    }

    /* ---------- SWITCHES (v0.46 — AJAX, modification directe) ---------- */

    /* ---------- SWITCHES (v0.50 — fix TypeError, repository : void) ---------- */

    /** Switch boîte : POST type_boite = 'BA'|'BM'|'' (vide = aucun). */
    public function majBoite(Request $request, string $id): void
    {
        $this->majSwitch($request, $id, 'type_boite', ['BA', 'BM']);
    }

    /** Switch niveau B : POST type_b = 'B1'..'B5'|''. */
    public function majNiveauB(Request $request, string $id): void
    {
        $this->majSwitch($request, $id, 'type_b', ['B1', 'B2', 'B3', 'B4', 'B5']);
    }

    private function majSwitch(Request $request, string $id, string $champ, array $valeurs): void
    {
        $this->authorize('eleves.modifier');

        $tid   = (int)Gate::user()['tenant_id'];
        $eleve = $this->eleves->trouver((int)$id, $tid, Gate::scopeAgence());
        if ($eleve === null) {
            Response::json(['ok' => false, 'erreur' => 'Élève introuvable.'], 404);
            return;
        }

        $valeur = trim((string)$request->post($champ, ''));
        if ($valeur !== '' && !in_array($valeur, $valeurs, true)) {
            Response::json(['ok' => false, 'erreur' => 'Valeur non autorisée.'], 422);
            return;
        }

        if ($champ === 'type_boite') {
            $this->eleves->majBoite((int)$eleve['id'], $tid, $valeur !== '' ? $valeur : null);
        } else {
            $this->eleves->majNiveauB((int)$eleve['id'], $tid, $valeur !== '' ? $valeur : null);
        }

        LogService::enregistrer('eleve.' . $champ . '_modifie', 'eleve', (int)$eleve['id'], 'succes', [
            'valeur' => $valeur !== '' ? $valeur : 'aucune',
        ]);

        Response::json(['ok' => true, 'valeur' => $valeur]);
    }
    /* ---------- Transfert d'agence ---------- */

    public function transferer(Request $request, string $id): void
    {
        $this->authorize('eleves.agences.transfert');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $eleve   = $this->trouverOuAbandonner((int)$id);

        $choisie = trim((string)$request->post('agence_id', ''));
        $autorisee = false;
        $nomAgence = '';
        foreach (Gate::agencesAccessibles() as $a) {
            if ($choisie === (string)$a['id']) {
                $autorisee = true;
                $nomAgence = (string)$a['agence_nom'];
                break;
            }
        }

        if (!$autorisee || (int)$choisie === (int)$eleve['agence_id']) {
            $this->flashError(!$autorisee
                ? 'Agence non autorisée pour un transfert.'
                : 'L\'élève est déjà rattaché à cette agence.');
            $this->redirect('/eleves/' . (int)$eleve['id']);
            return;
        }

        $avant = (string)($eleve['agence_nom'] ?? '—');
        $this->eleves->transferer((int)$eleve['id'], $tid, (int)$choisie);
        LogService::enregistrer('eleve.transfere', 'eleve', (int)$eleve['id'], 'succes', [
            'avant' => $avant, 'apres' => $nomAgence,
        ]);
        $this->flashSuccess('Élève transféré vers « ' . $nomAgence . ' ».');
        $this->redirect('/eleves/' . (int)$eleve['id']);
    }

    /* ---------- Archivage (§58) ---------- */

    public function archiver(Request $request, string $id): void
    {
        $this->authorize('eleves.supprimer');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $eleve   = $this->trouverOuAbandonner((int)$id);

        $commentaire = trim((string)$request->post('commentaire_archivage', ''));

        $this->eleves->archiver((int)$eleve['id'], $tid, (int)$courant['id']);

                /* J7 : archivage récursif des dossiers de l'élève (§58). */
        if (class_exists(\Modules\Dossiers\Repositories\DossiersRepository::class)) {
            (new \Modules\Dossiers\Repositories\DossiersRepository())
                ->archiverPourEleve((int)$eleve['id'], $tid, (int)$courant['id']);
        }
        
        if ($commentaire !== '') {
            (new CommentairesRepository())->ajouter([
                'tenant_id'   => $tid,
                'objet_type'  => 'eleve',
                'objet_id'    => (int)$eleve['id'],
                'commentaire' => $commentaire,
            ], (int)$courant['id']);
        }

        LogService::enregistrer('eleve.archive', 'eleve', (int)$eleve['id'], 'succes', ['nom' => $eleve['nom']]);
        $this->flashSuccess('Élève « ' . (string)$eleve['nom'] . ' » archivé (consultable dans « Archives »).');
        $this->redirect('/eleves/' . (int)$eleve['id']);
    }

    /* ---------- Suppression LOGIQUE (motif obligatoire) ---------- */

    public function supprimer(Request $request, string $id): void
    {
        $this->authorize('eleves.supprimer');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $eleve   = $this->trouverOuAbandonner((int)$id);

        $motif = trim((string)$request->post('motif_suppression', ''));
        if ($motif === '' || mb_strlen($motif) > 255) {
            $this->flashError('Un motif de suppression est obligatoire (255 caractères maximum).');
            $this->redirect('/eleves/' . (int)$eleve['id']);
            return;
        }

        $this->eleves->supprimerLogique((int)$eleve['id'], $tid, (int)$courant['id'], $motif);

        (new CommentairesRepository())->ajouter([
            'tenant_id'   => $tid,
            'objet_type'  => 'eleve',
            'objet_id'    => (int)$eleve['id'],
            'commentaire' => 'Suppression logique — motif : ' . $motif,
        ], (int)$courant['id']);

        LogService::enregistrer('eleve.supprime', 'eleve', (int)$eleve['id'], 'succes', [
            'nom' => $eleve['nom'], 'type' => 'logique', 'motif' => $motif,
        ]);
        $this->flashSuccess('Élève « ' . (string)$eleve['nom'] . ' » supprimé (suppression logique, motif conservé).');
        $this->redirect('/eleves');
    }

    /* ---------- Helpers ---------- */

    /** @return array<string, mixed> */
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
        $typesPermis = [];
        foreach ($this->typesPermis->toutesPourTenant($tid, true) as $tp) {
            $typesPermis[(int)$tp['id']] = (string)$tp['nom'];
        }

        return [
            'optionsAgences'     => $agences,
            'optionsDomaines'    => $domaines,
            'optionsTypesPermis' => $typesPermis,
        ];
    }

    /** @return array{0: array<string, string>, 1: array<string, ?string>} */
    private function valider(int $tenantId): array
    {
        [$erreurs, $e] = Validate::check($_POST, [
            'civilite'               => 'in:Monsieur,Madame',
            'nom'                    => 'required|max:80',
            'prenom'                 => 'required|max:80',
            'date_naissance'         => 'date',
            'lieu_naissance'         => 'max:120',
            'email'                  => 'email|max:190',
            'telephone'              => 'max:30',
            'adresse'                => 'max:255',
            'code_postal'            => 'max:10',
            'ville'                  => 'max:80',
            'pays'                   => 'max:80',
            'type_boite'             => 'in:BA,BM',
            'type_b'                 => 'in:B1,B2,B3,B4,B5',
            'responsable_nom'        => 'max:80',
            'responsable_telephone' => 'max:30',
            'responsable_email'      => 'email|max:190',
        ]);

        if (!isset($erreurs['domaine_id']) && !empty($_POST['domaine_id'])
            && $this->domaines->trouver((int)$_POST['domaine_id'], $tenantId) === null) {
            $erreurs['domaine_id'] = 'Lieu de préférence inconnu.';
        }
        if (!isset($erreurs['type_permis_id']) && !empty($_POST['type_permis_id'])) {
            $typeConnu = false;
            foreach ($this->typesPermis->toutesPourTenant($tenantId, false) as $tp) {
                if ((int)$tp['id'] === (int)$_POST['type_permis_id']) {
                    $typeConnu = true;
                    break;
                }
            }
            if (!$typeConnu) {
                $erreurs['type_permis_id'] = 'Type de permis inconnu.';
            }
        }

        $e['agence_id'] = null;
        $choisie = trim((string)($_POST['agence_id'] ?? ''));
        if ($choisie !== '') {
            $idsAgences = array_map('strval', array_keys(Gate::agencesAccessibles()));
            if (in_array($choisie, $idsAgences, true)) {
                $e['agence_id'] = (int)$choisie;
            } else {
                $erreurs['agence_id'] = 'Agence non autorisée.';
            }
        }

        $e['domaine_id']     = !empty($_POST['domaine_id']) ? (int)$_POST['domaine_id'] : null;
        $e['type_permis_id'] = !empty($_POST['type_permis_id']) ? (int)$_POST['type_permis_id'] : null;
        $e['provenance_id']  = !empty($_POST['provenance_id']) ? (int)$_POST['provenance_id'] : null;
        $e['actif']          = isset($_POST['actif']) ? 1 : 0;

        return [$erreurs, $e];
    }

    /** @return array<string, mixed> */
    private function trouverOuAbandonner(int $id): array
    {
        $eleve = $this->eleves->trouver($id, (int)Gate::user()['tenant_id'], Gate::scopeAgence());
        if ($eleve === null) {
            abort(404, 'Élève introuvable', 'Cet élève n\'existe pas ou n\'est pas dans votre périmètre.', '/eleves');
        }
        return $eleve;
    }

    /** Export CSV (BOM UTF-8, « ; ») — périmètre = liste (CDC §27/§34). */
    private function exporterExcel(int $tenantId, ?int $scope, string $q): void
    {
        $lignes = $this->eleves->exporter($tenantId, $scope, $q);

        $entetes = ['Nom', 'Prénom', 'Naissance', 'Lieu de naissance', 'Email', 'Téléphone', 'Ville',
                    'Domaine', 'Agence', 'Ajouté le'];
        $cellule = static function ($v): string {
            return '"' . str_replace('"', '""', (string)($v ?? '')) . '"';
        };

        while (ob_get_level() > 0) { @ob_end_clean(); }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="export-eleves-' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');

        echo "\xEF\xBB\xBF";
        echo implode(';', array_map($cellule, $entetes)) . "\r\n";
        foreach ($lignes as $l) {
            echo implode(';', array_map($cellule, [
                $l['nom'], $l['prenom'], $l['date_naissance'], $l['lieu_naissance'],
                $l['email'], $l['telephone'], $l['ville'],
                $l['domaine_nom'], $l['agence_nom'], $l['ajout_le'],
            ])) . "\r\n";
        }
        exit;
    }
}