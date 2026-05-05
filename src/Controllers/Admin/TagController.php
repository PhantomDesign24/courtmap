<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Response;

final class TagController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth('admin');
        $tags = Db::fetchAll(
            'SELECT ft.*,
                    (SELECT COUNT(*) FROM venue_facility_tags WHERE tag_id = ft.id) AS use_count
             FROM facility_tags ft
             ORDER BY ft.sort_order, ft.id'
        );
        $this->view('admin/tags', [
            'title' => '시설 태그 — 어드민',
            'user'  => $user,
            'tags'  => $tags,
        ], layout: 'admin');
    }

    public function create(): void
    {
        $this->requireAuth('admin');
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') $this->redirect('/admin/tags');
        $maxOrder = (int) (Db::fetch('SELECT COALESCE(MAX(sort_order), -1) AS m FROM facility_tags')['m'] ?? -1);
        Db::insert('facility_tags', [
            'name'       => $name,
            'sort_order' => $maxOrder + 1,
        ]);
        $this->redirect('/admin/tags');
    }

    public function update(string $id): void
    {
        $this->requireAuth('admin');
        $t = Db::fetch('SELECT id FROM facility_tags WHERE id = ?', [(int) $id]);
        if (!$t) Response::notFound();
        $name = trim((string) ($_POST['name'] ?? ''));
        $sort = max(0, (int) ($_POST['sort_order'] ?? 0));
        if ($name === '') $this->redirect('/admin/tags');
        Db::query('UPDATE facility_tags SET name = ?, sort_order = ? WHERE id = ?', [$name, $sort, (int) $t['id']]);
        $this->redirect('/admin/tags');
    }

    public function delete(string $id): void
    {
        $this->requireAuth('admin');
        $t = Db::fetch('SELECT id FROM facility_tags WHERE id = ?', [(int) $id]);
        if (!$t) Response::notFound();
        Db::query('DELETE FROM venue_facility_tags WHERE tag_id = ?', [(int) $t['id']]);
        Db::query('DELETE FROM facility_tags WHERE id = ?', [(int) $t['id']]);
        $this->redirect('/admin/tags');
    }
}
