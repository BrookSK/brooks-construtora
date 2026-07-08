<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class PresenceSite extends Model
{
    protected static string $table = 'presence_sites';

    public static function allActive(): array
    {
        return Database::fetchAll("SELECT * FROM presence_sites WHERE active = 1 ORDER BY name ASC");
    }

    /**
     * Busca por nome (autocomplete).
     */
    public static function search(string $term): array
    {
        $like = '%' . $term . '%';
        return Database::fetchAll(
            "SELECT * FROM presence_sites
             WHERE active = 1 AND (name LIKE ? OR address LIKE ?)
             ORDER BY name ASC
             LIMIT 15",
            [$like, $like]
        );
    }

    public static function findByName(string $name): ?array
    {
        return Database::fetch("SELECT * FROM presence_sites WHERE name = ? AND active = 1", [$name]);
    }
}
