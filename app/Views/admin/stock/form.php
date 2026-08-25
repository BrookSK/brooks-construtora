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

                        <!-- Imagem/Foto do item de estoque (opcional, propria do estoque) -->
                        <div class="col-12">
                            <label class="form-label">Imagem do Produto <span class="text-muted small">(opcional)</span></label>
                            <div class="d-flex align-items-center gap-3">
                                <img id="stockImagePreview" src="<?= $isEdit && !empty($item['image_path']) ? htmlspecialchars($item['image_path']) : '' ?>" alt="" class="rounded border"
                                     style="width:72px;height:72px;object-fit:cover;<?= $isEdit && !empty($item['image_path']) ? '' : 'display:none;' ?>">
                                <div id="stockImagePlaceholder" class="rounded border d-flex align-items-center justify-content-center text-muted"
                                     style="width:72px;height:72px;background:#f8f9fa;<?= $isEdit && !empty($item['image_path']) ? 'display:none;' : '' ?>">
                                    <i class="bi bi-image" style="font-size:1.5rem;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="stockPickImageBtn">
                                            <i class="bi bi-upload"></i> Selecionar
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" id="stockCameraBtn">
                                            <i class="bi bi-camera"></i> Câmera
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm" id="stockRemoveImageBtn" style="<?= $isEdit && !empty($item['image_path']) ? '' : 'display:none;' ?>">
                                            <i class="bi bi-trash"></i> Remover
                                        </button>
                                    </div>
                                    <input type="file" accept="image/*" id="stockImageFile" class="d-none">
                                    <input type="file" accept="image/*" capture="environment" id="stockImageCamera" class="d-none">
                                    <div class="small text-muted mt-1" id="stockImageHint">
                                        <?= $isEdit ? 'Selecione uma imagem ou use a câmera. Máx. 5 MB (JPG, PNG, WEBP, GIF).' : 'Salve o item para habilitar o envio da imagem.' ?>
                                    </div>
                                </div>
                            </div>
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

// ===== Imagem do item de estoque (upload / camera / remover) =====
(function () {
    const stockItemId   = <?= $isEdit ? (int) $item['id'] : 0 ?>;
    const previewEl     = document.getElementById('stockImagePreview');
    const placeholderEl = document.getElementById('stockImagePlaceholder');
    const hintEl        = document.getElementById('stockImageHint');
    const pickBtn       = document.getElementById('stockPickImageBtn');
    const cameraBtn     = document.getElementById('stockCameraBtn');
    const removeBtn     = document.getElementById('stockRemoveImageBtn');
    const fileInput     = document.getElementById('stockImageFile');
    const cameraInput   = document.getElementById('stockImageCamera');

    function showImage(url) {
        if (url) {
            previewEl.src = url;
            previewEl.style.display = '';
            placeholderEl.style.display = 'none';
            removeBtn.style.display = '';
        } else {
            previewEl.src = '';
            previewEl.style.display = 'none';
            placeholderEl.style.display = '';
            removeBtn.style.display = 'none';
        }
    }

    // Upload so e possivel com item ja salvo (precisa do id).
    const hasId = stockItemId > 0;
    pickBtn.disabled = !hasId;
    cameraBtn.disabled = !hasId;

    // Esconde o botao de camera quando claramente indisponivel.
    const hasCameraSupport = 'mediaDevices' in navigator
        || /Mobi|Android|iPhone|iPad/i.test(navigator.userAgent);
    if (!hasCameraSupport) {
        cameraBtn.style.display = 'none';
    }

    pickBtn.addEventListener('click', () => { if (!pickBtn.disabled) fileInput.click(); });
    cameraBtn.addEventListener('click', () => { if (!cameraBtn.disabled) cameraInput.click(); });

    async function doUpload(file) {
        if (!hasId) { alert('Salve o item primeiro.'); return; }
        if (!file) return;
        if (file.size > 5 * 1024 * 1024) { alert('Imagem muito grande. Máximo 5 MB.'); return; }

        const fd = new FormData();
        fd.append('id', stockItemId);
        fd.append('image', file);

        hintEl.textContent = 'Enviando imagem...';
        try {
            const resp = await fetch('/admin/stock/upload-image', { method: 'POST', body: fd });
            const data = await resp.json();
            if (data.success && data.url) {
                showImage(data.url + '?t=' + Date.now());
                hintEl.textContent = 'Imagem enviada com sucesso.';
            } else {
                hintEl.textContent = data.error || 'Erro ao enviar imagem.';
            }
        } catch (e) {
            hintEl.textContent = 'Erro de rede ao enviar imagem.';
        }
    }

    fileInput.addEventListener('change', function () { doUpload(this.files[0]); this.value = ''; });
    cameraInput.addEventListener('change', function () { doUpload(this.files[0]); this.value = ''; });

    removeBtn.addEventListener('click', async function () {
        if (!hasId) return;
        if (!confirm('Remover a imagem deste item?')) return;
        try {
            const resp = await fetch('/admin/stock/remove-image', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ id: stockItemId }),
            });
            const data = await resp.json();
            if (data.success) {
                showImage('');
                hintEl.textContent = 'Imagem removida.';
            } else {
                hintEl.textContent = data.error || 'Erro ao remover imagem.';
            }
        } catch (e) {
            hintEl.textContent = 'Erro de rede ao remover imagem.';
        }
    });
})();
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/Views/admin/layouts/app.php';
?>
