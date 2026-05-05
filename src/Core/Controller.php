<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function view(string $name, array $data = [], ?string $layout = 'app'): void
    {
        View::render($name, $data, $layout);
    }

    protected function json(mixed $data, int $status = 200): never
    {
        Response::json($data, $status);
    }

    protected function redirect(string $url): never
    {
        Response::redirect($url);
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return Request::input($key, $default);
    }

    protected function requireAuth(string $role = 'user'): array
    {
        return Auth::requireRole($role);
    }
}
