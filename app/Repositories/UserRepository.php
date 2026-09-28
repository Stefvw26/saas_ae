<?php
// fichier : app/Repositories/UserRepository.php — v0.16
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Accès aux données utilisateurs (Jalon 1 : lecture + mot de passe).
 * v0.16 : sans agence_domaine (colne supprimée) ; joint roles pour le libellé.
 */
final class UserRepository
{
    private const BASE_SELECT = <<<SQL
SELECT u.id, u.tenant_id, u.login, u.mot_de_passe, u.mail, u.telephone, u.nom, u.prenom, u.couleur,
       u.droit, u.role, u.est_un_enseignant, u.agence, u.tous_droit, u.photographie,
       u.actif, u.date_de_naissance, u.civilite,
       a.agence_nom, a.agence_couleur, a.agence_initiale,
       r.nom AS role_nom,
       t.nom AS tenant_nom
FROM utilisateurs u
INNER JOIN tenants t ON t.id = u.tenant_id AND t.actif = 1 AND t.supprimer = 0
LEFT JOIN agences a ON a.id = u.agence
LEFT JOIN roles r ON r.code = u.role
SQL;

    /** Utilisateur actif (compte et tenant actifs) recherché par login. */
    public function findActiveByLogin(string $login): ?array
    {
        return Database::fetch(
            self::BASE_SELECT . ' WHERE u.login = :login AND u.actif = 1 AND u.supprimer = 0 LIMIT 1',
            ['login' => $login]
        );
    }

    /** Utilisateur actif recherché par identifiant (contrôle de session). */
    public function findActiveById(int $id): ?array
    {
        return Database::fetch(
            self::BASE_SELECT . ' WHERE u.id = :id AND u.actif = 1 AND u.supprimer = 0 LIMIT 1',
            ['id' => $id]
        );
    }

    public function fetchPassword(int $id): ?array
    {
        return Database::fetch(
            'SELECT id, mot_de_passe FROM utilisateurs WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    public function updatePassword(int $id, string $hash): void
    {
        Database::execute(
            'UPDATE utilisateurs SET mot_de_passe = :hash WHERE id = :id',
            ['hash' => $hash, 'id' => $id]
        );
    }
}