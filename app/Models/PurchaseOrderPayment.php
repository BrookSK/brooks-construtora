<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class PurchaseOrderPayment extends Model
{
    protected static string $table = 'purchase_order_payments';

    public static function getByOrder(int $orderId): array
    {
        return Database::fetchAll(
            "SELECT * FROM purchase_order_payments WHERE order_id = ? ORDER BY type ASC, created_at DESC",
            [$orderId]
        );
    }

    public static function getPending(): array
    {
        return Database::fetchAll(
            "SELECT pop.*, po.code as order_code 
             FROM purchase_order_payments pop
             JOIN purchase_orders po ON pop.order_id = po.id
             WHERE pop.paid = 0
             ORDER BY pop.due_date ASC"
        );
    }

    public static function getByType(string $type): array
    {
        return Database::fetchAll(
            "SELECT pop.*, po.code as order_code 
             FROM purchase_order_payments pop
             JOIN purchase_orders po ON pop.order_id = po.id
             WHERE pop.type = ?
             ORDER BY pop.created_at DESC",
            [$type]
        );
    }
}
