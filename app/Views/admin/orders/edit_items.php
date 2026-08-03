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
            <span><i class="bi bi-list-check"></i> Itens do Pedido <span class="badge bg-primary ms-1" id="itemCountBadge">0</span></span>
            <button type="button" class="btn btn-sm btn-primary" id="addItemBtn">
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
        <button type="submit" class="btn btn-primary" onclick="return confirm('Confirma a edição dos itens? Notificações serão reenviadas.')">
            <i class="bi bi-check-lg"></i> Salvar Alterações e Reenviar
        </button>
    </div>
</form>

<script src="/assets/js/searchable-select.js"></script>
<script>
const materials = <?= json_encode($materials) ?>;
let itemCount = 0;

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
</script>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
