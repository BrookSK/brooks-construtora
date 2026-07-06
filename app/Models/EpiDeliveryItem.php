<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class EpiDeliveryItem extends Model
{
    protected static string $table = 'epi_delivery_items';

    public static function forDelivery(int $deliveryId): array
    {
        return Database::fetchAll("SELECT * FROM epi_delivery_items WHERE delivery_id = ?", [$deliveryId]);
    }

    /**
     * EPIs recebidos por um colaborador (por documento), com dados da entrega,
     * data da última substituição e total de substituições, para cálculo de
     * elegibilidade e numeração das trocas.
     */
    public static function activeForWorker(string $document): array
    {
        return Database::fetchAll(
            "SELECT i.*, d.worker_name, d.worker_document,
                    (SELECT COUNT(*) FROM epi_replacements r WHERE r.delivery_item_id = i.id) AS replacement_count,
                    (SELECT MAX(r.created_at) FROM epi_replacements r WHERE r.delivery_item_id = i.id) AS last_replaced_at
             FROM epi_delivery_items i
             INNER JOIN epi_deliveries d ON d.id = i.delivery_id
             WHERE d.worker_document = ?
             ORDER BY i.delivered_at DESC",
            [$document]
        );
    }
}
