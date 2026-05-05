<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Db;

final class HealthController extends Controller
{
    public function index(): void
    {
        try {
            Db::fetch('SELECT 1 AS ok');
            $db = 'ok';
        } catch (\Throwable $e) {
            $db = 'error';
        }
        $this->json([
            'status'  => 'ok',
            'service' => '코트맵',
            'version' => '0.1.0',
            'time'    => date('c'),
            'db'      => $db,
        ]);
    }
}
