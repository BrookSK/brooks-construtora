<?php
$currentPage = 'stock';
ob_start();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <form method="GET" action="/admin/stock/movements" class="d-flex align-items-center gap-2">
        <select name="site_id" class="form-select form-select-sm" style="min-width:200px;" onchange="this.form.submit()">
            <option value="">Todas as obras</option>
            <?php foreach ($sites as $site): ?>
                <option value="<?= $site['id'] ?>" <?= ($selectedSite ?? '') == $site['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($site['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    <a href="/admin/stock" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-arrow-left"></i> Voltar ao Estoque
    </a>
</div>

<?php if (empty($movements)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-clock-history text-muted" style="font-size:3rem;"></i>
            <p class="text-muted mt-3 mb-0">Nenhuma movimentação registrada.</p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Material</th>
                            <th>Qtd</th>
                            <th>Origem</th>
                            <th>Destino</th>
                            <th>Status</th>
                            <th class="d-none d-md-table-cell">Solicitante</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movements as $mov): ?>
                            <tr>
                                <td><small><?= date('d/m/Y H:i', strtotime($mov['created_at'])) ?></small></td>
                                <td>
                                    <?php
                                    $typeLabels = [
                                        'entry' => '<span class="badge bg-success">Entrada</span>',
                                        'exit' => '<span class="badge bg-danger">Saída</span>',
                                        'transfer' => '<span class="badge bg-primary">Transferência</span>',
                                        'adjustment' => '<span class="badge bg-warning text-dark">Ajuste</span>',
                                    ];
                                    echo $typeLabels[$mov['type']] ?? $mov['type'];
                                    ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($mov['material_name']) ?></strong>
                                </td>
                                <td>
                                    <strong><?= number_format($mov['quantity'], 2, ',', '.') ?></strong>
                                    <small class="text-muted"><?= $mov['unit_abbr'] ?? '' ?></small>
                                </td>
                                <td><?= htmlspecialchars($mov['from_site_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($mov['to_site_name'] ?? '-') ?></td>
                                <td>
                                    <?php
                                    $statusLabels = [
                                        'pending' => '<span class="badge bg-warning text-dark">Pendente</span>',
                                        'in_transit' => '<span class="badge bg-info">Em Trânsito</span>',
                                        'delivered' => '<span class="badge bg-success">Entregue</span>',
                                        'cancelled' => '<span class="badge bg-secondary">Cancelado</span>',
                                    ];
                                    echo $statusLabels[$mov['status']] ?? $mov['status'];
                                    ?>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <small><?= htmlspecialchars($mov['requested_by'] ?? '-') ?></small>
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
