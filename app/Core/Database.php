<?php
// fichier : app/Core/Database.php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Accès PDO partagé — toutes les requêtes passent par des requêtes préparées.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $c = Config::get('database', []);
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $c['host'] ?? '127.0.0.1',
                $c['port'] ?? 3306,
                $c['database'] ?? '',
                $c['charset'] ?? 'utf8mb4'
            );
            try {
                self::$pdo = new PDO(
                    $dsn,
                    (string)($c['username'] ?? ''),
                    (string)($c['password'] ?? ''),
                    $c['options'] ?? []
                );
            } catch (PDOException $e) {
                throw new RuntimeException(
                    'Connexion à la base de données impossible : ' . $e->getMessage(),
                    0,
                    $e
                );
            }
        }
        return self::$pdo;
    }

    /** @return array<string, mixed>|null */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $row;
    }

    /** @return array<int, array<string, mixed>> */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    public static function lastInsertId(): int
    {
        return (int)self::pdo()->lastInsertId();
    }
}