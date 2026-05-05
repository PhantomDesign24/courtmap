<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;

final class SlotService
{
    /**
     * 특정 날짜의 구장 슬롯 가용성 조회.
     * @return array{date:string, slot_unit:int, courts:array, slots:array, is_holiday:bool, holiday_name:?string}
     */
    public static function forDate(int $venueId, string $date): array
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new \RuntimeException('date must be YYYY-MM-DD');
        }
        $venue = Db::fetch('SELECT * FROM venues WHERE id = ? AND status = "active"', [$venueId]);
        if (!$venue) throw new \RuntimeException('구장을 찾을 수 없습니다.');

        $dow = (int) date('w', strtotime($date));
        $isHoliday = (bool) Db::fetch('SELECT 1 FROM holidays WHERE holiday_date = ?', [$date]);
        $holidayName = null;
        if ($isHoliday) {
            $h = Db::fetch('SELECT name FROM holidays WHERE holiday_date = ?', [$date]);
            $holidayName = $h['name'] ?? null;
        }

        // 슬롯 단위 결정 (우선순위: specific_date > holiday > dow > default)
        $slotUnit = self::resolveSlotUnit($venueId, $date, $dow, $isHoliday);

        // 운영 시간
        $hours = Db::fetch(
            'SELECT open_time, close_time, is_closed FROM venue_hours WHERE venue_id = ? AND day_of_week = ?',
            [$venueId, $dow]
        );
        $isClosed = $hours ? (bool) $hours['is_closed'] : false;
        $openH  = $hours ? (int) substr($hours['open_time'],  0, 2) : 10;
        $closeH = $hours ? (int) substr($hours['close_time'], 0, 2) : 23;
        if ($hours && substr($hours['close_time'], 0, 5) === '23:59') $closeH = 24;
        // 임시 휴무
        if (Db::fetch('SELECT 1 FROM venue_closures WHERE venue_id = ? AND closed_date = ?', [$venueId, $date])) {
            $isClosed = true;
        }

        // 코트
        $courts = Db::fetchAll(
            'SELECT id, name, sort_order, COALESCE(price_override, ?) AS price
             FROM courts WHERE venue_id = ? AND status = "active" ORDER BY sort_order, id',
            [(int) $venue['price_per_hour'], $venueId]
        );
        $courtsOut = array_map(static fn($c) => [
            'id'    => (int) $c['id'],
            'name'  => $c['name'],
            'price' => (int) $c['price'],
        ], $courts);

        // 활성 예약 (충돌 체크용) — 해당 날짜
        $resv = Db::fetchAll(
            'SELECT court_id, start_hour, duration_hours
             FROM reservations
             WHERE venue_id = ? AND reservation_date = ?
               AND status IN ("pending", "confirmed")',
            [$venueId, $date]
        );
        $blocked = []; // [court_id][hour] => true
        foreach ($resv as $r) {
            $cid = (int) $r['court_id'];
            $sh  = (int) $r['start_hour'];
            $dur = (int) $r['duration_hours'];
            for ($h = $sh; $h < $sh + $dur; $h++) {
                $blocked[$cid][$h] = true;
            }
        }

        // 다이나믹 프라이싱 (오늘 active)
        $dpRows = Db::fetchAll(
            'SELECT court_id, target_start_hour, target_end_hour, discount_pct
             FROM dynamic_pricing
             WHERE venue_id = ? AND target_date = ? AND status = "active"',
            [$venueId, $date]
        );

        // 슬롯 빌드
        $slots = [];
        if (!$isClosed) {
            for ($h = $openH; $h + $slotUnit <= $closeH; $h += $slotUnit) {
                $slotCourts = [];
                foreach ($courts as $c) {
                    $cid = (int) $c['id'];
                    $avail = true;
                    for ($k = 0; $k < $slotUnit; $k++) {
                        if (!empty($blocked[$cid][$h + $k])) { $avail = false; break; }
                    }
                    // 다이나믹 프라이싱
                    $hot = false;
                    $disc = 0;
                    foreach ($dpRows as $dp) {
                        if ($dp['court_id'] !== null && (int) $dp['court_id'] !== $cid) continue;
                        $dpStart = (int) $dp['target_start_hour'];
                        $dpEnd   = (int) $dp['target_end_hour'];
                        if ($h >= $dpStart && $h + $slotUnit <= $dpEnd) {
                            $hot = true;
                            $disc = max($disc, (int) $dp['discount_pct']);
                        }
                    }
                    $slotCourts[] = [
                        'court_id' => $cid,
                        'name'     => $c['name'],
                        'avail'    => $avail,
                        'hot'      => $hot,
                        'discount_pct' => $disc,
                    ];
                }
                $slots[] = [
                    'hour'     => $h,
                    'end_hour' => $h + $slotUnit,
                    'label'    => sprintf('%02d:00', $h),
                    'end_label'=> sprintf('%02d:00', $h + $slotUnit),
                    'courts'   => $slotCourts,
                ];
            }
        }

        return [
            'date'         => $date,
            'slot_unit'    => $slotUnit,
            'is_closed'    => $isClosed,
            'is_holiday'   => $isHoliday,
            'holiday_name' => $holidayName,
            'courts'       => $courtsOut,
            'slots'        => $slots,
        ];
    }

    private static function resolveSlotUnit(int $venueId, string $date, int $dow, bool $isHoliday): int
    {
        $rules = Db::fetchAll('SELECT * FROM slot_rules WHERE venue_id = ?', [$venueId]);
        // 우선순위: specific_date > holiday > dow > default
        foreach ($rules as $r) {
            if ($r['rule_type'] === 'specific_date' && $r['specific_date'] === $date) {
                return (int) $r['slot_unit_hours'];
            }
        }
        if ($isHoliday) {
            foreach ($rules as $r) {
                if ($r['rule_type'] === 'holiday') return (int) $r['slot_unit_hours'];
            }
        }
        foreach ($rules as $r) {
            if ($r['rule_type'] === 'dow' && (int) $r['day_of_week'] === $dow) {
                return (int) $r['slot_unit_hours'];
            }
        }
        foreach ($rules as $r) {
            if ($r['rule_type'] === 'default') return (int) $r['slot_unit_hours'];
        }
        return 1;
    }
}
