<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class PurchaseOrderHistory extends Model
{
    protected static string $table = 'purchase_order_history';

    /**
     * Busca histórico de um pedido
     */
    public static function getByOrder(int $orderId): array
    {
        return Database::fetchAll(
            "SELECT * FROM purchase_order_history WHERE order_id = ? ORDER BY created_at ASC",
            [$orderId]
        );
    }

    /**
     * Adiciona entrada no histórico
     */
    public static function log(int $orderId, string $action, string $description, ?string $performedBy = null, ?int $userId = null): int
    {
        return self::create([
            'order_id' => $orderId,
            'action' => $action,
            'description' => $description,
            'performed_by_name' => $performedBy,
            'performed_by_user_id' => $userId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
