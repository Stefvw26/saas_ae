<?php
// fichier : modules/administration/Controllers/DroitsController.php — v0.16
declare(strict_types=1);

namespace Modules\Administration\Controllers;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Services\Gate;
use App\Services\LogService;
use Modules\Administration\Repositories\DroitsRepository;

final class DroitsController extends Controller
{
    private DroitsRepository $repo;

    public function __construct()
    {
        $this->repo = new DroitsRepository();
    }

    public function index(Request $request): void
    {
        $this->authorize('droits.consulter');

        $this->view('@administration/droits/index', [
            'title'       => 'Rôles et droits',
            'roles'       => $this->repo->roles(),
            'permissions' => $this->repo->permissions(),
            'matrice'     => $this->repo->matrice(),
        ]);
    }

    public function save(Request $request): void
    {
        $this->authorize('droits.modifier');

        $roles       = $this->repo->roles();
        $permissions = $this->repo->permissions();

        $coches = is_array($_POST['droit'] ?? null) ? $_POST['droit'] : [];

        $paires = [];
        $idRoleAdmin = null;
        $idPermAdminAcces = null;
        $idPermDroitsModifier = null;

        foreach ($roles as $role) {
            $roleId = (int)$role['id'];
            if ($role['code'] === 'administrateur') {
                $idRoleAdmin = $roleId;
            }
            foreach ($permissions as $permission) {
                $permId = (int)$permission['id'];
                if ($role['code'] === 'administrateur' && $permission['code'] === 'administration.acceder') {
                    $idPermAdminAcces = $permId;
                }
                if ($role['code'] === 'administrateur' && $permission['code'] === 'droits.modifier') {
                    $idPermDroitsModifier = $permId;
                }
                if (!empty($coches[$roleId][$permId])) {
                    $paires[] = [$roleId, $permId];
                }
            }
        }

        /* Anti-verrouillage : l'administrateur conserve l'accès à l'administration
           et à la modification des droits (garde technique documentée). */
        $antiVerrou = [[$idRoleAdmin, $idPermAdminAcces], [$idRoleAdmin, $idPermDroitsModifier]];
        foreach ($antiVerrou as $paire) {
            if ($paire[0] !== null && $paire[1] !== null && !in_array($paire, $paires, true)) {
                $paires[] = $paire;
            }
        }

        $this->repo->remplacerMatrice($paires);

        LogService::enregistrer('droits.modifies', 'roles', null, 'succes', ['paires' => count($paires)]);
        $this->flashSuccess('Matrice des droits enregistrée.');
        $this->redirect('/administration/droits');
    }

    /**
     * Création d'un RÔLE personnalisé (directive utilisateur : « un autre
     * intitulé comme VISITEUR » auquel on attribue ensuite des droits).
     */
    public function storeRole(Request $request): void
    {
        $this->authorize('roles.creer');

        $nom        = trim((string)$request->post('nom', ''));
        $descriptif = trim((string)$request->post('descriptif', ''));

        $erreurs = [];
        if ($nom === '' || mb_strlen($nom) > 50) {
            $erreurs['nom'] = 'Intitulé obligatoire (50 caractères maximum).';
        }
        if (mb_strlen($descriptif) > 255) {
            $erreurs['descriptif'] = 'Descriptif trop long (255 caractères maximum).';
        }

        $code = $nom !== '' ? $this->slugRole($nom) : '';
        if ($code === '') {
            $erreurs['nom'] = 'Impossible de dériver un code technique depuis cet intitulé.';
        } elseif ($this->repo->roleExiste($code, $nom)) {
            $erreurs['nom'] = 'Ce rôle existe déjà (intitulé ou code équivalent).';
        }

        if ($erreurs !== []) {
            Session::flash('errors', $erreurs);
            Session::flash('old', ['nom' => $nom, 'descriptif' => $descriptif]);
            $this->redirect('/administration/droits');
            return;
        }

        $id = $this->repo->creerRole($code, $nom, $descriptif !== '' ? $descriptif : null);

        LogService::enregistrer('role.cree', 'role', $id, 'succes', ['code' => $code, 'nom' => $nom]);
        $this->flashSuccess('Rôle « ' . $nom . ' » créé — attribuez-lui ses droits dans la matrice ci-dessous ; il est déjà disponible dans les fiches utilisateurs.');
        $this->redirect('/administration/droits');
    }

    /** Code technique dérivé de l'intitulé (sans accents, tirets). */
    private function slugRole(string $nom): string
    {
        $s = trim($nom);
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
        $s = preg_replace('#[^a-z0-9]+#', '-', $s) ?? '';
        $s = trim($s, '-');
        return mb_substr($s, 0, 30);
    }
}