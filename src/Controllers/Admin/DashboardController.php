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
                (SELECT COUNT(*) FROM users  WHERE role = "user"        AND status = "active") AS active_users,
                (SELECT COUNT(*) FROM users  WHERE role = "operator"    AND status = "active") AS active_operators,
                (SELECT COUNT(*) FROM users  WHERE created_at >= NOW() - INTERVAL 7 DAY)        AS new_users_week,
                (SELECT COUNT(*) FROM reservations WHERE status IN ("confirmed","done")
                  AND DATE(created_at) = CURDATE())                                              AS today_reservations,
                (SELECT COALESCE(SUM(total_price), 0) FROM reservations WHERE status IN ("confirmed","done")
                  AND DATE(paid_at) = CURDATE())                                                 AS today_revenue,
                (SELECT COUNT(*) FROM noshow_logs WHERE created_at >= NOW() - INTERVAL 7 DAY)    AS noshow_week'
        );
        $this->view('admin/dashboard', [
            'title' => '어드민 — 코트맵',
            'user'  => $user,
            'stats' => $stats,
        ], layout: 'admin');
    }
}
