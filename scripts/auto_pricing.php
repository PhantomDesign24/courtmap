<?php
// 자동 프라이싱 — auto_pricing_rules 의 active 룰을 순회하며 조건 맞는 빈 슬롯에 dynamic_pricing 자동 발행. 매 5분 cron.
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

use App\Core\Db;

$now    = new DateTime();
$today  = $now->format('Y-m-d');
$dow    = (int) $now->format('w');
$hourNow= (int) $now->format('G');

$rules = Db::fetchAll('SELECT * FROM auto_pricing_rules WHERE status = "active"');
$created = 0;

foreach ($rules as $r) {
    // 요일 마스크 검사
    if (!((int) $r['dow_mask'] & (1 << $dow))) continue;

    $venueId   = (int) $r['venue_id'];
    $hoursBef  = (int) $r['trigger_hours_before'];
    $applyFrom = max((int) $r['apply_from_hour'], $hourNow);
    $applyTo   = (int) $r['apply_to_hour'];
    $disc      = (int) $r['discount_pct'];

    // 검사 범위: 지금 ~ 지금+hoursBef
    $rangeEnd = min(24, $hourNow + $hoursBef);
    if ($applyFrom >= min($applyTo, $rangeEnd)) continue;

    // 코트별 빈 슬롯 확인
    $courts = Db::fetchAll('SELECT id FROM courts WHERE venue_id = ? AND status = "active"', [$venueId]);
    foreach ($courts as $c) {
        $cid = (int) $c['id'];
        for ($h = $applyFrom; $h < min($applyTo, $rangeEnd); $h++) {
            // 이 슬롯에 활성 예약 있나?
            $busy = Db::fetch(
                'SELECT 1 FROM reservations WHERE court_id = ? AND reservation_date = ?
                   AND status IN ("pending","confirmed")
                   AND start_hour <= ? AND (start_hour + duration_hours) > ?',
                [$cid, $today, $h, $h]
            );
            if ($busy) continue;

            // 이미 dynamic_pricing 있나?
            $exists = Db::fetch(
                'SELECT 1 FROM dynamic_pricing
                 WHERE venue_id = ? AND court_id = ? AND target_date = ?
                   AND target_start_hour <= ? AND target_end_hour > ?
                   AND status = "active"',
                [$venueId, $cid, $today, $h, $h]
            );
            if ($exists) continue;

            Db::insert('dynamic_pricing', [
                'venue_id'          => $venueId,
                'court_id'          => $cid,
                'target_date'       => $today,
                'target_start_hour' => $h,
                'target_end_hour'   => $h + 1,
                'discount_pct'      => $disc,
                'status'            => 'active',
                'created_by'        => (int) Db::fetch('SELECT owner_id FROM venues WHERE id = ?', [$venueId])['owner_id'],
                'expires_at'        => date('Y-m-d H:i:s', strtotime("$today " . sprintf('%02d:00', $h + 1))),
            ]);
            $created++;
        }
    }
}
echo date('Y-m-d H:i:s') . " auto_pricing: $created hot deals created\n";
