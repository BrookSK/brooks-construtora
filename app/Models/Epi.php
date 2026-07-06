<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Epi extends Model
{
    protected static string $table = 'epis';

    public static function allActive(): array
    {
        return Database::fetchAll("SELECT * FROM epis WHERE active = 1 ORDER BY category ASC, name ASC");
    }

    public static function distinctCategories(): array
    {
        $rows = Database::fetchAll(
            "SELECT DISTINCT category FROM epis WHERE category IS NOT NULL AND category != '' ORDER BY category ASC"
        );
        return array_column($rows, 'category');
    }
}
