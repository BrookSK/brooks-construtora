<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class PurchaseOrderItemPrice extends Model
{
    protected static string $table = 'purchase_order_item_prices';

    public static function getByOrderAndSupplier(int $orderId, int $supplierId): array
    {
        return Database::fetchAll(
            "SELECT * FROM purchase_order_item_prices WHERE order_id = ? AND supplier_id = ?",
            [$orderId, $supplierId]
        );
    }

    public static function getByOrder(int $orderId): array
    {
        return Database::fetchAll(
            "SELECT poip.*, s.name as supplier_name
             FROM purchase_order_item_prices poip
             JOIN suppliers s ON poip.supplier_id = s.id
             WHERE poip.order_id = ?
             ORDER BY poip.supplier_id, poip.item_id",
            [$orderId]
        );
    }

    public static function calculateSupplierTotal(int $orderId, int $supplierId): float
    {
        $result = Database::fetch(
            "SELECT SUM(total_price) as total FROM purchase_order_item_prices WHERE order_id = ? AND supplier_id = ?",
            [$orderId, $supplierId]
        );
        return (float) ($result['total'] ?? 0);
    }
}
