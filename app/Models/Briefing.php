<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Briefing extends Model
{
    protected static string $table = 'briefings';

    /**
     * Retorna o briefing mais recente de um projeto, com dados do projeto anexados.
     */
    public static function findByProject(int $clientProjectId): ?array
    {
        return Database::fetch(
            "SELECT b.*, cp.client_name, cp.client_document, cp.client_phone, cp.client_email,
                    cp.project_type, cp.project_address, cp.project_cep, cp.project_city,
                    cp.project_goal, cp.project_area
             FROM briefings b
             JOIN clients_projects cp ON b.client_project_id = cp.id
             WHERE b.client_project_id = ?
             ORDER BY b.id DESC
             LIMIT 1",
            [$clientProjectId]
        );
    }

    /**
     * Retorna todos os briefings de um projeto com contagem de objetos gerados.
     */
    public static function allByProject(int $clientProjectId): array
    {
        return Database::fetchAll(
            "SELECT b.*,
                    (SELECT COUNT(*) FROM contract_objects WHERE briefing_id = b.id) AS objects_count
             FROM briefings b
             WHERE b.client_project_id = ?
             ORDER BY b.id DESC",
            [$clientProjectId]
        );
    }
}
