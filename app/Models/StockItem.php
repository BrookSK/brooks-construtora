<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class StockItem extends Model
{
    protected static string $table = 'stock_items';

    /**
     * Lista estoque por obra
     */
    public static function getBySite(int $siteId): array
    {
        return Database::fetchAll(
            "SELECT si.*, m.name as material_name, m.specification, m.classification,
                    mu.name as unit_name, mu.abbreviation as unit_abbr,
                    mc.name as category_name, cs.name as site_name
             FROM stock_items si
             JOIN materials m ON si.material_id = m.id
             LEFT JOIN measurement_units mu ON m.unit_id = mu.id
             LEFT JOIN material_categories mc ON m.category_id = mc.id
             JOIN construction_sites cs ON si.construction_site_id = cs.id
             WHERE si.construction_site_id = ?
             ORDER BY m.name ASC",
            [$siteId]
        );
    }

    /**
     * Lista todo o estoque com filtros
     */
    public static function allWithRelations(?int $siteId = null): array
    {
        $where = '1=1';
        $params = [];

        if ($siteId) {
            $where .= ' AND si.construction_site_id = ?';
            $params[] = $siteId;
        }

        return Database::fetchAll(
            "SELECT si.*, m.name as material_name, m.specification, m.classification,
                    mu.name as unit_name, mu.abbreviation as unit_abbr,
                    mc.name as category_name, cs.name as site_name, cs.code as site_code
             FROM stock_items si
             JOIN materials m ON si.material_id = m.id
             LEFT JOIN measurement_units mu ON m.unit_id = mu.id
             LEFT JOIN material_categories mc ON m.category_id = mc.id
             JOIN construction_sites cs ON si.construction_site_id = cs.id
             WHERE {$where}
             ORDER BY cs.name ASC, m.name ASC",
            $params
        );
    }

    /**
     * Busca item específico: material + obra
     */
    public static function findByMaterialAndSite(int $materialId, int $siteId): ?array
    {
        return Database::fetch(
            "SELECT si.*, m.name as material_name, cs.name as site_name
             FROM stock_items si
             JOIN materials m ON si.material_id = m.id
             JOIN construction_sites cs ON si.construction_site_id = cs.id
             WHERE si.material_id = ? AND si.construction_site_id = ?",
            [$materialId, $siteId]
        );
    }

    /**
     * Busca material em TODOS os estoques (exceto a obra destino)
     */
    public static function findMaterialInAllStocks(int $materialId, ?int $excludeSiteId = null): array
    {
        $where = 'si.material_id = ? AND si.quantity > 0';
        $params = [$materialId];

        if ($excludeSiteId) {
            $where .= ' AND si.construction_site_id != ?';
            $params[] = $excludeSiteId;
        }

        return Database::fetchAll(
            "SELECT si.*, cs.name as site_name, cs.code as site_code,
                    m.name as material_name, mu.abbreviation as unit_abbr
             FROM stock_items si
             JOIN construction_sites cs ON si.construction_site_id = cs.id
             JOIN materials m ON si.material_id = m.id
             LEFT JOIN measurement_units mu ON m.unit_id = mu.id
             WHERE {$where}
             ORDER BY si.quantity DESC",
            $params
        );
    }

    /**
     * Verifica disponibilidade de múltiplos materiais em todos os estoques
     * Retorna array indexado por material_id
     */
    public static function checkAvailability(array $materialIds, ?int $targetSiteId = null): array
    {
        if (empty($materialIds)) return [];

        $placeholders = implode(',', array_fill(0, count($materialIds), '?'));
        $params = $materialIds;

        $query = "SELECT si.*, cs.name as site_name, cs.code as site_code,
                         m.name as material_name, mu.abbreviation as unit_abbr
                  FROM stock_items si
                  JOIN construction_sites cs ON si.construction_site_id = cs.id
                  JOIN materials m ON si.material_id = m.id
                  LEFT JOIN measurement_units mu ON m.unit_id = mu.id
                  WHERE si.material_id IN ({$placeholders}) AND si.quantity > 0";

        if ($targetSiteId) {
            $query .= " AND si.construction_site_id != ?";
            $params[] = $targetSiteId;
        }

        $query .= " ORDER BY si.material_id, si.quantity DESC";

        $results = Database::fetchAll($query, $params);

        // Agrupar por material_id
        $grouped = [];
        foreach ($results as $row) {
            $grouped[$row['material_id']][] = $row;
        }

        // Também verificar estoque na obra destino
        if ($targetSiteId) {
            $localResults = Database::fetchAll(
                "SELECT si.*, cs.name as site_name, cs.code as site_code,
                        m.name as material_name, mu.abbreviation as unit_abbr
                 FROM stock_items si
                 JOIN construction_sites cs ON si.construction_site_id = cs.id
                 JOIN materials m ON si.material_id = m.id
                 LEFT JOIN measurement_units mu ON m.unit_id = mu.id
                 WHERE si.material_id IN ({$placeholders}) AND si.construction_site_id = ? AND si.quantity > 0",
                array_merge($materialIds, [$targetSiteId])
            );

            foreach ($localResults as $row) {
                $grouped['local'][$row['material_id']] = $row;
            }
        }

        return $grouped;
    }

    /**
     * Debitar quantidade do estoque
     */
    public static function debit(int $id, float $quantity): bool
    {
        $item = self::find($id);
        if (!$item || $item['quantity'] < $quantity) return false;

        $newQty = $item['quantity'] - $quantity;
        self::updateById($id, ['quantity' => $newQty, 'updated_at' => date('Y-m-d H:i:s')]);
        return true;
    }

    /**
     * Creditar quantidade no estoque
     */
    public static function credit(int $id, float $quantity): void
    {
        $item = self::find($id);
        if (!$item) return;

        $newQty = $item['quantity'] + $quantity;
        self::updateById($id, ['quantity' => $newQty, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Buscar ou criar item de estoque
     */
    public static function findOrCreate(int $materialId, int $siteId): int
    {
        $existing = self::findByMaterialAndSite($materialId, $siteId);
        if ($existing) return $existing['id'];

        return self::create([
            'material_id' => $materialId,
            'construction_site_id' => $siteId,
            'quantity' => 0,
            'min_quantity' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
