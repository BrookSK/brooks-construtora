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
                    <div style="position:relative;height:160px;"><canvas id="chartSpend"></canvas></div>
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
                    <div style="position:relative;height:160px;"><canvas id="chartPayments"></canvas></div>
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
                    <div style="position:relative;height:160px;"><canvas id="chartConsumption"></canvas></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tabela: Pedidos -->
<div class="card mb-3 dash-section" data-limit="8">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0">Pedidos da Obra</h6>
        <?php if (!empty($orders)): ?>
        <div class="d-flex gap-2 flex-md-nowrap flex-wrap align-items-center">
            <select class="form-select form-select-sm dash-sort" style="max-width:210px;">
                <option value="">Ordenar por...</option>
                <option value="total_desc">Maior Valor Total</option>
                <option value="total_asc">Menor Valor Total</option>
                <option value="unit_desc">Maior Preço Unitário</option>
                <option value="unit_asc">Menor Preço Unitário</option>
                <option value="qty_desc">Maior qtd. vendas</option>
                <option value="qty_asc">Menor qtd. vendas</option>
            </select>
            <div class="input-group input-group-sm" style="max-width:260px;">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control dash-search" placeholder="Buscar pedido, fornecedor...">
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($orders)): ?>
            <p class="text-muted text-center py-4 mb-0">Nenhum pedido vinculado a esta obra.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle dash-table">
                <thead class="table-light">
                    <tr>
                        <th>Código</th><th>Fornecedor</th><th class="text-center">Status</th>
                        <th>Data</th><th class="text-end">Valor</th><th class="text-end">Pago</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $orderStatusMap = [
                        'draft' => ['Rascunho', 'secondary'],
                        'pending_quote' => ['Aguard. Cotação', 'warning'],
                        'quoted' => ['Cotado', 'info'],
                        'pending_approval' => ['Aguard. Aprovação', 'primary'],
                        'approved' => ['Aprovado', 'success'],
                        'rejected' => ['Rejeitado', 'danger'],
                        'cancelled' => ['Cancelado', 'dark'],
                    ];
                    ?>
                    <?php foreach ($orders as $o): ?>
                    <?php $ost = $orderStatusMap[$o['status'] ?? ''] ?? [ucfirst(str_replace('_', ' ', $o['status'] ?? '')), 'secondary']; ?>
                    <tr class="dash-row" data-search="<?= htmlspecialchars(mb_strtolower(($o['code'] ?? '') . ' ' . ($o['supplier_name'] ?? '') . ' ' . ($ost[0] ?? ''))) ?>" data-total="<?= (float) ($o['total_estimated'] ?? 0) ?>" data-unit="0" data-qty="<?= (float) ($o['total_estimated'] ?? 0) ?>">
                        <td><a href="/admin/orders/show/<?= (int) $o['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($o['code'] ?? ('#' . $o['id'])) ?></a></td>
                        <td><?= htmlspecialchars($o['supplier_name'] ?? '—') ?></td>
                        <td class="text-center"><span class="badge bg-<?= $ost[1] ?>"><?= htmlspecialchars($ost[0]) ?></span></td>
                        <td><?= !empty($o['created_at']) ? date('d/m/Y', strtotime($o['created_at'])) : '—' ?></td>
                        <td class="text-end"><?= $fmtMoney($o['total_estimated'] ?? 0) ?></td>
                        <td class="text-end text-success"><?= $fmtMoney($o['paid'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="dash-no-results" style="display:none;"><td colspan="6" class="text-center text-muted py-3">Nenhum resultado para a busca.</td></tr>
                </tbody>
            </table>
        </div>
        <div class="text-center py-2 border-top dash-more-wrap" style="display:none;">
            <button type="button" class="btn btn-sm btn-outline-primary dash-more-btn">
                <i class="bi bi-chevron-down"></i> Ver mais
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tabela: Materiais -->
<div class="card mb-3 dash-section" data-limit="8">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0">Materiais da Obra</h6>
        <?php if (!empty($materials)): ?>
        <div class="d-flex gap-2 flex-md-nowrap flex-wrap align-items-center">
            <select class="form-select form-select-sm dash-sort" style="max-width:210px;">
                <option value="">Ordenar por...</option>
                <option value="total_desc">Maior Valor Total</option>
                <option value="total_asc">Menor Valor Total</option>
                <option value="unit_desc">Maior Preço Unitário</option>
                <option value="unit_asc">Menor Preço Unitário</option>
                <option value="qty_desc">Maior qtd. vendas</option>
                <option value="qty_asc">Menor qtd. vendas</option>
            </select>
            <div class="input-group input-group-sm" style="max-width:260px;">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control dash-search" placeholder="Buscar material...">
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($materials)): ?>
            <p class="text-muted text-center py-4 mb-0">Nenhum material registrado nos pedidos desta obra.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle dash-table">
                <thead class="table-light">
                    <tr>
                        <th>Material</th><th class="text-center">Quantidade</th>
                        <th class="text-end">Preço Unit.</th><th class="text-end">Valor Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materials as $m): ?>
                    <tr class="dash-row" data-search="<?= htmlspecialchars(mb_strtolower($m['material_name'] ?? '')) ?>" data-total="<?= (float) ($m['total_price'] ?? 0) ?>" data-unit="<?= (float) ($m['unit_price'] ?? 0) ?>" data-qty="<?= (float) ($m['quantity'] ?? 0) ?>">
                        <td><?= htmlspecialchars($m['material_name'] ?? '—') ?></td>
                        <td class="text-center"><?= $fmtQty($m['quantity'] ?? 0) ?></td>
                        <td class="text-end"><?= !empty($m['unit_price']) ? $fmtMoney($m['unit_price']) : '<span class="text-muted">Não informado</span>' ?></td>
                        <td class="text-end"><?= !empty($m['total_price']) ? $fmtMoney($m['total_price']) : '<span class="text-muted">Não informado</span>' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="dash-no-results" style="display:none;"><td colspan="4" class="text-center text-muted py-3">Nenhum resultado para a busca.</td></tr>
                </tbody>
            </table>
        </div>
        <div class="text-center py-2 border-top dash-more-wrap" style="display:none;">
            <button type="button" class="btn btn-sm btn-outline-primary dash-more-btn">
                <i class="bi bi-chevron-down"></i> Ver mais
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tabela: Fornecedores -->
<div class="card mb-3 dash-section" data-limit="8">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0">Fornecedores da Obra</h6>
        <?php if (!empty($suppliers)): ?>
        <div class="d-flex gap-2 flex-md-nowrap flex-wrap align-items-center">
            <select class="form-select form-select-sm dash-sort" style="max-width:210px;">
                <option value="">Ordenar por...</option>
                <option value="total_desc">Maior Valor Total</option>
                <option value="total_asc">Menor Valor Total</option>
                <option value="unit_desc">Maior Preço Unitário</option>
                <option value="unit_asc">Menor Preço Unitário</option>
                <option value="qty_desc">Maior qtd. vendas</option>
                <option value="qty_asc">Menor qtd. vendas</option>
            </select>
            <div class="input-group input-group-sm" style="max-width:260px;">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control dash-search" placeholder="Buscar fornecedor, CNPJ...">
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($suppliers)): ?>
            <p class="text-muted text-center py-4 mb-0">Nenhum fornecedor relacionado aos pedidos desta obra.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle dash-table">
                <thead class="table-light">
                    <tr>
                        <th>Fornecedor</th><th>CNPJ</th>
                        <th class="text-center">Pedidos</th><th class="text-end">Total Aprovado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suppliers as $sup): ?>
                    <tr class="dash-row" data-search="<?= htmlspecialchars(mb_strtolower(($sup['supplier_name'] ?? '') . ' ' . ($sup['cnpj'] ?? ''))) ?>" data-total="<?= (float) ($sup['approved_total'] ?? 0) ?>" data-unit="0" data-qty="<?= (int) ($sup['orders_count'] ?? 0) ?>">
                        <td><?= htmlspecialchars($sup['supplier_name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($sup['cnpj'] ?? '—') ?></td>
                        <td class="text-center"><?= (int) ($sup['orders_count'] ?? 0) ?></td>
                        <td class="text-end"><?= $fmtMoney($sup['approved_total'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="dash-no-results" style="display:none;"><td colspan="4" class="text-center text-muted py-3">Nenhum resultado para a busca.</td></tr>
                </tbody>
            </table>
        </div>
        <div class="text-center py-2 border-top dash-more-wrap" style="display:none;">
            <button type="button" class="btn btn-sm btn-outline-primary dash-more-btn">
                <i class="bi bi-chevron-down"></i> Ver mais
            </button>
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
<div class="card mb-4 dash-section" data-limit="8">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0">Estoque da Obra</h6>
        <?php if (!empty($stock)): ?>
        <div class="d-flex gap-2 flex-md-nowrap flex-wrap align-items-center">
            <select class="form-select form-select-sm dash-sort" style="max-width:210px;">
                <option value="">Ordenar por...</option>
                <option value="total_desc">Maior Valor Total</option>
                <option value="total_asc">Menor Valor Total</option>
                <option value="unit_desc">Maior Preço Unitário</option>
                <option value="unit_asc">Menor Preço Unitário</option>
            </select>
            <div class="input-group input-group-sm" style="max-width:260px;">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control dash-search" placeholder="Buscar material, depósito...">
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($stock)): ?>
            <p class="text-muted text-center py-4 mb-0">Nenhum item de estoque vinculado a esta obra.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle dash-table">
                <thead class="table-light">
                    <tr>
                        <th>Material</th><th>Depósito</th><th class="text-center">Qtd</th>
                        <th class="text-end">Valor Unit.</th><th class="text-end">Valor Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stock as $it): ?>
                    <tr class="dash-row" data-search="<?= htmlspecialchars(mb_strtolower(($it['material_name'] ?? '') . ' ' . ($it['location_name'] ?? ''))) ?>" data-total="<?= (float) ($it['total_value'] ?? 0) ?>" data-unit="<?= (float) ($it['unit_price'] ?? 0) ?>">
                        <td><?= htmlspecialchars($it['material_name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($it['location_name'] ?? '—') ?></td>
                        <td class="text-center"><?= $fmtQty($it['quantity'] ?? 0) ?></td>
                        <td class="text-end"><?= !empty($it['unit_price']) ? $fmtMoney($it['unit_price']) : '<span class="text-muted">Não informado</span>' ?></td>
                        <td class="text-end"><?= $fmtMoney($it['total_value'] ?? 0) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="dash-no-results" style="display:none;"><td colspan="5" class="text-center text-muted py-3">Nenhum resultado para a busca.</td></tr>
                </tbody>
            </table>
        </div>
        <div class="text-center py-2 border-top dash-more-wrap" style="display:none;">
            <button type="button" class="btn btn-sm btn-outline-primary dash-more-btn">
                <i class="bi bi-chevron-down"></i> Ver mais
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// ===== Busca + "Ver mais" por seção (Pedidos, Materiais, Fornecedores, Estoque) =====
(function () {
    document.querySelectorAll('.dash-section').forEach(function (section) {
        const limit    = parseInt(section.getAttribute('data-limit') || '0', 10);
        const rows     = Array.prototype.slice.call(section.querySelectorAll('.dash-row'));
        const searchEl = section.querySelector('.dash-search');
        const sortEl   = section.querySelector('.dash-sort');
        const moreWrap = section.querySelector('.dash-more-wrap');
        const moreBtn  = section.querySelector('.dash-more-btn');
        const noResult = section.querySelector('.dash-no-results');
        const tbody    = rows.length ? rows[0].parentNode : null;
        let expanded   = false;

        // Reordena as linhas no DOM conforme o criterio escolhido.
        function applySort() {
            if (!sortEl || !tbody) return;
            const mode = sortEl.value;
            if (!mode) return; // "Ordenar por..." mantem a ordem original
            const key = mode.indexOf('unit') === 0 ? 'unit' : (mode.indexOf('qty') === 0 ? 'qty' : 'total');
            const dir = mode.indexOf('asc') !== -1 ? 1 : -1;
            const sorted = rows.slice().sort(function (a, b) {
                const va = parseFloat(a.getAttribute('data-' + key)) || 0;
                const vb = parseFloat(b.getAttribute('data-' + key)) || 0;
                return (va - vb) * dir;
            });
            sorted.forEach(function (row) { tbody.appendChild(row); });
            if (noResult) tbody.appendChild(noResult); // mantem a linha de "sem resultado" no fim
        }

        // Renderiza conforme busca + estado de expansão.
        function render() {
            const term = (searchEl && searchEl.value ? searchEl.value : '').toLowerCase().trim();
            const searching = term !== '';
            let matches = 0, shown = 0;

            rows.forEach(function (row) {
                const hay = row.getAttribute('data-search') || '';
                const match = !term || hay.indexOf(term) !== -1;
                if (!match) { row.style.display = 'none'; return; }
                matches++;
                // Durante a busca, mostra todos os que casam (ignora limite).
                if (searching || expanded || limit <= 0 || shown < limit) {
                    row.style.display = '';
                    shown++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (noResult) noResult.style.display = matches === 0 ? '' : 'none';

            // Botão "Ver mais / Exibir menos": só quando não está buscando, há limite e há excedente.
            if (moreWrap && moreBtn) {
                if (!searching && limit > 0 && matches > limit) {
                    moreWrap.style.display = '';
                    if (expanded) {
                        moreBtn.innerHTML = '<i class="bi bi-chevron-up"></i> Exibir menos';
                    } else {
                        moreBtn.innerHTML = '<i class="bi bi-chevron-down"></i> Exibir mais (' + (matches - limit) + ')';
                    }
                } else {
                    moreWrap.style.display = 'none';
                }
            }
        }

        if (moreBtn) {
            moreBtn.addEventListener('click', function () {
                expanded = !expanded;
                render();
                // Ao recolher, reposiciona a rolagem no topo da seção.
                if (!expanded) {
                    section.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        }
        if (searchEl) {
            searchEl.addEventListener('input', render);
        }
        if (sortEl) {
            sortEl.addEventListener('change', function () { applySort(); render(); });
        }
        render();
    });
})();
</script>

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
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', align: 'center', labels: { boxWidth: 12, font: { size: 11 } } },
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
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right', align: 'center', labels: { boxWidth: 12, font: { size: 11 } } },
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
                responsive: true,
                maintainAspectRatio: false,
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
