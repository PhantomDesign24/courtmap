<?php
declare(strict_types=1);

namespace App\Controllers\Operator;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Response;

final class SlotController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth('operator');
        $venues = Db::fetchAll('SELECT id, name FROM venues WHERE owner_id = ? ORDER BY id', [$user['id']]);

        $venueId = (int) ($_GET['venue_id'] ?? ($venues[0]['id'] ?? 0));
        $rules = $venueId ? Db::fetchAll(
            'SELECT * FROM slot_rules WHERE venue_id = ?
             ORDER BY FIELD(rule_type, "specific_date", "holiday", "dow", "default"), id',
            [$venueId]
        ) : [];

        $this->view('operator/slots', [
            'title'   => '슬롯 규칙 — 운영자',
            'user'    => $user,
            'venues'  => $venues,
            'venueId' => $venueId,
            'rules'   => $rules,
        ], layout: 'operator');
    }

    public function add(): void
    {
        $user = $this->requireAuth('operator');
        $venueId = (int) $_POST['venue_id'];
        $this->ensureOwn($venueId, (int) $user['id']);

        $type = (string) $_POST['rule_type'];
        if (!in_array($type, ['default','dow','holiday','specific_date'], true)) {
            $this->redirect('/operator/slots?venue_id=' . $venueId);
        }
        $unit = (int) $_POST['slot_unit_hours'];
        if (!in_array($unit, [1, 2, 3], true)) {
            $this->redirect('/operator/slots?venue_id=' . $venueId);
        }
        $dow = $type === 'dow'           ? (int) $_POST['day_of_week']  : null;
        $dat = $type === 'specific_date' ? (string) $_POST['specific_date'] : null;

        Db::insert('slot_rules', [
            'venue_id'        => $venueId,
            'rule_type'       => $type,
            'day_of_week'     => $dow,
            'specific_date'   => $dat,
            'slot_unit_hours' => $unit,
            'note'            => (string) ($_POST['note'] ?? ''),
        ]);
        $this->redirect('/operator/slots?venue_id=' . $venueId);
    }

    public function update(string $id): void
    {
        $user = $this->requireAuth('operator');
        $rule = Db::fetch('SELECT sr.id, sr.venue_id, v.owner_id FROM slot_rules sr JOIN venues v ON v.id = sr.venue_id WHERE sr.id = ?', [(int) $id]);
        if (!$rule)                                       Response::notFound();
        if ((int) $rule['owner_id'] !== (int) $user['id']) Response::forbidden();
        $unit = (int) $_POST['slot_unit_hours'];
        if (!in_array($unit, [1, 2, 3], true)) {
            $this->redirect('/operator/slots?venue_id=' . (int) $rule['venue_id']);
        }
        Db::query('UPDATE slot_rules SET slot_unit_hours = ?, note = ? WHERE id = ?', [
            $unit, (string) ($_POST['note'] ?? ''), (int) $rule['id'],
        ]);
        $this->redirect('/operator/slots?venue_id=' . (int) $rule['venue_id']);
    }

    public function delete(string $id): void
    {
        $user = $this->requireAuth('operator');
        $rule = Db::fetch('SELECT sr.id, sr.venue_id, v.owner_id FROM slot_rules sr JOIN venues v ON v.id = sr.venue_id WHERE sr.id = ?', [(int) $id]);
        if (!$rule)                                       Response::notFound();
        if ((int) $rule['owner_id'] !== (int) $user['id']) Response::forbidden();
        Db::query('DELETE FROM slot_rules WHERE id = ?', [(int) $rule['id']]);
        $this->redirect('/operator/slots?venue_id=' . (int) $rule['venue_id']);
    }

    private function ensureOwn(int $venueId, int $userId): void
    {
        $v = Db::fetch('SELECT owner_id FROM venues WHERE id = ?', [$venueId]);
        if (!$v || (int) $v['owner_id'] !== $userId) Response::forbidden('이 구장의 운영자가 아닙니다.');
    }
}
