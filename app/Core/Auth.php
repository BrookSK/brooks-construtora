<?php

namespace App\Core;

use App\Models\User;

class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $user = User::whereFirst('email', $email);

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password'])) {
            return false;
        }

        if ($user['active'] != 1) {
            return false;
        }

        // Salva na sessão
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];

        // Atualiza último login
        User::updateById($user['id'], ['last_login' => date('Y-m-d H:i:s')]);

        return true;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role'],
        ];
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function role(): ?string
    {
        return $_SESSION['user_role'] ?? null;
    }

    public static function isSuperAdmin(): bool
    {
        return self::role() === 'super_admin';
    }

    public static function isAdmin(): bool
    {
        return in_array(self::role(), ['super_admin', 'admin']);
    }

    public static function isDesigner(): bool
    {
        return in_array(self::role(), ['super_admin', 'admin', 'designer']);
    }

    public static function hasPermission(string $permission): bool
    {
        $role = self::role();

        $permissions = [
            'super_admin' => ['all'],
            'admin' => [
                'dashboard', 'settings', 'newsletter', 'users', 
                'magazines', 'magazines.edit', 'magazines.publish',
                'orders', 'orders.create', 'orders.approve', 'orders.quote',
                'suppliers', 'materials', 'orders.settings'
            ],
            'designer' => ['dashboard', 'magazines', 'magazines.edit'],
            'editor' => ['dashboard', 'magazines', 'orders', 'suppliers', 'materials'],
            'comprador' => ['dashboard', 'orders', 'orders.create', 'orders.quote', 'suppliers', 'materials'],
            'cotador' => ['dashboard', 'orders', 'orders.quote', 'suppliers', 'materials'],
            'aprovador' => ['dashboard', 'orders', 'orders.approve'],
            'financeiro' => ['dashboard', 'orders', 'orders.payment'],
            'epi' => ['dashboard', 'epi'],
            'entrega' => ['dashboard', 'orders', 'orders.create', 'suppliers', 'materials'],
        ];

        if (!isset($permissions[$role])) {
            return false;
        }

        if (in_array('all', $permissions[$role])) {
            return true;
        }

        return in_array($permission, $permissions[$role]);
    }

    public static function logout(): void
    {
        session_destroy();
        $_SESSION = [];
    }
}
