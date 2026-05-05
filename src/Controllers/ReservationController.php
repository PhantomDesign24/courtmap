<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Services\ReservationService;
use App\Services\VenueQueries;

final class ReservationController extends Controller
{
    public function show(string $code): void
    {
        $user = $this->requireAuth();
        $r = ReservationService::findByCode($code, (int) $user['id']);
        if (!$r) Response::notFound('예약을 찾을 수 없습니다.');

        $booking = ReservationService::toBookingShape($r);

        // pending → U5 (입금 안내). ?paid=1 또는 confirmed → U6. canceled/done/noshow/expired → 상세.
        $screen = match (true) {
            $r['status'] === 'pending' && (($_GET['paid'] ?? '') !== '1') => 'reservation_deposit',
            in_array($r['status'], ['pending', 'confirmed'], true)        => 'reservation_complete',
            default                                                       => 'reservation_detail',
        };

        $this->view('app', [
            'title'   => '예약 — ' . $r['venue_name'],
            'noindex' => true,
            'screen'  => $screen,
            'data'   => [
                'booking' => $booking,
                'venues'  => VenueQueries::listForCards(),
            ],
        ], layout: null);
    }

    public function markPaid(string $code): void
    {
        $user = $this->requireAuth();
        $r = ReservationService::findByCode($code, (int) $user['id']);
        if (!$r) Response::notFound('예약을 찾을 수 없습니다.');
        // MVP: 사용자가 입금을 보고했음만 표시. 실제 status 변경은 운영자가 O12에서.
        $this->redirect('/reservations/' . $code . '?paid=1');
    }

    public function entry(string $code): void
    {
        $user = $this->requireAuth();
        $r = ReservationService::findByCode($code, (int) $user['id']);
        if (!$r) Response::notFound('예약을 찾을 수 없습니다.');

        $kdays = ['일','월','화','수','목','금','토'];
        $d = new \DateTime($r['reservation_date']);
        $startH = (int) $r['start_hour'];
        $duration = (int) $r['duration_hours'];

        $reservation = [
            'code'     => $r['code'],
            'name'     => $user['name'],
            'phone'    => $user['phone'],
            'venue'    => [
                'id'       => (int) $r['venue_id'],
                'name'     => $r['venue_name'],
                'area'     => $r['venue_area'],
                'img'      => $r['img'],
                'walkMin'  => 10,
            ],
            'day'      => sprintf('%d월 %d일 (%s)', (int) $d->format('n'), (int) $d->format('j'), $kdays[(int) $d->format('w')]),
            'time'     => sprintf('%02d:00 ~ %02d:00', $startH, $startH + $duration),
            'court'    => $r['court_name'],
        ];

        $this->view('app', [
            'title'  => '입장 안내 — 코트맵',
            'screen' => 'entry',
            'data'   => [
                'reservation' => $reservation,
                'venues'      => VenueQueries::listForCards(),
            ],
        ], layout: null);
    }
}
