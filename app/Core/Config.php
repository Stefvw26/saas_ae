<?php
// fichier : app/Core/Config.php
declare(strict_types=1);

namespace App\Core;

/**
 * Configuration : charge /config/*.php, accès par notation pointée.
 * Exemple : Config::get('app.session.lifetime')
 */
final class Config
{
    /** @var array<string, mixed> */
    private static array $items = [];

    public static function load(string $directory): void
    {
        foreach (glob($directory . '/*.php') ?: [] as $file) {
            $key = basename($file, '.php');
            self::$items[$key] = require $file;
        }
    }

    /** @return mixed */
    public static function get(string $key, $default = null)
    {
        $value = self::$items;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}