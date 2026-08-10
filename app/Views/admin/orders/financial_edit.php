<?php $pageTitle = 'Edição Financeira - ' . $order['code']; $currentPage = 'orders'; ?>
<?php ob_start(); ?>

<style>
.financial-edit-table input[type="number"],
.financial-edit-table input[type="text"] {
    max-width: 140px;
}
.item-total {
    font-weight: 600;
    color: #198754;
}
.changed {
    background-color: rgba(255, 193, 7, 0.1) !important;
}
.text-purple { color: #8b5cf6 !important; }
.border-purple { border-color: #8b5cf6 !important; }
.bg-purple-light { background-color: rgba(139, 92, 246, 0.05) !important; }
.price-mode-toggle { cursor: pointer; user-select: none; }
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

<div class="alert alert-warning py-2 small mb-3">
    <i class="bi bi-exclamation-triangle"></i> <strong>Atenção:</strong> Esta edição é restrita ao financeiro. Você pode alterar <strong>quantidade</strong>, <strong>preço unitário ou total</strong> dos itens, <strong>dados financeiros</strong> (frete, IPI, ICMS, desconto, acréscimo) e a <strong>obra</strong>.
    Todas as alterações serão registradas no histórico e notificadas para o cotador e aprovador.
</div>

<form method="POST" action="/admin/orders/financial-update" id="financialEditForm">
    <input type="hidden" name="id" value="<?= $order['id'] ?>">

    <!-- Informações do Pedido + Obra -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-info-circle"></i> Informações do Pedido</span>
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
                    <strong class="text-success fs-5">R$ <?= number_format($order['total_estimated'] ?? 0, 2, ',', '.') ?></strong>
                </div>
            </div>
            <hr class="my-3">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label small fw-bold"><i class="bi bi-buildings"></i> Obra</label>
                    <select name="construction_site_id" class="form-select form-select-sm" id="obraSelect">
                        <option value="">-- Sem obra vinculada --</option>
                        <?php foreach ($constructionSites as $site): ?>
                        <option value="<?= $site['id'] ?>" <?= ((int)($order['construction_site_id'] ?? 0)) === (int)$site['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($site['code'] ?? '') . ' - ' . $site['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Obra atual</label>
                    <div class="form-control-plaintext small">
                        <?php if (!empty($order['construction_site_name'])): ?>
                            <i class="bi bi-buildings"></i> <?= htmlspecialchars(($order['construction_site_code'] ?? '') . ' - ' . $order['construction_site_name']) ?>
                        <?php else: ?>
                            <span class="text-muted">Nenhuma obra vinculada</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Itens do Pedido -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list-check"></i> Itens do Pedido</span>
            <div>
                <span class="badge bg-primary me-2"><?= count($items) ?> itens</span>
                <div class="form-check form-switch d-inline-block ms-2" title="Alternar entre editar preço unitário ou preço total">
                    <input class="form-check-input" type="checkbox" id="priceModeToggle">
                    <label class="form-check-label small price-mode-toggle" for="priceModeToggle">Editar Total</label>
                </div>
            </div>
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
                            <th class="text-center" style="width:120px;">Quantidade</th>
                            <th class="text-end col-unit-price" style="width:150px;">Preço Unitário</th>
                            <th class="text-end col-total-price" style="width:150px; display:none;">Preço Total</th>
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
                        $subtotalItems = 0;
                        ?>
                        <?php foreach ($items as $i => $item): ?>
                        <?php
                        $itemTotal = (float)($item['total_price'] ?? ((float)($item['unit_price'] ?? 0) * (float)$item['quantity']));
                        $subtotalItems += $itemTotal;
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
                            <td class="text-end col-unit-price">
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
                            <td class="text-end col-total-price" style="display:none;">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text" style="font-size:0.75rem;">R$</span>
                                    <input type="number"
                                           name="items[<?= $item['id'] ?>][total_price]"
                                           class="form-control form-control-sm text-end item-total-input"
                                           value="<?= number_format($itemTotal, 2, '.', '') ?>"
                                           step="0.01"
                                           min="0"
                                           data-original="<?= $itemTotal ?>">
                                </div>
                            </td>
                            <td class="text-end item-total-display">
                                R$ <?= number_format($itemTotal, 2, ',', '.') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="5" class="text-end fw-bold">Subtotal Itens:</td>
                            <td colspan="2" class="text-end fw-bold" id="subtotalItems">R$ <?= number_format($subtotalItems, 2, ',', '.') ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Dados Financeiros (por fornecedor aprovado) -->
    <?php
    $approvedSuppliers = array_filter($orderSuppliers, fn($os) => !empty($os['approved']));
    if (empty($approvedSuppliers)) {
        // Se não tem fornecedor aprovado, criar um placeholder para edição livre
        $approvedSuppliers = [['id' => 0, 'supplier_id' => 0, 'supplier_name' => 'Geral', 'discount_value' => 0, 'discount_type' => 'percent', 'surcharge_value' => 0, 'surcharge_type' => 'percent', 'ipi_percent' => 0, 'icms_percent' => 0, 'freight' => 0, 'subtotal_items' => $subtotalItems, 'subtotal_final' => $order['total_estimated'] ?? 0]];
    }
    ?>
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-calculator"></i> Dados Financeiros
        </div>
        <div class="card-body">
            <?php foreach ($approvedSuppliers as $idx => $supplier): ?>
            <?php if (count($approvedSuppliers) > 1): ?>
            <h6 class="fw-bold mb-2 <?= $idx > 0 ? 'mt-4 pt-3 border-top' : '' ?>">
                <i class="bi bi-building"></i> <?= htmlspecialchars($supplier['supplier_name']) ?>
            </h6>
            <?php endif; ?>
            <input type="hidden" name="suppliers[<?= $idx ?>][supplier_order_id]" value="<?= $supplier['id'] ?>">
            <input type="hidden" name="suppliers[<?= $idx ?>][supplier_id]" value="<?= $supplier['supplier_id'] ?>">
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-0">Desconto</label>
                    <div class="input-group input-group-sm">
                        <input type="number" step="0.01" min="0" class="form-control fin-field"
                               name="suppliers[<?= $idx ?>][discount_value]"
                               value="<?= (float)($supplier['discount_value'] ?? 0) ?>"
                               data-original="<?= (float)($supplier['discount_value'] ?? 0) ?>"
                               data-field="discount">
                        <select class="form-select" style="max-width:60px;"
                                name="suppliers[<?= $idx ?>][discount_type]"
                                data-original="<?= $supplier['discount_type'] ?? 'percent' ?>">
                            <option value="percent" <?= ($supplier['discount_type'] ?? 'percent') === 'percent' ? 'selected' : '' ?>>%</option>
                            <option value="fixed" <?= ($supplier['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>R$</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-0">Acréscimo</label>
                    <div class="input-group input-group-sm">
                        <input type="number" step="0.01" min="0" class="form-control fin-field"
                               name="suppliers[<?= $idx ?>][surcharge_value]"
                               value="<?= (float)($supplier['surcharge_value'] ?? 0) ?>"
                               data-original="<?= (float)($supplier['surcharge_value'] ?? 0) ?>"
                               data-field="surcharge">
                        <select class="form-select" style="max-width:60px;"
                                name="suppliers[<?= $idx ?>][surcharge_type]"
                                data-original="<?= $supplier['surcharge_type'] ?? 'percent' ?>">
                            <option value="percent" <?= ($supplier['surcharge_type'] ?? 'percent') === 'percent' ? 'selected' : '' ?>>%</option>
                            <option value="fixed" <?= ($supplier['surcharge_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>R$</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-0">IPI (%)</label>
                    <div class="input-group input-group-sm">
                        <input type="number" step="0.01" min="0" class="form-control fin-field"
                               name="suppliers[<?= $idx ?>][ipi_percent]"
                               value="<?= (float)($supplier['ipi_percent'] ?? 0) ?>"
                               data-original="<?= (float)($supplier['ipi_percent'] ?? 0) ?>"
                               data-field="ipi">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-0">ICMS (%)</label>
                    <div class="input-group input-group-sm">
                        <input type="number" step="0.01" min="0" class="form-control fin-field"
                               name="suppliers[<?= $idx ?>][icms_percent]"
                               value="<?= (float)($supplier['icms_percent'] ?? 0) ?>"
                               data-original="<?= (float)($supplier['icms_percent'] ?? 0) ?>"
                               data-field="icms">
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-0">Frete (R$)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">R$</span>
                        <input type="number" step="0.01" min="0" class="form-control fin-field"
                               name="suppliers[<?= $idx ?>][freight]"
                               value="<?= number_format((float)($supplier['freight'] ?? 0), 2, '.', '') ?>"
                               data-original="<?= (float)($supplier['freight'] ?? 0) ?>"
                               data-field="freight">
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <hr class="my-3">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <small class="text-muted">Fórmula: Subtotal Itens - Desconto + Acréscimo + IPI + ICMS + Frete = <strong>Total Final</strong></small>
                </div>
                <div class="col-md-4 text-end">
                    <span class="text-muted">Novo Total:</span>
                    <strong class="fs-5 text-success" id="grandTotal">R$ <?= number_format($order['total_estimated'] ?? 0, 2, ',', '.') ?></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden field para modo de preço -->
    <input type="hidden" name="price_mode" id="priceModeField" value="unit">

    <div class="d-flex justify-content-between align-items-center">
        <a href="/admin/orders/show/<?= $order['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-x-lg"></i> Cancelar
        </a>
        <button type="submit" class="btn btn-primary" onclick="return confirm('Confirma a edição financeira? As alterações serão registradas no histórico e notificações serão enviadas ao cotador e aprovador.')">
            <i class="bi bi-check-lg"></i> Salvar Edição Financeira
        </button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const grandTotalEl = document.getElementById('grandTotal');
    const subtotalItemsEl = document.getElementById('subtotalItems');
    const priceModeToggle = document.getElementById('priceModeToggle');
    const priceModeField = document.getElementById('priceModeField');
    let priceMode = 'unit'; // 'unit' or 'total'

    function formatBRL(value) {
        return 'R\$ ' + value.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    // Toggle entre preço unitário e preço total
    priceModeToggle.addEventListener('change', function() {
        priceMode = this.checked ? 'total' : 'unit';
        priceModeField.value = priceMode;

        document.querySelectorAll('.col-unit-price').forEach(el => {
            el.style.display = priceMode === 'unit' ? '' : 'none';
        });
        document.querySelectorAll('.col-total-price').forEach(el => {
            el.style.display = priceMode === 'total' ? '' : 'none';
        });

        // Sincronizar valores ao trocar modo
        if (priceMode === 'total') {
            // Copiar valor calculado para o campo total
            document.querySelectorAll('tr[data-item-id]').forEach(function(row) {
                const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
                const unitPrice = parseFloat(row.querySelector('.item-price').value) || 0;
                const totalInput = row.querySelector('.item-total-input');
                totalInput.value = (qty * unitPrice).toFixed(2);
            });
        } else {
            // Recalcular unitário a partir do total
            document.querySelectorAll('tr[data-item-id]').forEach(function(row) {
                const qty = parseFloat(row.querySelector('.item-qty').value) || 1;
                const totalPrice = parseFloat(row.querySelector('.item-total-input').value) || 0;
                const unitInput = row.querySelector('.item-price');
                unitInput.value = (totalPrice / qty).toFixed(2);
            });
        }
        recalculate();
    });

    function recalculate() {
        let subtotalItems = 0;

        document.querySelectorAll('#financialEditForm tbody tr[data-item-id]').forEach(function(row) {
            const qtyInput = row.querySelector('.item-qty');
            const priceInput = row.querySelector('.item-price');
            const totalInput = row.querySelector('.item-total-input');
            const displayCell = row.querySelector('.item-total-display');

            const qty = parseFloat(qtyInput.value) || 0;
            let itemTotal = 0;

            if (priceMode === 'total') {
                itemTotal = parseFloat(totalInput.value) || 0;
                // Atualizar unitário por referência
                if (qty > 0) {
                    priceInput.value = (itemTotal / qty).toFixed(2);
                }
            } else {
                const unitPrice = parseFloat(priceInput.value) || 0;
                itemTotal = qty * unitPrice;
                totalInput.value = itemTotal.toFixed(2);
            }

            displayCell.textContent = formatBRL(itemTotal);
            subtotalItems += itemTotal;

            // Highlight changed fields
            highlightField(qtyInput);
            highlightField(priceInput);
            if (priceMode === 'total') highlightField(totalInput);
        });

        subtotalItemsEl.textContent = formatBRL(subtotalItems);

        // Calcular financeiros
        let totalFinal = subtotalItems;
        document.querySelectorAll('.fin-field').forEach(function(input) {
            highlightField(input);
        });

        // Para cada grupo de fornecedor, calcular financeiros
        // Pegar todos os campos financeiros
        const supplierGroups = {};
        document.querySelectorAll('input[name^="suppliers["]').forEach(function(input) {
            const match = input.name.match(/suppliers\[(\d+)\]\[(\w+)\]/);
            if (match) {
                const idx = match[1];
                const field = match[2];
                if (!supplierGroups[idx]) supplierGroups[idx] = {};
                if (input.tagName === 'SELECT' || input.type === 'hidden') {
                    supplierGroups[idx][field] = input.value;
                } else {
                    supplierGroups[idx][field] = parseFloat(input.value) || 0;
                }
            }
        });
        document.querySelectorAll('select[name^="suppliers["]').forEach(function(select) {
            const match = select.name.match(/suppliers\[(\d+)\]\[(\w+)\]/);
            if (match) {
                const idx = match[1];
                const field = match[2];
                if (!supplierGroups[idx]) supplierGroups[idx] = {};
                supplierGroups[idx][field] = select.value;
            }
        });

        // Se temos apenas um fornecedor, aplicar financeiros ao subtotal total
        // Se múltiplos, dividir proporcionalmente (simplificação: aplicar ao total)
        totalFinal = subtotalItems;
        Object.values(supplierGroups).forEach(function(sup) {
            const discVal = sup.discount_value || 0;
            const discType = sup.discount_type || 'percent';
            const surVal = sup.surcharge_value || 0;
            const surType = sup.surcharge_type || 'percent';
            const ipi = sup.ipi_percent || 0;
            const icms = sup.icms_percent || 0;
            const freight = sup.freight || 0;

            if (discVal > 0) {
                totalFinal -= (discType === 'percent') ? subtotalItems * (discVal / 100) : discVal;
            }
            if (surVal > 0) {
                totalFinal += (surType === 'percent') ? subtotalItems * (surVal / 100) : surVal;
            }
            if (ipi > 0) totalFinal += subtotalItems * (ipi / 100);
            if (icms > 0) totalFinal += subtotalItems * (icms / 100);
            if (freight > 0) totalFinal += freight;
        });

        grandTotalEl.textContent = formatBRL(totalFinal);
    }

    function highlightField(input) {
        if (!input || input.dataset.original === undefined) return;
        const current = parseFloat(input.value) || 0;
        const original = parseFloat(input.dataset.original) || 0;
        if (Math.abs(current - original) > 0.001) {
            input.classList.add('border-warning', 'bg-warning', 'bg-opacity-10');
        } else {
            input.classList.remove('border-warning', 'bg-warning', 'bg-opacity-10');
        }
    }

    // Bind events para recalcular
    document.querySelectorAll('.item-qty, .item-price, .item-total-input, .fin-field').forEach(function(input) {
        input.addEventListener('input', recalculate);
        input.addEventListener('change', recalculate);
    });
    document.querySelectorAll('select[name^="suppliers["]').forEach(function(select) {
        select.addEventListener('change', recalculate);
    });

    // Calcular ao carregar
    recalculate();
});
</script>

<?php $content = ob_get_clean(); ?>
<?php include __DIR__ . '/../layouts/app.php'; ?>
