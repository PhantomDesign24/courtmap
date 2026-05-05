<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Db;

final class VenueQueries
{
    /**
     * 카드 리스트용 venue 데이터 (React VENUES 와 동일 shape).
     */
    public static function listForCards(?string $q = null): array
    {
        $sql = 'SELECT v.id, v.name, v.area, v.lat, v.lng,
                       v.price_per_hour AS price, v.rating_avg AS rating, v.review_count AS reviews,
                       (SELECT COUNT(*) FROM courts c WHERE c.venue_id = v.id AND c.status = "active") AS courts,
                       (SELECT url FROM venue_photos p WHERE p.venue_id = v.id AND p.is_main = 1 LIMIT 1) AS img
                FROM venues v WHERE v.status = "active"';
        $params = [];
        if ($q) {
            $sql .= ' AND (v.name LIKE ? OR v.area LIKE ?)';
            $params[] = "%$q%";
            $params[] = "%$q%";
        }
        $sql .= ' ORDER BY v.id ASC';
        $venues = Db::fetchAll($sql, $params);

        // tags bulk fetch
        $ids = array_map(static fn($v) => (int) $v['id'], $venues);
        $tagsByVenue = [];
        if ($ids) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $rows = Db::fetchAll(
                "SELECT vft.venue_id, ft.name FROM venue_facility_tags vft
                 JOIN facility_tags ft ON ft.id = vft.tag_id
                 WHERE vft.venue_id IN ($in) ORDER BY ft.sort_order",
                $ids
            );
            foreach ($rows as $r) {
                $tagsByVenue[(int) $r['venue_id']][] = $r['name'];
            }
        }

        // React VENUES shape — 일부 필드는 추후 동적 계산 (TODO)
        return array_values(array_map(static function ($v, $i) use ($tagsByVenue) {
            $vid = (int) $v['id'];
            $distanceKm = round(0.7 + $i * 0.5, 1);
            return [
                'id'         => $vid,                          // numeric DB id
                'name'       => $v['name'],
                'area'       => $v['area'],
                'price'      => (int) $v['price'],
                'distanceKm' => $distanceKm,                   // TODO: GPS 받아서 계산
                'walkMin'    => (int) round($distanceKm * 12), // TODO
                'rating'     => (float) ($v['rating'] ?: 4.5),
                'reviews'    => (int) $v['reviews'],
                'courts'     => (int) $v['courts'],
                'tags'       => $tagsByVenue[$vid] ?? [],
                'img'        => $v['img'] ?: 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=600&q=70',
                'nextSlot'   => '오늘 19:00',                  // TODO: 슬롯 엔진 붙이면 실제 계산
                'hot'        => false,                          // TODO: dynamic_pricing 연동
            ];
        }, $venues, array_keys($venues)));
    }

    public static function findById(int $id): ?array
    {
        return Db::fetch('SELECT * FROM venues WHERE id = ? AND status = ?', [$id, 'active']);
    }
}
