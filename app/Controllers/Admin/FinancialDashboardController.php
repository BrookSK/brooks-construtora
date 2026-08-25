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
            'spent'         => 0.0, // valor gasto (total_estimated dos pedidos)
            'paid'          => 0.0, // valor pago (NF/boletos pagos)
            'to_pay'        => 0.0, // valor a pagar (NF/boletos pendentes)
            'freight'       => 0.0, // frete de compra (fornecedores aprovados)
            'consumed'      => 0.0, // valor consumido (itens de estoque)
            'stock_value'   => 0.0, // valor imobilizado em estoque
        ];

        try {
            if ($this->tablesReady()) {
                $sites  = $this->buildSiteIndicators();
                foreach ($sites as $s) {
                    $totals['sites']++;
                    $totals['orders']      += (int) ($s['orders_count'] ?? 0);
                    $totals['spent']       += (float) ($s['spent'] ?? 0);
                    $totals['paid']        += (float) ($s['paid'] ?? 0);
                    $totals['to_pay']      += (float) ($s['to_pay'] ?? 0);
                    $totals['freight']     += (float) ($s['freight'] ?? 0);
                    $totals['consumed']    += (float) ($s['consumed'] ?? 0);
                    $totals['stock_value'] += (float) ($s['stock_value'] ?? 0);
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
     * Agrega os indicadores financeiros e de consumo de cada obra a partir
     * das estruturas JÁ existentes, consolidando os módulos como fonte de dados.
     * (Nenhum módulo é alterado — somente leitura.)
     *
     * Fontes reais (mapeadas no sistema):
     * - orders_count : COUNT de purchase_orders da obra (construction_site_id).
     * - spent (gasto): purchase_orders.total_estimated (valor efetivo, gravado na
     *                  aprovação/edição financeira). Fallback: SUM(purchase_order_items.total_price).
     *                  Ignora pedidos cancelled/rejected. IMPORTANTE: total_estimated vem PRIMEIRO
     *                  porque purchase_order_suppliers.subtotal_final costuma ficar 0 (DEFAULT) e
     *                  travava o COALESCE anterior, causando os indicadores zerados.
     * - paid (pago)  : SUM(purchase_order_payments.amount) apenas dos registros pagos (paid = 1).
     * - to_pay       : SUM(purchase_order_payments.amount) dos NÃO pagos (paid = 0).
     * - consumed     : itens de estoque (source_type stock_use/stock_transfer). Usa total_price
     *                  quando existir; senão estima quantity * stock_items.unit_price do material na obra.
     * - price_min/max: menor/maior preço unitário cotado por insumo (purchase_order_item_prices.unit_price),
     *                  fonte real por obra. Substitui o antigo subtotal_final (que vinha 0).
     * - stock_value  : valor imobilizado em estoque = SUM(quantity * unit_price) por obra
     *                  (via stock_items, resolvendo a obra por location ou coluna direta).
     * - freight      : SUM(purchase_order_suppliers.freight) dos fornecedores aprovados (frete de compra).
     */
    private function buildSiteIndicators(): array
    {
        // Tabelas opcionais (módulos que podem não existir em todos os ambientes).
        // Se ausentes, os respectivos indicadores retornam 0 sem quebrar a query.
        $hasPayments   = $this->tableExists('purchase_order_payments');
        $hasSuppliers  = $this->tableExists('purchase_order_suppliers');
        $hasItems      = $this->tableExists('purchase_order_items');
        $hasItemPrices = $this->tableExists('purchase_order_item_prices');
        $hasStockItems = $this->tableExists('stock_items');

        $spentFallback = $hasItems
            ? "(SELECT SUM(poi.total_price) FROM purchase_order_items poi
                 WHERE poi.order_id = po.id AND poi.total_price > 0)"
            : "NULL";

        $paidExpr = $hasPayments
            ? "(SELECT COALESCE(SUM(pop.amount), 0)
                 FROM purchase_order_payments pop
                 INNER JOIN purchase_orders po ON po.id = pop.order_id
                 WHERE po.construction_site_id = cs.id AND pop.paid = 1)"
            : "0";

        $toPayExpr = $hasPayments
            ? "(SELECT COALESCE(SUM(pop.amount), 0)
                 FROM purchase_order_payments pop
                 INNER JOIN purchase_orders po ON po.id = pop.order_id
                 WHERE po.construction_site_id = cs.id AND pop.paid = 0)"
            : "0";

        $freightExpr = $hasSuppliers
            ? "(SELECT COALESCE(SUM(pos.freight), 0)
                 FROM purchase_order_suppliers pos
                 INNER JOIN purchase_orders po ON po.id = pos.order_id
                 WHERE po.construction_site_id = cs.id AND pos.approved = 1)"
            : "0";

        $unitPriceLookup = $hasStockItems
            ? "COALESCE((SELECT si.unit_price FROM stock_items si
                          WHERE si.material_id = poi.material_id
                            AND si.construction_site_id = po.construction_site_id
                            AND si.unit_price IS NOT NULL LIMIT 1), 0)"
            : "0";

        $consumedExpr = $hasItems
            ? "(SELECT COALESCE(SUM(
                    CASE
                        WHEN poi.total_price > 0 THEN poi.total_price
                        ELSE poi.quantity * {$unitPriceLookup}
                    END
                 ), 0)
                 FROM purchase_order_items poi
                 INNER JOIN purchase_orders po ON po.id = poi.order_id
                 WHERE po.construction_site_id = cs.id
                   AND poi.source_type IN ('stock_use', 'stock_transfer'))"
            : "0";

        $stockValueExpr = $hasStockItems
            ? "(SELECT COALESCE(SUM(si.quantity * COALESCE(si.unit_price, 0)), 0)
                 FROM stock_items si
                 WHERE si.construction_site_id = cs.id)"
            : "0";

        $priceMinExpr = $hasItemPrices
            ? "(SELECT MIN(pip.unit_price)
                 FROM purchase_order_item_prices pip
                 INNER JOIN purchase_orders po ON po.id = pip.order_id
                 WHERE po.construction_site_id = cs.id
                   AND pip.unit_price IS NOT NULL AND pip.unit_price > 0)"
            : "NULL";

        $priceMaxExpr = $hasItemPrices
            ? "(SELECT MAX(pip.unit_price)
                 FROM purchase_order_item_prices pip
                 INNER JOIN purchase_orders po ON po.id = pip.order_id
                 WHERE po.construction_site_id = cs.id
                   AND pip.unit_price IS NOT NULL AND pip.unit_price > 0)"
            : "NULL";

        $sql = "
            SELECT
                cs.id,
                cs.name,
                cs.code,
                cs.city,
                cs.state,
                cs.status,

                (SELECT COUNT(*) FROM purchase_orders po
                  WHERE po.construction_site_id = cs.id) AS orders_count,

                /* Valor gasto: total_estimated (real) com fallback na soma dos itens */
                (SELECT COALESCE(SUM(
                    COALESCE(
                        NULLIF(po.total_estimated, 0),
                        {$spentFallback},
                        0
                    )
                 ), 0)
                 FROM purchase_orders po
                 WHERE po.construction_site_id = cs.id
                   AND po.status NOT IN ('cancelled', 'rejected')) AS spent,

                /* Valor pago (NF/boleto marcados como pagos) */
                {$paidExpr} AS paid,

                /* Valor a pagar (NF/boleto pendentes) */
                {$toPayExpr} AS to_pay,

                /* Frete de compra (fornecedores aprovados) */
                {$freightExpr} AS freight,

                /* Valor consumido (itens que saíram do estoque) */
                {$consumedExpr} AS consumed,

                /* Valor imobilizado em estoque na obra */
                {$stockValueExpr} AS stock_value,

                /* Preço mínimo cotado por insumo (fonte real por obra) */
                {$priceMinExpr} AS price_min,

                /* Preço máximo cotado por insumo (fonte real por obra) */
                {$priceMaxExpr} AS price_max

            FROM construction_sites cs
            ORDER BY cs.name ASC
        ";

        return Database::fetchAll($sql);
    }

    /**
     * Verifica se uma tabela existe no banco (para módulos opcionais).
     */
    private function tableExists(string $table): bool
    {
        try {
            $r = Database::fetch(
                "SELECT 1 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1",
                [$table]
            );
            return !empty($r);
        } catch (\Exception $e) {
            return false;
        }
    }
}
