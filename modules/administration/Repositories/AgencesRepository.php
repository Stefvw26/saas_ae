<?php
// fichier : modules/administration/Repositories/AgencesRepository.php — v0.42 (FIX)
declare(strict_types=1);

namespace Modules\Administration\Repositories;

use App\Core\Database;

/**
 * CRUD agences (CDC §8 + adresse complète + email).
 * v0.42 : accès DÉFENSIF aux clés optionnelles (?? null) — un appelant
 * ancien ne transmettant pas code_postal/ville/pays/agence_mail ne peut
 * plus provoquer d'erreur.
 */
final class AgencesRepository
{
    private const COLONNES = 'id, tenant_id, agence_nom, agence_adresse, code_postal, ville, pays,
            agence_telephone, agence_mail, agence_agreement, agence_agreement_date,
            agence_agreement_exploitant, agence_assurance, agence_couleur, latitude, longitude,
            agence_initiale, actif, ajout_le';

    /** @return array<int, array<string, mixed>> */
    public function toutesPourTenant(int $tenantId): array
    {
        return Database::fetchAll(
            'SELECT ' . self::COLONNES . ' FROM agences
             WHERE tenant_id = :tenant AND supprimer = 0
             ORDER BY agence_nom',
            ['tenant' => $tenantId]
        );
    }

    /** @return array<string, mixed>|null */
    public function trouver(int $id, int $tenantId): ?array
    {
        return Database::fetch(
            'SELECT ' . self::COLONNES . ' FROM agences
             WHERE id = :id AND tenant_id = :tenant AND supprimer = 0 LIMIT 1',
            ['id' => $id, 'tenant' => $tenantId]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function paginer(int $tenantId, ?int $scope, int $page, int $parPage): array
    {
        $params = ['tenant' => $tenantId, 'limite' => $parPage, 'offset' => max(0, ($page - 1) * $parPage)];
        $sql = 'SELECT ' . self::COLONNES . ' FROM agences WHERE tenant_id = :tenant AND supprimer = 0';
        if ($scope !== null) {
            $sql .= ' AND id = :scope';
            $params['scope'] = $scope;
        }
        $sql .= ' ORDER BY agence_nom LIMIT :limite OFFSET :offset';
        return Database::fetchAll($sql, $params);
    }

    public function compter(int $tenantId, ?int $scope): int
    {
        $params = ['tenant' => $tenantId];
        $sql = 'SELECT COUNT(*) AS n FROM agences WHERE tenant_id = :tenant AND supprimer = 0';
        if ($scope !== null) {
            $sql .= ' AND id = :scope';
            $params['scope'] = $scope;
        }
        $row = Database::fetch($sql, $params);
        return (int)($row['n'] ?? 0);
    }

    /** @param array<string, ?string> $a */
    public function creer(array $a, int $tenantId, int $auteurId): int
    {
        Database::execute(
            'INSERT INTO agences
                (tenant_id, agence_nom, agence_adresse, code_postal, ville, pays,
                 agence_telephone, agence_mail,
                 agence_agreement, agence_agreement_date, agence_agreement_exploitant, agence_assurance,
                 agence_couleur, latitude, longitude, agence_initiale, actif, ajout_le, ajout_par)
             VALUES
                (:tenant, :nom, :adresse, :cp, :ville, :pays,
                 :telephone, :mail,
                 :agreement, :agreement_date, :exploitant, :assurance,
                 :couleur, :latitude, :longitude, :initiale, :actif, :maintenant, :auteur)',
            [
                'tenant'        => $tenantId,
                'nom'           => $a['agence_nom'] ?? null,
                'adresse'       => $a['agence_adresse'] ?? null,
                'cp'            => $a['code_postal'] ?? null,
                'ville'         => $a['ville'] ?? null,
                'pays'          => $a['pays'] ?? null,
                'telephone'     => $a['agence_telephone'] ?? null,
                'mail'          => $a['agence_mail'] ?? null,
                'agreement'     => $a['agence_agreement'] ?? null,
                'agreement_date'=> $a['agence_agreement_date'] ?? null,
                'exploitant'    => $a['agence_agreement_exploitant'] ?? null,
                'assurance'     => $a['agence_assurance'] ?? null,
                'couleur'       => $a['agence_couleur'] ?? '#2563eb',
                'latitude'      => $a['latitude'] ?? null,
                'longitude'     => $a['longitude'] ?? null,
                'initiale'      => $a['agence_initiale'] ?? null,
                'actif'         => (int)($a['actif'] ?? 1),
                'maintenant'    => date('Y-m-d H:i:s'),
                'auteur'        => $auteurId,
            ]
        );
        return Database::lastInsertId();
    }

    /** @param array<string, ?string> $a */
    public function modifier(int $id, int $tenantId, array $a): void
    {
        Database::execute(
            'UPDATE agences SET
                agence_nom = :nom, agence_adresse = :adresse, code_postal = :cp, ville = :ville, pays = :pays,
                agence_telephone = :telephone, agence_mail = :mail,
                agence_agreement = :agreement, agence_agreement_date = :agreement_date,
                agence_agreement_exploitant = :exploitant, agence_assurance = :assurance,
                agence_couleur = :couleur, latitude = :latitude, longitude = :longitude,
                agence_initiale = :initiale, actif = :actif
             WHERE id = :id AND tenant_id = :tenant',
            [
                'nom'           => $a['agence_nom'] ?? null,
                'adresse'       => $a['agence_adresse'] ?? null,
                'cp'            => $a['code_postal'] ?? null,
                'ville'         => $a['ville'] ?? null,
                'pays'          => $a['pays'] ?? null,
                'telephone'     => $a['agence_telephone'] ?? null,
                'mail'          => $a['agence_mail'] ?? null,
                'agreement'     => $a['agence_agreement'] ?? null,
                'agreement_date'=> $a['agence_agreement_date'] ?? null,
                'exploitant'    => $a['agence_agreement_exploitant'] ?? null,
                'assurance'     => $a['agence_assurance'] ?? null,
                'couleur'       => $a['agence_couleur'] ?? '#2563eb',
                'latitude'      => $a['latitude'] ?? null,
                'longitude'     => $a['longitude'] ?? null,
                'initiale'      => $a['agence_initiale'] ?? null,
                'actif'         => (int)($a['actif'] ?? 1),
                'id'            => $id,
                'tenant'        => $tenantId,
            ]
        );
    }

    public function supprimerLogique(int $id, int $tenantId, int $auteurId): void
    {
        Database::execute(
            'UPDATE agences SET supprimer = 1, supprimer_par = :auteur, supprimer_date = :maintenant, actif = 0
             WHERE id = :id AND tenant_id = :tenant',
            ['auteur' => $auteurId, 'maintenant' => date('Y-m-d H:i:s'), 'id' => $id, 'tenant' => $tenantId]
        );
    }
}