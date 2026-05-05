<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Db;

final class CalendarController extends Controller
{
    /**
     * iCal 피드 — 구장의 확정 예약 export. 인증 없음 (퍼블릭).
     * URL: /api/venues/{id}/calendar.ics
     */
    public function venue(string $id): void
    {
        $venueId = (int) $id;
        $venue = Db::fetch('SELECT id, name FROM venues WHERE id = ? AND status = "active"', [$venueId]);
        if (!$venue) { http_response_code(404); echo 'not found'; exit; }

        $rows = Db::fetchAll(
            'SELECT r.code, r.reservation_date, r.start_hour, r.duration_hours, r.status,
                    c.name AS court_name, u.name AS user_name
             FROM reservations r
             JOIN courts c ON c.id = r.court_id
             JOIN users  u ON u.id = r.user_id
             WHERE r.venue_id = ? AND r.status IN ("confirmed", "done")
               AND r.reservation_date >= CURDATE() - INTERVAL 30 DAY
             ORDER BY r.reservation_date, r.start_hour',
            [$venueId]
        );

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: inline; filename="venue-' . $venueId . '.ics"');

        $lines = [];
        $lines[] = 'BEGIN:VCALENDAR';
        $lines[] = 'VERSION:2.0';
        $lines[] = 'PRODID:-//CourtMap//Venue ' . $venueId . '//KO';
        $lines[] = 'X-WR-CALNAME:' . self::esc('코트맵 — ' . $venue['name']);
        $lines[] = 'X-WR-TIMEZONE:Asia/Seoul';

        foreach ($rows as $r) {
            $startH = (int) $r['start_hour'];
            $dur    = (int) $r['duration_hours'];
            $dt     = (string) $r['reservation_date'];
            $start  = sprintf('%sT%02d0000', str_replace('-', '', $dt), $startH);
            $end    = sprintf('%sT%02d0000', str_replace('-', '', $dt), $startH + $dur);
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:' . $r['code'] . '@bad.mvc.kr';
            $lines[] = 'DTSTAMP:' . gmdate('Ymd\THis\Z');
            $lines[] = 'DTSTART;TZID=Asia/Seoul:' . $start;
            $lines[] = 'DTEND;TZID=Asia/Seoul:' . $end;
            $lines[] = 'SUMMARY:' . self::esc('[' . $r['court_name'] . '] ' . $r['user_name']);
            $lines[] = 'DESCRIPTION:' . self::esc('예약번호 ' . $r['code'] . ' · 상태 ' . $r['status']);
            $lines[] = 'END:VEVENT';
        }
        $lines[] = 'END:VCALENDAR';
        echo implode("\r\n", $lines) . "\r\n";
        exit;
    }

    private static function esc(string $s): string
    {
        $s = str_replace(['\\', "\n", ',', ';'], ['\\\\', '\\n', '\\,', '\\;'], $s);
        return $s;
    }
}
