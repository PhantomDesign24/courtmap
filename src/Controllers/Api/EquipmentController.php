<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Db;
use App\Core\Response;

final class EquipmentController extends Controller
{
    public function venue(string $id): void
    {
        $rows = Db::fetchAll(
            'SELECT id, type, name, description, price, default_check, max_qty
             FROM equipment_options
             WHERE venue_id = ? AND status = "active" ORDER BY sort_order, id',
            [(int) $id]
        );
        Response::json([
            'items' => array_map(static fn($r) => [
                'id'            => (int) $r['id'],
                'type'          => $r['type'],
                'name'          => $r['name'],
                'description'   => $r['description'],
                'price'         => (int) $r['price'],
                'default_check' => (bool) $r['default_check'],
                'max_qty'       => (int) $r['max_qty'],
            ], $rows),
        ]);
    }
}
