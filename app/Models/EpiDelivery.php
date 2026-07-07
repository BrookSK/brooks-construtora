<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class EpiDelivery extends Model
{
    protected static string $table = 'epi_deliveries';

    /**
     * Lista de destinatários distintos que já receberam EPIs.
     *
     * @param string $recipientType  'worker' (padrão) ou 'third_party'
     */
    public static function distinctWorkers(string $recipientType = 'worker'): array
    {
        return Database::fetchAll(
            "SELECT worker_document, MAX(worker_name) AS worker_name, MAX(worker_role) AS worker_role
             FROM epi_deliveries
             WHERE recipient_type = ?
             GROUP BY worker_document
             ORDER BY worker_name ASC",
            [$recipientType]
        );
    }

    /**
     * Busca destinatários por nome ou documento (para autocomplete).
     * Retorna o registro mais recente de cada destinatário (por documento).
     *
     * @param string $recipientType  'worker' (padrão) ou 'third_party'
     */
    public static function searchWorkers(string $term, string $recipientType = 'worker'): array
    {
        $like = '%' . $term . '%';
        return Database::fetchAll(
            "SELECT worker_document, worker_name, worker_role
             FROM epi_deliveries d
             WHERE (worker_name LIKE ? OR worker_document LIKE ?)
               AND recipient_type = ?
               AND created_at = (
                   SELECT MAX(created_at) FROM epi_deliveries d2
                   WHERE d2.worker_document = d.worker_document AND d2.recipient_type = d.recipient_type
               )
             GROUP BY worker_document, worker_name, worker_role
             ORDER BY worker_name ASC
             LIMIT 15",
            [$like, $like, $recipientType]
        );
    }
}
