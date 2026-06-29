<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class PurchaseOrder extends Model
{
    protected static string $table = 'purchase_orders';

    /**
     * Gera o próximo código de pedido (PED-000001)
     */
    public static function generateCode(): string
    {
        $last = Database::fetch("SELECT code FROM purchase_orders ORDER BY id DESC LIMIT 1");
        if ($last) {
            $number = (int) substr($last['code'], 4) + 1;
        } else {
            $number = 1;
        }
        return 'PED-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Gera um token seguro
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Busca pedido por token de cotação
     */
    public static function findByQuoteToken(string $token): ?array
    {
        return Database::fetch("SELECT * FROM purchase_orders WHERE quote_token = ?", [$token]);
    }

    /**
     * Busca pedido por token de aprovação
     */
    public static function findByApprovalToken(string $token): ?array
    {
        return Database::fetch("SELECT * FROM purchase_orders WHERE approval_token = ?", [$token]);
    }

    /**
     * Lista todos com fornecedor
     */
    public static function allWithSupplier(string $orderBy = 'po.created_at DESC'): array
    {
        return Database::fetchAll(
            "SELECT po.*, s.name as supplier_name,
             COALESCE(
                (SELECT pos.subtotal_final FROM purchase_order_suppliers pos WHERE pos.order_id = po.id AND pos.approved = 1 LIMIT 1),
                po.total_estimated
             ) as display_total
             FROM purchase_orders po
             LEFT JOIN suppliers s ON po.supplier_id = s.id
             ORDER BY {$orderBy}"
        );
    }

    /**
     * Busca pedido completo com fornecedor
     */
    public static function findFull(int $id): ?array
    {
        return Database::fetch(
            "SELECT po.*, s.name as supplier_name, s.email as supplier_email, s.phone as supplier_phone, s.cnpj as supplier_cnpj
             FROM purchase_orders po
             LEFT JOIN suppliers s ON po.supplier_id = s.id
             WHERE po.id = ?",
            [$id]
        );
    }

    /**
     * Conta pedidos por status
     */
    public static function countByStatus(string $status): int
    {
        return self::count("status = ?", [$status]);
    }
}
