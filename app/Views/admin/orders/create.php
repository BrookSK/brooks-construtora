<?php $pageTitle = 'Novo Pedido de Materiais'; $currentPage = 'orders'; ?>
<?php ob_start(); ?>

<form method="POST" action="/admin/orders/store" id="orderForm">
    <!-- Fornecedor -->
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-building"></i> Fornecedor</div>
        <div class="card-body">
            <label class="form-label">Selecionar Fornecedor</label>
            <select class="form-select mb-2" name="supplier_id" id="supplierSelect">
                <option value="">-- Selecione --</option>
                <?php foreach ($suppliers as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> <?= $s['cnpj'] ? '(' . $s['cnpj'] . ')' : '' ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="btn btn-outline-primary btn-sm w-100" data-bs-toggle="modal" data-bs-target="#newSupplierModal">
                <i class="bi bi-plus"></i> Cadastrar Novo Fornecedor
            </button>
        </div>
    </div>

    <!-- Itens do pedido -->
    <div class="card mb-3">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-check"></i> Itens</span>
                <span class="badge bg-primary" id="itemCountBadge">0</span>
            </div>
        </div>
        <div class="card-body">
            <div class="d-grid gap-2 mb-3">
                <button type="button" class="btn btn-primary" id="addItemBtn">
                    <i class="bi bi-plus-lg"></i> Adicionar Item
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#newMaterialModal">
                    <i class="bi bi-box-seam"></i> Cadastrar Novo Material
                </button>
            </div>

            <div id="itemsList">
                <div class="text-center text-muted py-3" id="emptyItemsMsg">
                    <i class="bi bi-inbox d-block mb-1" style="font-size:1.5rem;"></i>
                    Nenhum item adicionado
                </div>
            </div>
        </div>
    </div>

    <!-- Observações -->
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-chat-left-text"></i> Observações</div>
        <div class="card-body">
            <textarea class="form-control" name="description" rows="3" placeholder="Observações adicionais sobre o pedido..."></textarea>
        </div>
    </div>

    <!-- Ações -->
    <div class="d-grid gap-2 mb-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-send"></i> Criar Pedido e Enviar para Cotação
        </button>
        <a href="/admin/orders" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
</form>

<!-- Modal Novo Fornecedor -->
<div class="modal fade" id="newSupplierModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Fornecedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nome *</label>
                    <input type="text" class="form-control" id="newSupplierName" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">CNPJ</label>
                    <input type="text" class="form-control" id="newSupplierCnpj">
                </div>
                <div class="mb-3">
                    <label class="form-label">E-mail</label>
                    <input type="email" class="form-control" id="newSupplierEmail">
                </div>
                <div class="mb-3">
                    <label class="form-label">Telefone</label>
                    <input type="text" class="form-control" id="newSupplierPhone">
                </div>
                <div class="mb-3">
                    <label class="form-label">Contato</label>
                    <input type="text" class="form-control" id="newSupplierContact">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveSupplierBtn">Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Novo Material -->
<div class="modal fade" id="newMaterialModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nome do Material *</label>
                    <input type="text" class="form-control" id="newMatName" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Especificação (Tipo)</label>
                    <div class="input-group">
                        <select class="form-select" id="newMatSpec">
                            <option value="">-- Selecione --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['name']) ?>" data-id="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-outline-primary" onclick="quickAddCategory()" title="Nova">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Classificação</label>
                    <input type="text" class="form-control" id="newMatClassification" placeholder="Ex: 100mm, 3/4&quot;, 50x40">
                </div>
                <div class="mb-3">
                    <label class="form-label">Unidade de Medida</label>
                    <div class="input-group">
                        <select class="form-select" id="newMatUnit">
                            <option value="">-- Selecione --</option>
                            <?php foreach ($units as $u): ?>
                            <option value="<?= $u['id'] ?>" data-abbr="<?= htmlspecialchars($u['abbreviation']) ?>"><?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['abbreviation']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-outline-primary" onclick="quickAddUnit()" title="Nova">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveMaterialBtn">Salvar Material</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cadastro Rápido Especificação -->
<div class="modal fade" id="quickCategoryModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title">Nova Especificação</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control" id="quickCatName" placeholder="Ex: mat. Elétrico">
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="saveQuickCategory()">Salvar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cadastro Rápido Unidade -->
<div class="modal fade" id="quickUnitModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title">Nova Unidade de Medida</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <input type="text" class="form-control" id="quickUnitName" placeholder="Nome (Ex: Galão)">
                </div>
                <div>
                    <input type="text" class="form-control" id="quickUnitAbbr" placeholder="Abreviação (Ex: gal)">
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-primary" onclick="saveQuickUnit()">Salvar</button>
            </div>
        </div>
    </div>
</div>

<style>
.item-card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 10px;
    background: #fafbfc;
    position: relative;
}
.item-card .item-number {
    position: absolute;
    top: -8px;
    left: 10px;
    background: #3a3b4e;
    color: #fff;
    font-size: 0.65rem;
    padding: 1px 8px;
    border-radius: 10px;
}
.item-card .item-details {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 6px;
}
.item-card .item-details .badge {
    font-weight: 400;
    font-size: 0.7rem;
}
</style>

<script>
const materials = <?= json_encode($materials) ?>;
let itemCount = 0;

document.getElementById('addItemBtn').addEventListener('click', () => addItem());

function updateItemCount() {
    const count = document.querySelectorAll('.item-card').length;
    document.getElementById('itemCountBadge').textContent = count;
}

function addItem(prefill = null) {
    itemCount++;
    document.getElementById('emptyItemsMsg').style.display = 'none';
    
    // Montar options do select
    let materialOptions = '<option value="">-- Selecione o material --</option>';
    materials.forEach(m => {
        const label = m.name + (m.classification ? ' - ' + m.classification : '');
        const selected = prefill && prefill.id == m.id ? 'selected' : '';
        materialOptions += `<option value="${m.id}" 
            data-name="${m.name}" 
            data-spec="${m.specification || m.category_name || ''}" 
            data-class="${m.classification || ''}" 
            data-unit="${m.unit_abbr || m.unit_name || ''}"
            ${selected}>${label}</option>`;
    });
    
    const card = document.createElement('div');
    card.className = 'item-card';
    card.innerHTML = `
        <span class="item-number">#${itemCount}</span>
        <div class="mb-2">
            <div class="d-flex gap-2 align-items-center">
                <select class="form-select form-select-sm material-select flex-grow-1" onchange="materialSelected(this)">
                    ${materialOptions}
                </select>
                <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0" onclick="removeItem(this)" title="Remover item">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <input type="hidden" name="items[${itemCount}][material_id]" class="material-id" value="${prefill ? (prefill.id || '') : ''}">
            <input type="hidden" name="items[${itemCount}][material_name]" class="material-name" value="${prefill ? (prefill.name || '') : ''}">
            <input type="hidden" name="items[${itemCount}][specification]" class="spec-field" value="${prefill ? (prefill.specification || '') : ''}">
            <input type="hidden" name="items[${itemCount}][classification]" class="class-field" value="${prefill ? (prefill.classification || '') : ''}">
            <input type="hidden" name="items[${itemCount}][unit]" class="unit-field" value="${prefill ? (prefill.unit || '') : ''}">
        </div>
        <div class="item-details" id="itemDetails${itemCount}">
            ${prefill ? `
                <span class="badge bg-light text-dark">${prefill.specification || ''}</span>
                <span class="badge bg-light text-dark">${prefill.classification || ''}</span>
                <span class="badge bg-light text-dark">${prefill.unit || ''}</span>
            ` : '<span class="text-muted" style="font-size:0.75rem;">Selecione um material acima</span>'}
        </div>
        <div class="d-flex align-items-center gap-2 mt-2">
            <label class="form-label mb-0 small fw-bold" style="white-space:nowrap;">Qtd:</label>
            <input type="number" class="form-control form-control-sm" style="max-width:100px;" 
                name="items[${itemCount}][quantity]" min="0.01" step="0.01" 
                value="${prefill ? (prefill.quantity || 1) : 1}" required>
            <span class="text-muted small unit-label">${prefill ? (prefill.unit || '') : ''}</span>
        </div>
    `;
    
    document.getElementById('itemsList').appendChild(card);
    updateItemCount();

    // Se tem prefill, garantir select
    if (prefill && prefill.id) {
        card.querySelector('.material-select').value = prefill.id;
    }
}

function removeItem(btn) {
    btn.closest('.item-card').remove();
    if (document.querySelectorAll('.item-card').length === 0) {
        document.getElementById('emptyItemsMsg').style.display = '';
    }
    updateItemCount();
}

function materialSelected(selectEl) {
    const card = selectEl.closest('.item-card');
    const option = selectEl.selectedOptions[0];
    const detailsEl = card.querySelector('.item-details');
    
    if (option && option.value) {
        card.querySelector('.material-id').value = option.value;
        card.querySelector('.material-name').value = option.dataset.name || '';
        card.querySelector('.spec-field').value = option.dataset.spec || '';
        card.querySelector('.class-field').value = option.dataset.class || '';
        card.querySelector('.unit-field').value = option.dataset.unit || '';
        card.querySelector('.unit-label').textContent = option.dataset.unit || '';
        
        detailsEl.innerHTML = '';
        if (option.dataset.spec) detailsEl.innerHTML += `<span class="badge bg-light text-dark">${option.dataset.spec}</span>`;
        if (option.dataset.class) detailsEl.innerHTML += `<span class="badge bg-light text-dark">${option.dataset.class}</span>`;
        if (option.dataset.unit) detailsEl.innerHTML += `<span class="badge bg-info text-white">${option.dataset.unit}</span>`;
    } else {
        card.querySelector('.material-id').value = '';
        card.querySelector('.material-name').value = '';
        card.querySelector('.spec-field').value = '';
        card.querySelector('.class-field').value = '';
        card.querySelector('.unit-field').value = '';
        card.querySelector('.unit-label').textContent = '';
        detailsEl.innerHTML = '<span class="text-muted" style="font-size:0.75rem;">Selecione um material acima</span>';
    }
}

// Salvar fornecedor inline
document.getElementById('saveSupplierBtn').addEventListener('click', async function() {
    const name = document.getElementById('newSupplierName').value.trim();
    if (!name) { alert('Nome é obrigatório'); return; }

    const resp = await fetch('/admin/suppliers/quick-store', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            name, cnpj: document.getElementById('newSupplierCnpj').value,
            email: document.getElementById('newSupplierEmail').value,
            phone: document.getElementById('newSupplierPhone').value,
            contact_person: document.getElementById('newSupplierContact').value,
        })
    });
    const data = await resp.json();
    if (data.success) {
        document.getElementById('supplierSelect').add(new Option(data.supplier.name, data.supplier.id, true, true));
        bootstrap.Modal.getInstance(document.getElementById('newSupplierModal')).hide();
        ['newSupplierName','newSupplierCnpj','newSupplierEmail','newSupplierPhone','newSupplierContact'].forEach(id => document.getElementById(id).value = '');
    } else { alert(data.error || 'Erro'); }
});

// Salvar material inline
document.getElementById('saveMaterialBtn').addEventListener('click', async function() {
    const name = document.getElementById('newMatName').value.trim();
    if (!name) { alert('Nome é obrigatório'); return; }

    const specSelect = document.getElementById('newMatSpec');
    const unitSelect = document.getElementById('newMatUnit');

    const resp = await fetch('/admin/materials/quick-store', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            name, specification: specSelect.value,
            category_id: specSelect.selectedOptions[0]?.dataset?.id || '',
            unit_id: unitSelect.value || '',
            classification: document.getElementById('newMatClassification').value,
        })
    });
    const data = await resp.json();
    if (data.success) {
        const unitAbbr = unitSelect.selectedOptions[0]?.dataset?.abbr || '';
        materials.push({ id: data.material.id, name: data.material.name, specification: data.material.specification || specSelect.value, classification: data.material.classification || '', unit_abbr: unitAbbr, unit_name: '', category_name: specSelect.value });
        
        // Adicionar options nos selects existentes
        document.querySelectorAll('.material-select').forEach(sel => {
            const label = data.material.name + (data.material.classification ? ' - ' + data.material.classification : '');
            const opt = new Option(label, data.material.id);
            opt.dataset.name = data.material.name;
            opt.dataset.spec = specSelect.value;
            opt.dataset.class = data.material.classification || '';
            opt.dataset.unit = unitAbbr;
            sel.add(opt);
        });
        
        addItem({ id: data.material.id, name: data.material.name, specification: specSelect.value, classification: data.material.classification || '', unit: unitAbbr, quantity: 1 });
        bootstrap.Modal.getInstance(document.getElementById('newMaterialModal')).hide();
        ['newMatName','newMatClassification'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('newMatSpec').value = '';
        document.getElementById('newMatUnit').value = '';
    } else { alert(data.error || 'Erro'); }
});

// Validação
document.getElementById('orderForm').addEventListener('submit', function(e) {
    if (document.querySelectorAll('.item-card').length === 0) { e.preventDefault(); alert('Adicione pelo menos um item.'); return; }
    let valid = true;
    document.querySelectorAll('.material-select').forEach(sel => {
        if (!sel.value) { valid = false; sel.classList.add('is-invalid'); } else { sel.classList.remove('is-invalid'); }
    });
    if (!valid) { e.preventDefault(); alert('Selecione um material para cada item.'); }
});

// Quick add category/unit
function quickAddCategory() { new bootstrap.Modal(document.getElementById('quickCategoryModal')).show(); }
async function saveQuickCategory() {
    const name = document.getElementById('quickCatName').value.trim();
    if (!name) { alert('Nome é obrigatório'); return; }
    const resp = await fetch('/admin/materials/quick-store-category', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: new URLSearchParams({ name }) });
    const data = await resp.json();
    if (data.success) {
        const opt = new Option(data.category.name, data.category.name); opt.dataset.id = data.category.id;
        document.getElementById('newMatSpec').add(opt); document.getElementById('newMatSpec').value = data.category.name;
        bootstrap.Modal.getInstance(document.getElementById('quickCategoryModal')).hide(); document.getElementById('quickCatName').value = '';
    } else { alert(data.error || 'Erro'); }
}
function quickAddUnit() { new bootstrap.Modal(document.getElementById('quickUnitModal')).show(); }
async function saveQuickUnit() {
    const name = document.getElementById('quickUnitName').value.trim();
    const abbr = document.getElementById('quickUnitAbbr').value.trim();
    if (!name || !abbr) { alert('Nome e abreviação são obrigatórios'); return; }
    const resp = await fetch('/admin/materials/quick-store-unit', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: new URLSearchParams({ name, abbreviation: abbr }) });
    const data = await resp.json();
    if (data.success) {
        const opt = new Option(`${data.unit.name} (${data.unit.abbreviation})`, data.unit.id); opt.dataset.abbr = data.unit.abbreviation;
        document.getElementById('newMatUnit').add(opt); document.getElementById('newMatUnit').value = data.unit.id;
        bootstrap.Modal.getInstance(document.getElementById('quickUnitModal')).hide(); document.getElementById('quickUnitName').value = ''; document.getElementById('quickUnitAbbr').value = '';
    } else { alert(data.error || 'Erro'); }
}
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
