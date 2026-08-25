<?php $pageTitle = 'Detalhes da Obra'; $currentPage = 'financeiro'; ob_start(); ?>

<?php
/**
 * Dashboard de Obras — Detalhamento de uma obra (somente leitura).
 * Todos os dados vem filtrados por construction_site_id no controller.
 */
$fmtMoney = static function ($v): string {
    return 'R$ ' . number_format((float) ($v ?? 0), 2, ',', '.');
};
$fmtQty = static function ($v): string {
    $v = (float) ($v ?? 0);
    return number_format($v, $v == (int) $v ? 0 : 2, ',', '.');
};
$statusLabels = [
    'active'    => ['Ativa', 'success'],
    'inactive'  => ['Inativa', 'secondary'],
    'completed' => ['Concluída', 'primary'],
];
$site       = $site ?? [];
$ind        = $indicators ?? [];
$orders     = $orders ?? [];
$materials  = $materials ?? [];
$suppliers  = $suppliers ?? [];
$payments   = $payments ?? [];
$stock      = $stock ?? [];
$charts     = $charts ?? ['spend_by_category' => [], 'payments' => [], 'consumption' => []];
$st = $statusLabels[$site['status'] ?? ''] ?? [ucfirst((string) ($site['status'] ?? '')), 'secondary'];
?>

<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <a href="/admin/financeiro" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Voltar ao Dashboard</a>
        <h4 class="mb-1 mt-1">
            <?= htmlspecialchars($site['name'] ?? 'Obra') ?>
            <span class="badge bg-<?= $st[1] ?> align-middle" style="font-size:0.7rem;"><?= htmlspecialchars($st[0]) ?></span>
        </h4>
        <p class="text-muted mb-0 small">
            <?= htmlspecialchars($site['code'] ?? '') ?>
            <?php if (!empty($site['city'])): ?>
                &middot; <?= htmlspecialchars($site['city']) ?><?= !empty($site['state']) ? '/' . htmlspecialchars($site['state']) : '' ?>
            <?php endif; ?>
        </p>
    </div>
</div>

<!-- Cards de indicadores -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card stat-card p-3" style="border-left-color:#0dcaf0;">
            <div class="stat-number"><?= (int) ($ind['orders_count'] ?? 0) ?></div>
            <div class="text-muted">Pedidos</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card p-3" style="border-left-color:#ffc107;">
            <div class="stat-number" style="font-size:1.2rem;"><?= $fmtMoney($ind['spent'] ?? 0) ?></div>
            <div class="text-muted">Valor Gasto</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card p-3" style="border-left-color:#28a745;">
            <div class="stat-number" style="font-size:1.2rem;"><?= $fmtMoney($ind['paid'] ?? 0) ?></div>
            <div class="text-muted">Valor Pago</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card p-3" style="border-left-color:#6610f2;">
            <div class="stat-number" style="font-size:1.2rem;"><?= $fmtMoney($ind['consumed'] ?? 0) ?></div>
            <div class="text-muted">Valor Consumido</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card p-3" style="border-left-color:#fd7e14;">
            <div class="stat-number" style="font-size:1.2rem;"><?= $fmtMoney($ind['to_pay'] ?? 0) ?></div>
            <div class="text-muted">A Pagar</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card p-3" style="border-left-color:#20c997;">
            <div class="stat-number" style="font-size:1.2rem;"><?= $fmtMoney($ind['freight'] ?? 0) ?></div>
            <div class="text-muted">Frete (compras)</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card p-3" style="border-left-color:#6c757d;">
            <div class="stat-number" style="font-size:1.2rem;"><?= $fmtMoney($ind['stock_value'] ?? 0) ?></div>
            <div class="text-muted">Estoque na obra</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card p-3" style="border-left-color:#adb5bd;">
            <div class="stat-number" style="font-size:0.95rem;">
                <?= ($ind['price_min'] ?? null) !== null ? $fmtMoney($ind['price_min']) : '—' ?>
                <span class="text-muted">/</span>
                <?= ($ind['price_max'] ?? null) !== null ? $fmtMoney($ind['price_max']) : '—' ?>
            </div>
            <div class="text-muted">Preço mín. / máx.</div>
        </div>
    </div>
</div>

<!-- Análises em gráficos -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0">Gastos por Categoria</h6></div>
            <div class="card-body">
                <?php if (empty($charts['spend_by_category'])): ?>
                    <p class="text-muted text-center py-4 mb-0">Sem dados de gastos.</p>
                <?php else: ?>
                    <canvas id="chartSpend" height="220"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0">Pagamentos</h6></div>
            <div class="card-body">
                <?php if ((float) ($ind['paid'] ?? 0) <= 0 && (float) ($ind['to_pay'] ?? 0) <= 0): ?>
                    <p class="text-muted text-center py-4 mb-0">Sem dados de pagamento.</p>
                <?php else: ?>
                    <canvas id="chartPayments" height="220"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header"><h6 class="mb-0">Consumo por Material</h6></div>
            <div class="card-body">
                <?php if (empty($charts['consumption'])): ?>
                    <p class="text-muted text-center py-4 mb-0">Sem consumo de estoque registrado.</p>
                <?php else: ?>
                    <canvas id="chartConsumption" height="220"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tabela: Pedidos -->
<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">Pedidos da Obra</h6></div>
    <div class="card-body p-0">
        <?php if (empty($orders)): ?>
            <p class="text-muted text-center py-4 mb-0">Nenhum pedido vinculado a esta obra.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Código</th><th>Fornecedor</th><th class="text-center">Status</th>
                        <th>Data</th><th class="text-end">Valor</th><th class="text-end">Pago</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><a href="/admin/orders/show/<?= (int) $o['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($o['code'] ?? ('#' . $o['id'])) ?></a></td>
                        <td><?= htmlspecialchars($o['supplier_name'] ?? '—') ?></td>
                        <td class="text-center"><span class="badge bg-secondary"><?= htmlspecialchars($o['status'] ?? '') ?></span></td>
                        <td><?= !empty($o['created_at']) ? date('d/m/Y', strtotime($o['created_at'])) : '—' ?></td>
                        <td class="text-end"><?= $fmtMoney($o['total_estimated'] ?? 0) ?></td>
                        <td class="text-end text-success"><?= $fmtMoney($o['paid'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tabela: Materiais -->
<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">Materiais da Obra</h6></div>
    <div class="card-body p-0">
        <?php if (empty($materials)): ?>
            <p class="text-muted text-center py-4 mb-0">Nenhum material registrado nos pedidos desta obra.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Material</th><th class="text-center">Quantidade</th>
                        <th class="text-end">Preço Unit.</th><th class="text-end">Valor Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materials as $m): ?>
                    <tr>
                        <td><?= htmlspecialchars($m['material_name'] ?? '—') ?></td>
                        <td class="text-center"><?= $fmtQty($m['quantity'] ?? 0) ?></td>
                        <td class="text-end"><?= !empty($m['unit_price']) ? $fmtMoney($m['unit_price']) : '<span class="text-muted">Não informado</span>' ?></td>
                        <td class="text-end"><?= !empty($m['total_price']) ? $fmtMoney($m['total_price']) : '<span class="text-muted">Não informado</span>' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tabela: Fornecedores -->
<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">Fornecedores da Obra</h6></div>
    <div class="card-body p-0">
        <?php if (empty($suppliers)): ?>
            <p class="text-muted text-center py-4 mb-0">Nenhum fornecedor relacionado aos pedidos desta obra.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Fornecedor</th><th>CNPJ</th>
                        <th class="text-center">Pedidos</th><th class="text-end">Total Aprovado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $sup): ?>
                    <tr>
                        <td><?= htmlspecialchars($sup['supplier_name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($sup['cnpj'] ?? '—') ?></td>
                        <td class="text-center"><?= (int) ($sup['orders_count'] ?? 0) ?></td>
                        <td class="text-end"><?= $fmtMoney($sup['approved_total'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tabela: Pagamentos -->
<div class="card mb-3">
    <div class="card-header"><h6 class="mb-0">Pagamentos (NF / Boletos)</h6></div>
    <div class="card-body p-0">
        <?php if (empty($payments)): ?>
            <p class="text-muted text-center py-4 mb-0">Nenhum pagamento registrado para esta obra.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Pedido</th><th>Tipo</th><th>Número</th>
                        <th class="text-end">Valor</th><th>Vencimento</th><th class="text-center">Situação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['order_code'] ?? '—') ?></td>
                        <td><?= htmlspecialchars(strtoupper($p['type'] ?? '')) ?></td>
                        <td><?= htmlspecialchars($p['number'] ?? '—') ?></td>
                        <td class="text-end"><?= $fmtMoney($p['amount'] ?? 0) ?></td>
                        <td><?= !empty($p['due_date']) ? date('d/m/Y', strtotime($p['due_date'])) : '—' ?></td>
                        <td class="text-center">
                            <?php if (!empty($p['paid'])): ?>
                                <span class="badge bg-success">Pago</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Pendente</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tabela: Estoque -->
<div class="card mb-4">
    <div class="card-header"><h6 class="mb-0">Estoque da Obra</h6></div>
    <div class="card-body p-0">
        <?php if (empty($stock)): ?>
            <p class="text-muted text-center py-4 mb-0">Nenhum item de estoque vinculado a esta obra.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Material</th><th>Depósito</th><th class="text-center">Qtd</th>
                        <th class="text-end">Valor Unit.</th><th class="text-end">Valor Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stock as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars($it['material_name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($it['location_name'] ?? '—') ?></td>
                        <td class="text-center"><?= $fmtQty($it['quantity'] ?? 0) ?></td>
                        <td class="text-end"><?= !empty($it['unit_price']) ? $fmtMoney($it['unit_price']) : '<span class="text-muted">Não informado</span>' ?></td>
                        <td class="text-end"><?= $fmtMoney($it['total_value'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    if (typeof Chart === 'undefined') return;

    const palette = ['#0d6efd','#ffc107','#28a745','#6610f2','#fd7e14','#20c997','#dc3545','#6c757d'];
    const brl = v => 'R$ ' + Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // Gastos por categoria (doughnut)
    const spend = <?= json_encode($charts['spend_by_category'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const elSpend = document.getElementById('chartSpend');
    if (elSpend && spend.length) {
        new Chart(elSpend, {
            type: 'doughnut',
            data: {
                labels: spend.map(x => x.label),
                datasets: [{ data: spend.map(x => Number(x.value)), backgroundColor: palette }]
            },
            options: {
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                    tooltip: { callbacks: { label: c => c.label + ': ' + brl(c.parsed) } }
                }
            }
        });
    }

    // Pagamentos (pago x a pagar)
    const pay = <?= json_encode($charts['payments'] ?? ['paid' => 0, 'to_pay' => 0], JSON_UNESCAPED_UNICODE) ?>;
    const elPay = document.getElementById('chartPayments');
    if (elPay && (Number(pay.paid) > 0 || Number(pay.to_pay) > 0)) {
        new Chart(elPay, {
            type: 'doughnut',
            data: {
                labels: ['Pago', 'A pagar'],
                datasets: [{ data: [Number(pay.paid), Number(pay.to_pay)], backgroundColor: ['#28a745', '#fd7e14'] }]
            },
            options: {
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
                    tooltip: { callbacks: { label: c => c.label + ': ' + brl(c.parsed) } }
                }
            }
        });
    }

    // Consumo por material (bar horizontal)
    const cons = <?= json_encode($charts['consumption'] ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const elCons = document.getElementById('chartConsumption');
    if (elCons && cons.length) {
        new Chart(elCons, {
            type: 'bar',
            data: {
                labels: cons.map(x => x.label),
                datasets: [{ label: 'Valor consumido', data: cons.map(x => Number(x.value)), backgroundColor: '#6610f2' }]
            },
            options: {
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: c => brl(c.parsed.x) } }
                },
                scales: { x: { ticks: { callback: v => brl(v) } } }
            }
        });
    }
})();
</script>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
