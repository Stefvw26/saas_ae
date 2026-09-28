<?php
// fichier : app/Repositories/CommentairesRepository.php — v0.35 (+ pourObjets groupé)
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Services\Gate;

/**
 * Composant transverse : commentaires conversationnels (CDC §33).
 * Table unique polymorphe — isolation tenant systématique.
 */
final class CommentairesRepository
{
    /**
     * Commentaires d'un objet, avec informations d'auteur (avatar CDC §33)
     * et libellé du partenaire éventuel (§18).
     *
     * @return array<int, array<string, mixed>>
     */
    public function pourObjet(string $type, int $objetId, int $tenantId): array
    {
        return Database::fetchAll(
            'SELECT c.id, c.km, c.partenaire_id, c.commentaire, c.cree_le,
                    c.utilisateur_id, u.login, u.prenom, u.nom, u.couleur, u.photographie,
                    pt.nom AS partenaire_nom
             FROM commentaires c
             LEFT JOIN utilisateurs u ON u.id = c.utilisateur_id
             LEFT JOIN partenaires pt ON pt.id = c.partenaire_id
             WHERE c.tenant_id = :tenant AND c.objet_type = :type AND c.objet_id = :objet
             ORDER BY c.cree_le ASC, c.id ASC',
            ['tenant' => $tenantId, 'type' => $type, 'objet' => $objetId]
        );
    }

    /**
     * v0.35 : commentaires de PLUSIEURS objets d'un même type, groupés par
     * objet_id (une seule requête pour la page de cards prospects).
     *
     * @param array<int, int> $ids
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function pourObjets(string $type, array $ids, int $tenantId): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }

        $params = ['tenant' => $tenantId, 'type' => $type];
        $marqueurs = [];
        foreach ($ids as $i => $id) {
            $marqueurs[] = ':oid' . $i;
            $params['oid' . $i] = $id;
        }

        $rows = Database::fetchAll(
            'SELECT c.id, c.objet_id, c.km, c.partenaire_id, c.commentaire, c.cree_le,
                    c.utilisateur_id, u.login, u.prenom, u.nom, u.couleur, u.photographie
             FROM commentaires c
             LEFT JOIN utilisateurs u ON u.id = c.utilisateur_id
             WHERE c.tenant_id = :tenant AND c.objet_type = :type
               AND c.objet_id IN (' . implode(', ', $marqueurs) . ')
             ORDER BY c.cree_le ASC, c.id ASC',
            $params
        );

        $groupe = [];
        foreach ($rows as $row) {
            $groupe[(int)$row['objet_id']][] = $row;
        }
        return $groupe;
    }

    /**
     * @param array<string, ?string|int> $d objet_type, objet_id, commentaire, km?, partenaire_id?
     */
    public function ajouter(array $d, int $utilisateurId): int
    {
        Database::execute(
            'INSERT INTO commentaires
                (tenant_id, agence_id, objet_type, objet_id, km, partenaire_id, commentaire, utilisateur_id, cree_le)
             VALUES
                (:tenant, :agence, :type, :objet, :km, :partenaire, :commentaire, :auteur, :quand)',
            [
                'tenant'     => (int)$d['tenant_id'],
                'agence'     => Gate::scopeAgence(),
                'type'       => (string)$d['objet_type'],
                'objet'      => (int)$d['objet_id'],
                'km'         => $d['km'] ?? null,
                'partenaire' => $d['partenaire_id'] ?? null,
                'commentaire' => (string)$d['commentaire'],
                'auteur'     => $utilisateurId,
                'quand'      => date('Y-m-d H:i:s'),
            ]
        );
        return Database::lastInsertId();
    }
}