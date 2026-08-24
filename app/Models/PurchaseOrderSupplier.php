<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class PurchaseOrderSupplier extends Model
{
    protected static string $table = 'purchase_order_suppliers';

    public static function getByOrder(int $orderId): array
    {
        return Database::fetchAll(
            "SELECT pos.*, s.name as supplier_name, s.cnpj, s.email, s.phone
             FROM purchase_order_suppliers pos
             JOIN suppliers s ON pos.supplier_id = s.id
             WHERE pos.order_id = ?
             ORDER BY pos.id ASC",
            [$orderId]
        );
    }

    public static function getApproved(int $orderId): ?array
    {
        return Database::fetch(
            "SELECT pos.*, s.name as supplier_name, s.cnpj, s.email, s.phone
             FROM purchase_order_suppliers pos
             JOIN suppliers s ON pos.supplier_id = s.id
             WHERE pos.order_id = ? AND pos.approved = 1
             LIMIT 1",
            [$orderId]
        );
    }

    public static function getAllApproved(int $orderId): array
    {
        return Database::fetchAll(
            "SELECT pos.*, s.name as supplier_name, s.cnpj, s.email, s.phone
             FROM purchase_order_suppliers pos
             JOIN suppliers s ON pos.supplier_id = s.id
             WHERE pos.order_id = ? AND pos.approved = 1",
            [$orderId]
        );
    }

    public static function findByOrderAndSupplier(int $orderId, int $supplierId): ?array
    {
        return Database::fetch(
            "SELECT * FROM purchase_order_suppliers WHERE order_id = ? AND supplier_id = ?",
            [$orderId, $supplierId]
        );
    }

    /**
     * Remove um fornecedor do pedido e todos os dados relacionados
     * (preços de item e PDFs vinculados)
     */
    public static function deleteFromOrder(int $orderId, int $supplierId): void
    {
        // Remover preços de item deste fornecedor
        Database::delete('purchase_order_item_prices', 'order_id = ? AND supplier_id = ?', [$orderId, $supplierId]);

        // Remover PDFs vinculados (se a tabela existir)
        try {
            Database::delete('purchase_order_supplier_pdfs', 'order_id = ? AND supplier_id = ?', [$orderId, $supplierId]);
        } catch (\Exception $e) {}

        // Remover o fornecedor do pedido
        Database::delete('purchase_order_suppliers', 'order_id = ? AND supplier_id = ?', [$orderId, $supplierId]);
    }
}
