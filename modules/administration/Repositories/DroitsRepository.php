<?php
// fichier : modules/administration/Repositories/DroitsRepository.php — v0.16
declare(strict_types=1);

namespace Modules\Administration\Repositories;

use App\Core\Database;
use Throwable;

final class DroitsRepository
{
    /** @return array<int, array<string, mixed>> */
    public function roles(): array
    {
        return Database::fetchAll('SELECT id, code, nom, descriptif FROM roles ORDER BY id');
    }

    /** @return array<int, array<string, mixed>> */
    public function permissions(): array
    {
        return Database::fetchAll('SELECT id, module, code, libelle FROM permissions ORDER BY module, id');
    }

    /** @return array<int, array<int, string>> roleId => [codes de permission] */
    public function matrice(): array
    {
        $matrice = [];
        $rows = Database::fetchAll(
            'SELECT rp.role_id, p.code FROM role_permissions rp
             INNER JOIN permissions p ON p.id = rp.permission_id'
        );
        foreach ($rows as $row) {
            $matrice[(int)$row['role_id']][] = (string)$row['code'];
        }
        return $matrice;
    }

    /**
     * Remplace intégralement la matrice (transaction).
     *
     * @param array<int, array{0:int, 1:int}> $paires [roleId, permissionId]
     */
    public function remplacerMatrice(array $paires): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->exec('DELETE FROM role_permissions');
            $stmt = $pdo->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (:r, :p)');
            foreach ($paires as [$roleId, $permissionId]) {
                $stmt->execute(['r' => $roleId, 'p' => $permissionId]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /* ---------- Rôles personnalisés (directive utilisateur v0.16) ---------- */

    public function roleExiste(string $code, string $nom): bool
    {
        return Database::fetch(
            'SELECT id FROM roles WHERE code = :code OR nom = :nom LIMIT 1',
            ['code' => $code, 'nom' => $nom]
        ) !== null;
    }

    public function creerRole(string $code, string $nom, ?string $descriptif): int
    {
        Database::execute(
            'INSERT INTO roles (code, nom, descriptif) VALUES (:code, :nom, :descriptif)',
            ['code' => $code, 'nom' => $nom, 'descriptif' => $descriptif]
        );
        return Database::lastInsertId();
    }
}