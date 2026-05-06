<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Response;
use App\Services\SlotWatchService;

final class MeWatchController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth();
        $userId = (int) $user['id'];

        $active = Db::fetchAll(
            'SELECT w.*, v.name AS venue_name, c.name AS court_name
             FROM slot_watches w
             JOIN venues v ON v.id = w.venue_id
             LEFT JOIN courts c ON c.id = w.court_id
             WHERE w.user_id = ? AND w.status = "active"
             ORDER BY w.type, w.day_of_week, w.target_date, w.start_hour',
            [$userId]
        );
        $past = Db::fetchAll(
            'SELECT w.*, v.name AS venue_name, c.name AS court_name
             FROM slot_watches w
             JOIN venues v ON v.id = w.venue_id
             LEFT JOIN courts c ON c.id = w.court_id
             WHERE w.user_id = ? AND w.status != "active"
             ORDER BY w.id DESC LIMIT 30',
            [$userId]
        );
        $alerts = Db::fetchAll(
            'SELECT a.*, v.name AS venue_name, c.name AS court_name
             FROM slot_watch_alerts a
             JOIN slot_watches w ON w.id = a.watch_id
             JOIN venues v ON v.id = w.venue_id
             LEFT JOIN courts c ON c.id = a.slot_court_id
             WHERE w.user_id = ?
             ORDER BY a.sent_at DESC LIMIT 30',
            [$userId]
        );
        $venues = Db::fetchAll(
            'SELECT id, name FROM venues WHERE status = "active" ORDER BY name LIMIT 200'
        );
        $recommended = SlotWatchService::recommendTimes($userId, 5);

        $this->view('me_watches', [
            'title'       => '빈자리 알림 — 코트맵',
            'noindex'     => true,
            'user'        => $user,
            'active'      => $active,
            'past'        => $past,
            'alerts'      => $alerts,
            'venues'      => $venues,
            'recommended' => $recommended,
            'flashOk'     => $_SESSION['flash_ok']  ?? null,
            'flashErr'    => $_SESSION['flash_err'] ?? null,
        ]);
        unset($_SESSION['flash_ok'], $_SESSION['flash_err']);
    }

    public function create(): void
    {
        $user = $this->requireAuth();
        $userId = (int) $user['id'];

        $venueId   = (int) ($_POST['venue_id'] ?? 0);
        $courtId   = empty($_POST['court_id']) ? null : (int) $_POST['court_id'];
        $type      = (string) ($_POST['type'] ?? 'recurring');
        $startHour = (int) ($_POST['start_hour'] ?? 0);
        $endHour   = (int) ($_POST['end_hour'] ?? 0);
        $unit      = (int) ($_POST['slot_unit_hours'] ?? 1);
        $note      = trim((string) ($_POST['note'] ?? ''));

        if (!in_array($type, ['recurring','one_time'], true)) $this->redirect('/me/watches');
        if (!Db::fetch('SELECT id FROM venues WHERE id = ?', [$venueId])) {
            $_SESSION['flash_err'] = '구장을 선택해주세요.';
            $this->redirect('/me/watches');
        }
        if ($startHour < 0 || $startHour > 23 || $endHour <= $startHour || $endHour > 24) {
            $_SESSION['flash_err'] = '시간대가 올바르지 않습니다.';
            $this->redirect('/me/watches');
        }
        if (!in_array($unit, [1, 2, 3], true)) $unit = 1;
        if ($courtId !== null) {
            $c = Db::fetch('SELECT venue_id FROM courts WHERE id = ?', [$courtId]);
            if (!$c || (int) $c['venue_id'] !== $venueId) $courtId = null;
        }

        $data = [
            'user_id'         => $userId,
            'venue_id'        => $venueId,
            'court_id'        => $courtId,
            'type'            => $type,
            'start_hour'      => $startHour,
            'end_hour'        => $endHour,
            'slot_unit_hours' => $unit,
            'note'            => $note ?: null,
            'status'          => 'active',
        ];
        if ($type === 'recurring') {
            $data['day_of_week'] = (int) ($_POST['day_of_week'] ?? 0);
            if ($data['day_of_week'] < 0 || $data['day_of_week'] > 6) $data['day_of_week'] = 0;
            $expires = trim((string) ($_POST['expires_at'] ?? ''));
            if ($expires !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $expires)) {
                $data['expires_at'] = $expires . ' 23:59:59';
            }
        } else {
            $date = trim((string) ($_POST['target_date'] ?? ''));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) < strtotime('today')) {
                $_SESSION['flash_err'] = '날짜를 선택해주세요 (오늘 이후).';
                $this->redirect('/me/watches');
            }
            $data['target_date'] = $date;
        }
        Db::insert('slot_watches', $data);
        $_SESSION['flash_ok'] = '알림 등록 완료. 빈자리가 생기면 알려드릴게요.';
        $this->redirect('/me/watches');
    }

    public function delete(string $id): void
    {
        $user = $this->requireAuth();
        $w = Db::fetch('SELECT id, user_id FROM slot_watches WHERE id = ?', [(int) $id]);
        if (!$w) Response::notFound();
        if ((int) $w['user_id'] !== (int) $user['id']) Response::forbidden();
        Db::query('UPDATE slot_watches SET status = "canceled" WHERE id = ?', [(int) $w['id']]);
        $this->redirect('/me/watches');
    }
}
