<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class ContractorCompany extends Model
{
    protected static string $table = 'contractor_companies';

    public static function allActive(): array
    {
        return Database::fetchAll(
            "SELECT * FROM contractor_companies WHERE active = 1 ORDER BY company_name ASC"
        );
    }

    public static function search(string $term): array
    {
        $like = '%' . $term . '%';
        return Database::fetchAll(
            "SELECT * FROM contractor_companies WHERE active = 1 AND (company_name LIKE ? OR cnpj LIKE ?) ORDER BY company_name ASC LIMIT 20",
            [$like, $like]
        );
    }
}
