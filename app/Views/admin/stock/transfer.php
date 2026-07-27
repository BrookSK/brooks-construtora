<?php
$currentPage = 'stock';
ob_start();
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-arrow-left-right"></i> Transferir Estoque entre Obras</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/stock/process-transfer" id="transferForm">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Material *</label>
                            <select name="material_id" id="transferMaterial" required style="display:none;">
                                <option value="">Selecione o material...</option>
                                <?php foreach ($materials as $mat): ?>
                                    <option value="<?= $mat['id'] ?>" data-name="<?= htmlspecialchars($mat['name']) ?>" data-unit="<?= htmlspecialchars($mat['unit_abbr'] ?? '') ?>"><?= htmlspecialchars($mat['name']) ?><?= !empty($mat['unit_abbr']) ? ' (' . $mat['unit_abbr'] . ')' : '' ?><?= !empty($mat['specification'] ?? $mat['category_name'] ?? '') ? ' - ' . htmlspecialchars($mat['specification'] ?? $mat['category_name'] ?? '') : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Estoque de Origem *</label>
                            <select name="from_location_id" id="fromSite" class="form-select" required>
                                <option value="">De onde sai...</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?><?= !empty($loc['construction_site_name']) ? ' (' . $loc['construction_site_name'] . ')' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted" id="fromStockInfo"></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Estoque de Destino *</label>
                            <select name="to_location_id" id="toSite" class="form-select" required>
                                <option value="">Para onde vai...</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?= $loc['id'] ?>"><?= htmlspecialchars($loc['name']) ?><?= !empty($loc['construction_site_name']) ? ' (' . $loc['construction_site_name'] . ')' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Quantidade *</label>
                            <input type="text" name="quantity" class="form-control" required placeholder="0">
                        </div>

                        <div class="col-12">
                            <div class="alert alert-info small mb-0">
                                <i class="bi bi-info-circle"></i>
                                Ao confirmar, o responsável pelo transporte será notificado automaticamente.
                                A movimentação ficará pendente até a entrega ser confirmada.
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="/admin/stock" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Voltar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-arrow-left-right"></i> Solicitar Transferência
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
document.addEventListener('DOMContentLoaded', function() {
    const materialSelect = document.getElementById('transferMaterial');
    const fromSiteSelect = document.getElementById('fromSite');
    const fromStockInfo = document.getElementById('fromStockInfo');

    new SearchableSelect(materialSelect, {
        placeholder: 'Buscar material...',
        onSelect: function() { checkAvailableStock(); }
    });

    function checkAvailableStock() {
        const materialId = materialSelect.value;
        const fromSiteId = fromSiteSelect.value;
        
        if (!materialId || !fromSiteId) {
            fromStockInfo.textContent = '';
            return;
        }

        fetch(`/admin/stock/search-stock?material_id=${materialId}&exclude_site_id=0`)
            .then(r => r.json())
            .then(data => {
                const stock = data.stocks.find(s => s.construction_site_id == fromSiteId);
                if (stock) {
                    fromStockInfo.textContent = `Disponível: ${stock.quantity} ${stock.unit_abbr || ''}`;
                    fromStockInfo.className = 'text-success small';
                } else {
                    fromStockInfo.textContent = 'Sem estoque nesta obra';
                    fromStockInfo.className = 'text-danger small';
                }
            });
    }

    fromSiteSelect.addEventListener('change', checkAvailableStock);
});
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/Views/admin/layouts/app.php';
?>
