<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\PurchaseOrderReportService;

/**
 * Painel analítico de pedidos de compra.
 *
 * Acesso restrito a:
 *   - Administradores do sistema (super_admin / admin), OU
 *   - Usuários com PIN de permissão "completo" (role 'all').
 *
 * Rotas:
 *   /admin/relatorio-pedidos           -> tela com os indicadores
 *   /admin/relatorio-pedidos/download  -> download do Excel (.xlsx)
 */
class AnalyticsController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/admin/login');
            exit;
        }
        if (!$this->canAccess()) {
            $this->redirect('/admin/dashboard');
            exit;
        }
    }

    /**
     * Acesso permitido para admin de sistema ou PIN com role 'all' (completo).
     */
    private function canAccess(): bool
    {
        if (Auth::isAdmin()) {
            return true;
        }
        // PIN individual com permissão completa
        return ($_SESSION['pin_user_role'] ?? null) === 'all';
    }

    /**
     * Tela do painel analítico.
     */
    public function index(): void
    {
        [$from, $to, $preset] = $this->resolvePeriod();

        $error = null;
        $data = [];
        try {
            $data = PurchaseOrderReportService::collectData($from, $to);
        } catch (\Throwable $e) {
            $error = 'Não foi possível carregar os indicadores: ' . $e->getMessage();
        }

        $this->view('admin.analytics.orders', [
            'user'        => Auth::user(),
            'flash'       => $this->getFlash(),
            'pageTitle'   => 'Relatório de Pedidos',
            'currentPage' => 'analytics_orders',
            'data'        => $data,
            'error'       => $error,
            'filterFrom'  => $from,
            'filterTo'    => $to,
            'filterPreset'=> $preset,
        ]);
    }

    /**
     * Lê o período da querystring e resolve presets (30/90/180/365 dias).
     * Retorna [from(YYYY-MM-DD|null), to(YYYY-MM-DD|null), preset(string)].
     *
     * @return array{0:?string,1:?string,2:string}
     */
    private function resolvePeriod(): array
    {
        $preset = (string) ($_GET['preset'] ?? '');
        $from = PurchaseOrderReportService::normalizeDate($_GET['from'] ?? null);
        $to   = PurchaseOrderReportService::normalizeDate($_GET['to'] ?? null);

        $presetDays = [
            '30'  => 30,
            '90'  => 90,
            '180' => 180,
            '365' => 365,
        ];
        if (isset($presetDays[$preset])) {
            $to   = date('Y-m-d');
            $from = date('Y-m-d', strtotime('-' . $presetDays[$preset] . ' days'));
        } elseif ($preset === 'all') {
            $from = null;
            $to   = null;
        } elseif ($from || $to) {
            // Datas manuais -> marca como período customizado.
            $preset = 'custom';
        } else {
            $preset = 'all';
        }

        return [$from, $to, $preset];
    }

    /**
     * Gera e faz o download do relatório em Excel (.xlsx).
     */
    public function download(): void
    {
        [$from, $to] = $this->resolvePeriod();

        try {
            $binary = PurchaseOrderReportService::buildXlsx($from, $to);
        } catch (\Throwable $e) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Erro ao gerar o relatório: ' . $e->getMessage();
            exit;
        }

        $filename = PurchaseOrderReportService::suggestedFilename($from, $to);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($binary));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        echo $binary;
        exit;
    }
}
