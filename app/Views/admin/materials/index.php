<?php $pageTitle = 'Materiais'; $currentPage = 'materials'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <span class="badge bg-secondary"><?= count($materials) ?> materiais</span>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newMaterialModal">
        <i class="bi bi-plus-lg"></i> Novo Material
    </button>
</div>

<!-- Busca -->
<div class="card mb-3">
    <div class="card-body py-2">
        <input type="text" class="form-control" id="searchInput" placeholder="Buscar materiais...">
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0" id="materialsTable">
            <thead>
                <tr>
                    <th>Material</th>
                    <th>Especificação</th>
                    <th>Classificação</th>
                    <th>Unid. Medida</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($materials)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">Nenhum material cadastrado.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($materials as $m): ?>
                <tr class="<?= !$m['active'] ? 'opacity-50' : '' ?> material-row">
                    <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
                    <td><?= htmlspecialchars($m['specification'] ?? $m['category_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($m['classification'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($m['unit_name'] ?? '-') ?> <?= $m['unit_abbr'] ? '(' . $m['unit_abbr'] . ')' : '' ?></td>
                    <td>
                        <?= $m['active'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>' ?>
                    </td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary edit-material-btn" 
                            data-id="<?= $m['id'] ?>" 
                            data-name="<?= htmlspecialchars($m['name']) ?>"
                            data-specification="<?= htmlspecialchars($m['specification'] ?? '') ?>"
                            data-category-id="<?= $m['category_id'] ?? '' ?>"
                            data-unit-id="<?= $m['unit_id'] ?? '' ?>"
                            data-classification="<?= htmlspecialchars($m['classification'] ?? '') ?>"
                            title="Editar">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <?php if ($m['active']): ?>
                        <form method="POST" action="/admin/materials/delete" class="d-inline" onsubmit="return confirm('Desativar este material?')">
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Desativar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Novo Material -->
<div class="modal fade" id="newMaterialModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/admin/materials/store" id="materialForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="materialModalTitle">Novo Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="matId">
                    <div class="mb-3">
                        <label class="form-label">Nome do Material *</label>
                        <input type="text" class="form-control" name="name" id="matName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Especificação (Tipo)</label>
                        <select class="form-select" name="category_id" id="matCategoryId">
                            <option value="">-- Selecione --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="specification" id="matSpecification">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Classificação</label>
                        <input type="text" class="form-control" name="classification" id="matClassification" placeholder="Ex: 100mm, 3/4&quot;, 50x40, 500L">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unidade de Medida</label>
                        <select class="form-select" name="unit_id" id="matUnitId">
                            <option value="">-- Selecione --</option>
                            <?php foreach ($units as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['abbreviation']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="matSubmitBtn">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Busca
document.getElementById('searchInput').addEventListener('input', function() {
    const val = this.value.toLowerCase();
    document.querySelectorAll('.material-row').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
    });
});

// Editar material
document.querySelectorAll('.edit-material-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('materialModalTitle').textContent = 'Editar Material';
        document.getElementById('matSubmitBtn').textContent = 'Atualizar';
        document.getElementById('materialForm').action = '/admin/materials/update';
        document.getElementById('matId').value = this.dataset.id;
        document.getElementById('matName').value = this.dataset.name;
        document.getElementById('matCategoryId').value = this.dataset.categoryId;
        document.getElementById('matSpecification').value = this.dataset.specification;
        document.getElementById('matClassification').value = this.dataset.classification;
        document.getElementById('matUnitId').value = this.dataset.unitId;
        new bootstrap.Modal(document.getElementById('newMaterialModal')).show();
    });
});

// Reset modal on close
document.getElementById('newMaterialModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('materialModalTitle').textContent = 'Novo Material';
    document.getElementById('matSubmitBtn').textContent = 'Cadastrar';
    document.getElementById('materialForm').action = '/admin/materials/store';
    document.getElementById('materialForm').reset();
    document.getElementById('matId').value = '';
});

// Sync category name to specification hidden field
document.getElementById('matCategoryId').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    document.getElementById('matSpecification').value = selected.textContent.trim() !== '-- Selecione --' ? selected.textContent.trim() : '';
});
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
