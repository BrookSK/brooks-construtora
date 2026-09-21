<?php $pageTitle = 'Listas de Materiais'; $currentPage = 'material_lists'; ?>
<?php ob_start(); ?>

<link rel="stylesheet" href="/assets/css/searchable-select.css">

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><i class="bi bi-list-stars"></i> Listas de Materiais Pré-definidas</h5>
        <small class="text-muted">Monte listas reutilizáveis por categoria para agilizar a criação de pedidos.</small>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#recreateListsModal" title="Apagar todas as listas e recriar a partir das categorias de materiais">
            <i class="bi bi-arrow-clockwise"></i> Recriar Listas
        </button>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newListModal">
            <i class="bi bi-plus-lg"></i> Nova Lista
        </button>
    </div>
</div>

<div class="alert alert-warning d-flex align-items-start gap-2 small">
    <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
    <div>
        <strong>Uso por conta e risco do gerente.</strong>
        As listas são apenas um ponto de partida. Ao aplicar uma lista em um pedido, revise sempre
        os itens e as quantidades — o responsável pelo pedido deve pedir só o que realmente precisa,
        evitando sobra ou itens desnecessários.
    </div>
</div>

<?php if (empty($templates)): ?>
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-inbox d-block mb-2" style="font-size:2rem;"></i>
            Nenhuma lista cadastrada ainda. Clique em <strong>Nova Lista</strong> para começar.
        </div>
    </div>
<?php else: ?>
<div class="accordion" id="listsAccordion">
    <?php foreach ($templates as $t): $tid = (int) $t['id']; $items = $itemsByTemplate[$tid] ?? []; ?>
    <div class="accordion-item" data-template-id="<?= $tid ?>">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#tpl-<?= $tid ?>">
                <span class="fw-bold"><?= htmlspecialchars($t['name']) ?></span>
                <span class="badge bg-secondary ms-2 tpl-count" data-tid="<?= $tid ?>"><?= (int) ($t['item_count'] ?? 0) ?> itens</span>
                <?php if ((int) $t['active'] !== 1): ?>
                    <span class="badge bg-danger ms-2">inativa</span>
                <?php endif; ?>
            </button>
        </h2>
        <div id="tpl-<?= $tid ?>" class="accordion-collapse collapse" data-bs-parent="#listsAccordion">
            <div class="accordion-body">
                <?php if (!empty($t['description'])): ?>
                    <p class="text-muted small mb-3"><?= htmlspecialchars($t['description']) ?></p>
                <?php endif; ?>

                <!-- Ações do cabeçalho da lista -->
                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-secondary edit-list-btn"
                            data-id="<?= $tid ?>" data-name="<?= htmlspecialchars($t['name'], ENT_QUOTES) ?>"
                            data-description="<?= htmlspecialchars($t['description'] ?? '', ENT_QUOTES) ?>">
                        <i class="bi bi-pencil"></i> Editar nome/descrição
                    </button>
                    <form method="POST" action="/admin/material-lists/delete" class="d-inline"
                          onsubmit="return confirm('Excluir a lista &quot;<?= htmlspecialchars($t['name'], ENT_QUOTES) ?>&quot; e todos os seus itens?');">
                        <input type="hidden" name="id" value="<?= $tid ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Excluir lista</button>
                    </form>
                </div>

                <!-- Adicionar item -->
                <div class="card bg-light border-0 mb-3">
                    <div class="card-body p-3">
                        <label class="form-label small fw-bold mb-1">Adicionar material à lista</label>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-7">
                                <select class="tpl-mat-select" id="mat-select-<?= $tid ?>" style="display:none;"></select>
                                <div id="mat-ss-<?= $tid ?>"></div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-0">Qtd padrão</label>
                                <input type="number" class="form-control form-control-sm tpl-qty-input" id="qty-<?= $tid ?>" min="0.01" step="0.01" value="1">
                            </div>
                            <div class="col-md-2 d-grid">
                                <button type="button" class="btn btn-sm btn-primary" onclick="addTemplateItem(<?= $tid ?>)">
                                    <i class="bi bi-plus"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabela de itens -->
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0" id="items-table-<?= $tid ?>">
                        <thead>
                            <tr class="bg-light">
                                <th style="width:40px;">Ativo</th>
                                <th>Material</th>
                                <th style="width:120px;">Especificação</th>
                                <th style="width:100px;">Classificação</th>
                                <th style="width:80px;">Tipo</th>
                                <th style="width:110px;">Qtd padrão</th>
                                <th style="width:50px;"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body-<?= $tid ?>">
                            <?php foreach ($items as $it): $iid = (int) $it['id']; 
                                $projectType = $it['project_type'] ?? $it['material_project_type'] ?? 'both';
                                $projectTypeLabels = [
                                    'construction' => ['🏗️', 'Construção', 'primary'],
                                    'renovation' => ['🔧', 'Reforma', 'warning'],
                                    'both' => ['🏗️🔧', 'Ambos', 'secondary'],
                                ];
                                $ptInfo = $projectTypeLabels[$projectType] ?? $projectTypeLabels['both'];
                            ?>
                            <tr id="tpl-item-<?= $iid ?>" class="<?= (int) $it['active'] !== 1 ? 'table-secondary opacity-75' : '' ?>">
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input tpl-item-active" data-id="<?= $iid ?>" <?= (int) $it['active'] === 1 ? 'checked' : '' ?>>
                                </td>
                                <td><?= htmlspecialchars($it['material_name']) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($it['specification'] ?? ($it['category_name'] ?? '')) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars($it['classification'] ?? '') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $ptInfo[2] ?>" title="<?= $ptInfo[1] ?>" style="font-size:0.7rem;"><?= $ptInfo[0] ?></span>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" class="form-control tpl-item-qty" data-id="<?= $iid ?>" min="0.01" step="0.01" value="<?= rtrim(rtrim(number_format((float) $it['default_quantity'], 2, '.', ''), '0'), '.') ?>">
                                        <span class="input-group-text"><?= htmlspecialchars($it['unit_abbr'] ?? $it['unit'] ?? '') ?></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteTemplateItem(<?= $iid ?>, <?= $tid ?>)"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="text-center text-muted py-3 <?= empty($items) ? '' : 'd-none' ?>" id="empty-items-<?= $tid ?>">
                        <i class="bi bi-inbox"></i> Nenhum item nesta lista ainda.
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Modal Nova Lista -->
<div class="modal fade" id="newListModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="/admin/material-lists/store">
            <div class="modal-header">
                <h5 class="modal-title">Nova Lista de Materiais</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nome da lista *</label>
                    <input type="text" class="form-control" name="name" placeholder="Ex: Início de Obra, Elétrica, Hidráulica" required>
                </div>
                <div class="mb-1">
                    <label class="form-label">Descrição (opcional)</label>
                    <textarea class="form-control" name="description" rows="2" placeholder="Para que serve esta lista..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Criar Lista</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Lista -->
<div class="modal fade" id="editListModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="/admin/material-lists/update">
            <div class="modal-header">
                <h5 class="modal-title">Editar Lista</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="editListId">
                <div class="mb-3">
                    <label class="form-label">Nome da lista *</label>
                    <input type="text" class="form-control" name="name" id="editListName" required>
                </div>
                <div class="mb-1">
                    <label class="form-label">Descrição (opcional)</label>
                    <textarea class="form-control" name="description" id="editListDescription" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Recriar Listas -->
<div class="modal fade" id="recreateListsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="/admin/material-lists/recreate" id="recreateForm">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill"></i> Recriar Todas as Listas</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <strong>Atenção!</strong> Esta ação irá:
                    <ul class="mb-0 mt-2">
                        <li><strong>Apagar TODAS</strong> as listas existentes e seus itens</li>
                        <li>Criar novas listas baseadas nas <strong>categorias de materiais</strong> cadastradas</li>
                        <li>Cada categoria se tornará uma lista com todos os materiais ativos daquela categoria</li>
                    </ul>
                </div>
                <p class="text-muted small mb-3">
                    Esta ação <strong>não pode ser desfeita</strong>. Listas personalizadas, quantidades ajustadas e itens desativados serão perdidos.
                </p>
                <div class="mb-0">
                    <label class="form-label fw-bold">Para confirmar, digite <span class="text-danger">RECRIAR</span> abaixo:</label>
                    <input type="text" class="form-control" name="confirm" id="recreateConfirm" placeholder="Digite RECRIAR" autocomplete="off" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger" id="recreateBtn" disabled>
                    <i class="bi bi-arrow-clockwise"></i> Recriar Todas as Listas
                </button>
            </div>
        </form>
    </div>
</div>

<script src="/assets/js/searchable-select.js"></script>
<script>
const tplMaterials = <?= json_encode($materials) ?>;

// Monta as opções do select de materiais
function buildTplMaterialOptions() {
    let opts = '<option value="">-- Buscar material --</option>';
    tplMaterials.forEach(m => {
        const label = m.name + (m.classification ? ' - ' + m.classification : '') + (m.specification ? ' (' + m.specification + ')' : '');
        opts += `<option value="${m.id}" data-name="${(m.name || '').replace(/"/g, '&quot;')}" data-spec="${(m.specification || m.category_name || '').replace(/"/g, '&quot;')}" data-class="${(m.classification || '').replace(/"/g, '&quot;')}" data-unit="${(m.unit_abbr || m.unit_name || '').replace(/"/g, '&quot;')}">${label}</option>`;
    });
    return opts;
}

// Guarda o material selecionado por template
const tplSelected = {};

// Inicializa os SearchableSelect de cada lista aberta
document.querySelectorAll('.tpl-mat-select').forEach(sel => {
    const tid = sel.id.replace('mat-select-', '');
    sel.innerHTML = buildTplMaterialOptions();
    new SearchableSelect(sel, {
        placeholder: 'Buscar material...',
        onSelect: function(value, text, dataset) {
            tplSelected[tid] = {
                id: value,
                name: dataset.name || '',
                specification: dataset.spec || '',
                classification: dataset.class || '',
                unit: dataset.unit || '',
            };
        }
    });
});

async function addTemplateItem(tid) {
    const sel = tplSelected[tid];
    if (!sel || !sel.id) { alert('Selecione um material primeiro.'); return; }
    const qty = parseFloat(document.getElementById('qty-' + tid).value) || 1;

    const resp = await fetch('/admin/material-lists/store-item', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            template_id: tid,
            material_id: sel.id,
            material_name: sel.name,
            specification: sel.specification,
            classification: sel.classification,
            unit: sel.unit,
            default_quantity: qty,
        })
    });
    const data = await resp.json();
    if (!data.success) { alert(data.error || 'Erro ao adicionar item.'); return; }

    const it = data.item;
    const qtyDisplay = String(it.default_quantity).replace(/\.?0+$/, '') || it.default_quantity;
    const projectType = it.project_type || 'both';
    const ptLabels = {
        'construction': ['🏗️', 'Construção', 'primary'],
        'renovation': ['🔧', 'Reforma', 'warning'],
        'both': ['🏗️🔧', 'Ambos', 'secondary']
    };
    const ptInfo = ptLabels[projectType] || ptLabels['both'];
    const tr = document.createElement('tr');
    tr.id = 'tpl-item-' + it.id;
    tr.innerHTML = `
        <td class="text-center"><input type="checkbox" class="form-check-input tpl-item-active" data-id="${it.id}" checked></td>
        <td>${escapeHtml(it.material_name)}</td>
        <td class="small text-muted">${escapeHtml(it.specification || '')}</td>
        <td class="small text-muted">${escapeHtml(it.classification || '')}</td>
        <td class="text-center"><span class="badge bg-${ptInfo[2]}" title="${ptInfo[1]}" style="font-size:0.7rem;">${ptInfo[0]}</span></td>
        <td><div class="input-group input-group-sm">
            <input type="number" class="form-control tpl-item-qty" data-id="${it.id}" min="0.01" step="0.01" value="${qtyDisplay}">
            <span class="input-group-text">${escapeHtml(it.unit || '')}</span>
        </div></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteTemplateItem(${it.id}, ${tid})"><i class="bi bi-trash"></i></button></td>
    `;
    document.getElementById('items-body-' + tid).appendChild(tr);
    bindItemRow(tr);
    document.getElementById('empty-items-' + tid).classList.add('d-none');
    updateCount(tid, 1);

    // Reset seleção
    tplSelected[tid] = null;
    document.getElementById('qty-' + tid).value = 1;
}

async function deleteTemplateItem(iid, tid) {
    if (!confirm('Remover este item da lista?')) return;
    const resp = await fetch('/admin/material-lists/delete-item', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ id: iid })
    });
    const data = await resp.json();
    if (data.success) {
        document.getElementById('tpl-item-' + iid)?.remove();
        updateCount(tid, -1);
    }
}

async function updateTemplateItem(iid, payload) {
    await fetch('/admin/material-lists/update-item', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(Object.assign({ id: iid }, payload))
    });
}

function bindItemRow(row) {
    const qtyInput = row.querySelector('.tpl-item-qty');
    if (qtyInput) {
        qtyInput.addEventListener('change', function() {
            let v = parseFloat(this.value);
            if (isNaN(v) || v < 0.01) { v = 1; this.value = 1; }
            updateTemplateItem(this.dataset.id, { default_quantity: v });
        });
    }
    const activeChk = row.querySelector('.tpl-item-active');
    if (activeChk) {
        activeChk.addEventListener('change', function() {
            const on = this.checked ? 1 : 0;
            row.classList.toggle('table-secondary', !on);
            row.classList.toggle('opacity-75', !on);
            updateTemplateItem(this.dataset.id, { active: on });
        });
    }
}

function updateCount(tid, delta) {
    const badge = document.querySelector('.tpl-count[data-tid="' + tid + '"]');
    if (!badge) return;
    const current = parseInt(badge.textContent) || 0;
    const next = Math.max(0, current + delta);
    badge.textContent = next + ' itens';
    if (next === 0) document.getElementById('empty-items-' + tid).classList.remove('d-none');
}

function escapeHtml(str) {
    return String(str || '').replace(/[&<>"']/g, s => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[s]));
}

// Bind das linhas já renderizadas
document.querySelectorAll('[id^="items-body-"] tr').forEach(bindItemRow);

// Editar cabeçalho da lista
document.querySelectorAll('.edit-list-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('editListId').value = this.dataset.id;
        document.getElementById('editListName').value = this.dataset.name;
        document.getElementById('editListDescription').value = this.dataset.description;
        new bootstrap.Modal(document.getElementById('editListModal')).show();
    });
});

// Abrir automaticamente a lista recém-criada/editada (?open=ID)
(function() {
    const params = new URLSearchParams(window.location.search);
    const open = params.get('open');
    if (open) {
        const el = document.getElementById('tpl-' + open);
        if (el) {
            new bootstrap.Collapse(el, { show: true });
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
})();

// Validação do campo de confirmação para recriar listas
(function() {
    const confirmInput = document.getElementById('recreateConfirm');
    const recreateBtn = document.getElementById('recreateBtn');
    if (confirmInput && recreateBtn) {
        confirmInput.addEventListener('input', function() {
            recreateBtn.disabled = this.value.trim().toUpperCase() !== 'RECRIAR';
        });
        // Reset ao abrir o modal
        document.getElementById('recreateListsModal')?.addEventListener('show.bs.modal', function() {
            confirmInput.value = '';
            recreateBtn.disabled = true;
        });
    }
})();
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
