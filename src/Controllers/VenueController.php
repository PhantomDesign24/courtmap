<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Db;
use App\Core\Response;
use App\Services\VenueQueries;

final class VenueController extends Controller
{
    public function index(): void
    {
        $q   = trim((string) ($_GET['q']   ?? ''));
        $tag = trim((string) ($_GET['tag'] ?? ''));
        $max = (int) ($_GET['max_price'] ?? 0);

        if ($q || $tag || $max) {
            // 통합 검색 API 와 동일한 필터 사용
            $sql = 'SELECT v.id FROM venues v ';
            $where = ['v.status = "active"'];
            $params = [];
            if ($tag) {
                $sql .= 'JOIN venue_facility_tags vft ON vft.venue_id = v.id JOIN facility_tags ft ON ft.id = vft.tag_id AND ft.code = ? ';
                array_unshift($params, $tag);
            }
            if ($q) {
                $where[] = '(v.name LIKE ? OR v.area LIKE ?)';
                array_push($params, "%$q%", "%$q%");
            }
            if ($max) {
                $where[] = 'v.price_per_hour <= ?';
                $params[] = $max;
            }
            $sql .= 'WHERE ' . implode(' AND ', $where) . ' ORDER BY v.id ASC';
            $okIds = array_map(static fn($r) => (int) $r['id'], Db::fetchAll($sql, $params));
            $venues = array_values(array_filter(VenueQueries::listForCards(), static fn($v) => in_array($v['id'], $okIds, true)));
        } else {
            $venues = VenueQueries::listForCards();
        }

        $this->view('app', [
            'title'  => '구장 찾기 — 코트맵',
            'screen' => 'venues_list',
            'data'   => [
                'venues'   => $venues,
                'location' => $_SESSION['user_area'] ?? '강남구 역삼동',
            ],
        ], layout: null);
    }

    public function show(string $id): void
    {
        $id = (int) $id;
        $v  = VenueQueries::findById($id);
        if (!$v) Response::notFound('구장을 찾을 수 없습니다.');

        $venues = VenueQueries::listForCards();
        $courts = Db::fetchAll(
            'SELECT id, name, sort_order FROM courts WHERE venue_id = ? AND status = "active" ORDER BY sort_order, id',
            [$id]
        );

        $hours  = Db::fetchAll('SELECT day_of_week, open_time, close_time, is_closed FROM venue_hours WHERE venue_id = ? ORDER BY day_of_week', [$id]);
        $venueTags = Db::fetchAll('SELECT ft.name FROM venue_facility_tags vft JOIN facility_tags ft ON ft.id = vft.tag_id WHERE vft.venue_id = ? ORDER BY ft.sort_order', [$id]);
        $photos = Db::fetchAll('SELECT url FROM venue_photos WHERE venue_id = ? ORDER BY is_main DESC, sort_order', [$id]);
        $coachesRaw = Db::fetchAll('SELECT id, name, career, price_per_lesson, duration_min, img_url FROM coaches WHERE venue_id = ? AND status = "active" ORDER BY sort_order, id', [$id]);
        $coaches = array_map(static fn($c) => [
            'id'           => (int) $c['id'],
            'name'         => $c['name'],
            'career'       => $c['career'] ?: '',
            'price'        => (int) $c['price_per_lesson'],
            'duration_min' => (int) $c['duration_min'],
            'img'          => $c['img_url'] ?: 'https://images.unsplash.com/photo-1599566150163-29194dcaad36?w=200&q=70',
        ], $coachesRaw);

        $isFav = false;
        if ($u = Auth::user()) {
            $isFav = (bool) Db::fetch('SELECT user_id FROM favorites WHERE user_id = ? AND venue_id = ?', [$u['id'], $id]);
        }

        $tags = Db::fetchAll(
            'SELECT ft.name FROM venue_facility_tags vft
             JOIN facility_tags ft ON ft.id = vft.tag_id
             WHERE vft.venue_id = ? ORDER BY ft.sort_order',
            [$id]
        );
        $tagNames = array_map(static fn($t) => $t['name'], $tags);

        $jsonld = [
            '@context'    => 'https://schema.org',
            '@type'       => 'SportsActivityLocation',
            'name'        => $v['name'],
            'description' => $v['description'] ?? ($v['name'] . ' — 코트맵 등록 배드민턴 구장.'),
            'address'     => [
                '@type'           => 'PostalAddress',
                'streetAddress'   => $v['address'],
                'addressLocality' => $v['area'],
                'addressCountry'  => 'KR',
            ],
            'geo' => [
                '@type'    => 'GeoCoordinates',
                'latitude' => (float) $v['lat'],
                'longitude'=> (float) $v['lng'],
            ],
            'telephone'   => $v['phone'],
            'priceRange'  => '₩' . number_format((int) $v['price_per_hour']),
            'amenityFeature' => array_map(static fn($t) => [
                '@type' => 'LocationFeatureSpecification',
                'name'  => $t,
                'value' => true,
            ], $tagNames),
            'url' => 'https://bad.mvc.kr/venues/' . $id,
        ];

        $mainImg = Db::fetch('SELECT url FROM venue_photos WHERE venue_id = ? AND is_main = 1', [$id]);

        $this->view('app', [
            'title'       => $v['name'] . ' — 코트맵',
            'description' => $v['name'] . ' · ' . $v['area'] . ' · 코트 ' . count($courts) . '면 · ' . number_format((int) $v['price_per_hour']) . '원/시간 · 실시간 예약',
            'og_image'    => $mainImg['url'] ?? null,
            'og_type'     => 'place',
            'jsonld'      => $jsonld,
            'screen'      => 'venue_detail',
            'data'        => [
                'venues'  => $venues,
                'venueId' => $id,
                'isFav'   => $isFav,
                'courts'  => array_map(static fn($c) => [
                    'id'   => (int) $c['id'],
                    'name' => $c['name'],
                ], $courts),
                'venueDetail' => [
                    'address'     => $v['address'],
                    'phone'       => $v['phone'],
                    'description' => $v['description'] ?? '',
                    'lat'         => (float) $v['lat'],
                    'lng'         => (float) $v['lng'],
                    'hours'       => array_map(static fn($h) => [
                        'dow'   => (int) $h['day_of_week'],
                        'open'  => substr($h['open_time'], 0, 5),
                        'close' => substr($h['close_time'], 0, 5),
                        'closed'=> (bool) $h['is_closed'],
                    ], $hours),
                    'tags'        => array_map(static fn($t) => $t['name'], $venueTags),
                    'photos'      => array_map(static fn($p) => $p['url'], $photos),
                    'coaches'     => $coaches,
                ],
            ],
        ], layout: null);
    }
}
