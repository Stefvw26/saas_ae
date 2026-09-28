<?php
// fichier : modules/administration/Repositories/LogsRepository.php
declare(strict_types=1);

namespace Modules\Administration\Repositories;

use App\Core\Database;

final class LogsRepository
{
    /**
     * @param array<string, ?string> $filtres
     */
    private static function conditions(int $tenantId, ?int $scope, array $filtres, array &$params): string
    {
        $sql = ' WHERE tenant_id = :tenant';
        $params['tenant'] = $tenantId;

        if ($scope !== null) {
            $sql .= ' AND agence_id = :scope';
            $params['scope'] = $scope;
        }
        if (!empty($filtres['action'])) {
            $sql .= ' AND action = :action';
            $params['action'] = $filtres['action'];
        }
        if (!empty($filtres['q'])) {
            $sql .= ' AND (login LIKE :q OR objet LIKE :q)';
            $params['q'] = '%' . $filtres['q'] . '%';
        }
        if (!empty($filtres['de'])) {
            $sql .= ' AND cree_le >= :de';
            $params['de'] = $filtres['de'] . ' 00:00:00';
        }
        if (!empty($filtres['a'])) {
            $sql .= ' AND cree_le <= :a';
            $params['a'] = $filtres['a'] . ' 23:59:59';
        }
        return $sql;
    }

    /** @param array<string, ?string> $filtres */
    public function compter(int $tenantId, ?int $scope, array $filtres): int
    {
        $params = [];
        $row = Database::fetch('SELECT COUNT(*) AS n FROM logs' . self::conditions($tenantId, $scope, $filtres, $params), $params);
        return (int)($row['n'] ?? 0);
    }

    /** @param array<string, ?string> $filtres */
    public function paginer(int $tenantId, ?int $scope, array $filtres, int $page, int $parPage): array
    {
        $params = ['limite' => $parPage, 'offset' => max(0, ($page - 1) * $parPage)];
        $sql = 'SELECT * FROM logs'
            . self::conditions($tenantId, $scope, $filtres, $params)
            . ' ORDER BY cree_le DESC, id DESC LIMIT :limite OFFSET :offset';
        return Database::fetchAll($sql, $params);
    }

    /** @return array<int, string> */
    public function actionsDistinctes(int $tenantId): array
    {
        $rows = Database::fetchAll(
            'SELECT DISTINCT action FROM logs WHERE tenant_id = :tenant ORDER BY action',
            ['tenant' => $tenantId]
        );
        return array_map(static fn (array $r): string => (string)$r['action'], $rows);
    }
}