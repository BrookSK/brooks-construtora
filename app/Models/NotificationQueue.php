<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class NotificationQueue extends Model
{
    protected static string $table = 'notification_queue';

    /**
     * Enfileirar envio de e-mail
     */
    public static function queueEmail(string $to, string $subject, string $body): int
    {
        return self::create([
            'type' => 'email',
            'status' => 'pending',
            'to_email' => $to,
            'subject' => $subject,
            'body' => $body,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Enfileirar envio de webhook
     */
    public static function queueWebhook(string $url, array $payload): int
    {
        return self::create([
            'type' => 'webhook',
            'status' => 'pending',
            'webhook_url' => $url,
            'webhook_payload' => json_encode($payload),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Buscar pendentes para processar
     */
    public static function getPending(int $limit = 20): array
    {
        return Database::fetchAll(
            "SELECT * FROM notification_queue 
             WHERE status = 'pending' AND attempts < max_attempts AND scheduled_at <= NOW()
             ORDER BY created_at ASC 
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Marcar como processando
     */
    public static function markProcessing(int $id): void
    {
        self::updateById($id, ['status' => 'processing']);
    }

    /**
     * Marcar como enviado
     */
    public static function markSent(int $id): void
    {
        self::updateById($id, ['status' => 'sent', 'sent_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Marcar como falha
     */
    public static function markFailed(int $id, string $error): void
    {
        Database::query(
            "UPDATE notification_queue SET status = IF(attempts + 1 >= max_attempts, 'failed', 'pending'), attempts = attempts + 1, last_error = ? WHERE id = ?",
            [$error, $id]
        );
    }

    /**
     * Contar pendentes
     */
    public static function countPending(): int
    {
        return self::count("status = 'pending'");
    }
}
