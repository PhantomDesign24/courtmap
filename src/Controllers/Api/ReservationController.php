<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\ReservationService;

final class ReservationController extends Controller
{
    public function create(): void
    {
        $user = Auth::user();
        if (!$user) Response::json(['error' => '로그인이 필요합니다'], 401);

        $restriction = Auth::reservationRestriction($user);
        if ($restriction) Response::json(['error' => $restriction], 403);

        $input = Request::isJson() ? (Request::json() ?? []) : Request::all();

        try {
            $r = ReservationService::create((int) $user['id'], $input);
            Response::json([
                'ok'       => true,
                'code'     => $r['code'],
                'redirect' => '/reservations/' . $r['code'],
            ]);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }

    public function status(string $code): void
    {
        $user = Auth::user();
        if (!$user) Response::json(['error' => '로그인이 필요합니다'], 401);

        $r = ReservationService::findByCode($code, (int) $user['id']);
        if (!$r) Response::json(['error' => 'not found'], 404);

        Response::json([
            'code'    => $r['code'],
            'status'  => $r['status'],
            'paid_at' => $r['paid_at'],
        ]);
    }

    public function cancel(string $code): void
    {
        $user = Auth::user();
        if (!$user) Response::json(['error' => '로그인이 필요합니다'], 401);

        $r = ReservationService::findByCode($code, (int) $user['id']);
        if (!$r) Response::json(['error' => 'not found'], 404);
        if (!in_array($r['status'], ['pending', 'confirmed'], true)) {
            Response::json(['error' => '취소할 수 없는 상태입니다.'], 400);
        }

        // 환불율 계산
        $startTs = strtotime($r['reservation_date'] . ' ' . sprintf('%02d:00:00', (int) $r['start_hour']));
        $diffH = ($startTs - time()) / 3600;
        $pct = $diffH >= 24 ? (int) $r['refund_24h_pct']
             : ($diffH >= 1 ? (int) $r['refund_1h_pct']
                            : (int) $r['refund_lt1h_pct']);

        $reason = (string) ($_POST['reason'] ?? '사용자 취소');
        // bulk_group 이 있으면 같은 묶음 모두 일괄 취소 + 빈자리 알림 매칭
        [$totalRefund, $freedSlots] = \App\Core\Db::transaction(function () use ($r, $pct, $user, $reason) {
            $rows = !empty($r['bulk_group'])
                ? \App\Core\Db::fetchAll(
                    'SELECT id, total_price, venue_id, court_id, reservation_date, start_hour, duration_hours
                     FROM reservations
                     WHERE bulk_group = ? AND user_id = ? AND status IN ("pending","confirmed")',
                    [$r['bulk_group'], (int) $user['id']]
                )
                : [[
                    'id' => (int) $r['id'], 'total_price' => (int) $r['total_price'],
                    'venue_id' => (int) $r['venue_id'], 'court_id' => (int) $r['court_id'],
                    'reservation_date' => $r['reservation_date'],
                    'start_hour' => (int) $r['start_hour'], 'duration_hours' => (int) $r['duration_hours'],
                  ]];

            $sum = 0;
            $freed = [];
            foreach ($rows as $row) {
                $rf = (int) round((int) $row['total_price'] * $pct / 100);
                \App\Core\Db::query(
                    'UPDATE reservations SET status = "canceled", canceled_at = NOW(), canceled_by = "user",
                                             cancel_reason = ?, refund_amount = ?, updated_at = NOW()
                     WHERE id = ? AND status IN ("pending","confirmed")',
                    [$reason, $rf, (int) $row['id']]
                );
                $sum += $rf;
                $freed[] = $row;
            }
            return [$sum, $freed];
        });

        foreach ($freedSlots as $s) {
            \App\Services\SlotWatchService::onSlotFreed(
                (int) $s['venue_id'], (int) $s['court_id'], (string) $s['reservation_date'],
                (int) $s['start_hour'], (int) $s['duration_hours'], (int) $s['id']
            );
        }

        \App\Core\Db::insert('notifications', [
            'user_id' => (int) $user['id'],
            'type'    => 'system',
            'title'   => '예약이 취소되었습니다',
            'body'    => (!empty($r['bulk_group']) ? '묶음 예약 일괄 취소 · ' : '예약번호 ' . $r['code'] . ' · ')
                       . "환불 {$pct}% (" . number_format($totalRefund) . "원)",
            'related_type' => 'reservation',
            'related_id'   => (int) $r['id'],
        ]);

        Response::json(['ok' => true, 'refund_amount' => $totalRefund, 'refund_pct' => $pct]);
    }
}
