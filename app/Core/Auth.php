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
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // Se é sessão do PIN global (user_id = 0), verificar se ainda está ativo
        if (($_SESSION['user_id'] === 0 || $_SESSION['user_id'] === '0') && !empty($_SESSION['pin_auth']) && empty($_SESSION['pin_user_id'])) {
            $pinGlobalActive = \App\Models\Setting::get('orders_pin_global_active', '1') === '1';
            if (!$pinGlobalActive) {
                // Invalidar sessão do PIN global
                unset($_SESSION['pin_auth'], $_SESSION['user_id'], $_SESSION['user_name'],
                      $_SESSION['user_email'], $_SESSION['user_role'], $_SESSION['pin_auth_time']);
                if (isset($_COOKIE['pin_session'])) {
                    setcookie('pin_session', '', time() - 3600, '/');
                }
                // Redirecionar para login de PIN (não para o admin/login)
                header('Location: /pedidos/login');
                exit;
            }
        }

        // Se é PIN individual sem telefone cadastrado, forçar atualização de cadastro
        if (!empty($_SESSION['pin_auth']) && !empty($_SESSION['pin_user_id'])) {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            if (strpos($uri, '/pin/minha-conta') === false && strpos($uri, '/admin/logout') === false) {
                $pinUser = \App\Models\PinUser::find((int) $_SESSION['pin_user_id']);
                if ($pinUser && empty($pinUser['phone'])) {
                    header('Location: /pin/minha-conta');
                    exit;
                }
            }
        }

        return true;
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
                'suppliers', 'materials', 'orders.settings', 'obras', 'stock', 'transport'
            ],
            'designer' => ['dashboard', 'magazines', 'magazines.edit'],
            'editor' => ['dashboard', 'magazines', 'orders', 'suppliers', 'materials', 'obras'],
            'comprador' => ['dashboard', 'orders', 'orders.create', 'orders.quote', 'suppliers', 'materials', 'obras', 'stock'],
            'cotador' => ['dashboard', 'orders', 'orders.quote', 'suppliers', 'materials', 'obras'],
            'aprovador' => ['dashboard', 'orders', 'orders.approve', 'obras'],
            'financeiro' => ['dashboard', 'orders', 'orders.payment', 'obras'],
            'epi' => ['dashboard', 'epi', 'orders', 'orders.create', 'orders.quote', 'suppliers', 'materials', 'obras'],
            'entrega' => ['dashboard', 'orders', 'orders.create', 'suppliers', 'materials', 'obras'],
            'transporte' => ['dashboard', 'orders', 'transport', 'obras'],
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
