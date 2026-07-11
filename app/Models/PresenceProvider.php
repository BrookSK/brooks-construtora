<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class PresenceProvider extends Model
{
    protected static string $table = 'presence_providers';

    public static function allActive(): array
    {
        return Database::fetchAll("SELECT * FROM presence_providers WHERE active = 1 ORDER BY name ASC");
    }

    /**
     * Busca por nome, documento ou empresa (autocomplete).
     */
    public static function search(string $term): array
    {
        $like = '%' . $term . '%';
        return Database::fetchAll(
            "SELECT * FROM presence_providers
             WHERE active = 1 AND (name LIKE ? OR document LIKE ? OR company LIKE ?)
             ORDER BY name ASC
             LIMIT 15",
            [$like, $like, $like]
        );
    }
}
