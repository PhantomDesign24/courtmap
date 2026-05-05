<?php
declare(strict_types=1);

[$app] = require dirname(__DIR__) . '/src/bootstrap.php';

$router = new \App\Core\Router();
require dirname(__DIR__) . '/src/routes.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = $_SERVER['REQUEST_URI']    ?? '/';
$path   = parse_url($uri, PHP_URL_PATH) ?: '/';

// CSRF 가드 — 인증 진입점은 제외 (세션 형성 전이라 토큰 발급 불가)
\App\Core\Csrf::guard($method, $path, [
    '/login', '/register',
    '/auth/kakao', '/auth/kakao/callback', '/auth/kakao/complete',
]);

$router->dispatch($method, $uri);
