<?php
// fichier : modules/prospects/Repositories/ProspectsRepository.php — v0.47
declare(strict_types=1);

namespace Modules\Prospects\Repositories;

use App\Core\Database;

final class ProspectsRepository
{
    private const PAR_PAGE = 12;

    private const BASE = 'SELECT p.*, d.nom AS domaine_nom, d.couleur AS domaine_couleur,
            tp.nom AS type_permis_nom, tp.initiale AS type_permis_initiale,
            a.agence_nom, a.agence_couleur, pv.provenance
            FROM prospects p
            LEFT JOIN domaines d ON d.id = p.domaine_id
            LEFT JOIN types_permis tp ON tp.id = p.type_permis_id
            LEFT JOIN agences a ON a.id = p.agence_id
            LEFT JOIN provenances pv ON pv.id = p.provenance_id';

    /** @param array<string, ?string> $filtres */
    private static function conditions(int $tenantId, ?int $scope, array $filtres, array &$params): string
    {
        $sql = ' WHERE p.tenant_id = :tenant AND p.supprimer = 0';
        $params['tenant'] = $tenantId;

        if ($scope !== null) {
            $sql .= ' AND p.agence_id = :scope';
            $params['scope'] = $scope;
        }

        $statut = (string)($filtres['statut'] ?? '');
        if ($statut === 'archive') {
            $sql .= " AND p.statut = 'archive'";
        } elseif ($statut === 'nouveau' || $statut === 'traite') {
            $sql .= ' AND p.statut = :statut';
            $params['statut'] = $statut;
        } else {
            $sql .= " AND p.statut != 'archive'";
        }

        if (!empty($filtres['q'])) {
            $sql .= ' AND (p.nom LIKE :q OR p.prenom LIKE :q OR p.email LIKE :q OR p.telephone LIKE :q)';
            $params['q'] = '%' . $filtres['q'] . '%';
        }
        return $sql;
    }

    /** @param array<string, ?string> $filtres */
    public function compter(int $tenantId, ?int $scope, array $filtres): int
    {
        $params = [];
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM prospects p' . self::conditions($tenantId, $scope, $filtres, $params),
            $params
        );
        return (int)($row['n'] ?? 0);
    }

    /** @param array<string, ?string> $filtres */
    public function paginer(int $tenantId, ?int $scope, array $filtres, int $page): array
    {
        $params = ['limite' => self::PAR_PAGE, 'offset' => max(0, ($page - 1) * self::PAR_PAGE)];
        $sql = self::BASE . self::conditions($tenantId, $scope, $filtres, $params)
            . ' ORDER BY p.ajout_le DESC, p.id DESC LIMIT :limite OFFSET :offset';
        return Database::fetchAll($sql, $params);
    }

    /** @return array{nouveau:int, traite:int, archive:int, convertis:int} */
    public function stats(int $tenantId, ?int $scope): array
    {
        $params = ['tenant' => $tenantId];
        $sql = 'SELECT statut, COUNT(*) AS n FROM prospects p WHERE p.tenant_id = :tenant AND p.supprimer = 0';
        if ($scope !== null) {
            $sql .= ' AND p.agence_id = :scope';
            $params['scope'] = $scope;
        }
        $sql .= ' GROUP BY statut';

        $stats = ['nouveau' => 0, 'traite' => 0, 'archive' => 0, 'convertis' => 0];
        foreach (Database::fetchAll($sql, $params) as $row) {
            $stats[(string)$row['statut']] = (int)$row['n'];
        }

        $params2 = ['tenant' => $tenantId];
        $sql2 = 'SELECT COUNT(*) AS n FROM prospects p WHERE p.tenant_id = :tenant AND p.supprimer = 0 AND p.convertis = 1';
        if ($scope !== null) {
            $sql2 .= ' AND p.agence_id = :scope';
            $params2['scope'] = $scope;
        }
        $stats['convertis'] = (int)(Database::fetch($sql2, $params2)['n'] ?? 0);
        return $stats;
    }

    /** @return array<string, mixed>|null */
    public function trouver(int $id, int $tenantId, ?int $scope): ?array
    {
        $params = ['id' => $id, 'tenant' => $tenantId];
        $sql = self::BASE . ' WHERE p.id = :id AND p.tenant_id = :tenant AND p.supprimer = 0';
        if ($scope !== null) {
            $sql .= ' AND p.agence_id = :scope';
            $params['scope'] = $scope;
        }
        $sql .= ' LIMIT 1';
        return Database::fetch($sql, $params);
    }

    /** @param array<string, ?string|int> $d */
    public function creer(array $d, int $tenantId, int $auteurId): int
    {
        Database::execute(
            'INSERT INTO prospects
                (tenant_id, agence_id, domaine_id, nom, prenom, date_naissance, lieu_naissance,
                 email, telephone, adresse, code_postal, ville, pays,
                 type_permis_id, parcours, commentaire, provenance_id, statut, ajout_le, ajout_par)
             VALUES
                (:tenant, :agence, :domaine, :nom, :prenom, :naissance, :lieu,
                 :email, :telephone, :adresse, :cp, :ville, :pays,
                 :type_permis, :parcours, :commentaire, :provenance, :statut, :maintenant, :auteur)',
            [
                'tenant'     => $tenantId,
                'agence'     => $d['agence_id'],
                'domaine'    => $d['domaine_id'],
                'nom'        => $d['nom'],
                'prenom'     => $d['prenom'],
                'naissance'  => $d['date_naissance'],
                'lieu'       => $d['lieu_naissance'],
                'email'      => $d['email'],
                'telephone'  => $d['telephone'],
                'adresse'    => $d['adresse'],
                'cp'         => $d['code_postal'],
                'ville'      => $d['ville'],
                'pays'       => $d['pays'],
                'type_permis'=> $d['type_permis_id'],
                'parcours'   => $d['parcours'],
                'commentaire'=> $d['commentaire'],
                'provenance' => $d['provenance_id'],
                'statut'     => 'nouveau',
                'maintenant' => date('Y-m-d H:i:s'),
                'auteur'     => $auteurId,
            ]
        );
        return Database::lastInsertId();
    }

    /** @param array<string, ?string|int> $d */
    public function modifier(int $id, int $tenantId, array $d): void
    {
        Database::execute(
            'UPDATE prospects SET
                agence_id = :agence, domaine_id = :domaine, nom = :nom, prenom = :prenom,
                date_naissance = :naissance, lieu_naissance = :lieu,
                email = :email, telephone = :telephone,
                adresse = :adresse, code_postal = :cp, ville = :ville, pays = :pays,
                type_permis_id = :type_permis, parcours = :parcours, commentaire = :commentaire
             WHERE id = :id AND tenant_id = :tenant',
            [
                'agence'     => $d['agence_id'],
                'domaine'    => $d['domaine_id'],
                'nom'        => $d['nom'],
                'prenom'     => $d['prenom'],
                'naissance'  => $d['date_naissance'],
                'lieu'       => $d['lieu_naissance'],
                'email'      => $d['email'],
                'telephone'  => $d['telephone'],
                'adresse'    => $d['adresse'],
                'cp'         => $d['code_postal'],
                'ville'      => $d['ville'],
                'pays'       => $d['pays'],
                'type_permis'=> $d['type_permis_id'],
                'parcours'   => $d['parcours'],
                'commentaire'=> $d['commentaire'],
                'id'         => $id,
                'tenant'     => $tenantId,
            ]
        );
    }

    public function traiter(int $id, int $tenantId): void
    {
        Database::execute(
            "UPDATE prospects SET statut = 'traite' WHERE id = :id AND tenant_id = :tenant",
            ['id' => $id, 'tenant' => $tenantId]
        );
    }

    public function archiver(int $id, int $tenantId, string $motif): void
    {
        Database::execute(
            "UPDATE prospects SET statut = 'archive', motif_archivage = :motif
             WHERE id = :id AND tenant_id = :tenant",
            ['motif' => $motif, 'id' => $id, 'tenant' => $tenantId]
        );
    }

    public function convertirEtArchiver(int $id, int $tenantId, int $auteurId, string $motif): void
    {
        Database::execute(
            "UPDATE prospects SET
                convertis = 1, convertis_par = :auteur, convertis_date = :quand,
                statut = 'archive', motif_archivage = :motif
             WHERE id = :id AND tenant_id = :tenant",
            ['auteur' => $auteurId, 'quand' => date('Y-m-d H:i:s'), 'motif' => $motif, 'id' => $id, 'tenant' => $tenantId]
        );
    }

    public function provenancePassant(int $tenantId): ?int
    {
        $row = Database::fetch(
            "SELECT id FROM provenances WHERE tenant_id = :t AND provenance = 'Passant' AND supprimer = 0 LIMIT 1",
            ['t' => $tenantId]
        );
        if ($row !== null) {
            return (int)$row['id'];
        }
        $row = Database::fetch(
            'SELECT id FROM provenances WHERE tenant_id = :t AND supprimer = 0 AND actif = 1 ORDER BY id LIMIT 1',
            ['t' => $tenantId]
        );
        return $row !== null ? (int)$row['id'] : null;
    }

    public function parPage(): int
    {
        return self::PAR_PAGE;
    }
}