<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class ClientProject extends Model
{
    protected static string $table = 'clients_projects';

    /**
     * Retorna projetos com o briefing mais recente associado e status do contrato.
     */
    public static function allWithBriefing(int $limit = 50, string $statusFilter = ''): array
    {
        $sql = "SELECT cp.*,
                    b.id AS briefing_id,
                    b.briefing_summary,
                    b.contract_value,
                    (SELECT COUNT(*) FROM contract_objects co
                     JOIN briefings bb ON co.briefing_id = bb.id
                     WHERE bb.client_project_id = cp.id) AS objects_count,
                    (SELECT co2.status FROM contract_objects co2
                     JOIN briefings bb2 ON co2.briefing_id = bb2.id
                     WHERE bb2.client_project_id = cp.id
                     ORDER BY co2.id DESC LIMIT 1) AS contract_status,
                    (SELECT co3.created_at FROM contract_objects co3
                     JOIN briefings bb3 ON co3.briefing_id = bb3.id
                     WHERE bb3.client_project_id = cp.id
                     ORDER BY co3.id DESC LIMIT 1) AS last_object_date
             FROM clients_projects cp
             LEFT JOIN briefings b ON b.id = (
                 SELECT id FROM briefings WHERE client_project_id = cp.id ORDER BY id DESC LIMIT 1
             )";

        $params = [];

        if ($statusFilter === 'approved') {
            $sql .= " HAVING contract_status = 'approved'";
        } elseif ($statusFilter === 'pending') {
            $sql .= " HAVING contract_status = 'generated'";
        } elseif ($statusFilter === 'no_object') {
            $sql .= " HAVING contract_status IS NULL";
        }

        $sql .= " ORDER BY cp.created_at DESC LIMIT ?";
        $params[] = $limit;

        return Database::fetchAll($sql, $params);
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
