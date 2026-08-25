<?php $pageTitle = 'Dashboard Financeiro'; $currentPage = 'financeiro'; ob_start(); ?>

<?php
/**
 * Dashboard Financeiro por Obra (somente leitura).
 * Dados agregados no FinancialDashboardController a partir de
 * construction_sites + purchase_orders e tabelas relacionadas.
 */
$fmtMoney = static function ($v): string {
    return 'R$ ' . number_format((float) ($v ?? 0), 2, ',', '.');
};
$statusLabels = [
    'active'    => ['Ativa', 'success'],
    'inactive'  => ['Inativa', 'secondary'],
    'completed' => ['Concluída', 'primary'],
];
$sites  = $sites ?? [];
$totals = $totals ?? ['sites' => 0, 'orders' => 0, 'spent' => 0, 'paid' => 0, 'consumed' => 0];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Dashboard Financeiro</h4>
        <p class="text-muted mb-0">Acompanhamento financeiro e de consumo por obra.</p>
    </div>
    <a href="/admin/obras" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-buildings"></i> Ver Obras
    </a>
</div>

<!-- Indicadores gerais -->
<div class="row g-4 mb-4">
    <div class="col-6 col-md-3">
        <div class="card stat-card p-3" style="border-left-color: #6c757d;">
            <div class="stat-number"><?= (int) $totals['sites'] ?></div>
            <div class="text-muted">Obras</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card p-3" style="border-left-color: #0dcaf0;">
            <div class="stat-number"><?= (int) $totals['orders'] ?></div>
            <div class="text-muted">Pedidos</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card p-3" style="border-left-color: #ffc107;">
            <div class="stat-number" style="font-size:1.25rem;"><?= $fmtMoney($totals['spent']) ?></div>
            <div class="text-muted">Valor Gasto</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card p-3" style="border-left-color: #28a745;">
            <div class="stat-number" style="font-size:1.25rem;"><?= $fmtMoney($totals['paid']) ?></div>
            <div class="text-muted">Valor Pago</div>
        </div>
    </div>
</div>

<!-- Detalhamento por obra -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Detalhamento por Obra</h6>
        <span class="text-muted small">Valor consumido total: <?= $fmtMoney($totals['consumed']) ?></span>
    </div>
    <div class="card-body">
        <?php if (empty($sites)): ?>
            <p class="text-muted text-center py-4 mb-0">
                Nenhuma obra com dados disponíveis. Cadastre obras e vincule pedidos para visualizar os indicadores.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Obra</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Pedidos</th>
                            <th class="text-end">Preço Mín.</th>
                            <th class="text-end">Preço Máx.</th>
                            <th class="text-end">Valor Gasto</th>
                            <th class="text-end">Valor Pago</th>
                            <th class="text-end">Valor Consumido</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sites as $s): ?>
                            <?php
                                $st = $statusLabels[$s['status'] ?? ''] ?? [ucfirst((string) ($s['status'] ?? '')), 'secondary'];
                                $ordersCount = (int) ($s['orders_count'] ?? 0);
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($s['name'] ?? '') ?></div>
                                    <div class="text-muted small">
                                        <?= htmlspecialchars($s['code'] ?? '') ?>
                                        <?php if (!empty($s['city'])): ?>
                                            &middot; <?= htmlspecialchars($s['city']) ?><?= !empty($s['state']) ? '/' . htmlspecialchars($s['state']) : '' ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $st[1] ?>"><?= htmlspecialchars($st[0]) ?></span>
                                </td>
                                <td class="text-center"><?= $ordersCount ?></td>
                                <td class="text-end">
                                    <?= ($s['price_min'] ?? null) !== null ? $fmtMoney($s['price_min']) : '<span class="text-muted">Não informado</span>' ?>
                                </td>
                                <td class="text-end">
                                    <?= ($s['price_max'] ?? null) !== null ? $fmtMoney($s['price_max']) : '<span class="text-muted">Não informado</span>' ?>
                                </td>
                                <td class="text-end fw-semibold"><?= $fmtMoney($s['spent'] ?? 0) ?></td>
                                <td class="text-end text-success"><?= $fmtMoney($s['paid'] ?? 0) ?></td>
                                <td class="text-end"><?= $fmtMoney($s['consumed'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-semibold border-top">
                            <td colspan="2">Total</td>
                            <td class="text-center"><?= (int) $totals['orders'] ?></td>
                            <td class="text-end">&mdash;</td>
                            <td class="text-end">&mdash;</td>
                            <td class="text-end"><?= $fmtMoney($totals['spent']) ?></td>
                            <td class="text-end text-success"><?= $fmtMoney($totals['paid']) ?></td>
                            <td class="text-end"><?= $fmtMoney($totals['consumed']) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <p class="text-muted small mb-0 mt-2">
                <i class="bi bi-info-circle"></i>
                Valores calculados a partir dos pedidos vinculados a cada obra. Preço mín./máx. considera as cotações por fornecedor.
            </p>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
