<?php
// fichier : modules/administration/Controllers/UtilisateursController.php — v0.22
declare(strict_types=1);

namespace Modules\Administration\Controllers;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validate;
use App\Repositories\CommentairesRepository;
use App\Services\Gate;
use App\Services\LogService;
use App\Services\PolitiqueMotDePasse;
use Modules\Administration\Repositories\AgencesRepository;
use Modules\Administration\Repositories\DroitsRepository;
use Modules\Administration\Repositories\UtilisateursRepository;
use RuntimeException;

final class UtilisateursController extends Controller
{
    private const PAR_PAGE = 20;

    private UtilisateursRepository $utilisateurs;
    private AgencesRepository $agences;
    private DroitsRepository $droits;

    public function __construct()
    {
        $this->utilisateurs = new UtilisateursRepository();
        $this->agences      = new AgencesRepository();
        $this->droits       = new DroitsRepository();
    }

    public function index(Request $request): void
    {
        $this->authorize('utilisateurs.consulter');

        $tid   = (int)Gate::user()['tenant_id'];
        $scope = Gate::scopeAgence();

        $filtres = [
            'q'      => trim((string)$request->get('q', '')),
            'role'   => (string)$request->get('role', ''),
            'agence' => (string)$request->get('agence', ''),
            'actif'  => (string)$request->get('actif', ''),
        ];

        $total = $this->utilisateurs->compter($tid, $scope, $filtres);
        $pages = max(1, (int)ceil($total / self::PAR_PAGE));
        $page  = min(max(1, (int)$request->get('page', 1)), $pages);

        $rolesMap = [];
        foreach ($this->droits->roles() as $role) {
            $rolesMap[(string)$role['code']] = (string)$role['nom'];
        }

        $this->view('@administration/utilisateurs/index', [
            'title'        => 'Utilisateurs',
            'filtres'      => $filtres,
            'liste'        => $this->utilisateurs->paginer($tid, $scope, $filtres, $page, self::PAR_PAGE),
            'roles'        => $this->droits->roles(),
            'rolesMap'     => $rolesMap,
            'agences'      => $scope === null ? $this->agences->toutesPourTenant($tid) : [],
            'total'        => $total,
            'page'         => $page,
            'pages'        => $pages,
            'baseUrl'      => '/administration/utilisateurs',
            'politiqueMdp' => PolitiqueMotDePasse::regles(),
        ]);
    }

    public function create(Request $request): void
    {
        $this->authorize('utilisateurs.creer');

        $this->view('@administration/utilisateurs/form', [
            'title'        => 'Créer un utilisateur',
            'utilisateur'  => null,
            'roles'        => $this->rolesAutorises(),
            'agences'      => $this->agencesChoisissables(),
            'permissions'  => Gate::tousDroit() ? $this->droits->permissions() : [],
            'droitsPerso'  => [],
            'politiqueMdp' => PolitiqueMotDePasse::regles(),
        ]);
    }

    public function store(Request $request): void
    {
        $this->authorize('utilisateurs.creer');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];

        [$erreurs, $d] = Validate::check($_POST, [
            'civilite'          => 'in:Monsieur,Madame',
            'nom'               => 'required|max:80',
            'prenom'            => 'required|max:80',
            'login'             => 'login|max:80',
            'mail'              => 'required|email|max:190',
            'telephone'         => 'required|max:30',
            'mot_de_passe'      => 'required|max:100',
            'date_de_naissance' => 'date',
            'couleur'           => 'hex',
            'role'              => 'required|login',
            'agence'            => 'int',
        ]);

        $erreurs = $this->completerValidation($erreurs, $d, $tid, null);

        if ($erreurs !== []) {
            $this->retourFormulaire('/administration/utilisateurs/creer', $erreurs, $_POST);
            return;
        }

        try {
            $photographie = $this->traiterPhotographie($_FILES['photographie'] ?? null, null, false);
        } catch (RuntimeException $e) {
            $this->flashError($e->getMessage());
            Session::flash('old', $_POST);
            $this->redirect('/administration/utilisateurs/creer');
            return;
        }

        $droitsPerso = $this->lireDroitsPersonnalises();

        $id = $this->utilisateurs->creer([
            'login'        => $this->loginFinal,
            'hash'         => password_hash((string)$d['mot_de_passe'], PASSWORD_DEFAULT),
            'mail'         => $d['mail'],
            'telephone'    => $d['telephone'],
            'nom'          => $d['nom'],
            'prenom'       => $d['prenom'],
            'couleur'      => $d['couleur'] ?? '#475569',
            'droit'        => $droitsPerso === [] ? null : json_encode($droitsPerso, JSON_UNESCAPED_UNICODE),
            'role'         => $this->roleFinal,
            'enseignant'   => isset($_POST['est_un_enseignant']) ? 1 : 0,
            'agence'       => $this->agenceFinale,
            'tous_droit'   => (Gate::tousDroit() && isset($_POST['tous_droit'])) ? 1 : 0,
            'photographie' => $photographie,
            'actif'        => isset($_POST['actif']) ? 1 : 0,
            'naissance'    => $d['date_de_naissance'],
            'civilite'     => $d['civilite'],
        ], $tid, (int)$courant['id']);

        LogService::enregistrer('utilisateur.cree', 'utilisateur', $id, 'succes', ['login' => $this->loginFinal]);
        $this->flashSuccess('Utilisateur « ' . $this->loginFinal . ' » créé.');
        $this->redirect('/administration/utilisateurs');
    }

    public function edit(Request $request, string $id): void
    {
        $this->authorize('utilisateurs.modifier');

        $cible = $this->trouverOuAbandonner((int)$id);
        $droitsPerso = json_decode((string)($cible['droit'] ?? ''), true);
        $droitsPerso = is_array($droitsPerso) ? $droitsPerso : [];

        $this->view('@administration/utilisateurs/form', [
            'title'        => 'Modifier un utilisateur',
            'utilisateur'  => $cible,
            'roles'        => $this->rolesAutorises(),
            'agences'      => $this->agencesChoisissables(),
            'permissions'  => Gate::tousDroit() ? $this->droits->permissions() : [],
            'droitsPerso'  => $droitsPerso,
            'politiqueMdp' => PolitiqueMotDePasse::regles(),
            /* Commentaires conversationnels (CDC §14). */
            'commentaires' => (new CommentairesRepository())->pourObjet('utilisateur', (int)$cible['id'], (int)Gate::user()['tenant_id']),
            'objetType'    => 'utilisateur',
            'objetId'      => (int)$cible['id'],
            'avecKm'       => false,
            'optionsPartenaires' => [],
        ]);
    }

    public function update(Request $request, string $id): void
    {
        $this->authorize('utilisateurs.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $cible   = $this->trouverOuAbandonner((int)$id);

        if ((int)$cible['tous_droit'] === 1 && !Gate::tousDroit()) {
            LogService::enregistrer('utilisateur.modifie', 'utilisateur', (int)$cible['id'], 'refuse');
            abort(403, 'Accès refusé', 'Seul un compte « tous droits » peut modifier un administrateur.', '/administration/utilisateurs');
        }

        [$erreurs, $d] = Validate::check($_POST, [
            'civilite'          => 'in:Monsieur,Madame',
            'nom'               => 'required|max:80',
            'prenom'            => 'required|max:80',
            'login'             => 'login|max:80',
            'mail'              => 'required|email|max:190',
            'telephone'         => 'required|max:30',
            'mot_de_passe'      => 'max:100',
            'date_de_naissance' => 'date',
            'couleur'           => 'hex',
            'role'              => 'required|login',
        ]);

        if ($d['mot_de_passe'] !== null && $d['mot_de_passe'] !== '') {
            $erreurMdp = PolitiqueMotDePasse::valider((string)$d['mot_de_passe']);
            if ($erreurMdp !== null) {
                $erreurs['mot_de_passe'] = $erreurMdp;
            }
        }

        $erreurs = $this->completerValidation($erreurs, $d, $tid, (int)$cible['id']);

        if ($erreurs !== []) {
            $this->retourFormulaire('/administration/utilisateurs/' . (int)$cible['id'] . '/modifier', $erreurs, $_POST);
            return;
        }

        /* L'agence de rattachement n'est modifiable que par un compte « tous droits ». */
        $agenceId = (int)$cible['agence'];
        if (Gate::tousDroit()) {
            $idsAgences = array_map(static fn (array $a): int => (int)$a['id'], $this->agencesChoisissables());
            $choisie = trim((string)$request->post('agence', ''));
            if ($choisie === '' || $choisie === '0') {
                $agenceId = 0;
            } elseif (in_array((int)$choisie, $idsAgences, true)) {
                $agenceId = (int)$choisie;
            }
        }

        /* Droits personnalisés : modifiables seulement par un compte « tous droits ». */
        $droitJson = (string)$cible['droit'];
        if (Gate::tousDroit()) {
            $droitsPerso = $this->lireDroitsPersonnalises();
            $droitJson = $droitsPerso === [] ? '' : (string)json_encode($droitsPerso, JSON_UNESCAPED_UNICODE);
        }

        /* Photographie : remplacement / retrait / conservation. */
        try {
            $photographie = $this->traiterPhotographie(
                $_FILES['photographie'] ?? null,
                $cible['photographie'] !== null ? (string)$cible['photographie'] : null,
                isset($_POST['retirer_photographie'])
            );
        } catch (RuntimeException $e) {
            $this->flashError($e->getMessage());
            Session::flash('old', $_POST);
            $this->redirect('/administration/utilisateurs/' . (int)$cible['id'] . '/modifier');
            return;
        }

        $this->utilisateurs->modifier((int)$cible['id'], $tid, [
            'login'      => $this->loginFinal,
            'mail'       => $d['mail'],
            'telephone'  => $d['telephone'],
            'nom'        => $d['nom'],
            'prenom'     => $d['prenom'],
            'couleur'    => $d['couleur'] ?? '#475569',
            'droit'      => $droitJson !== '' ? $droitJson : null,
            'role'       => $this->roleFinal,
            'enseignant' => isset($_POST['est_un_enseignant']) ? 1 : 0,
            'agence'     => $agenceId > 0 ? $agenceId : null,
            'tous_droit' => (Gate::tousDroit() && isset($_POST['tous_droit'])) ? 1 : 0,
            'actif'      => isset($_POST['actif']) ? 1 : 0,
            'naissance'  => $d['date_de_naissance'],
            'civilite'   => $d['civilite'],
        ]);

        if ($photographie !== $cible['photographie']) {
            $this->utilisateurs->modifierPhotographie((int)$cible['id'], $tid, $photographie);
        }
        if ($d['mot_de_passe'] !== null && $d['mot_de_passe'] !== '') {
            $this->utilisateurs->modifierMotDePasse((int)$cible['id'], $tid, password_hash((string)$d['mot_de_passe'], PASSWORD_DEFAULT));
        }

        LogService::enregistrer('utilisateur.modifie', 'utilisateur', (int)$cible['id'], 'succes', ['login' => $this->loginFinal]);
        $this->flashSuccess('Utilisateur « ' . $this->loginFinal . ' » modifié.');
        $this->redirect('/administration/utilisateurs');
    }

    public function delete(Request $request, string $id): void
    {
        $this->authorize('utilisateurs.supprimer');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $cible   = $this->trouverOuAbandonner((int)$id);

        if ((int)$cible['id'] === (int)$courant['id']) {
            $this->flashError('Vous ne pouvez pas supprimer votre propre compte.');
            $this->redirect('/administration/utilisateurs');
            return;
        }
        if ((int)$cible['tous_droit'] === 1 && !Gate::tousDroit()) {
            LogService::enregistrer('utilisateur.supprime', 'utilisateur', (int)$cible['id'], 'refuse');
            abort(403, 'Accès refusé', 'Seul un compte « tous droits » peut supprimer un administrateur.', '/administration/utilisateurs');
        }

        $this->utilisateurs->supprimerLogique((int)$cible['id'], $tid, (int)$courant['id']);
        LogService::enregistrer('utilisateur.supprime', 'utilisateur', (int)$cible['id'], 'succes', ['login' => $cible['login']]);
        $this->flashSuccess('Utilisateur « ' . (string)$cible['login'] . ' » supprimé (archivé).');
        $this->redirect('/administration/utilisateurs');
    }

    /* ---------- Mot de passe : MODALE depuis la liste ---------- */

    public function passwordUpdate(Request $request, string $id): void
    {
        $this->authorize('utilisateurs.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $cible   = $this->trouverOuAbandonner((int)$id);

        if ((int)$cible['tous_droit'] === 1 && !Gate::tousDroit()) {
            LogService::enregistrer('utilisateur.mot_de_passe.modifie', 'utilisateur', (int)$cible['id'], 'refuse');
            abort(403, 'Accès refusé', 'Seul un compte « tous droits » peut modifier le mot de passe d\'un administrateur.', '/administration/utilisateurs');
        }

        $mdp          = (string)$request->post('mot_de_passe', '');
        $confirmation = (string)$request->post('confirmation_mot_de_passe', '');

        $erreurs = [];
        if ($mdp === '') {
            $erreurs['mot_de_passe'] = 'Nouveau mot de passe obligatoire.';
        } else {
            $erreurMdp = PolitiqueMotDePasse::valider($mdp);
            if ($erreurMdp !== null) {
                $erreurs['mot_de_passe'] = $erreurMdp;
            }
        }
        if ($mdp !== $confirmation) {
            $erreurs['confirmation_mot_de_passe'] = 'La confirmation ne correspond pas.';
        }

        if ($erreurs !== []) {
            Session::flash('errors', $erreurs);
            Session::flash('pw_modal', [
                'id'    => (int)$cible['id'],
                'nom'   => trim(($cible['prenom'] ?? '') . ' ' . ($cible['nom'] ?? '')),
                'login' => (string)$cible['login'],
            ]);
            $this->redirect('/administration/utilisateurs');
            return;
        }

        $this->utilisateurs->modifierMotDePasse((int)$cible['id'], $tid, password_hash($mdp, PASSWORD_DEFAULT));
        LogService::enregistrer('utilisateur.mot_de_passe.modifie', 'utilisateur', (int)$cible['id'], 'succes', ['login' => $cible['login']]);
        $this->flashSuccess('Mot de passe de « ' . (string)$cible['login'] . ' » modifié.');
        $this->redirect('/administration/utilisateurs');
    }

    /* ---------- Activation / désactivation depuis la liste ---------- */

    public function toggleActive(Request $request, string $id): void
    {
        $this->authorize('utilisateurs.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $cible   = $this->trouverOuAbandonner((int)$id);

        if ((int)$cible['id'] === (int)$courant['id']) {
            $this->flashError('Vous ne pouvez pas activer ou désactiver votre propre compte.');
            $this->redirect('/administration/utilisateurs');
            return;
        }
        if ((int)$cible['tous_droit'] === 1 && !Gate::tousDroit()) {
            LogService::enregistrer('utilisateur.bascule_actif', 'utilisateur', (int)$cible['id'], 'refuse');
            abort(403, 'Accès refusé', 'Seul un compte « tous droits » peut activer ou désactiver un administrateur.', '/administration/utilisateurs');
        }

        $nouvelEtat = (int)$cible['actif'] === 1 ? 0 : 1;
        $this->utilisateurs->modifierActif((int)$cible['id'], $tid, $nouvelEtat);

        LogService::enregistrer(
            $nouvelEtat === 1 ? 'utilisateur.active' : 'utilisateur.desactive',
            'utilisateur',
            (int)$cible['id'],
            'succes',
            ['login' => $cible['login']]
        );
        $this->flashSuccess('Compte « ' . (string)$cible['login'] . ' » ' . ($nouvelEtat === 1 ? 'activé' : 'désactivé') . '.');
        $this->redirect('/administration/utilisateurs');
    }

    /* ---------- Validation commune ---------- */

    private string $loginFinal = '';
    private string $roleFinal = '';
    private ?int $agenceFinale = null;

    /**
     * @param array<string, string> $erreurs
     * @param array<string, ?string> $d
     * @return array<string, string>
     */
    private function completerValidation(array $erreurs, array $d, int $tenantId, ?int $horsId): array
    {
        /* Rôle : doit exister dans la table roles (système + personnalisés). */
        $this->roleFinal = (string)$d['role'];
        $roleConnu = false;
        foreach ($this->droits->roles() as $role) {
            if ((string)$role['code'] === $this->roleFinal) {
                $roleConnu = true;
                break;
            }
        }
        if (!$roleConnu) {
            $erreurs['role'] = 'Rôle inconnu.';
        } elseif ($this->roleFinal === 'administrateur' && !Gate::tousDroit()) {
            $erreurs['role'] = 'Seul un compte « tous droits » peut définir le rôle administrateur.';
        }

        /* Agence : uniquement celles choisissables (création). */
        if ($horsId === null) {
            $idsAgences = array_map(static fn (array $a): int => (int)$a['id'], $this->agencesChoisissables());
            $this->agenceFinale = $d['agence'] !== null ? (int)$d['agence'] : null;
            if ($this->agenceFinale !== null && !in_array($this->agenceFinale, $idsAgences, true)) {
                $erreurs['agence'] = 'Agence non autorisée.';
            }
        }

        /* Login : auto-généré depuis prénom + nom si vide, puis rendu unique. */
        $login = trim((string)($d['login'] ?? ''));
        if ($login === '') {
            $login = $this->genererLogin((string)$d['prenom'], (string)$d['nom']);
        }
        if ($login === '') {
            $erreurs['login'] = 'Identifiant obligatoire (généré automatiquement à partir du prénom et du nom).';
        } else {
            $base = $login;
            for ($i = 1; $this->utilisateurs->loginExiste($login, $tenantId, $horsId) && $i <= 99; $i++) {
                $login = $base . $i;
            }
            if ($this->utilisateurs->loginExiste($login, $tenantId, $horsId)) {
                $erreurs['login'] = 'Impossible de produire un identifiant unique.';
            }
        }
        $this->loginFinal = $login;

        return $erreurs;
    }

    /** Génère un identifiant « prenom.nom » sans accents ni caractères spéciaux. */
    private function genererLogin(string $prenom, string $nom): string
    {
        $nettoyer = static function (string $s): string {
            $s = trim($s);
            if (function_exists('iconv')) {
                $translit = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
                if (is_string($translit) && $translit !== '') {
                    $s = $translit;
                }
            }
            $s = strtr(mb_strtolower($s), [
                'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a',
                'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
                'î' => 'i', 'ï' => 'i', 'í' => 'i',
                'ô' => 'o', 'ö' => 'o', 'ó' => 'o',
                'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
                'ç' => 'c', 'ñ' => 'n',
            ]);
            $s = preg_replace('#[^a-z0-9]#', '', $s) ?? '';
            return $s;
        };

        $login = trim($nettoyer($prenom) . '.' . $nettoyer($nom), '.');
        return mb_substr($login, 0, 80);
    }

    /**
     * Téléversement sécurisé de la photographie : JPG/PNG/WebP, 2 Mo max,
     * nom aléatoire (32 hex + extension), suppression de l'ancien fichier.
     */
    private function traiterPhotographie(?array $fichier, ?string $ancienne, bool $retirer): ?string
    {
        $repertoire = STORAGE_PATH . '/uploads/utilisateurs';
        if (!is_dir($repertoire)) {
            @mkdir($repertoire, 0775, true);
        }

        $effacer = static function () use ($repertoire, $ancienne): void {
            if ($ancienne !== null && $ancienne !== ''
                && preg_match('#^[a-f0-9]{32}\.(jpg|jpeg|png|webp)$#', $ancienne) === 1) {
                @unlink($repertoire . '/' . $ancienne);
            }
        };

        if ($retirer) {
            $effacer();
            return null;
        }

        if ($fichier === null || (int)($fichier['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $ancienne;
        }
        if ((int)($fichier['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Échec de l\'envoi de la photographie (code ' . (int)$fichier['error'] . ').');
        }
        if ((int)($fichier['size'] ?? 0) > 2097152) {
            throw new RuntimeException('La photographie dépasse 2 Mo.');
        }

        $mime = '';
        if (class_exists('finfo')) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime  = (string)$finfo->file($fichier['tmp_name']);
        } elseif (function_exists('mime_content_type')) {
            $mime = (string)@mime_content_type($fichier['tmp_name']);
        }

        $autorises = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($autorises[$mime])) {
            throw new RuntimeException('Format de photographie non autorisé (JPG, PNG ou WebP uniquement).');
        }

        $nom = bin2hex(random_bytes(16)) . '.' . $autorises[$mime];
        if (!move_uploaded_file($fichier['tmp_name'], $repertoire . '/' . $nom)) {
            throw new RuntimeException('Impossible d\'enregistrer la photographie.');
        }

        $effacer();
        return $nom;
    }

    /* ---------- Helpers ---------- */

    /** @return array<string, mixed> */
    private function trouverOuAbandonner(int $id): array
    {
        $cible = $this->utilisateurs->trouver((int)Gate::user()['tenant_id'], Gate::scopeAgence(), $id);
        if ($cible === null) {
            abort(404, 'Utilisateur introuvable', 'Cet utilisateur n\'existe pas ou n\'est pas dans votre périmètre.', '/administration/utilisateurs');
        }
        return $cible;
    }

    /** @return array<int, array<string, mixed>> */
    private function rolesAutorises(): array
    {
        $roles = $this->droits->roles();
        if (Gate::tousDroit()) {
            return $roles;
        }
        return array_values(array_filter($roles, static fn (array $r): bool => $r['code'] !== 'administrateur'));
    }

    /** @return array<int, array<string, mixed>> */
    private function agencesChoisissables(): array
    {
        $courant = Gate::user();
        if (Gate::tousDroit()) {
            return $this->agences->toutesPourTenant((int)$courant['tenant_id']);
        }
        $propre = $courant['agence'] !== null
            ? $this->agences->trouver((int)$courant['agence'], (int)$courant['tenant_id'])
            : null;
        return $propre !== null ? [$propre] : [];
    }

    /** @return array<string, bool> */
    private function lireDroitsPersonnalises(): array
    {
        if (!Gate::tousDroit() || !is_array($_POST['droit'] ?? null)) {
            return [];
        }
        $codesValides = array_column($this->droits->permissions(), 'code');
        $resultat = [];
        foreach ($_POST['droit'] as $code => $valeur) {
            if (in_array((string)$code, $codesValides, true) && in_array((string)$valeur, ['0', '1'], true)) {
                $resultat[(string)$code] = $valeur === '1';
            }
        }
        return $resultat;
    }

    private function retourFormulaire(string $chemin, array $erreurs, array $anciennes): void
    {
        unset($anciennes['mot_de_passe'], $anciennes['droit']);
        Session::flash('errors', $erreurs);
        Session::flash('old', $anciennes);
        $this->redirect($chemin);
    }
}