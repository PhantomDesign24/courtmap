<?php
declare(strict_types=1);

namespace App\Controllers\Operator;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Response;

final class VenueController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth('operator');
        $venues = Db::fetchAll(
            'SELECT v.*, (SELECT COUNT(*) FROM courts WHERE venue_id = v.id AND status = "active") AS court_count
             FROM venues v WHERE owner_id = ? ORDER BY id',
            [$user['id']]
        );
        $this->view('operator/venues_index', [
            'title'  => '구장 관리 — 운영자',
            'user'   => $user,
            'venues' => $venues,
        ], layout: 'operator');
    }

    public function newForm(): void
    {
        $user = $this->requireAuth('operator');
        $this->view('operator/venues_new', [
            'title' => '구장 등록 신청 — 운영자',
            'user'  => $user,
        ], layout: 'operator');
    }

    public function create(): void
    {
        $user = $this->requireAuth('operator');

        $name = trim((string) $_POST['name']);
        $area = trim((string) $_POST['area']);
        $address = trim((string) $_POST['address']);
        $phone = trim((string) $_POST['phone']);
        $price = max(0, (int) $_POST['price_per_hour']);
        $courtCount = max(1, min(20, (int) ($_POST['court_count'] ?? 4)));

        if ($name === '' || $area === '' || $address === '' || $phone === '' || $price === 0) {
            $this->redirect('/operator/venues/new');
        }

        Db::transaction(function () use ($user, $name, $area, $address, $phone, $price, $courtCount) {
            $venueId = Db::insert('venues', [
                'owner_id'       => (int) $user['id'],
                'name'           => $name,
                'area'           => $area,
                'address'        => $address,
                'lat'            => (float) ($_POST['lat'] ?? 37.5005),
                'lng'            => (float) ($_POST['lng'] ?? 127.0364),
                'phone'          => $phone,
                'description'    => trim((string) ($_POST['description'] ?? '')),
                'price_per_hour' => $price,
                'bank_name'      => trim((string) $_POST['bank_name']),
                'bank_account'   => trim((string) $_POST['bank_account']),
                'bank_holder'    => trim((string) $_POST['bank_holder']),
                'status'         => 'pending',                       // 관리자 승인 후 active
            ]);

            // 코트 N개
            for ($i = 0; $i < $courtCount; $i++) {
                Db::insert('courts', [
                    'venue_id'   => $venueId,
                    'name'       => chr(ord('A') + $i) . '코트',
                    'sort_order' => $i,
                ]);
            }
            // 운영시간 기본 (월~일 10:00 ~ 23:59)
            for ($d = 0; $d < 7; $d++) {
                Db::insert('venue_hours', [
                    'venue_id'    => $venueId,
                    'day_of_week' => $d,
                    'open_time'   => '10:00:00',
                    'close_time'  => '23:59:59',
                ]);
            }
            // 슬롯 규칙 기본
            Db::insert('slot_rules', ['venue_id' => $venueId, 'rule_type' => 'default', 'slot_unit_hours' => 1]);
            Db::insert('slot_rules', ['venue_id' => $venueId, 'rule_type' => 'holiday', 'slot_unit_hours' => 2]);

            // 관리자에게 알림
            $admins = Db::fetchAll('SELECT id FROM users WHERE role = "admin" AND status = "active"');
            foreach ($admins as $a) {
                Db::insert('notifications', [
                    'user_id'      => (int) $a['id'],
                    'type'         => 'system',
                    'title'        => '신규 구장 등록 신청',
                    'body'         => "$name — 승인 대기",
                    'link_url'     => "/admin/venues",
                    'related_type' => 'venue',
                    'related_id'   => $venueId,
                ]);
            }
        });

        $this->redirect('/operator/venues');
    }

    public function edit(string $id): void
    {
        [$user, $v] = $this->loadOwn((int) $id);

        $courts = Db::fetchAll('SELECT * FROM courts WHERE venue_id = ? ORDER BY sort_order, id', [$v['id']]);
        $hours  = Db::fetchAll('SELECT * FROM venue_hours WHERE venue_id = ? ORDER BY day_of_week', [$v['id']]);
        $allTags = Db::fetchAll('SELECT * FROM facility_tags ORDER BY sort_order');
        $venueTags = Db::fetchAll('SELECT tag_id FROM venue_facility_tags WHERE venue_id = ?', [$v['id']]);
        $venueTagIds = array_map(static fn($r) => (int) $r['tag_id'], $venueTags);

        $this->view('operator/venues_edit', [
            'title'      => $v['name'] . ' — 편집',
            'user'       => $user,
            'venue'      => $v,
            'courts'     => $courts,
            'hours'      => $hours,
            'allTags'    => $allTags,
            'venueTagIds'=> $venueTagIds,
        ], layout: 'operator');
    }

    public function update(string $id): void
    {
        [$user, $v] = $this->loadOwn((int) $id);

        $data = [
            'name'           => trim((string) $_POST['name']),
            'area'           => trim((string) $_POST['area']),
            'address'        => trim((string) $_POST['address']),
            'phone'          => trim((string) $_POST['phone']),
            'description'    => trim((string) ($_POST['description'] ?? '')),
            'price_per_hour' => max(0, (int) $_POST['price_per_hour']),
            'lat'            => (float) $_POST['lat'],
            'lng'            => (float) $_POST['lng'],
            'bank_name'      => trim((string) $_POST['bank_name']),
            'bank_account'   => trim((string) $_POST['bank_account']),
            'bank_holder'    => trim((string) $_POST['bank_holder']),
            'deposit_due_hours' => max(1, min(168, (int) $_POST['deposit_due_hours'])),
            'refund_24h_pct' => max(0, min(100, (int) $_POST['refund_24h_pct'])),
            'refund_1h_pct'  => max(0, min(100, (int) $_POST['refund_1h_pct'])),
            'refund_lt1h_pct'=> max(0, min(100, (int) $_POST['refund_lt1h_pct'])),
        ];
        Db::update('venues', $data, 'id = :wid', ['wid' => $v['id']]);

        // 운영시간 재구성 (요일별 1행)
        Db::query('DELETE FROM venue_hours WHERE venue_id = ?', [$v['id']]);
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

        // 시설 태그 재구성
        Db::query('DELETE FROM venue_facility_tags WHERE venue_id = ?', [$v['id']]);
        foreach ((array) ($_POST['tags'] ?? []) as $tagId) {
            $tagId = (int) $tagId;
            if ($tagId) Db::query('INSERT IGNORE INTO venue_facility_tags (venue_id, tag_id) VALUES (?, ?)', [$v['id'], $tagId]);
        }

        $this->redirect('/operator/venues/' . $v['id'] . '/edit');
    }

    public function addCourt(string $id): void
    {
        [$user, $v] = $this->loadOwn((int) $id);
        $name = trim((string) $_POST['name']);
        if ($name === '') $this->redirect('/operator/venues/' . $v['id'] . '/edit');
        $maxOrder = (int) (Db::fetch('SELECT COALESCE(MAX(sort_order), -1) AS m FROM courts WHERE venue_id = ?', [$v['id']])['m'] ?? -1);
        Db::insert('courts', [
            'venue_id'   => (int) $v['id'],
            'name'       => $name,
            'sort_order' => $maxOrder + 1,
        ]);
        $this->redirect('/operator/venues/' . $v['id'] . '/edit');
    }

    public function deleteCourt(string $id, string $cid): void
    {
        [$user, $v] = $this->loadOwn((int) $id);
        $c = Db::fetch('SELECT id FROM courts WHERE id = ? AND venue_id = ?', [(int) $cid, $v['id']]);
        if (!$c) Response::notFound();
        Db::query('UPDATE courts SET status = "closed" WHERE id = ?', [(int) $c['id']]);
        $this->redirect('/operator/venues/' . $v['id'] . '/edit');
    }

    public function updateCourt(string $id, string $cid): void
    {
        [$user, $v] = $this->loadOwn((int) $id);
        $c = Db::fetch('SELECT id FROM courts WHERE id = ? AND venue_id = ?', [(int) $cid, $v['id']]);
        if (!$c) Response::notFound();
        $name = trim((string) ($_POST['name'] ?? ''));
        $price = $_POST['price_override'] === '' ? null : max(0, (int) $_POST['price_override']);
        $sort = max(0, (int) ($_POST['sort_order'] ?? 0));
        if ($name === '') $this->redirect('/operator/venues/' . $v['id'] . '/edit');
        Db::query(
            'UPDATE courts SET name = ?, price_override = ?, sort_order = ? WHERE id = ?',
            [$name, $price, $sort, (int) $c['id']]
        );
        $this->redirect('/operator/venues/' . $v['id'] . '/edit');
    }

    public function detail(string $id): void
    {
        [$user, $v] = $this->loadOwn((int) $id);
        $venueId = (int) $v['id'];

        $kpi = Db::fetch(
            'SELECT
               (SELECT COUNT(*) FROM reservations r JOIN courts c ON c.id = r.court_id
                  WHERE c.venue_id = ? AND r.reservation_date = CURDATE() AND r.status IN ("pending","confirmed")) AS today_cnt,
               (SELECT COALESCE(SUM(total_price),0) FROM reservations r JOIN courts c ON c.id = r.court_id
                  WHERE c.venue_id = ? AND r.reservation_date = CURDATE() AND r.status = "confirmed") AS today_rev,
               (SELECT COUNT(*) FROM reservations r JOIN courts c ON c.id = r.court_id
                  WHERE c.venue_id = ? AND r.status = "pending") AS pending_cnt,
               (SELECT COUNT(*) FROM courts WHERE venue_id = ? AND status = "active") AS court_cnt',
            [$venueId, $venueId, $venueId, $venueId]
        );

        $courts      = Db::fetchAll('SELECT * FROM courts WHERE venue_id = ? ORDER BY sort_order, id', [$venueId]);
        $hours       = Db::fetchAll('SELECT * FROM venue_hours WHERE venue_id = ? ORDER BY day_of_week', [$venueId]);
        $rules       = Db::fetchAll(
            'SELECT * FROM slot_rules WHERE venue_id = ?
             ORDER BY FIELD(rule_type, "specific_date","holiday","dow","default"), id',
            [$venueId]
        );
        $deals = Db::fetchAll(
            'SELECT dp.*, c.name AS court_name FROM dynamic_pricing dp
             LEFT JOIN courts c ON c.id = dp.court_id
             WHERE dp.venue_id = ? ORDER BY dp.target_date DESC, dp.id DESC LIMIT 20',
            [$venueId]
        );
        $equipment   = Db::fetchAll('SELECT * FROM equipment_options WHERE venue_id = ? ORDER BY sort_order, id', [$venueId]);
        $coaches     = Db::fetchAll('SELECT * FROM coaches WHERE venue_id = ? ORDER BY sort_order, id', [$venueId]);
        $coupons     = Db::fetchAll('SELECT * FROM coupons WHERE venue_id = ? ORDER BY id DESC', [$venueId]);
        $memberships = Db::fetchAll(
            'SELECT m.*, (SELECT COUNT(*) FROM user_memberships WHERE membership_id = m.id AND status = "active") AS active_count
             FROM memberships m WHERE m.venue_id = ? ORDER BY id DESC',
            [$venueId]
        );
        $recent = Db::fetchAll(
            'SELECT r.code, r.reservation_date, r.start_hour, r.duration_hours, r.status, r.total_price,
                    c.name AS court_name, u.name AS user_name
             FROM reservations r
             JOIN courts c ON c.id = r.court_id
             JOIN users u ON u.id = r.user_id
             WHERE c.venue_id = ?
             ORDER BY r.id DESC LIMIT 20',
            [$venueId]
        );

        $this->view('operator/venues_detail', [
            'title'       => $v['name'] . ' — 통합 관리',
            'user'        => $user,
            'venue'       => $v,
            'kpi'         => $kpi,
            'courts'      => $courts,
            'hours'       => $hours,
            'rules'       => $rules,
            'deals'       => $deals,
            'equipment'   => $equipment,
            'coaches'     => $coaches,
            'coupons'     => $coupons,
            'memberships' => $memberships,
            'recent'      => $recent,
        ], layout: 'operator');
    }

    /** @return array{0: array, 1: array} */
    private function loadOwn(int $id): array
    {
        $user = $this->requireAuth('operator');
        $v = Db::fetch('SELECT * FROM venues WHERE id = ?', [$id]);
        if (!$v)                                       Response::notFound();
        if ((int) $v['owner_id'] !== (int) $user['id']) Response::forbidden();
        return [$user, $v];
    }
}
