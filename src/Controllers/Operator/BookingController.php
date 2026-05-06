<?php
declare(strict_types=1);

namespace App\Controllers\Operator;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Response;

final class BookingController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth('operator');

        $range = (string) ($_GET['range'] ?? 'today');
        $status = (string) ($_GET['status'] ?? '');
        $q      = trim((string) ($_GET['q'] ?? ''));

        $where = ['v.owner_id = ?'];
        $params = [$user['id']];

        match ($range) {
            'today'   => array_push($where, 'r.reservation_date = CURDATE()'),
            'week'    => array_push($where, 'r.reservation_date BETWEEN CURDATE() AND CURDATE() + INTERVAL 7 DAY'),
            'past'    => array_push($where, 'r.reservation_date < CURDATE()'),
            'all'     => null,
            default   => array_push($where, 'r.reservation_date = CURDATE()'),
        };

        if ($status !== '' && in_array($status, ['pending','confirmed','done','noshow','canceled','expired'], true)) {
            $where[] = 'r.status = ?';
            $params[] = $status;
        }
        if ($q !== '') {
            $where[] = '(u.name LIKE ? OR u.phone LIKE ? OR r.code LIKE ?)';
            array_push($params, "%$q%", "%$q%", "%$q%");
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);
        $bookings = Db::fetchAll(
            "SELECT r.id, r.code, r.status, r.reservation_date, r.start_hour, r.duration_hours,
                    r.total_price, r.entered_at, r.depositor_name,
                    u.id AS user_id, u.name AS user_name, u.phone AS user_phone, u.trust_score,
                    v.name AS venue_name, c.name AS court_name
             FROM reservations r
             JOIN venues v ON v.id = r.venue_id
             JOIN courts c ON c.id = r.court_id
             JOIN users  u ON u.id = r.user_id
             $whereSql
             ORDER BY r.reservation_date ASC, r.start_hour ASC",
            $params
        );

        $this->view('operator/bookings', [
            'title'    => '예약 관리 — 운영자',
            'user'     => $user,
            'bookings' => $bookings,
            'range'    => $range,
            'status'   => $status,
            'q'        => $q,
        ], layout: 'operator');
    }

    public function checkIn(string $code): void
    {
        [$user, $r] = $this->loadOwn($code);
        if ($r['status'] !== 'confirmed') $this->redirect('/operator/bookings');
        Db::query('UPDATE reservations SET entered_at = NOW(), updated_at = NOW() WHERE id = ?', [$r['id']]);
        $this->redirect('/operator/bookings?range=' . ($_GET['from'] ?? 'today'));
    }

    public function noshow(string $code): void
    {
        [$user, $r] = $this->loadOwn($code);
        if ($r['status'] !== 'confirmed') $this->redirect('/operator/bookings');

        Db::transaction(function () use ($r, $user) {
            Db::query('UPDATE reservations SET status = "noshow", updated_at = NOW() WHERE id = ?', [$r['id']]);
            Db::insert('noshow_logs', [
                'reservation_id' => (int) $r['id'],
                'user_id'        => (int) $r['user_id'],
                'venue_id'       => (int) $r['venue_id'],
                'detected_by'    => 'operator',
                'reported_by'    => (int) $user['id'],
                'score_delta'    => -15,
                'note'           => '운영자 수동 신고',
            ]);
            Db::query('UPDATE users SET trust_score = GREATEST(0, trust_score - 15) WHERE id = ?', [$r['user_id']]);
            $u = Db::fetch('SELECT trust_score FROM users WHERE id = ?', [$r['user_id']]);
            $score = (int) $u['trust_score'];
            if ($score < 40) {
                Db::query('UPDATE users SET restricted_until = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = ?', [$r['user_id']]);
            } elseif ($score < 60) {
                Db::query('UPDATE users SET restricted_until = DATE_ADD(NOW(), INTERVAL 7 DAY) WHERE id = ?', [$r['user_id']]);
            }
            Db::insert('notifications', [
                'user_id' => (int) $r['user_id'],
                'type'    => 'system',
                'title'   => '노쇼 처리되었습니다',
                'body'    => "예약번호 {$r['code']} · 신뢰점수 -15",
                'related_type' => 'reservation',
                'related_id'   => (int) $r['id'],
            ]);
        });
        $this->redirect('/operator/bookings?range=' . ($_GET['from'] ?? 'today'));
    }

    public function cancel(string $code): void
    {
        [$user, $r] = $this->loadOwn($code);
        if (!in_array($r['status'], ['pending','confirmed'], true)) $this->redirect('/operator/bookings');

        $reason = (string) ($_POST['reason'] ?? '운영자 취소');
        $freed = Db::transaction(function () use ($r, $reason) {
            $rows = !empty($r['bulk_group'])
                ? Db::fetchAll(
                    'SELECT id, venue_id, court_id, reservation_date, start_hour, duration_hours
                     FROM reservations WHERE bulk_group = ? AND status IN ("pending","confirmed")',
                    [$r['bulk_group']]
                )
                : [[
                    'id' => (int) $r['id'], 'venue_id' => (int) $r['venue_id'], 'court_id' => (int) $r['court_id'],
                    'reservation_date' => $r['reservation_date'],
                    'start_hour' => (int) $r['start_hour'], 'duration_hours' => (int) $r['duration_hours'],
                  ]];
            if (!empty($r['bulk_group'])) {
                Db::query(
                    'UPDATE reservations SET status = "canceled", canceled_at = NOW(), canceled_by = "operator",
                                             cancel_reason = ?, updated_at = NOW()
                     WHERE bulk_group = ? AND status IN ("pending","confirmed")',
                    [$reason, $r['bulk_group']]
                );
            } else {
                Db::query(
                    'UPDATE reservations SET status = "canceled", canceled_at = NOW(), canceled_by = "operator",
                                             cancel_reason = ?, updated_at = NOW() WHERE id = ?',
                    [$reason, $r['id']]
                );
            }
            return $rows;
        });
        foreach ($freed as $s) {
            \App\Services\SlotWatchService::onSlotFreed(
                (int) $s['venue_id'], (int) $s['court_id'], (string) $s['reservation_date'],
                (int) $s['start_hour'], (int) $s['duration_hours'], (int) $s['id']
            );
        }
        Db::insert('notifications', [
            'user_id' => (int) $r['user_id'],
            'type'    => 'system',
            'title'   => '예약이 취소되었습니다',
            'body'    => (!empty($r['bulk_group']) ? '묶음 예약 일괄 취소' : "예약번호 {$r['code']} · 운영자 취소"),
            'related_type' => 'reservation',
            'related_id'   => (int) $r['id'],
        ]);
        $this->redirect('/operator/bookings?range=' . ($_GET['from'] ?? 'today'));
    }

    /** @return array{0: array, 1: array} [$user, $reservation] */
    private function loadOwn(string $code): array
    {
        $user = $this->requireAuth('operator');
        $r = Db::fetch(
            'SELECT r.*, v.owner_id FROM reservations r
             JOIN venues v ON v.id = r.venue_id WHERE r.code = ?',
            [$code]
        );
        if (!$r)                                        Response::notFound('예약을 찾을 수 없습니다.');
        if ((int) $r['owner_id'] !== (int) $user['id']) Response::forbidden('이 구장의 예약이 아닙니다.');
        return [$user, $r];
    }
}
