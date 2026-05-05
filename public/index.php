<?php
declare(strict_types=1);

[$app] = require dirname(__DIR__) . '/src/bootstrap.php';

$router = new \App\Core\Router();
require dirname(__DIR__) . '/src/routes.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = $_SERVER['REQUEST_URI']    ?? '/';
$path   = parse_url($uri, PHP_URL_PATH) ?: '/';

// CSRF 가드 — Kakao callback (외부 OAuth provider) 만 예외. /login 도 토큰 강제로 brute-force script 차단.
\App\Core\Csrf::guard($method, $path, [
    '/auth/kakao/callback',
]);

$router->dispatch($method, $uri);
