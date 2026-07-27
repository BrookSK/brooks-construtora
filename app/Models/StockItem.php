<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class StockItem extends Model
{
    protected static string $table = 'stock_items';

    /**
     * Lista estoque por depósito
     */
    public static function getByLocation(int $locationId): array
    {
        return Database::fetchAll(
            "SELECT si.*, m.name as material_name, m.specification, m.classification,
                    mu.name as unit_name, mu.abbreviation as unit_abbr,
                    mc.name as category_name, sl.name as location_name, sl.code as location_code
             FROM stock_items si
             JOIN materials m ON si.material_id = m.id
             LEFT JOIN measurement_units mu ON m.unit_id = mu.id
             LEFT JOIN material_categories mc ON m.category_id = mc.id
             LEFT JOIN stock_locations sl ON si.stock_location_id = sl.id
             WHERE si.stock_location_id = ?
             ORDER BY m.name ASC",
            [$locationId]
        );
    }

    /**
     * Lista todo o estoque com filtros
     */
    public static function allWithRelations(?int $locationId = null): array
    {
        $where = '1=1';
        $params = [];

        if ($locationId) {
            $where .= ' AND si.stock_location_id = ?';
            $params[] = $locationId;
        }

        return Database::fetchAll(
            "SELECT si.*, m.name as material_name, m.specification, m.classification,
                    mu.name as unit_name, mu.abbreviation as unit_abbr,
                    mc.name as category_name, 
                    sl.name as location_name, sl.code as location_code,
                    cs.name as site_name, cs.code as site_code
             FROM stock_items si
             JOIN materials m ON si.material_id = m.id
             LEFT JOIN measurement_units mu ON m.unit_id = mu.id
             LEFT JOIN material_categories mc ON m.category_id = mc.id
             LEFT JOIN stock_locations sl ON si.stock_location_id = sl.id
             LEFT JOIN construction_sites cs ON sl.construction_site_id = cs.id
             WHERE {$where}
             ORDER BY sl.name ASC, m.name ASC",
            $params
        );
    }

    /**
     * Busca item específico: material + depósito
     */
    public static function findByMaterialAndLocation(int $materialId, int $locationId): ?array
    {
        return Database::fetch(
            "SELECT si.*, m.name as material_name, sl.name as location_name
             FROM stock_items si
             JOIN materials m ON si.material_id = m.id
             LEFT JOIN stock_locations sl ON si.stock_location_id = sl.id
             WHERE si.material_id = ? AND si.stock_location_id = ?",
            [$materialId, $locationId]
        );
    }

    /**
     * Compat: busca por material + obra (busca o depósito vinculado à obra)
     */
    public static function findByMaterialAndSite(int $materialId, int $siteId): ?array
    {
        return Database::fetch(
            "SELECT si.*, m.name as material_name, sl.name as location_name, sl.id as stock_location_id
             FROM stock_items si
             JOIN materials m ON si.material_id = m.id
             JOIN stock_locations sl ON si.stock_location_id = sl.id
             WHERE si.material_id = ? AND sl.construction_site_id = ?",
            [$materialId, $siteId]
        );
    }

    /**
     * Busca material em TODOS os estoques (exceto um depósito específico)
     */
    public static function findMaterialInAllStocks(int $materialId, ?int $excludeLocationId = null): array
    {
        $where = 'si.material_id = ? AND si.quantity > 0';
        $params = [$materialId];

        if ($excludeLocationId) {
            $where .= ' AND si.stock_location_id != ?';
            $params[] = $excludeLocationId;
        }

        return Database::fetchAll(
            "SELECT si.*, sl.name as location_name, sl.code as location_code,
                    sl.construction_site_id,
                    cs.name as site_name, cs.code as site_code,
                    m.name as material_name, mu.abbreviation as unit_abbr
             FROM stock_items si
             JOIN stock_locations sl ON si.stock_location_id = sl.id
             LEFT JOIN construction_sites cs ON sl.construction_site_id = cs.id
             JOIN materials m ON si.material_id = m.id
             LEFT JOIN measurement_units mu ON m.unit_id = mu.id
             WHERE {$where}
             ORDER BY si.quantity DESC",
            $params
        );
    }

    /**
     * Verifica disponibilidade de múltiplos materiais em todos os depósitos
     * Retorna array indexado por material_id
     */
    public static function checkAvailability(array $materialIds, ?int $targetSiteId = null): array
    {
        if (empty($materialIds)) return [];

        $placeholders = implode(',', array_fill(0, count($materialIds), '?'));
        $params = $materialIds;

        $query = "SELECT si.*, sl.name as location_name, sl.code as location_code,
                         sl.construction_site_id,
                         cs.name as site_name, cs.code as site_code,
                         m.name as material_name, mu.abbreviation as unit_abbr
                  FROM stock_items si
                  JOIN stock_locations sl ON si.stock_location_id = sl.id
                  LEFT JOIN construction_sites cs ON sl.construction_site_id = cs.id
                  JOIN materials m ON si.material_id = m.id
                  LEFT JOIN measurement_units mu ON m.unit_id = mu.id
                  WHERE si.material_id IN ({$placeholders}) AND si.quantity > 0";

        if ($targetSiteId) {
            // Excluir o depósito vinculado à obra destino
            $query .= " AND (sl.construction_site_id != ? OR sl.construction_site_id IS NULL)";
            $params[] = $targetSiteId;
        }

        $query .= " ORDER BY si.material_id, si.quantity DESC";

        $results = Database::fetchAll($query, $params);

        // Agrupar por material_id
        $grouped = [];
        foreach ($results as $row) {
            $grouped[$row['material_id']][] = $row;
        }

        // Também verificar estoque no depósito da obra destino
        if ($targetSiteId) {
            $localResults = Database::fetchAll(
                "SELECT si.*, sl.name as location_name, sl.code as location_code,
                        sl.construction_site_id,
                        cs.name as site_name, cs.code as site_code,
                        m.name as material_name, mu.abbreviation as unit_abbr
                 FROM stock_items si
                 JOIN stock_locations sl ON si.stock_location_id = sl.id
                 LEFT JOIN construction_sites cs ON sl.construction_site_id = cs.id
                 JOIN materials m ON si.material_id = m.id
                 LEFT JOIN measurement_units mu ON m.unit_id = mu.id
                 WHERE si.material_id IN ({$placeholders}) AND sl.construction_site_id = ? AND si.quantity > 0",
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
     * Buscar ou criar item de estoque por depósito
     */
    public static function findOrCreate(int $materialId, int $locationId): int
    {
        $existing = self::findByMaterialAndLocation($materialId, $locationId);
        if ($existing) return $existing['id'];

        return self::create([
            'material_id' => $materialId,
            'stock_location_id' => $locationId,
            'quantity' => 0,
            'min_quantity' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Compat: Buscar ou criar por obra (encontra/cria o depósito da obra)
     */
    public static function findOrCreateBySite(int $materialId, int $siteId): int
    {
        // Buscar depósito vinculado à obra
        $location = StockLocation::findBySite($siteId);
        if (!$location) {
            // Criar depósito automaticamente para a obra
            $site = \App\Models\ConstructionSite::find($siteId);
            $locationId = StockLocation::create([
                'name' => 'Estoque ' . ($site['name'] ?? 'Obra'),
                'code' => StockLocation::generateCode(),
                'construction_site_id' => $siteId,
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $locationId = $location['id'];
        }

        return self::findOrCreate($materialId, $locationId);
    }
}
