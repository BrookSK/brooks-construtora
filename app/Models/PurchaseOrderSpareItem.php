<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class PurchaseOrderSpareItem extends Model
{
    protected static string $table = 'purchase_order_spare_items';

    /**
     * Busca itens sobressalentes de um pedido
     */
    public static function getByOrder(int $orderId): array
    {
        return Database::fetchAll(
            "SELECT * FROM purchase_order_spare_items WHERE order_id = ? ORDER BY purchased_at DESC, id DESC",
            [$orderId]
        );
    }

    /**
     * Total gasto em itens sobressalentes de um pedido
     */
    public static function totalByOrder(int $orderId): float
    {
        $r = Database::fetch("SELECT SUM(total_price) as total FROM purchase_order_spare_items WHERE order_id = ?", [$orderId]);
        return (float) ($r['total'] ?? 0);
    }

    /**
     * Total gasto na semana (segunda a domingo da data informada)
     */
    public static function totalThisWeek(?string $date = null): float
    {
        $date = $date ?: date('Y-m-d');
        $dayOfWeek = (int) date('N', strtotime($date)); // 1=seg, 7=dom
        $monday = date('Y-m-d', strtotime("-" . ($dayOfWeek - 1) . " days", strtotime($date)));
        $sunday = date('Y-m-d', strtotime("+" . (7 - $dayOfWeek) . " days", strtotime($date)));

        $r = Database::fetch(
            "SELECT SUM(total_price) as total FROM purchase_order_spare_items WHERE purchased_at BETWEEN ? AND ?",
            [$monday, $sunday]
        );
        return (float) ($r['total'] ?? 0);
    }

    /**
     * Lista todos da semana corrente
     */
    public static function getThisWeek(?string $date = null): array
    {
        $date = $date ?: date('Y-m-d');
        $dayOfWeek = (int) date('N', strtotime($date));
        $monday = date('Y-m-d', strtotime("-" . ($dayOfWeek - 1) . " days", strtotime($date)));
        $sunday = date('Y-m-d', strtotime("+" . (7 - $dayOfWeek) . " days", strtotime($date)));

        return Database::fetchAll(
            "SELECT si.*, po.code as order_code
             FROM purchase_order_spare_items si
             JOIN purchase_orders po ON si.order_id = po.id
             WHERE si.purchased_at BETWEEN ? AND ?
             ORDER BY si.purchased_at DESC, si.id DESC",
            [$monday, $sunday]
        );
    }

    /**
     * Lista todos agrupados por semana (para histórico)
     */
    public static function getAllGroupedByWeek(int $limit = 100): array
    {
        return Database::fetchAll(
            "SELECT si.*, po.code as order_code,
                    YEARWEEK(si.purchased_at, 1) as year_week
             FROM purchase_order_spare_items si
             JOIN purchase_orders po ON si.order_id = po.id
             ORDER BY si.purchased_at DESC, si.id DESC
             LIMIT ?",
            [$limit]
        );
    }
}
