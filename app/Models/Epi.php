<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Epi extends Model
{
    protected static string $table = 'epis';

    public static function allActive(): array
    {
        return Database::fetchAll("SELECT * FROM epis WHERE active = 1 ORDER BY name ASC");
    }
}
