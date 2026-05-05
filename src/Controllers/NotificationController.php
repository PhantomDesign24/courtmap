<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Response;
use App\Services\VenueQueries;

final class NotificationController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();
        $rows = Db::fetchAll(
            'SELECT id, type, title, body, link_url, related_type, related_id, is_read, created_at
             FROM notifications
             WHERE user_id = ? AND created_at >= NOW() - INTERVAL 7 DAY
             ORDER BY created_at DESC LIMIT 50',
            [$user['id']]
        );

        // 읽음 처리 (조회 시점)
        Db::query('UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0', [$user['id']]);

        // React NotificationList 가 기대하는 shape 으로 변환
        $items = array_map(static function ($r) {
            $iconMap = [
                'alert' => 'bell', 'confirm' => 'check', 'remind' => 'clock',
                'deal' => 'bolt', 'review' => 'star', 'system' => 'bell',
            ];
            return [
                'id'     => (int) $r['id'],
                'type'   => $r['type'],
                'icon'   => $iconMap[$r['type']] ?? 'bell',
                'title'  => $r['title'],
                'sub'    => $r['body'] ?? '',
                'time'   => self::relTime((string) $r['created_at']),
                'unread' => false, // 방금 읽음 처리됨
                'vId'    => $r['related_type'] === 'venue' ? (int) $r['related_id'] : null,
            ];
        }, $rows);

        $this->view('app', [
            'title'  => '알림 — 코트맵',
            'noindex' => true,
            'screen'  => 'notifications',
            'data'   => [
                'venues'        => VenueQueries::listForCards(),
                'notifications' => $items,
            ],
        ], layout: null);
    }

    public function search(): void
    {
        $this->requireAuth();
        $this->view('app', [
            'title'  => '검색 — 코트맵',
            'screen' => 'search',
            'data'   => ['venues' => VenueQueries::listForCards()],
        ], layout: null);
    }

    private static function relTime(string $iso): string
    {
        $diff = time() - strtotime($iso);
        if ($diff < 60)     return '방금 전';
        if ($diff < 3600)   return floor($diff / 60) . '분 전';
        if ($diff < 86400)  return floor($diff / 3600) . '시간 전';
        if ($diff < 86400 * 2) return '어제';
        return floor($diff / 86400) . '일 전';
    }
}
