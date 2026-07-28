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
            <div class="modal-footer flex-column flex-sm-row gap-2" style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom, 0px));">
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

<script>
let stockDecisions = {};

async function checkStockAvailability() {
    const rows = document.querySelectorAll('#itemsBodyDesktop tr');
    const siteSelect = document.getElementById('constructionSiteSelect');
    const targetSiteId = siteSelect ? parseInt(siteSelect.value) || 0 : 0;

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

    let html = `<div class="alert alert-info small mb-3">
        <i class="bi bi-lightbulb"></i> Defina quanto tirar de cada estoque. O restante vai pra cotação.
    </div>`;

    // Itens com estoque — interface de distribuição por quantidade
    withStock.forEach(item => {
        const neededQty = item.quantity;
        let allStocks = [];
        if (item.localStock) allStocks.push({...item.localStock, isLocal: true});
        item.stocks.forEach(s => allStocks.push({...s, isLocal: false}));

        html += `<div class="card mb-3 border-success">
            <div class="card-header bg-success bg-opacity-10 py-2">
                <strong>${escapeHtml(item.material_name)}</strong>
                <span class="badge bg-primary ms-2">Precisa: ${fmtQty(neededQty)} ${item.unit}</span>
            </div>
            <div class="card-body py-2">
                <small class="text-muted d-block mb-2">Distribua a quantidade entre os estoques:</small>
                <div class="stock-distribution" data-row="${item.row_id}" data-needed="${neededQty}">`;

        allStocks.forEach((s, sIdx) => {
            const availQty = parseFloat(s.quantity);
            const locName = escapeHtml(s.site_name || s.location_name || 'Estoque');
            const locId = s.stock_location_id || 0;
            const siteId = s.construction_site_id || 0;
            html += `<div class="d-flex align-items-center gap-2 mb-2 p-2 bg-light rounded">
                <div class="flex-grow-1">
                    <small class="fw-bold">${s.isLocal ? '<i class="bi bi-geo-alt text-success"></i> ' : '<i class="bi bi-buildings text-primary"></i> '}${locName}</small>
                    <br><small class="text-muted">Disponível: ${fmtQty(availQty)}</small>
                </div>
                <div style="width:90px;">
                    <input type="number" class="form-control form-control-sm text-center stock-qty-input" 
                           data-row="${item.row_id}" data-idx="${sIdx}" data-max="${availQty}"
                           data-site="${siteId}" data-location="${locId}" data-is-local="${s.isLocal ? 1 : 0}"
                           min="0" max="${availQty}" step="0.01" value="0" placeholder="0"
                           oninput="recalcStockDistribution('${item.row_id}')">
                </div>
            </div>`;
        });

        html += `<div class="d-flex align-items-center gap-2 p-2 border rounded border-warning">
                <div class="flex-grow-1">
                    <small class="fw-bold"><i class="bi bi-cart text-warning"></i> Cotação (comprar)</small>
                </div>
                <div style="width:90px;">
                    <input type="number" class="form-control form-control-sm text-center" id="stock-remaining-${item.row_id}" 
                           value="${neededQty}" readonly style="background:#fff3cd;">
                </div>
            </div>
            <div class="mt-1"><small id="stock-status-${item.row_id}" class="text-muted">Preencha as quantidades acima</small></div>
        </div></div></div>`;
    });

    // Itens sem estoque
    if (withoutStock.length > 0) {
        html += `<div class="card mb-3"><div class="card-header bg-light py-2">
            <small class="text-muted"><i class="bi bi-cart"></i> Sem estoque (seguem para cotação):</small>
        </div><div class="card-body py-2"><ul class="list-unstyled mb-0">`;
        withoutStock.forEach(item => {
            stockDecisions[item.row_id] = { action: 'purchase' };
            html += `<li class="small mb-1">• ${escapeHtml(item.material_name)} - ${fmtQty(item.quantity)} ${item.unit}</li>`;
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

    // Montar decisão
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
    const form = document.getElementById('orderForm');
    form.querySelectorAll('.stock-decision-input').forEach(el => el.remove());

    Object.keys(stockDecisions).forEach(rowId => {
        const decision = stockDecisions[rowId];

        if (decision.action === 'stock_multi' || decision.action === 'stock_multi_partial') {
            // Múltiplos estoques — serializar como JSON
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `stock_decisions[${rowId}][action]`;
            input.value = decision.action;
            input.className = 'stock-decision-input';
            form.appendChild(input);

            const distInput = document.createElement('input');
            distInput.type = 'hidden';
            distInput.name = `stock_decisions[${rowId}][distributions]`;
            distInput.value = JSON.stringify(decision.distributions);
            distInput.className = 'stock-decision-input';
            form.appendChild(distInput);

            if (decision.purchase_qty) {
                const pqInput = document.createElement('input');
                pqInput.type = 'hidden';
                pqInput.name = `stock_decisions[${rowId}][purchase_qty]`;
                pqInput.value = decision.purchase_qty;
                pqInput.className = 'stock-decision-input';
                form.appendChild(pqInput);
            }
        } else {
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = `stock_decisions[${rowId}][action]`;
            actionInput.value = decision.action;
            actionInput.className = 'stock-decision-input';
            form.appendChild(actionInput);

            if (decision.from_site_id) {
                const siteInput = document.createElement('input');
                siteInput.type = 'hidden';
                siteInput.name = `stock_decisions[${rowId}][from_site_id]`;
                siteInput.value = decision.from_site_id;
                siteInput.className = 'stock-decision-input';
                form.appendChild(siteInput);
            }
            if (decision.from_location_id) {
                const locInput = document.createElement('input');
                locInput.type = 'hidden';
                locInput.name = `stock_decisions[${rowId}][from_location_id]`;
                locInput.value = decision.from_location_id;
                locInput.className = 'stock-decision-input';
                form.appendChild(locInput);
            }
            if (decision.stock_qty) {
                const qtyInput = document.createElement('input');
                qtyInput.type = 'hidden';
                qtyInput.name = `stock_decisions[${rowId}][stock_qty]`;
                qtyInput.value = decision.stock_qty;
                qtyInput.className = 'stock-decision-input';
                form.appendChild(qtyInput);
            }
        }
    });

    bootstrap.Modal.getInstance(document.getElementById('stockCheckModal')).hide();
    form.submit();
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

function checkStockBeforeSubmit() {
    const rows = document.querySelectorAll('#itemsBodyDesktop tr');
    let hasMaterialIds = false;
    rows.forEach(row => {
        const mid = row.querySelector('[id^="mid-"]')?.value;
        if (mid && mid !== '' && mid !== '0') hasMaterialIds = true;
    });
    if (!hasMaterialIds) { document.getElementById('orderForm').submit(); return; }
    const stockModal = new bootstrap.Modal(document.getElementById('stockCheckModal'));
    stockModal.show();
    checkStockAvailability();
}
</script>
