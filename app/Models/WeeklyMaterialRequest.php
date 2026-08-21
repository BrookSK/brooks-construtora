<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class WeeklyMaterialRequest extends Model
{
    protected static string $table = 'weekly_material_requests';

    /**
     * Gerar token único
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Buscar por token
     */
    public static function findByToken(string $token): ?array
    {
        return Database::fetch(
            "SELECT wmr.*, pu.name as manager_name, pu.phone as manager_phone, pu.email as manager_email
             FROM weekly_material_requests wmr
             JOIN pin_users pu ON wmr.manager_id = pu.id
             WHERE wmr.token = ?",
            [$token]
        );
    }

    /**
     * Buscar registros de uma semana específica
     */
    public static function getByWeek(string $weekStart): array
    {
        return Database::fetchAll(
            "SELECT wmr.*, pu.name as manager_name, pu.phone as manager_phone, pu.email as manager_email
             FROM weekly_material_requests wmr
             JOIN pin_users pu ON wmr.manager_id = pu.id
             WHERE wmr.week_start = ?
             ORDER BY pu.name ASC",
            [$weekStart]
        );
    }

    /**
     * Buscar todas as semanas disponíveis (para listagem)
     */
    public static function getWeeks(): array
    {
        return Database::fetchAll(
            "SELECT week_start, 
                    COUNT(*) as total_managers,
                    SUM(CASE WHEN status = 'filled' THEN 1 ELSE 0 END) as filled_count,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_count
             FROM weekly_material_requests
             GROUP BY week_start
             ORDER BY week_start DESC"
        );
    }

    /**
     * Criar registros da semana para todos os gerentes ativos (pin_users com is_weekly_manager=1)
     */
    public static function createWeekRecords(string $weekStart): int
    {
        $managers = Database::fetchAll(
            "SELECT id, name, phone, email FROM pin_users WHERE active = 1 AND is_weekly_manager = 1 ORDER BY name ASC"
        );
        $created = 0;

        foreach ($managers as $manager) {
            // Verificar se já existe (usando manager_id = pin_user.id)
            $existing = Database::fetch(
                "SELECT id FROM weekly_material_requests WHERE manager_id = ? AND week_start = ?",
                [$manager['id'], $weekStart]
            );

            if (!$existing) {
                self::create([
                    'manager_id' => $manager['id'],
                    'week_start' => $weekStart,
                    'token' => self::generateToken(),
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $created++;
            }
        }

        return $created;
    }

    /**
     * Marcar como preenchido
     */
    public static function markFilled(int $id, ?string $notes = null, ?string $audioFilename = null): void
    {
        $data = [
            'status' => 'filled',
            'filled_at' => date('Y-m-d H:i:s'),
        ];
        if ($notes !== null) $data['notes'] = $notes;
        if ($audioFilename !== null) $data['audio_filename'] = $audioFilename;

        self::updateById($id, $data);
    }

    /**
     * Marcar pendentes antigos como overdue
     */
    public static function markOverdue(string $weekStart): int
    {
        return Database::update(
            self::$table,
            ['status' => 'overdue'],
            "week_start = ? AND status = 'pending'",
            [$weekStart]
        );
    }

    /**
     * Calcular segunda-feira da semana atual
     */
    public static function currentWeekStart(): string
    {
        $today = new \DateTime();
        $dayOfWeek = (int) $today->format('N'); // 1=Seg, 7=Dom
        $monday = clone $today;
        $monday->modify('-' . ($dayOfWeek - 1) . ' days');
        return $monday->format('Y-m-d');
    }

    /**
     * Calcular segunda-feira da próxima semana
     */
    public static function nextWeekStart(): string
    {
        $today = new \DateTime();
        $dayOfWeek = (int) $today->format('N');
        $nextMonday = clone $today;
        $nextMonday->modify('+' . (8 - $dayOfWeek) . ' days');
        return $nextMonday->format('Y-m-d');
    }

    /**
     * Itens de uma lista
     */
    public static function getItems(int $requestId): array
    {
        return Database::fetchAll(
            "SELECT * FROM weekly_material_request_items WHERE request_id = ? ORDER BY id ASC",
            [$requestId]
        );
    }

    /**
     * Salvar itens de uma lista
     */
    public static function saveItems(int $requestId, array $items): void
    {
        // Limpar itens existentes
        Database::delete('weekly_material_request_items', 'request_id = ?', [$requestId]);

        // Inserir novos
        foreach ($items as $item) {
            if (empty(trim($item['material_name'] ?? ''))) continue;
            Database::insert('weekly_material_request_items', [
                'request_id' => $requestId,
                'material_name' => trim($item['material_name']),
                'quantity' => (float) ($item['quantity'] ?? 1),
                'unit' => trim($item['unit'] ?? ''),
                'notes' => trim($item['notes'] ?? '') ?: null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
