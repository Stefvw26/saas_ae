<?php
// fichier : app/Repositories/DocumentsRepository.php — Jalon 7
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Documents génériques liés à un objet (dossier, élève, …) — pattern
 * polymorphe objet_type/objet_id, identique à CommentairesRepository.
 */
final class DocumentsRepository
{
    /** @return array<int, array<string, mixed>> */
    public function pourObjet(string $objetType, int $objetId, int $tenantId, ?string $type = null): array
    {
        $sql = 'SELECT d.*, u.nom AS auteur_nom, u.prenom AS auteur_prenom
                FROM documents d
                LEFT JOIN utilisateurs u ON u.id = d.ajout_par
                WHERE d.objet_type = :type AND d.objet_id = :id AND d.tenant_id = :tenant AND d.supprimer = 0';
        $params = ['type' => $objetType, 'id' => $objetId, 'tenant' => $tenantId];
        if ($type !== null) {
            $sql .= ' AND d.type = :sous_type';
            $params['sous_type'] = $type;
        }
        $sql .= ' ORDER BY d.ajout_le DESC';
        return Database::fetchAll($sql, $params);
    }

    /** @return array<string, mixed>|null */
    public function trouver(int $id, int $tenantId): ?array
    {
        return Database::fetch(
            'SELECT * FROM documents WHERE id = :id AND tenant_id = :tenant AND supprimer = 0 LIMIT 1',
            ['id' => $id, 'tenant' => $tenantId]
        );
    }

    /** @param array<string, mixed> $d */
    public function ajouter(array $d, int $tenantId, int $auteurId): int
    {
        Database::execute(
            'INSERT INTO documents (tenant_id, objet_type, objet_id, type, libelle, nom_original, chemin, mime, taille, ajout_le, ajout_par)
             VALUES (:tenant, :objet_type, :objet_id, :type, :libelle, :nom_original, :chemin, :mime, :taille, :maintenant, :auteur)',
            [
                'tenant'       => $tenantId,
                'objet_type'   => $d['objet_type'],
                'objet_id'     => $d['objet_id'],
                'type'         => $d['type'],
                'libelle'      => $d['libelle'],
                'nom_original' => $d['nom_original'],
                'chemin'       => $d['chemin'],
                'mime'         => $d['mime'],
                'taille'       => $d['taille'],
                'maintenant'   => date('Y-m-d H:i:s'),
                'auteur'       => $auteurId,
            ]
        );
        return Database::lastInsertId();
    }

    public function supprimerLogique(int $id, int $tenantId, int $auteurId): void
    {
        Database::execute(
            'UPDATE documents SET supprimer = 1, supprimer_par = :auteur, supprimer_date = :quand
             WHERE id = :id AND tenant_id = :tenant',
            ['auteur' => $auteurId, 'quand' => date('Y-m-d H:i:s'), 'id' => $id, 'tenant' => $tenantId]
        );
    }
}
