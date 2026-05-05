<?php
declare(strict_types=1);

// PSR-4-ish autoloader: App\Foo\Bar  →  src/Foo/Bar.php
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) return;
    $relative = substr($class, 4);
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) require $path;
});

// .env loader (Composer-less)
$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        if (strlen($v) >= 2 && $v[0] === '"' && substr($v, -1) === '"') {
            $v = substr($v, 1, -1);
        }
        if (!isset($_ENV[$k])) {
            $_ENV[$k]    = $v;
            $_SERVER[$k] = $v;
            putenv("$k=$v");
        }
    }
}

$root = dirname(__DIR__);
$app  = require $root . '/config/app.php';
$db   = require $root . '/config/database.php';

date_default_timezone_set($app['timezone']);

if ($app['debug']) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

\App\Core\Db::init($db);

session_name($app['session']['name']);
session_set_cookie_params([
    'lifetime' => $app['session']['lifetime'],
    'path'     => '/',
    'domain'   => '',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

return [$app, $db];
