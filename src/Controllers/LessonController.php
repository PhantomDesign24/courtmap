<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Db;
use App\Core\Response;

final class LessonController extends Controller
{
    /**
     * 레슨 예약 신청.
     * 입력: coach_id, lesson_date(YYYY-MM-DD), start_hour
     */
    public function create(): void
    {
        $user = Auth::user();
        if (!$user) Response::json(['error' => '로그인이 필요합니다'], 401);

        $coachId = (int) ($_POST['coach_id'] ?? 0);
        $date    = (string) ($_POST['lesson_date'] ?? '');
        $hour    = (int) ($_POST['start_hour'] ?? -1);

        $coach = Db::fetch('SELECT * FROM coaches WHERE id = ? AND status = "active"', [$coachId]);
        if (!$coach) Response::json(['error' => '강사를 찾을 수 없습니다.'], 404);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) Response::json(['error' => 'date 형식'], 400);
        if ($hour < 0 || $hour > 23) Response::json(['error' => 'start_hour 0~23'], 400);
        if ($date < date('Y-m-d')) Response::json(['error' => '과거 날짜는 예약 불가'], 400);

        // 충돌 체크 + INSERT 를 한 트랜잭션 + 강사 행 잠금 (M-3 race 방지)
        $code = 'LSN-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        try {
            $id = Db::transaction(function () use ($coachId, $date, $hour, $coach, $user, $code) {
                Db::query('SELECT id FROM coaches WHERE id = ? FOR UPDATE', [$coachId]);
                $conflict = Db::fetch(
                    'SELECT id FROM lesson_reservations
                     WHERE coach_id = ? AND lesson_date = ? AND start_hour = ?
                       AND status IN ("pending","confirmed")
                     LIMIT 1',
                    [$coachId, $date, $hour]
                );
                if ($conflict) throw new \RuntimeException('이미 예약된 시간입니다.');
                return Db::insert('lesson_reservations', [
                    'code'         => $code,
                    'user_id'      => (int) $user['id'],
                    'coach_id'     => $coachId,
                    'venue_id'     => (int) $coach['venue_id'],
                    'lesson_date'  => $date,
                    'start_hour'   => $hour,
                    'duration_min' => (int) $coach['duration_min'],
                    'price'        => (int) $coach['price_per_lesson'],
                    'status'       => 'pending',
                ]);
            });
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 409);
        }
        Db::insert('notifications', [
            'user_id'      => (int) $user['id'],
            'type'         => 'system',
            'title'        => '레슨 예약 신청 — ' . $coach['name'],
            'body'         => $date . ' ' . sprintf('%02d:00', $hour) . ' (' . $coach['duration_min'] . '분)',
            'related_type' => 'lesson',
            'related_id'   => $id,
        ]);
        Response::json(['ok' => true, 'code' => $code, 'id' => $id]);
    }
}
