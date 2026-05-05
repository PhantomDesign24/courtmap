<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Db;

final class ReportController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth('admin');
        // 노쇼 로그를 신고/문의 대용 (별도 reports 테이블 v2 도입 전)
        $rows = Db::fetchAll(
            'SELECT n.created_at, n.detected_by, n.score_delta, n.note,
                    u.name AS user_name, u.email AS user_email, u.trust_score,
                    v.name AS venue_name, r.code
             FROM noshow_logs n
             JOIN users u ON u.id = n.user_id
             JOIN venues v ON v.id = n.venue_id
             JOIN reservations r ON r.id = n.reservation_id
             ORDER BY n.created_at DESC LIMIT 100'
        );
        $this->view('admin/reports', [
            'title' => '신고·이슈 — 어드민',
            'user'  => $user,
            'rows'  => $rows,
        ], layout: 'admin');
    }
}
