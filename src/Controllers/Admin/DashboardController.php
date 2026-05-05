<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Db;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth('admin');
        $stats = Db::fetch(
            'SELECT
                (SELECT COUNT(*) FROM venues WHERE status = "pending")  AS pending_venues,
                (SELECT COUNT(*) FROM venues WHERE status = "active")   AS active_venues,
                (SELECT COUNT(*) FROM users  WHERE role = "user"     AND status = "active") AS active_users,
                (SELECT COUNT(*) FROM users  WHERE role = "operator" AND status = "active") AS active_operators,
                (SELECT COUNT(*) FROM users  WHERE created_at >= NOW() - INTERVAL 7 DAY) AS new_users_week,
                (SELECT COUNT(*) FROM reservations WHERE status IN ("confirmed","done")
                  AND DATE(created_at) = CURDATE()) AS today_reservations,
                (SELECT COALESCE(SUM(total_price), 0) FROM reservations WHERE status IN ("confirmed","done")
                  AND DATE(paid_at) = CURDATE()) AS today_revenue,
                (SELECT COUNT(*) FROM noshow_logs WHERE created_at >= NOW() - INTERVAL 7 DAY) AS noshow_week'
        );

        // 매출 추이 (전체 플랫폼, 30일)
        $daily = Db::fetchAll(
            'SELECT DATE(paid_at) AS d, SUM(total_price) AS rev, COUNT(*) AS cnt
             FROM reservations
             WHERE status IN ("confirmed","done") AND paid_at IS NOT NULL
               AND paid_at >= NOW() - INTERVAL 30 DAY
             GROUP BY DATE(paid_at) ORDER BY d'
        );

        // 인기 구장 TOP 10
        $topVenues = Db::fetchAll(
            'SELECT v.id, v.name, v.area,
                    COUNT(r.id) AS cnt,
                    COALESCE(SUM(r.total_price), 0) AS rev
             FROM venues v
             LEFT JOIN reservations r ON r.venue_id = v.id AND r.status IN ("confirmed","done")
             WHERE v.status = "active"
             GROUP BY v.id ORDER BY cnt DESC, rev DESC LIMIT 10'
        );

        // 지역 분포 (시·구 단위)
        $byArea = Db::fetchAll(
            'SELECT
                SUBSTRING_INDEX(area, " ", 2) AS region,
                COUNT(*) AS c
             FROM venues WHERE status = "active"
             GROUP BY region ORDER BY c DESC LIMIT 12'
        );

        // 시스템 헬스
        $health = self::collectHealth();

        $this->view('admin/dashboard', [
            'title'     => '어드민 — 코트맵',
            'user'      => $user,
            'stats'     => $stats,
            'daily'     => $daily,
            'topVenues' => $topVenues,
            'byArea'    => $byArea,
            'health'    => $health,
        ], layout: 'admin');
    }

    /** @return array{cron:array, webhook:array, db_ms:float} */
    private static function collectHealth(): array
    {
        // cron 마지막 실행 — 각 lock 파일 mtime
        $cronLocks = [
            '입금만료'      => '/tmp/courtmap-expire.lock',
            '자동 노쇼'     => '/tmp/courtmap-noshow.lock',
            '공휴일 sync'   => '/tmp/courtmap-holidays.lock',
            '단골 알림'     => '/tmp/courtmap-favalert.lock',
            '자동 프라이싱' => '/tmp/courtmap-pricing.lock',
            '이용완료 마킹' => '/tmp/courtmap-markdone.lock',
        ];
        $cron = [];
        foreach ($cronLocks as $name => $path) {
            $cron[$name] = is_file($path) ? filemtime($path) : null;
        }

        // Webhook 실패율
        $w = Db::fetch(
            'SELECT
                (SELECT COUNT(*) FROM webhooks)                                AS total,
                (SELECT COUNT(*) FROM webhooks WHERE status = "failed")        AS failed,
                (SELECT COUNT(*) FROM webhooks WHERE failure_count > 0)        AS recent_fail'
        );
        $webhook = [
            'total'       => (int) ($w['total'] ?? 0),
            'failed'      => (int) ($w['failed'] ?? 0),
            'recent_fail' => (int) ($w['recent_fail'] ?? 0),
        ];

        // DB 응답 시간
        $t0 = microtime(true);
        Db::fetch('SELECT 1');
        $dbMs = round((microtime(true) - $t0) * 1000, 2);

        return ['cron' => $cron, 'webhook' => $webhook, 'db_ms' => $dbMs];
    }
}
