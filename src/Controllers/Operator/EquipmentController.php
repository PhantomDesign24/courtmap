<?php
declare(strict_types=1);

namespace App\Controllers\Operator;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Response;

final class EquipmentController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth('operator');
        $venues = Db::fetchAll('SELECT id, name FROM venues WHERE owner_id = ? AND status = "active"', [$user['id']]);
        $venueId = (int) ($_GET['venue_id'] ?? ($venues[0]['id'] ?? 0));
        $items = $venueId ? Db::fetchAll(
            'SELECT * FROM equipment_options WHERE venue_id = ? ORDER BY sort_order, id',
            [$venueId]
        ) : [];
        $this->view('operator/equipment', [
            'title'   => '장비 대여 — 운영자',
            'user'    => $user,
            'venues'  => $venues,
            'venueId' => $venueId,
            'items'   => $items,
        ], layout: 'operator');
    }

    public function add(): void
    {
        $user = $this->requireAuth('operator');
        $venueId = (int) $_POST['venue_id'];
        $this->ensureOwn($venueId, (int) $user['id']);
        Db::insert('equipment_options', [
            'venue_id'      => $venueId,
            'type'          => in_array($_POST['type'], ['racket','shuttle','other'], true) ? $_POST['type'] : 'other',
            'name'          => trim((string) $_POST['name']),
            'description'   => trim((string) ($_POST['description'] ?? '')),
            'price'         => max(0, (int) $_POST['price']),
            'default_check' => empty($_POST['default_check']) ? 0 : 1,
            'max_qty'       => max(1, min(20, (int) ($_POST['max_qty'] ?? 1))),
            'status'        => 'active',
        ]);
        $this->redirect('/operator/equipment?venue_id=' . $venueId);
    }

    public function update(string $id): void
    {
        $user = $this->requireAuth('operator');
        $r = Db::fetch('SELECT eo.id, eo.venue_id, v.owner_id FROM equipment_options eo JOIN venues v ON v.id = eo.venue_id WHERE eo.id = ?', [(int) $id]);
        if (!$r) Response::notFound();
        if ((int) $r['owner_id'] !== (int) $user['id']) Response::forbidden();
        Db::query(
            'UPDATE equipment_options SET name = ?, description = ?, price = ?, default_check = ?, max_qty = ?, sort_order = ? WHERE id = ?',
            [
                trim((string) $_POST['name']),
                trim((string) ($_POST['description'] ?? '')),
                max(0, (int) $_POST['price']),
                empty($_POST['default_check']) ? 0 : 1,
                max(1, min(20, (int) ($_POST['max_qty'] ?? 1))),
                max(0, (int) ($_POST['sort_order'] ?? 0)),
                (int) $r['id'],
            ]
        );
        $this->redirect('/operator/equipment?venue_id=' . (int) $r['venue_id']);
    }

    public function delete(string $id): void
    {
        $user = $this->requireAuth('operator');
        $r = Db::fetch('SELECT eo.id, eo.venue_id, v.owner_id FROM equipment_options eo JOIN venues v ON v.id = eo.venue_id WHERE eo.id = ?', [(int) $id]);
        if (!$r) Response::notFound();
        if ((int) $r['owner_id'] !== (int) $user['id']) Response::forbidden();
        Db::query('UPDATE equipment_options SET status = "suspended" WHERE id = ?', [(int) $r['id']]);
        $this->redirect('/operator/equipment?venue_id=' . (int) $r['venue_id']);
    }

    private function ensureOwn(int $venueId, int $userId): void
    {
        $v = Db::fetch('SELECT owner_id FROM venues WHERE id = ?', [$venueId]);
        if (!$v || (int) $v['owner_id'] !== $userId) Response::forbidden();
    }
}
