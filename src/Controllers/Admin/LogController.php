<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Db;

final class LogController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth('admin');

        $cronLocks = [
            '입금만료 expire'        => '/tmp/courtmap-expire.lock',
            '캘린더 sync calendar'    => '/tmp/courtmap-calendar.lock',
            '공휴일 holiday'          => '/tmp/courtmap-holiday.lock',
            '리마인더 reminder'       => '/tmp/courtmap-reminder.lock',
            '노쇼 mark mark-noshow'   => '/tmp/courtmap-mark-noshow.lock',
            '완료 mark mark-done'     => '/tmp/courtmap-mark-done.lock',
            '동적가격 dynamic-pricing'=> '/tmp/courtmap-dynamic.lock',
            '추천예약 recurring'      => '/tmp/courtmap-recurring.lock',
        ];
        $cronStatus = [];
        foreach ($cronLocks as $name => $path) {
            $cronStatus[$name] = is_file($path) ? @filemtime($path) : null;
        }

        $webhooks = Db::fetchAll(
            'SELECT w.*, v.name AS venue_name
             FROM webhooks w JOIN venues v ON v.id = w.venue_id
             ORDER BY (w.status = "failed") DESC, w.last_failure_at DESC, w.id DESC LIMIT 100'
        );

        $blockedLogins = Db::fetchAll(
            'SELECT * FROM login_attempts
             WHERE blocked_until IS NOT NULL AND blocked_until > NOW()
             ORDER BY blocked_until DESC LIMIT 50'
        );
        $recentLogins = Db::fetchAll(
            'SELECT * FROM login_attempts ORDER BY updated_at DESC LIMIT 50'
        );

        $dbStart = microtime(true);
        Db::fetch('SELECT 1');
        $dbMs = (int) round((microtime(true) - $dbStart) * 1000);

        $this->view('admin/logs', [
            'title'         => '시스템 로그 — 어드민',
            'user'          => $user,
            'cronStatus'    => $cronStatus,
            'webhooks'      => $webhooks,
            'blockedLogins' => $blockedLogins,
            'recentLogins'  => $recentLogins,
            'dbMs'          => $dbMs,
        ], layout: 'admin');
    }

    public function clearBlock(string $key): void
    {
        $this->requireAuth('admin');
        Db::query('UPDATE login_attempts SET blocked_until = NULL, fail_count = 0 WHERE attempt_key = ?', [$key]);
        $this->redirect('/admin/logs');
    }

    public function reactivateWebhook(string $id): void
    {
        $this->requireAuth('admin');
        Db::query('UPDATE webhooks SET status = "active", failure_count = 0 WHERE id = ?', [(int) $id]);
        $this->redirect('/admin/logs');
    }
}
