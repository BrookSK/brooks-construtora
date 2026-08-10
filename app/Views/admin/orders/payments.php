<?php $pageTitle = 'NF e Boletos'; $currentPage = 'orders'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="/admin/orders" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Pedidos</a>
    </div>
    <a href="/admin/orders/export" class="btn btn-success btn-sm">
        <i class="bi bi-file-earmark-spreadsheet"></i> Exportar Planilha
    </a>
</div>

<!-- Pendências -->
<?php if (!empty($pending)): ?>
<div class="card mb-3 border-warning">
    <div class="card-header bg-warning bg-opacity-10">
        <i class="bi bi-exclamation-triangle text-warning"></i> <strong>Pendências (<?= count($pending) ?>)</strong>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Pedido</th><th>Tipo</th><th>Nº</th><th class="text-end">Valor</th><th>Vencimento</th><th class="text-end">Ações</th></tr></thead>
                <tbody>
                    <?php foreach ($pending as $p): ?>
                    <tr class="<?= $p['due_date'] && strtotime($p['due_date']) < time() ? 'table-danger' : '' ?>">
                        <td><a href="/admin/orders/show/<?= $p['order_id'] ?>"><?= htmlspecialchars($p['order_code']) ?></a></td>
                        <td><span class="badge bg-<?= $p['type'] === 'nf' ? 'info' : ($p['type'] === 'boleto' ? 'warning' : 'primary') ?>"><?= strtoupper($p['type']) ?></span></td>
                        <td><?= htmlspecialchars($p['number'] ?? '-') ?></td>
                        <td class="text-end"><?= $p['amount'] ? 'R$ ' . number_format($p['amount'], 2, ',', '.') : '-' ?></td>
                        <td><?= $p['due_date'] ? date('d/m/Y', strtotime($p['due_date'])) : '-' ?></td>
                        <td class="text-end">
                            <?php if ($p['file_path']): ?><a href="<?= $p['file_path'] ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a><?php endif; ?>
                            <form method="POST" action="/admin/orders/mark-paid" class="d-inline"><input type="hidden" name="id" value="<?= $p['id'] ?>"><button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check"></i> Pago</button></form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Todas NFs -->
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-file-text"></i> Notas Fiscais (<?= count($allNf) ?>)</div>
    <div class="card-body p-0">
        <?php if (empty($allNf)): ?>
        <p class="text-muted text-center py-3 mb-0">Nenhuma NF registrada.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Pedido</th><th>Nº</th><th class="text-end">Valor</th><th>Status</th><th>Data</th></tr></thead>
                <tbody>
                    <?php foreach ($allNf as $p): ?>
                    <tr>
                        <td><a href="/admin/orders/show/<?= $p['order_id'] ?>"><?= htmlspecialchars($p['order_code']) ?></a></td>
                        <td><?= htmlspecialchars($p['number'] ?? '-') ?></td>
                        <td class="text-end"><?= $p['amount'] ? 'R$ ' . number_format($p['amount'], 2, ',', '.') : '-' ?></td>
                        <td><?= $p['paid'] ? '<span class="badge bg-success">Pago</span>' : '<span class="badge bg-secondary">Pendente</span>' ?></td>
                        <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Todos Boletos -->
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-credit-card"></i> Boletos (<?= count($allBoleto) ?>)</div>
    <div class="card-body p-0">
        <?php if (empty($allBoleto)): ?>
        <p class="text-muted text-center py-3 mb-0">Nenhum boleto registrado.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Pedido</th><th>Nº</th><th class="text-end">Valor</th><th>Vencimento</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($allBoleto as $p): ?>
                    <tr>
                        <td><a href="/admin/orders/show/<?= $p['order_id'] ?>"><?= htmlspecialchars($p['order_code']) ?></a></td>
                        <td><?= htmlspecialchars($p['number'] ?? '-') ?></td>
                        <td class="text-end"><?= $p['amount'] ? 'R$ ' . number_format($p['amount'], 2, ',', '.') : '-' ?></td>
                        <td><?= $p['due_date'] ? date('d/m/Y', strtotime($p['due_date'])) : '-' ?></td>
                        <td><?= $p['paid'] ? '<span class="badge bg-success">Pago ' . ($p['paid_at'] ? date('d/m', strtotime($p['paid_at'])) : '') . '</span>' : '<span class="badge bg-secondary">Pendente</span>' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
