<?php
declare(strict_types=1);

namespace App\Controllers\Operator;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Response;

final class ApiTokenController extends Controller
{
    public function index(): void
    {
        $user = $this->requireAuth('operator');
        $venues = Db::fetchAll('SELECT id, name FROM venues WHERE owner_id = ? AND status = "active"', [$user['id']]);
        $venueIds = array_map(static fn($v) => (int) $v['id'], $venues);

        $tokens = $venueIds ? Db::fetchAll(
            'SELECT t.*, v.name AS venue_name FROM api_tokens t
             JOIN venues v ON v.id = t.venue_id
             WHERE t.venue_id IN (' . implode(',', array_fill(0, count($venueIds), '?')) . ')
             ORDER BY t.id DESC',
            $venueIds
        ) : [];

        $webhooks = $venueIds ? Db::fetchAll(
            'SELECT w.*, v.name AS venue_name FROM webhooks w
             JOIN venues v ON v.id = w.venue_id
             WHERE w.venue_id IN (' . implode(',', array_fill(0, count($venueIds), '?')) . ')
             ORDER BY w.id DESC',
            $venueIds
        ) : [];

        $newToken = $_SESSION['new_token_flash'] ?? null;
        unset($_SESSION['new_token_flash']);

        $this->view('operator/api_tokens', [
            'title'    => 'API 연동 — 운영자',
            'user'     => $user,
            'venues'   => $venues,
            'tokens'   => $tokens,
            'webhooks' => $webhooks,
            'newToken' => $newToken,
        ], layout: 'operator');
    }

    public function createToken(): void
    {
        $user = $this->requireAuth('operator');
        $venueId = (int) $_POST['venue_id'];
        $this->ensureOwn($venueId, (int) $user['id']);
        $token = 'cmap_' . bin2hex(random_bytes(20));
        Db::insert('api_tokens', [
            'venue_id'   => $venueId,
            'token'      => substr($token, 0, 12) . '…',          // 표시용 prefix 만 저장
            'token_hash' => hash('sha256', $token),                // 실제 매칭은 hash 로
            'name'       => trim((string) $_POST['name']),
            'scopes'     => trim((string) ($_POST['scopes'] ?? 'reservations:read')),
            'status'     => 'active',
        ]);
        $_SESSION['new_token_flash'] = $token;
        $this->redirect('/operator/api');
    }

    public function revokeToken(string $id): void
    {
        $user = $this->requireAuth('operator');
        $r = Db::fetch('SELECT t.id, t.venue_id, v.owner_id FROM api_tokens t JOIN venues v ON v.id = t.venue_id WHERE t.id = ?', [(int) $id]);
        if (!$r) Response::notFound();
        if ((int) $r['owner_id'] !== (int) $user['id']) Response::forbidden();
        Db::query('UPDATE api_tokens SET status = "revoked" WHERE id = ?', [(int) $r['id']]);
        $this->redirect('/operator/api');
    }

    public function createWebhook(): void
    {
        $user = $this->requireAuth('operator');
        $venueId = (int) $_POST['venue_id'];
        $this->ensureOwn($venueId, (int) $user['id']);

        $url = trim((string) $_POST['url']);
        $check = \App\Core\SsrfGuard::check($url);
        if (!$check['ok']) {
            // 간단히 메시지 + 리다이렉트 (실제로는 alert/flash 가 더 좋음)
            \App\Core\Response::html('<h1>Webhook URL 거부</h1><p>' . htmlspecialchars($check['reason']) . '</p><p><a href="/operator/api">돌아가기</a></p>', 400);
        }

        Db::insert('webhooks', [
            'venue_id'   => $venueId,
            'event_type' => (string) ($_POST['event_type'] ?? 'reservation.confirmed'),
            'url'        => $url,
            'secret'     => bin2hex(random_bytes(20)),
            'status'     => 'active',
        ]);
        $this->redirect('/operator/api');
    }

    public function deleteWebhook(string $id): void
    {
        $user = $this->requireAuth('operator');
        $r = Db::fetch('SELECT w.id, w.venue_id, v.owner_id FROM webhooks w JOIN venues v ON v.id = w.venue_id WHERE w.id = ?', [(int) $id]);
        if (!$r) Response::notFound();
        if ((int) $r['owner_id'] !== (int) $user['id']) Response::forbidden();
        Db::query('DELETE FROM webhooks WHERE id = ?', [(int) $r['id']]);
        $this->redirect('/operator/api');
    }

    private function ensureOwn(int $venueId, int $userId): void
    {
        $v = Db::fetch('SELECT owner_id FROM venues WHERE id = ?', [$venueId]);
        if (!$v || (int) $v['owner_id'] !== $userId) Response::forbidden();
    }
}
