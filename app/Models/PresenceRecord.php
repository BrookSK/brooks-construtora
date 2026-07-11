<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class PresenceRecord extends Model
{
    protected static string $table = 'presence_records';

    /**
     * Consulta com filtros opcionais: obra, empresa, prestador, período.
     */
    public static function filter(array $filters = []): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['site'])) {
            $where[] = 'site LIKE ?';
            $params[] = '%' . $filters['site'] . '%';
        }
        if (!empty($filters['company'])) {
            $where[] = 'company LIKE ?';
            $params[] = '%' . $filters['company'] . '%';
        }
        if (!empty($filters['provider'])) {
            $where[] = 'provider_name LIKE ?';
            $params[] = '%' . $filters['provider'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'presence_date >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'presence_date <= ?';
            $params[] = $filters['date_to'];
        }

        $sql = "SELECT * FROM presence_records WHERE " . implode(' AND ', $where)
             . " ORDER BY presence_date DESC, presence_time DESC LIMIT 500";
        return Database::fetchAll($sql, $params);
    }

    /**
     * Lista de obras distintas (para filtro).
     */
    public static function distinctSites(): array
    {
        $rows = Database::fetchAll("SELECT DISTINCT site FROM presence_records WHERE site != '' ORDER BY site ASC");
        return array_column($rows, 'site');
    }
}
