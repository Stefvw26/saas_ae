<?php
// fichier : app/Core/Request.php
declare(strict_types=1);

namespace App\Core;

/**
 * Requête HTTP entrante (encapsulation des superglobales).
 * La résolution du chemin est indépendante du sous-répertoire
 * d'installation (compatible WAMP : /crm-auto-ecole/public/…).
 */
final class Request
{
    private string $method;
    private string $path;
    private string $ip;

    /** @var array<string, mixed> utilisateur authentifié (rempli par AuthMiddleware) */
    private array $user = [];

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->path   = self::resolvePath();
        $this->ip     = (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public function method(): string { return $this->method; }
    public function path(): string   { return $this->path; }
    public function ip(): string     { return $this->ip; }
    public function isPost(): bool   { return $this->method === 'POST'; }

    /** Chemin courant, hors instance (liens actifs de la sidebar). */
    public static function currentPath(): string
    {
        return self::resolvePath();
    }

    /** @return mixed */
    public function get(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /** @return mixed (aucun trim : le mot de passe doit rester intact) */
    public function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    public function setUser(array $user): void { $this->user = $user; }
    public function user(): array { return $this->user; }

    public function userId(): ?int
    {
        return isset($this->user['id']) ? (int)$this->user['id'] : null;
    }

    private static function resolvePath(): string
    {
        $uri = (string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        $script = (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $base = rtrim(str_replace('\\', '/', dirname($script)), '/');

        if ($base !== '' && $base !== '/' && strpos($uri, $base . '/') === 0) {
            $uri = substr($uri, strlen($base));
        } elseif ($base !== '' && $uri === $base) {
            $uri = '/';
        }

        if (strpos($uri, '/index.php') === 0) {
            $uri = substr($uri, strlen('/index.php'));
            if ($uri === false || $uri === '') {
                $uri = '/';
            }
        }

        $uri = '/' . trim($uri, '/');
        return ($uri === '//' || $uri === '') ? '/' : $uri;
    }
}