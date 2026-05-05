<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\VenueQueries;

final class HomeController extends Controller
{
    public function index(): void
    {
        $venues = VenueQueries::listForCards();
        $location = $_SESSION['user_area'] ?? '강남구 역삼동';
        $this->view('app', [
            'title'       => '코트맵 — 배드민턴 사설 구장 실시간 예약',
            'description' => '전국 사설 배드민턴 구장의 빈 코트를 실시간으로 보고 1시간 단위로 즉시 예약하세요. 코발트블루 톤의 깔끔한 부킹앱.',
            'screen'      => 'home',
            'data'        => [
                'venues'   => $venues,
                'location' => $location,
            ],
        ], layout: null);
    }
}
