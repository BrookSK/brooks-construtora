<?php

namespace App\Core;

abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    public static function find(int $id): ?array
    {
        $table = static::$table;
        $pk = static::$primaryKey;
        return Database::fetch("SELECT * FROM {$table} WHERE {$pk} = ?", [$id]);
    }

    public static function all(string $orderBy = 'id DESC'): array
    {
        $table = static::$table;
        return Database::fetchAll("SELECT * FROM {$table} ORDER BY {$orderBy}");
    }

    public static function where(string $column, $value): array
    {
        $table = static::$table;
        return Database::fetchAll("SELECT * FROM {$table} WHERE {$column} = ?", [$value]);
    }

    public static function whereFirst(string $column, $value): ?array
    {
        $table = static::$table;
        return Database::fetch("SELECT * FROM {$table} WHERE {$column} = ?", [$value]);
    }

    public static function create(array $data): int
    {
        return Database::insert(static::$table, $data);
    }

    public static function updateById(int $id, array $data): int
    {
        $pk = static::$primaryKey;
        return Database::update(static::$table, $data, "{$pk} = ?", [$id]);
    }

    public static function deleteById(int $id): int
    {
        $pk = static::$primaryKey;
        return Database::delete(static::$table, "{$pk} = ?", [$id]);
    }

    public static function count(string $where = '1=1', array $params = []): int
    {
        $table = static::$table;
        $result = Database::fetch("SELECT COUNT(*) as total FROM {$table} WHERE {$where}", $params);
        return (int) ($result['total'] ?? 0);
    }
}
