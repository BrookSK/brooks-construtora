<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class ContractBaseTemplate extends Model
{
    protected static string $table = 'contract_base_templates';

    public static function allActive(): array
    {
        return Database::fetchAll(
            "SELECT * FROM contract_base_templates WHERE active = 1 ORDER BY is_default DESC, name ASC"
        );
    }

    public static function getDefault(): ?array
    {
        $default = Database::fetch(
            "SELECT * FROM contract_base_templates WHERE is_default = 1 AND active = 1 ORDER BY id ASC LIMIT 1"
        );
        return $default ?: Database::fetch(
            "SELECT * FROM contract_base_templates WHERE active = 1 ORDER BY id ASC LIMIT 1"
        );
    }

    /**
     * Escolhe o modelo-base pelo campo "Contrato" da capa do orçamento
     * (Execução / Administração / Gerenciamento). Cai no padrão se não casar.
     */
    public static function pickByType(?string $contractType): ?array
    {
        $type = self::normalizeType($contractType);
        if ($type !== '') {
            $row = Database::fetch(
                "SELECT * FROM contract_base_templates WHERE contract_type = ? AND active = 1 ORDER BY is_default DESC, id ASC LIMIT 1",
                [$type]
            );
            if ($row) {
                return $row;
            }
        }
        return self::getDefault();
    }

    public static function normalizeType(?string $raw): string
    {
        $s = mb_strtolower(trim((string)$raw));
        if ($s === '') {
            return '';
        }
        if (str_contains($s, 'admin')) {
            return 'administracao';
        }
        if (str_contains($s, 'gerenc')) {
            return 'gerenciamento';
        }
        if (str_contains($s, 'exec') || str_contains($s, 'empreit')) {
            return 'execucao';
        }
        return '';
    }
}
