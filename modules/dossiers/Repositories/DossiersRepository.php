<?php
// fichier : modules/dossiers/Repositories/DossiersRepository.php — v0.51
declare(strict_types=1);

namespace Modules\Dossiers\Repositories;

use App\Core\Database;

/**
 * Dossiers (CDC §26). RÈGLE ABSOLUE : JAMAIS de suppression physique.
 */
final class DossiersRepository
{
    private const BASE = 'SELECT d.*, e.nom AS eleve_nom, e.prenom AS eleve_prenom, a.agence_nom, a.agence_couleur,
            tp.nom AS type_permis_nom, dm.nom AS domaine_nom, dm.nb_echeances_max, f.nom AS formule_nom,
            (SELECT COUNT(*) FROM evenements ev WHERE ev.dossier_id = d.id AND ev.supprimer = 0) AS nb_evenements,
            (SELECT COUNT(*) FROM dossier_prestations dp WHERE dp.dossier_id = d.id AND dp.supprimer = 0) AS nb_prestations
            FROM dossiers d
            INNER JOIN eleves e ON e.id = d.eleve_id
            LEFT JOIN agences a ON a.id = d.agence_id
            LEFT JOIN types_permis tp ON tp.id = d.type_permis_id
            LEFT JOIN domaines dm ON dm.id = d.domaine_id
            LEFT JOIN formules f ON f.id = d.formule_id';

    /** Dossiers actifs d'un élève (fiche élève — onglet Dossiers). */
    public function pourEleve(int $eleveId, int $tenantId): array
    {
        return Database::fetchAll(
            self::BASE . ' WHERE d.tenant_id = :tenant AND d.eleve_id = :eleve AND d.supprimer = 0 ORDER BY d.ajout_le DESC',
            ['tenant' => $tenantId, 'eleve' => $eleveId]
        );
    }

    /** Événements de tous les dossiers actifs d'un élève (onglet Planning). */
    public function evenementsPourEleve(int $eleveId, int $tenantId): array
    {
        return Database::fetchAll(
            'SELECT ev.*, d.id AS dossier_id
             FROM evenements ev
             INNER JOIN dossiers d ON d.id = ev.dossier_id
                 AND d.tenant_id = :tenant AND d.supprimer = 0
                 AND d.eleve_id = :eleve
             WHERE ev.supprimer = 0
             ORDER BY ev.date_heure DESC',
            ['tenant' => $tenantId, 'eleve' => $eleveId]
        );
    }

    /** @return array<string, mixed>|null */
    public function trouver(int $id, int $tenantId, ?int $scope): ?array
    {
        $params = ['id' => $id, 'tenant' => $tenantId];
        $sql = self::BASE . ' WHERE d.id = :id AND d.tenant_id = :tenant AND d.supprimer = 0';
        if ($scope !== null) {
            $sql .= ' AND d.agence_id = :scope';
            $params['scope'] = $scope;
        }
        $sql .= ' LIMIT 1';
        return Database::fetch($sql, $params);
    }

    /** @param array<string, ?string|int|float> $d */
    public function creer(array $d, int $tenantId, int $auteurId): int
    {
        Database::execute(
            'INSERT INTO dossiers
                (tenant_id, eleve_id, agence_id, statut, domaine_id, type_permis_id,
                 nb_heures, montant_total, notes, actif, ajout_le, ajout_par)
             VALUES
                (:tenant, :eleve, :agence, :statut, :domaine, :type_permis,
                 :heures, :montant, :notes, 1, :maintenant, :auteur)',
            [
                'tenant'     => $tenantId,
                'eleve'     => (int)$d['eleve_id'],
                'agence'    => $d['agence_id'] ?? null,
                'statut'    => (string)($d['statut'] ?? 'en_cours'),
                'domaine'   => $d['domaine_id'] ?? null,
                'type_permis' => $d['type_permis_id'] ?? null,
                'heures'    => $d['nb_heures'] ?? null,
                'montant'   => $d['montant_total'] ?? null,
                'notes'     => $d['notes'] ?? null,
                'maintenant' => date('Y-m-d H:i:s'),
                'auteur'    => $auteurId,
            ]
        );
        return Database::lastInsertId();
    }

    /** @param array<string, ?string|int|float> $d */
    public function modifier(int $id, int $tenantId, array $d): void
    {
        $statut     = (string)($d['statut'] ?? 'en_cours');
        $valideLe   = in_array($statut, ['valide', 'confirme'], true) ? date('Y-m-d H:i:s') : null;
        $confirmeLe = $statut === 'confirme' ? date('Y-m-d H:i:s') : null;

        Database::execute(
            'UPDATE dossiers SET
                agence_id = :agence, statut = :statut,
                domaine_id = :domaine, type_permis_id = :type_permis,
                nb_heures = :heures, montant_total = :montant, notes = :notes,
                valide_le = :valide_le, confirme_le = :confirme_le
             WHERE id = :id AND tenant_id = :tenant',
            [
                'agence'     => $d['agence_id'] ?? null,
                'statut'    => $statut,
                'domaine'  => $d['domaine_id'] ?? null,
                'type_permis' => $d['type_permis_id'] ?? null,
                'heures'    => $d['nb_heures'] ?? null,
                'montant'   => $d['montant_total'] ?? null,
                'notes'    => $d['notes'] ?? null,
                'valide_le'  => $valideLe,
                'confirme_le' => $confirmeLe,
                'id'        => $id,
                'tenant'    => $tenantId,
            ]
        );
    }

    /** Archivage logique (règle absolue). */
    public function archiverLogique(int $id, int $tenantId, int $auteurId): void
    {
        Database::execute(
            "UPDATE dossiers SET supprimer = 1, supprimer_par = :auteur, supprimer_date = :quand, actif = 0, motif_suppression = 'archive'
             WHERE id = :id AND tenant_id = :tenant",
            ['auteur' => $auteurId, 'quand' => date('Y-m-d H:i:s'), 'id' => $id, 'tenant' => $tenantId]
        );
    }

    /** Archivage récursif : tous les dossiers actifs d'un élève (§58). */
    public function archiverPourEleve(int $eleveId, int $tenantId, int $auteurId): void
    {
        Database::execute(
            'UPDATE dossiers SET supprimer = 1, supprimer_par = :auteur, supprimer_date = :quand, actif = 0
             WHERE eleve_id = :eleve AND tenant_id = :tenant AND supprimer = 0',
            ['auteur' => $auteurId, 'quand' => date('Y-m-d H:i:s'), 'eleve' => $eleveId, 'tenant' => $tenantId]
        );
    }

    /* ---------- PANIER ---------- */

    /** Lignes du panier d'un dossier (avec prix et totaux de ligne). */
    public function panier(int $dossierId, int $tenantId): array
    {
        $cpfZero = (bool)param('cpf_montant_zero', true);
        return Database::fetchAll(
            'SELECT dp.*, pr.titre, pr.description, pr.prix_vente, pr.sku,
                    (CASE WHEN dp.cpf = 1 AND :cpf_zero = 1 THEN 0 ELSE dp.quantite * pr.prix_vente END) AS total_ligne
             FROM dossier_prestations dp
             INNER JOIN prestations pr ON pr.id = dp.prestation_id
             WHERE dp.dossier_id = :dossier AND dp.tenant_id = :tenant AND dp.supprimer = 0
             ORDER BY pr.titre',
            ['dossier' => $dossierId, 'tenant' => $tenantId, 'cpf_zero' => $cpfZero ? 1 : 0]
        );
    }

    /** Total du panier (offert = 0 € ; CPF = 0 € si le paramètre cpf_montant_zero est actif). */
    public function totalPanier(int $dossierId, int $tenantId): float
    {
        $cpfZero = (bool)param('cpf_montant_zero', true);
        $row = Database::fetch(
            'SELECT COALESCE(SUM(CASE WHEN dp.offert = 1 OR (dp.cpf = 1 AND :cpf_zero = 1) THEN 0 ELSE dp.quantite * pr.prix_vente END), 0) AS total
             FROM dossier_prestations dp
             INNER JOIN prestations pr ON pr.id = dp.prestation_id
             WHERE dp.dossier_id = :dossier AND dp.tenant_id = :tenant AND dp.supprimer = 0',
            ['dossier' => $dossierId, 'tenant' => $tenantId, 'cpf_zero' => $cpfZero ? 1 : 0]
        );
        return (float)($row['total'] ?? 0);
    }

    /**
     * Ajouter une prestation — REFUSE le doublon (directive : jamais deux fois
     * la même prestation). Retourne false si déjà présente.
     */
    public function ajouterAuPanier(int $dossierId, int $prestationId, int $quantite, int $tenantId, int $auteurId): bool
    {
        $dejaLa = Database::fetch(
            'SELECT id FROM dossier_prestations
             WHERE dossier_id = :dossier AND prestation_id = :prestation
               AND tenant_id = :tenant AND supprimer = 0
             LIMIT 1',
            ['dossier' => $dossierId, 'prestation' => $prestationId, 'tenant' => $tenantId]
        );
        if ($dejaLa !== null) {
            return false;
        }

        Database::execute(
            'INSERT INTO dossier_prestations
                (tenant_id, dossier_id, prestation_id, quantite, ajout_le, ajout_par)
             VALUES
                (:tenant, :dossier, :prestation, :quantite, :maintenant, :auteur)',
            [
                'tenant'     => $tenantId,
                'dossier'   => $dossierId,
                'prestation' => $prestationId,
                'quantite'  => $quantite,
                'maintenant' => date('Y-m-d H:i:s'),
                'auteur'    => $auteurId,
            ]
        );
        $this->recalculerMontant($dossierId, $tenantId);
        return true;
    }

    /**
     * Ajouter toutes les prestations d'une formule (sauf doublons).
     * Retourne le nombre réellement ajoutées.
     */
    public function ajouterFormuleAuPanier(int $dossierId, int $formuleId, int $tenantId, int $auteurId): int
    {
        $prestations = Database::fetchAll(
            'SELECT fp.prestation_id, fp.quantite
             FROM formule_prestations fp WHERE fp.formule_id = :formule',
            ['formule' => $formuleId]
        );
        $ajoutees = 0;
        foreach ($prestations as $p) {
            if ($this->ajouterAuPanier($dossierId, (int)$p['prestation_id'], (int)$p['quantite'], $tenantId, $auteurId) === true) {
                $ajoutees++;
            }
        }
        return $ajoutees;
    }

    /** Retirer une prestation du panier (logique). */
    public function retirerDuPanier(int $dossierId, int $prestationId, int $tenantId, int $auteurId): void
    {
        Database::execute(
            'UPDATE dossier_prestations
             SET supprimer = 1, supprimer_par = :auteur, supprimer_date = :quand
             WHERE dossier_id = :dossier AND prestation_id = :prestation AND tenant_id = :tenant',
            ['auteur' => $auteurId, 'quand' => date('Y-m-d H:i:s'),
             'dossier' => $dossierId, 'prestation' => $prestationId, 'tenant' => $tenantId]
        );
        $this->recalculerMontant($dossierId, $tenantId);
    }

    /**
     * Vider entièrement le panier (logique) — utilisé lors d'un changement
     * de type de formation (à la carte / formule) en cours de saisie,
     * après confirmation de l'utilisateur (directive).
     */
    public function viderPanier(int $dossierId, int $tenantId, int $auteurId): void
    {
        Database::execute(
            'UPDATE dossier_prestations
             SET supprimer = 1, supprimer_par = :auteur, supprimer_date = :quand
             WHERE dossier_id = :dossier AND tenant_id = :tenant AND supprimer = 0',
            ['auteur' => $auteurId, 'quand' => date('Y-m-d H:i:s'), 'dossier' => $dossierId, 'tenant' => $tenantId]
        );
        $this->recalculerMontant($dossierId, $tenantId);
    }

    /**
     * Modifier une ligne du panier (quantité / offert / cpf —
     * l'activation d'offert et cpf est contrôlée par les paramètres
     * activer_panier_offert et activer_panier_cpf (vérifiés par le contrôleur).
     *
     * @param array<string, int> $modifs
     */
    public function modifierLignePanier(int $dossierId, int $prestationId, int $tenantId, array $modifs): void
    {
        $sets   = [];
        $params = ['dossier' => $dossierId, 'prestation' => $prestationId, 'tenant' => $tenantId];

        if (isset($modifs['quantite'])) {
            $sets[] = 'quantite = :quantite';
            $params['quantite'] = max(1, (int)$modifs['quantite']);
        }
        if (isset($modifs['offert'])) {
            $sets[] = 'offert = :offert';
            $params['offert'] = (int)$modifs['offert'];
        }
        if (isset($modifs['cpf'])) {
            $sets[] = 'cpf = :cpf';
            $params['cpf'] = (int)$modifs['cpf'];
        }
        if ($sets !== []) {
            Database::execute(
                'UPDATE dossier_prestations SET ' . implode(', ', $sets)
                . ' WHERE dossier_id = :dossier AND prestation_id = :prestation AND tenant_id = :tenant',
                $params
            );
            $this->recalculerMontant($dossierId, $tenantId);
        }
    }

    /** Recalcul du montant TTC stocké dans le dossier. */
    private function recalculerMontant(int $dossierId, int $tenantId): void
    {
        $total = self::totalPanier($dossierId, $tenantId);
        Database::execute(
            'UPDATE dossiers SET montant_ttc = :total WHERE id = :dossier AND tenant_id = :tenant',
            ['total' => $total, 'dossier' => $dossierId, 'tenant' => $tenantId]
        );
    }

    /* ================= ÉTAT DU DOSSIER (panier / contrat) ================= */

    /** Valider le panier — passage 'a_confirmer' → 'panier_valide' (irréversible depuis l'écran). */
    public function validerPanier(int $dossierId, int $tenantId): void
    {
        Database::execute(
            "UPDATE dossiers SET etat_contrat = 'panier_valide', panier_valide_le = :quand
             WHERE id = :id AND tenant_id = :tenant AND etat_contrat = 'a_confirmer'",
            ['quand' => date('Y-m-d H:i:s'), 'id' => $dossierId, 'tenant' => $tenantId]
        );
    }

    /** Marquer le contrat comme généré — 'panier_valide' → 'contrat_genere'. */
    public function genererContrat(int $dossierId, int $tenantId): void
    {
        Database::execute(
            "UPDATE dossiers SET etat_contrat = 'contrat_genere'
             WHERE id = :id AND tenant_id = :tenant AND etat_contrat = 'panier_valide'",
            ['id' => $dossierId, 'tenant' => $tenantId]
        );
    }

    /** Enregistrer le mode (à la carte / formule) choisi — affiché dans la card récap. */
    public function enregistrerMode(int $dossierId, int $tenantId, string $mode, ?int $formuleId): void
    {
        Database::execute(
            'UPDATE dossiers SET mode = :mode, formule_id = :formule WHERE id = :id AND tenant_id = :tenant',
            ['mode' => $mode, 'formule' => $formuleId, 'id' => $dossierId, 'tenant' => $tenantId]
        );
    }

    /* ================= REMISE (un seul justificatif, montant fixe) ================= */

    /** @param array<string, mixed> $d */
    public function enregistrerRemise(int $dossierId, int $tenantId, array $d, int $auteurId): void
    {
        Database::execute(
            'UPDATE dossiers SET remise_justificatif = :justificatif, remise_montant = :montant,
                    remise_commentaire = :commentaire, remise_ajout_par = :auteur, remise_ajout_le = :quand
             WHERE id = :id AND tenant_id = :tenant',
            [
                'justificatif' => $d['justificatif'], 'montant' => $d['montant'], 'commentaire' => $d['commentaire'],
                'auteur' => $auteurId, 'quand' => date('Y-m-d H:i:s'), 'id' => $dossierId, 'tenant' => $tenantId,
            ]
        );
    }

    public function supprimerRemise(int $dossierId, int $tenantId): void
    {
        Database::execute(
            'UPDATE dossiers SET remise_justificatif = NULL, remise_montant = NULL, remise_commentaire = NULL,
                    remise_ajout_par = NULL, remise_ajout_le = NULL
             WHERE id = :id AND tenant_id = :tenant',
            ['id' => $dossierId, 'tenant' => $tenantId]
        );
    }

    /* ================= DÉCLARATIONS (CERFA / EDISER / NEPH) ================= */

    /** @return array<string, array<string, mixed>> indexé par type. */
    public function declarations(int $dossierId, int $tenantId): array
    {
        $lignes = Database::fetchAll(
            'SELECT * FROM dossier_declarations WHERE dossier_id = :dossier AND tenant_id = :tenant',
            ['dossier' => $dossierId, 'tenant' => $tenantId]
        );
        $parType = [];
        foreach ($lignes as $l) {
            $parType[(string)$l['type']] = $l;
        }
        return $parType;
    }

    /** @param array<string, mixed> $d */
    public function enregistrerDeclaration(int $dossierId, int $tenantId, string $type, array $d, int $auteurId): void
    {
        Database::execute(
            'INSERT INTO dossier_declarations (tenant_id, dossier_id, type, numero, date_envoi, date_reception, commentaire, document_id, ajout_le, ajout_par)
             VALUES (:tenant, :dossier, :type, :numero, :date_envoi, :date_reception, :commentaire, :document, :maintenant, :auteur)
             ON DUPLICATE KEY UPDATE
                numero = VALUES(numero), date_envoi = VALUES(date_envoi), date_reception = VALUES(date_reception),
                commentaire = VALUES(commentaire),
                document_id = COALESCE(VALUES(document_id), document_id),
                ajout_le = VALUES(ajout_le), ajout_par = VALUES(ajout_par)',
            [
                'tenant' => $tenantId, 'dossier' => $dossierId, 'type' => $type,
                'numero' => $d['numero'] ?? null, 'date_envoi' => $d['date_envoi'] ?? null,
                'date_reception' => $d['date_reception'] ?? null, 'commentaire' => $d['commentaire'] ?? null,
                'document' => $d['document_id'] ?? null, 'maintenant' => date('Y-m-d H:i:s'), 'auteur' => $auteurId,
            ]
        );
    }

    /* ================= ÉCHÉANCES ================= */

    /** @return array<int, array<string, mixed>> */
    public function echeances(int $dossierId, int $tenantId): array
    {
        return Database::fetchAll(
            'SELECT de.*, mp.nom AS mode_paiement_nom
             FROM dossier_echeances de
             LEFT JOIN modes_paiement mp ON mp.id = de.mode_paiement_id
             WHERE de.dossier_id = :dossier AND de.tenant_id = :tenant AND de.supprimer = 0
             ORDER BY de.numero_echeance',
            ['dossier' => $dossierId, 'tenant' => $tenantId]
        );
    }

    public function nombreEcheances(int $dossierId, int $tenantId): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM dossier_echeances WHERE dossier_id = :dossier AND tenant_id = :tenant AND supprimer = 0',
            ['dossier' => $dossierId, 'tenant' => $tenantId]
        );
        return (int)($row['n'] ?? 0);
    }

    /**
     * Définir en bloc les échéances du dossier (sélection du nombre puis
     * saisie groupée — directive). Remplace toute échéance existante tant
     * que le contrat n'est pas généré (contrôlé par le contrôleur).
     *
     * @param array<int, array{date_echeance:string, mode_paiement_id:?int, montant_ttc:string}> $lignes
     */
    public function definirEcheances(int $dossierId, int $tenantId, array $lignes, int $auteurId): void
    {
        Database::execute(
            'UPDATE dossier_echeances SET supprimer = 1, supprimer_par = :auteur, supprimer_date = :quand
             WHERE dossier_id = :dossier AND tenant_id = :tenant AND supprimer = 0',
            ['auteur' => $auteurId, 'quand' => date('Y-m-d H:i:s'), 'dossier' => $dossierId, 'tenant' => $tenantId]
        );
        $numero = 1;
        foreach ($lignes as $ligne) {
            $this->ajouterEcheance($dossierId, $tenantId, [
                'numero_echeance'  => $numero,
                'date_echeance'    => $ligne['date_echeance'],
                'mode_paiement_id' => $ligne['mode_paiement_id'],
                'montant_ttc'      => $ligne['montant_ttc'],
            ], $auteurId);
            $numero++;
        }
    }

    /** @param array<string, mixed> $d */
    public function ajouterEcheance(int $dossierId, int $tenantId, array $d, int $auteurId): int
    {
        Database::execute(
            'INSERT INTO dossier_echeances (tenant_id, dossier_id, numero_echeance, date_echeance, mode_paiement_id, montant_ttc, ajout_le, ajout_par)
             VALUES (:tenant, :dossier, :numero, :date_echeance, :mode, :montant, :maintenant, :auteur)',
            [
                'tenant' => $tenantId, 'dossier' => $dossierId, 'numero' => $d['numero_echeance'],
                'date_echeance' => $d['date_echeance'], 'mode' => $d['mode_paiement_id'] ?? null,
                'montant' => $d['montant_ttc'], 'maintenant' => date('Y-m-d H:i:s'), 'auteur' => $auteurId,
            ]
        );
        return Database::lastInsertId();
    }

    /** @param array<string, mixed> $d */
    public function modifierEcheance(int $id, int $tenantId, array $d): void
    {
        $sets   = [];
        $params = ['id' => $id, 'tenant' => $tenantId];
        foreach (['date_echeance', 'mode_paiement_id', 'montant_ttc', 'date_paiement', 'etat', 'commentaire'] as $champ) {
            if (array_key_exists($champ, $d)) {
                $sets[] = $champ . ' = :' . $champ;
                $params[$champ] = $d[$champ];
            }
        }
        if ($sets === []) {
            return;
        }
        Database::execute(
            'UPDATE dossier_echeances SET ' . implode(', ', $sets) . ' WHERE id = :id AND tenant_id = :tenant',
            $params
        );
    }

    public function supprimerEcheance(int $id, int $tenantId, int $auteurId): void
    {
        Database::execute(
            'UPDATE dossier_echeances SET supprimer = 1, supprimer_par = :auteur, supprimer_date = :quand
             WHERE id = :id AND tenant_id = :tenant',
            ['auteur' => $auteurId, 'quand' => date('Y-m-d H:i:s'), 'id' => $id, 'tenant' => $tenantId]
        );
    }

    /* ================= CHECKLIST DOCUMENTS OBLIGATOIRES ================= */

    /** @return array<int, array<string, mixed>> */
    public function checklistObligatoires(int $dossierId, int $tenantId): array
    {
        return Database::fetchAll(
            'SELECT dob.id AS document_obligatoire_id, dob.nom, dob.descriptif,
                    ddo.id AS ligne_id, COALESCE(ddo.recu, 0) AS recu, ddo.document_id
             FROM documents_obligatoires dob
             LEFT JOIN dossier_documents_obligatoires ddo
                    ON ddo.document_obligatoire_id = dob.id AND ddo.dossier_id = :dossier
             WHERE dob.tenant_id = :tenant AND dob.actif = 1 AND dob.supprimer = 0
             ORDER BY dob.nom',
            ['dossier' => $dossierId, 'tenant' => $tenantId]
        );
    }

    public function basculerObligatoire(int $dossierId, int $documentObligatoireId, int $tenantId, bool $recu, ?int $documentId, int $auteurId): void
    {
        Database::execute(
            'INSERT INTO dossier_documents_obligatoires (tenant_id, dossier_id, document_obligatoire_id, recu, document_id, ajout_le, ajout_par)
             VALUES (:tenant, :dossier, :doc_obl, :recu, :document, :maintenant, :auteur)
             ON DUPLICATE KEY UPDATE
                recu = VALUES(recu), document_id = COALESCE(VALUES(document_id), document_id),
                ajout_le = VALUES(ajout_le), ajout_par = VALUES(ajout_par)',
            [
                'tenant' => $tenantId, 'dossier' => $dossierId, 'doc_obl' => $documentObligatoireId,
                'recu' => $recu ? 1 : 0, 'document' => $documentId, 'maintenant' => date('Y-m-d H:i:s'), 'auteur' => $auteurId,
            ]
        );
    }

    /* ================= SUPPRESSION (toujours logique — RÈGLE ABSOLUE) ================= */

    /** Distinct d'archiverLogique() par le motif, mais reste une suppression logique (jamais de DELETE). */
    public function supprimerAvecMotif(int $id, int $tenantId, int $auteurId, string $motif): void
    {
        Database::execute(
            'UPDATE dossiers SET supprimer = 1, supprimer_par = :auteur, supprimer_date = :quand, actif = 0, motif_suppression = :motif
             WHERE id = :id AND tenant_id = :tenant',
            ['auteur' => $auteurId, 'quand' => date('Y-m-d H:i:s'), 'motif' => $motif, 'id' => $id, 'tenant' => $tenantId]
        );
    }

}