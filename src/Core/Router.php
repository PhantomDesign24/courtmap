<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{method:string, path:string, handler:callable|array}> */
    private array $routes = [];

    public function get(string $path, callable|array $handler): void  { $this->add('GET',  $path, $handler); }
    public function post(string $path, callable|array $handler): void { $this->add('POST', $path, $handler); }
    public function put(string $path, callable|array $handler): void  { $this->add('PUT',  $path, $handler); }
    public function delete(string $path, callable|array $handler): void { $this->add('DELETE', $path, $handler); }

    public function add(string $method, string $path, callable|array $handler): void
    {
        $this->routes[] = ['method' => $method, 'path' => $path, 'handler' => $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        if ($method === 'HEAD') $method = 'GET';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            $params = $this->match($route['path'], $path);
            if ($params !== null) {
                $this->call($route['handler'], $params);
                return;
            }
        }
        Response::notFound();
    }

    private function match(string $pattern, string $path): ?array
    {
        // /venues/{id} → /^\/venues\/(?P<id>[^\/]+)$/
        $regex = preg_replace('#\{([^}]+)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#u';
        if (!preg_match($regex, $path, $m)) return null;
        return array_filter($m, static fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY);
    }

    private function call(callable|array $handler, array $params): void
    {
        if (is_array($handler) && is_string($handler[0]) && class_exists($handler[0])) {
            $instance = new $handler[0]();
            $method = $handler[1];
            $instance->$method(...array_values($params));
            return;
        }
        if (is_callable($handler)) {
            ($handler)(...array_values($params));
            return;
        }
        throw new \RuntimeException('Invalid route handler');
    }
}
