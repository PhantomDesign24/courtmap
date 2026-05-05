<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function user(): ?array
    {
        $id = self::id();
        if ($id === null) return null;
        return Db::fetch(
            'SELECT id, email, name, phone, role, trust_score, restricted_until, status
             FROM users WHERE id = ? AND status = ?',
            [$id, 'active']
        );
    }

    public static function login(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        Db::query('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$userId]);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /**
     * 현재 로그인 사용자가 주어진 역할 이상인지 확인. 아니면 redirect/forbidden.
     * @return array 인증된 user row
     */
    public static function requireRole(string $role = 'user'): array
    {
        if (!self::check()) {
            Response::redirect('/login?next=' . urlencode(Request::uri()));
        }
        $user = self::user();
        if (!$user) {
            self::logout();
            Response::redirect('/login');
        }
        $rank = ['user' => 1, 'operator' => 2, 'admin' => 3];
        if (($rank[$user['role']] ?? 0) < ($rank[$role] ?? 0)) {
            Response::forbidden();
        }
        return $user;
    }

    /**
     * 신뢰점수 기반 예약 제한 체크.
     * @return ?string 제한 사유 (제한 없으면 null)
     */
    public static function reservationRestriction(array $user): ?string
    {
        if (!empty($user['restricted_until']) && strtotime($user['restricted_until']) > time()) {
            return '신뢰점수가 낮아 ' . substr($user['restricted_until'], 0, 10) . ' 까지 예약이 제한됩니다.';
        }
        $score = (int) ($user['trust_score'] ?? 100);
        if ($score < 40)  return '신뢰점수가 너무 낮아 예약이 제한됩니다.';
        return null;
    }
}
