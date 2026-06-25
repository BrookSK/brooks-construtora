<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class PinUser extends Model
{
    protected static string $table = 'pin_users';

    public static function findByPin(string $pin): ?array
    {
        return Database::fetch("SELECT * FROM pin_users WHERE pin = ? AND active = 1", [$pin]);
    }

    public static function findBySessionToken(string $token): ?array
    {
        return Database::fetch(
            "SELECT * FROM pin_users WHERE session_token = ? AND session_expires_at > NOW() AND active = 1",
            [$token]
        );
    }

    public static function isPinAvailable(string $pin, ?int $excludeId = null): bool
    {
        $query = "SELECT id FROM pin_users WHERE pin = ?";
        $params = [$pin];
        if ($excludeId) { $query .= " AND id != ?"; $params[] = $excludeId; }
        return Database::fetch($query, $params) === null;
    }

    public static function createSession(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        self::updateById($userId, [
            'session_token' => $token,
            'session_expires_at' => $expiresAt,
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);
        return $token;
    }

    public static function hasPermission(array $user, string $requiredRole): bool
    {
        if ($user['role'] === 'all') return true;
        return $user['role'] === $requiredRole;
    }
}
