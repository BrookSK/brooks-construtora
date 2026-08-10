<?php $pageTitle = 'Editar Itens - ' . $order['code']; $currentPage = 'orders'; ?>
<?php ob_start(); ?>

<link rel="stylesheet" href="/assets/css/searchable-select.css">

<style>
/* Garantir que o dropdown do SearchableSelect não fique cortado pela tabela */
.table-responsive { overflow: visible !important; }
.searchable-select-dropdown { z-index: 1050; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Editar Itens do Pedido <?= htmlspecialchars($order['code']) ?></h5>
    <a href="/admin/orders/show/<?= $order['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
</div>

<?php if (!empty($constructionSites) && !empty($order['construction_site_name'])): ?>
<div class="alert alert-light py-2 mb-3">
    <i class="bi bi-buildings"></i> <strong>Obra atual:</strong> <?= htmlspecialchars($order['construction_site_code'] . ' - ' . $order['construction_site_name']) ?>
</div>
<?php endif; ?>

<div class="alert alert-warning py-2 small">
    <i class="bi bi-exclamation-triangle"></i> <strong>Atenção:</strong> Ao salvar, os itens anteriores serão substituídos e movimentações de estoque vinculadas serão canceladas. Notificações serão reenviadas para cotação.
</div>

<form method="POST" action="/admin/orders/update-items" id="editItemsForm">
    <input type="hidden" name="id" value="<?= $order['id'] ?>">

    <?php if (!empty($constructionSites)): ?>
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <label class="form-label small fw-bold mb-1"><i class="bi bi-buildings"></i> Obra</label>
                    <select name="construction_site_id" class="form-select form-select-sm">
                        <option value="">-- Sem obra vinculada --</option>
                        <?php foreach ($constructionSites as $site): ?>
                        <option value="<?= $site['id'] ?>" <?= ((int)($order['construction_site_id'] ?? 0)) === (int)$site['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($site['code'] ?? '') . ' - ' . $site['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <?php if (!empty($order['construction_site_name'])): ?>
                    <small class="text-muted"><i class="bi bi-buildings"></i> Atual: <?= htmlspecialchars($order['construction_site_code'] . ' - ' . $order['construction_site_name']) ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list-check"></i> Itens do Pedido <span class="badge bg-primary ms-1" id="itemCountBadge">0</span></span>
            <button type="button" class="btn btn-sm btn-primary" id="addItemBtn">
                <i class="bi bi-plus"></i> Adicionar Item
            </button>
        </div>
        <div class="card-body p-0" style="overflow:visible;">
            <div class="table-responsive">
                <table class="table table-sm mb-0" id="itemsTable">
                    <thead>
                        <tr class="bg-light">
                            <th style="min-width:250px;">Material</th>
                            <th style="min-width:120px;">Especificação</th>
                            <th style="min-width:100px;">Classificação</th>
                            <th style="width:90px;">Qtd</th>
                            <th style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody"></tbody>
                </table>
                <div class="p-3 text-center text-muted" id="emptyState">
                    <i class="bi bi-inbox"></i> Clique em "Adicionar Item" para começar
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <a href="/admin/orders/show/<?= $order['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-x"></i> Cancelar
        </a>
        <button type="button" class="btn btn-primary" onclick="checkStockBeforeSubmit()">
            <i class="bi bi-check-lg"></i> Salvar Alterações e Reenviar
        </button>
    </div>
</form>

<!-- Modal de Verificação de Estoque -->
<div class="modal fade" id="stockCheckModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info bg-opacity-10">
                <h5 class="modal-title"><i class="bi bi-box-seam"></i> Verificação de Estoque</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="stockCheckBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Verificando disponibilidade no estoque...</p>
                </div>
            </div>
            <div class="modal-footer flex-column flex-sm-row gap-2">
                <button type="button" class="btn btn-outline-secondary w-100 order-2 order-sm-1" data-bs-dismiss="modal" style="flex:1;">
                    <i class="bi bi-pencil"></i> Voltar e Editar
                </button>
                <button type="button" class="btn btn-primary w-100 order-1 order-sm-2" id="stockConfirmBtn" onclick="confirmStockDecisions()" style="flex:1;" disabled>
                    <i class="bi bi-check-lg"></i> Confirmar e Enviar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/searchable-select.js"></script>
<script>
const materials = <?= json_encode($materials) ?>;
const constructionSiteId = <?= (int)($order['construction_site_id'] ?? 0) ?>;
let itemCount = 0;
let stockDecisions = {};

document.getElementById('addItemBtn').addEventListener('click', () => addItem());

function updateItemCount() {
    const count = document.querySelectorAll('#itemsBody tr').length;
    document.getElementById('itemCountBadge').textContent = count;
    document.getElementById('emptyState').style.display = count ? 'none' : '';
}

function buildMaterialOptions(prefill) {
    let opts = '<option value="">-- Selecione --</option>';
    materials.forEach(m => {
        const label = m.name + (m.classification ? ' - ' + m.classification : '') + (m.specification ? ' (' + m.specification + ')' : '');
        const selected = prefill && prefill.id == m.id ? 'selected' : '';
        opts += `<option value="${m.id}" data-name="${m.name}" data-spec="${m.specification || m.category_name || ''}" data-class="${m.classification || ''}" data-unit="${m.unit_abbr || m.unit_name || ''}" ${selected}>${label}</option>`;
    });
    return opts;
}

function addItem(prefill = null) {
    itemCount++;
    const idx = itemCount;
    const opts = buildMaterialOptions(prefill);

    const tr = document.createElement('tr');
    tr.id = 'item-row-' + idx;
    tr.innerHTML = `
        <td>
            <select class="material-select-raw" id="mat-select-${idx}" style="display:none;">${opts}</select>
            <div id="mat-ss-${idx}"></div>
            <input type="hidden" name="items[${idx}][material_id]" id="mid-${idx}" value="${prefill?.id || ''}">
            <input type="hidden" name="items[${idx}][material_name]" id="mname-${idx}" value="${prefill?.name || ''}">
            <input type="hidden" name="items[${idx}][unit]" id="unit-${idx}" value="${prefill?.unit || ''}">
        </td>
        <td><input type="text" class="form-control form-control-sm" name="items[${idx}][specification]" id="spec-${idx}" value="${prefill?.specification || ''}" readonly></td>
        <td><input type="text" class="form-control form-control-sm" name="items[${idx}][classification]" id="class-${idx}" value="${prefill?.classification || ''}" readonly></td>
        <td><input type="number" class="form-control form-control-sm" name="items[${idx}][quantity]" min="0.01" step="0.01" value="${prefill?.quantity || 1}" required></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(${idx})"><i class="bi bi-trash"></i></button></td>
    `;
    document.getElementById('itemsBody').appendChild(tr);

    // Inicializar SearchableSelect
    const matSS = new SearchableSelect(document.getElementById('mat-select-' + idx), {
        placeholder: 'Buscar material...',
        onSelect: function(value, text, dataset) {
            document.getElementById('mid-' + idx).value = value;
            document.getElementById('mname-' + idx).value = dataset.name || '';
            document.getElementById('spec-' + idx).value = dataset.spec || '';
            document.getElementById('class-' + idx).value = dataset.class || '';
            document.getElementById('unit-' + idx).value = dataset.unit || '';
        }
    });

    // Se tem prefill, setar valor
    if (prefill?.id) {
        matSS.setValue(prefill.id);
    }

    updateItemCount();
}

function removeItem(idx) {
    const row = document.getElementById('item-row-' + idx);
    if (row) row.remove();
    updateItemCount();
}

// Carregar itens existentes
document.addEventListener('DOMContentLoaded', function() {
    const existingItems = <?= json_encode(array_map(function($item) {
        return [
            'id' => $item['material_id'],
            'name' => $item['material_name'],
            'specification' => $item['specification'] ?? '',
            'classification' => $item['classification'] ?? '',
            'unit' => $item['unit'] ?? '',
            'quantity' => (float) $item['quantity'],
        ];
    }, $items)) ?>;

    existingItems.forEach(item => addItem(item));
});

// ============ VERIFICAÇÃO DE ESTOQUE ============

function checkStockBeforeSubmit() {
    if (!confirm('Confirma a edição dos itens? Notificações serão reenviadas.')) return;

    const rows = document.querySelectorAll('#itemsBody tr');
    let hasMaterialIds = false;
    rows.forEach(row => {
        const mid = row.querySelector('[id^="mid-"]')?.value;
        if (mid && mid !== '' && mid !== '0') hasMaterialIds = true;
    });
    if (!hasMaterialIds) { document.getElementById('editItemsForm').submit(); return; }

    const stockModal = new bootstrap.Modal(document.getElementById('stockCheckModal'));
    stockModal.show();
    checkStockAvailability();
}

async function checkStockAvailability() {
    const rows = document.querySelectorAll('#itemsBody tr');
    const targetSiteId = constructionSiteId;

    const items = [];
    rows.forEach(row => {
        const materialId = row.querySelector('[id^="mid-"]')?.value;
        const materialName = row.querySelector('[id^="mname-"]')?.value;
        const quantity = parseFloat(row.querySelector('[name*="[quantity]"]')?.value) || 1;
        const unit = row.querySelector('[id^="unit-"]')?.value || '';
        const rowId = row.id.replace('item-row-', '');
        items.push({ row_id: rowId, material_id: materialId ? parseInt(materialId) : null, material_name: materialName, quantity: quantity, unit: unit });
    });

    try {
        const resp = await fetch('/admin/stock/check-stock', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items: { target_site_id: targetSiteId, items: items } })
        });
        const data = await resp.json();
        renderStockDecisions(items, data.availability || {}, targetSiteId);
    } catch (e) {
        renderNoStockCheck(items);
    }
}

function renderStockDecisions(items, availability, targetSiteId) {
    const body = document.getElementById('stockCheckBody');
    stockDecisions = {};

    let hasStockItems = false;
    const withStock = [];
    const withoutStock = [];

    items.forEach(item => {
        const materialId = item.material_id;
        if (!materialId) { withoutStock.push(item); return; }
        const otherStocks = availability[materialId] || [];
        const localStock = availability['local'] ? availability['local'][materialId] : null;
        if (otherStocks.length > 0 || localStock) {
            hasStockItems = true;
            withStock.push({ ...item, stocks: otherStocks, localStock: localStock });
        } else {
            withoutStock.push(item);
        }
    });

    if (!hasStockItems) {
        body.innerHTML = `<div class="alert alert-light"><i class="bi bi-info-circle"></i> Nenhum dos materiais foi encontrado no estoque. Todos seguirão para cotação.</div>`;
        items.forEach(item => { stockDecisions[item.row_id] = { action: 'purchase' }; });
        document.getElementById('stockConfirmBtn').disabled = false;
        return;
    }

    let html = `<div class="alert alert-info small mb-3"><i class="bi bi-lightbulb"></i> Defina quanto tirar de cada estoque. O restante vai pra cotação.</div>`;

    withStock.forEach(item => {
        const neededQty = item.quantity;
        let allStocks = [];
        if (item.localStock) allStocks.push({...item.localStock, isLocal: true});
        item.stocks.forEach(s => allStocks.push({...s, isLocal: false}));

        html += `<div class="card mb-3 border-success"><div class="card-header bg-success bg-opacity-10 py-2">
            <strong>${escapeHtml(item.material_name)}</strong>
            <span class="badge bg-primary ms-2">Precisa: ${fmtQty(neededQty)}</span>
        </div><div class="card-body py-2">
            <small class="text-muted d-block mb-2">Distribua a quantidade entre os estoques:</small>
            <div class="stock-distribution" data-row="${item.row_id}" data-needed="${neededQty}">`;

        allStocks.forEach((s, sIdx) => {
            const availQty = parseFloat(s.quantity);
            const locName = escapeHtml(s.site_name || s.location_name || 'Estoque');
            html += `<div class="d-flex align-items-center gap-2 mb-2 p-2 bg-light rounded">
                <div class="flex-grow-1">
                    <small class="fw-bold">${s.isLocal ? '<i class="bi bi-geo-alt text-success"></i> ' : '<i class="bi bi-buildings text-primary"></i> '}${locName}</small>
                    <br><small class="text-muted">Disponível: ${fmtQty(availQty)}</small>
                </div>
                <div style="width:90px;">
                    <input type="number" class="form-control form-control-sm text-center stock-qty-input"
                           data-row="${item.row_id}" data-idx="${sIdx}" data-max="${availQty}"
                           data-site="${s.construction_site_id || 0}" data-location="${s.stock_location_id || 0}" data-is-local="${s.isLocal ? 1 : 0}"
                           min="0" max="${availQty}" step="0.01" value="0"
                           oninput="recalcStockDistribution('${item.row_id}')">
                </div>
            </div>`;
        });

        html += `<div class="d-flex align-items-center gap-2 p-2 border rounded border-warning">
                <div class="flex-grow-1"><small class="fw-bold"><i class="bi bi-cart text-warning"></i> Cotação (comprar)</small></div>
                <div style="width:90px;"><input type="number" class="form-control form-control-sm text-center" id="stock-remaining-${item.row_id}" value="${neededQty}" readonly style="background:#fff3cd;"></div>
            </div>
            <div class="mt-1"><small id="stock-status-${item.row_id}" class="text-muted">Preencha as quantidades acima</small></div>
        </div></div></div>`;
    });

    if (withoutStock.length > 0) {
        html += `<div class="card mb-3"><div class="card-header bg-light py-2"><small class="text-muted"><i class="bi bi-cart"></i> Sem estoque (seguem para cotação):</small></div><div class="card-body py-2"><ul class="list-unstyled mb-0">`;
        withoutStock.forEach(item => {
            stockDecisions[item.row_id] = { action: 'purchase' };
            html += `<li class="small mb-1">• ${escapeHtml(item.material_name)} - ${fmtQty(item.quantity)}</li>`;
        });
        html += `</ul></div></div>`;
    }

    body.innerHTML = html;
    withStock.forEach(item => recalcStockDistribution(item.row_id));
    document.getElementById('stockConfirmBtn').disabled = false;
}

function recalcStockDistribution(rowId) {
    const container = document.querySelector(`.stock-distribution[data-row="${rowId}"]`);
    if (!container) return;
    const needed = parseFloat(container.dataset.needed);
    const inputs = container.querySelectorAll('.stock-qty-input');
    let totalFromStock = 0;

    inputs.forEach(input => {
        let val = parseFloat(input.value) || 0;
        const max = parseFloat(input.dataset.max) || 0;
        if (val > max) { val = max; input.value = max; }
        if (val < 0) { val = 0; input.value = 0; }
        totalFromStock += val;
    });

    if (totalFromStock > needed) totalFromStock = needed;
    const remaining = Math.max(0, needed - totalFromStock);

    const remainingEl = document.getElementById('stock-remaining-' + rowId);
    const statusEl = document.getElementById('stock-status-' + rowId);
    if (remainingEl) remainingEl.value = fmtQty(remaining);
    if (statusEl) {
        if (totalFromStock === 0) statusEl.innerHTML = '<span class="text-warning">Tudo vai pra cotação</span>';
        else if (remaining === 0) statusEl.innerHTML = '<span class="text-success">✓ Tudo do estoque</span>';
        else statusEl.innerHTML = `<span class="text-info">${fmtQty(totalFromStock)} estoque + ${fmtQty(remaining)} cotação</span>`;
    }

    const distributions = [];
    inputs.forEach(input => {
        const val = parseFloat(input.value) || 0;
        if (val > 0) {
            distributions.push({
                site_id: parseInt(input.dataset.site) || 0,
                location_id: parseInt(input.dataset.location) || 0,
                quantity: val,
                is_local: input.dataset.isLocal === '1',
            });
        }
    });

    if (distributions.length === 0) {
        stockDecisions[rowId] = { action: 'purchase' };
    } else if (remaining === 0 && distributions.length === 1) {
        const d = distributions[0];
        stockDecisions[rowId] = { action: d.is_local ? 'stock_use' : 'stock_transfer', from_site_id: d.site_id, from_location_id: d.location_id, stock_qty: d.quantity };
    } else if (remaining === 0 && distributions.length > 1) {
        stockDecisions[rowId] = { action: 'stock_multi', distributions: distributions };
    } else if (distributions.length === 1) {
        const d = distributions[0];
        stockDecisions[rowId] = { action: d.is_local ? 'stock_partial' : 'stock_transfer_partial', from_site_id: d.site_id, from_location_id: d.location_id, stock_qty: d.quantity };
    } else {
        stockDecisions[rowId] = { action: 'stock_multi_partial', distributions: distributions, purchase_qty: remaining };
    }
}

function renderNoStockCheck(items) {
    const body = document.getElementById('stockCheckBody');
    body.innerHTML = `<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> Não foi possível verificar o estoque. Todos seguirão para cotação.</div>`;
    items.forEach(item => { stockDecisions[item.row_id] = { action: 'purchase' }; });
    document.getElementById('stockConfirmBtn').disabled = false;
}

function confirmStockDecisions() {
    const form = document.getElementById('editItemsForm');
    form.querySelectorAll('.stock-decision-input').forEach(el => el.remove());

    Object.keys(stockDecisions).forEach(rowId => {
        const decision = stockDecisions[rowId];

        if (decision.action === 'stock_multi' || decision.action === 'stock_multi_partial') {
            addHidden(form, `stock_decisions[${rowId}][action]`, decision.action);
            addHidden(form, `stock_decisions[${rowId}][distributions]`, JSON.stringify(decision.distributions));
            if (decision.purchase_qty) addHidden(form, `stock_decisions[${rowId}][purchase_qty]`, decision.purchase_qty);
        } else {
            addHidden(form, `stock_decisions[${rowId}][action]`, decision.action);
            if (decision.from_site_id) addHidden(form, `stock_decisions[${rowId}][from_site_id]`, decision.from_site_id);
            if (decision.from_location_id) addHidden(form, `stock_decisions[${rowId}][from_location_id]`, decision.from_location_id);
            if (decision.stock_qty) addHidden(form, `stock_decisions[${rowId}][stock_qty]`, decision.stock_qty);
        }
    });

    bootstrap.Modal.getInstance(document.getElementById('stockCheckModal')).hide();
    form.submit();
}

function addHidden(form, name, value) {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = name;
    input.value = value;
    input.className = 'stock-decision-input';
    form.appendChild(input);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function fmtQty(val) {
    const num = parseFloat(val);
    if (isNaN(num)) return '0';
    return num % 1 === 0 ? num.toFixed(0) : num.toFixed(2).replace('.', ',');
}
</script>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
