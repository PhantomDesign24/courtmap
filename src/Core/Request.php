<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function uri(): string
    {
        return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    }

    public static function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        if (self::isJson()) {
            return self::json()[$key] ?? $default;
        }
        return $_POST[$key] ?? $default;
    }

    public static function all(): array
    {
        return self::isJson() ? (self::json() ?? []) : $_POST;
    }

    public static function isJson(): bool
    {
        return str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');
    }

    public static function json(): ?array
    {
        static $cached = null;
        if ($cached !== null) return $cached;
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);
        return $cached = (is_array($data) ? $data : null);
    }

    public static function isApi(): bool
    {
        return str_starts_with(self::uri(), '/api/');
    }

    public static function ip(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    }
}
