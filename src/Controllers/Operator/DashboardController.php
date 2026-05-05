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
            [$user['id'], $user['id'], $user['id'], $user['id']]
        );

        $this->view('operator/dashboard', [
            'title' => '대시보드 — 운영자',
            'user'  => $user,
            'stats' => $stats,
        ], layout: 'operator');
    }
}
