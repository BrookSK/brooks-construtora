<?php

namespace App\Core;

use App\Models\Setting;

/**
 * Modo "somente consulta" do módulo de pedidos.
 *
 * Ativado em Config. de Pedidos (setting orders_new_disabled = 1).
 * Quando ativo, o sistema vira apenas base de consulta dos pedidos passados:
 * nada de novo pedido, cotação, aprovação/rejeição, compra, transporte,
 * entrega, financeiro, lista semanal etc. Só as rotas de leitura/exportação
 * listadas abaixo continuam funcionando.
 */
class OrdersReadOnly
{
    public const DEFAULT_MESSAGE = 'Sistema migrado para a Vetriks. Faça seu pedido por lá.';

    /** Rotas do admin permitidas (somente leitura) dentro dos grupos bloqueados. */
    private const ADMIN_ALLOWED = [
        'orders', 'orders/show', 'orders/settings', 'orders/settings/update',
        'orders/price-history', 'orders/export', 'orders/export-all',
        'orders/payments', 'orders/delivery-data', 'orders/tracking',
        'orders/spare-items', 'orders/shopping-list',
        'orders/quote-logs', 'orders/quote-logs/view', 'orders/list-audios',
        // Gestão de usuários PIN (usados também em EPI, presença etc.)
        'orders/pin-users', 'orders/generate-invite', 'orders/delete-invite',
        'orders/delete-pin-user', 'orders/update-pin-user', 'orders/update-pin-user-phone',
        'orders/update-pin-user-email', 'orders/send-invite-webhook',
        // Lista semanal: apenas telas de consulta
        'weekly-materials', 'weekly-materials/week', 'weekly-materials/purchases',
        'weekly-materials/manager', 'weekly-materials/monitoring',
        'weekly-materials/cycles', 'weekly-materials/export-control',
    ];

    /** Rotas públicas (site) permitidas dentro dos grupos bloqueados. */
    private const SITE_ALLOWED = [
        'pedido/pdf', 'pedido/xlsx', 'pedido/xlsx-especificacao', 'pedido/list-audios',
        'pedidos', 'pedidos/login', 'pedidos/auth', 'pedidos/logout',
        'relatorio-lista-semanal',
    ];

    /** Prefixos de rota sujeitos ao bloqueio. */
    private const ADMIN_PREFIXES = ['orders', 'weekly-materials'];
    private const SITE_PREFIXES = ['pedido', 'pedidos', 'lista-semanal', 'relatorio-lista-semanal'];

    public static function isActive(): bool
    {
        try {
            return Setting::get('orders_new_disabled', '0') === '1';
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function message(): string
    {
        try {
            $msg = trim((string) Setting::get('orders_new_disabled_message', ''));
        } catch (\Throwable $e) {
            $msg = '';
        }
        return $msg !== '' ? $msg : self::DEFAULT_MESSAGE;
    }

    /** true se a rota (chave do array de rotas) deve ser bloqueada. */
    public static function blocks(string $area, string $routeKey): bool
    {
        if (!self::isActive()) {
            return false;
        }
        $first = explode('/', $routeKey)[0];
        if ($area === 'admin') {
            return in_array($first, self::ADMIN_PREFIXES, true) && !in_array($routeKey, self::ADMIN_ALLOWED, true);
        }
        return in_array($first, self::SITE_PREFIXES, true) && !in_array($routeKey, self::SITE_ALLOWED, true);
    }

    /** Responde ao acesso bloqueado (JSON para AJAX/POST, redirect/página para navegação). */
    public static function deny(string $area): void
    {
        $msg = self::message();
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $isAjax = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest')
            || stripos($accept, 'application/json') !== false
            || stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;
        $isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

        if ($isAjax || ($isPost && stripos($accept, 'text/html') === false)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => $msg, 'message' => $msg], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($area === 'admin') {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => $msg];
            }
            header('Location: /admin/orders?new_disabled=1');
            exit;
        }

        http_response_code(403);
        $safe = htmlspecialchars($msg, ENT_QUOTES, 'UTF-8');
        echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Sistema migrado</title>'
            . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">'
            . '</head><body class="bg-light d-flex align-items-center" style="min-height:100vh;">'
            . '<main class="container" style="max-width:520px;"><div class="card shadow-sm"><div class="card-body text-center p-4">'
            . '<h1 class="h4 mb-3">Sistema migrado</h1>'
            . '<p class="mb-0" style="white-space:pre-line;">' . $safe . '</p>'
            . '</div></div></main></body></html>';
        exit;
    }
}
