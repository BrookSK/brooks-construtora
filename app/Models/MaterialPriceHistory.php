<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class MaterialPriceHistory extends Model
{
    protected static string $table = 'material_price_history';

    /**
     * Histórico de preços de um material específico
     */
    public static function getByMaterial(int $materialId): array
    {
        return Database::fetchAll(
            "SELECT mph.*, s.name as supplier_name, po.code as order_code
             FROM material_price_history mph
             JOIN suppliers s ON mph.supplier_id = s.id
             JOIN purchase_orders po ON mph.order_id = po.id
             WHERE mph.material_id = ?
             ORDER BY mph.created_at DESC",
            [$materialId]
        );
    }

    /**
     * Histórico de preços por fornecedor
     */
    public static function getBySupplier(int $supplierId): array
    {
        return Database::fetchAll(
            "SELECT mph.*, po.code as order_code
             FROM material_price_history mph
             JOIN purchase_orders po ON mph.order_id = po.id
             WHERE mph.supplier_id = ?
             ORDER BY mph.created_at DESC",
            [$supplierId]
        );
    }

    /**
     * Último preço de um material por fornecedor
     */
    public static function getLastPrice(int $materialId, int $supplierId): ?array
    {
        return Database::fetch(
            "SELECT * FROM material_price_history 
             WHERE material_id = ? AND supplier_id = ?
             ORDER BY created_at DESC LIMIT 1",
            [$materialId, $supplierId]
        );
    }

    /**
     * Todos os históricos com agrupamento por material
     */
    public static function getAllGroupedByMaterial(int $limit = 100): array
    {
        return Database::fetchAll(
            "SELECT mph.*, s.name as supplier_name, po.code as order_code
             FROM material_price_history mph
             JOIN suppliers s ON mph.supplier_id = s.id
             JOIN purchase_orders po ON mph.order_id = po.id
             ORDER BY mph.material_name ASC, mph.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Registrar preços de uma cotação no histórico
     */
    public static function recordFromQuote(int $orderId, int $supplierId, array $items, array $prices, bool $wasApproved = false): void
    {
        foreach ($items as $item) {
            $unitPrice = $prices[$item['id']] ?? null;
            if ($unitPrice === null) continue;

            self::create([
                'material_id' => $item['material_id'],
                'material_name' => $item['material_name'],
                'supplier_id' => $supplierId,
                'order_id' => $orderId,
                'unit_price' => $unitPrice,
                'quantity' => $item['quantity'],
                'was_approved' => $wasApproved ? 1 : 0,
                'quoted_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
