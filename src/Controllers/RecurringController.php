<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Db;
use App\Core\Response;
use App\Services\ReservationService;
use App\Services\VenueQueries;

final class RecurringController extends Controller
{
    public function newForm(): void
    {
        $user = $this->requireAuth();
        $venues = VenueQueries::listForCards();
        $this->view('app', [
            'title'  => '정기 예약 만들기',
            'screen' => 'recurring_new',
            'data'   => [
                'venues'   => $venues,
                'venuesAll'=> $venues,
            ],
        ], layout: null);
    }

    public function create(): void
    {
        $user = $this->requireAuth();

        $venueId   = (int) $_POST['venue_id'];
        $courtId   = (int) $_POST['court_id'];
        $dow       = (int) $_POST['day_of_week'];
        $startHour = (int) $_POST['start_hour'];
        $duration  = (int) $_POST['duration_hours'];
        $weeks     = max(1, min(12, (int) $_POST['week_count']));

        // 다음 dow 까지의 첫 날짜
        $today = new \DateTime('today');
        $diff = ($dow - (int) $today->format('w') + 7) % 7;
        if ($diff === 0) $diff = 7; // 다음 주 같은 요일부터
        $first = (clone $today)->modify("+{$diff} days");
        $end   = (clone $first)->modify('+' . (($weeks - 1) * 7) . ' days');

        try {
            $groupId = Db::insert('recurring_groups', [
                'user_id'        => (int) $user['id'],
                'venue_id'       => $venueId,
                'court_id'       => $courtId,
                'day_of_week'    => $dow,
                'start_hour'     => $startHour,
                'duration_hours' => $duration,
                'start_date'     => $first->format('Y-m-d'),
                'end_date'       => $end->format('Y-m-d'),
                'week_count'     => $weeks,
                'status'         => 'active',
            ]);

            $created = 0;
            $skipped = 0;
            for ($i = 0; $i < $weeks; $i++) {
                $d = (clone $first)->modify("+" . ($i * 7) . " days");
                try {
                    $r = ReservationService::create((int) $user['id'], [
                        'venue_id'       => $venueId,
                        'court_id'       => $courtId,
                        'date'           => $d->format('Y-m-d'),
                        'start_hour'     => $startHour,
                        'duration_hours' => $duration,
                    ]);
                    Db::query('UPDATE reservations SET recurring_group_id = ? WHERE id = ?', [$groupId, $r['id']]);
                    $created++;
                } catch (\Throwable $e) {
                    $skipped++;
                }
            }
            $this->redirect('/me/reservations?recurring=' . $created . ($skipped > 0 ? '&skipped=' . $skipped : ''));
        } catch (\Throwable $e) {
            Response::html('<h1>실패</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>', 400);
        }
    }
}
