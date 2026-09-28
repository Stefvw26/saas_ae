<?php
// fichier : app/Repositories/NotificationsRepository.php — CRM Auto-École, J4
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Notifications personnelles — chaque requête est filtrée par
 * utilisateur_id (le destinataire) ET tenant_id : un utilisateur ne voit
 jamais que SES notifications (contrôle serveur systématique).
 */
final class NotificationsRepository
{
    /** @return array<int, array<string, mixed>> */
    public function pourUtilisateur(int $utilisateurId, int $tenantId, int $limit = 100): array
    {
        return Database::fetchAll(
            'SELECT id, titre, message, url, lu, cree_le
             FROM notifications
             WHERE utilisateur_id = :u AND tenant_id = :t
             ORDER BY cree_le DESC, id DESC
             LIMIT ' . max(1, $limit),
            ['u' => $utilisateurId, 't' => $tenantId]
        );
    }

    public function compterNonLus(int $utilisateurId, int $tenantId): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM notifications
             WHERE utilisateur_id = :u AND tenant_id = :t AND lu = 0',
            ['u' => $utilisateurId, 't' => $tenantId]
        );
        return (int)($row['n'] ?? 0);
    }

    /** @return array<string, mixed>|null */
    public function trouverPourUtilisateur(int $id, int $utilisateurId, int $tenantId): ?array
    {
        return Database::fetch(
            'SELECT id, titre, message, url, lu, cree_le
             FROM notifications
             WHERE id = :id AND utilisateur_id = :u AND tenant_id = :t
             LIMIT 1',
            ['id' => $id, 'u' => $utilisateurId, 't' => $tenantId]
        );
    }

    public function marquerLu(int $id, int $utilisateurId, int $tenantId): void
    {
        Database::execute(
            'UPDATE notifications SET lu = 1 WHERE id = :id AND utilisateur_id = :u AND tenant_id = :t',
            ['id' => $id, 'u' => $utilisateurId, 't' => $tenantId]
        );
    }

    public function marquerToutLu(int $utilisateurId, int $tenantId): void
    {
        Database::execute(
            'UPDATE notifications SET lu = 1 WHERE utilisateur_id = :u AND tenant_id = :t',
            ['u' => $utilisateurId, 't' => $tenantId]
        );
    }
}