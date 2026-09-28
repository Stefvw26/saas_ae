<?php
// fichier : app/Core/App.php — CRM Auto-École, Jalon 2
declare(strict_types=1);

namespace App\Core;

/**
 * Kernel : session, expiration par inactivité, routes globales + routes
 * des modules métier détectés dans /modules, dispatch.
 */
final class App
{
    public function run(): void
    {
        $appConfig = Config::get('app', []);
        Session::start($appConfig['session'] ?? []);

        $this->enforceInactivityTimeout($appConfig['session'] ?? []);
        Session::set('_last_activity', time());

        $request = new Request();

        $router = new Router();
        require BASE_PATH . '/routes/web.php';

        /* Chargement automatique des routes des modules (ordre alphabétique) */
        $fichiersRoutes = glob(MODULES_PATH . '/*/routes.php') ?: [];
        sort($fichiersRoutes);
        foreach ($fichiersRoutes as $fichierRoutes) {
            require $fichierRoutes;
        }

        $router->dispatch($request);
    }

    /** Déconnecte l'utilisateur après la période d'inactivité configurée. */
    private function enforceInactivityTimeout(array $sessionConfig): void
    {
        if (!Session::has('user_id')) {
            return;
        }
        $lifetime = (int)($sessionConfig['lifetime'] ?? 7200);
        $last = (int)Session::get('_last_activity', 0);
        if ($last > 0 && (time() - $last) > $lifetime) {
            Logger::info('Session expirée (inactivité)');
            Session::destroy();
            Session::flash('error', 'Votre session a expirée. Merci de vous reconnecter.');
            Response::redirect(url('/connexion'));
        }
    }
}