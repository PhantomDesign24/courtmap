<?php
declare(strict_types=1);

namespace App\Controllers\Operator;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Response;

final class CouponController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth('operator');
        $venues = Db::fetchAll('SELECT id, name FROM venues WHERE owner_id = ? AND status = "active"', [$user['id']]);
        $venueIds = array_map(static fn($v) => (int) $v['id'], $venues);

        $coupons = $venueIds
            ? Db::fetchAll(
                'SELECT * FROM coupons WHERE venue_id IN (' . implode(',', array_fill(0, count($venueIds), '?')) . ') OR issued_by = ?
                 ORDER BY id DESC',
                array_merge($venueIds, [(int) $user['id']])
            )
            : [];

        $memberships = $venueIds
            ? Db::fetchAll(
                'SELECT m.*, v.name AS venue_name,
                        (SELECT COUNT(*) FROM user_memberships WHERE membership_id = m.id AND status = "active") AS active_count
                 FROM memberships m
                 JOIN venues v ON v.id = m.venue_id
                 WHERE m.venue_id IN (' . implode(',', array_fill(0, count($venueIds), '?')) . ')
                 ORDER BY m.id DESC',
                $venueIds
            )
            : [];

        $this->view('operator/coupons', [
            'title'       => '쿠폰·멤버십 — 운영자',
            'user'        => $user,
            'venues'      => $venues,
            'coupons'     => $coupons,
            'memberships' => $memberships,
        ], layout: 'operator');
    }

    public function createCoupon(): void
    {
        $user = $this->requireAuth('operator');
        $venueId = (int) $_POST['venue_id'];
        $this->ensureOwn($venueId, (int) $user['id']);

        Db::insert('coupons', [
            'name'           => trim((string) $_POST['name']),
            'venue_id'       => $venueId,
            'issued_by'      => (int) $user['id'],
            'discount_type'  => $_POST['discount_type'] === 'percent' ? 'percent' : 'fixed',
            'discount_value' => max(1, (int) $_POST['discount_value']),
            'min_amount'     => max(0, (int) ($_POST['min_amount'] ?? 0)),
            'valid_from'     => $_POST['valid_from'] ?: null,
            'valid_until'    => $_POST['valid_until'] ?: null,
            'total_quota'    => $_POST['total_quota'] ? (int) $_POST['total_quota'] : null,
            'status'         => 'active',
        ]);
        $this->redirect('/operator/coupons');
    }

    public function suspendCoupon(string $id): void
    {
        $user = $this->requireAuth('operator');
        $r = Db::fetch('SELECT c.id, c.status, v.owner_id FROM coupons c JOIN venues v ON v.id = c.venue_id WHERE c.id = ?', [(int) $id]);
        if (!$r) Response::notFound();
        if ((int) $r['owner_id'] !== (int) $user['id']) Response::forbidden();
        $next = $r['status'] === 'suspended' ? 'active' : 'suspended';
        Db::query('UPDATE coupons SET status = ? WHERE id = ?', [$next, (int) $r['id']]);
        $this->redirect('/operator/coupons');
    }

    public function suspendMembership(string $id): void
    {
        $user = $this->requireAuth('operator');
        $r = Db::fetch('SELECT m.id, m.status, v.owner_id FROM memberships m JOIN venues v ON v.id = m.venue_id WHERE m.id = ?', [(int) $id]);
        if (!$r) Response::notFound();
        if ((int) $r['owner_id'] !== (int) $user['id']) Response::forbidden();
        $next = $r['status'] === 'suspended' ? 'active' : 'suspended';
        Db::query('UPDATE memberships SET status = ? WHERE id = ?', [$next, (int) $r['id']]);
        $this->redirect('/operator/coupons');
    }

    public function createMembership(): void
    {
        $user = $this->requireAuth('operator');
        $venueId = (int) $_POST['venue_id'];
        $this->ensureOwn($venueId, (int) $user['id']);

        Db::insert('memberships', [
            'venue_id'      => $venueId,
            'name'          => trim((string) $_POST['name']),
            'description'   => trim((string) ($_POST['description'] ?? '')),
            'price'         => max(0, (int) $_POST['price']),
            'hours_total'   => max(1, (int) $_POST['hours_total']),
            'valid_months'  => max(1, (int) $_POST['valid_months']),
            'status'        => 'active',
        ]);
        $this->redirect('/operator/coupons');
    }

    private function ensureOwn(int $venueId, int $userId): void
    {
        $v = Db::fetch('SELECT owner_id FROM venues WHERE id = ?', [$venueId]);
        if (!$v || (int) $v['owner_id'] !== $userId) Response::forbidden();
    }
}
