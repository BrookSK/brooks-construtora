<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class VehicleUsage extends Model
{
    protected static string $table = 'vehicle_usage';

    /**
     * Lista todos os registros ordenados por data desc
     */
    public static function allOrdered(): array
    {
        return Database::fetchAll(
            "SELECT * FROM vehicle_usage ORDER BY pickup_date DESC, pickup_time DESC"
        );
    }

    /**
     * Verifica se o veículo está em uso (último registro sem devolução)
     */
    public static function getCurrentUsage(): ?array
    {
        return Database::fetch(
            "SELECT * FROM vehicle_usage WHERE return_date IS NULL ORDER BY pickup_date DESC, pickup_time DESC LIMIT 1"
        );
    }

    /**
     * Verifica se o veículo está disponível
     */
    public static function isAvailable(): bool
    {
        return self::getCurrentUsage() === null;
    }

    /**
     * Registrar retirada
     */
    public static function registerPickup(array $data): int
    {
        return self::create([
            'driver_name' => $data['driver_name'],
            'registered_by' => $data['registered_by'],
            'registered_by_user_id' => $data['registered_by_user_id'] ?? null,
            'pickup_date' => $data['pickup_date'],
            'pickup_time' => $data['pickup_time'],
            'pickup_location' => $data['pickup_location'],
            'pickup_km' => (int) $data['pickup_km'],
            'destination' => $data['destination'],
            'pickup_notes' => $data['pickup_notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Registrar devolução
     */
    public static function registerReturn(int $id, array $data): void
    {
        self::updateById($id, [
            'return_date' => $data['return_date'],
            'return_time' => $data['return_time'],
            'return_km' => (int) $data['return_km'],
            'return_notes' => $data['return_notes'] ?? null,
            'returned_by' => $data['returned_by'],
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Último KM registrado (para validação)
     */
    public static function getLastKm(): ?int
    {
        $last = Database::fetch(
            "SELECT COALESCE(return_km, pickup_km) as last_km FROM vehicle_usage ORDER BY id DESC LIMIT 1"
        );
        return $last ? (int) $last['last_km'] : null;
    }

    /**
     * Estatísticas resumidas
     */
    public static function getStats(): array
    {
        $total = self::count();
        $current = self::getCurrentUsage();
        $lastKm = self::getLastKm();

        // KM total percorrido (soma dos retornos - retiradas)
        $kmTotal = Database::fetch(
            "SELECT SUM(return_km - pickup_km) as total_km FROM vehicle_usage WHERE return_km IS NOT NULL"
        );

        return [
            'total_trips' => $total,
            'current_usage' => $current,
            'last_km' => $lastKm,
            'total_km' => (int) ($kmTotal['total_km'] ?? 0),
            'is_available' => $current === null,
        ];
    }
}
