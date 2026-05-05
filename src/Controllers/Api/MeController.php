<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;

final class MeController extends Controller
{
    /** 현재 사용자 위치 조회/저장 (세션 기반) */
    public function getLocation(): void
    {
        Response::json([
            'area' => $_SESSION['user_area'] ?? '강남구 역삼동',
        ]);
    }

    public function setLocation(): void
    {
        $body = Request::isJson() ? (Request::json() ?? []) : Request::all();
        $area = trim((string) ($body['area'] ?? ''));
        if ($area === '') Response::json(['error' => 'area required'], 400);
        $_SESSION['user_area'] = $area;
        Response::json(['ok' => true, 'area' => $area]);
    }
}
