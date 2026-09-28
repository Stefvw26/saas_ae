<?php
// fichier : app/Middleware/GuestMiddleware.php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Réserve les routes aux visiteurs non connectés (ex. formulaire de connexion).
 */
final class GuestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): void
    {
        if (Session::has('user_id')) {
            Response::redirect(url('/'));
        }
        $next($request);
    }
}