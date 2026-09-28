<?php
// fichier : app/Middleware/CsrfMiddleware.php — CRM Auto-École, Jalon 2
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Request;

/**
 * Vérifie le jeton CSRF sur toute requête mutative (POST, PUT, PATCH, DELETE).
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    private const MUTATING = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, callable $next): void
    {
        if (in_array($request->method(), self::MUTATING, true)) {
            $token = $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
            if (!Csrf::verify(is_string($token) ? $token : null)) {
                Logger::warning('Jeton CSRF invalide ou absent', [
                    'uri' => $request->path(),
                    'ip'  => $request->ip(),
                ]);
                abort(419, 'Session expirée', 'Votre session a expiré ou le formulaire est invalide. Merci de réessayer.', '/connexion');
            }
        }
        $next($request);
    }
}