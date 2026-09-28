<?php
// fichier : app/Repositories/LoginAttemptRepository.php — CRM Auto-École, Jalon 1
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Journal des tentatives de connexion (anti brute-force).
 * Les horodatages sont générés côté PHP (fuseau applicatif) afin que
 * la fenêtre de verrou soit calculée sur la même horloge que l'insertion.
 */
final class LoginAttemptRepository
{
    public function record(string $login, string $ip, bool $success): void
    {
        Database::execute(
            'INSERT INTO login_attempts (identifiant, ip, succes, tentative_le)
             VALUES (:login, :ip, :succes, :quand)',
            [
                'login'   => $login,
                'ip'      => $ip,
                'succes'  => $success ? 1 : 0,
                'quand'   => date('Y-m-d H:i:s'),
            ]
        );
    }

    /** Échecs récents pour cet identifiant OU cette adresse IP. */
    public function countRecentFailures(string $login, string $ip, int $windowSeconds): int
    {
        $since = date('Y-m-d H:i:s', time() - $windowSeconds);
        $row = Database::fetch(
            'SELECT COUNT(*) AS total FROM login_attempts
             WHERE succes = 0 AND tentative_le > :since AND (identifiant = :login OR ip = :ip)',
            ['since' => $since, 'login' => $login, 'ip' => $ip]
        );
        return (int)($row['total'] ?? 0);
    }

    /** Purge des tentatives anciennes (entretien). */
    public function purgeOlderThanDays(int $days): int
    {
        $limit = date('Y-m-d H:i:s', time() - $days * 86400);
        return Database::execute('DELETE FROM login_attempts WHERE tentative_le < :limit', ['limit' => $limit]);
    }
}