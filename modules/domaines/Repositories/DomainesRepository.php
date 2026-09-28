<?php
// fichier : modules/domaines/Repositories/DomainesRepository.php
declare(strict_types=1);

namespace Modules\Domaines\Repositories;

use App\Core\Database;

/**
 * CRUD domaines — isolation tenant, suppression logique, couleur (CDC §36).
 */
final class DomainesRepository
{
    private const COLONNES = 'id, tenant_id, nom, descriptif, initiale, couleur, nb_echeances_max, actif, ajout_le';

    /** @return array<int, array<string, mixed>> */
    public function toutesPourTenant(int $tenantId, bool $uniquementActifs = true): array
    {
        $sql = 'SELECT ' . self::COLONNES . ' FROM domaines WHERE tenant_id = :tenant AND supprimer = 0';
        $params = ['tenant' => $tenantId];
        if ($uniquementActifs) {
            $sql .= ' AND actif = 1';
        }
        $sql .= ' ORDER BY nom';
        return Database::fetchAll($sql, $params);
    }

    /** @return array<string, mixed>|null */
    public function trouver(int $id, int $tenantId): ?array
    {
        return Database::fetch(
            'SELECT ' . self::COLONNES . ' FROM domaines
             WHERE id = :id AND tenant_id = :tenant AND supprimer = 0 LIMIT 1',
            ['id' => $id, 'tenant' => $tenantId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function paginer(int $tenantId, int $page, int $parPage): array
    {
        return Database::fetchAll(
            'SELECT ' . self::COLONNES . ' FROM domaines
             WHERE tenant_id = :tenant AND supprimer = 0
             ORDER BY nom LIMIT :limite OFFSET :offset',
            ['tenant' => $tenantId, 'limite' => $parPage, 'offset' => max(0, ($page - 1) * $parPage)]
        );
    }

    public function compter(int $tenantId): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM domaines WHERE tenant_id = :tenant AND supprimer = 0',
            ['tenant' => $tenantId]
        );
        return (int)($row['n'] ?? 0);
    }

    /** @param array<string, ?string> $d */
    public function creer(array $d, int $tenantId, int $auteurId): int
    {
        Database::execute(
            'INSERT INTO domaines (tenant_id, nom, descriptif, initiale, couleur, nb_echeances_max, actif, ajout_le, ajout_par)
             VALUES (:tenant, :nom, :descriptif, :initiale, :couleur, :nb_echeances_max, :actif, :maintenant, :auteur)',
            [
                'tenant'           => $tenantId,
                'nom'              => $d['nom'],
                'descriptif'       => $d['descriptif'],
                'initiale'         => $d['initiale'],
                'couleur'          => $d['couleur'],
                'nb_echeances_max' => $d['nb_echeances_max'],
                'actif'            => $d['actif'],
                'maintenant'       => date('Y-m-d H:i:s'),
                'auteur'           => $auteurId,
            ]
        );
        return Database::lastInsertId();
    }

    /** @param array<string, ?string> $d */
    public function modifier(int $id, int $tenantId, array $d): void
    {
        Database::execute(
            'UPDATE domaines SET nom = :nom, descriptif = :descriptif, initiale = :initiale, couleur = :couleur,
                    nb_echeances_max = :nb_echeances_max, actif = :actif
             WHERE id = :id AND tenant_id = :tenant',
            [
                'nom'              => $d['nom'],
                'descriptif'       => $d['descriptif'],
                'initiale'         => $d['initiale'],
                'couleur'          => $d['couleur'],
                'nb_echeances_max' => $d['nb_echeances_max'],
                'actif'            => $d['actif'],
                'id'               => $id,
                'tenant'           => $tenantId,
            ]
        );
    }

    public function supprimerLogique(int $id, int $tenantId, int $auteurId): void
    {
        Database::execute(
            'UPDATE domaines SET supprimer = 1, supprimer_par = :auteur, supprimer_date = :maintenant, actif = 0
             WHERE id = :id AND tenant_id = :tenant',
            ['auteur' => $auteurId, 'maintenant' => date('Y-m-d H:i:s'), 'id' => $id, 'tenant' => $tenantId]
        );
    }

    /** Utilisateurs actifs rattachés (garde anti-suppression). */
    public function compterUtilisateurs(int $domaineId): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS n FROM utilisateurs WHERE domaine_id = :d AND supprimer = 0',
            ['d' => $domaineId]
        );
        return (int)($row['n'] ?? 0);
    }
}