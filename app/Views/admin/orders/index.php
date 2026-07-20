<?php $pageTitle = 'Pedidos de Materiais'; $currentPage = 'orders'; ?>
<?php ob_start(); ?>

<style>
.financial-reviewed td { background-color: rgba(34, 197, 94, 0.08) !important; }
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <span class="badge bg-secondary"><?= count($orders) ?> pedidos</span>
    <div class="d-flex flex-wrap gap-1 justify-content-end">
        <a href="/admin/orders/tracking" class="btn btn-outline-dark btn-sm">
            <i class="bi bi-clipboard-check"></i> <span class="d-none d-sm-inline">Acompanhamento</span><span class="d-sm-none">Acomp.</span>
        </a>
        <a href="/admin/orders/spare-items" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-bag-plus"></i> <span class="d-none d-sm-inline">Sobressalentes</span><span class="d-sm-none">Sobress.</span>
        </a>
        <a href="/admin/orders/create" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> <span class="d-none d-sm-inline">Novo Pedido</span><span class="d-sm-none">Novo</span>
        </a>
    </div>
</div>

<!-- Filtros -->
<div class="d-flex flex-wrap gap-1 mb-3">
    <button class="btn btn-sm btn-outline-secondary filter-btn active" data-status="all">Todos</button>
    <button class="btn btn-sm btn-outline-warning filter-btn" data-status="pending_quote">Cotação</button>
    <button class="btn btn-sm btn-outline-info filter-btn" data-status="pending_approval">Aprovação</button>
    <button class="btn btn-sm btn-outline-success filter-btn" data-status="approved">Aprovados</button>
    <button class="btn btn-sm btn-outline-danger filter-btn" data-status="rejected">Rejeitados</button>
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
                    ?>
                    <?php $showFinancialReview = \App\Core\Auth::isSuperAdmin() || \App\Core\Auth::hasPermission('orders.payment'); ?>
                    <?php $isReviewed = $showFinancialReview && !empty($order['financial_reviewed_at']); ?>
                    <tr class="order-row <?= $isReviewed ? 'financial-reviewed' : '' ?>" data-status="<?= $order['status'] ?>">
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
                            <small><i class="bi bi-buildings"></i> <?= htmlspecialchars($order['construction_site_code'] . ' - ' . $order['construction_site_name']) ?></small>
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
    ?>
    <a href="/admin/orders/show/<?= $order['id'] ?>" class="text-decoration-none order-row" data-status="<?= $order['status'] ?>">
        <div class="card mb-2">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <strong class="text-dark"><?= htmlspecialchars($order['code']) ?></strong>
                    <div>
                        <span class="badge bg-<?= $label[1] ?>" style="font-size:0.7rem;"><?= $label[0] ?></span>
                        <?php if ((\App\Core\Auth::isSuperAdmin() || \App\Core\Auth::hasPermission('orders.payment')) && !empty($order['financial_reviewed_at'])): ?>
                        <span class="badge bg-success" style="font-size:0.6rem;"><i class="bi bi-check2"></i></span>
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
                    <span class="text-muted" style="font-size:0.7rem;"><i class="bi bi-buildings"></i> <?= htmlspecialchars($order['construction_site_code'] . ' - ' . $order['construction_site_name']) ?></span>
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
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const status = this.dataset.status;
        document.querySelectorAll('.order-row').forEach(row => {
            const el = row.closest('tr') || row;
            el.style.display = (status === 'all' || row.dataset.status === status) ? '' : 'none';
        });
    });
});
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
