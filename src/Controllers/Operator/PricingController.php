<?php
declare(strict_types=1);

namespace App\Controllers\Operator;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Response;

final class PricingController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth('operator');
        $venues = Db::fetchAll('SELECT id, name FROM venues WHERE owner_id = ? ORDER BY id', [$user['id']]);
        $venueId = (int) ($_GET['venue_id'] ?? ($venues[0]['id'] ?? 0));

        $courts = $venueId ? Db::fetchAll(
            'SELECT id, name FROM courts WHERE venue_id = ? AND status = "active" ORDER BY sort_order',
            [$venueId]
        ) : [];

        $hotDeals = $venueId ? Db::fetchAll(
            'SELECT dp.*, c.name AS court_name FROM dynamic_pricing dp
             LEFT JOIN courts c ON c.id = dp.court_id
             WHERE dp.venue_id = ? AND dp.status = "active"
               AND dp.target_date >= CURDATE() - INTERVAL 1 DAY
             ORDER BY dp.target_date ASC, dp.target_start_hour ASC',
            [$venueId]
        ) : [];

        $autoRules = $venueId ? Db::fetchAll(
            'SELECT * FROM auto_pricing_rules WHERE venue_id = ? ORDER BY id',
            [$venueId]
        ) : [];

        $this->view('operator/pricing', [
            'title'    => '다이나믹 프라이싱 — 운영자',
            'user'     => $user,
            'venues'   => $venues,
            'venueId'  => $venueId,
            'courts'   => $courts,
            'hotDeals' => $hotDeals,
            'autoRules'=> $autoRules,
        ], layout: 'operator');
    }

    public function createHotDeal(): void
    {
        $user = $this->requireAuth('operator');
        $venueId = (int) $_POST['venue_id'];
        $this->ensureOwn($venueId, (int) $user['id']);

        $courtId   = empty($_POST['court_id']) ? null : (int) $_POST['court_id'];
        $date      = (string) $_POST['target_date'];
        $startHour = (int) $_POST['target_start_hour'];
        $endHour   = (int) $_POST['target_end_hour'];
        $discount  = (int) $_POST['discount_pct'];

        if ($discount < 1 || $discount > 99) $this->redirect('/operator/pricing?venue_id=' . $venueId);
        if ($startHour < 0 || $endHour > 24 || $endHour <= $startHour) $this->redirect('/operator/pricing?venue_id=' . $venueId);

        Db::insert('dynamic_pricing', [
            'venue_id'          => $venueId,
            'court_id'          => $courtId,
            'target_date'       => $date,
            'target_start_hour' => $startHour,
            'target_end_hour'   => $endHour,
            'discount_pct'      => $discount,
            'status'            => 'active',
            'created_by'        => (int) $user['id'],
            'expires_at'        => date('Y-m-d H:i:s', strtotime("$date " . sprintf('%02d:00:00', $endHour))),
        ]);
        $this->redirect('/operator/pricing?venue_id=' . $venueId);
    }

    public function cancelDeal(string $id): void
    {
        $user = $this->requireAuth('operator');
        $dp = Db::fetch('SELECT dp.id, dp.venue_id, v.owner_id FROM dynamic_pricing dp JOIN venues v ON v.id = dp.venue_id WHERE dp.id = ?', [(int) $id]);
        if (!$dp)                                       Response::notFound();
        if ((int) $dp['owner_id'] !== (int) $user['id']) Response::forbidden();
        Db::query('UPDATE dynamic_pricing SET status = "canceled" WHERE id = ?', [(int) $dp['id']]);
        $this->redirect('/operator/pricing?venue_id=' . (int) $dp['venue_id']);
    }

    public function createAutoRule(): void
    {
        $user = $this->requireAuth('operator');
        $venueId = (int) $_POST['venue_id'];
        $this->ensureOwn($venueId, (int) $user['id']);

        $name      = trim((string) $_POST['name']);
        $hoursBefore = (int) $_POST['trigger_hours_before'];
        $discount    = (int) $_POST['discount_pct'];
        $applyFrom = (int) ($_POST['apply_from_hour'] ?? 0);
        $applyTo   = (int) ($_POST['apply_to_hour'] ?? 24);

        // 요일 마스크: 체크된 day_of_week[] 값들의 비트 합산 (일=1, 월=2, ..., 토=64)
        $mask = 0;
        foreach ((array) ($_POST['day_of_week'] ?? []) as $d) {
            $d = (int) $d;
            if ($d >= 0 && $d <= 6) $mask |= (1 << $d);
        }
        if ($mask === 0) $mask = 127; // 미선택 시 전 요일

        if ($name === '' || $hoursBefore < 1 || $hoursBefore > 6 || $discount < 1 || $discount > 99) {
            $this->redirect('/operator/pricing?venue_id=' . $venueId);
        }

        Db::insert('auto_pricing_rules', [
            'venue_id'             => $venueId,
            'name'                 => $name,
            'trigger_hours_before' => $hoursBefore,
            'discount_pct'         => $discount,
            'dow_mask'             => $mask,
            'apply_from_hour'      => $applyFrom,
            'apply_to_hour'        => $applyTo,
            'status'               => 'active',
        ]);
        $this->redirect('/operator/pricing?venue_id=' . $venueId);
    }

    public function toggleAutoRule(string $id): void
    {
        $user = $this->requireAuth('operator');
        $r = Db::fetch('SELECT apr.id, apr.venue_id, apr.status, v.owner_id FROM auto_pricing_rules apr JOIN venues v ON v.id = apr.venue_id WHERE apr.id = ?', [(int) $id]);
        if (!$r)                                         Response::notFound();
        if ((int) $r['owner_id'] !== (int) $user['id']) Response::forbidden();
        $next = $r['status'] === 'active' ? 'paused' : 'active';
        Db::query('UPDATE auto_pricing_rules SET status = ? WHERE id = ?', [$next, $r['id']]);
        $this->redirect('/operator/pricing?venue_id=' . (int) $r['venue_id']);
    }

    public function deleteAutoRule(string $id): void
    {
        $user = $this->requireAuth('operator');
        $r = Db::fetch('SELECT apr.id, apr.venue_id, v.owner_id FROM auto_pricing_rules apr JOIN venues v ON v.id = apr.venue_id WHERE apr.id = ?', [(int) $id]);
        if (!$r)                                         Response::notFound();
        if ((int) $r['owner_id'] !== (int) $user['id']) Response::forbidden();
        Db::query('DELETE FROM auto_pricing_rules WHERE id = ?', [$r['id']]);
        $this->redirect('/operator/pricing?venue_id=' . (int) $r['venue_id']);
    }

    private function ensureOwn(int $venueId, int $userId): void
    {
        $v = Db::fetch('SELECT owner_id FROM venues WHERE id = ?', [$venueId]);
        if (!$v || (int) $v['owner_id'] !== $userId) Response::forbidden('이 구장의 운영자가 아닙니다.');
    }
}
