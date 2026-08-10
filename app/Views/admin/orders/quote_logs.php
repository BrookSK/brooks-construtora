<?php $pageTitle = 'Logs de Cotação (Debug)'; $currentPage = 'orders'; ob_start(); ?>

<div class="top-bar">
    <div>
        <h5 class="mb-0"><i class="bi bi-bug"></i> Logs de Cotação</h5>
        <small class="text-muted">Registro detalhado de todas as cotações para diagnóstico</small>
    </div>
    <a href="/admin/orders" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Voltar para Pedidos
    </a>
</div>

<?php if (!empty($flash)): ?>
<div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show">
    <?= htmlspecialchars($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filtro por pedido -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="/admin/orders/quote-logs" class="d-flex gap-2 align-items-center flex-wrap">
            <label class="form-label mb-0 small fw-bold">Filtrar por pedido:</label>
            <input type="number" name="order_id" class="form-control form-control-sm" style="width:120px;" placeholder="ID do pedido" value="<?= $filterOrderId ?: '' ?>">
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i> Filtrar</button>
            <?php if ($filterOrderId): ?>
            <a href="/admin/orders/quote-logs" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i> Limpar</a>
            <?php endif; ?>
            <span class="ms-auto text-muted small"><?= $total ?> log(s) encontrado(s)</span>
        </form>
    </div>
</div>

<?php if (empty($logs)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> Nenhum log encontrado<?= $filterOrderId ? " para o pedido #{$filterOrderId}" : '' ?>.
    <br><small class="text-muted">Os logs são gerados automaticamente a cada cotação enviada.</small>
</div>
<?php else: ?>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Data/Hora</th>
                    <th>Pedido</th>
                    <th>Cotado por</th>
                    <th>Fornecedores</th>
                    <th>Total Salvo</th>
                    <th>Frontend</th>
                    <th class="text-center">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="small"><?= htmlspecialchars($log['created_at'] ?? '-') ?></td>
                    <td>
                        <strong><?= htmlspecialchars($log['backend']['data']['order_code'] ?? "#{$log['order_id']}") ?></strong>
                        <br><small class="text-muted">ID: <?= $log['order_id'] ?? '-' ?></small>
                    </td>
                    <td class="small"><?= htmlspecialchars($log['backend']['data']['quoted_by'] ?? '-') ?></td>
                    <td>
                        <?php
                        $suppliers = $log['backend']['data']['suppliers_processing'] ?? [];
                        echo count($suppliers) . ' fornecedor(es)';
                        ?>
                    </td>
                    <td>
                        <?php
                        $total_saved = $log['backend']['data']['total_saved_to_order'] ?? 0;
                        echo '<strong>R$ ' . number_format($total_saved, 2, ',', '.') . '</strong>';
                        ?>
                    </td>
                    <td class="text-center">
                        <?php if (!empty($log['frontend'])): ?>
                            <span class="badge bg-success"><i class="bi bi-check"></i> Sim</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark"><i class="bi bi-x"></i> Não</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <a href="/admin/orders/quote-logs/view?file=<?= urlencode($log['_filename'] ?? '') ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> Ver
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
