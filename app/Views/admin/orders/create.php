<?php $pageTitle = 'Novo Pedido de Materiais'; $currentPage = 'orders'; ?>
<?php ob_start(); ?>

<form method="POST" action="/admin/orders/store" id="orderForm">
    <!-- Fornecedor -->
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-building"></i> Fornecedor</div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <label class="form-label">Selecionar Fornecedor</label>
                    <select class="form-select" name="supplier_id" id="supplierSelect">
                        <option value="">-- Selecione ou cadastre abaixo --</option>
                        <?php foreach ($suppliers as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> <?= $s['cnpj'] ? '(' . $s['cnpj'] . ')' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#newSupplierModal">
                        <i class="bi bi-plus"></i> Novo Fornecedor
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Itens do pedido -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list-check"></i> Itens do Pedido</span>
            <button type="button" class="btn btn-sm btn-primary" id="addItemBtn">
                <i class="bi bi-plus"></i> Adicionar Item
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0" id="itemsTable">
                    <thead>
                        <tr class="bg-light">
                            <th style="min-width:200px;">Material</th>
                            <th style="min-width:130px;">Especificação</th>
                            <th style="min-width:100px;">Classificação</th>
                            <th style="min-width:100px;">Unid. Medida</th>
                            <th style="width:80px;">Qtd</th>
                            <th style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                    </tbody>
                </table>
            </div>
            <div class="p-3 text-center text-muted" id="emptyItemsMsg">
                <i class="bi bi-inbox"></i> Clique em "Adicionar Item" para começar
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
    <div class="d-flex justify-content-between">
        <a href="/admin/orders" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-send"></i> Criar Pedido e Enviar para Cotação
        </button>
    </div>
</form>

<!-- Modal Novo Fornecedor -->
<div class="modal fade" id="newSupplierModal" tabindex="-1">
    <div class="modal-dialog">
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
                <button type="button" class="btn btn-primary" id="saveSupplierBtn">Salvar Fornecedor</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Novo Material (inline) -->
<div class="modal fade" id="newMaterialModal" tabindex="-1">
    <div class="modal-dialog">
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
                    <select class="form-select" id="newMatSpec">
                        <option value="">-- Selecione --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['name']) ?>" data-id="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Classificação</label>
                    <input type="text" class="form-control" id="newMatClassification" placeholder="Ex: 100mm, 3/4&quot;, 50x40">
                </div>
                <div class="mb-3">
                    <label class="form-label">Unidade de Medida</label>
                    <select class="form-select" id="newMatUnit">
                        <option value="">-- Selecione --</option>
                        <?php foreach ($units as $u): ?>
                        <option value="<?= $u['id'] ?>" data-abbr="<?= htmlspecialchars($u['abbreviation']) ?>"><?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['abbreviation']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveMaterialBtn">Salvar Material</button>
            </div>
        </div>
    </div>
</div>

<script>
const materials = <?= json_encode($materials) ?>;
const categories = <?= json_encode($categories) ?>;
const units = <?= json_encode($units) ?>;
let itemCount = 0;

// Adicionar item
document.getElementById('addItemBtn').addEventListener('click', addItem);

function addItem(prefill = null) {
    itemCount++;
    document.getElementById('emptyItemsMsg').style.display = 'none';
    
    const row = document.createElement('tr');
    row.innerHTML = `
        <td>
            <div class="input-group input-group-sm">
                <input type="text" class="form-control material-search" 
                    name="items[${itemCount}][material_name]" 
                    placeholder="Buscar material..." 
                    list="materialsList${itemCount}" 
                    value="${prefill ? prefill.name : ''}" required autocomplete="off">
                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#newMaterialModal" title="Novo material">
                    <i class="bi bi-plus"></i>
                </button>
            </div>
            <input type="hidden" name="items[${itemCount}][material_id]" class="material-id" value="${prefill ? (prefill.id || '') : ''}">
            <datalist id="materialsList${itemCount}">
                ${materials.map(m => `<option value="${m.name} ${m.classification ? '- ' + m.classification : ''}" data-id="${m.id}" data-spec="${m.specification || ''}" data-class="${m.classification || ''}" data-unit="${m.unit_abbr || m.unit_name || ''}">`).join('')}
            </datalist>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm" name="items[${itemCount}][specification]" placeholder="Tipo" value="${prefill ? (prefill.specification || '') : ''}">
        </td>
        <td>
            <input type="text" class="form-control form-control-sm" name="items[${itemCount}][classification]" placeholder="Ex: 100mm" value="${prefill ? (prefill.classification || '') : ''}">
        </td>
        <td>
            <input type="text" class="form-control form-control-sm" name="items[${itemCount}][unit]" placeholder="unid/mts" value="${prefill ? (prefill.unit || '') : ''}">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm" name="items[${itemCount}][quantity]" min="0.01" step="0.01" value="${prefill ? (prefill.quantity || 1) : 1}" required>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger remove-item-btn" title="Remover">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    
    document.getElementById('itemsBody').appendChild(row);

    // Material search auto-fill
    const searchInput = row.querySelector('.material-search');
    searchInput.addEventListener('change', function() {
        const val = this.value;
        const match = materials.find(m => `${m.name} ${m.classification ? '- ' + m.classification : ''}` === val || m.name === val);
        if (match) {
            row.querySelector('.material-id').value = match.id;
            row.querySelector('[name*="[specification]"]').value = match.specification || match.category_name || '';
            row.querySelector('[name*="[classification]"]').value = match.classification || '';
            row.querySelector('[name*="[unit]"]').value = match.unit_abbr || match.unit_name || '';
        }
    });

    // Remove item
    row.querySelector('.remove-item-btn').addEventListener('click', function() {
        row.remove();
        if (document.getElementById('itemsBody').children.length === 0) {
            document.getElementById('emptyItemsMsg').style.display = '';
        }
    });
}

// Salvar fornecedor inline
document.getElementById('saveSupplierBtn').addEventListener('click', async function() {
    const name = document.getElementById('newSupplierName').value.trim();
    if (!name) { alert('Nome é obrigatório'); return; }

    const resp = await fetch('/admin/suppliers/quick-store', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            name: name,
            cnpj: document.getElementById('newSupplierCnpj').value,
            email: document.getElementById('newSupplierEmail').value,
            phone: document.getElementById('newSupplierPhone').value,
            contact_person: document.getElementById('newSupplierContact').value,
        })
    });

    const data = await resp.json();
    if (data.success) {
        const opt = new Option(data.supplier.name, data.supplier.id, true, true);
        document.getElementById('supplierSelect').add(opt);
        bootstrap.Modal.getInstance(document.getElementById('newSupplierModal')).hide();
        // Limpar form
        document.getElementById('newSupplierName').value = '';
        document.getElementById('newSupplierCnpj').value = '';
        document.getElementById('newSupplierEmail').value = '';
        document.getElementById('newSupplierPhone').value = '';
        document.getElementById('newSupplierContact').value = '';
    } else {
        alert(data.error || 'Erro ao salvar fornecedor');
    }
});

// Salvar material inline
document.getElementById('saveMaterialBtn').addEventListener('click', async function() {
    const name = document.getElementById('newMatName').value.trim();
    if (!name) { alert('Nome é obrigatório'); return; }

    const specSelect = document.getElementById('newMatSpec');
    const unitSelect = document.getElementById('newMatUnit');
    const catId = specSelect.selectedOptions[0]?.dataset?.id || '';
    const unitId = unitSelect.value || '';

    const resp = await fetch('/admin/materials/quick-store', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            name: name,
            specification: specSelect.value,
            category_id: catId,
            unit_id: unitId,
            classification: document.getElementById('newMatClassification').value,
        })
    });

    const data = await resp.json();
    if (data.success) {
        // Adicionar ao array local
        const unitAbbr = unitSelect.selectedOptions[0]?.dataset?.abbr || '';
        materials.push({
            id: data.material.id,
            name: data.material.name,
            specification: data.material.specification,
            classification: data.material.classification,
            unit_abbr: unitAbbr,
            unit_name: '',
        });
        
        // Adicionar como item na lista
        addItem({
            id: data.material.id,
            name: data.material.name,
            specification: data.material.specification || '',
            classification: data.material.classification || '',
            unit: unitAbbr,
            quantity: 1,
        });
        
        bootstrap.Modal.getInstance(document.getElementById('newMaterialModal')).hide();
        document.getElementById('newMatName').value = '';
        document.getElementById('newMatSpec').value = '';
        document.getElementById('newMatClassification').value = '';
        document.getElementById('newMatUnit').value = '';
    } else {
        alert(data.error || 'Erro ao salvar material');
    }
});

// Validação do form
document.getElementById('orderForm').addEventListener('submit', function(e) {
    if (document.getElementById('itemsBody').children.length === 0) {
        e.preventDefault();
        alert('Adicione pelo menos um item ao pedido.');
    }
});
</script>

<style>
@media (max-width: 768px) {
    #itemsTable { font-size: 0.8rem; }
    #itemsTable th, #itemsTable td { padding: 0.4rem; }
}
</style>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
