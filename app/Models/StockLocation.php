<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class StockLocation extends Model
{
    protected static string $table = 'stock_locations';

    /**
     * Lista todos os estoques/depósitos ativos
     */
    public static function allActive(string $orderBy = 'name ASC'): array
    {
        return Database::fetchAll(
            "SELECT sl.*, cs.name as construction_site_name, cs.code as construction_site_code
             FROM stock_locations sl
             LEFT JOIN construction_sites cs ON sl.construction_site_id = cs.id
             WHERE sl.active = 1
             ORDER BY {$orderBy}"
        );
    }

    /**
     * Buscar com dados da obra vinculada
     */
    public static function findFull(int $id): ?array
    {
        return Database::fetch(
            "SELECT sl.*, cs.name as construction_site_name, cs.code as construction_site_code
             FROM stock_locations sl
             LEFT JOIN construction_sites cs ON sl.construction_site_id = cs.id
             WHERE sl.id = ?",
            [$id]
        );
    }

    /**
     * Buscar depósito vinculado a uma obra específica
     */
    public static function findBySite(int $siteId): ?array
    {
        return Database::fetch(
            "SELECT * FROM stock_locations WHERE construction_site_id = ? AND active = 1 LIMIT 1",
            [$siteId]
        );
    }

    /**
     * Buscar por nome
     */
    public static function search(string $term): array
    {
        return Database::fetchAll(
            "SELECT sl.*, cs.name as construction_site_name
             FROM stock_locations sl
             LEFT JOIN construction_sites cs ON sl.construction_site_id = cs.id
             WHERE sl.active = 1 AND (sl.name LIKE ? OR sl.code LIKE ?)
             ORDER BY sl.name ASC",
            ["%{$term}%", "%{$term}%"]
        );
    }

    /**
     * Gerar código sequencial (EST-000001)
     */
    public static function generateCode(): string
    {
        $last = Database::fetch("SELECT code FROM stock_locations WHERE code LIKE 'EST-%' ORDER BY id DESC LIMIT 1");
        if ($last && preg_match('/EST-(\d+)/', $last['code'], $m)) {
            $number = (int) $m[1] + 1;
        } else {
            $number = 1;
        }
        return 'EST-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}
