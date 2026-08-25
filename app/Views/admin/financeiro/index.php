<?php $pageTitle = 'Dashboard de Obras'; $currentPage = 'financeiro'; ob_start(); ?>

<?php
/**
 * Dashboard de Obras — Visao Geral (somente leitura).
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
$currentSort   = $currentSort ?? 'name';
$currentStatus = $currentStatus ?? '';
$sortOptions = [
    'name'          => 'Nome da obra',
    'status'        => 'Status',
    'spent_desc'    => 'Maior valor gasto',
    'spent_asc'     => 'Menor valor gasto',
    'paid_desc'     => 'Maior valor pago',
    'consumed_desc' => 'Maior valor consumido',
    'orders_desc'   => 'Maior qtd. de pedidos',
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Dashboard de Obras</h4>
        <p class="text-muted mb-0">Acompanhamento financeiro e de consumo por obra.</p>
    </div>
    <a href="/admin/obras" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-buildings"></i> Ver Obras
    </a>
</div>

<!-- Filtros e ordenação -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="/admin/financeiro" class="row g-2 align-items-end">
            <div class="col-6 col-md-4">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <?php foreach ($statusLabels as $key => $lbl): ?>
                        <option value="<?= $key ?>" <?= $currentStatus === $key ? 'selected' : '' ?>><?= htmlspecialchars($lbl[0]) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label small mb-1">Ordenar por</label>
                <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($sortOptions as $key => $lbl): ?>
                        <option value="<?= $key ?>" <?= $currentSort === $key ? 'selected' : '' ?>><?= htmlspecialchars($lbl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Aplicar</button>
                <?php if ($currentStatus !== '' || $currentSort !== 'name'): ?>
                <a href="/admin/financeiro" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i> Limpar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
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
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0">Detalhamento por Obra</h6>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <?php if (!empty($sites)): ?>
            <div class="input-group input-group-sm" style="max-width:240px;">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control" id="obraSearch" placeholder="Pesquisar obra...">
            </div>
            <?php endif; ?>
            <span class="text-muted small">Valor consumido total: <?= $fmtMoney($totals['consumed']) ?></span>
        </div>
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
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sites as $s): ?>
                            <?php
                                $st = $statusLabels[$s['status'] ?? ''] ?? [ucfirst((string) ($s['status'] ?? '')), 'secondary'];
                                $ordersCount = (int) ($s['orders_count'] ?? 0);
                            ?>
                            <tr class="obra-row" data-obra="<?= htmlspecialchars(mb_strtolower(($s['name'] ?? '') . ' ' . ($s['code'] ?? '') . ' ' . ($s['city'] ?? ''))) ?>">
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
                                <td class="text-end">
                                    <a href="/admin/financeiro/show/<?= (int) $s['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-graph-up"></i> Ver detalhes
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="obraNoResults" style="display:none;"><td colspan="9" class="text-center text-muted py-3">Nenhuma obra encontrada para a busca.</td></tr>
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
                            <td></td>
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

<script>
// Busca por nome da obra na tabela "Detalhamento por Obra" (client-side).
(function () {
    const input = document.getElementById('obraSearch');
    if (!input) return;
    const rows = Array.prototype.slice.call(document.querySelectorAll('.obra-row'));
    const noResults = document.getElementById('obraNoResults');

    input.addEventListener('input', function () {
        const term = this.value.toLowerCase().trim();
        let matches = 0;
        rows.forEach(function (row) {
            const hay = row.getAttribute('data-obra') || '';
            const show = !term || hay.indexOf(term) !== -1;
            row.style.display = show ? '' : 'none';
            if (show) matches++;
        });
        if (noResults) noResults.style.display = matches === 0 ? '' : 'none';
    });
})();
</script>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
