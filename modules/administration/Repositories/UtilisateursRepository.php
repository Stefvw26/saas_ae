<?php
// fichier : modules/administration/Repositories/UtilisateursRepository.php — v0.16
declare(strict_types=1);

namespace Modules\Administration\Repositories;

use App\Core\Database;

/**
 * CRUD utilisateurs (administration) — sans rattachement domaine (v0.16 :
 * l'agence est la référence). Requêtes filtrées tenant + périmètre d'agence.
 */
final class UtilisateursRepository
{
    private const BASE = 'SELECT u.id, u.login, u.mail, u.telephone, u.nom, u.prenom, u.couleur, u.droit, u.role,
            u.est_un_enseignant, u.agence, u.tous_droit, u.photographie, u.actif,
            u.date_de_naissance, u.civilite, u.ajout_le, u.ajout_par,
            a.agence_nom, a.agence_couleur, r.nom AS role_nom
            FROM utilisateurs u
            LEFT JOIN agences a ON a.id = u.agence
            LEFT JOIN roles r ON r.code = u.role';

    /** @param array<string, ?string> $filtres */
    private static function conditions(int $tenantId, ?int $scope, array $filtres, array &$params): string
    {
        $sql = ' WHERE u.tenant_id = :tenant AND u.supprimer = 0';
        $params['tenant'] = $tenantId;

        if ($scope !== null) {
            $sql .= ' AND u.agence = :scope';
            $params['scope'] = $scope;
        }
        if (!empty($filtres['q'])) {
            $sql .= ' AND (u.nom LIKE :q OR u.prenom LIKE :q OR u.login LIKE :q OR u.mail LIKE :q OR u.telephone LIKE :q)';
            $params['q'] = '%' . $filtres['q'] . '%';
        }
        if (!empty($filtres['role'])) {
            $sql .= ' AND u.role = :role';
            $params['role'] = $filtres['role'];
        }
        if (!empty($filtres['agence'])) {
            $sql .= ' AND u.agence = :agence';
            $params['agence'] = (int)$filtres['agence'];
        }
        if (($filtres['actif'] ?? '') !== '') {
            $sql .= ' AND u.actif = :actif';
            $params['actif'] = (int)$filtres['actif'];
        }
        return $sql;
    }

    /** @param array<string, ?string> $filtres */
    public function compter(int $tenantId, ?int $scope, array $filtres): int
    {
        $params = [];
        $sql = 'SELECT COUNT(*) AS n FROM utilisateurs u'
            . self::conditions($tenantId, $scope, $filtres, $params);
        $row = Database::fetch($sql, $params);
        return (int)($row['n'] ?? 0);
    }

    /** @param array<string, ?string> $filtres */
    public function paginer(int $tenantId, ?int $scope, array $filtres, int $page, int $parPage): array
    {
        $params = [];
        $sql = self::BASE
            . self::conditions($tenantId, $scope, $filtres, $params)
            . ' ORDER BY u.nom, u.prenom LIMIT :limite OFFSET :offset';
        $params['limite']  = $parPage;
        $params['offset']  = max(0, ($page - 1) * $parPage);
        return Database::fetchAll($sql, $params);
    }

    /** @return array<string, mixed>|null */
    public function trouver(int $tenantId, ?int $scope, int $id): ?array
    {
        $params = ['id' => $id, 'tenant' => $tenantId];
        $sql = self::BASE . ' WHERE u.id = :id AND u.tenant_id = :tenant AND u.supprimer = 0';
        if ($scope !== null) {
            $sql .= ' AND u.agence = :scope';
            $params['scope'] = $scope;
        }
        $sql .= ' LIMIT 1';
        return Database::fetch($sql, $params);
    }

    public function loginExiste(string $login, int $tenantId, ?int $horsId): bool
    {
        $params = ['login' => $login, 'tenant' => $tenantId];
        $sql = 'SELECT id FROM utilisateurs WHERE login = :login AND tenant_id = :tenant AND supprimer = 0';
        if ($horsId !== null) {
            $sql .= ' AND id != :hors';
            $params['hors'] = $horsId;
        }
        $sql .= ' LIMIT 1';
        return Database::fetch($sql, $params) !== null;
    }

    /** @param array<string, mixed> $u */
    public function creer(array $u, int $tenantId, int $auteurId): int
    {
        Database::execute(
            'INSERT INTO utilisateurs
                (tenant_id, login, mot_de_passe, mail, telephone, nom, prenom, couleur, droit, role,
                 est_un_enseignant, agence, tous_droit, photographie, actif,
                 date_de_naissance, civilite, ajout_le, ajout_par)
             VALUES
                (:tenant, :login, :hash, :mail, :telephone, :nom, :prenom, :couleur, :droit, :role,
                 :enseignant, :agence, :tous_droit, :photographie, :actif,
                 :naissance, :civilite, :maintenant, :auteur)',
            [
                'tenant'       => $tenantId,
                'login'        => $u['login'],
                'hash'         => $u['hash'],
                'mail'         => $u['mail'],
                'telephone'    => $u['telephone'],
                'nom'          => $u['nom'],
                'prenom'       => $u['prenom'],
                'couleur'      => $u['couleur'],
                'droit'        => $u['droit'],
                'role'         => $u['role'],
                'enseignant'   => $u['enseignant'],
                'agence'       => $u['agence'],
                'tous_droit'   => $u['tous_droit'],
                'photographie' => $u['photographie'],
                'actif'        => $u['actif'],
                'naissance'    => $u['naissance'],
                'civilite'     => $u['civilite'],
                'maintenant'   => date('Y-m-d H:i:s'),
                'auteur'       => $auteurId,
            ]
        );
        return Database::lastInsertId();
    }

    /** @param array<string, mixed> $u */
    public function modifier(int $id, int $tenantId, array $u): void
    {
        Database::execute(
            'UPDATE utilisateurs SET
                login = :login, mail = :mail, telephone = :telephone, nom = :nom, prenom = :prenom,
                couleur = :couleur, droit = :droit, role = :role, est_un_enseignant = :enseignant,
                agence = :agence, tous_droit = :tous_droit, actif = :actif,
                date_de_naissance = :naissance, civilite = :civilite
             WHERE id = :id AND tenant_id = :tenant',
            [
                'login'      => $u['login'],
                'mail'       => $u['mail'],
                'telephone'  => $u['telephone'],
                'nom'        => $u['nom'],
                'prenom'     => $u['prenom'],
                'couleur'    => $u['couleur'],
                'droit'      => $u['droit'],
                'role'       => $u['role'],
                'enseignant' => $u['enseignant'],
                'agence'     => $u['agence'],
                'tous_droit' => $u['tous_droit'],
                'actif'      => $u['actif'],
                'naissance'  => $u['naissance'],
                'civilite'   => $u['civilite'],
                'id'         => $id,
                'tenant'     => $tenantId,
            ]
        );
    }

    /** Activation / désactivation depuis la liste (directive utilisateur). */
    public function modifierActif(int $id, int $tenantId, int $actif): void
    {
        Database::execute(
            'UPDATE utilisateurs SET actif = :actif WHERE id = :id AND tenant_id = :tenant',
            ['actif' => $actif, 'id' => $id, 'tenant' => $tenantId]
        );
    }

    public function modifierMotDePasse(int $id, int $tenantId, string $hash): void
    {
        Database::execute(
            'UPDATE utilisateurs SET mot_de_passe = :hash WHERE id = :id AND tenant_id = :tenant',
            ['hash' => $hash, 'id' => $id, 'tenant' => $tenantId]
        );
    }

    public function modifierPhotographie(int $id, int $tenantId, ?string $nom): void
    {
        Database::execute(
            'UPDATE utilisateurs SET photographie = :photo WHERE id = :id AND tenant_id = :tenant',
            ['photo' => $nom, 'id' => $id, 'tenant' => $tenantId]
        );
    }

    /** Suppression logique (CDC : conservation de l'historique). */
    public function supprimerLogique(int $id, int $tenantId, int $auteurId): void
    {
        Database::execute(
            'UPDATE utilisateurs SET supprimer = 1, supprimer_par = :auteur, supprimer_date = :maintenant, actif = 0
             WHERE id = :id AND tenant_id = :tenant',
            ['auteur' => $auteurId, 'maintenant' => date('Y-m-d H:i:s'), 'id' => $id, 'tenant' => $tenantId]
        );
    }

    public function compterPourAgence(int $agenceId): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM utilisateurs WHERE agence = :agence AND supprimer = 0',
            ['agence' => $agenceId]
        );
        return (int)($row['n'] ?? 0);
    }
}