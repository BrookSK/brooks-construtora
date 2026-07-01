<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class PurchaseOrderSupplierMaterial extends Model
{
    protected static string $table = 'purchase_order_supplier_materials';

    public static function getByOrderAndSupplier(int $orderId, int $supplierId): array
    {
        return Database::fetchAll(
            "SELECT * FROM purchase_order_supplier_materials WHERE order_id = ? AND supplier_id = ? ORDER BY id ASC",
            [$orderId, $supplierId]
        );
    }

    public static function getByOrder(int $orderId): array
    {
        return Database::fetchAll(
            "SELECT posm.*, s.name as supplier_name
             FROM purchase_order_supplier_materials posm
             JOIN suppliers s ON posm.supplier_id = s.id
             WHERE posm.order_id = ?
             ORDER BY posm.supplier_id, posm.id ASC",
            [$orderId]
        );
    }

    public static function getByPdf(int $pdfId): array
    {
        return Database::fetchAll(
            "SELECT * FROM purchase_order_supplier_materials WHERE pdf_id = ? ORDER BY id ASC",
            [$pdfId]
        );
    }

    public static function deleteByOrderAndSupplier(int $orderId, int $supplierId): int
    {
        return Database::delete('purchase_order_supplier_materials', 'order_id = ? AND supplier_id = ?', [$orderId, $supplierId]);
    }
}
