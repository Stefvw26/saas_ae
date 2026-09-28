<?php
// fichier : app/Controllers/ReferentielController.php — CRM Auto-École, v0.25
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\Validate;
use App\Repositories\CommentairesRepository;
use App\Repositories\ReferentielRepository;
use App\Services\Gate;
use App\Services\LogService;
use Modules\Partenaires\Repositories\PartenairesRepository;
use RuntimeException;

/**
 * Socle générique des CRUD de référentiels — composant transverse.
 * Conventions projet d'office : autorisation serveur (403 + journal),
 * isolation tenant + périmètre agence, suppression dans la page de
 * MODIFICATION, confirmation, journalisation, photos sécurisées.
 *
 * v0.25 : hook filtrerValeurs() — nettoyage des champs dépendant des
 * options (ex. prestations : fournisseur_id / sejour_type).
 */
abstract class ReferentielController extends Controller
{
    protected ReferentielRepository $repository;
    protected string $prefixPermission = '';
    protected string $routeBase = '';
    protected string $titreSingulier = '';
    protected string $titrePluriel = '';
    protected string $genre = 'm';

    abstract protected function instancierRepository(): ReferentielRepository;

    /** @return array<string, array<int|string, string>> [champ => [valeur => libellé]] */
    protected function optionsDynamiques(): array
    {
        return [];
    }

    /** @return array<string, mixed> données supplémentaires pour les vues (surcharge module). */
    protected function donneesSupplementaires(): array
    {
        return [];
    }

    /** Type d'objet des commentaires (null = commentaires désactivés pour ce module). */
    protected function typeCommentaire(): ?string
    {
        return null;
    }

    /** Champ km sur les commentaires (véhicules, CDC §16). */
    protected function avecKmCommentaire(): bool
    {
        return false;
    }

    /** Champ partenaire sur les commentaires (centres, CDC §18). */
    protected function avecPartenaireCommentaire(): bool
    {
        return false;
    }

    /**
     * Validations supplémentaires (FK externes au module).
     *
     * @param array<string, string> $erreurs
     * @param array<string, ?string> $valeurs
     * @return array<string, string>
     */
    protected function validerSupplement(array $erreurs, array $valeurs, ?int $horsId): array
    {
        return $erreurs;
    }

    /**
     * v0.25 : nettoyage post-validation des champs dépendants (surcharge module).
     *
     * @param array<string, ?string|int> $valeurs
     * @return array<string, ?string|int>
     */
    protected function filtrerValeurs(array $valeurs): array
    {
        return $valeurs;
    }

    /** @var array<string, array<int|string, string>> */
    private array $options = [];

    /** @var array<string, array<int, string>> */
    private array $optionsCouleurs = [];

    public function __construct()
    {
        $this->repository = $this->instancierRepository();
        $this->construireOptions();
    }

    /** Options + couleurs des champs select / agence / referer. */
    private function construireOptions(): void
    {
        $options = [];
        $couleurs = [];

        foreach ($this->repository->definition() as $nom => $def) {
            $type = $def['type'] ?? 'text';
            if ($type === 'agence') {
                $liste = [];
                $couleur = [];
                foreach (Gate::agencesAccessibles() as $agence) {
                    $liste[(int)$agence['id']] = (string)$agence['agence_nom'];
                    $couleur[(int)$agence['id']] = (string)($agence['agence_couleur'] ?? '');
                }
                $options[$nom] = $liste;
                $couleurs[$nom] = $couleur;
            } elseif ($type === 'select') {
                $options[$nom] = $def['options'] ?? [];
            }
        }

        $this->options = array_merge($options, $this->optionsDynamiques());
        $this->optionsCouleurs = $couleurs;
    }

    /** @return array<string, mixed> */
    public function config(): array
    {
        return [
            'titreSingulier'     => $this->titreSingulier,
            'titrePluriel'       => $this->titrePluriel,
            'article'            => $this->genre === 'f' ? 'une' : 'un',
            'champs'             => $this->repository->definition(),
            'options'            => $this->options,
            'optionsCouleurs'    => $this->optionsCouleurs,
            'colonneIdentite'    => $this->repository->colonneIdentite(),
            'listeStatut'        => $this->repository->listeStatut(),
            'listeDate'          => $this->repository->listeDate(),
            'colonneDate'        => $this->repository->colonneDate(),
            'suppressionLogique' => $this->repository->suppressionLogique(),
            'prefixPermission'   => $this->prefixPermission,
            'routeBase'          => $this->routeBase,
                        'conditionCartes'     => $this->repository->conditionCartes(),
        ];
    }

    /* ---------- Actions ---------- */

    public function index(Request $request): void
    {
        $this->authorize($this->prefixPermission . '.consulter');

        $tid   = (int)Gate::user()['tenant_id'];
        $scope = Gate::scopeAgence();
        $q     = trim((string)$request->get('q', ''));

        /* Export Excel (CDC §27) : même requête scopée que la liste. */
        if ((string)$request->get('export', '') === 'excel') {
            $this->exporterExcel($tid, $scope, $q);
            return;
        }

        $total = $this->repository->compter($tid, $scope, $q);
        $pages = max(1, (int)ceil($total / $this->repository->parPage()));
        $page  = min(max(1, (int)$request->get('page', 1)), $pages);

        $this->view('referentiel/index', array_merge($this->config(), $this->donneesSupplementaires(), [
            'title'   => ucfirst($this->titrePluriel),
            'filtres' => ['q' => $q],
            'liste'   => $this->repository->paginer($tid, $scope, $q, $page),
            'total'   => $total,
            'page'    => $page,
            'pages'   => $pages,
            'baseUrl' => $this->routeBase,
        ]));
    }

    public function create(Request $request): void
    {
        $this->authorize($this->prefixPermission . '.creer');

        $this->view('referentiel/form', array_merge($this->config(), $this->donneesSupplementaires(), [
            'title'  => 'Créer ' . ($this->genre === 'f' ? 'une ' : 'un ') . $this->titreSingulier,
            'entite' => null,
        ]));
    }

    public function store(Request $request): void
    {
        $this->authorize($this->prefixPermission . '.creer');

        [$erreurs, $valeurs] = $this->valider(null);

        if ($erreurs !== []) {
            $this->retourFormulaire($this->routeBase . '/creer', $erreurs);
            return;
        }

        try {
            $valeurs = $this->traiterPhotos($valeurs, null);
        } catch (RuntimeException $e) {
            $this->flashError($e->getMessage());
            Session::flash('old', $_POST);
            $this->redirect($this->routeBase . '/creer');
            return;
        }

        $id = $this->repository->creer($valeurs, (int)Gate::user()['tenant_id'], (int)Gate::user()['id']);

        LogService::enregistrer($this->prefixPermission . '.cree', $this->prefixPermission, $id, 'succes', $this->resume($valeurs));
        $this->flashSuccess($this->message($valeurs, 'créé'));
        $this->redirect($this->routeBase);
    }

    public function edit(Request $request, string $id): void
    {
        $this->authorize($this->prefixPermission . '.modifier');

        $entite = $this->trouverOuAbandonner((int)$id);

        $data = array_merge($this->config(), $this->donneesSupplementaires(), [
            'title'  => 'Modifier ' . ($this->genre === 'f' ? 'une ' : 'un ') . $this->titreSingulier,
            'entite' => $entite,
        ]);

        /* Commentaires conversationnels (CDC §33). */
        $typeCommentaire = $this->typeCommentaire();
        if ($typeCommentaire !== null) {
            $tid = (int)Gate::user()['tenant_id'];
            $data['objetType'] = $typeCommentaire;
            $data['objetId'] = (int)$entite['id'];
            $data['avecKm'] = $this->avecKmCommentaire();
            $data['optionsPartenaires'] = $this->avecPartenaireCommentaire()
                ? $this->optionsPartenairesCommentaire($tid)
                : [];
            $data['commentaires'] = (new CommentairesRepository())->pourObjet($typeCommentaire, (int)$entite['id'], $tid);
        }

        $this->view('referentiel/form', $data);
    }

    /** @return array<int, string> id => nom (partenaires actifs du tenant). */
    private function optionsPartenairesCommentaire(int $tenantId): array
    {
        $options = [];
        foreach ((new PartenairesRepository())->toutesPourTenant($tenantId, true) as $partenaire) {
            $options[(int)$partenaire['id']] = (string)$partenaire['nom'];
        }
        return $options;
    }

    public function update(Request $request, string $id): void
    {
        $this->authorize($this->prefixPermission . '.modifier');

        $entite = $this->trouverOuAbandonner((int)$id);

        [$erreurs, $valeurs] = $this->valider((int)$entite['id']);

        if ($erreurs !== []) {
            $this->retourFormulaire($this->routeBase . '/' . (int)$entite['id'] . '/modifier', $erreurs);
            return;
        }

        try {
            $valeurs = $this->traiterPhotos($valeurs, $entite);
        } catch (RuntimeException $e) {
            $this->flashError($e->getMessage());
            Session::flash('old', $_POST);
            $this->redirect($this->routeBase . '/' . (int)$entite['id'] . '/modifier');
            return;
        }

        $this->repository->modifier((int)$entite['id'], (int)Gate::user()['tenant_id'], $valeurs);

        LogService::enregistrer($this->prefixPermission . '.modifie', $this->prefixPermission, (int)$entite['id'], 'succes', $this->resume($valeurs));
        $this->flashSuccess($this->message($valeurs, 'modifié'));
        $this->redirect($this->routeBase);
    }

    public function delete(Request $request, string $id): void
    {
        $this->authorize($this->prefixPermission . '.supprimer');

        $entite = $this->trouverOuAbandonner((int)$id);

        $this->repository->supprimer((int)$entite['id'], (int)Gate::user()['tenant_id'], (int)Gate::user()['id']);

        LogService::enregistrer($this->prefixPermission . '.supprime', $this->prefixPermission, (int)$entite['id'], 'succes', $this->resume($entite));
        $this->flashSuccess($this->message($entite, 'supprimé'));
        $this->redirect($this->routeBase);
    }

    /* ---------- Export Excel (CDC §27/§34) ---------- */

    private function exporterExcel(int $tenantId, ?int $scopeAgence, string $q): void
    {
        $lignes = $this->repository->exporter($tenantId, $scopeAgence, $q);

        $colonnes = [];
        foreach ($this->repository->definition() as $nom => $def) {
            if (($def['type'] ?? '') !== 'photo' && !empty($def['liste'])) {
                $colonnes[$nom] = $def;
            }
        }
        uasort($colonnes, static fn (array $a, array $b): int => (int)($a['position'] ?? 100) <=> (int)($b['position'] ?? 100));

        $calculees = $this->donneesSupplementaires()['colonnesCalculees'] ?? [];
        if (!is_array($calculees)) {
            $calculees = [];
        }

        $entetes = [];
        foreach ($colonnes as $def) {
            $entetes[] = (string)$def['label'];
        }
        foreach ($calculees as $col) {
            $entetes[] = (string)($col['label'] ?? '');
        }
        $entetes[] = 'Statut';
        $entetes[] = 'Ajouté le';

        $cellule = static function ($v): string {
            return '"' . str_replace('"', '""', (string)($v ?? '')) . '"';
        };

        $nomFichier = 'export-' . trim((string)preg_replace('#[^a-z0-9]+#', '-', strtolower($this->titrePluriel)), '-')
            . '-' . date('Y-m-d') . '.csv';

        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nomFichier . '"');
        header('Pragma: no-cache');

        echo "\xEF\xBB\xBF";
        echo implode(';', array_map($cellule, $entetes)) . "\r\n";

        foreach ($lignes as $entite) {
            $valeurs = [];
            foreach ($colonnes as $nom => $def) {
                $type = $def['type'] ?? 'text';
                $brut = $entite[$nom] ?? null;
                if ($type === 'agence' || $type === 'referer' || $type === 'select') {
                    $valeurs[] = ($brut !== null && (string)$brut !== '')
                        ? (string)($this->options[$nom][(string)$brut] ?? $brut)
                        : '';
                } elseif ($type === 'booleen') {
                    $valeurs[] = (int)$brut === 1 ? 'Oui' : 'Non';
                } else {
                    $valeurs[] = (string)($brut ?? '');
                }
            }
            foreach ($calculees as $col) {
                $valeurs[] = (string)($entite[$col['cle']] ?? '');
            }
            $valeurs[] = (int)($entite['actif'] ?? 0) === 1 ? 'Actif' : 'Inactif';
            $valeurs[] = (string)($entite[$this->repository->colonneDate()] ?? '');

            echo implode(';', array_map($cellule, $valeurs)) . "\r\n";
        }
        exit;
    }

    /* ---------- Validation ---------- */

    /**
     * @return array{0: array<string, string>, 1: array<string, ?string|int>}
     */
    private function valider(?int $horsId): array
    {
        $regles = [];
        foreach ($this->repository->definition() as $nom => $def) {
            $type = $def['type'] ?? 'text';
            if ($type === 'booleen' || $type === 'photo') {
                continue;
            }
            $regle = trim(($def['requis'] ?? false ? 'required|' : '') . $this->regleType($def));
            if ($regle !== '') {
                $regles[$nom] = $regle;
            }
        }
        [$erreurs, $valeurs] = Validate::check($_POST, $regles);

        /* Agence : parmi les agences accessibles. */
        foreach ($this->repository->definition() as $nom => $def) {
            if (($def['type'] ?? '') !== 'agence' || isset($erreurs[$nom])) {
                continue;
            }
            $idAgence = $valeurs[$nom] !== null ? (int)$valeurs[$nom] : null;
            if ($idAgence !== null && !isset($this->options[$nom][$idAgence])) {
                $erreurs[$nom] = 'Agence non autorisée.';
            }
        }

        /* Unicité du champ principal. */
        $principal = $this->repository->champPrincipal();
        if ($principal !== null && $this->repository->unicitePrincipale()
            && !isset($erreurs[$principal]) && ($valeurs[$principal] ?? null) !== null) {
            if ($this->repository->valeurExiste($principal, (string)$valeurs[$principal], (int)Gate::user()['tenant_id'], $horsId)) {
                $erreurs[$principal] = 'Cette valeur existe déjà.';
            }
        }

        /* Validations supplémentaires du module (FK externes). */
        $erreurs = $this->validerSupplement($erreurs, $valeurs, $horsId);

        /* Booléens + actif. */
        foreach ($this->repository->definition() as $nom => $def) {
            if (($def['type'] ?? '') === 'booleen') {
                $valeurs[$nom] = isset($_POST[$nom]) ? 1 : 0;
            }
        }
        $valeurs['actif'] = isset($_POST['actif']) ? 1 : 0;

        /* v0.25 : nettoyage des champs dépendants (après lecture des booléens). */
        $valeurs = $this->filtrerValeurs($valeurs);

        return [$erreurs, $valeurs];
    }

    private function regleType(array $def): string
    {
        switch ($def['type'] ?? 'text') {
            case 'color':   return 'hex';
            case 'entier':
            case 'etoiles':
            case 'agence':
            case 'referer': return 'int';
            case 'decimal': return 'decimal';
            case 'date':    return 'date';
            case 'select':  return 'in:' . implode(',', array_map('strval', array_keys($def['options'] ?? [])));
            default:        return 'max:' . (int)($def['max'] ?? 255);
        }
    }

    /* ---------- Photos ---------- */

    /**
     * @param array<string, ?string|int> $valeurs
     * @param array<string, mixed>|null $entite
     * @return array<string, ?string|int>
     */
    private function traiterPhotos(array $valeurs, ?array $entite): array
    {
        foreach ($this->repository->definition() as $nom => $def) {
            if (($def['type'] ?? '') !== 'photo') {
                continue;
            }
            $ancienne = $entite !== null ? (string)($entite[$nom] ?? '') : '';
            $retirer  = isset($_POST['retirer_' . $nom]);
            $valeurs[$nom] = $this->traiterPhoto($_FILES[$nom] ?? null, $ancienne, $retirer, (string)($def['dossier'] ?? 'divers'));
        }
        return $valeurs;
    }

    private function traiterPhoto(?array $fichier, string $ancienne, bool $retirer, string $dossier): ?string
    {
        $repertoire = STORAGE_PATH . '/uploads/' . $dossier;
        if (!is_dir($repertoire)) {
            @mkdir($repertoire, 0775, true);
        }

        $effacer = static function () use ($repertoire, $ancienne): void {
            if ($ancienne !== ''
                && preg_match('#^[a-f0-9]{32}\.(jpg|jpeg|png|webp)$#', $ancienne) === 1) {
                @unlink($repertoire . '/' . $ancienne);
            }
        };

        if ($retirer) {
            $effacer();
            return null;
        }

        if ($fichier === null || (int)($fichier['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $ancienne !== '' ? $ancienne : null;
        }
        if ((int)($fichier['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Échec de l\'envoi de l\'image (code ' . (int)$fichier['error'] . ').');
        }
        if ((int)($fichier['size'] ?? 0) > 2097152) {
            throw new RuntimeException('L\'image dépasse 2 Mo.');
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
            throw new RuntimeException('Format d\'image non autorisé (JPG, PNG ou WebP uniquement).');
        }

        $nom = bin2hex(random_bytes(16)) . '.' . $autorises[$mime];
        if (!move_uploaded_file($fichier['tmp_name'], $repertoire . '/' . $nom)) {
            throw new RuntimeException('Impossible d\'enregistrer l\'image.');
        }

        $effacer();
        return $nom;
    }

    /* ---------- Helpers ---------- */

    /** @return array<string, mixed> */
    private function trouverOuAbandonner(int $id): array
    {
        $entite = $this->repository->trouver($id, (int)Gate::user()['tenant_id'], Gate::scopeAgence());
        if ($entite === null) {
            abort(404, ucfirst($this->titreSingulier) . ' introuvable', 'Cet élément n\'existe pas dans votre périmètre.', $this->routeBase);
        }
        return $entite;
    }

    /** Message flash accordé. */
    private function message(array $valeurs, string $participe): string
    {
        $principal = $this->repository->champPrincipal();
        $libelle   = $principal !== null ? (string)($valeurs[$principal] ?? '') : '';
        $accorde   = $participe . ($this->genre === 'f' ? 'e' : '');
        $suffixe   = ($this->repository->suppressionLogique() && $participe === 'supprimé')
            ? ' (archivé' . ($this->genre === 'f' ? 'e' : '') . ')'
            : '';
        return ucfirst($this->titreSingulier) . ' « ' . $libelle . ' » ' . $accorde . $suffixe . '.';
    }

    /** @return array<string, string> */
    private function resume(array $valeurs): array
    {
        $principal = $this->repository->champPrincipal();
        return $principal !== null ? [$principal => (string)($valeurs[$principal] ?? '')] : [];
    }

    private function retourFormulaire(string $chemin, array $erreurs): void
    {
        Session::flash('errors', $erreurs);
        Session::flash('old', $_POST);
        $this->redirect($chemin);
    }
}