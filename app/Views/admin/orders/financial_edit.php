<?php $pageTitle = 'Edição Financeira - ' . $order['code']; $currentPage = 'orders'; ?>
<?php ob_start(); ?>

<style>
.financial-edit-table input[type="number"] {
    max-width: 120px;
}
.item-total {
    font-weight: 600;
    color: #198754;
}
.changed {
    background-color: rgba(255, 193, 7, 0.1) !important;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-pencil-square text-purple"></i> Edição Financeira - <?= htmlspecialchars($order['code']) ?></h5>
    <a href="/admin/orders/show/<?= $order['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
</div>

<?php if (!empty($flash['error'])): ?>
<div class="alert alert-danger py-2"><?= $flash['error'] ?></div>
<?php endif; ?>
<?php if (!empty($flash['info'])): ?>
<div class="alert alert-info py-2"><?= $flash['info'] ?></div>
<?php endif; ?>

<?php if (!empty($order['construction_site_name'])): ?>
<div class="alert alert-light py-2 mb-3">
    <i class="bi bi-buildings"></i> <strong>Obra:</strong> <?= htmlspecialchars(($order['construction_site_code'] ?? '') . ' - ' . $order['construction_site_name']) ?>
</div>
<?php endif; ?>

<div class="alert alert-warning py-2 small mb-3">
    <i class="bi bi-exclamation-triangle"></i> <strong>Atenção:</strong> Esta edição é restrita ao financeiro. Você pode alterar a <strong>quantidade</strong> e o <strong>preço unitário</strong> dos itens.
    Todas as alterações serão registradas no histórico e notificadas para o cotador e aprovador.
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-cash-stack"></i> Informações do Pedido</span>
        <span class="badge bg-success">Aprovado</span>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <small class="text-muted d-block">Código</small>
                <strong><?= htmlspecialchars($order['code']) ?></strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Aprovado por</small>
                <strong><?= htmlspecialchars($order['approved_by_name'] ?? '-') ?></strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Data Aprovação</small>
                <strong><?= !empty($order['approved_at']) ? date('d/m/Y H:i', strtotime($order['approved_at'])) : '-' ?></strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Total Atual</small>
                <strong class="text-success">R$ <?= number_format($order['total_estimated'] ?? 0, 2, ',', '.') ?></strong>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="/admin/orders/financial-update" id="financialEditForm">
    <input type="hidden" name="id" value="<?= $order['id'] ?>">

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list-check"></i> Itens do Pedido</span>
            <span class="badge bg-primary"><?= count($items) ?> itens</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 financial-edit-table">
                    <thead>
                        <tr class="bg-light">
                            <th>#</th>
                            <th>Material</th>
                            <th>Espec.</th>
                            <th>Origem</th>
                            <th class="text-center" style="width:130px;">Quantidade</th>
                            <th class="text-end" style="width:150px;">Preço Unitário</th>
                            <th class="text-end" style="width:130px;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $supplierNamesMap = [];
                        if (!empty($orderSuppliers)) {
                            foreach ($orderSuppliers as $os) {
                                $supplierNamesMap[$os['supplier_id']] = $os['supplier_name'];
                            }
                        }
                        $grandTotal = 0;
                        ?>
                        <?php foreach ($items as $i => $item): ?>
                        <?php
                        $itemTotal = (float)($item['unit_price'] ?? 0) * (float)$item['quantity'];
                        $grandTotal += $itemTotal;
                        $isStockItem = !empty($item['source_type']) && $item['source_type'] !== 'purchase';
                        ?>
                        <tr class="<?= $isStockItem ? 'table-success' : '' ?>" data-item-id="<?= $item['id'] ?>">
                            <td><?= $i + 1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($item['material_name']) ?></strong>
                                <?php if (!empty($supplierNamesMap[$item['approved_supplier_id'] ?? 0])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($supplierNamesMap[$item['approved_supplier_id']]) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><small><?= htmlspecialchars($item['specification'] ?? '-') ?></small></td>
                            <td>
                                <?php if ($isStockItem): ?>
                                    <?php if ($item['source_type'] === 'stock_transfer'): ?>
                                        <span class="badge bg-primary" style="font-size:0.6rem;">Transferência</span>
                                    <?php else: ?>
                                        <span class="badge bg-success" style="font-size:0.6rem;">Estoque</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark" style="font-size:0.6rem;">Compra</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <input type="number"
                                       name="items[<?= $item['id'] ?>][quantity]"
                                       class="form-control form-control-sm text-center item-qty"
                                       value="<?= number_format((float)$item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2, '.', '') ?>"
                                       step="0.01"
                                       min="0.01"
                                       data-original="<?= (float)$item['quantity'] ?>"
                                       required>
                            </td>
                            <td class="text-end">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text" style="font-size:0.75rem;">R$</span>
                                    <input type="number"
                                           name="items[<?= $item['id'] ?>][unit_price]"
                                           class="form-control form-control-sm text-end item-price"
                                           value="<?= number_format((float)($item['unit_price'] ?? 0), 2, '.', '') ?>"
                                           step="0.01"
                                           min="0"
                                           data-original="<?= (float)($item['unit_price'] ?? 0) ?>"
                                           required>
                                </div>
                            </td>
                            <td class="text-end item-total">
                                R$ <?= number_format($itemTotal, 2, ',', '.') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="6" class="text-end fw-bold">TOTAL:</td>
                            <td class="text-end fw-bold text-success" id="grandTotal">R$ <?= number_format($grandTotal, 2, ',', '.') ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center">
        <a href="/admin/orders/show/<?= $order['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-x-lg"></i> Cancelar
        </a>
        <button type="submit" class="btn btn-primary" onclick="return confirm('Confirma a edição financeira dos itens? As alterações serão registradas no histórico e notificações serão enviadas.')">
            <i class="bi bi-check-lg"></i> Salvar Edição Financeira
        </button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('financialEditForm');
    const grandTotalEl = document.getElementById('grandTotal');

    function formatBRL(value) {
        return 'R$ ' + value.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function recalculate() {
        let grandTotal = 0;
        document.querySelectorAll('#financialEditForm tbody tr[data-item-id]').forEach(function(row) {
            const qtyInput = row.querySelector('.item-qty');
            const priceInput = row.querySelector('.item-price');
            const totalCell = row.querySelector('.item-total');

            const qty = parseFloat(qtyInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            const total = qty * price;

            totalCell.textContent = formatBRL(total);
            grandTotal += total;

            // Highlight changed fields
            const qtyOriginal = parseFloat(qtyInput.dataset.original) || 0;
            const priceOriginal = parseFloat(priceInput.dataset.original) || 0;

            if (Math.abs(qty - qtyOriginal) > 0.001) {
                qtyInput.classList.add('border-warning', 'bg-warning', 'bg-opacity-10');
            } else {
                qtyInput.classList.remove('border-warning', 'bg-warning', 'bg-opacity-10');
            }

            if (Math.abs(price - priceOriginal) > 0.001) {
                priceInput.classList.add('border-warning', 'bg-warning', 'bg-opacity-10');
            } else {
                priceInput.classList.remove('border-warning', 'bg-warning', 'bg-opacity-10');
            }
        });

        grandTotalEl.textContent = formatBRL(grandTotal);
    }

    // Bind events
    document.querySelectorAll('.item-qty, .item-price').forEach(function(input) {
        input.addEventListener('input', recalculate);
        input.addEventListener('change', recalculate);
    });
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/app.php'; ?>
