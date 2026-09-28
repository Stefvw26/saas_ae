<?php
// fichier : app/Core/Csrf.php
declare(strict_types=1);

namespace App\Core;

/**
 * Protection CSRF : jeton de session, vérification à temps constant.
 */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . self::token() . '">';
    }

    public static function verify(?string $token): bool
    {
        $known = (string)($_SESSION['_csrf'] ?? '');
        return $token !== null && $token !== '' && $known !== '' && hash_equals($known, $token);
    }
        /** Jeton CSRF courant (pour les meta tags et l'AJAX). */
    public static function currentToken(): string
    {
        return self::token();
    }
}