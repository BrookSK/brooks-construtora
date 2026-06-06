<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Setting extends Model
{
    protected static string $table = 'settings';

    public static function get(string $key, $default = null): ?string
    {
        $result = Database::fetch("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
        return $result ? $result['setting_value'] : $default;
    }

    public static function set(string $key, ?string $value): void
    {
        $existing = Database::fetch("SELECT id FROM settings WHERE setting_key = ?", [$key]);

        if ($existing) {
            Database::update('settings', ['setting_value' => $value, 'updated_at' => date('Y-m-d H:i:s')], 'setting_key = ?', [$key]);
        } else {
            Database::insert('settings', [
                'setting_key' => $key,
                'setting_value' => $value,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public static function getGroup(string $prefix): array
    {
        $results = Database::fetchAll("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE ?", [$prefix . '%']);
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    public static function setMultiple(array $data): void
    {
        foreach ($data as $key => $value) {
            self::set($key, $value);
        }
    }
}
