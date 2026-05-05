<?php
// 입장 체크된 confirmed 예약 → 시간 종료 후 done 처리 + trust_score +1 (월 최대 5).
// 매 10분 cron.
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

use App\Core\Db;

$now = new DateTime();
$nowStr = $now->format('Y-m-d H:i:s');

// 종료 시각 = reservation_date + (start_hour + duration_hours):00
// entered_at IS NOT NULL && 종료 시각 < NOW() && status='confirmed' → done
$rows = Db::fetchAll(
    "SELECT r.id, r.user_id, r.code FROM reservations r
     WHERE r.status = 'confirmed' AND r.entered_at IS NOT NULL
       AND CONCAT(r.reservation_date, ' ', LPAD(r.start_hour + r.duration_hours, 2, '0'), ':00:00') < NOW()"
);

$done = 0;
foreach ($rows as $r) {
    Db::transaction(function () use ($r) {
        Db::query('UPDATE reservations SET status = "done", updated_at = NOW() WHERE id = ? AND status = "confirmed"', [(int) $r['id']]);

        // 신뢰점수 +1 (월 최대 5)
        $monthGain = (int) (Db::fetch(
            "SELECT COALESCE(SUM(score_delta), 0) AS s FROM noshow_logs
             WHERE user_id = ? AND score_delta > 0 AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')",
            [(int) $r['user_id']]
        )['s'] ?? 0);
        if ($monthGain < 5) {
            Db::query('UPDATE users SET trust_score = LEAST(100, trust_score + 1) WHERE id = ?', [(int) $r['user_id']]);
            // 로그 (noshow_logs 의 양수로 기록)
            Db::insert('noshow_logs', [
                'reservation_id' => (int) $r['id'],
                'user_id'        => (int) $r['user_id'],
                'venue_id'       => (int) (Db::fetch('SELECT venue_id FROM reservations WHERE id = ?', [$r['id']])['venue_id']),
                'detected_by'    => 'auto',
                'score_delta'    => 1,
                'note'           => '정상 이용 +1',
            ]);
            // 점수 회복으로 restricted_until 해제
            Db::query(
                'UPDATE users SET restricted_until = NULL WHERE id = ? AND trust_score >= 60',
                [(int) $r['user_id']]
            );
        }
    });
    $done++;
}
echo date('Y-m-d H:i:s') . " mark_done: $done done\n";
