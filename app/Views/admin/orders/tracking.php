<?php $pageTitle = 'Acompanhamento de Entregas'; $currentPage = 'orders'; ?>
<?php ob_start(); ?>

<?php
$statusLabels = [
    'pending' => ['Pendente', 'secondary', 'bi-clock'],
    'delivered' => ['Entregue', 'primary', 'bi-box-seam'],
    'checked' => ['Conferido', 'success', 'bi-check-circle-fill'],
    'divergence' => ['Divergência', 'danger', 'bi-exclamation-triangle'],
    'replacement_requested' => ['Troca Solic.', 'warning', 'bi-arrow-repeat'],
    'replacement_delivered' => ['Troca OK', 'success', 'bi-check-all'],
];
$paymentLabels = ['pix'=>'PIX','boleto'=>'Boleto','cartao'=>'Cartão','transferencia'=>'Transf.','dinheiro'=>'Dinheiro','outro'=>'Outro'];
$baseUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0">Acompanhamento de Entregas</h5>
        <small class="text-muted">Visão consolidada de todos os pedidos aprovados com checklist ativo</small>
    </div>
    <a href="/admin/orders" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar</a>
</div>

<?php if (empty($trackingData)): ?>
<div class="card">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-clipboard-check" style="font-size:3rem;"></i>
        <p class="mt-2 mb-0">Nenhum pedido com checklist de entrega ativo.</p>
        <p class="small">Crie checklists a partir dos pedidos aprovados.</p>
    </div>
</div>
<?php else: ?>

<!-- Resumo geral -->
<?php
$totalLate = array_sum(array_column($trackingData, 'late_count'));
$totalPending = array_sum(array_column($trackingData, 'pending_count'));
$totalDone = array_sum(array_column($trackingData, 'done_count'));
$totalItems = array_sum(array_column($trackingData, 'total_count'));
?>
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="card text-center p-2 border-danger <?= $totalLate > 0 ? 'bg-danger bg-opacity-10' : '' ?>">
            <div class="fw-bold fs-4 text-danger"><?= $totalLate ?></div>
            <small class="text-muted">Atrasados</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-2">
            <div class="fw-bold fs-4 text-secondary"><?= $totalPending ?></div>
            <small class="text-muted">Pendentes</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-2">
            <div class="fw-bold fs-4 text-success"><?= $totalDone ?></div>
            <small class="text-muted">Concluídos</small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-2">
            <div class="fw-bold fs-4"><?= $totalItems ?></div>
            <small class="text-muted">Total itens</small>
        </div>
    </div>
</div>
<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="small fw-bold">Filtrar:</span>
            <button class="btn btn-sm btn-outline-secondary active filter-btn" data-filter="all">Todos</button>
            <button class="btn btn-sm btn-outline-danger filter-btn" data-filter="late">Atrasados</button>
            <button class="btn btn-sm btn-outline-secondary filter-btn" data-filter="pending">Pendentes</button>
            <button class="btn btn-sm btn-outline-primary filter-btn" data-filter="delivered">Entregues</button>
            <button class="btn btn-sm btn-outline-warning filter-btn" data-filter="divergence">Com Problema</button>
            <button class="btn btn-sm btn-outline-success filter-btn" data-filter="done">Concluídos</button>
        </div>
    </div>
</div>

<!-- Tabela de acompanhamento -->
<?php foreach ($trackingData as $td): ?>
<?php $order = $td['order']; ?>
<div class="card mb-3 order-tracking-card" data-late="<?= $td['late_count'] ?>">
    <div class="card-header py-2 d-flex justify-content-between align-items-center flex-wrap gap-1 <?= $td['late_count'] > 0 ? 'bg-danger bg-opacity-10' : '' ?>">
        <div>
            <a href="/admin/orders/show/<?= $order['id'] ?>" class="fw-bold text-decoration-none"><?= $order['code'] ?></a>
            <span class="text-muted small ms-2">R$ <?= number_format($order['total_estimated'], 2, ',', '.') ?></span>
        </div>
        <div class="d-flex gap-1 align-items-center">
            <?php if ($td['late_count'] > 0): ?>
            <span class="badge bg-danger"><?= $td['late_count'] ?> atrasado(s)</span>
            <?php endif; ?>
            <span class="badge bg-secondary"><?= $td['done_count'] ?>/<?= $td['total_count'] ?> OK</span>
            <?php if ($order['delivery_token']): ?>
            <a href="<?= $baseUrl ?>/pedido/entrega/<?= $order['delivery_token'] ?>" target="_blank" class="btn btn-sm btn-outline-dark" title="Link público"><i class="bi bi-box-arrow-up-right"></i></a>
            <?php endif; ?>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size:0.78rem;">
            <thead class="table-light">
                <tr>
                    <th>Material</th>
                    <th>Fornecedor</th>
                    <th class="text-center">Qtd</th>
                    <th>Entrega Prevista</th>
                    <th>Pagamento</th>
                    <th>Status</th>
                    <th>Obs</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($td['deliveries'] as $d): ?>
                <?php $si = $statusLabels[$d['status']] ?? ['?','secondary','bi-question']; ?>
                <tr class="tracking-row" data-status="<?= $d['status'] ?>" data-late="<?= $d['is_late'] ? '1' : '0' ?>">
                    <td><strong><?= htmlspecialchars($d['material_name']) ?></strong></td>
                    <td><?= htmlspecialchars($d['supplier_name'] ?? '-') ?></td>
                    <td class="text-center"><?= number_format($d['quantity'], $d['quantity'] == (int)$d['quantity'] ? 0 : 2) ?> <?= htmlspecialchars($d['unit'] ?? '') ?></td>
                    <td>
                        <?php if ($d['expected_date']): ?>
                        <span class="<?= $d['is_late'] ? 'text-danger fw-bold' : '' ?>">
                            <?= date('d/m/Y', strtotime($d['expected_date'])) ?>
                            <?= $d['is_late'] ? ' ⚠️' : '' ?>
                        </span>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($d['payment_method'])): ?>
                        <span class="badge bg-light text-dark" style="font-size:0.65rem;"><?= $paymentLabels[$d['payment_method']] ?? $d['payment_method'] ?></span>
                        <?php if (!empty($d['payment_condition'])): ?><br><small class="text-muted"><?= htmlspecialchars($d['payment_condition']) ?></small><?php endif; ?>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge bg-<?= $si[1] ?>" style="font-size:0.65rem;"><i class="bi <?= $si[2] ?>"></i> <?= $si[0] ?></span></td>
                    <td style="max-width:150px;">
                        <?php if ($d['divergence_notes']): ?><small class="text-danger"><?= htmlspecialchars(mb_substr($d['divergence_notes'], 0, 40)) ?></small><?php endif; ?>
                        <?php if ($d['replacement_notes']): ?><small class="text-warning"><?= htmlspecialchars(mb_substr($d['replacement_notes'], 0, 40)) ?></small><?php endif; ?>
                        <?php if ($d['checked_by']): ?><small class="text-success"><i class="bi bi-person-check"></i> <?= htmlspecialchars($d['checked_by']) ?></small><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<script>
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        const filter = this.dataset.filter;
        
        document.querySelectorAll('.tracking-row').forEach(row => {
            const status = row.dataset.status;
            const isLate = row.dataset.late === '1';
            let show = true;

            if (filter === 'late') show = isLate;
            else if (filter === 'pending') show = status === 'pending';
            else if (filter === 'delivered') show = status === 'delivered';
            else if (filter === 'divergence') show = (status === 'divergence' || status === 'replacement_requested');
            else if (filter === 'done') show = (status === 'checked' || status === 'replacement_delivered');

            row.style.display = show ? '' : 'none';
        });

        // Esconder cards que ficaram sem linhas visíveis
        document.querySelectorAll('.order-tracking-card').forEach(card => {
            const visibleRows = card.querySelectorAll('.tracking-row[style=""], .tracking-row:not([style])');
            const hiddenRows = card.querySelectorAll('.tracking-row[style*="none"]');
            card.style.display = (visibleRows.length === 0 && hiddenRows.length > 0) ? 'none' : '';
        });
    });
});
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
