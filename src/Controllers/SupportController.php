<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class SupportController extends Controller
{
    public function index(): void
    {
        $this->view('app', [
            'title'       => '고객센터 — 코트맵',
            'description' => '코트맵 고객센터 — FAQ, 공지사항, 약관, 개인정보 처리방침.',
            'screen'      => 'support',
            'data'        => [
                'page' => 'index',
                'faqs' => self::faqs(),
                'notices' => self::notices(),
            ],
        ], layout: null);
    }

    public function terms(): void
    {
        $this->view('app', [
            'title'  => '이용약관 — 코트맵',
            'screen' => 'support',
            'data'   => ['page' => 'terms'],
        ], layout: null);
    }

    public function privacy(): void
    {
        $this->view('app', [
            'title'  => '개인정보처리방침 — 코트맵',
            'screen' => 'support',
            'data'   => ['page' => 'privacy'],
        ], layout: null);
    }

    private static function faqs(): array
    {
        return [
            ['q' => '예약 시 결제는 어떻게 하나요?',
             'a' => '현재 무통장입금만 지원합니다. 예약 신청 후 24시간 내 입금하시면 운영자가 확인 후 확정 처리합니다.'],
            ['q' => '예약 취소·환불은 어떻게 되나요?',
             'a' => '이용 24시간 전 취소 시 100%, 1시간 전까지 50%, 1시간 이내 환불 불가입니다 (구장별 정책에 따라 다를 수 있음). 환불은 가입 시 등록한 본인 계좌로 송금됩니다.'],
            ['q' => '신뢰점수는 어떻게 결정되나요?',
             'a' => '가입 시 100점에서 시작합니다. 노쇼(미입장) 시 −15점, 정상 이용 시 +1점(월 최대 +5)이 부여됩니다. 60점 미만이면 입금 기한 단축, 40점 미만이면 7일/30일 예약 제한이 적용됩니다.'],
            ['q' => '단골 구장은 무엇인가요?',
             'a' => '구장 상세 화면에서 ♥ 를 누르면 단골로 등록됩니다. 단골 구장에 빈자리가 나면 자동으로 알림을 보내드립니다.'],
            ['q' => '여러 코트를 한 번에 예약할 수 있나요?',
             'a' => '네, 예약 시트의 "코트 선택"에서 여러 코트를 동시에 선택하면 같은 시간대에 함께 예약됩니다 (동호회 단체 예약).'],
            ['q' => '공휴일은 슬롯 단위가 다른가요?',
             'a' => '구장에 따라 다릅니다. 운영자가 공휴일/특정 요일/특정 날짜에 대해 슬롯 단위(1H/2H/3H)를 별도로 설정할 수 있습니다.'],
            ['q' => '구장 운영자로 가입하려면?',
             'a' => '회원가입 화면에서 "구장 운영자"를 선택하시면 됩니다. 가입 후 구장을 등록 신청하면 관리자 승인 후 노출됩니다.'],
        ];
    }

    private static function notices(): array
    {
        return [
            ['date' => '2026-05-05', 'title' => '코트맵 베타 오픈', 'body' => '안녕하세요, 코트맵이 정식 오픈했습니다. 강남 일대 6개 구장으로 시작하며 점차 확대할 예정입니다.'],
            ['date' => '2026-05-04', 'title' => '결제 수단 안내', 'body' => '현재는 무통장입금만 지원합니다. 카드 결제는 v2에서 도입 예정입니다.'],
        ];
    }
}
