<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class ClientProject extends Model
{
    protected static string $table = 'clients_projects';

    /**
     * Retorna projetos com o briefing mais recente associado.
     */
    public static function allWithBriefing(int $limit = 50): array
    {
        return Database::fetchAll(
            "SELECT cp.*,
                    b.id AS briefing_id,
                    b.briefing_summary,
                    b.contract_value,
                    (SELECT COUNT(*) FROM contract_objects co
                     JOIN briefings bb ON co.briefing_id = bb.id
                     WHERE bb.client_project_id = cp.id) AS objects_count
             FROM clients_projects cp
             LEFT JOIN briefings b ON b.id = (
                 SELECT id FROM briefings WHERE client_project_id = cp.id ORDER BY id DESC LIMIT 1
             )
             ORDER BY cp.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Busca por nome ou documento do cliente.
     */
    public static function search(string $term): array
    {
        $like = '%' . $term . '%';
        return Database::fetchAll(
            "SELECT * FROM clients_projects
             WHERE client_name LIKE ? OR client_document LIKE ? OR client_email LIKE ?
             ORDER BY client_name ASC
             LIMIT 30",
            [$like, $like, $like]
        );
    }
}
