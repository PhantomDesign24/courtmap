<?php
declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csrf'];
    }

    public static function validate(string $tok): bool
    {
        $expected = $_SESSION['csrf'] ?? '';
        return $tok !== '' && $expected !== '' && hash_equals($expected, $tok);
    }

    /**
     * POST/PUT/DELETE 요청에 대해 CSRF 검증. 실패 시 403.
     * exempt: 검사 제외 경로 (예: 외부 webhook 인입은 별도 인증)
     */
    public static function guard(string $method, string $path, array $exempt = []): void
    {
        if (in_array(strtoupper($method), ['GET', 'HEAD'], true)) return;
        foreach ($exempt as $e) {
            if (str_starts_with($path, $e)) return;
        }
        $tok = (string) ($_POST['_csrf']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? (Request::isJson() ? (Request::json()['_csrf'] ?? '') : ''));
        if (!self::validate($tok)) {
            Response::forbidden('CSRF 토큰이 유효하지 않습니다.');
        }
    }
}
