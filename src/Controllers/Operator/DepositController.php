<?php
declare(strict_types=1);

namespace App\Controllers\Operator;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Db;
use App\Core\Response;

final class DepositController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth('operator');

        $pending = Db::fetchAll(
            'SELECT r.id, r.code, r.depositor_name, r.total_price,
                    r.deposit_due_at, r.created_at, r.reservation_date,
                    r.start_hour, r.duration_hours,
                    r.user_id, u.name AS user_name, u.phone AS user_phone, u.trust_score,
                    v.name AS venue_name, v.bank_name, v.bank_account,
                    c.name AS court_name
             FROM reservations r
             JOIN venues v ON v.id = r.venue_id
             JOIN courts c ON c.id = r.court_id
             JOIN users  u ON u.id = r.user_id
             WHERE v.owner_id = ? AND r.status = "pending"
             ORDER BY r.deposit_due_at ASC',
            [$user['id']]
        );

        $confirmed = Db::fetchAll(
            'SELECT r.code, r.depositor_name, r.total_price, r.paid_at,
                    r.reservation_date, r.start_hour, r.duration_hours,
                    v.name AS venue_name, c.name AS court_name, u.name AS user_name
             FROM reservations r
             JOIN venues v ON v.id = r.venue_id
             JOIN courts c ON c.id = r.court_id
             JOIN users  u ON u.id = r.user_id
             WHERE v.owner_id = ? AND r.status = "confirmed" AND r.paid_at IS NOT NULL
               AND r.paid_at >= NOW() - INTERVAL 7 DAY
             ORDER BY r.paid_at DESC LIMIT 20',
            [$user['id']]
        );

        $this->view('operator/deposits', [
            'title'     => '입금 확인 — 운영자',
            'user'      => $user,
            'pending'   => $pending,
            'confirmed' => $confirmed,
        ], layout: 'operator');
    }

    /**
     * 운영자가 입금 확인 → 예약을 confirmed 로 전환.
     */
    public function confirm(string $code): void
    {
        $user = $this->requireAuth('operator');

        $r = Db::fetch(
            'SELECT r.id, r.status, v.owner_id
             FROM reservations r
             JOIN venues v ON v.id = r.venue_id
             WHERE r.code = ?',
            [$code]
        );
        if (!$r)                               Response::notFound('예약을 찾을 수 없습니다.');
        if ((int) $r['owner_id'] !== (int) $user['id']) Response::forbidden('이 구장의 예약이 아닙니다.');
        if ($r['status'] !== 'pending')        Response::redirect('/operator/deposits');

        Db::query(
            'UPDATE reservations SET status = "confirmed", paid_at = NOW(), updated_at = NOW() WHERE id = ?',
            [$r['id']]
        );

        $rFull = Db::fetch('SELECT * FROM reservations WHERE id = ?', [$r['id']]);
        Db::insert('notifications', [
            'user_id'      => (int) $rFull['user_id'],
            'type'         => 'confirm',
            'title'        => '예약 확정 — 입금이 확인되었습니다',
            'body'         => "예약번호 $code",
            'link_url'     => '/reservations/' . $code,
            'related_type' => 'reservation',
            'related_id'   => (int) $r['id'],
        ]);
        // 알림톡 (키 미설정 시 자동 skip)
        $u = Db::fetch('SELECT name, phone FROM users WHERE id = ?', [(int) $rFull['user_id']]);
        \App\Services\Alimtalk::send(
            $_ENV['ALIMTALK_TPL_CONFIRM'] ?? 'reservation_confirm',
            $u['phone'] ?? '',
            ['#{name}' => $u['name'] ?? '', '#{code}' => $code],
            "[코트맵] 예약 확정 — $code"
        );

        \App\Services\WebhookService::fire('reservation.confirmed', (int) $rFull['venue_id'], [
            'code'      => $code,
            'venue_id'  => (int) $rFull['venue_id'],
            'court_id'  => (int) $rFull['court_id'],
            'user_id'   => (int) $rFull['user_id'],
            'date'      => $rFull['reservation_date'],
            'start_hour'=> (int) $rFull['start_hour'],
            'duration'  => (int) $rFull['duration_hours'],
            'total'     => (int) $rFull['total_price'],
        ]);

        $this->redirect('/operator/deposits');
    }

    /**
     * 운영자가 예약 취소 (입금 안 들어옴 등).
     */
    public function cancel(string $code): void
    {
        $user = $this->requireAuth('operator');

        $r = Db::fetch(
            'SELECT r.id, r.status, v.owner_id
             FROM reservations r
             JOIN venues v ON v.id = r.venue_id
             WHERE r.code = ?',
            [$code]
        );
        if (!$r)                                       Response::notFound();
        if ((int) $r['owner_id'] !== (int) $user['id']) Response::forbidden();
        if ($r['status'] !== 'pending')                 Response::redirect('/operator/deposits');

        Db::query(
            'UPDATE reservations SET status = "canceled", canceled_at = NOW(), canceled_by = "operator",
                                     cancel_reason = ?, updated_at = NOW() WHERE id = ?',
            [(string) ($_POST['reason'] ?? '운영자 취소'), $r['id']]
        );

        $this->redirect('/operator/deposits');
    }
}
