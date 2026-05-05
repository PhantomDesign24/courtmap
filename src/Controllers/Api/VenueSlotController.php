<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Response;
use App\Services\SlotService;

final class VenueSlotController extends Controller
{
    public function show(string $id): void
    {
        $date = (string) ($_GET['date'] ?? date('Y-m-d'));
        try {
            $data = SlotService::forDate((int) $id, $date);
            Response::json($data);
        } catch (\Throwable $e) {
            Response::json(['error' => $e->getMessage()], 400);
        }
    }
}
