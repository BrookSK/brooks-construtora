<?php $pageTitle = 'Materiais'; $currentPage = 'materials'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="badge bg-secondary"><?= count($materials) ?> materiais</span>
    <div class="d-flex gap-2">
        <a href="/admin/materials/import" class="btn btn-outline-success btn-sm">
            <i class="bi bi-upload"></i> <span class="d-none d-sm-inline">Importar</span>
        </a>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newMaterialModal">
            <i class="bi bi-plus-lg"></i> Novo Material
        </button>
    </div>
</div>

<!-- Busca -->
<div class="card mb-3">
    <div class="card-body py-2">
        <input type="text" class="form-control" id="searchInput" placeholder="Buscar materiais...">
    </div>
</div>

<!-- Desktop -->
<div class="card d-none d-md-block">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
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
                <tr><td colspan="6" class="text-center text-muted py-4">Nenhum material cadastrado.</td></tr>
                <?php else: ?>
                <?php foreach ($materials as $m): ?>
                <tr class="<?= !$m['active'] ? 'opacity-50' : '' ?> material-row">
                    <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
                    <td><?= htmlspecialchars($m['specification'] ?? $m['category_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($m['classification'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($m['unit_name'] ?? '-') ?> <?= $m['unit_abbr'] ? '(' . $m['unit_abbr'] . ')' : '' ?></td>
                    <td><?= $m['active'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>' ?></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary edit-material-btn" data-id="<?= $m['id'] ?>" data-name="<?= htmlspecialchars($m['name']) ?>" data-code="<?= htmlspecialchars($m['code'] ?? '') ?>" data-specification="<?= htmlspecialchars($m['specification'] ?? '') ?>" data-category-id="<?= $m['category_id'] ?? '' ?>" data-unit-id="<?= $m['unit_id'] ?? '' ?>" data-classification="<?= htmlspecialchars($m['classification'] ?? '') ?>"><i class="bi bi-pencil"></i></button>
                        <?php if ($m['active']): ?>
                        <form method="POST" action="/admin/materials/delete" class="d-inline" onsubmit="return confirm('Desativar?')"><input type="hidden" name="id" value="<?= $m['id'] ?>"><button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                        <?php elseif (\App\Core\Auth::isSuperAdmin()): ?>
                        <form method="POST" action="/admin/materials/delete" class="d-inline" onsubmit="return confirm('EXCLUIR permanentemente este material?')"><input type="hidden" name="id" value="<?= $m['id'] ?>"><input type="hidden" name="action" value="permanent"><button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button></form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Mobile -->
<div class="d-md-none">
    <?php if (empty($materials)): ?>
    <div class="card"><div class="card-body text-center text-muted py-4">Nenhum material cadastrado.</div></div>
    <?php else: ?>
    <?php foreach ($materials as $m): ?>
    <div class="card mb-2 material-row <?= !$m['active'] ? 'opacity-50' : '' ?>">
        <div class="card-body py-2 px-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong style="font-size:0.9rem;"><?= htmlspecialchars($m['name']) ?></strong>
                    <div class="d-flex flex-wrap gap-1 mt-1">
                        <?php if ($m['specification'] ?? $m['category_name'] ?? null): ?><span class="badge bg-light text-dark" style="font-size:0.65rem;"><?= htmlspecialchars($m['specification'] ?? $m['category_name']) ?></span><?php endif; ?>
                        <?php if ($m['classification']): ?><span class="badge bg-light text-dark" style="font-size:0.65rem;"><?= htmlspecialchars($m['classification']) ?></span><?php endif; ?>
                        <?php if ($m['unit_abbr']): ?><span class="badge bg-info text-white" style="font-size:0.65rem;"><?= htmlspecialchars($m['unit_abbr']) ?></span><?php endif; ?>
                    </div>
                </div>
                <div class="d-flex gap-1">
                    <button class="btn btn-sm btn-outline-primary edit-material-btn" data-id="<?= $m['id'] ?>" data-name="<?= htmlspecialchars($m['name']) ?>" data-code="<?= htmlspecialchars($m['code'] ?? '') ?>" data-specification="<?= htmlspecialchars($m['specification'] ?? '') ?>" data-category-id="<?= $m['category_id'] ?? '' ?>" data-unit-id="<?= $m['unit_id'] ?? '' ?>" data-classification="<?= htmlspecialchars($m['classification'] ?? '') ?>"><i class="bi bi-pencil"></i></button>
                    <?php if ($m['active']): ?><form method="POST" action="/admin/materials/delete" onsubmit="return confirm('Desativar?')"><input type="hidden" name="id" value="<?= $m['id'] ?>"><button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                    <?php elseif (\App\Core\Auth::isSuperAdmin()): ?><form method="POST" action="/admin/materials/delete" onsubmit="return confirm('EXCLUIR permanentemente?')"><input type="hidden" name="id" value="<?= $m['id'] ?>"><input type="hidden" name="action" value="permanent"><button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button></form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal Novo Material (com steps internos - sem modal sobre modal) -->
<div class="modal fade" id="newMaterialModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <!-- Step 1: Formulário principal -->
            <div id="matStep1">
                <form method="POST" action="/admin/materials/store" id="materialForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="materialModalTitle">Novo Material</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="matId">
                        <div class="row g-2 mb-3">
                            <div class="col-8">
                                <label class="form-label">Nome do Material *</label>
                                <input type="text" class="form-control" name="name" id="matName" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label">Código</label>
                                <input type="text" class="form-control" name="code" id="matCode" placeholder="Ex: 11270">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Especificação (Tipo)</label>
                            <div class="input-group">
                                <select class="form-select" name="category_id" id="matCategoryId">
                                    <option value="">-- Selecione --</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-outline-primary" onclick="showQuickAddMat('category')"><i class="bi bi-plus"></i></button>
                            </div>
                            <input type="hidden" name="specification" id="matSpecification">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Classificação</label>
                            <input type="text" class="form-control" name="classification" id="matClassification" placeholder="Ex: 100mm, 3/4&quot;, 50x40, 500L">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Unidade de Medida</label>
                            <div class="input-group">
                                <select class="form-select" name="unit_id" id="matUnitId">
                                    <option value="">-- Selecione --</option>
                                    <?php foreach ($units as $u): ?>
                                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['abbreviation']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-outline-primary" onclick="showQuickAddMat('unit')"><i class="bi bi-plus"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="matSubmitBtn">Cadastrar</button>
                    </div>
                </form>
            </div>

            <!-- Step 2: Cadastro rápido -->
            <div id="matStep2" style="display:none;">
                <div class="modal-header">
                    <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="backToMatStep1()"><i class="bi bi-arrow-left"></i></button>
                    <h6 class="modal-title" id="quickAddMatTitle">Nova Especificação</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="quickCatMatFields">
                        <label class="form-label">Nome da Especificação *</label>
                        <input type="text" class="form-control" id="quickCatMatName" placeholder="Ex: mat. Elétrico">
                    </div>
                    <div id="quickUnitMatFields" style="display:none;">
                        <div class="mb-3">
                            <label class="form-label">Nome *</label>
                            <input type="text" class="form-control" id="quickUnitMatName" placeholder="Ex: Galão">
                        </div>
                        <div>
                            <label class="form-label">Abreviação *</label>
                            <input type="text" class="form-control" id="quickUnitMatAbbr" placeholder="Ex: gal">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="backToMatStep1()">Voltar</button>
                    <button type="button" class="btn btn-primary" onclick="saveQuickAddMat()">Salvar</button>
                </div>
            </div>
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
        document.getElementById('matCode').value = this.dataset.code || '';
        document.getElementById('matCategoryId').value = this.dataset.categoryId;
        document.getElementById('matSpecification').value = this.dataset.specification;
        document.getElementById('matClassification').value = this.dataset.classification;
        document.getElementById('matUnitId').value = this.dataset.unitId;
        new bootstrap.Modal(document.getElementById('newMaterialModal')).show();
    });
});

// Reset modal + voltar pro step 1 ao fechar
document.getElementById('newMaterialModal').addEventListener('hidden.bs.modal', function() {
    backToMatStep1();
    document.getElementById('materialModalTitle').textContent = 'Novo Material';
    document.getElementById('matSubmitBtn').textContent = 'Cadastrar';
    document.getElementById('materialForm').action = '/admin/materials/store';
    document.getElementById('materialForm').reset();
    document.getElementById('matId').value = '';
});

// Sync category name
document.getElementById('matCategoryId').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    document.getElementById('matSpecification').value = selected.value ? selected.textContent.trim() : '';
});

// Step system
let quickAddMatMode = '';

function showQuickAddMat(mode) {
    quickAddMatMode = mode;
    document.getElementById('matStep1').style.display = 'none';
    document.getElementById('matStep2').style.display = 'block';
    if (mode === 'category') {
        document.getElementById('quickAddMatTitle').textContent = 'Nova Especificação';
        document.getElementById('quickCatMatFields').style.display = '';
        document.getElementById('quickUnitMatFields').style.display = 'none';
        setTimeout(() => document.getElementById('quickCatMatName').focus(), 100);
    } else {
        document.getElementById('quickAddMatTitle').textContent = 'Nova Unidade de Medida';
        document.getElementById('quickCatMatFields').style.display = 'none';
        document.getElementById('quickUnitMatFields').style.display = '';
        setTimeout(() => document.getElementById('quickUnitMatName').focus(), 100);
    }
}

function backToMatStep1() {
    document.getElementById('matStep2').style.display = 'none';
    document.getElementById('matStep1').style.display = 'block';
}

async function saveQuickAddMat() {
    if (quickAddMatMode === 'category') {
        const name = document.getElementById('quickCatMatName').value.trim();
        if (!name) { alert('Nome é obrigatório'); return; }
        const resp = await fetch('/admin/materials/quick-store-category', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: new URLSearchParams({ name }) });
        const data = await resp.json();
        if (data.success) {
            const opt = new Option(data.category.name, data.category.id);
            document.getElementById('matCategoryId').add(opt);
            document.getElementById('matCategoryId').value = data.category.id;
            document.getElementById('matSpecification').value = data.category.name;
            document.getElementById('quickCatMatName').value = '';
            backToMatStep1();
        } else { alert(data.error || 'Erro'); }
    } else {
        const name = document.getElementById('quickUnitMatName').value.trim();
        const abbr = document.getElementById('quickUnitMatAbbr').value.trim();
        if (!name || !abbr) { alert('Nome e abreviação são obrigatórios'); return; }
        const resp = await fetch('/admin/materials/quick-store-unit', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: new URLSearchParams({ name, abbreviation: abbr }) });
        const data = await resp.json();
        if (data.success) {
            const opt = new Option(`${data.unit.name} (${data.unit.abbreviation})`, data.unit.id);
            document.getElementById('matUnitId').add(opt);
            document.getElementById('matUnitId').value = data.unit.id;
            document.getElementById('quickUnitMatName').value = '';
            document.getElementById('quickUnitMatAbbr').value = '';
            backToMatStep1();
        } else { alert(data.error || 'Erro'); }
    }
}
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
