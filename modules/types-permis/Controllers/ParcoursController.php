<?php
// fichier : modules/types-permis/Controllers/ParcoursController.php — v0.41
declare(strict_types=1);

namespace Modules\TypesPermis\Controllers;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validate;
use App\Services\Gate;
use App\Services\LogService;
use Modules\TypesPermis\Repositories\ParcoursRepository;

/**
 * Générateur de parcours (v0.41) :
 *  - questions (créer/modifier/supprimer, options) ;
 *  - RÈGLES « On affiche la question X si la réponse est égale à V » :
 *    définies SUR LA QUESTION DÉCLENCHEUSE (parent) — storeRegle/removeRegle.
 * Le conditionnement n'est plus édité depuis la question dépendante.
 */
final class ParcoursController extends Controller
{
    private ParcoursRepository $parcours;

    private const TYPES_REPONSE = ['oui_non', 'choix', 'nombre', 'texte'];

    public function __construct()
    {
        $this->parcours = new ParcoursRepository();
    }

    public function storeQuestion(Request $request): void
    {
        $this->authorize('types-permis.parcours');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];

        [$erreurs, $d] = Validate::check($_POST, [
            'type_permis_id' => 'required|int',
            'libelle'        => 'required|max:500',
            'type_reponse'   => 'required|in:' . implode(',', self::TYPES_REPONSE),
            'position'       => 'int',
        ]);

        $options = [];
        if (($d['type_reponse'] ?? '') === 'choix' && is_array($_POST['opt_valeur'] ?? null)) {
            $valeurs  = $_POST['opt_valeur'];
            $libelles = $_POST['opt_libelle'] ?? [];
            $vues = [];
            foreach ($valeurs as $i => $valeur) {
                $valeur  = trim((string)$valeur);
                $libelle = trim((string)($libelles[$i] ?? ''));
                if ($valeur === '' || $libelle === '') {
                    continue;
                }
                if (in_array($valeur, $vues, true)) {
                    $erreurs['options'] = 'Valeur d\'option en doublon : ' . $valeur . '.';
                    break;
                }
                $vues[] = $valeur;
                $options[] = [$valeur, $libelle];
            }
            if ($options === []) {
                $erreurs['options'] = 'Une question de type « choix » exige au moins une option.';
            }
        }

        if ($erreurs !== []) {
            Session::flash('errors', $erreurs);
            Session::flash('old', $_POST);
            $this->redirect('/types-permis/' . (int)$d['type_permis_id'] . '/modifier');
            return;
        }

        $id = $this->parcours->creerQuestion($d, $tid, (int)$courant['id'], $options);
        LogService::enregistrer('parcours.question.creee', 'parcours_question', $id, 'succes', ['libelle' => $d['libelle']]);
        $this->flashSuccess('Question ajoutée au parcours.');
        $this->redirect('/types-permis/' . (int)$d['type_permis_id'] . '/modifier');
    }

    public function updateQuestion(Request $request, string $id): void
    {
        $this->authorize('types-permis.parcours');

        $tid      = (int)Gate::user()['tenant_id'];
        $question = $this->parcours->trouverQuestion((int)$id, $tid);
        if ($question === null) {
            abort(404, 'Question introuvable', 'Cette question n\'existe pas dans votre périmètre.', '/types-permis');
        }

        [$erreurs, $d] = Validate::check($_POST, [
            'libelle'      => 'required|max:500',
            'type_reponse' => 'required|in:' . implode(',', self::TYPES_REPONSE),
            'position'     => 'int',
        ]);

        if ($erreurs !== []) {
            Session::flash('errors', $erreurs);
            Session::flash('old', $_POST);
            $this->redirect('/types-permis/' . (int)$question['type_permis_id'] . '/modifier');
            return;
        }

        /* Le conditionnement n'est PAS modifié ici (règles parent — v0.41). */
        $this->parcours->modifierQuestion((int)$id, $tid, $d);
        LogService::enregistrer('parcours.question.modifiee', 'parcours_question', (int)$id, 'succes', []);
        $this->flashSuccess('Question modifiée.');
        $this->redirect('/types-permis/' . (int)$question['type_permis_id'] . '/modifier');
    }

    public function deleteQuestion(Request $request, string $id): void
    {
        $this->authorize('types-permis.parcours');

        $courant  = Gate::user();
        $tid      = (int)$courant['tenant_id'];
        $question = $this->parcours->trouverQuestion((int)$id, $tid);
        if ($question === null) {
            abort(404, 'Question introuvable', 'Cette question n\'existe pas dans votre périmètre.', '/types-permis');
        }

        /* Supprime la question ET détache les enfants conditionnés sur elle. */
        $this->parcours->supprimerQuestionLogique((int)$id, $tid, (int)$courant['id']);
        LogService::enregistrer('parcours.question.supprimee', 'parcours_question', (int)$id, 'succes', []);
        $this->flashSuccess('Question supprimée (archivée) — les règles qui l\'utilisaient ont été retirées.');
        $this->redirect('/types-permis/' . (int)$question['type_permis_id'] . '/modifier');
    }

    /* ---------- OPTIONS ---------- */

    public function storeOption(Request $request, string $id): void
    {
        $this->authorize('types-permis.parcours');

        $tid      = (int)Gate::user()['tenant_id'];
        $question = $this->parcours->trouverQuestion((int)$id, $tid);
        if ($question === null) {
            abort(404, 'Question introuvable', 'Cette question n\'existe pas dans votre périmètre.', '/types-permis');
        }

        [$erreurs, $d] = Validate::check($_POST, [
            'valeur'   => 'required|login|max:100',
            'libelle'  => 'required|max:255',
            'position' => 'int',
        ]);
        if ($erreurs === [] && $this->parcours->valeurOptionExiste((int)$id, (string)$d['valeur'])) {
            $erreurs['valeur'] = 'Cette valeur existe déjà.';
        }
        if ($erreurs !== []) {
            Session::flash('errors', $erreurs);
            Session::flash('old', $_POST);
            $this->redirect('/types-permis/' . (int)$question['type_permis_id'] . '/modifier');
            return;
        }

        $this->parcours->creerOption((int)$id, (string)$d['valeur'], (string)$d['libelle'], (int)($d['position'] ?? 0));
        LogService::enregistrer('parcours.option.creee', 'parcours_option', (int)$id, 'succes', ['valeur' => $d['valeur']]);
        $this->flashSuccess('Option ajoutée.');
        $this->redirect('/types-permis/' . (int)$question['type_permis_id'] . '/modifier');
    }

    public function deleteOption(Request $request, string $id): void
    {
        $this->authorize('types-permis.parcours');

        $tid    = (int)Gate::user()['tenant_id'];
        $option = $this->parcours->trouverOption((int)$id, $tid);
        if ($option === null) {
            abort(404, 'Option introuvable', 'Cette option n\'existe pas dans votre périmètre.', '/types-permis');
        }

        $this->parcours->supprimerOption((int)$id);
        LogService::enregistrer('parcours.option.supprimee', 'parcours_option', (int)$id, 'succes', []);
        $this->flashSuccess('Option supprimée.');
        $this->redirect('/types-permis/' . (int)$option['question_id'] . '/modifier');
    }

    /* ---------- RÈGLES « On affiche… si la réponse est égale à » (v0.41) ---------- */

    public function storeRegle(Request $request): void
    {
        $this->authorize('types-permis.parcours');

        $tid = (int)Gate::user()['tenant_id'];

        [$erreurs, $d] = Validate::check($_POST, [
            'type_permis_id'    => 'required|int',
            'parent_id'         => 'required|int',
            'enfant_id'         => 'required|int',
            'condition_valeur'  => 'required|max:100',
        ]);

        $parent = $erreurs === [] ? $this->parcours->trouverQuestion((int)$d['parent_id'], $tid) : null;
        $enfant = $erreurs === [] ? $this->parcours->trouverQuestion((int)$d['enfant_id'], $tid) : null;

        if ($erreurs === []) {
            if ($parent === null || $enfant === null) {
                $erreurs['regle'] = 'Question inconnue.';
            } elseif ((int)$parent['id'] === (int)$enfant['id']) {
                $erreurs['regle'] = 'Une question ne peut pas s\'afficher selon sa propre réponse.';
            } elseif ((int)$parent['type_permis_id'] !== (int)$d['type_permis_id']
                   || (int)$enfant['type_permis_id'] !== (int)$d['type_permis_id']) {
                $erreurs['regle'] = 'Les deux questions doivent appartenir au même type de permis.';
            } elseif (!in_array((string)$d['condition_valeur'], ParcoursRepository::valeursPossibles($parent), true)) {
                $erreurs['regle'] = 'Valeur non proposée par le type de réponse de la question déclencheuse.';
            } elseif ($this->creeCycle((int)$enfant['id'], (int)$parent['id'], (int)$d['type_permis_id'], $tid)) {
                $erreurs['regle'] = 'Cette règle créerait une dépendance circulaire.';
            }
        }

        if ($erreurs !== []) {
            Session::flash('errors', ['regle' => (string)reset($erreurs)]);
            $this->redirect('/types-permis/' . (int)$d['type_permis_id'] . '/modifier');
            return;
        }

        $this->parcours->definirCondition((int)$enfant['id'], $tid, (int)$parent['id'], (string)$d['condition_valeur']);
        LogService::enregistrer('parcours.regle.creee', 'parcours_question', (int)$enfant['id'], 'succes', [
            'parent' => (int)$parent['id'], 'valeur' => (string)$d['condition_valeur'],
        ]);
        $this->flashSuccess('Règle enregistrée : « ' . mb_substr((string)$enfant['libelle'], 0, 60) . ' » s\'affichera si la réponse est « '
            . (string)$d['condition_valeur'] . ' ».');
        $this->redirect('/types-permis/' . (int)$d['type_permis_id'] . '/modifier');
    }

    public function removeRegle(Request $request, string $id): void
    {
        $this->authorize('types-permis.parcours');

        $tid      = (int)Gate::user()['tenant_id'];
        $enfant   = $this->parcours->trouverQuestion((int)$id, $tid);
        if ($enfant === null) {
            abort(404, 'Question introuvable', 'Cette question n\'existe pas dans votre périmètre.', '/types-permis');
        }

        $this->parcours->effacerCondition((int)$enfant['id'], $tid);
        LogService::enregistrer('parcours.regle.retiree', 'parcours_question', (int)$enfant['id'], 'succes', []);
        $this->flashSuccess('Règle retirée : la question est à nouveau toujours affichée.');
        $this->redirect('/types-permis/' . (int)$enfant['type_permis_id'] . '/modifier');
    }

    /* ---------- Helpers ---------- */

    /** Cycle ? En remontant les parents de $depuis, retombe-t-on sur $cible ? */
    private function creeCycle(int $depuis, int $cible, int $typePermisId, int $tenantId): bool
    {
        $parId = [];
        foreach ($this->parcours->questionsPourType($typePermisId, $tenantId, false) as $q) {
            if (!empty($q['condition_question_id'])) {
                $parId[(int)$q['id']] = (int)$q['condition_question_id'];
            }
        }
        $vu = [];
        $courant = $parId[$depuis] ?? null;
        while ($courant !== null) {
            if ($courant === $cible || isset($vu[$courant])) {
                return true;
            }
            $vu[$courant] = true;
            $courant = $parId[$courant] ?? null;
        }
        return false;
    }
}