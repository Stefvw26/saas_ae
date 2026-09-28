<?php
// fichier : modules/eleves/Repositories/ElevesRepository.php — v0.50
declare(strict_types=1);

namespace Modules\Eleves\Repositories;

use App\Core\Database;

/**
 * Élèves (CDC §50-59).
 * v0.50 : FIX TypeError — majBoite/majNiveauB ne retournent plus de bool
 * (Database::execute renvoie un int ; l'élève est déjà vérifié par le contrôleur).
 * RÈGLE ABSOLUE : JAMAIS de DELETE — suppression toujours logique.
 */
final class ElevesRepository
{
    private const PAR_PAGE = 20;

    private const BASE = 'SELECT e.*, a.agence_nom, a.agence_couleur, d.nom AS domaine_nom, d.couleur AS domaine_couleur,
            tp.nom AS type_permis_nom, pv.provenance
            FROM eleves e
            LEFT JOIN agences a ON a.id = e.agence_id
            LEFT JOIN domaines d ON d.id = e.domaine_id
            LEFT JOIN types_permis tp ON tp.id = e.type_permis_id
            LEFT JOIN provenances pv ON pv.id = e.provenance_id';

    /** @param array<string, ?string> $filtres */
    private static function conditions(int $tenantId, ?int $scope, array $filtres, array &$params): string
    {
        $sql = ' WHERE e.tenant_id = :tenant';
        $params['tenant'] = $tenantId;

        if (($filtres['statut'] ?? '') === 'archives') {
            $sql .= ' AND e.supprimer = 0 AND e.actif = 0';
        } elseif (($filtres['statut'] ?? '') === 'sans_dossier') {
            $sql .= ' AND e.supprimer = 0 AND e.actif = 1
                      AND NOT EXISTS (SELECT 1 FROM dossiers dd WHERE dd.eleve_id = e.id AND dd.supprimer = 0)';
        } elseif (!empty($filtres['statut_dossier'])) {
            $sql .= ' AND e.supprimer = 0 AND e.actif = 1
                      AND EXISTS (SELECT 1 FROM dossiers dd
                                  WHERE dd.eleve_id = e.id AND dd.supprimer = 0 AND dd.statut = :statut_dossier)';
            $params['statut_dossier'] = $filtres['statut_dossier'];
        } else {
            $sql .= ' AND e.supprimer = 0 AND e.actif = 1';
        }

        if ($scope !== null) {
            $sql .= ' AND e.agence_id = :scope';
            $params['scope'] = $scope;
        }
        if (!empty($filtres['q'])) {
            $sql .= ' AND (e.nom LIKE :q OR e.prenom LIKE :q OR e.email LIKE :q OR e.telephone LIKE :q)';
            $params['q'] = '%' . $filtres['q'] . '%';
        }
        if (!empty($filtres['domaine_id'])) {
            $sql .= ' AND e.domaine_id = :domaine';
            $params['domaine'] = (int)$filtres['domaine_id'];
        }
        if (!empty($filtres['agence_id'])) {
            $sql .= ' AND e.agence_id = :agence';
            $params['agence'] = (int)$filtres['agence_id'];
        }
        return $sql;
    }

    /** @param array<string, ?string> $filtres */
    public function compter(int $tenantId, ?int $scope, array $filtres): int
    {
        $params = [];
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM eleves e' . self::conditions($tenantId, $scope, $filtres, $params),
            $params
        );
        return (int)($row['n'] ?? 0);
    }

    /** @param array<string, ?string> $filtres */
    public function paginer(int $tenantId, ?int $scope, array $filtres, int $page): array
    {
        $params = ['limite' => self::PAR_PAGE, 'offset' => max(0, ($page - 1) * self::PAR_PAGE)];
        $sql = self::BASE . self::conditions($tenantId, $scope, $filtres, $params)
            . ' ORDER BY e.nom, e.prenom LIMIT :limite OFFSET :offset';
        return Database::fetchAll($sql, $params);
    }

    /** Élève consultable : jamais supprimé logiquement (404 sinon). */
    public function trouver(int $id, int $tenantId, ?int $scope): ?array
    {
        $params = ['id' => $id, 'tenant' => $tenantId];
        $sql = self::BASE . ' WHERE e.id = :id AND e.tenant_id = :tenant AND e.supprimer = 0';
        if ($scope !== null) {
            $sql .= ' AND e.agence_id = :scope';
            $params['scope'] = $scope;
        }
        $sql .= ' LIMIT 1';
        return Database::fetch($sql, $params);
    }

    /** @param array<string, ?string|int> $e */
    public function creer(array $e, int $tenantId, int $auteurId): int
    {
        Database::execute(
            'INSERT INTO eleves
                (tenant_id, agence_id, domaine_id, provenance_id, type_permis_id,
                 civilite, nom, prenom, date_naissance, lieu_naissance, email, telephone,
                 adresse, code_postal, ville, pays, type_boite, type_b,
                 responsable_nom, responsable_telephone, responsable_email,
                 actif, ajout_le, ajout_par)
             VALUES
                (:tenant, :agence, :domaine, :provenance, :type_permis,
                 :civilite, :nom, :prenom, :naissance, :lieu, :email, :telephone,
                 :adresse, :cp, :ville, :pays, :boite, :type_b,
                 :resp_nom, :resp_tel, :resp_mail,
                 :actif, :maintenant, :auteur)',
            [
                'tenant'    => $tenantId,
                'agence'    => $e['agence_id'] ?? null,
                'domaine'   => $e['domaine_id'] ?? null,
                'provenance'=> $e['provenance_id'] ?? null,
                'type_permis'=> $e['type_permis_id'] ?? null,
                'civilite'  => $e['civilite'] ?? null,
                'nom'       => $e['nom'],
                'prenom'    => $e['prenom'] ?? null,
                'naissance' => $e['date_naissance'] ?? null,
                'lieu'      => $e['lieu_naissance'] ?? null,
                'email'     => $e['email'] ?? null,
                'telephone' => $e['telephone'] ?? null,
                'adresse'   => $e['adresse'] ?? null,
                'cp'        => $e['code_postal'] ?? null,
                'ville'     => $e['ville'] ?? null,
                'pays'      => $e['pays'] ?? null,
                'boite'     => $e['type_boite'] ?? null,
                'type_b'    => $e['type_b'] ?? null,
                'resp_nom'  => $e['responsable_nom'] ?? null,
                'resp_tel'  => $e['responsable_telephone'] ?? null,
                'resp_mail' => $e['responsable_email'] ?? null,
                'actif'     => (int)($e['actif'] ?? 1),
                'maintenant'=> date('Y-m-d H:i:s'),
                'auteur'    => $auteurId,
            ]
        );
        return Database::lastInsertId();
    }

    /** @param array<string, ?string|int> $e */
    public function modifier(int $id, int $tenantId, array $e): void
    {
        Database::execute(
            'UPDATE eleves SET
                agence_id = :agence, domaine_id = :domaine, provenance_id = :provenance, type_permis_id = :type_permis,
                civilite = :civilite, nom = :nom, prenom = :prenom,
                date_naissance = :naissance, lieu_naissance = :lieu,
                email = :email, telephone = :telephone,
                adresse = :adresse, code_postal = :cp, ville = :ville, pays = :pays,
                type_boite = :boite, type_b = :type_b,
                responsable_nom = :resp_nom, responsable_telephone = :resp_tel, responsable_email = :resp_mail,
                actif = :actif
             WHERE id = :id AND tenant_id = :tenant',
            [
                'agence'    => $e['agence_id'] ?? null,
                'domaine'   => $e['domaine_id'] ?? null,
                'provenance'=> $e['provenance_id'] ?? null,
                'type_permis'=> $e['type_permis_id'] ?? null,
                'civilite'  => $e['civilite'] ?? null,
                'nom'       => $e['nom'],
                'prenom'    => $e['prenom'] ?? null,
                'naissance' => $e['date_naissance'] ?? null,
                'lieu'      => $e['lieu_naissance'] ?? null,
                'email'     => $e['email'] ?? null,
                'telephone' => $e['telephone'] ?? null,
                'adresse'   => $e['adresse'] ?? null,
                'cp'        => $e['code_postal'] ?? null,
                'ville'     => $e['ville'] ?? null,
                'pays'      => $e['pays'] ?? null,
                'boite'     => $e['type_boite'] ?? null,
                'type_b'    => $e['type_b'] ?? null,
                'resp_nom'  => $e['responsable_nom'] ?? null,
                'resp_tel'  => $e['responsable_telephone'] ?? null,
                'resp_mail' => $e['responsable_email'] ?? null,
                'actif'     => (int)($e['actif'] ?? 1),
                'id'        => $id,
                'tenant'    => $tenantId,
            ]
        );
    }

    /* ---------- SWITCHES (v0.50 — FIX TypeError : plus de retour bool) ---------- */

    /** Switch boîte : 'BA', 'BM' ou null. L'élève est vérifié par le contrôleur. */
    public function majBoite(int $id, int $tenantId, ?string $valeur): void
    {
        Database::execute(
            'UPDATE eleves SET type_boite = :v WHERE id = :id AND tenant_id = :tenant AND supprimer = 0',
            ['v' => $valeur, 'id' => $id, 'tenant' => $tenantId]
        );
    }

    /** Switch niveau B : 'B1'..'B5' ou null. */
    public function majNiveauB(int $id, int $tenantId, ?string $valeur): void
    {
        Database::execute(
            'UPDATE eleves SET type_b = :v WHERE id = :id AND tenant_id = :tenant AND supprimer = 0',
            ['v' => $valeur, 'id' => $id, 'tenant' => $tenantId]
        );
    }

    /* ---------- Transfert / Archivage / Suppression ---------- */

    public function transferer(int $id, int $tenantId, int $nouvelleAgenceId): void
    {
        Database::execute(
            'UPDATE eleves SET agence_id = :agence WHERE id = :id AND tenant_id = :tenant',
            ['agence' => $nouvelleAgenceId, 'id' => $id, 'tenant' => $tenantId]
        );
    }

    /** Archivage §58 (actif=0, consultable en « Archives »). */
    public function archiver(int $id, int $tenantId): void
    {
        Database::execute(
            'UPDATE eleves SET actif = 0 WHERE id = :id AND tenant_id = :tenant AND supprimer = 0',
            ['id' => $id, 'tenant' => $tenantId]
        );
    }

    /** Suppression LOGIQUE (JAMAIS de DELETE) — motif obligatoire. */
    public function supprimerLogique(int $id, int $tenantId, int $auteurId, string $motif): void
    {
        Database::execute(
            'UPDATE eleves SET
                supprimer = 1, supprimer_par = :auteur, supprimer_date = :quand,
                actif = 0, motif_suppression = :motif
             WHERE id = :id AND tenant_id = :tenant',
            ['auteur' => $auteurId, 'quand' => date('Y-m-d H:i:s'), 'motif' => $motif, 'id' => $id, 'tenant' => $tenantId]
        );
    }

    /* ---------- Annexes ---------- */

    public function anniversairesDuJour(int $tenantId, ?int $scope): array
    {
        $params = ['t' => $tenantId, 'md' => date('m-d')];
        $sql = 'SELECT prenom, nom FROM eleves
                WHERE tenant_id = :t AND supprimer = 0 AND actif = 1
                  AND date_naissance IS NOT NULL
                  AND DATE_FORMAT(date_naissance, "%m-%d") = :md';
        if ($scope !== null) {
            $sql .= ' AND agence_id = :s';
            $params['s'] = $scope;
        }
        $sql .= ' ORDER BY prenom, nom';

        $noms = [];
        foreach (Database::fetchAll($sql, $params) as $row) {
            $noms[] = trim((string)$row['prenom'] . ' ' . (string)$row['nom']);
        }
        return $noms;
    }

    /** Export CSV (actifs, périmètre = liste — CDC §27/§34). */
    public function exporter(int $tenantId, ?int $scope, string $q): array
    {
        $filtres = ['q' => $q, 'statut' => 'actifs'];
        $params = [];
        $sql = self::BASE . self::conditions($tenantId, $scope, $filtres, $params) . ' ORDER BY e.nom, e.prenom';
        return Database::fetchAll($sql, $params);
    }

    /** @return array{actifs:int, archives:int} */
    public function stats(int $tenantId, ?int $scope): array
    {
        $out = ['actifs' => 0, 'archives' => 0];
        foreach (['actifs', 'archives'] as $statut) {
            $params = [];
            $sql = 'SELECT COUNT(*) AS n FROM eleves e'
                . self::conditions($tenantId, $scope, ['statut' => $statut], $params);
            $out[$statut] = (int)(Database::fetch($sql, $params)['n'] ?? 0);
        }
        return $out;
    }

    public function parPage(): int { return self::PAR_PAGE; }
}