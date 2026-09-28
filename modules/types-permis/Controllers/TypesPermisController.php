<?php
// fichier : modules/types-permis/Controllers/TypesPermisController.php — v0.40
declare(strict_types=1);

namespace Modules\TypesPermis\Controllers;

use App\Controllers\ReferentielController;
use App\Core\Request;
use App\Repositories\ReferentielRepository;
use App\Services\Gate;
use Modules\TypesPermis\Repositories\ParcoursRepository;
use Modules\TypesPermis\Repositories\TypesPermisRepository;

final class TypesPermisController extends ReferentielController
{
    protected string $prefixPermission = 'types-permis';
    protected string $routeBase = '/types-permis';
    protected string $titreSingulier = 'type de permis';
    protected string $titrePluriel = 'types de permis';
    protected string $genre = 'm';

    private ParcoursRepository $parcours;

    public function __construct()
    {
        parent::__construct();
        $this->parcours = new ParcoursRepository();
    }

    protected function instancierRepository(): ReferentielRepository
    {
        return new TypesPermisRepository();
    }

    /**
     * v0.40 : edit() — injecte les données du GÉNÉRATEUR de parcours
     * (carte « Parcours prospect — questionnaire ») + l'utilisateur courant
     * (affiché sur le formulaire de création : « Sera ajouté par … »).
     */
    public function edit(Request $request, string $id): void
    {
        $this->authorize($this->prefixPermission . '.modifier');

        $user = Gate::user();
        $tid  = (int)$user['tenant_id'];

        $entite = $this->repository->trouver((int)$id, $tid, Gate::scopeAgence());
        if ($entite === null) {
            abort(404, ucfirst($this->titreSingulier) . ' introuvable', 'Cet élément n\'existe pas dans votre périmètre.', $this->routeBase);
        }

        $utilisateurCourant = trim((string)($user['prenom'] ?? '') . ' ' . (string)($user['nom'] ?? ''));
        if ($utilisateurCourant === '') {
            $utilisateurCourant = (string)($user['login'] ?? '');
        }

        $this->view('referentiel/form', array_merge($this->config(), $this->donneesSupplementaires(), [
            'title'  => 'Modifier ' . ($this->genre === 'f' ? 'une ' : 'un ') . $this->titreSingulier,
            'entite' => $entite,
            'parcoursAdmin' => [
                'type'              => $entite,
                'questions'         => $this->parcours->questionsPourType((int)$entite['id'], $tid, false),
                'peutGerer'         => Gate::can('types-permis.parcours'),
                'utilisateurCourant' => $utilisateurCourant,
            ],
        ]));
    }
}