<?php
// fichier : app/Middleware/MiddlewareInterface.php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;

interface MiddlewareInterface
{
    /**
     * Traite la requête. Pour interrompre la chaîne, le middleware
     * envoie sa propre réponse (redirection, erreur…) sans appeler $next.
     */
    public function handle(Request $request, callable $next): void;
}