<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class WeeklyMaterialManager extends Model
{
    protected static string $table = 'weekly_material_managers';

    /**
     * Lista todos os gerentes ativos
     */
    public static function allActive(): array
    {
        return Database::fetchAll(
            "SELECT wm.*, cs.name as construction_site_name, cs.code as construction_site_code
             FROM weekly_material_managers wm
             LEFT JOIN construction_sites cs ON wm.construction_site_id = cs.id
             WHERE wm.active = 1
             ORDER BY wm.name ASC"
        );
    }

    /**
     * Lista todos (incluindo inativos)
     */
    public static function allWithSite(): array
    {
        return Database::fetchAll(
            "SELECT wm.*, cs.name as construction_site_name, cs.code as construction_site_code
             FROM weekly_material_managers wm
             LEFT JOIN construction_sites cs ON wm.construction_site_id = cs.id
             ORDER BY wm.active DESC, wm.name ASC"
        );
    }
}
