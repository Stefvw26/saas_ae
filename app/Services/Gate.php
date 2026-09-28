<?php
// fichier : app/Services/Gate.php — CRM Auto-École, v0.31
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use App\Repositories\UserRepository;
use Modules\Administration\Repositories\AgencesRepository;

/**
 * Contrôle d'accès central — TOUJOURS côté serveur.
 * Priorité : tous_droit > droits personnalisés (colonne « droit », JSON) > matrice rôles/permissions.
 * Contexte multi-agence (agence active, périmètre de données).
 *
 * v0.22 : l'admin n'est jamais restreint PAR DÉFAUT (toutes les agences).
 * v0.31 (directive) : le sélecteur d'agence revient pour l'admin — il peut
 * filtrer VOLONTAIREMENT sur une agence ; « Toutes les agences » reste
 * l'état par défaut à la connexion.
 */
final class Gate
{
    private static ?array $user = null;
    /** @var array<string, array<int, string>>|null */
    private static ?array $matrice = null;
    /** @var array<string, bool>|null */
    private static ?array $droitsPerso = null;
    /** @var array<int, array<string, mixed>>|null */
    private static ?array $agencesAccessibles = null;

    public static function setUser(array $user): void
    {
        self::$user = $user;
        self::$matrice = null;
        self::$droitsPerso = null;
        self::$agencesAccessibles = null;
    }

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        if (self::$user === null && Session::has('user_id')) {
            self::$user = (new UserRepository())->findActiveById((int)Session::get('user_id'));
        }
        return self::$user;
    }

    public static function tousDroit(): bool
    {
        $u = self::user();
        return $u !== null && (int)($u['tous_droit'] ?? 0) === 1;
    }

    public static function can(string $permission): bool
    {
        $u = self::user();
        if ($u === null) {
            return false;
        }
        if ((int)($u['tous_droit'] ?? 0) === 1) {
            return true;
        }
        $perso = self::droitsPerso();
        if (array_key_exists($permission, $perso)) {
            return (bool)$perso[$permission];
        }
        return in_array($permission, self::matrice()[(string)$u['role']] ?? [], true);
    }

    /** @return array<string, bool> */
    private static function droitsPerso(): array
    {
        if (self::$droitsPerso === null) {
            $u = self::user();
            $json = (string)($u['droit'] ?? '');
            $decode = $json !== '' ? json_decode($json, true) : null;
            self::$droitsPerso = is_array($decode) ? $decode : [];
        }
        return self::$droitsPerso;
    }

    /** @return array<string, array<int, string>> code de rôle => [codes de permission] */
    private static function matrice(): array
    {
        if (self::$matrice === null) {
            $matrice = [];
            $rows = Database::fetchAll(
                'SELECT r.code AS role, p.code AS permission
                 FROM role_permissions rp
                 INNER JOIN roles r ON r.id = rp.role_id
                 INNER JOIN permissions p ON p.id = rp.permission_id'
            );
            foreach ($rows as $row) {
                $matrice[(string)$row['role']][] = (string)$row['permission'];
            }
            self::$matrice = $matrice;
        }
        return self::$matrice;
    }

    /* ---------- Multi-agence ---------- */

    /** @return array<int, array<string, mixed>> */
    public static function agencesAccessibles(): array
    {
        if (self::$agencesAccessibles === null) {
            $u = self::user();
            $repo = new AgencesRepository();
            if ($u === null) {
                self::$agencesAccessibles = [];
            } elseif (self::tousDroit() || self::can('agences.changer')) {
                self::$agencesAccessibles = $repo->toutesPourTenant((int)$u['tenant_id']);
            } else {
                $propre = $u['agence'] !== null ? $repo->trouver((int)$u['agence'], (int)$u['tenant_id']) : null;
                self::$agencesAccessibles = $propre !== null ? [$propre] : [];
            }
        }
        return self::$agencesAccessibles;
    }

    /**
     * Initialise ou valide l'agence active en session.
     * 0 = « toutes les agences ».
     */
    public static function initAgenceActive(): void
    {
        $u = self::user();
        if ($u === null) {
            return;
        }

        $ids = array_map(static fn (array $a): int => (int)$a['id'], self::agencesAccessibles());
        $active = Session::has('agence_active_id') ? (int)Session::get('agence_active_id') : null;

        if (self::tousDroit()) {
            /* v0.31 : défaut = toutes (aucune restriction) ; l'admin peut
               filtrer volontairement via le sélecteur (0 ou agence valide). */
            if ($active === null || ($active !== 0 && !in_array($active, $ids, true))) {
                Session::set('agence_active_id', 0);
            }
            return;
        }

        if ($active === null || $active === 0 || !in_array($active, $ids, true)) {
            $defaut = ($u['agence'] !== null && in_array((int)$u['agence'], $ids, true))
                ? (int)$u['agence']
                : ($ids[0] ?? 0);
            Session::set('agence_active_id', $defaut);
        }
    }

    /** @return array<string, mixed>|null agence active, ou null si « toutes » */
    public static function agenceActive(): ?array
    {
        if (self::user() === null) {
            return null;
        }
        $active = Session::has('agence_active_id') ? (int)Session::get('agence_active_id') : 0;
        if ($active === 0) {
            return null;
        }
        foreach (self::agencesAccessibles() as $agence) {
            if ((int)$agence['id'] === $active) {
                return $agence;
            }
        }
        return null;
    }

    /**
     * Périmètre de données appliqué aux requêtes : identifiant d'agence, ou null = tout le tenant.
     * - v0.31 : tous_droit => « toutes » par défaut (null) ; filtre volontaire
     *   via le sélecteur (agence active) ;
     * - agences.changer => agence active (0 = toutes) ;
     * - sinon => agence de rattachement de l'utilisateur.
     */
    public static function scopeAgence(): ?int
    {
        $u = self::user();
        if ($u === null) {
            return null;
        }
        if (self::tousDroit() || self::can('agences.changer')) {
            $active = Session::has('agence_active_id') ? (int)Session::get('agence_active_id') : 0;
            return $active === 0 ? null : $active;
        }
        return $u['agence'] !== null ? (int)$u['agence'] : null;
    }
}