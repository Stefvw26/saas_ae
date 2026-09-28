<?php
// fichier : app/Controllers/CommentairesController.php — v0.42 (+type eleve)
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\Validate;
use App\Repositories\CommentairesRepository;
use App\Services\Gate;
use App\Services\LogService;
use App\Services\NotificationService;
use Modules\Administration\Repositories\UtilisateursRepository;
use Modules\Centres\Repositories\CentresRepository;
use Modules\Eleves\Repositories\ElevesRepository;
use Modules\Partenaires\Repositories\PartenairesRepository;
use Modules\Prospects\Repositories\ProspectsRepository;
use Modules\Vehicules\Repositories\VehiculesRepository;

/**
 * Commentaires conversationnels (CDC §33) — véhicules (§16), centres (§18),
 * utilisateurs (§14), partenaires, prospects, élèves (J6).
 */
final class CommentairesController extends Controller
{
    private const LIBELLES = [
        'vehicule'    => 'le véhicule',
        'centre'      => 'le centre',
        'utilisateur' => 'l\'utilisateur',
        'partenaire'  => 'le partenaire',
        'prospect'    => 'le prospect',
        'eleve'       => 'l\'élève',
        'dossier'      => 'le dossier',
    ];

    public function store(Request $request): void
    {
        $this->authorize('commentaires.creer');

        [$erreurs, $d] = Validate::check($_POST, [
            'objet_type'    => 'required|in:vehicule,centre,utilisateur,partenaire,prospect,eleve,dossier',
            'objet_id'      => 'required|int',
            'commentaire'   => 'required|max:2000',
            'km'            => 'int',
            'partenaire_id' => 'int',
        ]);

        $type    = (string)$d['objet_type'];
        $objetId = (int)$d['objet_id'];
        $tid     = (int)Gate::user()['tenant_id'];
        $scope   = Gate::scopeAgence();

        $retour = null;
        $libelleObjet = '';
        switch ($type) {
            case 'vehicule':
                $cible = (new VehiculesRepository())->trouver($objetId, $tid, $scope);
                $retour = '/vehicules/' . $objetId . '/modifier';
                $libelleObjet = (string)($cible['immatriculation'] ?? '');
                break;
            case 'centre':
                $cible = (new CentresRepository())->trouver($objetId, $tid);
                $retour = '/centres/' . $objetId . '/modifier';
                $libelleObjet = (string)($cible['nom'] ?? '');
                break;
            case 'utilisateur':
                $cible = (new UtilisateursRepository())->trouver($tid, $scope, $objetId);
                $retour = '/administration/utilisateurs/' . $objetId . '/modifier';
                $libelleObjet = (string)($cible['login'] ?? '');
                break;
            case 'partenaire':
                $cible = (new PartenairesRepository())->trouver($objetId, $tid);
                $retour = '/partenaires/' . $objetId . '/modifier';
                $libelleObjet = (string)($cible['nom'] ?? '');
                break;
            case 'prospect':
                $cible = (new ProspectsRepository())->trouver($objetId, $tid, $scope);
                $retour = '/prospects/' . $objetId;
                $libelleObjet = trim((string)($cible['prenom'] ?? '') . ' ' . (string)($cible['nom'] ?? ''));
                break;
            case 'eleve':
                $cible = (new ElevesRepository())->trouver($objetId, $tid, $scope);
                $retour = '/eleves/' . $objetId;
                $libelleObjet = trim((string)($cible['prenom'] ?? '') . ' ' . (string)($cible['nom'] ?? ''));
                break;
                            case 'dossier':
                $cible = (new \Modules\Dossiers\Repositories\DossiersRepository())
                    ->trouver($objetId, $tid, $scope);
                $retour = '/dossiers/' . $objetId;
                $libelleObjet = '#' . $objetId;
                break;
        }
        if ($cible === null) {
            abort(404, 'Cible introuvable', 'Cet élément n\'existe pas dans votre périmètre.', '/');
        }

        $km = null;
        $partenaireId = null;
        if ($type === 'vehicule') {
            $km = $d['km'] !== null ? (int)$d['km'] : null;
            if ($km !== null && $km < 0) {
                $erreurs['km'] = 'Kilométrage invalide.';
            }
        }
        if ($type === 'centre' && $d['partenaire_id'] !== null) {
            $partenaireId = (int)$d['partenaire_id'];
            if ((new PartenairesRepository())->trouver($partenaireId, $tid) === null) {
                $erreurs['partenaire_id'] = 'Partenaire inconnu.';
            }
        }

        if ($erreurs !== []) {
            Session::flash('errors', $erreurs);
            Session::flash('old', $_POST);
            $this->redirect($retour);
            return;
        }

        $d['tenant_id'] = $tid;
        $id = (new CommentairesRepository())->ajouter($d, (int)Gate::user()['id']);

        LogService::enregistrer('commentaire.cree', $type, $objetId, 'succes', ['commentaire_id' => $id]);

        NotificationService::notifierConversation(
            $type,
            $objetId,
            self::LIBELLES[$type] ?? $type,
            $libelleObjet,
            $retour,
            (int)Gate::user()['id']
        );

        $this->flashSuccess('Commentaire ajouté.');
        $this->redirect($retour);
    }
}