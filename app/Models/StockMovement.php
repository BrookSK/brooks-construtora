<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class StockMovement extends Model
{
    protected static string $table = 'stock_movements';

    // Tipos de movimentação
    const TYPE_ENTRY = 'entry';           // Entrada no estoque
    const TYPE_EXIT = 'exit';             // Saída (uso na obra)
    const TYPE_TRANSFER = 'transfer';     // Transferência entre obras
    const TYPE_ADJUSTMENT = 'adjustment'; // Ajuste manual

    // Status (para transferências)
    const STATUS_PENDING = 'pending';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Registrar movimentação de estoque
     */
    public static function record(array $data): int
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['status'] = $data['status'] ?? self::STATUS_DELIVERED;
        return self::create($data);
    }

    /**
     * Registrar transferência entre obras/estoques
     */
    public static function transfer(int $materialId, int $fromSiteId, ?int $toSiteId, float $quantity, string $requestedBy, ?int $orderId = null): int
    {
        // Buscar location vinculada à obra
        $fromLocation = \App\Models\StockLocation::findBySite($fromSiteId);
        $toLocation = $toSiteId ? \App\Models\StockLocation::findBySite($toSiteId) : null;

        return self::record([
            'material_id' => $materialId,
            'from_site_id' => $fromSiteId,
            'to_site_id' => $toSiteId,
            'from_location_id' => $fromLocation ? $fromLocation['id'] : null,
            'to_location_id' => $toLocation ? $toLocation['id'] : null,
            'quantity' => $quantity,
            'type' => self::TYPE_TRANSFER,
            'status' => self::STATUS_PENDING,
            'requested_by' => $requestedBy,
            'order_id' => $orderId,
        ]);
    }

    /**
     * Registrar saída de estoque (uso na obra)
     */
    public static function stockExit(int $materialId, int $siteId, float $quantity, string $requestedBy, ?int $orderId = null): int
    {
        $fromLocation = \App\Models\StockLocation::findBySite($siteId);

        return self::record([
            'material_id' => $materialId,
            'from_site_id' => $siteId,
            'to_site_id' => null,
            'from_location_id' => $fromLocation ? $fromLocation['id'] : null,
            'to_location_id' => null,
            'quantity' => $quantity,
            'type' => self::TYPE_EXIT,
            'status' => self::STATUS_PENDING,
            'requested_by' => $requestedBy,
            'order_id' => $orderId,
        ]);
    }

    /**
     * Buscar movimentações pendentes do transportador (Wilton)
     * Apenas movimentações vinculadas a pedidos
     */
    public static function getPendingForTransport(): array
    {
        return Database::fetchAll(
            "SELECT sm.*, 
                    m.name as material_name, m.specification, 
                    mu.abbreviation as unit_abbr,
                    cs_from.name as from_site_name, cs_from.code as from_site_code,
                    cs_to.name as to_site_name, cs_to.code as to_site_code,
                    sl_from.name as from_location_name,
                    sl_to.name as to_location_name,
                    po.code as order_code
             FROM stock_movements sm
             JOIN materials m ON sm.material_id = m.id
             LEFT JOIN measurement_units mu ON m.unit_id = mu.id
             LEFT JOIN construction_sites cs_from ON sm.from_site_id = cs_from.id
             LEFT JOIN construction_sites cs_to ON sm.to_site_id = cs_to.id
             LEFT JOIN stock_locations sl_from ON sm.from_location_id = sl_from.id
             LEFT JOIN stock_locations sl_to ON sm.to_location_id = sl_to.id
             LEFT JOIN purchase_orders po ON sm.order_id = po.id
             WHERE sm.status = 'pending' AND sm.order_id IS NOT NULL
             ORDER BY sm.created_at ASC"
        );
    }

    /**
     * Buscar movimentações em trânsito (apenas de pedidos)
     */
    public static function getInTransit(): array
    {
        return Database::fetchAll(
            "SELECT sm.*, 
                    m.name as material_name, m.specification,
                    mu.abbreviation as unit_abbr,
                    cs_from.name as from_site_name, cs_from.code as from_site_code,
                    cs_to.name as to_site_name, cs_to.code as to_site_code,
                    sl_from.name as from_location_name,
                    sl_to.name as to_location_name,
                    po.code as order_code
             FROM stock_movements sm
             JOIN materials m ON sm.material_id = m.id
             LEFT JOIN measurement_units mu ON m.unit_id = mu.id
             LEFT JOIN construction_sites cs_from ON sm.from_site_id = cs_from.id
             LEFT JOIN construction_sites cs_to ON sm.to_site_id = cs_to.id
             LEFT JOIN stock_locations sl_from ON sm.from_location_id = sl_from.id
             LEFT JOIN stock_locations sl_to ON sm.to_location_id = sl_to.id
             LEFT JOIN purchase_orders po ON sm.order_id = po.id
             WHERE sm.status = 'in_transit' AND sm.order_id IS NOT NULL
             ORDER BY sm.created_at ASC"
        );
    }

    /**
     * Buscar movimentações entregues (histórico, apenas de pedidos)
     */
    public static function getDelivered(int $limit = 50): array
    {
        return Database::fetchAll(
            "SELECT sm.*, 
                    m.name as material_name, m.specification,
                    mu.abbreviation as unit_abbr,
                    cs_from.name as from_site_name, cs_from.code as from_site_code,
                    cs_to.name as to_site_name, cs_to.code as to_site_code,
                    sl_from.name as from_location_name,
                    sl_to.name as to_location_name,
                    po.code as order_code
             FROM stock_movements sm
             JOIN materials m ON sm.material_id = m.id
             LEFT JOIN measurement_units mu ON m.unit_id = mu.id
             LEFT JOIN construction_sites cs_from ON sm.from_site_id = cs_from.id
             LEFT JOIN construction_sites cs_to ON sm.to_site_id = cs_to.id
             LEFT JOIN stock_locations sl_from ON sm.from_location_id = sl_from.id
             LEFT JOIN stock_locations sl_to ON sm.to_location_id = sl_to.id
             LEFT JOIN purchase_orders po ON sm.order_id = po.id
             WHERE sm.status = 'delivered' AND sm.order_id IS NOT NULL
             ORDER BY sm.delivered_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Marcar como em trânsito
     */
    public static function markInTransit(int $id, string $transportedBy): void
    {
        self::updateById($id, [
            'status' => self::STATUS_IN_TRANSIT,
            'transported_by' => $transportedBy,
            'transit_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Marcar como entregue (efetua a movimentação no estoque)
     */
    public static function markDelivered(int $id, string $deliveredBy): void
    {
        $movement = self::find($id);
        if (!$movement) return;

        // Debitar do estoque de origem
        if ($movement['from_site_id']) {
            $stockItem = StockItem::findByMaterialAndSite($movement['material_id'], $movement['from_site_id']);
            if ($stockItem) {
                StockItem::debit($stockItem['id'], $movement['quantity']);
            }
        }

        // Creditar no estoque destino (se for transferência)
        if ($movement['to_site_id'] && $movement['type'] === self::TYPE_TRANSFER) {
            $destStockId = StockItem::findOrCreate($movement['material_id'], $movement['to_site_id']);
            StockItem::credit($destStockId, $movement['quantity']);
        }

        self::updateById($id, [
            'status' => self::STATUS_DELIVERED,
            'delivered_by' => $deliveredBy,
            'delivered_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Buscar movimentações de um pedido
     */
    public static function getByOrder(int $orderId): array
    {
        return Database::fetchAll(
            "SELECT sm.*, 
                    m.name as material_name, m.specification,
                    mu.abbreviation as unit_abbr,
                    cs_from.name as from_site_name,
                    cs_to.name as to_site_name
             FROM stock_movements sm
             JOIN materials m ON sm.material_id = m.id
             LEFT JOIN measurement_units mu ON m.unit_id = mu.id
             LEFT JOIN construction_sites cs_from ON sm.from_site_id = cs_from.id
             LEFT JOIN construction_sites cs_to ON sm.to_site_id = cs_to.id
             WHERE sm.order_id = ?
             ORDER BY sm.created_at ASC",
            [$orderId]
        );
    }

    /**
     * Histórico de movimentações de um site
     */
    public static function getBySite(int $siteId, int $limit = 100): array
    {
        return Database::fetchAll(
            "SELECT sm.*, 
                    m.name as material_name, m.specification,
                    mu.abbreviation as unit_abbr,
                    cs_from.name as from_site_name,
                    cs_to.name as to_site_name
             FROM stock_movements sm
             JOIN materials m ON sm.material_id = m.id
             LEFT JOIN measurement_units mu ON m.unit_id = mu.id
             LEFT JOIN construction_sites cs_from ON sm.from_site_id = cs_from.id
             LEFT JOIN construction_sites cs_to ON sm.to_site_id = cs_to.id
             WHERE sm.from_site_id = ? OR sm.to_site_id = ?
             ORDER BY sm.created_at DESC
             LIMIT ?",
            [$siteId, $siteId, $limit]
        );
    }
}
