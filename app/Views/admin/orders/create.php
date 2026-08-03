<?php $pageTitle = 'Novo Pedido'; $currentPage = 'orders'; ?>
<?php ob_start(); ?>

<link rel="stylesheet" href="/assets/css/searchable-select.css">

<form method="POST" action="/admin/orders/store" id="orderForm">
    <div class="row">
        <div class="col-lg-9">
            <!-- Tipo do Pedido -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-tag"></i> Tipo do Pedido</div>
                <div class="card-body">
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="order_type" id="typeMaterial" value="material" checked>
                        <label class="btn btn-outline-primary" for="typeMaterial">
                            <i class="bi bi-box-seam"></i> Material
                        </label>
                        <input type="radio" class="btn-check" name="order_type" id="typeService" value="service">
                        <label class="btn btn-outline-success" for="typeService">
                            <i class="bi bi-wrench"></i> Serviço
                        </label>
                    </div>
                    <small class="text-muted d-block mt-2" id="orderTypeHint">Pedido de materiais: siga o fluxo padrão de cotação.</small>
                </div>
            </div>

            <!-- Obra -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-buildings"></i> Obra</div>
                <div class="card-body">
                    <select class="form-select" name="construction_site_id" id="constructionSiteSelect">
                        <option value="">-- Selecione a obra (opcional) --</option>
                        <?php foreach ($constructionSites as $site): ?>
                        <option value="<?= $site['id'] ?>"><?= htmlspecialchars($site['code'] . ' - ' . $site['name']) ?><?= !empty($site['client_name']) ? ' (' . htmlspecialchars($site['client_name']) . ')' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted d-block mt-1">Vincule este pedido a uma obra para rastreamento.</small>
                </div>
            </div>

            <!-- Upload PDF com IA -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-file-earmark-pdf"></i> Importar Materiais (opcional)</div>
                <div class="card-body">
                    <p class="text-muted small mb-2">Faça upload de um PDF ou imagem com a lista de materiais. A IA irá identificar e adicionar automaticamente.</p>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="file" class="form-control form-control-sm" id="pdfUpload" accept=".pdf,.jpg,.jpeg,.png,.webp">
                        <button type="button" class="btn btn-sm btn-outline-primary flex-shrink-0" id="parsePdfBtn" onclick="parsePdfFile()">
                            <i class="bi bi-magic"></i> <span class="d-none d-sm-inline">Analisar</span>
                        </button>
                    </div>
                    <div id="pdfStatus" class="mt-2" style="display:none;"></div>
                </div>
            </div>

            <!-- Itens do pedido -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-list-check"></i> Itens do Pedido <span class="badge bg-primary ms-1" id="itemCountBadge">0</span></span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#newMaterialModal">
                            <i class="bi bi-box-seam"></i> <span class="d-none d-sm-inline">Novo Material</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" id="addItemBtn">
                            <i class="bi bi-plus"></i> Adicionar Item
                        </button>
                    </div>
                </div>
                <div class="card-body" style="overflow:visible;">
                    <!-- Desktop: Tabela -->
                    <div class="d-none d-md-block">
                        <table class="table table-sm mb-0" id="itemsTableDesktop">
                            <thead>
                                <tr class="bg-light">
                                    <th style="min-width:250px;">Material</th>
                                    <th style="min-width:120px;">Especificação</th>
                                    <th style="min-width:100px;">Classificação</th>
                                    <th style="width:90px;">Qtd</th>
                                    <th style="width:50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBodyDesktop"></tbody>
                        </table>
                        <div class="p-3 text-center text-muted" id="emptyDesktop">
                            <i class="bi bi-inbox"></i> Clique em "Adicionar Item" para começar
                        </div>
                    </div>
                    <!-- Mobile: Cards -->
                    <div class="d-md-none p-3">
                        <div id="itemsBodyMobile"></div>
                        <div class="text-center text-muted py-2" id="emptyMobile">
                            <i class="bi bi-inbox d-block mb-1" style="font-size:1.5rem;"></i>
                            Nenhum item adicionado
                        </div>
                        <!-- Botão adicionar item inline (sempre visível abaixo dos itens) -->
                        <button type="button" class="btn btn-outline-primary w-100 mt-2 addItemInline" id="addItemBtnInline">
                            <i class="bi bi-plus-lg"></i> Adicionar Item
                        </button>
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
        </div>

        <!-- Sidebar desktop (esconde no mobile) -->
        <div class="col-lg-3 d-none d-lg-block">
            <div class="card sticky-top" style="top:1.5rem;">
                <div class="card-body d-grid gap-2">
                    <button type="button" class="btn btn-primary" onclick="showReview()">
                        <i class="bi bi-eye"></i> Revisar e Enviar
                    </button>
                    <a href="/admin/orders" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile: Botão fixo inferior -->
    <div class="d-lg-none position-fixed start-0 end-0 bg-white border-top shadow" id="mobileReviewBtn" style="z-index:1100; bottom: env(safe-area-inset-bottom, 0px); padding-bottom: env(safe-area-inset-bottom, 0px);">
        <div class="p-2">
            <button type="button" class="btn btn-primary w-100 py-2" onclick="showReview()" style="font-size:1rem;">
                <i class="bi bi-eye"></i> Revisar e Enviar Pedido
            </button>
        </div>
    </div>
    <div class="d-lg-none" style="height:80px;"></div>
</form>

<!-- Modal de Revisão do Pedido -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Revisão do Pedido</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="reviewBody">
                <!-- Preenchido via JS -->
            </div>
            <div class="modal-footer flex-column flex-sm-row gap-2" style="padding-bottom: calc(0.75rem + env(safe-area-inset-bottom, 0px));">
                <button type="button" class="btn btn-outline-secondary w-100 order-2 order-sm-1" data-bs-dismiss="modal" style="flex:1;">
                    <i class="bi bi-pencil"></i> Voltar e Editar
                </button>
                <button type="button" class="btn btn-primary w-100 order-1 order-sm-2" onclick="confirmSubmit()" style="flex:1;">
                    <i class="bi bi-send"></i> Confirmar e Enviar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Novo Material (com steps internos) -->
<div class="modal fade" id="newMaterialModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div id="matStep1">
                <div class="modal-header">
                    <h5 class="modal-title">Novo Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Nome do Material *</label><input type="text" class="form-control" id="newMatName" required></div>
                    <div class="mb-3">
                        <label class="form-label">Especificação (Tipo)</label>
                        <div class="input-group">
                            <select class="form-select" id="newMatSpec">
                                <option value="">-- Selecione --</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['name']) ?>" data-id="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-outline-primary" onclick="showQuickAdd('category')"><i class="bi bi-plus"></i></button>
                        </div>
                    </div>
                    <div class="mb-3"><label class="form-label">Classificação</label><input type="text" class="form-control" id="newMatClassification" placeholder="Ex: 100mm, 3/4&quot;, 50x40"></div>
                    <div class="mb-3">
                        <label class="form-label">Unidade de Medida</label>
                        <div class="input-group">
                            <select class="form-select" id="newMatUnit">
                                <option value="">-- Selecione --</option>
                                <?php foreach ($units as $u): ?>
                                <option value="<?= $u['id'] ?>" data-abbr="<?= htmlspecialchars($u['abbreviation']) ?>"><?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['abbreviation']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-outline-primary" onclick="showQuickAdd('unit')"><i class="bi bi-plus"></i></button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="saveMaterialBtn">Salvar Material</button>
                </div>
            </div>
            <div id="matStep2" style="display:none;">
                <div class="modal-header">
                    <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="backToMatStep1()"><i class="bi bi-arrow-left"></i></button>
                    <h6 class="modal-title" id="quickAddTitle">Nova Especificação</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="quickCatFields"><label class="form-label">Nome da Especificação *</label><input type="text" class="form-control" id="quickCatName" placeholder="Ex: mat. Elétrico"></div>
                    <div id="quickUnitFields" style="display:none;">
                        <div class="mb-3"><label class="form-label">Nome *</label><input type="text" class="form-control" id="quickUnitName" placeholder="Ex: Galão"></div>
                        <div><label class="form-label">Abreviação *</label><input type="text" class="form-control" id="quickUnitAbbr" placeholder="Ex: gal"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="backToMatStep1()">Voltar</button>
                    <button type="button" class="btn btn-primary" onclick="saveQuickAdd()">Salvar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.item-card { border:1px solid #e0e0e0; border-radius:8px; padding:12px; margin-bottom:10px; background:#fafbfc; position:relative; }
.item-card .item-number { position:absolute; top:-8px; left:10px; background:#3a3b4e; color:#fff; font-size:0.65rem; padding:1px 8px; border-radius:10px; }
.item-card .item-details { display:flex; flex-wrap:wrap; gap:4px; margin-top:6px; }
.item-card .item-details .badge { font-weight:400; font-size:0.7rem; }
</style>

<script src="/assets/js/searchable-select.js"></script>
<script>
const materials = <?= json_encode($materials) ?>;
let itemCount = 0;

document.getElementById('addItemBtn').addEventListener('click', () => addItem());

function updateItemCount() {
    const count = document.querySelectorAll('#itemsBodyDesktop tr').length;
    document.getElementById('itemCountBadge').textContent = count;
    document.getElementById('emptyDesktop').style.display = count ? 'none' : '';
    document.getElementById('emptyMobile').style.display = count ? 'none' : '';
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
    const opts = buildMaterialOptions(prefill);

    // Desktop row
    const tr = document.createElement('tr');
    tr.id = 'item-row-' + itemCount;
    const idx = itemCount;
    tr.innerHTML = `
        <td>
            <select class="material-select-raw" id="mat-select-${idx}" style="display:none;">${opts}</select>
            <div id="mat-ss-${idx}"></div>
            <input type="hidden" name="items[${idx}][material_id]" id="mid-${idx}" value="${prefill?.id || ''}">
            <input type="hidden" name="items[${idx}][material_name]" id="mname-${idx}" value="${prefill?.name || ''}">
        </td>
        <td><input type="text" class="form-control form-control-sm" name="items[${idx}][specification]" id="spec-${idx}" value="${prefill?.specification || ''}" readonly></td>
        <td><input type="text" class="form-control form-control-sm" name="items[${idx}][classification]" id="class-${idx}" value="${prefill?.classification || ''}" readonly></td>
        <input type="hidden" name="items[${idx}][unit]" id="unit-${idx}" value="${prefill?.unit || ''}">
        <td><input type="number" class="form-control form-control-sm" name="items[${idx}][quantity]" min="0.01" step="0.01" value="${prefill?.quantity || 1}" required></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeItem(${idx})"><i class="bi bi-trash"></i></button></td>
    `;
    document.getElementById('itemsBodyDesktop').appendChild(tr);

    // Inicializar SearchableSelect para o material
    const matSS = new SearchableSelect(document.getElementById('mat-select-' + idx), {
        placeholder: 'Buscar material...',
        onSelect: function(value, text, dataset) {
            // EPIs entram como itens não vinculados (material_id nulo)
            document.getElementById('mid-' + idx).value = String(value).startsWith('epi-') ? '' : value;
            document.getElementById('mname-' + idx).value = dataset.name || '';
            document.getElementById('spec-' + idx).value = dataset.spec || '';
            document.getElementById('class-' + idx).value = dataset.class || '';
            document.getElementById('unit-' + idx).value = dataset.unit || '';
            // Sync mobile
            updateMobileDetails(idx, { dataset });
        }
    });

    // Se tem prefill, setar valor
    if (prefill?.id) {
        matSS.setValue(prefill.id);
    }

    // Mobile card
    const card = document.createElement('div');
    card.className = 'item-card';
    card.id = 'item-card-' + itemCount;
    card.innerHTML = `
        <span class="item-number">#${itemCount}</span>
        <div class="d-flex gap-2 align-items-center mb-2">
            <select class="material-select-raw-m" id="mat-select-m-${idx}" style="display:none;">${opts}</select>
            <div class="flex-grow-1" id="mat-ss-m-${idx}"></div>
            <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0" onclick="removeItem(${idx})"><i class="bi bi-trash"></i></button>
        </div>
        <div class="item-details" id="details-m-${idx}">
            ${prefill ? `<span class="badge bg-light text-dark">${prefill.specification||''}</span><span class="badge bg-light text-dark">${prefill.classification||''}</span>` : '<span class="text-muted" style="font-size:0.75rem;">Busque um material acima</span>'}
        </div>
        <div class="d-flex align-items-center gap-2 mt-2">
            <label class="form-label mb-0 small fw-bold">Qtd:</label>
            <input type="number" class="form-control form-control-sm qty-mobile" style="max-width:100px;" data-idx="${idx}" min="0.01" step="0.01" value="${prefill?.quantity || 1}" required>
        </div>
    `;
    document.getElementById('itemsBodyMobile').appendChild(card);

    // SearchableSelect para mobile
    const matSSM = new SearchableSelect(document.getElementById('mat-select-m-' + idx), {
        placeholder: 'Buscar material...',
        onSelect: function(value, text, dataset) {
            // EPIs entram como itens não vinculados (material_id nulo)
            document.getElementById('mid-' + idx).value = String(value).startsWith('epi-') ? '' : value;
            document.getElementById('mname-' + idx).value = dataset.name || '';
            document.getElementById('spec-' + idx).value = dataset.spec || '';
            document.getElementById('class-' + idx).value = dataset.class || '';
            document.getElementById('unit-' + idx).value = dataset.unit || '';
            updateMobileDetails(idx, { dataset });
        }
    });

    if (prefill?.id) {
        matSSM.setValue(prefill.id);
    }

    // Sincronizar quantidade mobile → desktop
    card.querySelector('.qty-mobile').addEventListener('input', function() {
        const desktopInput = document.querySelector(`#item-row-${this.dataset.idx} [name="items[${this.dataset.idx}][quantity]"]`);
        if (desktopInput) desktopInput.value = this.value;
    });
    // Sincronizar desktop → mobile
    const desktopQty = tr.querySelector(`[name="items[${idx}][quantity]"]`);
    if (desktopQty) {
        desktopQty.addEventListener('input', function() {
            const mobileInput = card.querySelector('.qty-mobile');
            if (mobileInput) mobileInput.value = this.value;
        });
    }

    updateItemCount();
}

function removeItem(idx) {
    document.getElementById('item-row-' + idx)?.remove();
    document.getElementById('item-card-' + idx)?.remove();
    updateItemCount();
}

function updateMobileDetails(idx, opt) {
    const el = document.getElementById('details-m-' + idx);
    if (!el) return;
    const ds = opt?.dataset || {};
    if (ds.spec || ds.class || ds.unit) {
        el.innerHTML = '';
        if (ds.spec) el.innerHTML += `<span class="badge bg-light text-dark">${ds.spec}</span>`;
        if (ds.class) el.innerHTML += `<span class="badge bg-light text-dark">${ds.class}</span>`;
    } else {
        el.innerHTML = '<span class="text-muted" style="font-size:0.75rem;">Busque um material acima</span>';
    }
}

// Material inline
document.getElementById('saveMaterialBtn').addEventListener('click', async function() {
    const name = document.getElementById('newMatName').value.trim();
    if (!name) { alert('Nome é obrigatório'); return; }
    const specSelect = document.getElementById('newMatSpec'), unitSelect = document.getElementById('newMatUnit');
    const resp = await fetch('/admin/materials/quick-store', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({ name, specification:specSelect.value, category_id:specSelect.selectedOptions[0]?.dataset?.id||'', unit_id:unitSelect.value||'', classification:document.getElementById('newMatClassification').value }) });
    const data = await resp.json();
    if (data.success) {
        const unitAbbr = unitSelect.selectedOptions[0]?.dataset?.abbr || '';
        materials.push({ id:data.material.id, name:data.material.name, specification:data.material.specification||specSelect.value, classification:data.material.classification||'', unit_abbr:unitAbbr, unit_name:'', category_name:specSelect.value });
        addItem({ id:data.material.id, name:data.material.name, specification:specSelect.value, classification:data.material.classification||'', unit:unitAbbr, quantity:1 });
        bootstrap.Modal.getInstance(document.getElementById('newMaterialModal')).hide();
        ['newMatName','newMatClassification'].forEach(id => document.getElementById(id).value='');
        document.getElementById('newMatSpec').value=''; document.getElementById('newMatUnit').value='';
    } else { alert(data.error||'Erro'); }
});

// Validação
document.getElementById('orderForm').addEventListener('submit', function(e) {
    if (!document.querySelectorAll('#itemsBodyDesktop tr').length) { e.preventDefault(); alert('Adicione pelo menos um item.'); return; }
    let valid = true;
    document.querySelectorAll('[id^="mname-"]').forEach(input => {
        if (!input.value) { valid = false; }
    });
    if (!valid) { e.preventDefault(); alert('Selecione um material para cada item.'); }
});

// Botão inline de adicionar item (mobile)
document.getElementById('addItemBtnInline')?.addEventListener('click', () => addItem());

// Upload de PDF com IA
async function parsePdfFile() {
    const fileInput = document.getElementById('pdfUpload');
    const statusEl = document.getElementById('pdfStatus');
    
    if (!fileInput.files.length) { alert('Selecione um arquivo primeiro.'); return; }
    
    const btn = document.getElementById('parsePdfBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    statusEl.style.display = 'block';
    statusEl.innerHTML = '<div class="alert alert-info small py-2 mb-0"><i class="bi bi-hourglass-split"></i> Analisando documento com IA... pode levar alguns segundos.</div>';

    const formData = new FormData();
    formData.append('pdf', fileInput.files[0]);

    try {
        const resp = await fetch('/admin/orders/parse-pdf', { method: 'POST', body: formData });
        const data = await resp.json();

        if (data.success && data.materials) {
            let found = 0;
            let notFound = 0;
            let notFoundList = [];
            let notFoundItems = [];

            data.materials.forEach(m => {
                // Matching inteligente: normaliza texto (remove acentos, lowercase)
                const normalize = (str) => (str || '').toLowerCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9\s]/g, ' ')
                    .replace(/\s+/g, ' ').trim();
                
                const mNorm = normalize(m.name);
                const mWords = mNorm.split(' ').filter(w => w.length > 2);
                
                let bestMatch = null;
                let bestScore = 0;
                
                materials.forEach(mat => {
                    const matNorm = normalize(mat.name);
                    if (matNorm === mNorm) { bestMatch = mat; bestScore = 100; return; }
                    if (matNorm.includes(mNorm) || mNorm.includes(matNorm)) { if (bestScore < 80) { bestMatch = mat; bestScore = 80; } return; }
                    const matWords = matNorm.split(' ').filter(w => w.length > 2);
                    let commonWords = 0;
                    mWords.forEach(w => { if (matWords.includes(w)) commonWords++; });
                    const score = mWords.length > 0 ? (commonWords / mWords.length) * 70 : 0;
                    if (score > bestScore && score >= 40) { bestMatch = mat; bestScore = score; }
                });

                const existing = bestMatch;
                
                if (existing) {
                    found++;
                    // Encontrado: adiciona direto nos itens do pedido
                    addItem({
                        id: existing.id,
                        name: existing.name,
                        specification: existing.specification || m.specification || '',
                        classification: existing.classification || m.classification || '',
                        unit: existing.unit_abbr || existing.unit_name || m.unit || '',
                        quantity: m.quantity || 1,
                    });
                } else {
                    // Não encontrado: NÃO adiciona nos itens, vai só pra tabela de revisão
                    notFound++;
                    notFoundList.push(m.name);
                    notFoundItems.push(m);
                }
            });
            
            let resultHtml = `<div class="alert alert-success small py-2 mb-2"><i class="bi bi-check-circle"></i> <strong>${data.materials.length} materiais</strong> identificados pela IA.`;
            if (found > 0) resultHtml += ` <span class="text-success">${found} vinculados</span> ao cadastro.`;
            if (notFound > 0) resultHtml += ` <span class="text-warning">${notFound} não encontrados</span>.`;
            resultHtml += `</div>`;

            if (notFound > 0) {
                resultHtml += `<div class="card border-warning mb-3"><div class="card-header bg-warning bg-opacity-10 py-2"><strong class="small">Materiais não encontrados no cadastro (${notFound})</strong><br><small class="text-muted">Revise e cadastre os que precisar:</small></div>`;
                resultHtml += `<div class="card-body p-0"><div class="table-responsive"><table class="table table-sm mb-0" style="font-size:0.8rem;">`;
                resultHtml += `<thead><tr><th>Nome</th><th>Espec.</th><th>Class.</th><th>Qtd</th><th></th></tr></thead><tbody>`;
                
                notFoundItems.forEach((m, idx) => {
                    resultHtml += `<tr id="nf-row-${idx}">`;
                    resultHtml += `<td><input type="text" class="form-control form-control-sm" value="${m.name}" id="nf-name-${idx}"></td>`;
                    resultHtml += `<td><input type="text" class="form-control form-control-sm" value="${m.specification || ''}" id="nf-spec-${idx}" style="width:100px;"></td>`;
                    resultHtml += `<td><input type="text" class="form-control form-control-sm" value="${m.classification || ''}" id="nf-class-${idx}" style="width:80px;"></td>`;
                    resultHtml += `<td><input type="number" class="form-control form-control-sm" value="${m.quantity || 1}" id="nf-qty-${idx}" style="width:60px;"></td>`;
                    resultHtml += `<td><button type="button" class="btn btn-sm btn-outline-success" onclick="quickRegisterFromPdf(${idx})" title="Cadastrar material"><i class="bi bi-plus-circle"></i></button></td>`;
                    resultHtml += `</tr>`;
                });
                
                resultHtml += `</tbody></table></div></div>`;
                resultHtml += `<div class="card-footer py-2 text-end"><button type="button" class="btn btn-sm btn-success" onclick="quickRegisterAllFromPdf()"><i class="bi bi-check-all"></i> Cadastrar Todos</button></div></div>`;
            }

            statusEl.innerHTML = resultHtml;
        } else {
            statusEl.innerHTML = `<div class="alert alert-danger small py-2 mb-0"><i class="bi bi-x-circle"></i> ${data.error || 'Erro ao analisar'}</div>`;
        }
    } catch (e) {
        statusEl.innerHTML = `<div class="alert alert-danger small py-2 mb-0"><i class="bi bi-x-circle"></i> Erro de conexão</div>`;
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-magic"></i> <span class="d-none d-sm-inline">Analisar</span>';
    fileInput.value = '';
}

// Cadastro rápido de material identificado pelo PDF
async function quickRegisterFromPdf(idx) {
    const name = document.getElementById('nf-name-' + idx)?.value?.trim();
    const spec = document.getElementById('nf-spec-' + idx)?.value?.trim();
    const cls = document.getElementById('nf-class-' + idx)?.value?.trim();
    const unit = document.getElementById('nf-unit-' + idx)?.value?.trim();
    const qty = document.getElementById('nf-qty-' + idx)?.value || 1;
    
    if (!name) { alert('Nome é obrigatório'); return; }

    const resp = await fetch('/admin/materials/quick-store', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ name, specification: spec, classification: cls, unit_id: '', category_id: '' })
    });
    const data = await resp.json();
    
    if (data.success) {
        // Adicionar ao array local de materiais
        materials.push({ id: data.material.id, name: data.material.name, specification: spec, classification: cls, unit_abbr: unit, unit_name: '', category_name: spec });
        
        // Adicionar como item no pedido (com vínculo)
        addItem({
            id: data.material.id,
            name: data.material.name,
            specification: spec,
            classification: cls,
            unit: unit,
            quantity: parseFloat(qty) || 1,
        });

        // Marcar linha como cadastrado
        const row = document.getElementById('nf-row-' + idx);
        if (row) {
            row.style.opacity = '0.4';
            row.style.textDecoration = 'line-through';
            row.querySelector('button').innerHTML = '<i class="bi bi-check text-success"></i>';
            row.querySelector('button').disabled = true;
        }
    } else {
        alert(data.error || 'Erro ao cadastrar');
    }
}

async function quickRegisterAllFromPdf() {
    const rows = document.querySelectorAll('[id^="nf-row-"]');
    let registered = 0;
    
    for (const row of rows) {
        const btn = row.querySelector('button');
        if (btn && !btn.disabled) {
            const idx = row.id.replace('nf-row-', '');
            await quickRegisterFromPdf(parseInt(idx));
            registered++;
        }
    }
    
    if (registered > 0) alert(`${registered} materiais cadastrados com sucesso!`);
}

// Revisão do pedido
function showReview() {
    const rows = document.querySelectorAll('#itemsBodyDesktop tr');
    if (rows.length === 0) { alert('Adicione pelo menos um item.'); return; }
    
    // Validar que todos têm material selecionado (EPIs são itens não vinculados: valida pelo nome)
    let valid = true;
    document.querySelectorAll('[id^="mname-"]').forEach(input => { if (!input.value) valid = false; });
    if (!valid) { alert('Selecione um material para cada item.'); return; }

    // Validar quantidade mínima (>= 0.01)
    let qtyValid = true;
    let invalidItemName = '';
    rows.forEach(row => {
        const qtyInput = row.querySelector('[name*="[quantity]"]');
        const nameInput = row.querySelector('[id^="mname-"]');
        const qty = parseFloat(qtyInput?.value) || 0;
        if (qty < 0.01) {
            qtyValid = false;
            invalidItemName = nameInput?.value || 'Item';
            if (qtyInput) { qtyInput.classList.add('is-invalid'); qtyInput.focus(); }
        }
    });
    if (!qtyValid) { alert('A quantidade de "' + invalidItemName + '" deve ser no mínimo 0,01.'); return; }

    // Montar resumo
    let html = '<h6 class="mb-3">Itens do pedido:</h6>';

    // Mostrar obra selecionada
    const siteSelect = document.getElementById('constructionSiteSelect');
    if (siteSelect && siteSelect.value) {
        const siteName = siteSelect.options[siteSelect.selectedIndex].text;
        html += `<div class="alert alert-light py-2 mb-3"><i class="bi bi-buildings"></i> <strong>Obra:</strong> ${siteName}</div>`;
    }

    html += '<table class="table table-sm table-bordered"><thead><tr><th>#</th><th>' + (document.getElementById('typeService').checked ? 'Serviço' : 'Material') + '</th><th>Espec.</th><th>Class.</th><th class="text-center">Qtd</th></tr></thead><tbody>';
    
    let count = 0;
    rows.forEach(row => {
        count++;
        const name = row.querySelector('[id^="mname-"]')?.value || '-';
        const spec = row.querySelector('[id^="spec-"]')?.value || '-';
        const cls = row.querySelector('[id^="class-"]')?.value || '-';
        const qty = row.querySelector('[name*="[quantity]"]')?.value || '1';
        html += `<tr><td>${count}</td><td><strong>${name}</strong></td><td>${spec}</td><td>${cls}</td><td class="text-center">${qty}</td></tr>`;
    });
    html += '</tbody></table>';

    const obs = document.querySelector('[name="description"]')?.value;
    if (obs) {
        html += `<div class="alert alert-light mt-3"><strong>Observações:</strong> ${obs}</div>`;
    }

    html += `<div class="alert alert-info mt-3 small"><i class="bi bi-info-circle"></i> Ao confirmar, o pedido será criado e enviado para cotação. Os responsáveis serão notificados.</div>`;

    document.getElementById('reviewBody').innerHTML = html;
    new bootstrap.Modal(document.getElementById('reviewModal')).show();
}

function confirmSubmit() {
    bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();
    
    // Verificar estoque antes de enviar
    if (typeof checkStockBeforeSubmit === 'function') {
        checkStockBeforeSubmit();
    } else {
        document.getElementById('orderForm').submit();
    }
}

// Quick add steps
let quickAddMode = '';
function showQuickAdd(mode) {
    quickAddMode = mode;
    document.getElementById('matStep1').style.display = 'none';
    document.getElementById('matStep2').style.display = 'block';
    if (mode === 'category') { document.getElementById('quickAddTitle').textContent='Nova Especificação'; document.getElementById('quickCatFields').style.display=''; document.getElementById('quickUnitFields').style.display='none'; setTimeout(()=>document.getElementById('quickCatName').focus(),100); }
    else { document.getElementById('quickAddTitle').textContent='Nova Unidade de Medida'; document.getElementById('quickCatFields').style.display='none'; document.getElementById('quickUnitFields').style.display=''; setTimeout(()=>document.getElementById('quickUnitName').focus(),100); }
}
function backToMatStep1() { document.getElementById('matStep2').style.display='none'; document.getElementById('matStep1').style.display='block'; }
async function saveQuickAdd() {
    if (quickAddMode==='category') {
        const name=document.getElementById('quickCatName').value.trim(); if(!name){alert('Nome é obrigatório');return;}
        const r=await fetch('/admin/materials/quick-store-category',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({name})});
        const d=await r.json(); if(d.success){const o=new Option(d.category.name,d.category.name);o.dataset.id=d.category.id;document.getElementById('newMatSpec').add(o);document.getElementById('newMatSpec').value=d.category.name;document.getElementById('quickCatName').value='';backToMatStep1();}else{alert(d.error||'Erro');}
    } else {
        const name=document.getElementById('quickUnitName').value.trim(),abbr=document.getElementById('quickUnitAbbr').value.trim(); if(!name||!abbr){alert('Preencha nome e abreviação');return;}
        const r=await fetch('/admin/materials/quick-store-unit',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({name,abbreviation:abbr})});
        const d=await r.json(); if(d.success){const o=new Option(`${d.unit.name} (${d.unit.abbreviation})`,d.unit.id);o.dataset.abbr=d.unit.abbreviation;document.getElementById('newMatUnit').add(o);document.getElementById('newMatUnit').value=d.unit.id;document.getElementById('quickUnitName').value='';document.getElementById('quickUnitAbbr').value='';backToMatStep1();}else{alert(d.error||'Erro');}
    }
}
document.getElementById('newMaterialModal').addEventListener('hidden.bs.modal', function(){backToMatStep1();['newMatName','newMatClassification'].forEach(id=>document.getElementById(id).value='');document.getElementById('newMatSpec').value='';document.getElementById('newMatUnit').value='';});

// Tipo do pedido toggle
document.querySelectorAll('input[name="order_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const hint = document.getElementById('orderTypeHint');
        if (this.value === 'service') {
            hint.textContent = 'Pedido de serviço: na cotação, o fornecedor poderá enviar um PDF com a lista de materiais necessários.';
        } else {
            hint.textContent = 'Pedido de materiais: siga o fluxo padrão de cotação.';
        }
    });
});

// Esconder botão fixo mobile quando o modal de revisão estiver aberto
const reviewModal = document.getElementById('reviewModal');
const mobileBtn = document.getElementById('mobileReviewBtn');
if (reviewModal && mobileBtn) {
    reviewModal.addEventListener('show.bs.modal', () => { mobileBtn.style.display = 'none'; });
    reviewModal.addEventListener('hidden.bs.modal', () => { mobileBtn.style.display = ''; });
}
</script>

<?php require ROOT_PATH . '/app/Views/admin/orders/partials/stock_check_modal.php'; ?>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
