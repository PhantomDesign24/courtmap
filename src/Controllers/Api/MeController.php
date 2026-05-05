<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Db;
use App\Core\Request;
use App\Core\Response;

final class MeController extends Controller
{
    /** 현재 사용자 위치 + 최근 변경 이력 (세션 기반) */
    public function getLocation(): void
    {
        Response::json([
            'area'   => $_SESSION['user_area']   ?? '강남구 역삼동',
            'recent' => $_SESSION['recent_areas'] ?? [],
        ]);
    }

    public function setLocation(): void
    {
        $body = Request::isJson() ? (Request::json() ?? []) : Request::all();
        $area = trim((string) ($body['area'] ?? ''));
        if ($area === '') Response::json(['error' => 'area required'], 400);

        // 최근 변경 이력 (최대 4개)
        $recent = $_SESSION['recent_areas'] ?? [];
        $recent = array_values(array_filter($recent, static fn($a) => $a !== $area));
        array_unshift($recent, $area);
        $_SESSION['recent_areas'] = array_slice($recent, 0, 4);
        $_SESSION['user_area']    = $area;

        Response::json(['ok' => true, 'area' => $area]);
    }

    /** 인기 동네 — venues.area 분포 */
    public function popularAreas(): void
    {
        $rows = Db::fetchAll(
            'SELECT area, COUNT(*) AS c FROM venues WHERE status = "active"
             GROUP BY area ORDER BY c DESC LIMIT 8'
        );
        Response::json(['areas' => array_map(static fn($r) => $r['area'], $rows)]);
    }

    /** 인기 검색어 — search_logs 최근 7일 */
    public function popularSearches(): void
    {
        $rows = Db::fetchAll(
            'SELECT query, COUNT(*) AS c FROM search_logs
             WHERE searched_at >= NOW() - INTERVAL 7 DAY AND query <> ""
             GROUP BY query ORDER BY c DESC LIMIT 5'
        );
        Response::json(['queries' => array_map(static fn($r) => $r['query'], $rows)]);
    }

    /** 미읽음 알림 카운트 */
    public function unreadCount(): void
    {
        $user = Auth::user();
        if (!$user) Response::json(['count' => 0]);
        $row = Db::fetch('SELECT COUNT(*) AS c FROM notifications WHERE user_id = ? AND is_read = 0', [(int) $user['id']]);
        Response::json(['count' => (int) ($row['c'] ?? 0)]);
    }
}
