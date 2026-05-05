<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ReservationService;
use App\Services\VenueQueries;

final class MeReservationsController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();
        $rows = ReservationService::listForUser((int) $user['id']);

        $today = date('Y-m-d');
        $upcoming = [];
        $past     = [];

        foreach ($rows as $r) {
            $shape = self::toU7Shape($r, $today);
            if (in_array($r['status'], ['done', 'noshow', 'canceled', 'expired'], true)) {
                $past[] = $shape;
            } elseif ($r['reservation_date'] >= $today) {
                $upcoming[] = $shape;
            } else {
                $past[] = $shape;
            }
        }

        $this->view('app', [
            'title'   => '내 예약 — 코트맵',
            'noindex' => true,
            'screen'  => 'my_reservations',
            'data'   => [
                'venues'   => VenueQueries::listForCards(),
                'upcoming' => $upcoming,
                'past'     => $past,
            ],
        ], layout: null);
    }

    private static function toU7Shape(array $r, string $today): array
    {
        $kdays    = ['일','월','화','수','목','금','토'];
        $d        = new \DateTime($r['reservation_date']);
        $startH   = (int) $r['start_hour'];
        $duration = (int) $r['duration_hours'];

        $diffDays = (int) ((strtotime($r['reservation_date']) - strtotime($today)) / 86400);
        $inDays = match (true) {
            $diffDays === 0  => '오늘',
            $diffDays === 1  => '내일',
            $diffDays === 2  => '이틀 후',
            $diffDays > 0    => $diffDays . '일 후',
            $diffDays === -1 => '어제',
            default          => abs($diffDays) . '일 전',
        };

        return [
            'id'     => 'r' . $r['id'],
            'code'   => $r['code'],
            'status' => $r['status'],
            'venue'  => [
                'id'   => (int) $r['venue_id'],
                'name' => $r['venue_name'],
                'area' => $r['venue_area'],
                'img'  => $r['img'],
            ],
            'day'   => sprintf('%d월 %d일 (%s)', (int) $d->format('n'), (int) $d->format('j'), $kdays[(int) $d->format('w')]),
            'time'  => sprintf('%02d:00 ~ %02d:00', $startH, $startH + $duration),
            'court' => $r['court_name'],
            'price' => (int) $r['total_price'],
            'inDays'    => $inDays,
            'recurring' => !empty($r['recurring_group_id']),
        ];
    }
}
