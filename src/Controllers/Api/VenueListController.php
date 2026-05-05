<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Response;
use App\Services\VenueQueries;

final class VenueListController extends Controller
{
    /**
     * 통합 검색·필터 API.
     * params: q, area, range(now|1h|2h|today|weekend), tag, max_price, sort
     */
    public function index(): void
    {
        $q     = trim((string) ($_GET['q']    ?? ''));
        $area  = trim((string) ($_GET['area'] ?? ''));
        $range = (string) ($_GET['range']     ?? '');
        $tag   = trim((string) ($_GET['tag']  ?? ''));
        $maxPrice = (int) ($_GET['max_price'] ?? 0);
        $sort  = (string) ($_GET['sort']      ?? 'distance');

        $where  = ['v.status = "active"'];
        $params = [];
        if ($q !== '') {
            $where[] = '(v.name LIKE ? OR v.area LIKE ? OR v.address LIKE ?)';
            array_push($params, "%$q%", "%$q%", "%$q%");
        }
        if ($area !== '') {
            $where[] = 'v.area LIKE ?';
            $params[] = "%$area%";
        }
        if ($maxPrice > 0) {
            $where[] = 'v.price_per_hour <= ?';
            $params[] = $maxPrice;
        }
        $tagJoin = '';
        if ($tag !== '') {
            $tagJoin = 'JOIN venue_facility_tags vft ON vft.venue_id = v.id JOIN facility_tags ft ON ft.id = vft.tag_id AND ft.code = ?';
            array_unshift($params, $tag);
        }

        $orderBy = match ($sort) {
            'price'  => 'v.price_per_hour ASC',
            'rating' => 'v.rating_avg DESC',
            default  => 'v.id ASC',
        };

        $sql = "SELECT v.id FROM venues v $tagJoin WHERE " . implode(' AND ', $where) . " ORDER BY $orderBy LIMIT 50";
        $ids = array_map(static fn($r) => (int) $r['id'], Db::fetchAll($sql, $params));

        // 시간 윈도우 필터 — 빈 슬롯 있는 venue 만 (간이)
        if ($range !== '' && $ids) {
            [$dateFrom, $hourFrom, $dateTo, $hourTo] = self::resolveRange($range);
            $idStr = implode(',', $ids);
            // 해당 날짜·시간 윈도우에 활성 예약 없는 코트가 1개라도 있는 venue 만 통과
            $busy = Db::fetchAll(
                "SELECT venue_id, court_id, reservation_date, start_hour, duration_hours
                 FROM reservations
                 WHERE venue_id IN ($idStr)
                   AND status IN ('pending','confirmed')
                   AND reservation_date BETWEEN ? AND ?",
                [$dateFrom, $dateTo]
            );
            $venueCourts = [];
            foreach (Db::fetchAll("SELECT venue_id, id FROM courts WHERE venue_id IN ($idStr) AND status = 'active'") as $c) {
                $venueCourts[(int) $c['venue_id']][] = (int) $c['id'];
            }
            $blocked = [];
            foreach ($busy as $b) {
                for ($h = (int)$b['start_hour']; $h < (int)$b['start_hour'] + (int)$b['duration_hours']; $h++) {
                    $blocked[(int)$b['venue_id']][(int)$b['court_id']][$b['reservation_date']][$h] = true;
                }
            }
            $okIds = [];
            foreach ($ids as $vid) {
                $courts = $venueCourts[$vid] ?? [];
                $hasFree = false;
                foreach ($courts as $cid) {
                    if (self::hasFreeSlot($blocked[$vid][$cid] ?? [], $dateFrom, $hourFrom, $dateTo, $hourTo)) {
                        $hasFree = true; break;
                    }
                }
                if ($hasFree) $okIds[] = $vid;
            }
            $ids = $okIds;
        }

        // shape 변환
        $venues = $ids ? VenueQueries::listForCards() : [];
        $venues = array_values(array_filter($venues, static fn($v) => in_array($v['id'], $ids, true)));

        Response::json(['venues' => $venues, 'count' => count($venues)]);
    }

    /** @return array{0:string,1:int,2:string,3:int} dateFrom, hourFrom, dateTo, hourTo */
    private static function resolveRange(string $range): array
    {
        $now = new \DateTime();
        $today = $now->format('Y-m-d');
        $h = (int) $now->format('G');
        switch ($range) {
            case 'now':     return [$today, $h, $today, min(24, $h + 2)];
            case '1h':      return [$today, $h, $today, min(24, $h + 1)];
            case '2h':      return [$today, $h, $today, min(24, $h + 2)];
            case 'today':   return [$today, 18, $today, 22];
            case 'weekend':
                $sat = (clone $now)->modify('next saturday')->format('Y-m-d');
                $sun = (clone $now)->modify('next sunday')->format('Y-m-d');
                return [$sat, 9, $sun, 22];
        }
        return [$today, 0, $today, 24];
    }

    private static function hasFreeSlot(array $courtBlock, string $dateFrom, int $hFrom, string $dateTo, int $hTo): bool
    {
        $cur = new \DateTime($dateFrom);
        $end = new \DateTime($dateTo);
        while ($cur <= $end) {
            $d = $cur->format('Y-m-d');
            $startH = ($d === $dateFrom) ? $hFrom : 10;
            $endH   = ($d === $dateTo)   ? $hTo   : 22;
            for ($h = $startH; $h < $endH; $h++) {
                if (empty($courtBlock[$d][$h])) return true;
            }
            $cur->modify('+1 day');
        }
        return false;
    }
}
