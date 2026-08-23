<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

/**
 * Log de auditoria da rotina de Lista Semanal (PARTE 31).
 * Registra apenas eventos de controle gerencial da rotina.
 * Os dados operacionais permanecem no Pedido (fonte única da verdade).
 */
class WeeklyMaterialLog extends Model
{
    protected static string $table = 'weekly_material_logs';

    const ACTION_WEEK_CREATED = 'week_created';
    const ACTION_LINK_GENERATED = 'link_generated';
    const ACTION_LINK_SENT = 'link_sent';
    const ACTION_REMINDER_SENT = 'reminder_sent';
    const ACTION_FORM_OPENED = 'form_opened';
    const ACTION_FORM_SUBMITTED = 'form_submitted';
    const ACTION_ORDER_CREATE_ATTEMPT = 'order_create_attempt';
    const ACTION_ORDER_CREATED = 'order_created';
    const ACTION_ORDER_FAILED = 'order_failed';
    const ACTION_MARKED_OVERDUE = 'marked_overdue';

    public static function record(
        string $action,
        ?int $requestId = null,
        ?string $description = null,
        ?string $weekStart = null,
        ?int $orderId = null
    ): int {
        return self::create([
            'request_id' => $requestId,
            'week_start' => $weekStart,
            'action' => $action,
            'description' => $description,
            'order_id' => $orderId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Logs de uma solicitação específica
     */
    public static function getByRequest(int $requestId): array
    {
        return Database::fetchAll(
            "SELECT * FROM weekly_material_logs WHERE request_id = ? ORDER BY created_at ASC, id ASC",
            [$requestId]
        );
    }

    /**
     * Logs de uma semana
     */
    public static function getByWeek(string $weekStart): array
    {
        return Database::fetchAll(
            "SELECT * FROM weekly_material_logs WHERE week_start = ? ORDER BY created_at DESC, id DESC",
            [$weekStart]
        );
    }
}
