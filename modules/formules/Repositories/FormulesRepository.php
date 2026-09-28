<?php
// fichier : modules/formules/Repositories/FormulesRepository.php — v0.54 (fix)
declare(strict_types=1);

namespace Modules\Formules\Repositories;

use App\Core\Database;

/**
 * Formules : regroupement de prestations par domaine.
 * Montant TTC = somme (prix_vente × quantité) — calculé dynamiquement.
 * RÈGLE ABSOLUE : aucune suppression physique des formules.
 * Doublons de prestation REFUSÉS (directive : jamais deux fois).
 */
final class FormulesRepository
{
    private const PAR_PAGE = 20;

    private const BASE = 'SELECT f.*, d.nom AS domaine_nom, d.couleur AS domaine_couleur,
            (SELECT COALESCE(SUM(pr.prix_vente * fp.quantite), 0)
             FROM formule_prestations fp
             INNER JOIN prestations pr ON pr.id = fp.prestation_id
             WHERE fp.formule_id = f.id) AS montant_ttc,
            (SELECT COUNT(*) FROM formule_prestations fp WHERE fp.formule_id = f.id) AS nb_prestations
            FROM formules f
            LEFT JOIN domaines d ON d.id = f.domaine_id';

    private const SELECT_AVEC_LIAISON = 'SELECT fp.id AS fp_id, fp.quantite, pr.id AS prestation_id, pr.titre,
            pr.description, pr.prix_vente, pr.sku, pr.actif
            FROM formule_prestations fp
            INNER JOIN prestations pr ON pr.id = fp.prestation_id
            WHERE fp.formule_id = :formule
            ORDER BY pr.titre';

    public function compter(int $tenantId, string $q = ''): int
    {
        $params = ['tenant' => $tenantId];
        $sql = 'SELECT COUNT(*) AS n FROM formules f WHERE f.tenant_id = :tenant AND f.supprimer = 0';
        if ($q !== '') {
            $sql .= ' AND f.nom LIKE :q';
            $params['q'] = '%' . $q . '%';
        }
        return (int)(Database::fetch($sql, $params)['n'] ?? 0);
    }

    public function paginer(int $tenantId, string $q, int $page): array
    {
        $params = [
            'limite'  => self::PAR_PAGE,
            'offset'  => max(0, ($page - 1) * self::PAR_PAGE),
            'tenant' => $tenantId,
        ];
        $sql = self::BASE . ' WHERE f.tenant_id = :tenant AND f.supprimer = 0';
        if ($q !== '') {
            $sql .= ' AND f.nom LIKE :q';
            $params['q'] = '%' . $q . '%';
        }
        $sql .= ' ORDER BY f.nom LIMIT :limite OFFSET :offset';
        return Database::fetchAll($sql, $params);
    }

    public function toutesPourTenant(int $tenantId, bool $uniquementActives = true): array
    {
        $sql = self::BASE . ' WHERE f.tenant_id = :tenant AND f.supprimer = 0';
        if ($uniquementActives) {
            $sql .= ' AND f.actif = 1';
        }
        $sql .= ' ORDER BY f.nom';
        return Database::fetchAll($sql, ['tenant' => $tenantId]);
    }

    public function pourDomaine(int $domaineId, int $tenantId): array
    {
        return Database::fetchAll(
            self::BASE . ' WHERE f.tenant_id = :tenant AND f.domaine_id = :domaine
                 AND f.supprimer = 0 AND f.actif = 1
             ORDER BY f.nom',
            ['tenant' => $tenantId, 'domaine' => $domaineId]
        );
    }

    /** @return array<string, mixed>|null */
    public function trouver(int $id, int $tenantId): ?array
    {
        return Database::fetch(
            self::BASE . ' WHERE f.id = :id AND f.tenant_id = :tenant AND f.supprimer = 0 LIMIT 1',
            ['id' => $id, 'tenant' => $tenantId]
        );
    }

    /** Prestations d'une formule (fp_id = identifiant de la ligne de liaison). */
    public function prestations(int $formuleId): array
    {
        return Database::fetchAll(
            self::SELECT_AVEC_LIAISON,
            ['formule' => $formuleId]
        );
    }

    /** @param array<string, ?string|int> $f */
    public function creer(array $f, int $tenantId, int $auteurId): int
    {
        Database::execute(
            'INSERT INTO formules
                (tenant_id, domaine_id, nom, descriptif, actif, ajout_le, ajout_par)
             VALUES
                (:tenant, :domaine, :nom, :descriptif, :actif, :maintenant, :auteur)',
            [
                'tenant'     => $tenantId,
                'domaine'    => $f['domaine_id'] ?? null,
                'nom'        => $f['nom'],
                'descriptif' => $f['descriptif'] ?? null,
                'actif'      => (int)($f['actif'] ?? 1),
                'maintenant' => date('Y-m-d H:i:s'),
                'auteur'     => $auteurId,
            ]
        );
        return Database::lastInsertId();
    }

    /** @param array<string, ?string|int> $f */
    public function modifier(int $id, int $tenantId, array $f): void
    {
        Database::execute(
            'UPDATE formules SET
                domaine_id = :domaine, nom = :nom, descriptif = :descriptif, actif = :actif
             WHERE id = :id AND tenant_id = :tenant',
            [
                'domaine'    => $f['domaine_id'] ?? null,
                'nom'        => $f['nom'],
                'descriptif' => $f['descriptif'] ?? null,
                'actif'      => (int)($f['actif'] ?? 1),
                'id'         => $id,
                'tenant'     => $tenantId,
            ]
        );
    }

    public function supprimerLogique(int $id, int $tenantId, int $auteurId): void
    {
        Database::execute(
            'UPDATE formules
             SET supprimer = 1, supprimer_par = :auteur, supprimer_date = :quand, actif = 0
             WHERE id = :id AND tenant_id = :tenant',
            ['auteur' => $auteurId, 'quand' => date('Y-m-d H:i:s'), 'id' => $id, 'tenant' => $tenantId]
        );
    }

    /* ---------- Prestations de la formule ---------- */

    /**
     * Ajouter une prestation — REFUSE le doublon (directive).
     * Retourne false si déjà liée à la formule.
     */
    public function ajouterPrestation(int $formuleId, int $prestationId, int $quantite = 1): bool
    {
        $dejaLa = Database::fetch(
            'SELECT id FROM formule_prestations WHERE formule_id = :f AND prestation_id = :p LIMIT 1',
            ['f' => $formuleId, 'p' => $prestationId]
        );
        if ($dejaLa !== null) {
            return false;
        }

        Database::execute(
            'INSERT INTO formule_prestations (formule_id, prestation_id, quantite) VALUES (:f, :p, :q)',
            ['f' => $formuleId, 'p' => $prestationId, 'q' => max(1, $quantite)]
        );
        return true;
    }

    /** Formule propriétaire d'une ligne de liaison (contrôle tenant). */
    public function formuleIdDeLigne(int $fpId, int $tenantId): ?int
    {
        $row = Database::fetch(
            'SELECT fp.formule_id FROM formule_prestations fp
             INNER JOIN formules f ON f.id = fp.formule_id
                 AND f.tenant_id = :tenant AND f.supprimer = 0
             WHERE fp.id = :pid LIMIT 1',
            ['tenant' => $tenantId, 'pid' => $fpId]
        );
        return $row !== null ? (int)$row['formule_id'] : null;
    }

    /** Détacher une prestation (par identifiant de ligne). */
    public function retirerPrestationParId(int $fpId): void
    {
        Database::execute('DELETE FROM formule_prestations WHERE id = :id', ['id' => $fpId]);
    }

    /** Modifier la quantité d'une ligne. */
    public function modifierQuantiteParId(int $fpId, int $quantite): void
    {
        if ($quantite < 1) {
            $quantite = 1;
        }
        Database::execute(
            'UPDATE formule_prestations SET quantite = :q WHERE id = :id',
            ['q' => $quantite, 'id' => $fpId]
        );
    }

    public function parPage(): int
    {
        return self::PAR_PAGE;
    }
}