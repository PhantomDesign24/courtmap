<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Db;

final class MeController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();
        $uid = (int) $user['id'];

        // 단골 구장
        $favorites = Db::fetchAll(
            'SELECT v.id, v.name, v.area,
                    (SELECT url FROM venue_photos WHERE venue_id = v.id AND is_main = 1 LIMIT 1) AS img
             FROM favorites f
             JOIN venues v ON v.id = f.venue_id
             WHERE f.user_id = ? AND v.status = "active"
             ORDER BY f.created_at DESC',
            [$uid]
        );

        // 보유 쿠폰
        $couponRows = Db::fetchAll(
            'SELECT uc.id, uc.expires_at, c.name, c.discount_type, c.discount_value, c.min_amount
             FROM user_coupons uc
             JOIN coupons c ON c.id = uc.coupon_id
             WHERE uc.user_id = ? AND uc.used_at IS NULL
               AND (uc.expires_at IS NULL OR uc.expires_at > NOW())
             ORDER BY uc.expires_at ASC',
            [$uid]
        );
        $coupons = array_map(static function ($c) {
            $isPct = $c['discount_type'] === 'percent';
            return [
                'amount_label' => $isPct ? (int) $c['discount_value'] : number_format((int) $c['discount_value']),
                'unit'         => $isPct ? '% 할인' : '원 할인',
                'title'        => $c['name'],
                'expires'      => $c['expires_at'] ? date('m월 d일까지', strtotime($c['expires_at'])) : '무기한',
                'min_amount'   => $c['min_amount'] > 0 ? number_format((int) $c['min_amount']) . '원 이상' : '',
            ];
        }, $couponRows);

        // 활성 멤버십
        $m = Db::fetch(
            'SELECT um.hours_remaining, um.expires_at, m.name, m.hours_total, v.name AS venue_name
             FROM user_memberships um
             JOIN memberships m ON m.id = um.membership_id
             JOIN venues v ON v.id = m.venue_id
             WHERE um.user_id = ? AND um.status = "active" AND um.expires_at > NOW()
             ORDER BY um.id DESC LIMIT 1',
            [$uid]
        );
        $membership = $m ? [
            'name'              => $m['name'],
            'venue_name'        => $m['venue_name'],
            'hours_remaining'   => (int) $m['hours_remaining'],
            'hours_total'       => (int) $m['hours_total'],
            'expires_at_short'  => date('n/j', strtotime($m['expires_at'])),
        ] : null;

        // 환불계좌 마스킹
        $userPayload = [
            'id'             => $uid,
            'name'           => $user['name'],
            'email'          => $user['email'],
            'phone'          => $user['phone'],
            'role'           => $user['role'],
            'trust_score'    => (int) $user['trust_score'],
            'refund_bank_name' => Db::fetch('SELECT refund_bank_name FROM users WHERE id = ?', [$uid])['refund_bank_name'] ?? '',
            'refund_bank_account_masked' => self::maskAccount(
                Db::fetch('SELECT refund_bank_account FROM users WHERE id = ?', [$uid])['refund_bank_account'] ?? ''
            ),
        ];

        $this->view('app', [
            'title'    => '마이 — 코트맵',
            'noindex'  => true,
            'screen'   => 'me',
            'data'   => [
                'favorites'  => $favorites,
                'coupons'    => $coupons,
                'membership' => $membership,
                'stats'      => [
                    'favorites_count'      => count($favorites),
                    'coupons_count'        => count($coupons),
                    'membership_remaining' => $membership['hours_remaining'] ?? 0,
                ],
                // ProfileScreen 가 window.__USER__ 도 사용
                'me_user'    => $userPayload,
            ],
        ], layout: null);
    }

    private static function maskAccount(string $acct): string
    {
        if ($acct === '') return '';
        $digits = preg_replace('/\D+/', '', $acct) ?? '';
        if ($digits === '') return '***';
        return '***-' . substr($digits, -4);
    }
}
