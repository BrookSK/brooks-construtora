<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class ConstructionSite extends Model
{
    protected static string $table = 'construction_sites';

    /**
     * Lista todas as obras ativas ordenadas por nome
     */
    public static function allActive(string $orderBy = 'name ASC'): array
    {
        return Database::fetchAll(
            "SELECT * FROM construction_sites WHERE status = 'active' ORDER BY {$orderBy}"
        );
    }

    /**
     * Lista todas com paginação e filtro
     */
    public static function allWithFilter(?string $status = null, string $orderBy = 'created_at DESC'): array
    {
        $where = '1=1';
        $params = [];

        if ($status) {
            $where .= ' AND status = ?';
            $params[] = $status;
        }

        return Database::fetchAll(
            "SELECT cs.*, 
                    (SELECT COUNT(*) FROM purchase_orders po WHERE po.construction_site_id = cs.id) as orders_count
             FROM construction_sites cs
             WHERE {$where}
             ORDER BY {$orderBy}",
            $params
        );
    }

    /**
     * Busca obra com contagem de pedidos
     */
    public static function findWithStats(int $id): ?array
    {
        return Database::fetch(
            "SELECT cs.*,
                    (SELECT COUNT(*) FROM purchase_orders po WHERE po.construction_site_id = cs.id) as orders_count,
                    (SELECT COALESCE(SUM(po.total_estimated), 0) FROM purchase_orders po WHERE po.construction_site_id = cs.id AND po.status = 'approved') as total_approved
             FROM construction_sites cs
             WHERE cs.id = ?",
            [$id]
        );
    }

    /**
     * Busca pedidos de uma obra
     */
    public static function getOrders(int $siteId, string $orderBy = 'po.created_at DESC'): array
    {
        return Database::fetchAll(
            "SELECT po.*, s.name as supplier_name,
                    COALESCE(
                        (SELECT pos.subtotal_final FROM purchase_order_suppliers pos WHERE pos.order_id = po.id AND pos.approved = 1 LIMIT 1),
                        po.total_estimated
                    ) as display_total
             FROM purchase_orders po
             LEFT JOIN suppliers s ON po.supplier_id = s.id
             WHERE po.construction_site_id = ?
             ORDER BY {$orderBy}",
            [$siteId]
        );
    }

    /**
     * Busca por nome
     */
    public static function search(string $term): array
    {
        return Database::fetchAll(
            "SELECT * FROM construction_sites WHERE status = 'active' AND (name LIKE ? OR code LIKE ? OR address LIKE ?) ORDER BY name ASC",
            ["%{$term}%", "%{$term}%", "%{$term}%"]
        );
    }

    /**
     * Gera código sequencial para obra (OBR-000001)
     */
    public static function generateCode(): string
    {
        $last = Database::fetch("SELECT code FROM construction_sites WHERE code LIKE 'OBR-%' ORDER BY id DESC LIMIT 1");
        if ($last && preg_match('/OBR-(\d+)/', $last['code'], $m)) {
            $number = (int) $m[1] + 1;
        } else {
            $number = 1;
        }
        return 'OBR-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}
