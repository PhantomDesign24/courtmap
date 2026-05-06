<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Db;

final class MeNotifySettingsController extends Controller
{
    public function form(): void
    {
        $user = $this->requireAuth();
        $u = Db::fetch(
            'SELECT name, email, phone, notify_reminder, notify_broadcast, notify_marketing
             FROM users WHERE id = ?',
            [(int) $user['id']]
        );
        $this->view('me_notify_settings', [
            'title'   => '알림 설정 — 코트맵',
            'noindex' => true,
            'u'       => $u,
            'flashOk' => $_SESSION['flash_ok'] ?? null,
        ]);
        unset($_SESSION['flash_ok']);
    }

    public function update(): void
    {
        $user = $this->requireAuth();
        Db::query(
            'UPDATE users SET notify_reminder = ?, notify_broadcast = ?, notify_marketing = ? WHERE id = ?',
            [
                empty($_POST['notify_reminder'])  ? 0 : 1,
                empty($_POST['notify_broadcast']) ? 0 : 1,
                empty($_POST['notify_marketing']) ? 0 : 1,
                (int) $user['id'],
            ]
        );
        $_SESSION['flash_ok'] = '알림 설정이 저장되었습니다.';
        $this->redirect('/me/notify-settings');
    }
}
