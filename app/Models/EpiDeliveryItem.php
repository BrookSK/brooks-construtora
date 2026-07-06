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
     * EPIs ativos (não substituídos) recebidos por um colaborador (por documento),
     * incluindo dados da entrega para cálculo de elegibilidade.
     */
    public static function activeForWorker(string $document): array
    {
        return Database::fetchAll(
            "SELECT i.*, d.worker_name, d.worker_document
             FROM epi_delivery_items i
             INNER JOIN epi_deliveries d ON d.id = i.delivery_id
             WHERE d.worker_document = ? AND i.replaced = 0
             ORDER BY i.delivered_at DESC",
            [$document]
        );
    }
}
