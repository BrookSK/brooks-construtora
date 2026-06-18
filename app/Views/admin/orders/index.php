<?php $pageTitle = 'Pedidos de Materiais'; $currentPage = 'orders'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="badge bg-secondary"><?= count($orders) ?> pedidos</span>
    </div>
    <a href="/admin/orders/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Novo Pedido
    </a>
</div>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="small text-muted">Filtrar:</span>
            <button class="btn btn-sm btn-outline-secondary filter-btn active" data-status="all">Todos</button>
            <button class="btn btn-sm btn-outline-warning filter-btn" data-status="pending_quote">Aguard. Cotação</button>
            <button class="btn btn-sm btn-outline-info filter-btn" data-status="pending_approval">Aguard. Aprovação</button>
            <button class="btn btn-sm btn-outline-success filter-btn" data-status="approved">Aprovados</button>
            <button class="btn btn-sm btn-outline-danger filter-btn" data-status="rejected">Rejeitados</button>
        </div>
    </div>
</div>

<!-- Lista de pedidos -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Fornecedor</th>
                    <th>Status</th>
                    <th>Valor</th>
                    <th>Solicitante</th>
                    <th>Data</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-inbox" style="font-size:2rem;"></i>
                        <p class="mt-2 mb-0">Nenhum pedido registrado.</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($orders as $order): ?>
                <tr class="order-row" data-status="<?= $order['status'] ?>">
                    <td>
                        <a href="/admin/orders/show/<?= $order['id'] ?>" class="fw-bold text-decoration-none">
                            <?= htmlspecialchars($order['code']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($order['supplier_name'] ?? 'N/A') ?></td>
                    <td>
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
                        <span class="badge bg-<?= $label[1] ?>"><?= $label[0] ?></span>
                    </td>
                    <td>
                        <?php if ($order['total_estimated'] > 0): ?>
                            <strong>R$ <?= number_format($order['total_estimated'], 2, ',', '.') ?></strong>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($order['created_by_name'] ?? '-') ?></td>
                    <td>
                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></small>
                    </td>
                    <td class="text-end">
                        <a href="/admin/orders/show/<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver detalhes">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const status = this.dataset.status;
        document.querySelectorAll('.order-row').forEach(row => {
            row.style.display = (status === 'all' || row.dataset.status === status) ? '' : 'none';
        });
    });
});
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
