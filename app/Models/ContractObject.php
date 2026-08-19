<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class ContractObject extends Model
{
    protected static string $table = 'contract_objects';

    /**
     * Retorna os objetos gerados para um briefing, do mais recente ao mais antigo.
     */
    public static function allByBriefing(int $briefingId): array
    {
        return Database::fetchAll(
            "SELECT co.*, ct.name AS template_name
             FROM contract_objects co
             LEFT JOIN contract_templates ct ON co.contract_template_id = ct.id
             WHERE co.briefing_id = ?
             ORDER BY co.created_at DESC",
            [$briefingId]
        );
    }

    /**
     * Retorna o objeto mais recente de um briefing.
     */
    public static function latestByBriefing(int $briefingId): ?array
    {
        return Database::fetch(
            "SELECT co.*, ct.name AS template_name
             FROM contract_objects co
             LEFT JOIN contract_templates ct ON co.contract_template_id = ct.id
             WHERE co.briefing_id = ?
             ORDER BY co.created_at DESC
             LIMIT 1",
            [$briefingId]
        );
    }

    /**
     * Aprova um objeto e registra quem aprovou.
     */
    public static function approve(int $id, int $userId): void
    {
        Database::update(
            'contract_objects',
            ['status' => 'approved', 'approved_by' => $userId, 'approved_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$id]
        );
    }
}
