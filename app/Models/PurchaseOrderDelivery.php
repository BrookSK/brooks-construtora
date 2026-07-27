<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class PurchaseOrderDelivery extends Model
{
    protected static string $table = 'purchase_order_deliveries';

    const STATUS_PENDING = 'pending';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CHECKED = 'checked';
    const STATUS_DIVERGENCE = 'divergence';
    const STATUS_REPLACEMENT_REQUESTED = 'replacement_requested';
    const STATUS_REPLACEMENT_DELIVERED = 'replacement_delivered';

    public static array $statusLabels = [
        'pending' => ['Pendente', 'secondary', 'bi-clock'],
        'delivered' => ['Entregue', 'primary', 'bi-box-seam'],
        'checked' => ['Conferido', 'success', 'bi-check-circle-fill'],
        'divergence' => ['Divergência', 'danger', 'bi-exclamation-triangle'],
        'replacement_requested' => ['Troca Solicitada', 'warning', 'bi-arrow-repeat'],
        'replacement_delivered' => ['Troca Entregue', 'success', 'bi-check-all'],
    ];

    /**
     * Busca todos os registros de entrega de um pedido com dados do item
     */
    public static function getByOrder(int $orderId): array
    {
        return Database::fetchAll(
            "SELECT pod.*, poi.material_name, poi.quantity, poi.unit, poi.approved_supplier_id,
                    poi.source_type, poi.stock_movement_id,
                    s.name as supplier_name
             FROM purchase_order_deliveries pod
             JOIN purchase_order_items poi ON pod.item_id = poi.id
             LEFT JOIN suppliers s ON pod.supplier_id = s.id
             WHERE pod.order_id = ?
             ORDER BY pod.id ASC",
            [$orderId]
        );
    }

    /**
     * Busca registro de entrega de um item específico
     */
    public static function findByItem(int $orderId, int $itemId): ?array
    {
        return Database::fetch(
            "SELECT * FROM purchase_order_deliveries WHERE order_id = ? AND item_id = ?",
            [$orderId, $itemId]
        );
    }

    /**
     * Inicializa o checklist para todos os itens de um pedido aprovado
     */
    public static function initializeForOrder(int $orderId): void
    {
        $items = PurchaseOrderItem::getByOrder($orderId);
        
        // Fallback: se os itens não têm approved_supplier_id, usa o supplier_id do pedido
        $order = Database::fetch("SELECT supplier_id FROM purchase_orders WHERE id = ?", [$orderId]);
        $fallbackSupplierId = $order['supplier_id'] ?? null;

        // Se tem fornecedores aprovados no pedido, montar mapa de preços para inferir
        $approvedSuppliers = PurchaseOrderSupplier::getAllApproved($orderId);
        $priceMap = [];
        if (!empty($approvedSuppliers)) {
            foreach ($approvedSuppliers as $as) {
                $prices = \App\Models\PurchaseOrderItemPrice::getByOrderAndSupplier($orderId, $as['supplier_id']);
                foreach ($prices as $p) {
                    // Se o item tem preço deste fornecedor e o preço bate com o unit_price do item, é ele
                    $priceMap[$p['item_id']][$as['supplier_id']] = $p;
                }
            }
        }
        
        foreach ($items as $item) {
            $existing = self::findByItem($orderId, $item['id']);
            if (!$existing) {
                // Determinar o fornecedor deste item
                $supplierId = $item['approved_supplier_id'] ?? null;
                
                if (!$supplierId && !empty($priceMap[$item['id']])) {
                    // Inferir pelo preço: qual fornecedor tem preço == unit_price do item
                    foreach ($priceMap[$item['id']] as $sid => $p) {
                        if ((float)$p['unit_price'] == (float)$item['unit_price']) {
                            $supplierId = $sid;
                            break;
                        }
                    }
                }

                if (!$supplierId) {
                    $supplierId = $fallbackSupplierId;
                }

                self::create([
                    'order_id' => $orderId,
                    'item_id' => $item['id'],
                    'supplier_id' => $supplierId,
                    'status' => self::STATUS_PENDING,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    /**
     * Conta itens por status para um pedido
     */
    public static function countByStatus(int $orderId): array
    {
        $rows = Database::fetchAll(
            "SELECT status, COUNT(*) as total FROM purchase_order_deliveries WHERE order_id = ? GROUP BY status",
            [$orderId]
        );
        $counts = [];
        foreach ($rows as $r) {
            $counts[$r['status']] = (int) $r['total'];
        }
        return $counts;
    }

    /**
     * Verifica se algum item está atrasado
     * Atrasado = data combinada já passou E status NÃO é checked/delivered/replacement_delivered
     */
    public static function getLateItems(int $orderId): array
    {
        return Database::fetchAll(
            "SELECT pod.*, poi.material_name, poi.quantity, poi.unit, s.name as supplier_name
             FROM purchase_order_deliveries pod
             JOIN purchase_order_items poi ON pod.item_id = poi.id
             LEFT JOIN suppliers s ON pod.supplier_id = s.id
             WHERE pod.order_id = ? 
               AND pod.status NOT IN ('checked', 'replacement_delivered')
               AND (
                   (pod.expected_date IS NOT NULL AND pod.expected_date < CURDATE() AND pod.status IN ('pending', 'divergence', 'replacement_requested'))
                   OR
                   (pod.replacement_expected_date IS NOT NULL AND pod.replacement_expected_date < CURDATE() AND pod.status = 'replacement_requested')
               )
             ORDER BY pod.expected_date ASC",
            [$orderId]
        );
    }
}
