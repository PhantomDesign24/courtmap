<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;

/**
 * 빈자리 알림 (slot watch) 매칭/발송.
 *
 * 동작 방식:
 *  - 예약이 취소·만료되면 onSlotFreed() 가 해당 슬롯과 매칭되는 active watch 를 찾아
 *    notifications 에 알림을 INSERT.
 *  - slot_watch_alerts 의 (watch_id, slot_date, slot_start_hour, slot_court_id) UNIQUE 로
 *    동일 슬롯 중복 알림 방지.
 *  - one_time watch 는 1회 발송 후 status='triggered'.
 *  - recurring watch 는 last_alerted_at 만 갱신, 계속 활성.
 */
final class SlotWatchService
{
    /**
     * 슬롯이 비었을 때 호출.
     * 매칭되는 active watch 들에 알림 발송.
     */
    public static function onSlotFreed(
        int $venueId,
        int $courtId,
        string $date,
        int $startHour,
        int $duration,
        ?int $reservationId = null
    ): int {
        $dow = (int) date('w', strtotime($date));
        $endHour = $startHour + $duration;

        // 매칭 조건:
        //  - 같은 venue
        //  - 코트: watch.court_id IS NULL (전체) 또는 = $courtId
        //  - type=recurring 이면 day_of_week == $dow
        //  - type=one_time 이면 target_date == $date
        //  - watch 의 시간대(start~end) 가 freed slot 의 startHour 를 포함
        //    즉, watch.start_hour <= $startHour AND watch.end_hour >= $endHour
        //    (사용자가 7~10시 사이를 원하면 7시 1시간, 7시 2시간, 8시 1시간 모두 매칭)
        $watches = Db::fetchAll(
            'SELECT id, user_id, type, court_id, slot_unit_hours
             FROM slot_watches
             WHERE status = "active"
               AND venue_id = ?
               AND (court_id IS NULL OR court_id = ?)
               AND (
                 (type = "recurring" AND day_of_week = ? AND (expires_at IS NULL OR expires_at > NOW()))
                 OR (type = "one_time" AND target_date = ?)
               )
               AND start_hour <= ? AND end_hour >= ?
               AND slot_unit_hours <= ?',
            [$venueId, $courtId, $dow, $date, $startHour, $endHour, $duration]
        );
        if (!$watches) return 0;

        // venue/court 이름 조회 (notifications 메시지용)
        $info = Db::fetch(
            'SELECT v.name AS venue_name, c.name AS court_name
             FROM venues v JOIN courts c ON c.id = ?
             WHERE v.id = ?',
            [$courtId, $venueId]
        );
        $venueName = $info['venue_name'] ?? '구장';
        $courtName = $info['court_name'] ?? '';

        $sent = 0;
        foreach ($watches as $w) {
            // 중복 알림 방지 — UNIQUE INSERT 시도, 충돌 시 skip
            try {
                Db::insert('slot_watch_alerts', [
                    'watch_id'        => (int) $w['id'],
                    'reservation_id'  => $reservationId,
                    'slot_date'       => $date,
                    'slot_start_hour' => $startHour,
                    'slot_court_id'   => $courtId,
                ]);
            } catch (\Throwable $e) {
                continue; // duplicate
            }

            $title = '빈자리 알림';
            $body  = sprintf(
                '%s %s · %s %02d:00 자리가 났어요',
                $venueName,
                $courtName,
                $date,
                $startHour
            );

            Db::insert('notifications', [
                'user_id'      => (int) $w['user_id'],
                'type'         => 'watch',
                'title'        => $title,
                'body'         => $body,
                'link_url'     => '/venues/' . $venueId . '?date=' . $date,
                'related_type' => 'venue',
                'related_id'   => $venueId,
            ]);

            // 상태 갱신
            if ($w['type'] === 'one_time') {
                Db::query('UPDATE slot_watches SET status = "triggered", last_alerted_at = NOW() WHERE id = ?', [(int) $w['id']]);
            } else {
                Db::query('UPDATE slot_watches SET last_alerted_at = NOW() WHERE id = ?', [(int) $w['id']]);
            }
            $sent++;
        }
        return $sent;
    }

    /**
     * 만료된 watch 정리 (크론 보조).
     */
    public static function expireOld(): int
    {
        $aff = Db::query(
            'UPDATE slot_watches SET status = "expired"
             WHERE status = "active"
               AND (
                 (type = "one_time" AND target_date < CURDATE())
                 OR (type = "recurring" AND expires_at IS NOT NULL AND expires_at < NOW())
               )'
        );
        return is_int($aff) ? $aff : 0;
    }

    /**
     * 사용자의 과거 예약 이력에서 자주 이용한 (venue_id, day_of_week, start_hour) 추천.
     * @return array<int, array{venue_id:int, venue_name:string, day_of_week:int, start_hour:int, cnt:int}>
     */
    public static function recommendTimes(int $userId, int $limit = 5): array
    {
        return Db::fetchAll(
            'SELECT r.venue_id, v.name AS venue_name,
                    DAYOFWEEK(r.reservation_date) - 1 AS day_of_week,
                    r.start_hour, COUNT(*) AS cnt
             FROM reservations r
             JOIN venues v ON v.id = r.venue_id
             WHERE r.user_id = ?
               AND r.status IN ("confirmed","done")
               AND r.reservation_date >= CURDATE() - INTERVAL 90 DAY
             GROUP BY r.venue_id, day_of_week, r.start_hour
             HAVING cnt >= 2
             ORDER BY cnt DESC, day_of_week, start_hour
             LIMIT ?',
            [$userId, $limit]
        );
    }
}
