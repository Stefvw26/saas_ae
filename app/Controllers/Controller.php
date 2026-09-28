<?php
// fichier : app/Controllers/Controller.php — CRM Auto-École, Jalon 2
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\Gate;
use App\Services\LogService;

abstract class Controller
{
    /** @param array<string, mixed> $data */
    protected function view(string $template, array $data = [], ?string $layout = 'app'): void
    {
        View::render($template, $data, $layout);
    }

    protected function redirect(string $path): void
    {
        Response::redirect(url($path));
    }

    protected function flashError(string $message): void
    {
        Session::flash('error', $message);
    }

    protected function flashSuccess(string $message): void
    {
        Session::flash('success', $message);
    }

    /**
     * Contrôle de permission CÔTÉ SERVEUR — à appeler en tête de chaque action protégée.
     */
    protected function authorize(string $permission): void
    {
        if (!Gate::can($permission)) {
            LogService::enregistrer('acces.refuse', 'permission', null, 'refuse', ['permission' => $permission]);
            abort(403, 'Accès refusé', 'Vous ne disposez pas des droits nécessaires pour cette action.', '/');
        }
    }
}