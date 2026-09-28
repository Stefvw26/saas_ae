<?php
// fichier : app/Services/SystemService.php — CRM Auto-École, Jalon 1
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use Throwable;

/**
 * État réel du système : connexion base, compteurs, modules détectés.
 * Toutes les valeurs affichées proviennent de mesures réelles — jamais de valeurs fictives.
 */
final class SystemService
{
    public function databaseConnected(): bool
    {
        try {
            Database::fetch('SELECT 1 AS ok');
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function databaseName(): string
    {
        return (string)Config::get('database.database', '');
    }

    /** Comptes actifs et non supprimés (conforme au libellé affiché sur le tableau de bord). */
    public function userCount(): int
    {
        try {
            $row = Database::fetch('SELECT COUNT(*) AS n FROM utilisateurs WHERE supprimer = 0 AND actif = 1');
            return (int)($row['n'] ?? 0);
        } catch (Throwable) {
            return 0;
        }
    }

    public function migrationCount(): int
    {
        try {
            $row = Database::fetch('SELECT COUNT(*) AS n FROM migrations');
            return (int)($row['n'] ?? 0);
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * Modules métier réellement présents dans /modules.
     * Un module expose un manifest.php : ['nom' => …, 'version' => …, 'description' => …]
     *
     * @return array<int, array{code:string, nom:string, version:string, description:string}>
     */
    public function detectedModules(): array
    {
        $modules = [];
        $dir = BASE_PATH . '/modules';
        if (!is_dir($dir)) {
            return $modules;
        }

        foreach (glob($dir . '/*', GLOB_ONLYDIR) ?: [] as $path) {
            $code     = basename($path);
            $manifest = $path . '/manifest.php';
            $info     = is_file($manifest) ? include $manifest : [];
            if (!is_array($info)) {
                $info = [];
            }

            $modules[] = [
                'code'        => $code,
                'nom'         => (string)($info['nom'] ?? ucfirst(str_replace('-', ' ', $code))),
                'version'     => (string)($info['version'] ?? '—'),
                'description' => (string)($info['description'] ?? ''),
            ];
        }

        usort($modules, static fn (array $a, array $b): int => strcasecmp($a['nom'], $b['nom']));
        return $modules;
    }
}