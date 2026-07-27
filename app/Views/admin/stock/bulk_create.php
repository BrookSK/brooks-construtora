<?php
$currentPage = 'stock';
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-collection"></i> Cadastro em Massa - Estoque</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/stock/bulk-store" id="bulkForm">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Estoque/Depósito *</label>
                            <select name="stock_location_id" class="form-select" required>
                                <option value="">Selecione o estoque...</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?><?= !empty($loc['construction_site_name']) ? ' (' . $loc['construction_site_name'] . ')' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-primary" onclick="addItem()">
                                <i class="bi bi-plus-lg"></i> Adicionar Material
                            </button>
                        </div>
                    </div>

                    <div id="itemsContainer">
                        <div class="row g-2 mb-2 item-row align-items-end">
                            <div class="col-md-7">
                                <label class="form-label small">Material</label>
                                <select name="stock_items[0][material_id]" class="form-select form-select-sm" required style="display:none;">
                                    <option value="">Selecione...</option>
                                    <?php foreach ($materials as $mat): ?>
                                        <option value="<?= $mat['id'] ?>">
                                            <?= htmlspecialchars($mat['name']) ?>
                                            <?= !empty($mat['unit_abbr']) ? ' (' . $mat['unit_abbr'] . ')' : '' ?>
                                            <?= !empty($mat['specification'] ?? $mat['category_name'] ?? '') ? ' - ' . htmlspecialchars($mat['specification'] ?? $mat['category_name'] ?? '') : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Quantidade</label>
                                <input type="text" name="stock_items[0][quantity]" class="form-control form-control-sm" placeholder="0" required>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(this)" title="Remover">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="/admin/stock" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Voltar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> Cadastrar Todos
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="/assets/css/searchable-select.css">
<script src="/assets/js/searchable-select.js"></script>
<script>
let itemIndex = 1;

function initMaterialSearch(selectEl) {
    new SearchableSelect(selectEl, {
        placeholder: 'Buscar material...'
    });
}

// Inicializar o primeiro
document.addEventListener('DOMContentLoaded', function() {
    const firstSelect = document.querySelector('.item-row select');
    if (firstSelect) initMaterialSearch(firstSelect);
});

function addItem() {
    const container = document.getElementById('itemsContainer');
    const firstSelect = container.querySelector('select');
    const materialsOptions = firstSelect.innerHTML;
    
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 item-row align-items-end';
    row.innerHTML = `
        <div class="col-md-7">
            <select name="stock_items[${itemIndex}][material_id]" class="form-select form-select-sm" required style="display:none;">
                ${materialsOptions}
            </select>
        </div>
        <div class="col-md-3">
            <input type="text" name="stock_items[${itemIndex}][quantity]" class="form-control form-control-sm" placeholder="0" required>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(this)" title="Remover">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(row);
    
    // Inicializar SearchableSelect no novo select
    const newSelect = row.querySelector('select');
    initMaterialSearch(newSelect);
    
    itemIndex++;
}

function removeItem(btn) {
    const rows = document.querySelectorAll('.item-row');
    if (rows.length <= 1) return;
    btn.closest('.item-row').remove();
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/Views/admin/layouts/app.php';
?>
