<?php
// fichier : modules/dossiers/Controllers/DossiersController.php — v0.54
declare(strict_types=1);

namespace Modules\Dossiers\Controllers;

use App\Controllers\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CommentairesRepository;
use App\Repositories\DocumentsRepository;
use App\Services\Gate;
use App\Services\LogService;
use Modules\Dossiers\Repositories\DossiersRepository;
use Modules\Dossiers\Repositories\EvenementsRepository;
use Modules\Domaines\Repositories\DomainesRepository;
use Modules\Eleves\Repositories\ElevesRepository;
use Modules\Formules\Repositories\FormulesRepository;
use Modules\ModesPaiement\Repositories\ModesPaiementRepository;
use Modules\Prestations\Repositories\PrestationsRepository;
use Modules\TypesPermis\Repositories\TypesPermisRepository;
use RuntimeException;

final class DossiersController extends Controller
{
    private DossiersRepository $dossiers;
    private ElevesRepository $eleves;
    private DomainesRepository $domaines;
    private TypesPermisRepository $typesPermis;
    private FormulesRepository $formules;
    private PrestationsRepository $prestations;
    private ModesPaiementRepository $modesPaiement;
    private DocumentsRepository $documents;

    private const STATUTS = ['en_cours', 'valide', 'confirme'];
    private const JUSTIFICATIFS_REMISE = ['geste_commercial', 'promotion', 'remise_exceptionnelle'];
    private const TYPES_DECLARATION = ['cerfa', 'ediser', 'neph'];

    public function __construct()
    {
        $this->dossiers   = new DossiersRepository();
        $this->eleves    = new ElevesRepository();
        $this->domaines  = new DomainesRepository();
        $this->typesPermis = new TypesPermisRepository();
        $this->formules  = new FormulesRepository();
        $this->prestations = new PrestationsRepository();
        $this->modesPaiement = new ModesPaiementRepository();
        $this->documents = new DocumentsRepository();
    }

    /* ---------- Création ---------- */

    /**
     * Un dossier ne peut être créé que depuis la fiche d'un élève :
     * eleve_id est obligatoire (query string), sinon on abandonne (CDC §50/§55).
     *
     * Un dossier "brouillon" est créé immédiatement (agence = celle de
     * l'élève) afin que le panier (AJAX, qui a besoin d'un id de dossier
     * réel) soit utilisable dès l'ouverture du formulaire. L'utilisateur
     * poursuit ensuite sur le formulaire de modification.
     */
    public function create(Request $request): void
    {
        $this->authorize('dossiers.creer');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];

        $eleveId = (int)$request->get('eleve_id', 0);
        $eleve = $eleveId > 0
            ? $this->eleves->trouver($eleveId, $tid, Gate::scopeAgence())
            : null;

        if ($eleve === null) {
            abort(404, 'Élève requis', 'Un dossier ne peut être créé que depuis la fiche d\'un élève.', '/eleves');
        }

        $id = $this->dossiers->creer([
            'eleve_id'  => (int)$eleve['id'],
            'agence_id' => $eleve['agence_id'] ?? null,
        ], $tid, (int)$courant['id']);

        LogService::enregistrer('dossier.cree', 'dossier', $id, 'succes', ['eleve' => (int)$eleve['id']]);
        $this->redirect('/dossiers/' . $id . '/modifier');
    }

    /* ---------- Fiche ---------- */

    public function fiche(Request $request, string $id): void
    {
        $this->authorize('dossiers.consulter');

        $dossier = $this->trouverOuAbandonner((int)$id);
        $tid     = (int)Gate::user()['tenant_id'];
        $did     = (int)$dossier['id'];

        $modesPaiement = [];
        foreach ($this->modesPaiement->toutesPourTenant($tid, true) as $mp) {
            $modesPaiement[(int)$mp['id']] = (string)$mp['nom'];
        }

        $this->view('@dossiers/fiche', array_merge($this->options(), [
            'title'              => 'Dossier #' . $did,
            'dossier'            => $dossier,
            'evenements'         => (new EvenementsRepository())->pourDossier($did, $tid),
            'commentaires'       => (new CommentairesRepository())->pourObjet('dossier', $did, $tid),
            'objetType'          => 'dossier',
            'objetId'            => $did,
            'panier'             => $this->dossiers->panier($did, $tid),
            'panierTotal'        => $this->dossiers->totalPanier($did, $tid),
            'declarations'       => $this->dossiers->declarations($did, $tid),
            'echeances'          => $this->dossiers->echeances($did, $tid),
            'checklistObligatoires' => $this->dossiers->checklistObligatoires($did, $tid),
            'documentsTous'      => $this->documents->pourObjet('dossier', $did, $tid),
            'optionsModesPaiement' => $modesPaiement,
        ]));
    }

    /* ---------- Modification ---------- */

    public function edit(Request $request, string $id): void
    {
        $this->authorize('dossiers.modifier');

        $dossier = $this->trouverOuAbandonner((int)$id);
        $tid     = (int)Gate::user()['tenant_id'];

        $data = $this->options();
        $data['title']               = 'Modifier le dossier #' . (int)$dossier['id'];
        $data['dossier']            = $dossier;
        $data['elevePreselectionne'] = $this->eleves->trouver((int)$dossier['eleve_id'], $tid, Gate::scopeAgence());
        $data['panier']             = $this->dossiers->panier((int)$dossier['id'], $tid);
        $data['panierTotal']        = $this->dossiers->totalPanier((int)$dossier['id'], $tid);
        $data['formulesDomaine']   = $this->formulesDuDomaine(
            old('domaine_id', (string)($dossier['domaine_id'] ?? '0'))
        );

        $this->view('@dossiers/form', $data);
    }

    public function update(Request $request, string $id): void
    {
        $this->authorize('dossiers.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $dossier = $this->trouverOuAbandonner((int)$id);

        $eleve = $this->eleves->trouver((int)$dossier['eleve_id'], $tid, Gate::scopeAgence());

        $d = [
            'eleve_id'       => (int)$dossier['eleve_id'],
            'agence_id'      => $eleve['agence_id'] ?? null,
            'statut'         => in_array((string)$request->post('statut', ''), self::STATUTS, true)
                ? (string)$request->post('statut') : 'en_cours',
            'domaine_id'     => !empty($_POST['domaine_id']) ? (int)$_POST['domaine_id'] : null,
            'type_permis_id' => !empty($_POST['type_permis_id']) ? (int)$_POST['type_permis_id'] : null,
            'nb_heures'     => $this->decimalOuNull((string)$request->post('nb_heures', '')),
            'montant_total' => $this->decimalOuNull((string)$request->post('montant_total', '')),
            'notes'         => trim((string)$request->post('notes', '')) ?: null,
        ];

        $this->dossiers->modifier((int)$dossier['id'], $tid, $d);
        $modePost = (string)$request->post('mode', 'a_la_carte') === 'formule' ? 'formule' : 'a_la_carte';
        $this->dossiers->enregistrerMode((int)$dossier['id'], $tid, $modePost, $dossier['formule_id'] ?? null);
        LogService::enregistrer('dossier.modifie', 'dossier', (int)$dossier['id'], 'succes', []);
        $this->flashSuccess('Dossier modifié.');
        $this->redirect('/dossiers/' . (int)$dossier['id']);
    }

    /* ---------- Archivage ---------- */

    public function archiver(Request $request, string $id): void
    {
        $this->authorize('dossiers.supprimer');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $dossier = $this->trouverOuAbandonner((int)$id);
        $eleveId = (int)$dossier['eleve_id'];

        $this->dossiers->archiverLogique((int)$dossier['id'], $tid, (int)$courant['id']);
        LogService::enregistrer('dossier.archive', 'dossier', (int)$dossier['id'], 'succes', []);
        $this->flashSuccess('Dossier archivé.');
        $this->redirect('/eleves/' . $eleveId);
    }

    /**
     * Supprimer le dossier — RÈGLE ABSOLUE : jamais de DELETE réel.
     * Reste une suppression logique (comme archiver()), distinguée par motif_suppression.
     */
    public function supprimer(Request $request, string $id): void
    {
        $this->authorize('dossiers.supprimer');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $dossier = $this->trouverOuAbandonner((int)$id);
        $eleveId = (int)$dossier['eleve_id'];

        $this->dossiers->supprimerAvecMotif((int)$dossier['id'], $tid, (int)$courant['id'], 'supprime');
        LogService::enregistrer('dossier.supprime', 'dossier', (int)$dossier['id'], 'succes', []);
        $this->flashSuccess('Dossier supprimé.');
        $this->redirect('/eleves/' . $eleveId);
    }

    /* ---------- PANIER (AJAX) ---------- */

    public function panierAjouter(Request $request, string $id): void
    {
        $this->authorize('dossiers.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $dossier = $this->trouverOuAbandonner((int)$id);

        $prestationId = (int)$request->post('prestation_id', 0);
        $quantite    = max(1, (int)$request->post('quantite', 1));

        $prestation = Database::fetch(
            'SELECT id, titre FROM prestations WHERE id = :id AND tenant_id = :tenant AND supprimer = 0',
            ['id' => $prestationId, 'tenant' => $tid]
        );
        if ($prestation === null) {
            Response::json(['ok' => false, 'erreur' => 'Prestation inconnue.'], 422);
            return;
        }

        $ajoute = $this->dossiers->ajouterAuPanier(
            (int)$dossier['id'], $prestationId, $quantite, $tid, (int)$courant['id']
        );
        if (!$ajoute) {
            Response::json([
                'ok'      => false,
                'erreur'  => 'Cette prestation est déjà dans le panier.',
                'message' => 'Cette prestation est déjà dans le panier.',
            ], 422);
            return;
        }

        LogService::enregistrer('panier.prestation_ajoutee', 'dossier', (int)$dossier['id'], 'succes', ['prestation' => $prestationId]);

        Response::json([
            'ok'      => true,
            'message' => 'Prestation « ' . (string)$prestation['titre'] . ' » ajoutée au panier.',
            'panier'  => $this->panierPourJson((int)$dossier['id'], $tid),
        ]);
    }

    public function panierAjouterFormule(Request $request, string $id): void
    {
        $this->authorize('dossiers.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $dossier = $this->trouverOuAbandonner((int)$id);

        $formuleId = (int)$request->post('formule_id', 0);

        $formule = Database::fetch(
            'SELECT f.id, f.nom, f.domaine_id FROM formules f
             WHERE f.id = :id AND f.tenant_id = :tenant AND f.supprimer = 0 AND f.actif = 1',
            ['id' => $formuleId, 'tenant' => $tid]
        );
        if ($formule === null) {
            Response::json([
                'ok'      => false,
                'erreur'  => 'Formule inconnue.',
                'message' => 'Formule inconnue.',
            ], 422);
            return;
        }

        /* Directive : formules du domaine du dossier uniquement. */
        if ($dossier['domaine_id'] !== null && $formule['domaine_id'] !== null
            && (int)$formule['domaine_id'] !== (int)$dossier['domaine_id']) {
            Response::json([
                'ok'      => false,
                'erreur'  => 'La formule doit appartenir au même domaine que le dossier.',
                'message' => 'La formule doit appartenir au même domaine que le dossier.',
            ], 422);
            return;
        }

        $ajoutees = $this->dossiers->ajouterFormuleAuPanier(
            (int)$dossier['id'], $formuleId, $tid, (int)$courant['id']
        );
        $this->dossiers->enregistrerMode((int)$dossier['id'], $tid, 'formule', $formuleId);
        LogService::enregistrer('panier.formule_ajoutee', 'dossier', (int)$dossier['id'], 'succes', ['formule' => $formuleId]);

        $message = $ajoutees > 0
            ? $ajoutees . ' prestation' . ($ajoutees > 1 ? 's' : '') . ' de la formule « ' . (string)$formule['nom'] . ' » ajoutée' . ($ajoutees > 1 ? 's' : '') . ' au panier.'
            : 'Toutes les prestations de cette formule sont déjà dans le panier.';

        Response::json([
            'ok'      => true,
            'message' => $message,
            'panier'  => $this->panierPourJson((int)$dossier['id'], $tid),
        ]);
    }

    public function panierRetirer(Request $request, string $id): void
    {
        $this->authorize('dossiers.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $dossier = $this->trouverOuAbandonner((int)$id);

        $prestationId = (int)$request->post('prestation_id', 0);

        $this->dossiers->retirerDuPanier((int)$dossier['id'], $prestationId, $tid, (int)$courant['id']);
        LogService::enregistrer('panier.prestation_retiree', 'dossier', (int)$dossier['id'], 'succes', ['prestation' => $prestationId]);

        Response::json([
            'ok'      => true,
            'message' => 'Prestation retirée du panier.',
            'panier'  => $this->panierPourJson((int)$dossier['id'], $tid),
        ]);
    }

    /**
     * Vider entièrement le panier — appelé après confirmation utilisateur
     * lors d'un changement de type de formation en cours de saisie (directive).
     */
    public function panierVider(Request $request, string $id): void
    {
        $this->authorize('dossiers.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $dossier = $this->trouverOuAbandonner((int)$id);

        $this->dossiers->viderPanier((int)$dossier['id'], $tid, (int)$courant['id']);
        LogService::enregistrer('panier.vide', 'dossier', (int)$dossier['id'], 'succes', []);

        Response::json([
            'ok'      => true,
            'message' => 'Panier vidé.',
            'panier'  => $this->panierPourJson((int)$dossier['id'], $tid),
        ]);
    }

    /* ================= ÉTAT DU DOSSIER ================= */

    /** Valider le panier — exige une confirmation côté écran (directive), irréversible ici. */
    public function panierValider(Request $request, string $id): void
    {
        $this->authorize('dossiers.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $dossier = $this->trouverOuAbandonner((int)$id);

        $this->dossiers->validerPanier((int)$dossier['id'], $tid);
        LogService::enregistrer('dossier.panier_valide', 'dossier', (int)$dossier['id'], 'succes', []);
        $this->flashSuccess('Panier validé.');
        $this->redirect('/dossiers/' . (int)$dossier['id']);
    }

    /**
     * Générer le contrat — disponible quand le panier est validé.
     * NOTE TECHNIQUE : aucune bibliothèque de génération PDF n'est présente
     * dans le socle (pas de Composer, cf. prompt maître). Cette action
     * enregistre donc l'état « contrat généré » ; la production réelle du
     * fichier PDF du contrat est un point à valider séparément (bibliothèque
     * à choisir) avant d'être développée.
     */
    public function contratGenerer(Request $request, string $id): void
    {
        $this->authorize('dossiers.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $dossier = $this->trouverOuAbandonner((int)$id);

        $this->dossiers->genererContrat((int)$dossier['id'], $tid);
        LogService::enregistrer('dossier.contrat_genere', 'dossier', (int)$dossier['id'], 'succes', []);
        $this->flashSuccess('Contrat généré.');
        $this->redirect('/dossiers/' . (int)$dossier['id']);
    }

    /**
     * Ajouter/remplacer le devis — badge visible dans les icônes d'état.
     * Disponible tant que le contrat n'est pas généré (directive).
     */
    public function devisAjouter(Request $request, string $id): void
    {
        $this->authorize('dossiers.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $dossier = $this->trouverOuAbandonner((int)$id);

        if ((string)$dossier['etat_contrat'] === 'contrat_genere') {
            $this->flashError('Le contrat est déjà généré : le devis n\'est plus disponible.');
            $this->redirect('/dossiers/' . (int)$dossier['id']);
            return;
        }

        try {
            $documentId = $this->traiterDocumentJoint(
                $_FILES['fichier'] ?? null, (int)$dossier['id'], 'devis', 'Devis', $tid, (int)$courant['id'], true
            );
        } catch (RuntimeException $e) {
            $this->flashError($e->getMessage());
            $this->redirect('/dossiers/' . (int)$dossier['id']);
            return;
        }

        LogService::enregistrer('dossier.devis_ajoute', 'dossier', (int)$dossier['id'], 'succes', ['document' => $documentId]);
        $this->flashSuccess('Devis ajouté.');
        $this->redirect('/dossiers/' . (int)$dossier['id']);
    }

    /* ================= REMISE ================= */

    public function remiseEnregistrer(Request $request, string $id): void
    {
        $this->authorize('dossiers.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $dossier = $this->trouverOuAbandonner((int)$id);

        if ((string)$dossier['etat_contrat'] === 'contrat_genere') {
            $this->flashError('La remise ne peut plus être modifiée une fois le contrat généré.');
            $this->redirect('/dossiers/' . (int)$dossier['id']);
            return;
        }

        $justificatif = (string)$request->post('remise_justificatif', '');
        if (!in_array($justificatif, self::JUSTIFICATIFS_REMISE, true)) {
            $this->flashError('Justificatif de remise invalide.');
            $this->redirect('/dossiers/' . (int)$dossier['id']);
            return;
        }
        $montant = $this->decimalOuNull((string)$request->post('remise_montant', ''));
        if ($montant === null) {
            $this->flashError('Le montant de la remise est requis.');
            $this->redirect('/dossiers/' . (int)$dossier['id']);
            return;
        }

        $this->dossiers->enregistrerRemise((int)$dossier['id'], $tid, [
            'justificatif' => $justificatif,
            'montant'      => $montant,
            'commentaire'  => trim((string)$request->post('remise_commentaire', '')) ?: null,
        ], (int)$courant['id']);
        LogService::enregistrer('dossier.remise_enregistree', 'dossier', (int)$dossier['id'], 'succes', []);
        $this->flashSuccess('Remise enregistrée.');
        $this->redirect('/dossiers/' . (int)$dossier['id']);
    }

    public function remiseSupprimer(Request $request, string $id): void
    {
        $this->authorize('dossiers.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $dossier = $this->trouverOuAbandonner((int)$id);

        $this->dossiers->supprimerRemise((int)$dossier['id'], $tid);
        LogService::enregistrer('dossier.remise_supprimee', 'dossier', (int)$dossier['id'], 'succes', []);
        $this->flashSuccess('Remise retirée.');
        $this->redirect('/dossiers/' . (int)$dossier['id']);
    }

    /* ================= DÉCLARATIONS (CERFA / EDISER / NEPH) ================= */

    /** Unifié pour les 3 types — dispatché depuis 3 modales distinctes (directive). */
    public function declarationEnregistrer(Request $request, string $id): void
    {
        $this->authorize('dossiers.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $dossier = $this->trouverOuAbandonner((int)$id);

        $type = (string)$request->post('type', '');
        if (!in_array($type, self::TYPES_DECLARATION, true)) {
            $this->flashError('Type de déclaration invalide.');
            $this->redirect('/dossiers/' . (int)$dossier['id']);
            return;
        }

        $documentId = null;
        if ($type === 'cerfa') {
            if ((string)$request->post('date_envoi', '') === '') {
                $this->flashError('La date d\'envoi du formulaire CERFA est requise.');
                $this->redirect('/dossiers/' . (int)$dossier['id']);
                return;
            }
            try {
                $documentId = $this->traiterDocumentJoint(
                    $_FILES['fichier_cerfa'] ?? null, (int)$dossier['id'], 'cerfa', 'CERFA', $tid, (int)$courant['id']
                );
            } catch (RuntimeException $e) {
                $this->flashError($e->getMessage());
                $this->redirect('/dossiers/' . (int)$dossier['id']);
                return;
            }
        }

        $this->dossiers->enregistrerDeclaration((int)$dossier['id'], $tid, $type, [
            'numero'         => trim((string)$request->post('numero', '')) ?: null,
            'date_envoi'     => (string)$request->post('date_envoi', '') ?: null,
            'date_reception' => (string)$request->post('date_reception', '') ?: null,
            'commentaire'    => trim((string)$request->post('commentaire', '')) ?: null,
            'document_id'    => $documentId,
        ], (int)$courant['id']);

        LogService::enregistrer('dossier.declaration_' . $type, 'dossier', (int)$dossier['id'], 'succes', []);
        $this->flashSuccess(strtoupper($type) . ' déclaré.');
        $this->redirect('/dossiers/' . (int)$dossier['id']);
    }

    /* ================= ÉCHÉANCES ================= */

    /**
     * Définir en bloc les échéances (nombre choisi dans un menu déroulant,
     * puis saisie groupée — directive). Remplace toute échéance existante.
     * Dates basées sur la date de validation du panier, +1 mois par échéance.
     */
    public function echeancesDefinir(Request $request, string $id): void
    {
        $this->authorize('dossiers.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $dossier = $this->trouverOuAbandonner((int)$id);

        if ((string)$dossier['etat_contrat'] === 'contrat_genere') {
            $this->flashError('Impossible de redéfinir les échéances : le contrat est déjà généré.');
            $this->redirect('/dossiers/' . (int)$dossier['id']);
            return;
        }

        $max = $dossier['nb_echeances_max'] !== null ? (int)$dossier['nb_echeances_max'] : 3;
        $nombre = max(1, min($max, (int)$request->post('nombre', 0)));

        $base = (string)($dossier['panier_valide_le'] ?? $dossier['ajout_le']);
        $datesPost   = (array)($_POST['date_echeance'] ?? []);
        $modesPost   = (array)($_POST['mode_paiement_id'] ?? []);
        $montantsPost = (array)($_POST['montant_ttc'] ?? []);

        $lignes = [];
        for ($i = 0; $i < $nombre; $i++) {
            $montant = $this->decimalOuNull((string)($montantsPost[$i] ?? ''));
            if ($montant === null) {
                $this->flashError('Le montant de l\'échéance n°' . ($i + 1) . ' est requis.');
                $this->redirect('/dossiers/' . (int)$dossier['id']);
                return;
            }
            $dateDefaut = date('Y-m-d', strtotime('+' . $i . ' months', strtotime($base)));
            $lignes[] = [
                'date_echeance'    => (string)($datesPost[$i] ?? '') ?: $dateDefaut,
                'mode_paiement_id' => !empty($modesPost[$i]) ? (int)$modesPost[$i] : null,
                'montant_ttc'      => $montant,
            ];
        }

        $this->dossiers->definirEcheances((int)$dossier['id'], $tid, $lignes, (int)$courant['id']);
        LogService::enregistrer('dossier.echeances_definies', 'dossier', (int)$dossier['id'], 'succes', ['nombre' => $nombre]);
        $this->flashSuccess('Échéances enregistrées.');
        $this->redirect('/dossiers/' . (int)$dossier['id']);
    }

    public function echeanceModifier(Request $request, string $id, string $echeanceId): void
    {
        $this->authorize('dossiers.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $dossier = $this->trouverOuAbandonner((int)$id);

        $contratGenere = (string)$dossier['etat_contrat'] === 'contrat_genere';

        $d = [];
        if ($contratGenere) {
            /* Après génération du contrat : seule la mise à jour du règlement reste possible. */
            $d['date_paiement'] = (string)$request->post('date_paiement', '') ?: null;
            $d['mode_paiement_id'] = !empty($_POST['mode_paiement_id']) ? (int)$_POST['mode_paiement_id'] : null;
            $d['montant_ttc'] = $this->decimalOuNull((string)$request->post('montant_ttc', ''));
            $d['commentaire'] = trim((string)$request->post('commentaire', '')) ?: null;
            $d['etat'] = $d['date_paiement'] !== null ? 'payee' : 'a_venir';
        } else {
            $d['date_echeance'] = (string)$request->post('date_echeance', '') ?: null;
            $d['mode_paiement_id'] = !empty($_POST['mode_paiement_id']) ? (int)$_POST['mode_paiement_id'] : null;
            $d['montant_ttc'] = $this->decimalOuNull((string)$request->post('montant_ttc', ''));
        }

        $this->dossiers->modifierEcheance((int)$echeanceId, $tid, $d);
        LogService::enregistrer('dossier.echeance_modifiee', 'dossier', (int)$dossier['id'], 'succes', ['echeance' => (int)$echeanceId]);
        $this->flashSuccess('Échéance mise à jour.');
        $this->redirect('/dossiers/' . (int)$dossier['id']);
    }

    public function echeanceSupprimer(Request $request, string $id, string $echeanceId): void
    {
        $this->authorize('dossiers.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $dossier = $this->trouverOuAbandonner((int)$id);

        if ((string)$dossier['etat_contrat'] === 'contrat_genere') {
            $this->flashError('Impossible de supprimer une échéance : le contrat est déjà généré.');
            $this->redirect('/dossiers/' . (int)$dossier['id']);
            return;
        }

        $this->dossiers->supprimerEcheance((int)$echeanceId, $tid, (int)$courant['id']);
        LogService::enregistrer('dossier.echeance_supprimee', 'dossier', (int)$dossier['id'], 'succes', ['echeance' => (int)$echeanceId]);
        $this->flashSuccess('Échéance supprimée.');
        $this->redirect('/dossiers/' . (int)$dossier['id']);
    }

    /* ================= DOCUMENTS (libres : attestations, procuration, etc.) ================= */

    public function documentAjouter(Request $request, string $id): void
    {
        $this->authorize('dossiers.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $dossier = $this->trouverOuAbandonner((int)$id);

        $libelle = trim((string)$request->post('libelle', ''));
        if ($libelle === '') {
            $this->flashError('Le libellé du document est requis.');
            $this->redirect('/dossiers/' . (int)$dossier['id']);
            return;
        }
        /* Seul 'contrat_signe' peut être envoyé explicitement (bouton dédié de la card Actions) ;
           tout le reste tombe dans 'libre' (attestation, procuration, document personnalisé…). */
        $type = (string)$request->post('type', '') === 'contrat_signe' ? 'contrat_signe' : 'libre';

        try {
            $documentId = $this->traiterDocumentJoint(
                $_FILES['fichier'] ?? null, (int)$dossier['id'], $type, $libelle, $tid, (int)$courant['id'], true
            );
        } catch (RuntimeException $e) {
            $this->flashError($e->getMessage());
            $this->redirect('/dossiers/' . (int)$dossier['id']);
            return;
        }
        if ($documentId === null) {
            $this->flashError('Aucun fichier reçu.');
            $this->redirect('/dossiers/' . (int)$dossier['id']);
            return;
        }

        LogService::enregistrer('dossier.document_ajoute', 'dossier', (int)$dossier['id'], 'succes', ['document' => $documentId]);
        $this->flashSuccess('Document ajouté.');
        $this->redirect('/dossiers/' . (int)$dossier['id']);
    }

    public function documentSupprimer(Request $request, string $id, string $documentId): void
    {
        $this->authorize('dossiers.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $dossier = $this->trouverOuAbandonner((int)$id);

        $this->documents->supprimerLogique((int)$documentId, $tid, (int)$courant['id']);
        LogService::enregistrer('dossier.document_supprime', 'dossier', (int)$dossier['id'], 'succes', ['document' => (int)$documentId]);
        $this->flashSuccess('Document supprimé.');
        $this->redirect('/dossiers/' . (int)$dossier['id']);
    }

    /* ================= CHECKLIST DOCUMENTS OBLIGATOIRES ================= */

    public function obligatoireBasculer(Request $request, string $id, string $documentObligatoireId): void
    {
        $this->authorize('dossiers.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $dossier = $this->trouverOuAbandonner((int)$id);

        $documentId = null;
        try {
            $documentId = $this->traiterDocumentJoint(
                $_FILES['fichier'] ?? null, (int)$dossier['id'], 'obligatoire', 'Pièce jointe', $tid, (int)$courant['id'], true
            );
        } catch (RuntimeException $e) {
            $this->flashError($e->getMessage());
            $this->redirect('/dossiers/' . (int)$dossier['id']);
            return;
        }

        $recu = (bool)$request->post('recu', false);
        $this->dossiers->basculerObligatoire(
            (int)$dossier['id'], (int)$documentObligatoireId, $tid, $recu, $documentId, (int)$courant['id']
        );
        LogService::enregistrer('dossier.obligatoire_maj', 'dossier', (int)$dossier['id'], 'succes', ['document_obligatoire' => (int)$documentObligatoireId]);
        $this->flashSuccess('Mis à jour.');
        $this->redirect('/dossiers/' . (int)$dossier['id']);
    }

    public function panierModifier(Request $request, string $id): void
    {
        $this->authorize('dossiers.modifier');

        $courant = Gate::user();
        $tid     = (int)$courant['tenant_id'];
        $dossier = $this->trouverOuAbandonner((int)$id);

        $prestationId = (int)$request->post('prestation_id', 0);
        $modifs = [];

        if ($request->post('quantite') !== null) {
            $modifs['quantite'] = (int)$request->post('quantite');
        }
        if ($request->post('offert') !== null && (bool)param('activer_panier_offert', true)) {
            $modifs['offert'] = (int)$request->post('offert');
        }
        if ($request->post('cpf') !== null && (bool)param('activer_panier_cpf', true)) {
            $modifs['cpf'] = (int)$request->post('cpf');
        }

        if ($modifs !== []) {
            $this->dossiers->modifierLignePanier((int)$dossier['id'], $prestationId, $tid, $modifs);
            LogService::enregistrer('panier.ligne_modifiee', 'dossier', (int)$dossier['id'], 'succes', $modifs);
        }

        Response::json([
            'ok'      => true,
            'message' => 'Panier mis à jour.',
            'panier'  => $this->panierPourJson((int)$dossier['id'], $tid),
        ]);
    }

    /* ---------- Helpers ---------- */

    /** Sérialisation du panier (lignes + total) pour les réponses AJAX. */
    private function panierPourJson(int $dossierId, int $tid): array
    {
        $lignes = [];
        foreach ($this->dossiers->panier($dossierId, $tid) as $l) {
            $lignes[] = [
                'prestation_id' => (int)$l['prestation_id'],
                'titre'         => (string)$l['titre'],
                'sku'           => (string)($l['sku'] ?? ''),
                'prix_vente'    => (float)$l['prix_vente'],
                'quantite'      => (int)$l['quantite'],
                'total_ligne'   => (float)$l['total_ligne'],
                'offert'        => (int)$l['offert'],
                'cpf'           => (int)$l['cpf'],
            ];
        }
        return [
            'lignes' => $lignes,
            'total'  => $this->dossiers->totalPanier($dossierId, $tid),
        ];
    }

    /**
     * Formules actives limitées au domaine indiqué
     * (« 0 » ou vide = toutes, aucun domaine choisi).
     *
     * @return array<int, array<string, mixed>>
     */
    private function formulesDuDomaine(string $domaineId): array
    {
        $tid      = (int)Gate::user()['tenant_id'];
        $formules = $this->formules->toutesPourTenant($tid, true);
        $domaine = (int)$domaineId;

        if ($domaine <= 0) {
            return $formules;
        }

        $filtrees = [];
        foreach ($formules as $f) {
            if ($f['domaine_id'] === null || (int)$f['domaine_id'] === $domaine) {
                $filtrees[] = $f;
            }
        }
        return $filtrees;
    }

    /** @return array<string, mixed> */
    private function options(): array
    {
        $tid = (int)Gate::user()['tenant_id'];

        $domaines = [];
        foreach ($this->domaines->toutesPourTenant($tid, true) as $d) {
            $domaines[(int)$d['id']] = ['nom' => (string)$d['nom'], 'couleur' => (string)($d['couleur'] ?? '')];
        }

        $typesPermis = [];
        foreach ($this->typesPermis->toutesPourTenant($tid, true) as $tp) {
            $typesPermis[(int)$tp['id']] = (string)$tp['nom'];
        }

        $prestations = [];
        foreach ($this->prestations->toutesPourTenant($tid, true) as $p) {
            $prestations[] = [
                'id'         => (int)$p['id'],
                'titre'      => (string)$p['titre'],
                'prix_vente' => $p['prix_vente'],
            ];
        }

        $formules = [];
        foreach ($this->formules->toutesPourTenant($tid, true) as $f) {
            $formules[] = [
                'id'              => (int)$f['id'],
                'nom'            => (string)$f['nom'],
                'domaine_id'     => $f['domaine_id'] !== null ? (int)$f['domaine_id'] : null,
                'montant_ttc'    => $f['montant_ttc'] ?? 0,
                'nb_prestations' => (int)($f['nb_prestations'] ?? 0),
            ];
        }

        return [
            'optionsDomaines'   => $domaines,
            'optionsTypesPermis' => $typesPermis,
            'optionsPrestations' => $prestations,
            'formules'          => $formules,
            'panierActifOffert' => (bool)param('activer_panier_offert', true),
            'panierActifCpf'   => (bool)param('activer_panier_cpf', true),
        ];
    }

    private function decimalOuNull(string $valeur): ?string
    {
        $valeur = trim(str_replace(',', '.', $valeur));
        return $valeur !== '' && is_numeric($valeur) ? $valeur : null;
    }

    /**
     * Enregistre un fichier joint (CERFA, document libre, pièce obligatoire)
     * sur le disque puis crée sa ligne dans `documents`. Formats acceptés :
     * PDF, JPG, PNG — 10 Mo max.
     *
     * @return int|null l'id du document créé, ou null si aucun fichier n'a
     *                   été envoyé et que $requis vaut false (sinon exception).
     */
    private function traiterDocumentJoint(
        ?array $fichier, int $dossierId, string $type, string $libelle, int $tenantId, int $auteurId, bool $requis = false
    ): ?int {
        if ($fichier === null || (int)($fichier['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if ($requis) {
                throw new RuntimeException('Aucun fichier reçu.');
            }
            return null;
        }
        if ((int)($fichier['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Échec de l\'envoi du fichier (code ' . (int)$fichier['error'] . ').');
        }
        if ((int)($fichier['size'] ?? 0) > 10485760) {
            throw new RuntimeException('Le fichier dépasse 10 Mo.');
        }

        $mime = '';
        if (class_exists('finfo')) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime  = (string)$finfo->file($fichier['tmp_name']);
        } elseif (function_exists('mime_content_type')) {
            $mime = (string)@mime_content_type($fichier['tmp_name']);
        }

        $autorises = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!isset($autorises[$mime])) {
            throw new RuntimeException('Format de fichier non autorisé (PDF, JPG ou PNG uniquement).');
        }

        $repertoire = STORAGE_PATH . '/uploads/dossiers/' . $dossierId;
        if (!is_dir($repertoire)) {
            mkdir($repertoire, 0755, true);
        }
        $nom = bin2hex(random_bytes(16)) . '.' . $autorises[$mime];
        if (!move_uploaded_file($fichier['tmp_name'], $repertoire . '/' . $nom)) {
            throw new RuntimeException('Impossible d\'enregistrer le fichier.');
        }

        return $this->documents->ajouter([
            'objet_type'   => 'dossier',
            'objet_id'     => $dossierId,
            'type'         => $type,
            'libelle'      => $libelle,
            'nom_original' => (string)$fichier['name'],
            'chemin'       => 'dossiers/' . $dossierId . '/' . $nom,
            'mime'         => $mime,
            'taille'       => (int)$fichier['size'],
        ], $tenantId, $auteurId);
    }

    /** @return array<string, mixed> */
    private function trouverOuAbandonner(int $id): array
    {
        $dossier = $this->dossiers->trouver($id, (int)Gate::user()['tenant_id'], Gate::scopeAgence());
        if ($dossier === null) {
            abort(404, 'Dossier introuvable', 'Ce dossier n\'existe pas ou n\'est pas dans votre périmètre.', '/eleves');
        }
        return $dossier;
    }
}