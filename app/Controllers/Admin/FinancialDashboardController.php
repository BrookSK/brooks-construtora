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

        // Filtro por status (dado real) + ordenacao (aplicados em PHP, sem tocar no SQL).
        $status = (string) $this->input('status', '');
        if ($status !== '') {
            $sites = array_values(array_filter($sites, static fn($s) => ($s['status'] ?? '') === $status));
        }

        $sort = (string) $this->input('sort', 'name');
        $sites = $this->sortSites($sites, $sort);

        $this->view('admin.financeiro.index', [
            'sites'        => $sites,
            'totals'       => $totals,
            'currentSort'  => $sort,
            'currentStatus'=> $status,
            'user'         => Auth::user(),
            'flash'        => $this->getFlash(),
        ]);
    }

    /**
     * Ordena a lista de obras conforme criterio real disponivel.
     */
    private function sortSites(array $sites, string $sort): array
    {
        $cmp = [
            'spent_desc'    => fn($a, $b) => ($b['spent'] ?? 0)        <=> ($a['spent'] ?? 0),
            'spent_asc'     => fn($a, $b) => ($a['spent'] ?? 0)        <=> ($b['spent'] ?? 0),
            'paid_desc'     => fn($a, $b) => ($b['paid'] ?? 0)         <=> ($a['paid'] ?? 0),
            'consumed_desc' => fn($a, $b) => ($b['consumed'] ?? 0)     <=> ($a['consumed'] ?? 0),
            'orders_desc'   => fn($a, $b) => ($b['orders_count'] ?? 0) <=> ($a['orders_count'] ?? 0),
            'status'        => fn($a, $b) => strcmp((string) ($a['status'] ?? ''), (string) ($b['status'] ?? '')),
            'name'          => fn($a, $b) => strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')),
        ];
        $fn = $cmp[$sort] ?? $cmp['name'];
        usort($sites, $fn);
        return $sites;
    }

    /**
     * Detalhamento de uma obra especifica.
     * Todos os dados sao filtrados por construction_site_id = :id.
     */
    public function show(int $id = 0): void
    {
        $id = $id ?: (int) $this->input('id', 0);
        if ($id <= 0) {
            $this->redirect('/admin/financeiro');
            return;
        }

        $site = \App\Models\ConstructionSite::find($id);
        if (!$site) {
            $this->setFlash('error', 'Obra não encontrada.');
            $this->redirect('/admin/financeiro');
            return;
        }

        $indicators = ['orders_count' => 0, 'spent' => 0.0, 'paid' => 0.0, 'to_pay' => 0.0,
                       'freight' => 0.0, 'consumed' => 0.0, 'stock_value' => 0.0,
                       'price_min' => null, 'price_max' => null];
        $orders     = [];
        $materials  = [];
        $suppliers  = [];
        $payments   = [];
        $stock      = [];
        $charts     = ['spend_by_category' => [], 'payments' => [], 'consumption' => []];

        try {
            if ($this->tablesReady()) {
                $row = $this->buildSiteIndicators($id);
                if (!empty($row[0])) {
                    $r = $row[0];
                    $indicators = [
                        'orders_count' => (int) ($r['orders_count'] ?? 0),
                        'spent'        => (float) ($r['spent'] ?? 0),
                        'paid'         => (float) ($r['paid'] ?? 0),
                        'to_pay'       => (float) ($r['to_pay'] ?? 0),
                        'freight'      => (float) ($r['freight'] ?? 0),
                        'consumed'     => (float) ($r['consumed'] ?? 0),
                        'stock_value'  => (float) ($r['stock_value'] ?? 0),
                        'price_min'    => $r['price_min'] ?? null,
                        'price_max'    => $r['price_max'] ?? null,
                    ];
                }
                $orders    = $this->orderDetails($id);
                $materials = $this->materialDetails($id);
                $suppliers = $this->supplierDetails($id);
                $payments  = $this->paymentDetails($id);
                $stock     = $this->stockDetails($id);
                $charts    = [
                    'spend_by_category' => $this->spendByCategory($id),
                    'payments'          => ['paid' => $indicators['paid'], 'to_pay' => $indicators['to_pay']],
                    'consumption'       => $this->consumptionByMaterial($id),
                ];
            }
        } catch (\Exception $e) {
            // Estado vazio em caso de falha — nunca 500.
        }

        $this->view('admin.financeiro.show', [
            'site'       => $site,
            'indicators' => $indicators,
            'orders'     => $orders,
            'materials'  => $materials,
            'suppliers'  => $suppliers,
            'payments'   => $payments,
            'stock'      => $stock,
            'charts'     => $charts,
            'user'       => Auth::user(),
            'flash'      => $this->getFlash(),
        ]);
    }

    /**
     * Pedidos da obra (dados reais de purchase_orders).
     */
    private function orderDetails(int $siteId): array
    {
        return Database::fetchAll(
            "SELECT po.id, po.code, po.status, po.created_at, po.total_estimated,
                    s.name AS supplier_name,
                    (SELECT COALESCE(SUM(pop.amount), 0) FROM purchase_order_payments pop
                      WHERE pop.order_id = po.id AND pop.paid = 1) AS paid
             FROM purchase_orders po
             LEFT JOIN suppliers s ON po.supplier_id = s.id
             WHERE po.construction_site_id = ?
             ORDER BY po.created_at DESC",
            [$siteId]
        );
    }

    /**
     * Materiais dos pedidos da obra, agregados por material (dados reais dos itens).
     */
    private function materialDetails(int $siteId): array
    {
        return Database::fetchAll(
            "SELECT poi.material_name,
                    SUM(poi.quantity) AS quantity,
                    MAX(poi.unit_price) AS unit_price,
                    SUM(COALESCE(poi.total_price, 0)) AS total_price
             FROM purchase_order_items poi
             INNER JOIN purchase_orders po ON po.id = poi.order_id
             WHERE po.construction_site_id = ?
             GROUP BY poi.material_name
             ORDER BY total_price DESC",
            [$siteId]
        );
    }

    /**
     * Fornecedores relacionados aos pedidos da obra (dados reais).
     */
    private function supplierDetails(int $siteId): array
    {
        return Database::fetchAll(
            "SELECT s.name AS supplier_name, s.cnpj,
                    COUNT(DISTINCT pos.order_id) AS orders_count,
                    COALESCE(SUM(CASE WHEN pos.approved = 1 THEN pos.subtotal_final ELSE 0 END), 0) AS approved_total
             FROM purchase_order_suppliers pos
             INNER JOIN purchase_orders po ON po.id = pos.order_id
             INNER JOIN suppliers s ON s.id = pos.supplier_id
             WHERE po.construction_site_id = ?
             GROUP BY s.id, s.name, s.cnpj
             ORDER BY approved_total DESC, orders_count DESC",
            [$siteId]
        );
    }

    /**
     * Pagamentos (NF/boletos) dos pedidos da obra (dados reais).
     */
    private function paymentDetails(int $siteId): array
    {
        return Database::fetchAll(
            "SELECT pop.type, pop.number, pop.amount, pop.paid, pop.due_date, pop.created_at,
                    po.code AS order_code
             FROM purchase_order_payments pop
             INNER JOIN purchase_orders po ON po.id = pop.order_id
             WHERE po.construction_site_id = ?
             ORDER BY pop.created_at DESC",
            [$siteId]
        );
    }

    /**
     * Estoque vinculado a obra (via location OU coluna direta), dados reais.
     */
    private function stockDetails(int $siteId): array
    {
        if (!$this->tableExists('stock_items')) {
            return [];
        }
        return Database::fetchAll(
            "SELECT m.name AS material_name,
                    si.quantity, si.unit_price,
                    (si.quantity * COALESCE(si.unit_price, 0)) AS total_value,
                    sl.name AS location_name
             FROM stock_items si
             INNER JOIN materials m ON m.id = si.material_id
             LEFT JOIN stock_locations sl ON sl.id = si.stock_location_id
             WHERE COALESCE(sl.construction_site_id, si.construction_site_id) = ?
             ORDER BY total_value DESC",
            [$siteId]
        );
    }

    /**
     * Distribuicao de gastos por categoria de material (para grafico de gastos).
     */
    private function spendByCategory(int $siteId): array
    {
        return Database::fetchAll(
            "SELECT COALESCE(mc.name, 'Sem categoria') AS label,
                    SUM(COALESCE(poi.total_price, 0)) AS value
             FROM purchase_order_items poi
             INNER JOIN purchase_orders po ON po.id = poi.order_id
             LEFT JOIN materials m ON m.id = poi.material_id
             LEFT JOIN material_categories mc ON mc.id = m.category_id
             WHERE po.construction_site_id = ?
               AND COALESCE(poi.total_price, 0) > 0
             GROUP BY COALESCE(mc.name, 'Sem categoria')
             ORDER BY value DESC
             LIMIT 8",
            [$siteId]
        );
    }

    /**
     * Consumo por material (itens de estoque usados/transferidos) — para grafico de consumo.
     */
    private function consumptionByMaterial(int $siteId): array
    {
        return Database::fetchAll(
            "SELECT poi.material_name AS label,
                    SUM(poi.quantity) AS qty,
                    SUM(COALESCE(poi.total_price, 0)) AS value
             FROM purchase_order_items poi
             INNER JOIN purchase_orders po ON po.id = poi.order_id
             WHERE po.construction_site_id = ?
               AND poi.source_type IN ('stock_use', 'stock_transfer')
             GROUP BY poi.material_name
             ORDER BY value DESC, qty DESC
             LIMIT 8",
            [$siteId]
        );
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
    private function buildSiteIndicators(?int $siteId = null): array
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
            " . ($siteId !== null ? "WHERE cs.id = ?" : "") . "
            ORDER BY cs.name ASC
        ";

        return Database::fetchAll($sql, $siteId !== null ? [$siteId] : []);
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
