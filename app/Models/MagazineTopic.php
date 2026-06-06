<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class MagazineTopic extends Model
{
    protected static string $table = 'magazine_topics';

    public static function getPending(): array
    {
        return Database::fetchAll("SELECT * FROM magazine_topics WHERE used = 0 ORDER BY created_at ASC");
    }

    public static function markAsUsed(int $id): void
    {
        Database::update('magazine_topics', ['used' => 1, 'used_at' => date('Y-m-d H:i:s')], 'id = ?', [$id]);
    }
}
