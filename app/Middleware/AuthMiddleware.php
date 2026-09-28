<?php
// fichier : app/Middleware/AuthMiddleware.php — CRM Auto-École, Jalon 2
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\UserRepository;
use App\Services\Gate;

/**
 * Exige un utilisateur authentifié et actif ; recharge l'utilisateur depuis
 * la base à chaque requête et initialise le contexte Gate (droits + agence).
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): void
    {
        if (!Session::has('user_id')) {
            $this->deny('Merci de vous connecter pour accéder à l\'application.');
            return;
        }

        $user = (new UserRepository())->findActiveById((int)Session::get('user_id'));
        if ($user === null) {
            Logger::warning('Utilisateur de session invalide ou désactivé', [
                'user_id' => (int)Session::get('user_id'),
            ]);
            Session::destroy();
            $this->deny('Votre compte n\'est plus accessible. Merci de vous reconnecter.');
            return;
        }

        $request->setUser($user);
        Gate::setUser($user);
        Gate::initAgenceActive();

        $next($request);
    }

    private function deny(string $message): void
    {
        Session::flash('error', $message);
        Response::redirect(url('/connexion'));
    }
}