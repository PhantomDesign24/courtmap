<?php
declare(strict_types=1);

namespace App\Controllers\Operator;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Response;

final class CoachController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth('operator');
        $venues = Db::fetchAll('SELECT id, name FROM venues WHERE owner_id = ? AND status = "active"', [$user['id']]);
        $venueId = (int) ($_GET['venue_id'] ?? ($venues[0]['id'] ?? 0));
        $coaches = $venueId ? Db::fetchAll(
            'SELECT * FROM coaches WHERE venue_id = ? ORDER BY sort_order, id',
            [$venueId]
        ) : [];
        $this->view('operator/coaches', [
            'title'   => '강사 관리 — 운영자',
            'user'    => $user,
            'venues'  => $venues,
            'venueId' => $venueId,
            'coaches' => $coaches,
        ], layout: 'operator');
    }

    public function add(): void
    {
        $user = $this->requireAuth('operator');
        $venueId = (int) $_POST['venue_id'];
        $this->ensureOwn($venueId, (int) $user['id']);
        Db::insert('coaches', [
            'venue_id'         => $venueId,
            'name'             => trim((string) $_POST['name']),
            'career'           => trim((string) ($_POST['career'] ?? '')),
            'bio'              => trim((string) ($_POST['bio'] ?? '')),
            'price_per_lesson' => max(0, (int) $_POST['price']),
            'duration_min'     => max(15, (int) ($_POST['duration_min'] ?? 60)),
            'img_url'          => trim((string) ($_POST['img_url'] ?? '')) ?: null,
            'status'           => 'active',
        ]);
        $this->redirect('/operator/coaches?venue_id=' . $venueId);
    }

    public function update(string $id): void
    {
        $user = $this->requireAuth('operator');
        $r = Db::fetch('SELECT c.id, c.venue_id, v.owner_id FROM coaches c JOIN venues v ON v.id = c.venue_id WHERE c.id = ?', [(int) $id]);
        if (!$r) Response::notFound();
        if ((int) $r['owner_id'] !== (int) $user['id']) Response::forbidden();
        Db::query(
            'UPDATE coaches SET name = ?, career = ?, bio = ?, price_per_lesson = ?, duration_min = ?, img_url = ? WHERE id = ?',
            [
                trim((string) $_POST['name']),
                trim((string) ($_POST['career'] ?? '')),
                trim((string) ($_POST['bio'] ?? '')),
                max(0, (int) $_POST['price']),
                max(15, (int) ($_POST['duration_min'] ?? 60)),
                trim((string) ($_POST['img_url'] ?? '')) ?: null,
                (int) $r['id'],
            ]
        );
        $this->redirect('/operator/coaches?venue_id=' . (int) $r['venue_id']);
    }

    public function delete(string $id): void
    {
        $user = $this->requireAuth('operator');
        $r = Db::fetch('SELECT c.id, c.venue_id, v.owner_id FROM coaches c JOIN venues v ON v.id = c.venue_id WHERE c.id = ?', [(int) $id]);
        if (!$r) Response::notFound();
        if ((int) $r['owner_id'] !== (int) $user['id']) Response::forbidden();
        Db::query('UPDATE coaches SET status = "suspended" WHERE id = ?', [(int) $r['id']]);
        $this->redirect('/operator/coaches?venue_id=' . (int) $r['venue_id']);
    }

    private function ensureOwn(int $venueId, int $userId): void
    {
        $v = Db::fetch('SELECT owner_id FROM venues WHERE id = ?', [$venueId]);
        if (!$v || (int) $v['owner_id'] !== $userId) Response::forbidden();
    }
}
