<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Newsletter extends Model
{
    protected static string $table = 'newsletter_subscribers';

    public static function findByEmail(string $email): ?array
    {
        return Database::fetch("SELECT * FROM newsletter_subscribers WHERE email = ?", [$email]);
    }

    public static function subscribe(string $email, string $name = ''): bool
    {
        $existing = self::findByEmail($email);

        if ($existing) {
            return false; // Já inscrito
        }

        Database::insert('newsletter_subscribers', [
            'email' => $email,
            'name' => $name,
            'subscribed_at' => date('Y-m-d H:i:s'),
            'active' => 1,
        ]);

        return true;
    }

    public static function unsubscribe(string $email): bool
    {
        $existing = self::findByEmail($email);

        if (!$existing) {
            return false;
        }

        Database::update('newsletter_subscribers', ['active' => 0], 'email = ?', [$email]);
        return true;
    }

    public static function getActiveSubscribers(): array
    {
        return Database::fetchAll("SELECT * FROM newsletter_subscribers WHERE active = 1 ORDER BY subscribed_at DESC");
    }
}
