<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function html(string $content, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo $content;
        exit;
    }

    public static function redirect(string $url, int $status = 302): never
    {
        header('Location: ' . $url, true, $status);
        exit;
    }

    public static function notFound(string $message = '페이지를 찾을 수 없습니다'): never
    {
        if (Request::isApi()) {
            self::json(['error' => $message], 404);
        }
        self::html('<!doctype html><meta charset="utf-8"><title>404</title><h1 style="font-family:sans-serif;text-align:center;margin-top:80px">404 — ' . htmlspecialchars($message) . '</h1>', 404);
    }

    public static function forbidden(string $message = '접근 권한이 없습니다'): never
    {
        if (Request::isApi()) {
            self::json(['error' => $message], 403);
        }
        self::html('<!doctype html><meta charset="utf-8"><title>403</title><h1 style="font-family:sans-serif;text-align:center;margin-top:80px">403 — ' . htmlspecialchars($message) . '</h1>', 403);
    }
}
