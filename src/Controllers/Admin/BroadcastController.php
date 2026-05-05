<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Db;

final class BroadcastController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth('admin');
        $recent = Db::fetchAll(
            'SELECT title, body, link_url, COUNT(*) AS recipients, MIN(created_at) AS sent_at
             FROM notifications
             WHERE type = "broadcast"
             GROUP BY title, body, link_url, DATE(created_at), HOUR(created_at), MINUTE(created_at)
             ORDER BY sent_at DESC LIMIT 30'
        );
        $stats = Db::fetch(
            'SELECT
               (SELECT COUNT(*) FROM users WHERE status = "active" AND role = "user")     AS users,
               (SELECT COUNT(*) FROM users WHERE status = "active" AND role = "operator") AS operators,
               (SELECT COUNT(*) FROM users WHERE status = "active")                       AS all_active'
        );
        $this->view('admin/broadcast', [
            'title'  => '공지·알림 발송 — 어드민',
            'user'   => $user,
            'recent' => $recent,
            'stats'  => $stats,
        ], layout: 'admin');
    }

    public function send(): void
    {
        $this->requireAuth('admin');
        $title = trim((string) ($_POST['title'] ?? ''));
        $body  = trim((string) ($_POST['body'] ?? ''));
        $link  = trim((string) ($_POST['link_url'] ?? ''));
        $audience = (string) ($_POST['audience'] ?? 'all');

        if ($title === '' || $body === '') $this->redirect('/admin/broadcast');

        $where = match ($audience) {
            'user'     => "role = 'user'",
            'operator' => "role = 'operator'",
            default    => "1=1",
        };
        $rows = Db::fetchAll("SELECT id FROM users WHERE status = 'active' AND $where");

        Db::transaction(function () use ($rows, $title, $body, $link) {
            foreach ($rows as $r) {
                Db::insert('notifications', [
                    'user_id'  => (int) $r['id'],
                    'type'     => 'broadcast',
                    'title'    => $title,
                    'body'     => $body,
                    'link_url' => $link !== '' ? $link : null,
                ]);
            }
        });
        $_SESSION['flash_ok'] = count($rows) . '명에게 발송됨';
        $this->redirect('/admin/broadcast');
    }
}
