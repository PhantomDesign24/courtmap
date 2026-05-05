<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Response;

final class VenueController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth('admin');
        $tab  = (string) ($_GET['tab'] ?? 'pending');
        $statusFilter = match ($tab) {
            'active'    => 'active',
            'suspended' => 'suspended',
            default     => 'pending',
        };
        $venues = Db::fetchAll(
            'SELECT v.*, u.name AS owner_name, u.email AS owner_email,
                    (SELECT COUNT(*) FROM courts WHERE venue_id = v.id) AS court_count
             FROM venues v
             JOIN users u ON u.id = v.owner_id
             WHERE v.status = ?
             ORDER BY v.created_at DESC',
            [$statusFilter]
        );
        $this->view('admin/venues', [
            'title'  => '구장 승인 — 어드민',
            'user'   => $user,
            'tab'    => $tab,
            'venues' => $venues,
        ], layout: 'admin');
    }

    public function approve(string $id): void
    {
        $user = $this->requireAuth('admin');
        $v = Db::fetch('SELECT id, owner_id, name FROM venues WHERE id = ?', [(int) $id]);
        if (!$v) Response::notFound();
        Db::query('UPDATE venues SET status = "active", updated_at = NOW() WHERE id = ?', [(int) $v['id']]);
        Db::insert('notifications', [
            'user_id' => (int) $v['owner_id'],
            'type'    => 'system',
            'title'   => '구장이 승인되었습니다',
            'body'    => $v['name'] . ' — 사용자에게 노출 시작',
            'link_url'=> '/operator/venues',
        ]);
        $this->redirect('/admin/venues?tab=pending');
    }

    public function reject(string $id): void
    {
        $user = $this->requireAuth('admin');
        $v = Db::fetch('SELECT id, owner_id, name FROM venues WHERE id = ?', [(int) $id]);
        if (!$v) Response::notFound();
        $reason = trim((string) ($_POST['reason'] ?? '관리자 반려'));
        Db::query('UPDATE venues SET status = "suspended", updated_at = NOW() WHERE id = ?', [(int) $v['id']]);
        Db::insert('notifications', [
            'user_id' => (int) $v['owner_id'],
            'type'    => 'system',
            'title'   => '구장 등록이 반려되었습니다',
            'body'    => $v['name'] . ' — ' . $reason,
            'link_url'=> '/operator/venues',
        ]);
        $this->redirect('/admin/venues?tab=pending');
    }

    public function reactivate(string $id): void
    {
        $user = $this->requireAuth('admin');
        $v = Db::fetch('SELECT id FROM venues WHERE id = ?', [(int) $id]);
        if (!$v) Response::notFound();
        Db::query('UPDATE venues SET status = "active", updated_at = NOW() WHERE id = ?', [(int) $v['id']]);
        $this->redirect('/admin/venues?tab=suspended');
    }

    public function detail(string $id): void
    {
        $user = $this->requireAuth('admin');
        $v = Db::fetch(
            'SELECT v.*, u.name AS owner_name, u.email AS owner_email, u.phone AS owner_phone
             FROM venues v JOIN users u ON u.id = v.owner_id WHERE v.id = ?',
            [(int) $id]
        );
        if (!$v) Response::notFound();

        $courts  = Db::fetchAll('SELECT * FROM courts WHERE venue_id = ? ORDER BY sort_order, id', [(int) $id]);
        $hours   = Db::fetchAll('SELECT * FROM venue_hours WHERE venue_id = ? ORDER BY day_of_week', [(int) $id]);
        $tags    = Db::fetchAll('SELECT ft.name FROM venue_facility_tags vft JOIN facility_tags ft ON ft.id = vft.tag_id WHERE vft.venue_id = ? ORDER BY ft.sort_order', [(int) $id]);
        $stats   = Db::fetch(
            'SELECT
                (SELECT COUNT(*) FROM reservations WHERE venue_id = ?) AS total_res,
                (SELECT COUNT(*) FROM reservations WHERE venue_id = ? AND status = "confirmed") AS confirmed_res,
                (SELECT COUNT(*) FROM reservations WHERE venue_id = ? AND status = "noshow") AS noshow_res,
                (SELECT COALESCE(SUM(total_price),0) FROM reservations WHERE venue_id = ? AND status IN ("confirmed","done")) AS revenue',
            [(int) $id, (int) $id, (int) $id, (int) $id]
        );
        $recent = Db::fetchAll(
            'SELECT r.code, r.reservation_date, r.start_hour, r.duration_hours, r.status, r.total_price,
                    u.name AS user_name, c.name AS court_name
             FROM reservations r
             JOIN users u ON u.id = r.user_id
             JOIN courts c ON c.id = r.court_id
             WHERE r.venue_id = ? ORDER BY r.id DESC LIMIT 20',
            [(int) $id]
        );
        $this->view('admin/venue_detail', [
            'title'  => $v['name'] . ' — 구장 상세',
            'user'   => $user,
            'venue'  => $v,
            'courts' => $courts,
            'hours'  => $hours,
            'tags'   => $tags,
            'stats'  => $stats,
            'recent' => $recent,
        ], layout: 'admin');
    }
}
