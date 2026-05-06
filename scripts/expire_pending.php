<?php
// 입금기한 초과한 pending 예약을 expired 로 자동 전환 — 매분 cron 실행
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

use App\Core\Db;

$rows = Db::fetchAll(
    'SELECT id, code, user_id, venue_id, court_id, reservation_date, start_hour, duration_hours FROM reservations
     WHERE status = "pending" AND deposit_due_at < NOW()'
);

foreach ($rows as $r) {
    Db::query(
        'UPDATE reservations
         SET status = "expired",
             canceled_at = NOW(),
             canceled_by = "system",
             cancel_reason = "입금기한 초과 자동 취소",
             updated_at = NOW()
         WHERE id = ? AND status = "pending"',
        [$r['id']]
    );
    Db::insert('notifications', [
        'user_id'      => (int) $r['user_id'],
        'type'         => 'system',
        'title'        => '예약이 자동 취소되었습니다',
        'body'         => "입금기한 초과 — 예약번호 {$r['code']}",
        'related_type' => 'reservation',
        'related_id'   => (int) $r['id'],
    ]);
    \App\Services\SlotWatchService::onSlotFreed(
        (int) $r['venue_id'], (int) $r['court_id'], (string) $r['reservation_date'],
        (int) $r['start_hour'], (int) $r['duration_hours'], (int) $r['id']
    );
}

\App\Services\SlotWatchService::expireOld();

echo date('Y-m-d H:i:s') . " expire_pending: " . count($rows) . " row(s)\n";
