<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class PurchaseOrderSupplierPdf extends Model
{
    protected static string $table = 'purchase_order_supplier_pdfs';

    public static function getByOrderAndSupplier(int $orderId, int $supplierId): array
    {
        return Database::fetchAll(
            "SELECT * FROM purchase_order_supplier_pdfs WHERE order_id = ? AND supplier_id = ? ORDER BY uploaded_at DESC",
            [$orderId, $supplierId]
        );
    }

    public static function getByOrder(int $orderId): array
    {
        return Database::fetchAll(
            "SELECT posp.*, s.name as supplier_name
             FROM purchase_order_supplier_pdfs posp
             JOIN suppliers s ON posp.supplier_id = s.id
             WHERE posp.order_id = ?
             ORDER BY posp.supplier_id, posp.uploaded_at DESC",
            [$orderId]
        );
    }
}
