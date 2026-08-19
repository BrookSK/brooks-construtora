<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class ContractTemplate extends Model
{
    protected static string $table = 'contract_templates';

    /**
     * Retorna o modelo marcado como padrão, ou o primeiro disponível.
     */
    public static function getDefault(): ?array
    {
        $default = Database::fetch(
            "SELECT * FROM contract_templates WHERE is_default = 1 ORDER BY id ASC LIMIT 1"
        );
        if ($default) {
            return $default;
        }
        return Database::fetch("SELECT * FROM contract_templates ORDER BY id ASC LIMIT 1");
    }

    /**
     * Define um modelo como padrão e remove o flag dos demais.
     */
    public static function setDefault(int $id): void
    {
        Database::update('contract_templates', ['is_default' => 0], '1 = 1');
        Database::update('contract_templates', ['is_default' => 1], 'id = ?', [$id]);
    }
}
