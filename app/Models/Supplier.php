<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Supplier extends Model
{
    protected static string $table = 'suppliers';

    public static function allActive(string $orderBy = 'name ASC'): array
    {
        return Database::fetchAll("SELECT * FROM suppliers WHERE active = 1 ORDER BY {$orderBy}");
    }

    public static function search(string $term): array
    {
        return Database::fetchAll(
            "SELECT * FROM suppliers WHERE active = 1 AND (name LIKE ? OR cnpj LIKE ? OR email LIKE ?) ORDER BY name ASC",
            ["%{$term}%", "%{$term}%", "%{$term}%"]
        );
    }
}
