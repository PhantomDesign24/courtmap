<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Response;

final class UserController extends Controller
{
    public function index(): void
    {
        $admin = $this->requireAuth('admin');
        $q       = trim((string) ($_GET['q'] ?? ''));
        $role    = (string) ($_GET['role'] ?? '');
        $status  = (string) ($_GET['status'] ?? '');
        $minScore= (string) ($_GET['min_score'] ?? '');
        $maxScore= (string) ($_GET['max_score'] ?? '');
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 50;
        $offset  = ($page - 1) * $perPage;

        $where = ['1=1'];
        $params = [];
        if ($q !== '') {
            $needle = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
            $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)';
            array_push($params, $needle, $needle, $needle);
        }
        if (in_array($role, ['user','operator','admin'], true)) {
            $where[] = 'role = ?';
            $params[] = $role;
        }
        if (in_array($status, ['active','suspended'], true)) {
            $where[] = 'status = ?';
            $params[] = $status;
        }
        if ($minScore !== '' && ctype_digit($minScore)) {
            $where[] = 'trust_score >= ?';
            $params[] = (int) $minScore;
        }
        if ($maxScore !== '' && ctype_digit($maxScore)) {
            $where[] = 'trust_score <= ?';
            $params[] = (int) $maxScore;
        }
        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $total = (int) (Db::fetch("SELECT COUNT(*) AS c FROM users $whereSql", $params)['c'] ?? 0);
        $users = Db::fetchAll(
            "SELECT id, email, name, phone, role, trust_score, status, restricted_until, last_login_at, created_at
             FROM users $whereSql ORDER BY id DESC LIMIT $perPage OFFSET $offset",
            $params
        );
        $totalPages = max(1, (int) ceil($total / $perPage));

        $this->view('admin/users', [
            'title'      => '사용자 관리 — 어드민',
            'user'       => $admin,
            'users'      => $users,
            'q'          => $q,
            'role'       => $role,
            'status'     => $status,
            'minScore'   => $minScore,
            'maxScore'   => $maxScore,
            'page'       => $page,
            'totalPages' => $totalPages,
            'total'      => $total,
        ], layout: 'admin');
    }

    public function suspend(string $id): void
    {
        $this->requireAuth('admin');
        $u = Db::fetch('SELECT id, status FROM users WHERE id = ?', [(int) $id]);
        if (!$u) Response::notFound();
        $next = $u['status'] === 'suspended' ? 'active' : 'suspended';
        Db::query('UPDATE users SET status = ? WHERE id = ?', [$next, (int) $u['id']]);
        $this->redirect('/admin/users');
    }

    public function adjustScore(string $id): void
    {
        $this->requireAuth('admin');
        $u = Db::fetch('SELECT id FROM users WHERE id = ?', [(int) $id]);
        if (!$u) Response::notFound();
        $score = max(0, min(100, (int) $_POST['trust_score']));
        Db::query('UPDATE users SET trust_score = ? WHERE id = ?', [$score, (int) $u['id']]);
        Db::query('UPDATE users SET restricted_until = NULL WHERE id = ? AND trust_score >= 60', [(int) $u['id']]);
        $this->redirect('/admin/users');
    }

    public function changeRole(string $id): void
    {
        $this->requireAuth('admin');
        $u = Db::fetch('SELECT id FROM users WHERE id = ?', [(int) $id]);
        if (!$u) Response::notFound();
        $role = (string) ($_POST['role'] ?? 'user');
        if (!in_array($role, ['user', 'operator', 'admin'], true)) Response::redirect('/admin/users');
        Db::query('UPDATE users SET role = ? WHERE id = ?', [$role, (int) $u['id']]);
        $this->redirect('/admin/users');
    }
}
