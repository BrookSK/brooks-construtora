<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Material extends Model
{
    protected static string $table = 'materials';

    public static function allActive(string $orderBy = 'name ASC'): array
    {
        return Database::fetchAll(
            "SELECT m.*, mc.name as category_name, mu.name as unit_name, mu.abbreviation as unit_abbr 
             FROM materials m
             LEFT JOIN material_categories mc ON m.category_id = mc.id
             LEFT JOIN measurement_units mu ON m.unit_id = mu.id
             WHERE m.active = 1 
             ORDER BY {$orderBy}"
        );
    }

    public static function search(string $term): array
    {
        return Database::fetchAll(
            "SELECT m.*, mc.name as category_name, mu.name as unit_name, mu.abbreviation as unit_abbr 
             FROM materials m
             LEFT JOIN material_categories mc ON m.category_id = mc.id
             LEFT JOIN measurement_units mu ON m.unit_id = mu.id
             WHERE m.active = 1 AND (m.name LIKE ? OR m.specification LIKE ? OR m.classification LIKE ?)
             ORDER BY m.name ASC",
            ["%{$term}%", "%{$term}%", "%{$term}%"]
        );
    }

    public static function allWithRelations(string $orderBy = 'name ASC'): array
    {
        return Database::fetchAll(
            "SELECT m.*, mc.name as category_name, mu.name as unit_name, mu.abbreviation as unit_abbr 
             FROM materials m
             LEFT JOIN material_categories mc ON m.category_id = mc.id
             LEFT JOIN measurement_units mu ON m.unit_id = mu.id
             ORDER BY {$orderBy}"
        );
    }
}
