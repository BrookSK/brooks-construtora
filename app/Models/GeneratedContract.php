<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class GeneratedContract extends Model
{
    protected static string $table = 'generated_contracts';

    /**
     * Lista contratos agrupados por projeto (mais recente primeiro).
     */
    public static function allRecent(int $limit = 100): array
    {
        return Database::fetchAll(
            "SELECT gc.*, u.name AS created_by_name, bt.name AS template_name
             FROM generated_contracts gc
             LEFT JOIN users u ON gc.created_by = u.id
             LEFT JOIN contract_base_templates bt ON gc.base_template_id = bt.id
             ORDER BY gc.updated_at DESC, gc.id DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Todas as versões de um mesmo projeto.
     */
    public static function versionsByProject(string $projectCode): array
    {
        return Database::fetchAll(
            "SELECT gc.*, u.name AS created_by_name
             FROM generated_contracts gc
             LEFT JOIN users u ON gc.created_by = u.id
             WHERE gc.project_code = ?
             ORDER BY gc.version DESC",
            [$projectCode]
        );
    }

    /**
     * Próximo número de versão para um projeto (nunca sobrescreve).
     */
    public static function nextVersion(?string $projectCode): int
    {
        if (empty($projectCode)) {
            return 1;
        }
        $row = Database::fetch(
            "SELECT MAX(version) AS v FROM generated_contracts WHERE project_code = ?",
            [$projectCode]
        );
        return (int)($row['v'] ?? 0) + 1;
    }

    public static function findWithMeta(int $id): ?array
    {
        return Database::fetch(
            "SELECT gc.*, u.name AS created_by_name, bt.name AS template_name, bt.contract_type
             FROM generated_contracts gc
             LEFT JOIN users u ON gc.created_by = u.id
             LEFT JOIN contract_base_templates bt ON gc.base_template_id = bt.id
             WHERE gc.id = ?",
            [$id]
        );
    }
}
