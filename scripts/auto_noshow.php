<?php
// 시작 후 10분간 입장 체크 안 된 confirmed 예약을 자동 노쇼 처리. 매 10분 cron.
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

use App\Core\Db;

$rows = Db::fetchAll(
    'SELECT r.id, r.code, r.user_id, r.venue_id
     FROM reservations r
     WHERE r.status = "confirmed" AND r.entered_at IS NULL
       AND ADDTIME(CONCAT(r.reservation_date, " ", LPAD(r.start_hour, 2, "0"), ":00:00"), "00:10:00") < NOW()
       AND r.reservation_date >= CURDATE() - INTERVAL 7 DAY'
);

$processed = 0;
foreach ($rows as $r) {
    try {
        Db::transaction(function () use ($r) {
            // status=confirmed 일 때만 UPDATE — 다른 cron 인스턴스가 이미 처리했으면 0건 → skip
            $stmt = Db::query(
                'UPDATE reservations SET status = "noshow", updated_at = NOW() WHERE id = ? AND status = "confirmed"',
                [$r['id']]
            );
            if ($stmt->rowCount() === 0) {
                throw new \RuntimeException('already processed');
            }
            Db::insert('noshow_logs', [
                'reservation_id' => (int) $r['id'],
                'user_id'        => (int) $r['user_id'],
                'venue_id'       => (int) $r['venue_id'],
                'detected_by'    => 'auto',
                'score_delta'    => -15,
                'note'           => '시작 후 10분 입장 체크 없음',
            ]);
            // 신뢰점수 차감 (하한 0)
            Db::query('UPDATE users SET trust_score = GREATEST(0, trust_score - 15) WHERE id = ?', [$r['user_id']]);

            // 점수 임계치에 따라 restricted_until 자동 설정
            $u = Db::fetch('SELECT trust_score FROM users WHERE id = ?', [$r['user_id']]);
            $score = (int) $u['trust_score'];
            if ($score < 40) {
                Db::query('UPDATE users SET restricted_until = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = ?', [$r['user_id']]);
            } elseif ($score < 60) {
                Db::query('UPDATE users SET restricted_until = DATE_ADD(NOW(), INTERVAL 7 DAY) WHERE id = ?', [$r['user_id']]);
            }

            Db::insert('notifications', [
                'user_id'      => (int) $r['user_id'],
                'type'         => 'system',
                'title'        => '노쇼 처리되었습니다',
                'body'         => "예약번호 {$r['code']} · 신뢰점수 -15 차감",
                'related_type' => 'reservation',
                'related_id'   => (int) $r['id'],
            ]);
        });
        $processed++;
    } catch (\Throwable $e) {
        error_log('auto_noshow failed: ' . $e->getMessage());
    }
}

echo date('Y-m-d H:i:s') . " auto_noshow: $processed row(s)\n";
