<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method:string, regex:string, handler:array, auth:bool, perm:bool}> */
    private array $routes = [];

    public function add(string $method, string $pattern, array $handler, bool $auth = true, bool $perm = false): void
    {
        $this->routes[] = [
            'method'  => strtoupper($method),
            'regex'   => self::toRegex($pattern),
            'handler' => $handler,
            'auth'    => $auth || $perm,
            'perm'    => $perm,
        ];
    }

    /**
     * Convertit un chemin de style CodeIgniter en expression régulière.
     * Placeholders gérés : (:num), (:segment), (:any) - identiques à ceux de la table rules.
     */
    public static function toRegex(string $pattern): string
    {
        $p = trim($pattern, '/');
        $p = str_replace(['(:num)', '(:segment)', '(:any)'], ['@@N@@', '@@S@@', '@@A@@'], $p);
        $p = preg_quote($p, '#');
        $p = str_replace(['@@N@@', '@@S@@', '@@A@@'], ['([0-9]+)', '([^/]+)', '(.+)'], $p);
        return '#^' . $p . '$#';
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method || !preg_match($route['regex'], $path, $m)) {
                continue;
            }
            array_shift($m);

            if ($route['auth'] && !Auth::check()) {
                if ($method === 'GET' && $path !== '') {
                    $_SESSION['intended'] = '/' . $path;
                }
                Session::flash('info', 'Veuillez vous connecter pour continuer.');
                redirect('/');
            }
            if ($route['perm']) {
                $user = Auth::user();
                if ($user === null || !Permission::allowed((int) $user['group_id'], $method, $path)) {
                    http_response_code(403);
                    View::render('errors/403', ['title' => 'Accès refusé']);
                    return;
                }
            }

            [$class, $action] = $route['handler'];
            (new $class())->$action(...$m);
            return;
        }

        if (!Auth::check()) {
            redirect('/');
        }
        http_response_code(404);
        View::render('errors/404', ['title' => 'Page introuvable']);
    }
}
