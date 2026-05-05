<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Db;

final class FinanceController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth('admin');

        $from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 day'));
        $to   = $_GET['to']   ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $from)) $from = date('Y-m-d', strtotime('-30 day'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $to))   $to   = date('Y-m-d');

        $kpi = Db::fetch(
            'SELECT
               COUNT(*) AS cnt,
               COALESCE(SUM(total_price), 0) AS gross,
               COALESCE(SUM(refund_amount), 0) AS refunds,
               SUM(status = "noshow") AS noshow,
               SUM(status = "canceled") AS canceled,
               SUM(status IN ("confirmed","done")) AS confirmed
             FROM reservations
             WHERE reservation_date BETWEEN ? AND ?',
            [$from, $to]
        );

        $daily = Db::fetchAll(
            'SELECT reservation_date AS d,
                    COUNT(*) AS cnt,
                    COALESCE(SUM(CASE WHEN status IN ("confirmed","done") THEN total_price ELSE 0 END), 0) AS rev,
                    COALESCE(SUM(refund_amount), 0) AS refunds
             FROM reservations
             WHERE reservation_date BETWEEN ? AND ?
             GROUP BY reservation_date
             ORDER BY reservation_date',
            [$from, $to]
        );

        $byVenue = Db::fetchAll(
            'SELECT v.id, v.name, v.area,
                    COUNT(r.id) AS cnt,
                    COALESCE(SUM(CASE WHEN r.status IN ("confirmed","done") THEN r.total_price ELSE 0 END), 0) AS rev,
                    COALESCE(SUM(r.refund_amount), 0) AS refunds
             FROM venues v
             LEFT JOIN reservations r ON r.venue_id = v.id AND r.reservation_date BETWEEN ? AND ?
             GROUP BY v.id, v.name, v.area
             HAVING cnt > 0
             ORDER BY rev DESC LIMIT 30',
            [$from, $to]
        );

        $pendingDeposits = Db::fetchAll(
            'SELECT r.code, r.reservation_date, r.start_hour, r.deposit_due_at, r.total_price, r.depositor_name,
                    v.name AS venue_name, u.name AS user_name, u.phone AS user_phone
             FROM reservations r
             JOIN venues v ON v.id = r.venue_id
             JOIN users  u ON u.id = r.user_id
             WHERE r.status = "pending"
             ORDER BY r.deposit_due_at LIMIT 50'
        );

        $refundQueue = Db::fetchAll(
            'SELECT r.code, r.reservation_date, r.canceled_at, r.refund_amount, r.cancel_reason, r.canceled_by,
                    v.name AS venue_name, u.name AS user_name, u.phone AS user_phone
             FROM reservations r
             JOIN venues v ON v.id = r.venue_id
             JOIN users  u ON u.id = r.user_id
             WHERE r.status = "canceled" AND r.refund_amount > 0
             ORDER BY r.canceled_at DESC LIMIT 50'
        );

        $this->view('admin/finance', [
            'title'           => '재무 — 어드민',
            'user'            => $user,
            'from'            => $from,
            'to'              => $to,
            'kpi'             => $kpi,
            'daily'           => $daily,
            'byVenue'         => $byVenue,
            'pendingDeposits' => $pendingDeposits,
            'refundQueue'     => $refundQueue,
        ], layout: 'admin');
    }
}
