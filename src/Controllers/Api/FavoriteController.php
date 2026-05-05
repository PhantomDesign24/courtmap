<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Db;
use App\Core\Response;

final class FavoriteController extends Controller
{
    public function toggle(string $venueId): void
    {
        $user = Auth::user();
        if (!$user) Response::json(['error' => '로그인 필요'], 401);
        $vid = (int) $venueId;
        $exists = Db::fetch('SELECT user_id FROM favorites WHERE user_id = ? AND venue_id = ?', [$user['id'], $vid]);
        if ($exists) {
            Db::query('DELETE FROM favorites WHERE user_id = ? AND venue_id = ?', [$user['id'], $vid]);
            Response::json(['ok' => true, 'fav' => false]);
        }
        Db::query('INSERT IGNORE INTO favorites (user_id, venue_id) VALUES (?, ?)', [$user['id'], $vid]);
        Response::json(['ok' => true, 'fav' => true]);
    }
}
