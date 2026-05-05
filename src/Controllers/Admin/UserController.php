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
        $q    = trim((string) ($_GET['q'] ?? ''));
        $role = (string) ($_GET['role'] ?? '');

        $where = ['1=1'];
        $params = [];
        if ($q !== '') {
            $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)';
            array_push($params, "%$q%", "%$q%", "%$q%");
        }
        if (in_array($role, ['user','operator','admin'], true)) {
            $where[] = 'role = ?';
            $params[] = $role;
        }
        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $users = Db::fetchAll(
            "SELECT id, email, name, phone, role, trust_score, status, restricted_until, last_login_at, created_at
             FROM users $whereSql ORDER BY id DESC LIMIT 200",
            $params
        );
        $this->view('admin/users', [
            'title' => '사용자 관리 — 어드민',
            'user'  => $admin,
            'users' => $users,
            'q'     => $q,
            'role'  => $role,
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
