<?php
declare(strict_types=1);

namespace App\Controllers\Operator;

use App\Core\Controller;
use App\Core\Db;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth('operator');
        $uid  = (int) $user['id'];

        $stats = Db::fetch(
            'SELECT
                (SELECT COUNT(*) FROM reservations r JOIN venues v ON v.id = r.venue_id
                 WHERE v.owner_id = ? AND r.status = "pending") AS pending,
                (SELECT COUNT(*) FROM reservations r JOIN venues v ON v.id = r.venue_id
                 WHERE v.owner_id = ? AND r.status = "confirmed" AND r.reservation_date = CURDATE()) AS today_confirmed,
                (SELECT COALESCE(SUM(r.total_price), 0) FROM reservations r JOIN venues v ON v.id = r.venue_id
                 WHERE v.owner_id = ? AND r.status IN ("confirmed","done")
                   AND DATE(r.paid_at) = CURDATE()) AS today_revenue,
                (SELECT COUNT(*) FROM venues WHERE owner_id = ? AND status = "active") AS venue_count',
            [$uid, $uid, $uid, $uid]
        );

        // 다음 7일 예약 타임라인
        $upcoming = Db::fetchAll(
            'SELECT r.code, r.reservation_date, r.start_hour, r.duration_hours, r.status, r.total_price,
                    c.name AS court_name, v.name AS venue_name, u.name AS user_name, u.trust_score
             FROM reservations r
             JOIN venues v ON v.id = r.venue_id
             JOIN courts c ON c.id = r.court_id
             JOIN users  u ON u.id = r.user_id
             WHERE v.owner_id = ?
               AND r.reservation_date BETWEEN CURDATE() AND CURDATE() + INTERVAL 7 DAY
               AND r.status IN ("pending","confirmed")
             ORDER BY r.reservation_date, r.start_hour, c.sort_order',
            [$uid]
        );

        // 시간대 × 코트 히트맵 (지난 30일 confirmed/done)
        $heat = Db::fetchAll(
            'SELECT r.start_hour AS h, c.id AS cid, c.name AS court, COUNT(*) AS cnt
             FROM reservations r
             JOIN venues v ON v.id = r.venue_id
             JOIN courts c ON c.id = r.court_id
             WHERE v.owner_id = ? AND r.status IN ("confirmed","done")
               AND r.reservation_date >= CURDATE() - INTERVAL 30 DAY
             GROUP BY r.start_hour, c.id ORDER BY c.sort_order, r.start_hour',
            [$uid]
        );

        // 일별 매출 (지난 30일)
        $daily = Db::fetchAll(
            'SELECT DATE(r.paid_at) AS d, SUM(r.total_price) AS rev, COUNT(*) AS cnt
             FROM reservations r JOIN venues v ON v.id = r.venue_id
             WHERE v.owner_id = ? AND r.status IN ("confirmed","done") AND r.paid_at IS NOT NULL
               AND r.paid_at >= NOW() - INTERVAL 30 DAY
             GROUP BY DATE(r.paid_at) ORDER BY d',
            [$uid]
        );

        $this->view('operator/dashboard', [
            'title'    => '대시보드 — 운영자',
            'user'     => $user,
            'stats'    => $stats,
            'upcoming' => $upcoming,
            'heat'     => $heat,
            'daily'    => $daily,
        ], layout: 'operator');
    }
}
