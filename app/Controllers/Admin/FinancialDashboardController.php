<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;

/**
 * Dashboard Financeiro por Obra.
 *
 * Página somente-leitura que agrega valores financeiros e de consumo
 * a partir dos dados JÁ existentes de Obras (construction_sites) e
 * Pedidos (purchase_orders) e suas tabelas relacionadas.
 *
 * NÃO cria estruturas novas de dados e NÃO altera Pedidos/Obras.
 */
class FinancialDashboardController extends Controller
{
    public function __construct()
    {
        if (!Auth::check()) {
            $this->redirect('/admin/login');
            exit;
        }

        if (!Auth::hasPermission('orders') && !Auth::hasPermission('obras')) {
            $this->redirect('/admin/dashboard');
            exit;
        }
    }

    public function index(): void
    {
        $sites = [];
        $totals = [
            'sites'         => 0,
            'orders'        => 0,
            'spent'         => 0.0, // valor gasto (estimado/aprovado)
            'paid'          => 0.0, // valor pago (NF/pagamentos)
            'consumed'      => 0.0, // valor consumido (itens de estoque)
        ];

        try {
            if ($this->tablesReady()) {
                $sites  = $this->buildSiteIndicators();
                foreach ($sites as $s) {
                    $totals['sites']++;
                    $totals['orders']   += (int) $s['orders_count'];
                    $totals['spent']    += (float) $s['spent'];
                    $totals['paid']     += (float) $s['paid'];
                    $totals['consumed'] += (float) $s['consumed'];
                }
            }
        } catch (\Exception $e) {
            // Falha silenciosa: exibe dashboard vazio em vez de erro 500.
            $sites = [];
        }

        $this->view('admin.financeiro.index', [
            'sites'  => $sites,
            'totals' => $totals,
            'user'   => Auth::user(),
            'flash'  => $this->getFlash(),
        ]);
    }

    /**
     * Confere se as tabelas mínimas existem para montar o dashboard.
     */
    private function tablesReady(): bool
    {
        $tbl = Database::fetch(
            "SELECT 1 FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'construction_sites' LIMIT 1"
        );
        if (empty($tbl)) {
            return false;
        }

        $col = Database::fetch(
            "SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_orders'
               AND COLUMN_NAME = 'construction_site_id' LIMIT 1"
        );
        return !empty($col);
    }

    /**
     * Agrega os indicadores financeiros de cada obra reutilizando a mesma
     * lógica canônica de valor de pedido usada em PurchaseOrder::allWithSupplier.
     *
     * - spent    (valor gasto): COALESCE(subtotal aprovado, total_estimated, itens de estoque, 0)
     * - paid     (valor pago) : SUM(purchase_order_payments.amount)
     * - consumed (consumido)  : SUM(total_price) de itens com source_type != 'purchase'
     * - price_min / price_max : menor / maior subtotal cotado por fornecedor (purchase_order_suppliers)
     */
    private function buildSiteIndicators(): array
    {
        $sql = "
            SELECT
                cs.id,
                cs.name,
                cs.code,
                cs.city,
                cs.state,
                cs.status,
                (SELECT COUNT(*) FROM purchase_orders po WHERE po.construction_site_id = cs.id) AS orders_count,

                (SELECT COALESCE(SUM(
                    COALESCE(
                        (SELECT pos.subtotal_final FROM purchase_order_suppliers pos
                          WHERE pos.order_id = po.id AND pos.approved = 1 LIMIT 1),
                        NULLIF(po.total_estimated, 0),
                        (SELECT SUM(poi.total_price) FROM purchase_order_items poi
                          WHERE poi.order_id = po.id AND poi.source_type IS NOT NULL
                            AND poi.source_type != 'purchase' AND poi.total_price > 0),
                        0
                    )
                ), 0)
                 FROM purchase_orders po WHERE po.construction_site_id = cs.id) AS spent,

                (SELECT COALESCE(SUM(pop.amount), 0)
                 FROM purchase_order_payments pop
                 INNER JOIN purchase_orders po ON po.id = pop.order_id
                 WHERE po.construction_site_id = cs.id) AS paid,

                (SELECT COALESCE(SUM(poi.total_price), 0)
                 FROM purchase_order_items poi
                 INNER JOIN purchase_orders po ON po.id = poi.order_id
                 WHERE po.construction_site_id = cs.id
                   AND poi.source_type IS NOT NULL
                   AND poi.source_type != 'purchase'
                   AND poi.total_price > 0) AS consumed,

                (SELECT MIN(t.st) FROM (
                    SELECT pos.subtotal_final AS st
                    FROM purchase_order_suppliers pos
                    INNER JOIN purchase_orders po ON po.id = pos.order_id
                    WHERE po.construction_site_id = cs.id
                      AND pos.subtotal_final IS NOT NULL AND pos.subtotal_final > 0
                 ) t) AS price_min,

                (SELECT MAX(t.st) FROM (
                    SELECT pos.subtotal_final AS st
                    FROM purchase_order_suppliers pos
                    INNER JOIN purchase_orders po ON po.id = pos.order_id
                    WHERE po.construction_site_id = cs.id
                      AND pos.subtotal_final IS NOT NULL AND pos.subtotal_final > 0
                 ) t) AS price_max

            FROM construction_sites cs
            ORDER BY cs.name ASC
        ";

        return Database::fetchAll($sql);
    }
}
