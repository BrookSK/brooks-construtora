<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class PurchaseOrderItem extends Model
{
    protected static string $table = 'purchase_order_items';

    /**
     * Busca itens de um pedido
     */
    public static function getByOrder(int $orderId): array
    {
        return Database::fetchAll(
            "SELECT * FROM purchase_order_items WHERE order_id = ? ORDER BY id ASC",
            [$orderId]
        );
    }

    /**
     * Remove todos os itens de um pedido
     */
    public static function deleteByOrder(int $orderId): int
    {
        return Database::delete('purchase_order_items', 'order_id = ?', [$orderId]);
    }

    /**
     * Calcula o total de um pedido
     */
    public static function calculateOrderTotal(int $orderId): float
    {
        $result = Database::fetch(
            "SELECT SUM(total_price) as total FROM purchase_order_items WHERE order_id = ? AND total_price IS NOT NULL",
            [$orderId]
        );
        return (float) ($result['total'] ?? 0);
    }
}
