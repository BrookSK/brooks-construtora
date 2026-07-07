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
     * data da última substituição, total de substituições e quantidade já
     * devolvida, para cálculo de elegibilidade, numeração das trocas e
     * quantidade disponível para devolução.
     *
     * @param string $document  CPF/Matrícula do destinatário
     * @param string $recipientType  'worker' (padrão) ou 'third_party'
     */
    public static function activeForWorker(string $document, string $recipientType = 'worker'): array
    {
        return Database::fetchAll(
            "SELECT i.*, d.worker_name, d.worker_document,
                    (SELECT COUNT(*) FROM epi_replacements r WHERE r.delivery_item_id = i.id) AS replacement_count,
                    (SELECT MAX(r.created_at) FROM epi_replacements r WHERE r.delivery_item_id = i.id) AS last_replaced_at,
                    COALESCE((SELECT SUM(rt.quantity) FROM epi_returns rt WHERE rt.delivery_item_id = i.id), 0) AS returned_quantity
             FROM epi_delivery_items i
             INNER JOIN epi_deliveries d ON d.id = i.delivery_id
             WHERE d.worker_document = ? AND d.recipient_type = ?
             ORDER BY i.delivered_at DESC",
            [$document, $recipientType]
        );
    }

    /**
     * Quantidade total já devolvida de um item de entrega.
     */
    public static function returnedQuantity(int $deliveryItemId): float
    {
        $row = Database::fetch(
            "SELECT COALESCE(SUM(quantity), 0) AS total FROM epi_returns WHERE delivery_item_id = ?",
            [$deliveryItemId]
        );
        return (float) ($row['total'] ?? 0);
    }
}
