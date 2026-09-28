<?php
// fichier : modules/administration/Controllers/ParametresController.php — v0.16
declare(strict_types=1);

namespace Modules\Administration\Controllers;

use App\Controllers\Controller;
use App\Core\Request;
use App\Services\Gate;
use App\Services\LogService;
use Modules\Administration\Services\ParametreService;

final class ParametresController extends Controller
{
    /** Catégories (directive v0.16 : paramètres classés en onglets). */
    private const CATEGORIES = [
        'listes'         => 'Listes et affichage',
        'securite'       => 'Sécurité et mots de passe',
        'fonctionnalites'=> 'Fonctionnalités',
        'ia'             => 'Intelligence artificielle',
    ];

    /** cle => [categorie, libelle, type] — extensible. */
       /** Clés du CDC §25 + directives utilisateur — libellés et types. Extensible. */
    private const DEFINITIONS = [
        'nb_eleves_par_liste'      => ['listes', 'Nombre d\'élèves par liste', 'entier'],
        'nb_dossiers_cartouche'    => ['listes', 'Nombre de dossiers visibles dans la cartouche fiche élève', 'entier'],

        'mot_de_passe_longueur'    => ['securite', 'Longueur minimale des mots de passe', 'entier'],
        'mot_de_passe_majuscules' => ['securite', 'Exiger des majuscules dans les mots de passe', 'booleen'],
        'mot_de_passe_chiffres'   => ['securite', 'Exiger des chiffres dans les mots de passe', 'booleen'],
        'mot_de_passe_speciaux'  => ['securite', 'Exiger des caractères spéciaux dans les mots de passe', 'booleen'],

        'verifier_anniversaires'   => ['fonctionnalites', 'Vérifier les anniversaires du jour', 'booleen'],
        'activer_phrase_aleatoire' => ['fonctionnalites', 'Activer la phrase d\'ouverture aléatoire', 'booleen'],

        'activer_panier_offert'    => ['panier', 'Activer la mention « Offert » dans les paniers (dossiers)', 'booleen'],
        'activer_panier_cpf'      => ['panier', 'Activer la mention « CPF » dans les paniers (dossiers)', 'booleen'],
        'cpf_montant_zero'        => ['panier', 'Une ligne marquée « CPF » compte pour 0 € dans le panier', 'booleen'],

        'activer_ia'              => ['ia', 'Activer l\'IA', 'booleen'],
        'ia_interne'              => ['ia', 'IA interne', 'booleen'],
        'endpoint_ia'             => ['ia', 'Endpoint IA', 'chaine'],
    ];

    public function index(Request $request): void
    {
        $this->authorize('parametres.consulter');

        $this->view('@administration/parametres/index', [
            'title'      => 'Paramètres',
            'categories' => self::CATEGORIES,
            'groupes'    => $this->groupes(),
            'valeurs'    => ParametreService::tous(),
        ]);
    }

    public function save(Request $request): void
    {
        $this->authorize('parametres.modifier');

        $courant = Gate::user();
        $entrees = [];

        foreach (self::DEFINITIONS as $cle => [$categorie, $libelle, $type]) {
            if ($type === 'booleen') {
                $entrees[$cle] = ['valeur' => isset($_POST[$cle]) ? '1' : '0', 'type' => 'booleen'];
                continue;
            }

            $valeur = trim((string)$request->post($cle, ''));

            if ($type === 'entier') {
                if ($valeur === '' || !ctype_digit($valeur)) {
                    $this->flashError('« ' . $libelle . ' » doit être un nombre entier.');
                    $this->redirect('/administration/parametres');
                    return;
                }
                $minimum = ($cle === 'mot_de_passe_longueur') ? 8 : 1;
                $valeur = (string)max($minimum, (int)$valeur);
            } elseif (mb_strlen($valeur) > 500) {
                $this->flashError('« ' . $libelle . ' » est trop long (500 caractères maximum).');
                $this->redirect('/administration/parametres');
                return;
            }

            $entrees[$cle] = ['valeur' => $valeur, 'type' => $type];
        }

        ParametreService::enregistrer($entrees, (int)$courant['id']);
        LogService::enregistrer('parametres.modifies', 'parametres', null, 'succes', []);
        $this->flashSuccess('Paramètres enregistrés.');
        $this->redirect('/administration/parametres');
    }

    /** @return array<string, array<string, array{0:string, 1:string}>> categorie => cle => [libelle, type] */
    private function groupes(): array
    {
        $groupes = [];
        foreach (self::DEFINITIONS as $cle => [$categorie, $libelle, $type]) {
            $groupes[$categorie][$cle] = [$libelle, $type];
        }
        return $groupes;
    }
}