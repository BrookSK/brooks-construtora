<?php $pageTitle = 'Pedidos de Materiais'; $currentPage = 'orders'; ?>
<?php ob_start(); ?>

<style>
.financial-reviewed td { background-color: rgba(34, 197, 94, 0.08) !important; }
.filters-panel { background: #f8f9fa; border-radius: 8px; padding: 12px; }
.filters-panel select, .filters-panel input { font-size: 0.85rem; }
.filter-btn.active { font-weight: 600; }
.filters-toggle { cursor: pointer; }
.filters-toggle .bi-chevron-down { transition: transform 0.2s; }
.filters-toggle.collapsed .bi-chevron-down { transform: rotate(-90deg); }
.order-count-badge { font-size: 0.75rem; }
@media (max-width: 767px) {
    .filters-panel select, .filters-panel input { font-size: 0.8rem; height: 36px; }
    .filters-panel .row > div { margin-bottom: 6px; }
}
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <span class="badge bg-secondary order-count-badge"><?= count($orders) ?> pedidos</span>
    <div class="d-flex flex-wrap gap-1 justify-content-end">
        <?php if (\App\Core\Auth::hasPermission('orders.create')): ?>
        <a href="/admin/orders/tracking" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-clipboard-check"></i> <span class="d-none d-sm-inline">Acompanhamento</span><span class="d-sm-none">Acomp.</span>
        </a>
        <a href="/admin/orders/spare-items" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-bag-plus"></i> <span class="d-none d-sm-inline">Sobressalentes</span><span class="d-sm-none">Sobress.</span>
        </a>
        <a href="/admin/orders/create" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> <span class="d-none d-sm-inline">Novo Pedido</span><span class="d-sm-none">Novo</span>
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filtros de Status (botões rápidos) -->
<div class="d-flex flex-wrap gap-1 mb-2">
    <button class="btn btn-sm btn-outline-secondary filter-btn active" data-status="all">Todos</button>
    <button class="btn btn-sm btn-outline-warning filter-btn" data-status="pending_quote">Cotação</button>
    <button class="btn btn-sm btn-outline-info filter-btn" data-status="pending_approval">Aprovação</button>
    <button class="btn btn-sm btn-outline-success filter-btn" data-status="approved">Aprovados</button>
    <button class="btn btn-sm btn-outline-danger filter-btn" data-status="rejected">Rejeitados</button>
    <button class="btn btn-sm btn-outline-dark filter-btn" data-status="cancelled">Cancelados</button>
</div>

<!-- Painel de Filtros Avançados -->
<div class="mb-3">
    <a class="filters-toggle d-inline-flex align-items-center gap-1 text-muted small text-decoration-none" data-bs-toggle="collapse" href="#advancedFilters" role="button" aria-expanded="false">
        <i class="bi bi-funnel"></i> Filtros avançados <i class="bi bi-chevron-down"></i>
    </a>
    <div class="collapse mt-2" id="advancedFilters">
        <div class="filters-panel">
            <div class="row g-2">
                <!-- Busca -->
                <div class="col-12 col-md-4">
                    <input type="text" id="filterSearch" class="form-control form-control-sm" placeholder="Buscar por código, obra, fornecedor...">
                </div>
                <!-- Fornecedor -->
                <div class="col-6 col-md-2">
                    <select id="filterSupplier" class="form-select form-select-sm">
                        <option value="">Fornecedor</option>
                        <?php
                        $supplierNames = array_unique(array_filter(array_column($orders, 'supplier_name')));
                        sort($supplierNames);
                        foreach ($supplierNames as $sn): ?>
                        <option value="<?= htmlspecialchars($sn) ?>"><?= htmlspecialchars($sn) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Obra -->
                <div class="col-6 col-md-2">
                    <select id="filterSite" class="form-select form-select-sm">
                        <option value="">Obra</option>
                        <?php
                        $siteNames = [];
                        foreach ($orders as $o) {
                            if (!empty($o['construction_site_name'])) {
                                $key = $o['construction_site_code'] . ' - ' . $o['construction_site_name'];
                                $siteNames[$key] = $key;
                            }
                        }
                        ksort($siteNames);
                        foreach ($siteNames as $sn): ?>
                        <option value="<?= htmlspecialchars($sn) ?>"><?= htmlspecialchars($sn) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Tipo -->
                <div class="col-6 col-md-2">
                    <select id="filterType" class="form-select form-select-sm">
                        <option value="">Tipo</option>
                        <option value="material">Material</option>
                        <option value="service">Serviço</option>
                    </select>
                </div>
                <!-- Financeiro -->
                <div class="col-6 col-md-2">
                    <select id="filterFinancial" class="form-select form-select-sm">
                        <option value="">Financeiro</option>
                        <option value="reviewed">Conferido</option>
                        <option value="not_reviewed">Não conferido</option>
                    </select>
                </div>
                <!-- Período -->
                <div class="col-6 col-md-3">
                    <input type="date" id="filterDateFrom" class="form-control form-control-sm" title="Data inicial">
                </div>
                <div class="col-6 col-md-3">
                    <input type="date" id="filterDateTo" class="form-control form-control-sm" title="Data final">
                </div>
                <!-- Solicitante -->
                <div class="col-6 col-md-2">
                    <select id="filterRequester" class="form-select form-select-sm">
                        <option value="">Solicitante</option>
                        <?php
                        $requesters = array_unique(array_filter(array_column($orders, 'created_by_name')));
                        sort($requesters);
                        foreach ($requesters as $r): ?>
                        <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Limpar -->
                <div class="col-6 col-md-2 col-lg-1 d-flex align-items-end">
                    <button type="button" id="clearFilters" class="btn btn-sm btn-outline-secondary w-100">
                        <i class="bi bi-x-lg"></i> Limpar
                    </button>
                </div>
            </div>
            <div class="mt-2">
                <small class="text-muted"><span id="filteredCount"><?= count($orders) ?></span> de <?= count($orders) ?> pedidos</small>
            </div>
        </div>
    </div>
</div>

<!-- Lista mobile-friendly -->
<?php if (empty($orders)): ?>
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <i class="bi bi-inbox" style="font-size:2.5rem;"></i>
        <p class="mt-2 mb-0">Nenhum pedido registrado.</p>
        <a href="/admin/orders/create" class="btn btn-primary mt-3">Criar Primeiro Pedido</a>
    </div>
</div>
<?php else: ?>

<!-- Desktop: Tabela | Mobile: Cards -->
<div class="d-none d-md-block">
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Obra</th>
                        <th>Fornecedor</th>
                        <th>Status</th>
                        <th>Valor</th>
                        <th>Solicitante</th>
                        <th>Data</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <?php
                    $statusLabels = [
                        'draft' => ['Rascunho', 'secondary'],
                        'pending_quote' => ['Aguard. Cotação', 'warning'],
                        'quoted' => ['Cotado', 'info'],
                        'pending_approval' => ['Aguard. Aprovação', 'info'],
                        'approved' => ['Aprovado', 'success'],
                        'rejected' => ['Rejeitado', 'danger'],
                        'cancelled' => ['Cancelado', 'dark'],
                    ];
                    $label = $statusLabels[$order['status']] ?? ['Desconhecido', 'secondary'];
                    $siteName = !empty($order['construction_site_name']) ? $order['construction_site_code'] . ' - ' . $order['construction_site_name'] : '';
                    ?>
                    <?php $showFinancialReview = \App\Core\Auth::isSuperAdmin() || \App\Core\Auth::hasPermission('orders.payment'); ?>
                    <?php $isReviewed = $showFinancialReview && !empty($order['financial_reviewed_at']); ?>
                    <tr class="order-row <?= $isReviewed ? 'financial-reviewed' : '' ?>"
                        data-status="<?= $order['status'] ?>"
                        data-supplier="<?= htmlspecialchars($order['supplier_name'] ?? '') ?>"
                        data-site="<?= htmlspecialchars($siteName) ?>"
                        data-type="<?= htmlspecialchars($order['order_type'] ?? 'material') ?>"
                        data-financial="<?= !empty($order['financial_reviewed_at']) ? 'reviewed' : 'not_reviewed' ?>"
                        data-date="<?= date('Y-m-d', strtotime($order['created_at'])) ?>"
                        data-requester="<?= htmlspecialchars($order['created_by_name'] ?? '') ?>"
                        data-search="<?= htmlspecialchars(strtolower(($order['code'] ?? '') . ' ' . ($order['supplier_name'] ?? '') . ' ' . $siteName . ' ' . ($order['created_by_name'] ?? '') . ' ' . ($order['description'] ?? ''))) ?>">
                        <td>
                            <a href="/admin/orders/show/<?= $order['id'] ?>" class="fw-bold text-decoration-none">
                                <?= htmlspecialchars($order['code']) ?>
                            </a>
                            <?php if (($order['order_type'] ?? 'material') === 'service'): ?>
                            <span class="badge bg-dark ms-1" style="font-size:0.65rem;"><i class="bi bi-wrench"></i> Serviço</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($order['construction_site_name'])): ?>
                            <small><i class="bi bi-buildings"></i> <?= htmlspecialchars($siteName) ?></small>
                            <?php else: ?>
                            <small class="text-muted">-</small>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($order['supplier_name'] ?? 'N/A') ?></td>
                        <td><span class="badge bg-<?= $label[1] ?>"><?= $label[0] ?></span>
                            <?php if ($showFinancialReview && !empty($order['financial_reviewed_at'])): ?>
                            <span class="badge bg-success ms-1" style="font-size:0.6rem;" title="Revisado por <?= htmlspecialchars($order['financial_reviewed_by'] ?? '') ?> em <?= date('d/m/Y', strtotime($order['financial_reviewed_at'])) ?>"><i class="bi bi-check2"></i> Financeiro</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php $orderTotal = $order['display_total'] ?? $order['total_estimated']; ?>
                            <?= $orderTotal > 0 ? '<strong>R$ ' . number_format($orderTotal, 2, ',', '.') . '</strong>' : '<span class="text-muted">-</span>' ?>
                        </td>
                        <td><?= htmlspecialchars($order['created_by_name'] ?? '-') ?></td>
                        <td><small class="text-muted"><?= date('d/m/Y', strtotime($order['created_at'])) ?></small></td>
                        <td class="text-end">
                            <a href="/admin/orders/show/<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Mobile Cards -->
<div class="d-md-none">
    <?php foreach ($orders as $order): ?>
    <?php
    $statusLabels = [
        'draft' => ['Rascunho', 'secondary'],
        'pending_quote' => ['Aguard. Cotação', 'warning'],
        'quoted' => ['Cotado', 'info'],
        'pending_approval' => ['Aguard. Aprovação', 'info'],
        'approved' => ['Aprovado', 'success'],
        'rejected' => ['Rejeitado', 'danger'],
        'cancelled' => ['Cancelado', 'dark'],
    ];
    $label = $statusLabels[$order['status']] ?? ['Desconhecido', 'secondary'];
    $siteName = !empty($order['construction_site_name']) ? $order['construction_site_code'] . ' - ' . $order['construction_site_name'] : '';
    ?>
    <a href="/admin/orders/show/<?= $order['id'] ?>" class="text-decoration-none order-row"
       data-status="<?= $order['status'] ?>"
       data-supplier="<?= htmlspecialchars($order['supplier_name'] ?? '') ?>"
       data-site="<?= htmlspecialchars($siteName) ?>"
       data-type="<?= htmlspecialchars($order['order_type'] ?? 'material') ?>"
       data-financial="<?= !empty($order['financial_reviewed_at']) ? 'reviewed' : 'not_reviewed' ?>"
       data-date="<?= date('Y-m-d', strtotime($order['created_at'])) ?>"
       data-requester="<?= htmlspecialchars($order['created_by_name'] ?? '') ?>"
       data-search="<?= htmlspecialchars(strtolower(($order['code'] ?? '') . ' ' . ($order['supplier_name'] ?? '') . ' ' . $siteName . ' ' . ($order['created_by_name'] ?? '') . ' ' . ($order['description'] ?? ''))) ?>">
        <div class="card mb-2">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <strong class="text-dark"><?= htmlspecialchars($order['code']) ?></strong>
                    <div>
                        <span class="badge bg-<?= $label[1] ?>" style="font-size:0.7rem;"><?= $label[0] ?></span>
                        <?php if ((\App\Core\Auth::isSuperAdmin() || \App\Core\Auth::hasPermission('orders.payment')) && !empty($order['financial_reviewed_at'])): ?>
                        <span class="badge bg-success" style="font-size:0.6rem;"><i class="bi bi-check2"></i></span>
                        <?php endif; ?>
                        <?php if (($order['order_type'] ?? 'material') === 'service'): ?>
                        <span class="badge bg-dark" style="font-size:0.6rem;"><i class="bi bi-wrench"></i></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <span class="text-muted small"><?= htmlspecialchars($order['supplier_name'] ?? 'Sem fornecedor') ?></span>
                    <?php $orderTotal = $order['display_total'] ?? $order['total_estimated']; ?>
                    <?php if ($orderTotal > 0): ?>
                    <strong class="text-success small">R$ <?= number_format($orderTotal, 2, ',', '.') ?></strong>
                    <?php endif; ?>
                </div>
                <?php if (!empty($order['construction_site_name'])): ?>
                <div class="mt-1">
                    <span class="text-muted" style="font-size:0.7rem;"><i class="bi bi-buildings"></i> <?= htmlspecialchars($siteName) ?></span>
                </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between mt-1">
                    <span class="text-muted" style="font-size:0.7rem;"><?= htmlspecialchars($order['created_by_name'] ?? '') ?></span>
                    <span class="text-muted" style="font-size:0.7rem;"><?= date('d/m/Y', strtotime($order['created_at'])) ?></span>
                </div>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<script>
(function() {
    const rows = document.querySelectorAll('.order-row');
    const statusBtns = document.querySelectorAll('.filter-btn');
    const searchInput = document.getElementById('filterSearch');
    const supplierSelect = document.getElementById('filterSupplier');
    const siteSelect = document.getElementById('filterSite');
    const typeSelect = document.getElementById('filterType');
    const financialSelect = document.getElementById('filterFinancial');
    const dateFrom = document.getElementById('filterDateFrom');
    const dateTo = document.getElementById('filterDateTo');
    const requesterSelect = document.getElementById('filterRequester');
    const clearBtn = document.getElementById('clearFilters');
    const countEl = document.getElementById('filteredCount');

    let activeStatus = 'all';

    function applyFilters() {
        const search = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const supplier = supplierSelect ? supplierSelect.value : '';
        const site = siteSelect ? siteSelect.value : '';
        const type = typeSelect ? typeSelect.value : '';
        const financial = financialSelect ? financialSelect.value : '';
        const from = dateFrom ? dateFrom.value : '';
        const to = dateTo ? dateTo.value : '';
        const requester = requesterSelect ? requesterSelect.value : '';

        let visible = 0;

        rows.forEach(row => {
            let show = true;

            // Status
            if (activeStatus !== 'all' && row.dataset.status !== activeStatus) show = false;

            // Search
            if (show && search && !(row.dataset.search || '').includes(search)) show = false;

            // Supplier
            if (show && supplier && row.dataset.supplier !== supplier) show = false;

            // Site
            if (show && site && row.dataset.site !== site) show = false;

            // Type
            if (show && type && row.dataset.type !== type) show = false;

            // Financial
            if (show && financial && row.dataset.financial !== financial) show = false;

            // Date range
            if (show && from && row.dataset.date < from) show = false;
            if (show && to && row.dataset.date > to) show = false;

            // Requester
            if (show && requester && row.dataset.requester !== requester) show = false;

            // Show/hide
            const el = row.closest('tr') || row;
            el.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (countEl) countEl.textContent = visible;
    }

    // Status buttons
    statusBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            statusBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeStatus = this.dataset.status;
            applyFilters();
        });
    });

    // Advanced filters
    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (supplierSelect) supplierSelect.addEventListener('change', applyFilters);
    if (siteSelect) siteSelect.addEventListener('change', applyFilters);
    if (typeSelect) typeSelect.addEventListener('change', applyFilters);
    if (financialSelect) financialSelect.addEventListener('change', applyFilters);
    if (dateFrom) dateFrom.addEventListener('change', applyFilters);
    if (dateTo) dateTo.addEventListener('change', applyFilters);
    if (requesterSelect) requesterSelect.addEventListener('change', applyFilters);

    // Clear
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            if (supplierSelect) supplierSelect.value = '';
            if (siteSelect) siteSelect.value = '';
            if (typeSelect) typeSelect.value = '';
            if (financialSelect) financialSelect.value = '';
            if (dateFrom) dateFrom.value = '';
            if (dateTo) dateTo.value = '';
            if (requesterSelect) requesterSelect.value = '';
            statusBtns.forEach(b => b.classList.remove('active'));
            statusBtns[0].classList.add('active');
            activeStatus = 'all';
            applyFilters();
        });
    }

    // Toggle icon rotation
    const toggle = document.querySelector('.filters-toggle');
    if (toggle) {
        const collapseEl = document.getElementById('advancedFilters');
        if (collapseEl) {
            collapseEl.addEventListener('show.bs.collapse', () => toggle.classList.remove('collapsed'));
            collapseEl.addEventListener('hide.bs.collapse', () => toggle.classList.add('collapsed'));
            toggle.classList.add('collapsed');
        }
    }
})();
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
