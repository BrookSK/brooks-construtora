<?php
$pageTitle = $pageTitle ?? 'Pedidos (Visualização)';
$currentPage = 'transport_orders';
ob_start();
?>

<div class="alert alert-info small mb-3">
    <i class="bi bi-info-circle"></i> Visualização somente leitura. Para ações nos pedidos, acesse o painel de transporte.
</div>

<?php if (empty($orders)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-cart3 text-muted" style="font-size:3rem;"></i>
            <p class="text-muted mt-3 mb-0">Nenhum pedido encontrado.</p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Obra</th>
                            <th>Status</th>
                            <th class="d-none d-md-table-cell">Criado por</th>
                            <th class="d-none d-md-table-cell">Data</th>
                            <th class="text-end d-none d-md-table-cell">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($order['code']) ?></strong></td>
                                <td>
                                    <?php if (!empty($order['construction_site_name'])): ?>
                                        <small><?= htmlspecialchars($order['construction_site_name']) ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusLabels = [
                                        'pending_quote' => '<span class="badge bg-warning text-dark">Cotação</span>',
                                        'pending_approval' => '<span class="badge bg-info">Aprovação</span>',
                                        'approved' => '<span class="badge bg-success">Aprovado</span>',
                                        'rejected' => '<span class="badge bg-danger">Rejeitado</span>',
                                        'cancelled' => '<span class="badge bg-secondary">Cancelado</span>',
                                    ];
                                    echo $statusLabels[$order['status']] ?? '<span class="badge bg-secondary">' . $order['status'] . '</span>';
                                    ?>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <small><?= htmlspecialchars($order['created_by_name'] ?? '-') ?></small>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <small><?= date('d/m/Y', strtotime($order['created_at'])) ?></small>
                                </td>
                                <td class="text-end d-none d-md-table-cell">
                                    <?php if ($order['display_total'] > 0): ?>
                                        <small>R$ <?= number_format($order['display_total'], 2, ',', '.') ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/Views/admin/layouts/app.php';
?>
