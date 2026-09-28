<?php
// fichier : app/Core/Session.php
declare(strict_types=1);

namespace App\Core;

/**
 * Session durcie : cookie HttpOnly + SameSite, régénération périodique
 * de l'identifiant (anti-fixation), messages flash.
 */
final class Session
{
    public static function start(array $config = []): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name((string)($config['name'] ?? 'crmae_session'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => (bool)($config['secure'] ?? false),
            'httponly' => (bool)($config['httponly'] ?? true),
            'samesite' => (string)($config['samesite'] ?? 'Lax'),
        ]);
        session_start();

        /* Régénération périodique de l'ID de session */
        $created = isset($_SESSION['_created']) ? (int)$_SESSION['_created'] : 0;
        if ($created === 0) {
            $_SESSION['_created'] = time();
        } elseif (time() - $created > 1800) {
            session_regenerate_id(true);
            $_SESSION['_created'] = time();
        }
    }

    /** @return mixed */
    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /** @param mixed $value */
    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** @param mixed $value */
    public static function flash(string $key, $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    /** @return mixed */
    public static function getFlash(string $key, $default = null)
    {
        if (isset($_SESSION['_flash'][$key])) {
            $value = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $value;
        }
        return $default;
    }

    /**
     * Déconnexion : efface les données et régénère l'identifiant.
     * La session reste active pour permettre l'affichage d'un message flash.
     */
    public static function destroy(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}