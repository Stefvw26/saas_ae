<?php
// fichier : modules/dossiers/Repositories/EvenementsRepository.php — Jalon 7
declare(strict_types=1);

namespace Modules\Dossiers\Repositories;

use App\Core\Database;

/**
 * Événements liés aux dossiers (planning élève). RÈGLE ABSOLUE :
 * aucune suppression physique.
 */
final class EvenementsRepository
{
    /** @return array<int, array<string, mixed>> */
    public function pourDossier(int $dossierId, int $tenantId): array
    {
        return Database::fetchAll(
            'SELECT ev.*, u.nom AS moniteur_nom, u.prenom AS moniteur_prenom, v.immatriculation
             FROM evenements ev
             LEFT JOIN utilisateurs u ON u.id = ev.moniteur_id
             LEFT JOIN vehicules v ON v.id = ev.vehicule_id
             WHERE ev.dossier_id = :dossier AND ev.tenant_id = :tenant AND ev.supprimer = 0
             ORDER BY ev.date_heure DESC',
            ['dossier' => $dossierId, 'tenant' => $tenantId]
        );
    }

    /** @return array<string, mixed>|null */
    public function trouver(int $id, int $tenantId): ?array
    {
        return Database::fetch(
            'SELECT ev.* FROM evenements ev WHERE ev.id = :id AND ev.tenant_id = :tenant AND ev.supprimer = 0 LIMIT 1',
            ['id' => $id, 'tenant' => $tenantId]
        );
    }

    /** @param array<string, ?string|int> $e */
    public function creer(array $e, int $tenantId, int $auteurId): int
    {
        Database::execute(
            'INSERT INTO evenements
                (tenant_id, dossier_id, type, titre, date_heure, duree_minutes, moniteur_id, vehicule_id, notes, ajout_le, ajout_par)
             VALUES
                (:tenant, :dossier, :type, :titre, :date_heure, :duree, :moniteur, :vehicule, :notes, :maintenant, :auteur)',
            [
                'tenant'    => $tenantId,
                'dossier'   => (int)$e['dossier_id'],
                'type'      => $e['type'] ?? null,
                'titre'     => (string)$e['titre'],
                'date_heure'=> (string)$e['date_heure'],
                'duree'     => $e['duree_minutes'] ?? null,
                'moniteur'  => $e['moniteur_id'] ?? null,
                'vehicule'  => $e['vehicule_id'] ?? null,
                'notes'     => $e['notes'] ?? null,
                'maintenant' => date('Y-m-d H:i:s'),
                'auteur'    => $auteurId,
            ]
        );
        return Database::lastInsertId();
    }

    public function supprimerLogique(int $id, int $tenantId, int $auteurId): void
    {
        Database::execute(
            'UPDATE evenements SET supprimer = 1, supprimer_par = :auteur, supprimer_date = :quand
             WHERE id = :id AND tenant_id = :tenant',
            ['auteur' => $auteurId, 'quand' => date('Y-m-d H:i:s'), 'id' => $id, 'tenant' => $tenantId]
        );
    }
}