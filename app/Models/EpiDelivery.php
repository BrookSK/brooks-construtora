<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class EpiDelivery extends Model
{
    protected static string $table = 'epi_deliveries';

    /**
     * Lista de colaboradores distintos que já receberam EPIs.
     */
    public static function distinctWorkers(): array
    {
        return Database::fetchAll(
            "SELECT worker_document, MAX(worker_name) AS worker_name, MAX(worker_role) AS worker_role
             FROM epi_deliveries
             GROUP BY worker_document
             ORDER BY worker_name ASC"
        );
    }
}
