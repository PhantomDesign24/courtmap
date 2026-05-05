<?php
// 단골 빈자리 알림 — 단골 등록한 venue 의 향후 2시간 내 빈 슬롯 발견 시 알림 발송. 매 30분 cron.
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

use App\Core\Db;

$now = new DateTime();
$today = $now->format('Y-m-d');
$hourFrom = (int) $now->format('G');
$hourTo   = min(24, $hourFrom + 2);

$favs = Db::fetchAll(
    'SELECT f.user_id, f.venue_id, v.name AS venue_name
     FROM favorites f
     JOIN venues v ON v.id = f.venue_id
     WHERE f.notify_open_slot = 1 AND v.status = "active"'
);

$sent = 0;
foreach ($favs as $f) {
    $vid = (int) $f['venue_id'];

    // 최근 1시간 내 같은 venue 알림 보낸 적 있으면 skip (스팸 방지)
    $recent = Db::fetch(
        'SELECT 1 FROM notifications
         WHERE user_id = ? AND type = "alert" AND related_type = "venue" AND related_id = ?
           AND created_at > NOW() - INTERVAL 1 HOUR
         LIMIT 1',
        [(int) $f['user_id'], $vid]
    );
    if ($recent) continue;

    // 빈 슬롯 있는지 확인 — 활성 코트 중 차단 안된 슬롯이 1개라도?
    $courts = Db::fetchAll('SELECT id FROM courts WHERE venue_id = ? AND status = "active"', [$vid]);
    if (!$courts) continue;
    $cidIn = implode(',', array_map(static fn($c) => (int) $c['id'], $courts));
    $busy = Db::fetchAll(
        "SELECT court_id, start_hour, duration_hours FROM reservations
         WHERE venue_id = ? AND reservation_date = ?
           AND status IN ('pending','confirmed')
           AND start_hour < ? AND (start_hour + duration_hours) > ?",
        [$vid, $today, $hourTo, $hourFrom]
    );
    $blocked = []; // [court_id][hour] => true
    foreach ($busy as $b) {
        for ($h = (int) $b['start_hour']; $h < (int) $b['start_hour'] + (int) $b['duration_hours']; $h++) {
            $blocked[(int) $b['court_id']][$h] = true;
        }
    }
    $hasFree = false;
    foreach ($courts as $c) {
        $cid = (int) $c['id'];
        for ($h = $hourFrom; $h < $hourTo; $h++) {
            if (empty($blocked[$cid][$h])) { $hasFree = true; break 2; }
        }
    }
    if (!$hasFree) continue;

    Db::insert('notifications', [
        'user_id'      => (int) $f['user_id'],
        'type'         => 'alert',
        'title'        => $f['venue_name'] . ' 빈자리 떴어요!',
        'body'         => "지금부터 2시간 안에 빈 코트가 있어요",
        'link_url'     => '/venues/' . $vid,
        'related_type' => 'venue',
        'related_id'   => $vid,
    ]);
    $sent++;
}

echo date('Y-m-d H:i:s') . " favorites_alert: $sent sent\n";
