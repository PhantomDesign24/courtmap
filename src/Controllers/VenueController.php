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
        $venues = VenueQueries::listForCards();
        $this->view('app', [
            'title'  => '구장 찾기 — 코트맵',
            'screen' => 'venues_list',
            'data'   => [
                'venues'   => $venues,
                'location' => '강남구 역삼동',
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
            ],
        ], layout: null);
    }
}
