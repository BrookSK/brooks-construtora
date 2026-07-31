<?php
$currentPage = 'stock';
$isEdit = !empty($item);
ob_start();
?>

<link rel="stylesheet" href="/assets/css/searchable-select.css">

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><?= $isEdit ? 'Editar Item do Estoque' : 'Cadastrar Item no Estoque' ?></h6>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= $isEdit ? '/admin/stock/update' : '/admin/stock/store' ?>">
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Estoque/Depósito *</label>
                            <select name="stock_location_id" class="form-select" required <?= $isEdit ? 'disabled' : '' ?>>
                                <option value="">Selecione o estoque...</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= $loc['id'] ?>" <?= ($isEdit && ($item['stock_location_id'] ?? '') == $loc['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($loc['name']) ?>
                                        <?= !empty($loc['construction_site_name']) ? ' (' . $loc['construction_site_name'] . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($isEdit): ?>
                                <input type="hidden" name="stock_location_id" value="<?= $item['stock_location_id'] ?? '' ?>">
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Material *</label>
                            <?php if ($isEdit): ?>
                                <select name="material_id_display" class="form-select" disabled>
                                    <?php foreach ($materials as $mat): ?>
                                        <option value="<?= $mat['id'] ?>" <?= $item['material_id'] == $mat['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($mat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="material_id" value="<?= $item['material_id'] ?>">
                            <?php else: ?>
                                <select name="material_id" id="materialSelect" required style="display:none;">
                                    <option value="">Selecione o material...</option>
                                    <?php foreach ($materials as $mat): ?>
                                        <option value="<?= $mat['id'] ?>"><?= htmlspecialchars($mat['name']) ?><?= !empty($mat['unit_abbr']) ? ' (' . $mat['unit_abbr'] . ')' : '' ?><?= !empty($mat['specification'] ?? $mat['category_name'] ?? '') ? ' - ' . htmlspecialchars($mat['specification'] ?? $mat['category_name']) : '' ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Quantidade *</label>
                            <input type="text" name="quantity" class="form-control" 
                                   value="<?= $isEdit ? $item['quantity'] : '' ?>" required
                                   placeholder="0">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Quantidade Mínima</label>
                            <input type="text" name="min_quantity" class="form-control" 
                                   value="<?= $isEdit ? $item['min_quantity'] : '0' ?>"
                                   placeholder="0">
                            <small class="text-muted">Alerta quando atingir esse valor</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Valor Unitário (R$)</label>
                            <input type="text" name="unit_price" class="form-control" 
                                   value="<?= $isEdit && !empty($item['unit_price']) ? number_format($item['unit_price'], 2, ',', '.') : '' ?>"
                                   placeholder="0,00" inputmode="decimal">
                            <small class="text-muted">Valor unitário do material em estoque</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Localização</label>
                            <input type="text" name="location_detail" class="form-control" 
                                   value="<?= htmlspecialchars($isEdit ? ($item['location_detail'] ?? '') : '') ?>"
                                   placeholder="Ex: Almoxarifado A, Prateleira 3">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Observações</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Observações sobre este item..."><?= htmlspecialchars($isEdit ? ($item['notes'] ?? '') : '') ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="/admin/stock" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Voltar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg"></i> <?= $isEdit ? 'Atualizar' : 'Cadastrar' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/searchable-select.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('materialSelect');
    if (select) {
        new SearchableSelect(select, {
            placeholder: 'Buscar material...'
        });
    }
});
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/Views/admin/layouts/app.php';
?>
