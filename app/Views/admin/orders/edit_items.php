<?php $pageTitle = 'Editar Itens - ' . $order['code']; $currentPage = 'orders'; ?>
<?php ob_start(); ?>

<link rel="stylesheet" href="/assets/css/searchable-select.css">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Editar Itens do Pedido <?= htmlspecialchars($order['code']) ?></h5>
    <a href="/admin/orders/show/<?= $order['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
</div>

<?php if (!empty($order['construction_site_name'])): ?>
<div class="alert alert-light py-2 mb-3">
    <i class="bi bi-buildings"></i> <strong>Obra:</strong> <?= htmlspecialchars($order['construction_site_code'] . ' - ' . $order['construction_site_name']) ?>
</div>
<?php endif; ?>

<div class="alert alert-warning py-2 small">
    <i class="bi bi-exclamation-triangle"></i> <strong>Atenção:</strong> Ao salvar, os itens anteriores serão substituídos e movimentações de estoque vinculadas serão canceladas. Notificações serão reenviadas para cotação.
</div>

<form method="POST" action="/admin/orders/update-items" id="editItemsForm">
    <input type="hidden" name="id" value="<?= $order['id'] ?>">

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list-check"></i> Itens do Pedido <span class="badge bg-primary ms-1" id="itemCountBadge"><?= count($items) ?></span></span>
            <button type="button" class="btn btn-sm btn-primary" id="addItemBtn" onclick="addItem()">
                <i class="bi bi-plus"></i> Adicionar Item
            </button>
        </div>
        <div class="card-body p-0">
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
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between">
        <a href="/admin/orders/show/<?= $order['id'] ?>" class="btn btn-outline-secondary">
            <i class="bi bi-x"></i> Cancelar
        </a>
        <button type="submit" class="btn btn-primary" onclick="return confirm('Confirma a edição dos itens? Notificações serão reenviadas.')">
            <i class="bi bi-check-lg"></i> Salvar Alterações e Reenviar
        </button>
    </div>
</form>

<script>
const materials = <?= json_encode(array_map(function($m) {
    return [
        'id' => $m['id'],
        'name' => $m['name'],
        'specification' => $m['specification'] ?? $m['category_name'] ?? '',
        'classification' => $m['classification'] ?? '',
        'unit_abbr' => $m['unit_abbr'] ?? '',
        'unit_name' => $m['unit_name'] ?? '',
    ];
}, $materials)) ?>;

let itemIndex = 0;

function buildMaterialOptions(prefill) {
    let opts = '<option value="">-- Selecione ou digite --</option>';
    materials.forEach(m => {
        const label = m.name + (m.classification ? ' - ' + m.classification : '') + (m.specification ? ' (' + m.specification + ')' : '');
        const selected = prefill && prefill.id == m.id ? 'selected' : '';
        opts += `<option value="${m.id}" data-name="${m.name}" data-spec="${m.specification || ''}" data-class="${m.classification || ''}" data-unit="${m.unit_abbr || m.unit_name || ''}" ${selected}>${label}</option>`;
    });
    return opts;
}

function addItem(data) {
    const idx = itemIndex++;
    const name = data ? data.material_name : '';
    const spec = data ? (data.specification || '') : '';
    const cls = data ? (data.classification || '') : '';
    const unit = data ? (data.unit || '') : '';
    const qty = data ? data.quantity : 1;
    const matId = data ? (data.material_id || '') : '';

    const row = document.createElement('tr');
    row.id = `item-row-${idx}`;
    row.innerHTML = `
        <td>
            <select class="form-select form-select-sm material-select" id="mat-${idx}" onchange="onMaterialChange(${idx})">
                ${buildMaterialOptions(matId ? {id: matId} : null)}
            </select>
            <input type="hidden" name="items[${idx}][material_id]" id="matid-${idx}" value="${matId}">
            <input type="hidden" name="items[${idx}][material_name]" id="mname-${idx}" value="${name}">
            <input type="hidden" name="items[${idx}][unit]" id="unit-${idx}" value="${unit}">
        </td>
        <td><input type="text" class="form-control form-control-sm" name="items[${idx}][specification]" id="spec-${idx}" value="${spec}"></td>
        <td><input type="text" class="form-control form-control-sm" name="items[${idx}][classification]" id="class-${idx}" value="${cls}"></td>
        <td><input type="number" class="form-control form-control-sm" name="items[${idx}][quantity]" value="${qty}" min="0.01" step="0.01" required></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(${idx})"><i class="bi bi-trash"></i></button></td>
    `;
    document.getElementById('itemsBody').appendChild(row);
    updateCount();

    // Se não tem material_id, colocar nome manual
    if (!matId && name) {
        const select = document.getElementById(`mat-${idx}`);
        // Tentar encontrar pelo nome
        let found = false;
        for (let opt of select.options) {
            if (opt.dataset.name === name) {
                opt.selected = true;
                found = true;
                break;
            }
        }
        if (!found) {
            // Adicionar como opção custom
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = name + ' (não cadastrado)';
            opt.selected = true;
            select.appendChild(opt);
        }
    }

    // Inicializar searchable select
    if (typeof initSearchableSelect === 'function') {
        initSearchableSelect(document.getElementById(`mat-${idx}`));
    }
}

function removeItem(idx) {
    const row = document.getElementById(`item-row-${idx}`);
    if (row) row.remove();
    updateCount();
}

function onMaterialChange(idx) {
    const select = document.getElementById(`mat-${idx}`);
    const opt = select.selectedOptions[0];
    if (opt && opt.value) {
        document.getElementById(`matid-${idx}`).value = opt.value;
        document.getElementById(`mname-${idx}`).value = opt.dataset.name || '';
        document.getElementById(`spec-${idx}`).value = opt.dataset.spec || '';
        document.getElementById(`class-${idx}`).value = opt.dataset.class || '';
        document.getElementById(`unit-${idx}`).value = opt.dataset.unit || '';
    }
}

function updateCount() {
    const count = document.querySelectorAll('#itemsBody tr').length;
    document.getElementById('itemCountBadge').textContent = count;
}

// Carregar itens existentes
document.addEventListener('DOMContentLoaded', function() {
    const existingItems = <?= json_encode(array_map(function($item) {
        return [
            'material_id' => $item['material_id'],
            'material_name' => $item['material_name'],
            'specification' => $item['specification'] ?? '',
            'classification' => $item['classification'] ?? '',
            'unit' => $item['unit'] ?? '',
            'quantity' => (float) $item['quantity'],
            'source_type' => $item['source_type'] ?? null,
        ];
    }, $items)) ?>;

    // Só carregar itens que são de compra/cotação (ignorar os de estoque puro)
    existingItems.forEach(item => {
        // Incluir todos os itens para que o solicitante veja tudo e possa decidir
        addItem(item);
    });
});
</script>

<?php if (file_exists(ROOT_PATH . '/assets/js/searchable-select.js')): ?>
<script src="/assets/js/searchable-select.js"></script>
<?php endif; ?>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
