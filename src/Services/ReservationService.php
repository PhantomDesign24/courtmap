<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;

final class ReservationService
{
    /**
     * 예약 신청 (status=pending). 실패 시 \RuntimeException.
     * 멀티 코트 지원: court_ids[] 또는 단일 court_id.
     * 동적 장비: equipment[] = [{id, qty}] 또는 legacy rent_racket/rent_shuttle.
     * @return array{id:int, code:string, ids:array, group:?string}
     */
    public static function create(int $userId, array $in): array
    {
        $venueId  = (int) ($in['venue_id'] ?? 0);
        $courtIds = isset($in['court_ids']) && is_array($in['court_ids'])
                  ? array_values(array_filter(array_map('intval', $in['court_ids'])))
                  : [(int) ($in['court_id'] ?? 0)];
        $courtIds = array_values(array_unique(array_filter($courtIds)));
        sort($courtIds, SORT_NUMERIC); // deadlock 방지 — 항상 같은 순서로 잠금
        $date     = (string) ($in['date'] ?? '');
        $startH   = (int) ($in['start_hour'] ?? -1);
        $duration = (int) ($in['duration_hours'] ?? 0);

        // 동적 장비 입력: equipment = [{id, qty}, ...]
        $equipmentIn = isset($in['equipment']) && is_array($in['equipment']) ? $in['equipment'] : [];
        // 호환: rent_racket / rent_shuttle (구 입력)
        if (!empty($in['rent_racket']) || !empty($in['rent_shuttle'])) {
            $legacy = [];
            if (!empty($in['rent_racket'])) {
                $rk = \App\Core\Db::fetch('SELECT id FROM equipment_options WHERE venue_id = ? AND type = "racket" AND status = "active" LIMIT 1', [$venueId]);
                if ($rk) $legacy[] = ['id' => (int) $rk['id'], 'qty' => 1];
            }
            if (!empty($in['rent_shuttle'])) {
                $sh = \App\Core\Db::fetch('SELECT id FROM equipment_options WHERE venue_id = ? AND type = "shuttle" AND status = "active" LIMIT 1', [$venueId]);
                if ($sh) $legacy[] = ['id' => (int) $sh['id'], 'qty' => 1];
            }
            if (!$equipmentIn) $equipmentIn = $legacy;
        }

        if ($venueId <= 0)        throw new \RuntimeException('venue_id가 필요합니다');
        if (!$courtIds)           throw new \RuntimeException('court_id가 필요합니다');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) throw new \RuntimeException('date(YYYY-MM-DD)가 잘못되었습니다');
        if ($startH < 0 || $startH > 23) throw new \RuntimeException('start_hour는 0~23');
        if (!in_array($duration, [1, 2, 3], true)) throw new \RuntimeException('duration_hours는 1/2/3 중 하나');

        $venue = Db::fetch('SELECT * FROM venues WHERE id = ? AND status = "active"', [$venueId]);
        if (!$venue) throw new \RuntimeException('구장을 찾을 수 없습니다');

        $courts = Db::fetchAll(
            'SELECT id, COALESCE(price_override, ?) AS price FROM courts WHERE id IN (' . implode(',', array_fill(0, count($courtIds), '?')) . ') AND venue_id = ? AND status = "active"',
            array_merge([(int) $venue['price_per_hour']], $courtIds, [$venueId])
        );
        if (count($courts) !== count($courtIds)) throw new \RuntimeException('코트를 찾을 수 없습니다');

        if ($date < date('Y-m-d')) throw new \RuntimeException('과거 날짜는 예약할 수 없습니다');

        // 슬롯 단위·운영시간 검증 (M-2)
        $slotData = SlotService::forDate($venueId, $date);
        if (!empty($slotData['is_closed']))                      throw new \RuntimeException('휴무일입니다');
        if ((int) $slotData['slot_unit'] !== $duration)          throw new \RuntimeException('이 날 슬롯 단위는 ' . $slotData['slot_unit'] . '시간입니다');
        $hourMatch = false;
        foreach ($slotData['slots'] as $s) if ((int) $s['hour'] === $startH) { $hourMatch = true; break; }
        if (!$hourMatch) throw new \RuntimeException('운영 시간 외이거나 잘못된 시작 시각');

        $endH = $startH + $duration;
        // 충돌 체크는 트랜잭션 내부 + 코트 행 잠금 (C-2 race condition 방지)

        // 가격 계산 (모든 코트 합산)
        $basePerHourSum    = 0;
        foreach ($courts as $c) $basePerHourSum += (int) $c['price'];
        $basePriceOriginal = $basePerHourSum * $duration;

        // 다이나믹 프라이싱 (모든 코트에 동일 적용 — 가장 높은 할인 적용)
        $dpDiscount = 0;
        $dpId       = null;
        foreach ($courtIds as $cid) {
            $dp = Db::fetch(
                'SELECT id, discount_pct FROM dynamic_pricing
                 WHERE venue_id = ? AND (court_id = ? OR court_id IS NULL)
                   AND target_date = ? AND target_start_hour <= ? AND target_end_hour >= ?
                   AND status = "active"
                 ORDER BY discount_pct DESC LIMIT 1',
                [$venueId, $cid, $date, $startH, $endH]
            );
            if ($dp && (int) $dp['discount_pct'] > $dpDiscount) {
                $dpDiscount = (int) $dp['discount_pct'];
                $dpId       = (int) $dp['id'];
            }
        }
        $basePrice = (int) round($basePriceOriginal * (1 - $dpDiscount / 100));

        // 장비 (동적: equipment[] = [{id, qty}])
        $extras    = 0;
        $equipment = [];
        foreach ($equipmentIn as $row) {
            $eqId = (int) ($row['id'] ?? 0);
            $qty  = max(1, (int) ($row['qty'] ?? 1));
            if ($eqId <= 0) continue;
            $opt = Db::fetch('SELECT id, price, max_qty FROM equipment_options WHERE id = ? AND venue_id = ? AND status = "active"', [$eqId, $venueId]);
            if (!$opt) continue;
            $qty = min($qty, (int) $opt['max_qty']);
            $price = (int) $opt['price'];
            $extras += $price * $qty;
            $equipment[] = ['id' => (int) $opt['id'], 'qty' => $qty, 'price' => $price];
        }
        $total = $basePrice + $extras;

        $u = Db::fetch('SELECT name, depositor_name FROM users WHERE id = ?', [$userId]);
        $depositor = $u['depositor_name'] ?: $u['name'];

        $dueHours    = (int) ($venue['deposit_due_hours'] ?? 24);
        $depositDue  = date('Y-m-d H:i:s', time() + $dueHours * 3600);

        // 멀티코트면 bulk_group 부여, 가격은 코트당 분배
        $isBulk = count($courtIds) > 1;
        $bulkGroup = $isBulk ? 'BULK-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6)) : null;

        // 코트별 가격 비율 (코트 가격 / 합산 가격)
        $courtPriceMap = [];
        foreach ($courts as $c) $courtPriceMap[(int)$c['id']] = (int) $c['price'];

        return Db::transaction(function () use (
            $userId, $venueId, $courtIds, $courtPriceMap, $date, $startH, $duration, $endH,
            $basePriceOriginal, $dpDiscount, $extras, $total,
            $depositor, $depositDue, $dpId, $equipment, $isBulk, $bulkGroup
        ) {
            // 코트 행 비관적 잠금 — 동시 INSERT 방지 (C-2)
            foreach ($courtIds as $cid) {
                Db::query('SELECT id FROM courts WHERE id = ? FOR UPDATE', [$cid]);
            }
            // 잠금 안에서 충돌 재확인
            foreach ($courtIds as $cid) {
                $conflict = Db::fetch(
                    'SELECT id FROM reservations
                     WHERE court_id = ? AND reservation_date = ?
                       AND status IN ("pending", "confirmed")
                       AND start_hour < ? AND (start_hour + duration_hours) > ?
                     LIMIT 1',
                    [$cid, $date, $endH, $startH]
                );
                if ($conflict) throw new \RuntimeException('이미 예약된 시간입니다 (코트 ' . $cid . ')');
            }

            $ids = [];
            $firstCode = null;
            foreach ($courtIds as $cid) {
                $courtBase = $courtPriceMap[$cid] * $duration;
                $courtPrice = (int) round($courtBase * (1 - $dpDiscount / 100));
                // 장비/총액은 첫 코트에만 부착 (단순화). 가격 표시는 분리.
                $isFirst = !$ids;
                $thisExtras = $isFirst ? $extras : 0;
                $thisTotal  = $courtPrice + $thisExtras;

                $code = 'CMAP-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
                if (!$firstCode) $firstCode = $code;

                $rid = Db::insert('reservations', [
                    'code'                => $code,
                    'user_id'             => $userId,
                    'venue_id'            => $venueId,
                    'court_id'            => $cid,
                    'reservation_date'    => $date,
                    'start_hour'          => $startH,
                    'duration_hours'      => $duration,
                    'base_price'          => $courtPrice,
                    'base_price_original' => $courtPriceMap[$cid] * $duration,
                    'discount_pct'        => $dpDiscount,
                    'extras_price'        => $thisExtras,
                    'total_price'         => $thisTotal,
                    'depositor_name'      => $depositor,
                    'deposit_due_at'      => $depositDue,
                    'status'              => 'pending',
                    'dynamic_pricing_id'  => $dpId,
                    'bulk_group'          => $bulkGroup,
                ]);
                if ($isFirst) {
                    foreach ($equipment as $e) {
                        Db::insert('reservation_equipment', [
                            'reservation_id' => $rid,
                            'equipment_id'   => $e['id'],
                            'quantity'       => $e['qty'],
                            'price_at_time'  => $e['price'],
                        ]);
                    }
                }
                $ids[] = $rid;
            }
            return ['id' => $ids[0], 'code' => $firstCode, 'ids' => $ids, 'group' => $bulkGroup];
        });
    }

    /**
     * 예약 + 구장·코트·은행 정보 조인.
     */
    public static function findByCode(string $code, ?int $userId = null): ?array
    {
        $sql = 'SELECT r.*, v.name AS venue_name, v.area AS venue_area,
                       v.bank_name, v.bank_account, v.bank_holder,
                       v.refund_24h_pct, v.refund_1h_pct, v.refund_lt1h_pct,
                       (SELECT url FROM venue_photos WHERE venue_id = v.id AND is_main = 1 LIMIT 1) AS img,
                       c.name AS court_name
                FROM reservations r
                JOIN venues v ON v.id = r.venue_id
                JOIN courts c ON c.id = r.court_id
                WHERE r.code = ?';
        $params = [$code];
        if ($userId !== null) {
            $sql .= ' AND r.user_id = ?';
            $params[] = $userId;
        }
        return Db::fetch($sql, $params);
    }

    /**
     * 사용자 예약 목록 (다가오는·지난).
     */
    public static function listForUser(int $userId): array
    {
        return Db::fetchAll(
            'SELECT r.*, v.name AS venue_name, v.area AS venue_area,
                    (SELECT url FROM venue_photos WHERE venue_id = v.id AND is_main = 1 LIMIT 1) AS img,
                    c.name AS court_name
             FROM reservations r
             JOIN venues v ON v.id = r.venue_id
             JOIN courts c ON c.id = r.court_id
             WHERE r.user_id = ?
             ORDER BY r.reservation_date DESC, r.start_hour DESC',
            [$userId]
        );
    }

    /**
     * U5/U6 React 컴포넌트가 기대하는 booking 객체로 변환.
     */
    public static function toBookingShape(array $r): array
    {
        $kdays = ['일','월','화','수','목','금','토'];
        $d = new \DateTime($r['reservation_date']);
        return [
            'code'           => $r['code'],
            'status'         => $r['status'],
            'venue' => [
                'id'   => (int) $r['venue_id'],
                'name' => $r['venue_name'],
                'area' => $r['venue_area'],
                'img'  => $r['img'],
            ],
            'day' => [
                'day'       => (int) $d->format('j'),
                'dow'       => $kdays[(int) $d->format('w')],
                'date'      => $d->format('Y-m-d'),
                'isHoliday' => false,
            ],
            'hour'           => (int) $r['start_hour'],
            'duration'       => (int) $r['duration_hours'],
            'court'          => self::courtIdxFromName((string) $r['court_name']),
            'court_name'     => $r['court_name'],
            'rentRacket'     => false,
            'rentShuttle'    => false,
            'total'          => (int) $r['total_price'],
            'basePrice'      => (int) $r['base_price'],
            'extrasPrice'    => (int) $r['extras_price'],
            'discountPct'    => (int) $r['discount_pct'],
            'depositor_name' => $r['depositor_name'],
            'bank' => [
                'name'    => $r['bank_name'],
                'account' => $r['bank_account'],
                'holder'  => $r['bank_holder'],
            ],
            'deposit_due_at' => $r['deposit_due_at'],
            'refundPolicy' => [
                'p24' => (int) $r['refund_24h_pct'],
                'p1'  => (int) $r['refund_1h_pct'],
                'lt1' => (int) $r['refund_lt1h_pct'],
            ],
            'startsAt' => strtotime($r['reservation_date'] . ' ' . sprintf('%02d:00:00', (int) $r['start_hour'])),
        ];
    }

    private static function courtIdxFromName(string $name): int
    {
        if ($name === '') return 1;
        $first = $name[0];
        if ($first >= 'A' && $first <= 'D') {
            return ord($first) - ord('A') + 1;
        }
        return 1;
    }
}
