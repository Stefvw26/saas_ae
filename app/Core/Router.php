<?php
// fichier : app/Core/Router.php — CRM Auto-École, Jalon 2 (corrigé)
declare(strict_types=1);

namespace App\Core;

use App\Middleware\CsrfMiddleware;

/**
 * Routeur : GET/POST, paramètres {nom}, middleware par route,
 * vérification CSRF globale sur les méthodes mutatives.
 *
 * Handlers acceptés :
 *  - [Classe::class, 'methode']  (nom complet : App\... ou Modules\...)
 *  - ['Classe@methode']           (nom court -> préfixé App\Controllers\)
 *  - callable
 */
final class Router
{
    /** @var array<int, array{method:string, path:string, pattern:string, handler:mixed, middleware:array<int,string>}> */
    private array $routes = [];

    /** @param mixed $handler */
    public function get(string $path, $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    /** @param mixed $handler */
    public function post(string $path, $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    /** @param mixed $handler */
    private function add(string $method, string $path, $handler, array $middleware): void
    {
        $path = '/' . trim($path, '/');
        $this->routes[] = [
            'method'     => $method,
            'path'       => $path,
            'pattern'    => self::compile($path),
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    private static function compile(string $path): string
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        return '#^' . $regex . '$#';
    }

    public function dispatch(Request $request): void
    {
        /* CSRF : appliqué à toute requête mutative, avant tout le reste. */
        (new CsrfMiddleware())->handle($request, function (Request $req): void {
            $this->matchAndRun($req);
        });
    }

    private function matchAndRun(Request $request): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method()) {
                continue;
            }
            if (preg_match($route['pattern'], $request->path(), $matches) !== 1) {
                continue;
            }
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $this->runPipeline($route['middleware'], $request, function (Request $req) use ($route, $params): void {
                $this->invoke($route['handler'], $req, $params);
            });
            return;
        }

        /* Chemin connu mais mauvaise méthode -> 405 */
        foreach ($this->routes as $route) {
            if (preg_match($route['pattern'], $request->path()) === 1) {
                $this->abort(405, 'Méthode non autorisée');
                return;
            }
        }
        $this->abort(404, 'Page introuvable');
    }

    /** @param array<int, class-string> $middleware */
    private function runPipeline(array $middleware, Request $request, callable $core): void
    {
        $next = $core;
        foreach (array_reverse($middleware) as $class) {
            $instance = new $class();
            $inner = $next;
            $next = static function (Request $req) use ($instance, $inner): void {
                $instance->handle($req, $inner);
            };
        }
        $next($request);
    }

    /**
     * Instancie et appelle le handler de route.
     *
     * CORRECTION : un nom de classe contenant un antislash (nom complet, ex.
     * « Modules\Administration\Controllers\... ») est utilisé TEL QUEL ;
     * seul un nom COURT (ex. « AuthController ») est préfixé « App\Controllers\ ».
     *
     * @param mixed $handler
     * @param array<string, string> $params
     */
    private function invoke($handler, Request $request, array $params): void
    {
        if (is_string($handler) && strpos($handler, '@') !== false) {
            $handler = explode('@', $handler, 2);
        }
        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            if (is_string($class)) {
                $class = ltrim($class, '\\');
                if (strpos($class, '\\') === false) {
                    $class = 'App\\Controllers\\' . $class;
                }
            }
            $controller = new $class();
            $controller->{$method}($request, ...array_values($params));
            return;
        }
        if (is_callable($handler)) {
            $handler($request, ...array_values($params));
            return;
        }
        throw new \RuntimeException('Handler de route invalide.');
    }

    private function abort(int $code, string $titre): void
    {
        $message = $code === 404
            ? 'La page demandée n\'existe pas ou n\'est plus disponible.'
            : 'La méthode HTTP utilisée n\'est pas autorisée pour cette page.';
        abort($code, $titre, $message, '/');
    }
}