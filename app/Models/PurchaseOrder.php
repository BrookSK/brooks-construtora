<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class PurchaseOrder extends Model
{
    protected static string $table = 'purchase_orders';

    /**
     * Verifica se a tabela construction_sites e a coluna construction_site_id existem
     */
    private static ?bool $hasConstructionSites = null;

    private static function hasConstructionSites(): bool
    {
        if (self::$hasConstructionSites === null) {
            try {
                $result = Database::fetch("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_orders' AND COLUMN_NAME = 'construction_site_id' LIMIT 1");
                self::$hasConstructionSites = !empty($result);
            } catch (\Exception $e) {
                self::$hasConstructionSites = false;
            }
        }
        return self::$hasConstructionSites;
    }

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
     * Busca pedido por token de cotação (com dados da obra)
     */
    public static function findByQuoteToken(string $token): ?array
    {
        if (self::hasConstructionSites()) {
            return Database::fetch(
                "SELECT po.*, cs.name as construction_site_name, cs.code as construction_site_code, 
                        cs.address as construction_site_address, cs.city as construction_site_city, 
                        cs.state as construction_site_state, cs.client_name as construction_site_client
                 FROM purchase_orders po
                 LEFT JOIN construction_sites cs ON po.construction_site_id = cs.id
                 WHERE po.quote_token = ?",
                [$token]
            );
        }
        return Database::fetch("SELECT * FROM purchase_orders WHERE quote_token = ?", [$token]);
    }

    /**
     * Busca pedido por token de aprovação (com dados da obra)
     */
    public static function findByApprovalToken(string $token): ?array
    {
        if (self::hasConstructionSites()) {
            return Database::fetch(
                "SELECT po.*, cs.name as construction_site_name, cs.code as construction_site_code,
                        cs.address as construction_site_address, cs.city as construction_site_city,
                        cs.state as construction_site_state, cs.client_name as construction_site_client
                 FROM purchase_orders po
                 LEFT JOIN construction_sites cs ON po.construction_site_id = cs.id
                 WHERE po.approval_token = ?",
                [$token]
            );
        }
        return Database::fetch("SELECT * FROM purchase_orders WHERE approval_token = ?", [$token]);
    }

    /**
     * Lista todos com fornecedor e obra
     */
    public static function allWithSupplier(string $orderBy = 'po.created_at DESC'): array
    {
        if (self::hasConstructionSites()) {
            return Database::fetchAll(
                "SELECT po.*, s.name as supplier_name,
                 cs.name as construction_site_name, cs.code as construction_site_code,
                 COALESCE(
                    (SELECT pos.subtotal_final FROM purchase_order_suppliers pos WHERE pos.order_id = po.id AND pos.approved = 1 LIMIT 1),
                    po.total_estimated
                 ) as display_total,
                 (SELECT COALESCE(SUM(pop.amount), 0) FROM purchase_order_payments pop WHERE pop.order_id = po.id) as nf_total,
                 (SELECT GROUP_CONCAT(poi.material_name SEPARATOR ' | ') FROM purchase_order_items poi WHERE poi.order_id = po.id) as items_names
                 FROM purchase_orders po
                 LEFT JOIN suppliers s ON po.supplier_id = s.id
                 LEFT JOIN construction_sites cs ON po.construction_site_id = cs.id
                 ORDER BY {$orderBy}"
            );
        }
        return Database::fetchAll(
            "SELECT po.*, s.name as supplier_name,
             COALESCE(
                (SELECT pos.subtotal_final FROM purchase_order_suppliers pos WHERE pos.order_id = po.id AND pos.approved = 1 LIMIT 1),
                po.total_estimated
             ) as display_total,
             (SELECT COALESCE(SUM(pop.amount), 0) FROM purchase_order_payments pop WHERE pop.order_id = po.id) as nf_total,
             (SELECT GROUP_CONCAT(poi.material_name SEPARATOR ' | ') FROM purchase_order_items poi WHERE poi.order_id = po.id) as items_names
             FROM purchase_orders po
             LEFT JOIN suppliers s ON po.supplier_id = s.id
             ORDER BY {$orderBy}"
        );
    }

    /**
     * Busca pedido completo com fornecedor e obra
     */
    public static function findFull(int $id): ?array
    {
        if (self::hasConstructionSites()) {
            return Database::fetch(
                "SELECT po.*, s.name as supplier_name, s.email as supplier_email, s.phone as supplier_phone, s.cnpj as supplier_cnpj,
                        cs.name as construction_site_name, cs.code as construction_site_code, cs.address as construction_site_address,
                        cs.city as construction_site_city, cs.state as construction_site_state,
                        cs.responsible_name as construction_site_responsible, cs.client_name as construction_site_client
                 FROM purchase_orders po
                 LEFT JOIN suppliers s ON po.supplier_id = s.id
                 LEFT JOIN construction_sites cs ON po.construction_site_id = cs.id
                 WHERE po.id = ?",
                [$id]
            );
        }
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
