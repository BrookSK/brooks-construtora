<?php $pageTitle = 'Pedidos de Materiais'; $currentPage = 'orders'; ?>
<?php ob_start(); ?>

<style>
.financial-reviewed td { background-color: rgba(139, 92, 246, 0.08) !important; }
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
        <a href="/admin/orders/shopping-list" class="btn btn-outline-success btn-sm">
            <i class="bi bi-cart-check"></i> <span class="d-none d-sm-inline">Lista de Compras</span><span class="d-sm-none">Compras</span>
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
    <button class="btn btn-sm btn-outline-primary filter-btn" data-flag="in_transport">Em Transporte</button>
    <button class="btn btn-sm btn-outline-success filter-btn" data-flag="arrived">Chegou</button>
    <button class="btn btn-sm btn-outline-danger filter-btn" data-status="rejected">Rejeitados</button>
    <button class="btn btn-sm btn-outline-dark filter-btn" data-status="cancelled">Cancelados</button>
    <span class="ms-2 border-start ps-2 d-flex align-items-center gap-1">
        <small class="text-muted">Ordenar:</small>
        <button class="btn btn-sm btn-outline-primary sort-btn" data-sort="deadline" title="Ordenar por prazo (mais urgente primeiro)"><i class="bi bi-calendar-event"></i> Prazo</button>
        <button class="btn btn-sm btn-outline-danger sort-btn" data-sort="urgency" title="Ordenar por urgência (crítica primeiro)"><i class="bi bi-exclamation-triangle"></i> Urgência</button>
        <button class="btn btn-sm btn-outline-secondary sort-btn" data-sort="date" title="Ordenar por data (mais recente primeiro)"><i class="bi bi-clock"></i> Data</button>
    </span>
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
                <div class="col-12 col-md-3">
                    <input type="text" id="filterSearch" class="form-control form-control-sm" placeholder="Buscar por código, obra, fornecedor...">
                </div>
                <!-- Busca Material -->
                <div class="col-12 col-md-3">
                    <input type="text" id="filterMaterial" class="form-control form-control-sm" placeholder="Buscar por material/item...">
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
                        <option value="all">Todos (incluir conferidos)</option>
                    </select>
                </div>
                <!-- Comprado -->
                <div class="col-6 col-md-2">
                    <select id="filterPurchased" class="form-select form-select-sm">
                        <option value="">Comprado</option>
                        <option value="purchased">Comprado</option>
                        <option value="not_purchased">Não comprado</option>
                    </select>
                </div>
                <!-- Saiu do Estoque -->
                <div class="col-6 col-md-2">
                    <select id="filterStockDispatched" class="form-select form-select-sm">
                        <option value="">Saiu Estoque</option>
                        <option value="dispatched">Sim</option>
                        <option value="not_dispatched">Não</option>
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
                <!-- Urgência -->
                <div class="col-6 col-md-2">
                    <select id="filterUrgency" class="form-select form-select-sm">
                        <option value="">Urgência</option>
                        <option value="critical">🔴 Crítica</option>
                        <option value="high">🟠 Alta</option>
                        <option value="medium">🟡 Média</option>
                        <option value="low">🟢 Baixa</option>
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
                        <th>Urgência</th>
                        <th>Prazo</th>
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
                        data-purchased="<?= !empty($order['purchased_at']) ? 'purchased' : 'not_purchased' ?>"
                        data-in-transport="<?= !empty($order['in_transport_at']) ? 'in_transport' : 'not_in_transport' ?>"
                        data-arrived="<?= !empty($order['arrived_at']) ? 'arrived' : 'not_arrived' ?>"
                        data-stock-dispatched="<?= !empty($order['stock_dispatched_at']) ? 'dispatched' : 'not_dispatched' ?>"
                        data-date="<?= date('Y-m-d', strtotime($order['created_at'])) ?>"
                        data-requester="<?= htmlspecialchars($order['created_by_name'] ?? '') ?>"
                        data-urgency="<?= htmlspecialchars($order['urgency'] ?? 'medium') ?>"
                        data-deadline="<?= !empty($order['deadline']) ? $order['deadline'] : '' ?>"
                        data-items="<?= htmlspecialchars(strtolower($order['items_names'] ?? '')) ?>"
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
                            <?php if (!empty($order['quote_started_at']) && $order['status'] === 'pending_quote'): ?>
                            <span class="badge ms-1 bg-success" style="font-size:0.6rem;" title="Cotação iniciada por <?= htmlspecialchars($order['quote_started_by'] ?? '') ?> em <?= date('d/m/Y H:i', strtotime($order['quote_started_at'])) ?>"><i class="bi bi-play-fill"></i> Cotação Iniciada</span>
                            <?php endif; ?>
                            <?php if ($showFinancialReview && !empty($order['financial_reviewed_at'])): ?>
                            <span class="badge ms-1" style="font-size:0.6rem; background-color:#8b5cf6;" title="Revisado por <?= htmlspecialchars($order['financial_reviewed_by'] ?? '') ?> em <?= date('d/m/Y', strtotime($order['financial_reviewed_at'])) ?>"><i class="bi bi-check2"></i> Financeiro</span>
                            <?php endif; ?>
                            <?php if (!empty($order['arrived_at'])): ?>
                            <span class="badge ms-1" style="font-size:0.6rem; background-color:#198754;" title="Chegou na obra — <?= htmlspecialchars($order['arrived_by'] ?? '') ?> em <?= date('d/m/Y', strtotime($order['arrived_at'])) ?>"><i class="bi bi-geo-alt-fill"></i> Chegou</span>
                            <?php elseif (!empty($order['in_transport_at'])): ?>
                            <span class="badge ms-1" style="font-size:0.6rem; background-color:#0d6efd;" title="Em transporte — <?= htmlspecialchars($order['in_transport_by'] ?? '') ?> em <?= date('d/m/Y', strtotime($order['in_transport_at'])) ?>"><i class="bi bi-truck"></i> Em Transporte</span>
                            <?php elseif (!empty($order['purchased_at'])): ?>
                            <span class="badge ms-1" style="font-size:0.6rem; background-color:#e67e22;" title="Comprado por <?= htmlspecialchars($order['purchased_by'] ?? '') ?> em <?= date('d/m/Y', strtotime($order['purchased_at'])) ?>"><i class="bi bi-bag-check"></i> Comprado</span>
                            <?php endif; ?>
                            <?php if (!empty($order['stock_dispatched_at'])): ?>
                            <span class="badge ms-1" style="font-size:0.6rem; background-color:#607d8b;" title="Saiu do estoque — <?= htmlspecialchars($order['stock_dispatched_by'] ?? '') ?> em <?= date('d/m/Y', strtotime($order['stock_dispatched_at'])) ?>"><i class="bi bi-box-arrow-right"></i> Saiu Estoque</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $urgencyLabels = ['low' => ['Baixa','success'], 'medium' => ['Média','warning'], 'high' => ['Alta','orange'], 'critical' => ['Crítica','danger']];
                            $urg = $urgencyLabels[$order['urgency'] ?? 'medium'] ?? ['Média','warning'];
                            ?>
                            <span class="badge bg-<?= $urg[1] ?>"><?= $urg[0] ?></span>
                        </td>
                        <td>
                            <?php if (!empty($order['deadline'])): ?>
                            <?php
                            $deadlineDate = $order['deadline'];
                            $daysLeft = (int) ((strtotime($deadlineDate) - strtotime('today')) / 86400);
                            $deadlineClass = $daysLeft < 0 ? 'text-danger fw-bold' : ($daysLeft <= 2 ? 'text-warning fw-bold' : 'text-muted');
                            ?>
                            <small class="<?= $deadlineClass ?>"><?= date('d/m/Y', strtotime($deadlineDate)) ?></small>
                            <?php if ($daysLeft < 0): ?>
                            <br><span class="badge bg-danger" style="font-size:0.6rem;">Atrasado</span>
                            <?php elseif ($daysLeft === 0): ?>
                            <br><span class="badge bg-warning text-dark" style="font-size:0.6rem;">Hoje</span>
                            <?php elseif ($daysLeft <= 2): ?>
                            <br><span class="badge bg-warning text-dark" style="font-size:0.6rem;"><?= $daysLeft ?>d</span>
                            <?php endif; ?>
                            <?php else: ?>
                            <small class="text-muted">-</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php $orderTotal = $order['display_total'] ?? $order['total_estimated']; ?>
                            <?= $orderTotal > 0 ? '<strong>R$ ' . number_format($orderTotal, 2, ',', '.') . '</strong>' : '<span class="text-muted">-</span>' ?>
                            <?php $nfTotal = (float)($order['nf_total'] ?? 0); ?>
                            <?php if ($nfTotal > 0 && $nfTotal != $orderTotal): ?>
                            <br><small class="text-muted" title="Valor NF"><i class="bi bi-receipt"></i> NF: R$ <?= number_format($nfTotal, 2, ',', '.') ?></small>
                            <?php endif; ?>
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
       data-purchased="<?= !empty($order['purchased_at']) ? 'purchased' : 'not_purchased' ?>"
       data-in-transport="<?= !empty($order['in_transport_at']) ? 'in_transport' : 'not_in_transport' ?>"
       data-arrived="<?= !empty($order['arrived_at']) ? 'arrived' : 'not_arrived' ?>"
       data-stock-dispatched="<?= !empty($order['stock_dispatched_at']) ? 'dispatched' : 'not_dispatched' ?>"
       data-date="<?= date('Y-m-d', strtotime($order['created_at'])) ?>"
       data-requester="<?= htmlspecialchars($order['created_by_name'] ?? '') ?>"
       data-urgency="<?= htmlspecialchars($order['urgency'] ?? 'medium') ?>"
       data-deadline="<?= !empty($order['deadline']) ? $order['deadline'] : '' ?>"
       data-items="<?= htmlspecialchars(strtolower($order['items_names'] ?? '')) ?>"
       data-search="<?= htmlspecialchars(strtolower(($order['code'] ?? '') . ' ' . ($order['supplier_name'] ?? '') . ' ' . $siteName . ' ' . ($order['created_by_name'] ?? '') . ' ' . ($order['description'] ?? ''))) ?>">
        <div class="card mb-2">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <strong class="text-dark"><?= htmlspecialchars($order['code']) ?></strong>
                    <div>
                        <span class="badge bg-<?= $label[1] ?>" style="font-size:0.7rem;"><?= $label[0] ?></span>
                        <?php if (!empty($order['quote_started_at']) && $order['status'] === 'pending_quote'): ?>
                        <span class="badge bg-success" style="font-size:0.6rem;" title="Cotação iniciada por <?= htmlspecialchars($order['quote_started_by'] ?? '') ?>"><i class="bi bi-play-fill"></i></span>
                        <?php endif; ?>
                        <?php if ((\App\Core\Auth::isSuperAdmin() || \App\Core\Auth::hasPermission('orders.payment')) && !empty($order['financial_reviewed_at'])): ?>
                        <span class="badge" style="font-size:0.6rem; background-color:#8b5cf6;"><i class="bi bi-check2"></i></span>
                        <?php endif; ?>
                        <?php if (!empty($order['arrived_at'])): ?>
                        <span class="badge" style="font-size:0.6rem; background-color:#198754;" title="Chegou na obra"><i class="bi bi-geo-alt-fill"></i></span>
                        <?php elseif (!empty($order['in_transport_at'])): ?>
                        <span class="badge" style="font-size:0.6rem; background-color:#0d6efd;" title="Em transporte"><i class="bi bi-truck"></i></span>
                        <?php elseif (!empty($order['purchased_at'])): ?>
                        <span class="badge" style="font-size:0.6rem; background-color:#e67e22;"><i class="bi bi-bag-check"></i></span>
                        <?php endif; ?>
                        <?php if (!empty($order['stock_dispatched_at'])): ?>
                        <span class="badge" style="font-size:0.6rem; background-color:#607d8b;" title="Saiu do Estoque"><i class="bi bi-box-arrow-right"></i></span>
                        <?php endif; ?>
                        <?php if (($order['order_type'] ?? 'material') === 'service'): ?>
                        <span class="badge bg-dark" style="font-size:0.6rem;"><i class="bi bi-wrench"></i></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <span class="text-muted small"><?= htmlspecialchars($order['supplier_name'] ?? 'Sem fornecedor') ?></span>
                    <div class="text-end">
                        <?php $orderTotal = $order['display_total'] ?? $order['total_estimated']; ?>
                        <?php if ($orderTotal > 0): ?>
                        <strong class="text-success small">R$ <?= number_format($orderTotal, 2, ',', '.') ?></strong>
                        <?php endif; ?>
                        <?php $nfTotal = (float)($order['nf_total'] ?? 0); ?>
                        <?php if ($nfTotal > 0 && $nfTotal != $orderTotal): ?>
                        <br><span class="text-muted" style="font-size:0.65rem;"><i class="bi bi-receipt"></i> NF: R$ <?= number_format($nfTotal, 2, ',', '.') ?></span>
                        <?php endif; ?>
                    </div>
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
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <?php
                    $urgencyLabels = ['low' => ['🟢 Baixa','success'], 'medium' => ['🟡 Média','warning'], 'high' => ['🟠 Alta','orange'], 'critical' => ['🔴 Crítica','danger']];
                    $urg = $urgencyLabels[$order['urgency'] ?? 'medium'] ?? ['🟡 Média','warning'];
                    ?>
                    <span class="badge bg-<?= $urg[1] ?>" style="font-size:0.65rem;"><?= $urg[0] ?></span>
                    <?php if (!empty($order['deadline'])): ?>
                    <?php $daysLeft = (int) ((strtotime($order['deadline']) - strtotime('today')) / 86400); ?>
                    <span style="font-size:0.7rem;" class="<?= $daysLeft < 0 ? 'text-danger fw-bold' : ($daysLeft <= 2 ? 'text-warning fw-bold' : 'text-muted') ?>">
                        <i class="bi bi-calendar-event"></i> <?= date('d/m', strtotime($order['deadline'])) ?>
                        <?= $daysLeft < 0 ? '(atrasado)' : ($daysLeft === 0 ? '(hoje)' : ($daysLeft <= 2 ? '(' . $daysLeft . 'd)' : '')) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<script>
(function() {
    const STORAGE_KEY = 'brooks_orders_filters';
    const rows = document.querySelectorAll('.order-row');
    const statusBtns = document.querySelectorAll('.filter-btn');
    const searchInput = document.getElementById('filterSearch');
    const materialInput = document.getElementById('filterMaterial');
    const supplierSelect = document.getElementById('filterSupplier');
    const siteSelect = document.getElementById('filterSite');
    const typeSelect = document.getElementById('filterType');
    const financialSelect = document.getElementById('filterFinancial');
    const purchasedSelect = document.getElementById('filterPurchased');
    const stockDispatchedSelect = document.getElementById('filterStockDispatched');
    const dateFrom = document.getElementById('filterDateFrom');
    const dateTo = document.getElementById('filterDateTo');
    const requesterSelect = document.getElementById('filterRequester');
    const urgencySelect = document.getElementById('filterUrgency');
    const clearBtn = document.getElementById('clearFilters');
    const countEl = document.getElementById('filteredCount');

    let activeStatus = 'all';
    let activeFlag = ''; // filtro rápido por flag: '', 'in_transport' ou 'arrived'

    // Restaurar filtros: prioridade 1 = URL, prioridade 2 = sessionStorage (fallback)
    function loadFilters() {
        let data = null;

        // Prioridade 1: URL params (funciona com history.back e links diretos)
        const params = new URLSearchParams(window.location.search);
        if (params.toString()) {
            data = {
                status: params.get('status') || 'all',
                flag: params.get('flag') || '',
                q: params.get('q') || '',
                material: params.get('material') || '',
                supplier: params.get('supplier') || '',
                site: params.get('site') || '',
                type: params.get('type') || '',
                financial: params.get('financial') || '',
                purchased: params.get('purchased') || '',
                from: params.get('from') || '',
                to: params.get('to') || '',
                requester: params.get('requester') || '',
                urgency: params.get('urgency') || ''
            };
        }

        // Prioridade 2: sessionStorage (fallback para quando URL não preservou)
        if (!data) {
            try {
                const stored = sessionStorage.getItem(STORAGE_KEY);
                if (stored) data = JSON.parse(stored);
            } catch(e) {}
        }

        if (!data) return;

        activeFlag = data.flag || '';
        if (activeFlag) {
            // Filtro por flag (Em Transporte / Chegou) tem prioridade visual sobre o status
            activeStatus = 'all';
            statusBtns.forEach(b => b.classList.toggle('active', b.dataset.flag === activeFlag));
        } else if (data.status) {
            activeStatus = data.status;
            statusBtns.forEach(b => b.classList.toggle('active', b.dataset.status === activeStatus));
        }
        if (data.q && searchInput) searchInput.value = data.q;
        if (data.material && materialInput) materialInput.value = data.material;
        if (data.supplier && supplierSelect) supplierSelect.value = data.supplier;
        if (data.site && siteSelect) siteSelect.value = data.site;
        if (data.type && typeSelect) typeSelect.value = data.type;
        if (data.financial && financialSelect) financialSelect.value = data.financial;
        if (data.purchased && purchasedSelect) purchasedSelect.value = data.purchased;
        if (data.stockDispatched && stockDispatchedSelect) stockDispatchedSelect.value = data.stockDispatched;
        if (data.from && dateFrom) dateFrom.value = data.from;
        if (data.to && dateTo) dateTo.value = data.to;
        if (data.requester && requesterSelect) requesterSelect.value = data.requester;
        if (data.urgency && urgencySelect) urgencySelect.value = data.urgency;

        // Abrir painel se algum filtro avançado estiver ativo
        if (data.q || data.material || data.supplier || data.site || data.type || data.financial || data.purchased || data.stockDispatched || data.from || data.to || data.requester || data.urgency) {
            const panel = document.getElementById('advancedFilters');
            if (panel && typeof bootstrap !== 'undefined') {
                new bootstrap.Collapse(panel, { show: true });
            } else if (panel) {
                panel.classList.add('show');
            }
        }
    }

    // Salvar filtros no sessionStorage e na URL
    function saveFilters() {
        const data = {
            status: activeStatus,
            flag: activeFlag,
            q: searchInput ? searchInput.value.trim() : '',
            material: materialInput ? materialInput.value.trim() : '',
            supplier: supplierSelect ? supplierSelect.value : '',
            site: siteSelect ? siteSelect.value : '',
            type: typeSelect ? typeSelect.value : '',
            financial: financialSelect ? financialSelect.value : '',
            purchased: purchasedSelect ? purchasedSelect.value : '',
            stockDispatched: stockDispatchedSelect ? stockDispatchedSelect.value : '',
            from: dateFrom ? dateFrom.value : '',
            to: dateTo ? dateTo.value : '',
            requester: requesterSelect ? requesterSelect.value : '',
            urgency: urgencySelect ? urgencySelect.value : ''
        };

        // Salvar no sessionStorage
        try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(data)); } catch(e) {}

        // Atualizar URL
        const params = new URLSearchParams();
        if (data.status !== 'all') params.set('status', data.status);
        if (data.flag) params.set('flag', data.flag);
        if (data.q) params.set('q', data.q);
        if (data.material) params.set('material', data.material);
        if (data.supplier) params.set('supplier', data.supplier);
        if (data.site) params.set('site', data.site);
        if (data.type) params.set('type', data.type);
        if (data.financial) params.set('financial', data.financial);
        if (data.purchased) params.set('purchased', data.purchased);
        if (data.from) params.set('from', data.from);
        if (data.to) params.set('to', data.to);
        if (data.requester) params.set('requester', data.requester);
        if (data.urgency) params.set('urgency', data.urgency);

        const qs = params.toString();
        const newUrl = window.location.pathname + (qs ? '?' + qs : '');
        history.replaceState(null, '', newUrl);
    }

    function applyFilters() {
        const search = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const material = (materialInput ? materialInput.value : '').toLowerCase().trim();
        const supplier = supplierSelect ? supplierSelect.value : '';
        const site = siteSelect ? siteSelect.value : '';
        const type = typeSelect ? typeSelect.value : '';
        const financial = financialSelect ? financialSelect.value : '';
        const purchased = purchasedSelect ? purchasedSelect.value : '';
        const stockDispatched = stockDispatchedSelect ? stockDispatchedSelect.value : '';
        const from = dateFrom ? dateFrom.value : '';
        const to = dateTo ? dateTo.value : '';
        const requester = requesterSelect ? requesterSelect.value : '';

        let visible = 0;

        rows.forEach(row => {
            let show = true;

            if (activeStatus !== 'all' && row.dataset.status !== activeStatus) show = false;
            if (show && activeFlag === 'in_transport' && row.dataset.inTransport !== 'in_transport') show = false;
            if (show && activeFlag === 'arrived' && row.dataset.arrived !== 'arrived') show = false;
            if (show && search && !(row.dataset.search || '').includes(search)) show = false;
            if (show && material && !(row.dataset.items || '').includes(material)) show = false;
            if (show && supplier && row.dataset.supplier !== supplier) show = false;
            if (show && site && row.dataset.site !== site) show = false;
            if (show && type && row.dataset.type !== type) show = false;
            if (show && financial && financial !== 'all' && row.dataset.financial !== financial) show = false;
            if (show && !financial && row.dataset.financial === 'reviewed') show = false;
            if (show && purchased && row.dataset.purchased !== purchased) show = false;
            if (show && stockDispatched && row.dataset.stockDispatched !== stockDispatched) show = false;
            if (show && activeStatus === 'all' && row.dataset.status === 'cancelled') show = false;
            if (show && from && row.dataset.date < from) show = false;
            if (show && to && row.dataset.date > to) show = false;
            if (show && requester && row.dataset.requester !== requester) show = false;
            const urgency = urgencySelect ? urgencySelect.value : '';
            if (show && urgency && row.dataset.urgency !== urgency) show = false;

            const el = row.closest('tr') || row;
            el.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (countEl) countEl.textContent = visible;
        saveFilters();
    }

    // Status buttons (inclui botões por flag: Em Transporte / Chegou)
    statusBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            statusBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            if (this.dataset.flag) {
                // Botão por flag: filtra por in_transport/arrived, sem restringir status
                activeFlag = this.dataset.flag;
                activeStatus = 'all';
            } else {
                activeStatus = this.dataset.status;
                activeFlag = '';
            }
            applyFilters();
        });
    });

    // Advanced filters
    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (materialInput) materialInput.addEventListener('input', applyFilters);
    if (supplierSelect) supplierSelect.addEventListener('change', applyFilters);
    if (siteSelect) siteSelect.addEventListener('change', applyFilters);
    if (typeSelect) typeSelect.addEventListener('change', applyFilters);
    if (financialSelect) financialSelect.addEventListener('change', applyFilters);
    if (purchasedSelect) purchasedSelect.addEventListener('change', applyFilters);
    if (stockDispatchedSelect) stockDispatchedSelect.addEventListener('change', applyFilters);
    if (dateFrom) dateFrom.addEventListener('change', applyFilters);
    if (dateTo) dateTo.addEventListener('change', applyFilters);
    if (requesterSelect) requesterSelect.addEventListener('change', applyFilters);
    if (urgencySelect) urgencySelect.addEventListener('change', applyFilters);

    // Clear
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            if (materialInput) materialInput.value = '';
            if (supplierSelect) supplierSelect.value = '';
            if (siteSelect) siteSelect.value = '';
            if (typeSelect) typeSelect.value = '';
            if (financialSelect) financialSelect.value = '';
            if (purchasedSelect) purchasedSelect.value = '';
            if (stockDispatchedSelect) stockDispatchedSelect.value = '';
            if (dateFrom) dateFrom.value = '';
            if (dateTo) dateTo.value = '';
            if (requesterSelect) requesterSelect.value = '';
            if (urgencySelect) urgencySelect.value = '';
            statusBtns.forEach(b => b.classList.remove('active'));
            statusBtns[0].classList.add('active');
            activeStatus = 'all';
            activeFlag = '';
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

    // Ordenação
    const urgencyOrder = { critical: 0, high: 1, medium: 2, low: 3 };
    const sortBtns = document.querySelectorAll('.sort-btn');
    let currentSort = '';

    sortBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const sort = this.dataset.sort;
            sortBtns.forEach(b => b.classList.remove('active'));
            if (currentSort === sort) {
                currentSort = '';
            } else {
                currentSort = sort;
                this.classList.add('active');
            }
            sortRows();
        });
    });

    function sortRows() {
        const tbody = document.querySelector('.table tbody');
        if (!tbody) return;
        const trs = Array.from(tbody.querySelectorAll('tr.order-row'));

        if (!currentSort) {
            // Reset: ordem original (por data desc)
            trs.sort((a, b) => (b.dataset.date || '').localeCompare(a.dataset.date || ''));
        } else if (currentSort === 'urgency') {
            trs.sort((a, b) => (urgencyOrder[a.dataset.urgency] ?? 2) - (urgencyOrder[b.dataset.urgency] ?? 2));
        } else if (currentSort === 'deadline') {
            trs.sort((a, b) => {
                const da = a.dataset.deadline || '9999-99-99';
                const db = b.dataset.deadline || '9999-99-99';
                return da.localeCompare(db);
            });
        } else if (currentSort === 'date') {
            trs.sort((a, b) => (b.dataset.date || '').localeCompare(a.dataset.date || ''));
        }

        trs.forEach(tr => tbody.appendChild(tr));
    }

    // Inicializar
    loadFilters();
    applyFilters();
})();
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
