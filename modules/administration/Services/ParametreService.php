<?php
// fichier : modules/administration/Services/ParametreService.php
declare(strict_types=1);

namespace Modules\Administration\Services;

use App\Core\Database;
use App\Services\Gate;

/**
 * Paramètres centralisés (CDC §25), par tenant, avec cache par requête.
 * Système extensible : toute nouvelle clé est lisible via param('cle', defaut).
 */
final class ParametreService
{
    /** @var array<string, array{valeur: string, type: string}>|null */
    private static ?array $cache = null;

    /** @return array<string, mixed> */
    public static function tous(): array
    {
        self::charger();
        $sortis = [];
        foreach (self::$cache as $cle => $info) {
            $sortis[$cle] = self::caster($info['valeur'], $info['type']);
        }
        return $sortis;
    }

    /** @return mixed */
    public static function get(string $cle, $defaut = null)
    {
        self::charger();
        if (!array_key_exists($cle, self::$cache)) {
            return $defaut;
        }
        return self::caster(self::$cache[$cle]['valeur'], self::$cache[$cle]['type']);
    }

    /**
     * @param array<string, array{valeur: string, type: string}> $entrees
     */
    public static function enregistrer(array $entrees, int $utilisateurId): void
    {
        $tenantId = self::tenantId();
        if ($tenantId === null) {
            return;
        }
        $maintenant = date('Y-m-d H:i:s');
        foreach ($entrees as $cle => $entree) {
            Database::execute(
                'INSERT INTO parametres (tenant_id, cle, valeur, type, modifie_le, modifie_par)
                 VALUES (:tenant, :cle, :valeur, :type, :modifie, :par)
                 ON DUPLICATE KEY UPDATE valeur = :valeur2, modifie_le = :modifie2, modifie_par = :par2',
                [
                    'tenant'   => $tenantId,
                    'cle'      => $cle,
                    'valeur'   => $entree['valeur'],
                    'type'     => $entree['type'],
                    'modifie'  => $maintenant,
                    'par'      => $utilisateurId,
                    'valeur2'  => $entree['valeur'],
                    'modifie2' => $maintenant,
                    'par2'     => $utilisateurId,
                ]
            );
        }
        self::$cache = null;
    }

    private static function charger(): void
    {
        if (self::$cache !== null) {
            return;
        }
        $tenantId = self::tenantId();
        if ($tenantId === null) {
            self::$cache = [];
            return;
        }
        $rows = Database::fetchAll(
            'SELECT cle, valeur, type FROM parametres WHERE tenant_id = :tenant',
            ['tenant' => $tenantId]
        );
        $cache = [];
        foreach ($rows as $row) {
            $cache[(string)$row['cle']] = [
                'valeur' => (string)($row['valeur'] ?? ''),
                'type'   => (string)$row['type'],
            ];
        }
        self::$cache = $cache;
    }

    private static function tenantId(): ?int
    {
        $user = Gate::user();
        return $user !== null ? (int)$user['tenant_id'] : null;
    }

    /** @return mixed */
    private static function caster(string $valeur, string $type)
    {
        if ($type === 'entier') {
            return (int)$valeur;
        }
        if ($type === 'booleen') {
            return $valeur === '1';
        }
        return $valeur;
    }
}