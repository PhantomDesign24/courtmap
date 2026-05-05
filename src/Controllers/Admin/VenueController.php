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
        $tab  = (string) ($_GET['tab'] ?? 'all');
        $q    = trim((string) ($_GET['q'] ?? ''));

        $where = ['1=1'];
        $params = [];
        if (in_array($tab, ['pending','active','suspended','closed'], true)) {
            $where[] = 'v.status = ?';
            $params[] = $tab;
        }
        if ($q !== '') {
            $needle = '%' . str_replace(['%','_'], ['\%','\_'], $q) . '%';
            $where[] = '(v.name LIKE ? OR v.area LIKE ? OR v.address LIKE ? OR u.name LIKE ? OR u.email LIKE ?)';
            array_push($params, $needle, $needle, $needle, $needle, $needle);
        }
        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $venues = Db::fetchAll(
            "SELECT v.*, u.name AS owner_name, u.email AS owner_email,
                    (SELECT COUNT(*) FROM courts WHERE venue_id = v.id) AS court_count
             FROM venues v
             JOIN users u ON u.id = v.owner_id
             $whereSql
             ORDER BY (v.status = 'pending') DESC, v.created_at DESC
             LIMIT 200",
            $params
        );
        $counts = Db::fetch(
            "SELECT
               SUM(status = 'pending')   AS c_pending,
               SUM(status = 'active')    AS c_active,
               SUM(status = 'suspended') AS c_suspended,
               SUM(status = 'closed')    AS c_closed,
               COUNT(*)                  AS c_all
             FROM venues"
        );
        $this->view('admin/venues', [
            'title'  => '구장 관리 — 어드민',
            'user'   => $user,
            'tab'    => $tab,
            'q'      => $q,
            'venues' => $venues,
            'counts' => $counts,
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

    public function edit(string $id): void
    {
        $user = $this->requireAuth('admin');
        $v = Db::fetch('SELECT * FROM venues WHERE id = ?', [(int) $id]);
        if (!$v) Response::notFound();
        $owner = Db::fetch('SELECT id, name, email, phone FROM users WHERE id = ?', [(int) $v['owner_id']]);
        $courts = Db::fetchAll('SELECT * FROM courts WHERE venue_id = ? ORDER BY sort_order, id', [(int) $id]);
        $hours  = Db::fetchAll('SELECT * FROM venue_hours WHERE venue_id = ? ORDER BY day_of_week', [(int) $id]);
        $allTags    = Db::fetchAll('SELECT * FROM facility_tags ORDER BY sort_order');
        $venueTags  = Db::fetchAll('SELECT tag_id FROM venue_facility_tags WHERE venue_id = ?', [(int) $id]);
        $venueTagIds= array_map(static fn($r) => (int) $r['tag_id'], $venueTags);
        $this->view('admin/venue_edit', [
            'title'       => $v['name'] . ' — 어드민 편집',
            'user'        => $user,
            'venue'       => $v,
            'owner'       => $owner,
            'courts'      => $courts,
            'hours'       => $hours,
            'allTags'     => $allTags,
            'venueTagIds' => $venueTagIds,
        ], layout: 'admin');
    }

    public function update(string $id): void
    {
        $this->requireAuth('admin');
        $v = Db::fetch('SELECT id FROM venues WHERE id = ?', [(int) $id]);
        if (!$v) Response::notFound();

        $allowedStatus = ['pending','active','suspended','closed'];
        $status = in_array($_POST['status'] ?? '', $allowedStatus, true) ? $_POST['status'] : null;

        $data = [
            'name'              => trim((string) $_POST['name']),
            'area'              => trim((string) $_POST['area']),
            'address'           => trim((string) $_POST['address']),
            'phone'             => trim((string) $_POST['phone']),
            'description'       => trim((string) ($_POST['description'] ?? '')),
            'price_per_hour'    => max(0, (int) $_POST['price_per_hour']),
            'lat'               => (float) $_POST['lat'],
            'lng'               => (float) $_POST['lng'],
            'bank_name'         => trim((string) $_POST['bank_name']),
            'bank_account'      => trim((string) $_POST['bank_account']),
            'bank_holder'       => trim((string) $_POST['bank_holder']),
            'deposit_due_hours' => max(1, min(168, (int) $_POST['deposit_due_hours'])),
            'refund_24h_pct'    => max(0, min(100, (int) $_POST['refund_24h_pct'])),
            'refund_1h_pct'     => max(0, min(100, (int) $_POST['refund_1h_pct'])),
            'refund_lt1h_pct'   => max(0, min(100, (int) $_POST['refund_lt1h_pct'])),
        ];
        if ($status !== null) $data['status'] = $status;
        Db::update('venues', $data, 'id = :wid', ['wid' => (int) $v['id']]);

        Db::query('DELETE FROM venue_hours WHERE venue_id = ?', [(int) $v['id']]);
        for ($d = 0; $d < 7; $d++) {
            $closed = !empty($_POST["closed_$d"]);
            Db::insert('venue_hours', [
                'venue_id'    => (int) $v['id'],
                'day_of_week' => $d,
                'open_time'   => $closed ? '00:00:00' : ($_POST["open_$d"]  ?? '10:00') . ':00',
                'close_time'  => $closed ? '00:00:00' : ($_POST["close_$d"] ?? '23:59') . ':59',
                'is_closed'   => $closed ? 1 : 0,
            ]);
        }

        Db::query('DELETE FROM venue_facility_tags WHERE venue_id = ?', [(int) $v['id']]);
        foreach ((array) ($_POST['tags'] ?? []) as $tagId) {
            $tagId = (int) $tagId;
            if ($tagId) Db::query('INSERT IGNORE INTO venue_facility_tags (venue_id, tag_id) VALUES (?, ?)', [(int) $v['id'], $tagId]);
        }

        $this->redirect('/admin/venues/' . (int) $v['id'] . '/edit');
    }

    public function changeOwner(string $id): void
    {
        $this->requireAuth('admin');
        $v = Db::fetch('SELECT id, name, owner_id FROM venues WHERE id = ?', [(int) $id]);
        if (!$v) Response::notFound();
        $newOwnerId = (int) ($_POST['owner_id'] ?? 0);
        $newOwner = Db::fetch('SELECT id, role FROM users WHERE id = ?', [$newOwnerId]);
        if (!$newOwner) {
            $_SESSION['flash_err'] = '존재하지 않는 사용자입니다.';
            $this->redirect('/admin/venues/' . (int) $v['id'] . '/edit');
        }
        if ($newOwner['role'] !== 'operator' && $newOwner['role'] !== 'admin') {
            Db::query('UPDATE users SET role = "operator" WHERE id = ?', [(int) $newOwner['id']]);
        }
        Db::query('UPDATE venues SET owner_id = ? WHERE id = ?', [(int) $newOwner['id'], (int) $v['id']]);
        Db::insert('notifications', [
            'user_id' => (int) $newOwner['id'],
            'type'    => 'system',
            'title'   => '구장 운영자로 지정되었습니다',
            'body'    => $v['name'],
            'link_url'=> '/operator/venues',
        ]);
        $_SESSION['flash_ok'] = '운영자가 변경되었습니다.';
        $this->redirect('/admin/venues/' . (int) $v['id'] . '/edit');
    }

    public function forceSuspend(string $id): void
    {
        $this->requireAuth('admin');
        $v = Db::fetch('SELECT id, owner_id, name FROM venues WHERE id = ?', [(int) $id]);
        if (!$v) Response::notFound();
        $reason = trim((string) ($_POST['reason'] ?? ''));
        Db::query('UPDATE venues SET status = "suspended", updated_at = NOW() WHERE id = ?', [(int) $v['id']]);
        Db::insert('notifications', [
            'user_id' => (int) $v['owner_id'],
            'type'    => 'system',
            'title'   => '구장이 관리자에 의해 정지되었습니다',
            'body'    => $v['name'] . ($reason !== '' ? ' — ' . $reason : ''),
            'link_url'=> '/operator/venues',
        ]);
        $this->redirect('/admin/venues/' . (int) $v['id'] . '/edit');
    }

    public function softDelete(string $id): void
    {
        $this->requireAuth('admin');
        $v = Db::fetch('SELECT id, owner_id, name, status FROM venues WHERE id = ?', [(int) $id]);
        if (!$v) Response::notFound();
        Db::transaction(function () use ($v) {
            Db::query('UPDATE venues SET status = "closed", updated_at = NOW() WHERE id = ?', [(int) $v['id']]);
            Db::query('UPDATE courts SET status = "closed" WHERE venue_id = ?', [(int) $v['id']]);
        });
        Db::insert('notifications', [
            'user_id' => (int) $v['owner_id'],
            'type'    => 'system',
            'title'   => '구장이 폐쇄 처리되었습니다',
            'body'    => $v['name'],
            'link_url'=> '/operator/venues',
        ]);
        $this->redirect('/admin/venues');
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
