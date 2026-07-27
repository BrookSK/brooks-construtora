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

    // Coletar itens com material_id
    const items = [];
    rows.forEach(row => {
        const materialId = row.querySelector('[id^="mid-"]')?.value;
        const materialName = row.querySelector('[id^="mname-"]')?.value;
        const quantity = parseFloat(row.querySelector('[name*="[quantity]"]')?.value) || 1;
        const unit = row.querySelector('[id^="unit-"]')?.value || '';
        const rowId = row.id.replace('item-row-', '');

        items.push({
            row_id: rowId,
            material_id: materialId ? parseInt(materialId) : null,
            material_name: materialName,
            quantity: quantity,
            unit: unit
        });
    });

    // Verificar estoque via AJAX
    try {
        const resp = await fetch('/admin/stock/check-stock', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items: { target_site_id: targetSiteId, items: items } })
        });
        const data = await resp.json();
        renderStockDecisions(items, data.availability || {}, targetSiteId);
    } catch (e) {
        // Se falhar a verificação, permite seguir sem estoque
        renderNoStockCheck(items);
    }
}

function renderStockDecisions(items, availability, targetSiteId) {
    const body = document.getElementById('stockCheckBody');
    stockDecisions = {};

    let hasStockItems = false;
    let html = '';

    // Separar itens com e sem estoque disponível
    const withStock = [];
    const withoutStock = [];

    items.forEach(item => {
        const materialId = item.material_id;
        if (!materialId) {
            withoutStock.push(item);
            return;
        }

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
        // Nenhum item tem estoque - seguir direto para compra
        html = `<div class="alert alert-light">
            <i class="bi bi-info-circle"></i> Nenhum dos materiais foi encontrado no estoque. Todos os itens seguirão para cotação normalmente.
        </div>`;
        body.innerHTML = html;
        // Auto-marcar todos como compra
        items.forEach(item => {
            stockDecisions[item.row_id] = { action: 'purchase' };
        });
        document.getElementById('stockConfirmBtn').disabled = false;
        return;
    }

    html += `<div class="alert alert-info small mb-3">
        <i class="bi bi-lightbulb"></i> Encontramos materiais disponíveis no estoque! Para cada item, escolha a ação desejada.
    </div>`;

    // Itens com estoque
    withStock.forEach(item => {
        html += `<div class="card mb-3 border-success">
            <div class="card-header bg-success bg-opacity-10 py-2">
                <strong>${escapeHtml(item.material_name)}</strong>
                <span class="badge bg-primary ms-2">${fmtQty(item.quantity)} ${item.unit}</span>
            </div>
            <div class="card-body py-2">`;

        // Estoque local (na obra destino)
        if (item.localStock) {
            html += `<div class="mb-2 p-2 bg-light rounded">
                <i class="bi bi-geo-alt text-success"></i> 
                <strong>Estoque na obra destino:</strong> ${fmtQty(item.localStock.quantity)} ${item.localStock.unit_abbr || ''} 
                <small class="text-muted">(${escapeHtml(item.localStock.site_name)})</small>
            </div>`;
        }

        // Estoques em outras obras
        if (item.stocks.length > 0) {
            html += `<div class="mb-2"><small class="text-muted">Disponível em outras obras:</small><ul class="list-unstyled mb-0 ms-2">`;
            item.stocks.forEach(s => {
                html += `<li class="small"><i class="bi bi-buildings"></i> ${escapeHtml(s.site_name || s.location_name || '')} - <strong>${fmtQty(s.quantity)} ${s.unit_abbr || ''}</strong></li>`;
            });
            html += `</ul></div>`;
        }

        // Opções de decisão
        html += `<div class="mt-2 border-top pt-2">
            <label class="form-label small fw-bold mb-1">O que fazer com este item?</label>
            <div class="d-flex flex-column gap-1">`;

        const localQty = item.localStock ? parseFloat(item.localStock.quantity) : 0;
        const neededQty = item.quantity;
        const isPartial = localQty > 0 && localQty < neededQty;
        const remaining = neededQty - localQty;

        // Opção: Usar do estoque local (total - quando tem suficiente)
        if (item.localStock && localQty >= neededQty) {
            html += `<div class="form-check">
                <input class="form-check-input stock-decision" type="radio" name="stock_decision_${item.row_id}" 
                       value="use_local" data-row="${item.row_id}" data-site="${item.localStock.construction_site_id}"
                       onchange="updateStockDecision('${item.row_id}', 'stock_use', ${item.localStock.construction_site_id}, null, ${neededQty})">
                <label class="form-check-label small">
                    <i class="bi bi-check-circle text-success"></i> Usar tudo do estoque (${escapeHtml(item.localStock.site_name || item.localStock.location_name || '')})
                </label>
            </div>`;
        }

        // Opção: Usar parcial do estoque + comprar o resto
        if (isPartial) {
            html += `<div class="form-check">
                <input class="form-check-input stock-decision" type="radio" name="stock_decision_${item.row_id}" 
                       value="partial_local" data-row="${item.row_id}" data-site="${item.localStock.construction_site_id}"
                       onchange="updateStockDecision('${item.row_id}', 'stock_partial', ${item.localStock.construction_site_id}, null, ${localQty})">
                <label class="form-check-label small">
                    <i class="bi bi-pie-chart text-info"></i> Usar <strong>${fmtQty(localQty)}</strong> do estoque + comprar <strong>${fmtQty(remaining)}</strong> (cotação)
                </label>
            </div>`;
        }

        // Opção: Transferir de outra obra (total)
        item.stocks.forEach(s => {
            const stockQty = parseFloat(s.quantity);
            if (stockQty >= neededQty) {
                html += `<div class="form-check">
                    <input class="form-check-input stock-decision" type="radio" name="stock_decision_${item.row_id}" 
                           value="transfer_${s.construction_site_id || s.stock_location_id}" data-row="${item.row_id}" data-site="${s.construction_site_id || s.stock_location_id}"
                           onchange="updateStockDecision('${item.row_id}', 'stock_transfer', ${s.construction_site_id || s.stock_location_id}, null, ${neededQty})">
                    <label class="form-check-label small">
                        <i class="bi bi-arrow-left-right text-primary"></i> Transferir de ${escapeHtml(s.site_name || s.location_name)} (${fmtQty(stockQty)} ${s.unit_abbr || ''} disponíveis)
                    </label>
                </div>`;
            } else if (stockQty > 0) {
                // Transferência parcial
                html += `<div class="form-check">
                    <input class="form-check-input stock-decision" type="radio" name="stock_decision_${item.row_id}" 
                           value="transfer_partial_${s.construction_site_id || s.stock_location_id}" data-row="${item.row_id}" data-site="${s.construction_site_id || s.stock_location_id}"
                           onchange="updateStockDecision('${item.row_id}', 'stock_transfer_partial', ${s.construction_site_id || s.stock_location_id}, null, ${stockQty})">
                    <label class="form-check-label small">
                        <i class="bi bi-pie-chart text-primary"></i> Transferir <strong>${fmtQty(stockQty)}</strong> de ${escapeHtml(s.site_name || s.location_name)} + comprar <strong>${fmtQty(neededQty - stockQty)}</strong> (cotação)
                    </label>
                </div>`;
            }
        });

        // Opção: Comprar (sempre disponível)
        html += `<div class="form-check">
            <input class="form-check-input stock-decision" type="radio" name="stock_decision_${item.row_id}" 
                   value="purchase" data-row="${item.row_id}"
                   onchange="updateStockDecision('${item.row_id}', 'purchase', null)">
            <label class="form-check-label small">
                <i class="bi bi-cart text-warning"></i> Realizar pedido de compra (cotação)
            </label>
        </div>`;

        html += `</div></div></div></div>`;
    });

    // Itens sem estoque
    if (withoutStock.length > 0) {
        html += `<div class="card mb-3">
            <div class="card-header bg-light py-2">
                <small class="text-muted"><i class="bi bi-cart"></i> Itens sem estoque disponível (seguem para cotação):</small>
            </div>
            <div class="card-body py-2">
                <ul class="list-unstyled mb-0">`;
        withoutStock.forEach(item => {
            stockDecisions[item.row_id] = { action: 'purchase' };
            html += `<li class="small mb-1">• ${escapeHtml(item.material_name)} - ${item.quantity} ${item.unit}</li>`;
        });
        html += `</ul></div></div>`;
    }

    body.innerHTML = html;
    validateStockDecisions();
}

function renderNoStockCheck(items) {
    const body = document.getElementById('stockCheckBody');
    body.innerHTML = `<div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> Não foi possível verificar o estoque. Todos os itens seguirão para cotação.
    </div>`;
    items.forEach(item => {
        stockDecisions[item.row_id] = { action: 'purchase' };
    });
    document.getElementById('stockConfirmBtn').disabled = false;
}

function updateStockDecision(rowId, action, fromSiteId, toSiteId, stockQty) {
    stockDecisions[rowId] = { action: action, from_site_id: fromSiteId, stock_qty: stockQty || null };
    validateStockDecisions();
}

function validateStockDecisions() {
    // Verificar se todos os itens com estoque têm decisão
    const allRadios = document.querySelectorAll('.stock-decision');
    const groups = {};
    allRadios.forEach(r => {
        const name = r.getAttribute('name');
        if (!groups[name]) groups[name] = false;
        if (r.checked) groups[name] = true;
    });

    const allDecided = Object.values(groups).every(v => v);
    document.getElementById('stockConfirmBtn').disabled = !allDecided;
}

function confirmStockDecisions() {
    // Adicionar campos hidden com as decisões de estoque no formulário
    const form = document.getElementById('orderForm');

    // Remover decisões anteriores
    form.querySelectorAll('.stock-decision-input').forEach(el => el.remove());

    Object.keys(stockDecisions).forEach(rowId => {
        const decision = stockDecisions[rowId];
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

        if (decision.stock_qty) {
            const qtyInput = document.createElement('input');
            qtyInput.type = 'hidden';
            qtyInput.name = `stock_decisions[${rowId}][stock_qty]`;
            qtyInput.value = decision.stock_qty;
            qtyInput.className = 'stock-decision-input';
            form.appendChild(qtyInput);
        }
    });

    // Fechar modal e submeter
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

// Override do confirmSubmit original para interceptar com verificação de estoque
const originalConfirmSubmit = window.confirmSubmit;
window.confirmSubmit = function() {
    // Fechar modal de revisão
    bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();

    // Verificar se tem obra selecionada (estoque é por obra)
    const siteSelect = document.getElementById('constructionSiteSelect');
    if (!siteSelect || !siteSelect.value) {
        // Sem obra, não verifica estoque
        document.getElementById('orderForm').submit();
        return;
    }

    // Verificar se há materiais com material_id (necessário para buscar estoque)
    const rows = document.querySelectorAll('#itemsBodyDesktop tr');
    let hasMaterialIds = false;
    rows.forEach(row => {
        const mid = row.querySelector('[id^="mid-"]')?.value;
        if (mid && mid !== '' && mid !== '0') hasMaterialIds = true;
    });

    if (!hasMaterialIds) {
        document.getElementById('orderForm').submit();
        return;
    }

    // Abrir modal de verificação de estoque
    const stockModal = new bootstrap.Modal(document.getElementById('stockCheckModal'));
    stockModal.show();
    checkStockAvailability();
};
</script>
