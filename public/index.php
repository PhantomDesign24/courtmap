<?php
declare(strict_types=1);

[$app] = require dirname(__DIR__) . '/src/bootstrap.php';

$router = new \App\Core\Router();
require dirname(__DIR__) . '/src/routes.php';

$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI']    ?? '/'
);
