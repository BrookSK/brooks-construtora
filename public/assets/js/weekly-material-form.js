/**
 * Formulário público de Solicitação Semanal de Materiais.
 * Espelha o comportamento da tela "Novo Pedido" (admin/orders/create),
 * porém acessado via link único (token) e com urgência calculada
 * automaticamente pela data da necessidade.
 *
 * Depende de: SearchableSelect, Bootstrap.
 * Variáveis globais definidas no HTML: WEEKLY_TOKEN, WEEKLY_MIN_ADVANCE, WEEKLY_MIN_DATE, WEEKLY_MATERIALS.
 */
(function () {
    const TOKEN = window.WEEKLY_TOKEN;
    const MIN_ADVANCE = parseInt(window.WEEKLY_MIN_ADVANCE || 15, 10);
    const MIN_DATE = window.WEEKLY_MIN_DATE || '';
    const CYCLE_END = window.WEEKLY_CYCLE_END || '';
    const OBRA_TYPE = window.WEEKLY_OBRA_TYPE || '';
    const materials = window.WEEKLY_MATERIALS || [];
    let itemCount = 0;
    let audioBlob = null;

    // ─── Tipo de solicitação: Material x Serviço ─────────────────────────
    // Alterna o hidden `order_type` e ajusta rótulos da UI. O padrão é
    // "material" (comportamento anterior preservado).
    const orderTypeInput = document.getElementById('orderTypeInput');
    function currentOrderType() {
        return (orderTypeInput && orderTypeInput.value) || 'material';
    }
    function applyOrderTypeLabels(type) {
        const isService = type === 'service';
        const setText = function (id, txt) {
            const el = document.getElementById(id);
            if (el) el.textContent = txt;
        };
        // Cabeçalho da seção de itens e botões
        setText('itemsCardTitle', isService ? 'Itens do Serviço' : 'Itens do Pedido');
        setText('newMaterialBtnLabel', isService ? 'Novo Serviço' : 'Novo Material');
        // Cabeçalho da coluna principal da tabela (desktop)
        setText('colItemName', isService ? 'Serviço' : 'Material');
        // Empty states
        setText('emptyDesktop', isService
            ? 'Clique em "Adicionar Item" para começar'
            : 'Clique em "Adicionar Item" para começar');
        // Modal "Novo …"
        setText('newMaterialModalTitle', isService ? 'Novo Serviço' : 'Novo Material');
        setText('newMatNameLabel', isService ? 'Nome do Serviço *' : 'Nome do Material *');
        setText('newMatSpecLabel', isService ? 'Categoria (Tipo)' : 'Especificação (Tipo)');
        setText('saveMaterialBtnLabel', isService ? 'Salvar Serviço' : 'Salvar Material');

        // Alerta de ajuda da seção de itens
        const help = document.getElementById('itemsHelpAlert');
        if (help) {
            const alvo = isService ? 'serviços que precisam' : 'materiais que precisam';
            help.innerHTML = '<i class="bi bi-info-circle text-primary"></i> ' +
                'A <strong>Data (opcional)</strong> é para ' + alvo + ' de <strong>maior antecedência</strong>: ' +
                'informe uma data específica, sempre <strong>até a data máxima informada acima</strong> ' +
                '(campo "Preciso até"). Se deixar em branco, será usada essa data máxima.';
        }

        // Placeholder de busca nas linhas já existentes (desktop e mobile)
        document.querySelectorAll('.ss-input').forEach(function (inp) {
            inp.setAttribute('placeholder', isService ? 'Buscar serviço...' : 'Buscar material...');
        });
        // Dica do mobile ("Busque um material acima") nos cards sem seleção
        document.querySelectorAll('.item-details').forEach(function (el) {
            const hint = el.querySelector('.text-muted');
            if (hint && (hint.textContent || '').indexOf('Busque') === 0) {
                hint.textContent = isService ? 'Busque um serviço acima' : 'Busque um material acima';
            }
        });
    }
    // Rótulo dinâmico usado ao montar novas linhas de item.
    function itemSearchPlaceholder() {
        return currentOrderType() === 'service' ? 'Buscar serviço...' : 'Buscar material...';
    }
    function itemSearchHint() {
        return currentOrderType() === 'service' ? 'Busque um serviço acima' : 'Busque um material acima';
    }
    (function initOrderTypeTabs() {
        const tabs = document.querySelectorAll('#orderTypeTabs [data-order-type]');
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                const type = tab.getAttribute('data-order-type') || 'material';
                if (orderTypeInput) orderTypeInput.value = type;
                tabs.forEach(function (t) { t.classList.remove('active'); });
                tab.classList.add('active');
                applyOrderTypeLabels(type);
                if (typeof scheduleSave === 'function') scheduleSave();
            });
        });
    })();

    // ─── Data da necessidade: mínimo obrigatório (15 dias à frente) ──────
    // O responsável não pode escolher uma data anterior ao mínimo.
    const neededDate = document.getElementById('neededDate');
    // Data máxima dos itens = valor de "Preciso até" (a pessoa pode antecipar,
    // mas nunca ultrapassar a data máxima informada).
    function currentMaxDate() {
        return (neededDate && neededDate.value) ? neededDate.value : (CYCLE_END || '');
    }
    function syncItemDateBounds() {
        const max = currentMaxDate();
        document.querySelectorAll('.item-date, .date-mobile').forEach(function (el) {
            if (max) el.max = max;
            if (MIN_DATE) el.min = MIN_DATE;
            // Se a data do item ficou acima da nova máxima, ajusta para a máxima
            if (max && el.value && el.value > max) el.value = max;
        });
    }
    function enforceMinDate() {
        if (!neededDate || !MIN_DATE) return;
        if (!neededDate.value || neededDate.value < MIN_DATE) {
            neededDate.value = MIN_DATE;
        }
        syncItemDateBounds();
    }
    if (neededDate) {
        neededDate.addEventListener('change', enforceMinDate);
        neededDate.addEventListener('blur', enforceMinDate);
    }

    // ─── Itens ───────────────────────────────────────────────────────────
    function updateItemCount() {
        const count = document.querySelectorAll('#itemsBodyDesktop tr').length;
        document.getElementById('itemCountBadge').textContent = count;
        document.getElementById('emptyDesktop').style.display = count ? 'none' : '';
        document.getElementById('emptyMobile').style.display = count ? 'none' : '';
    }

    function buildMaterialOptions(prefill) {
        let opts = '<option value="">-- Selecione --</option>';
        materials.forEach(function (m) {
            const label = m.name + (m.classification ? ' - ' + m.classification : '') + (m.specification ? ' (' + m.specification + ')' : '');
            const selected = prefill && prefill.id == m.id ? 'selected' : '';
            opts += '<option value="' + m.id + '" data-name="' + m.name + '" data-spec="' + (m.specification || m.category_name || '') + '" data-class="' + (m.classification || '') + '" data-unit="' + (m.unit_abbr || m.unit_name || '') + '" ' + selected + '>' + label + '</option>';
        });
        return opts;
    }

    function addItem(prefill) {
        prefill = prefill || null;
        itemCount++;
        const idx = itemCount;
        const opts = buildMaterialOptions(prefill);

        const tr = document.createElement('tr');
        tr.id = 'item-row-' + idx;
        tr.innerHTML =
            '<td>' +
                '<select class="material-select-raw" id="mat-select-' + idx + '" style="display:none;">' + opts + '</select>' +
                '<div id="mat-ss-' + idx + '"></div>' +
                '<input type="hidden" name="items[' + idx + '][material_id]" id="mid-' + idx + '" value="' + (prefill && prefill.id || '') + '">' +
                '<input type="hidden" name="items[' + idx + '][material_name]" id="mname-' + idx + '" value="' + (prefill && prefill.name || '') + '">' +
            '</td>' +
            '<td><input type="text" class="form-control form-control-sm" name="items[' + idx + '][specification]" id="spec-' + idx + '" value="' + (prefill && prefill.specification || '') + '" readonly></td>' +
            '<td><input type="text" class="form-control form-control-sm" name="items[' + idx + '][classification]" id="class-' + idx + '" value="' + (prefill && prefill.classification || '') + '" readonly></td>' +
            '<input type="hidden" name="items[' + idx + '][unit]" id="unit-' + idx + '" value="' + (prefill && prefill.unit || '') + '">' +
            '<td><input type="number" class="form-control form-control-sm" name="items[' + idx + '][quantity]" min="0.01" step="0.01" value="' + (prefill && prefill.quantity || 1) + '" required></td>' +
            '<td><input type="date" class="form-control form-control-sm item-date" name="items[' + idx + '][needed_date]" id="idate-' + idx + '"' + (MIN_DATE ? ' min="' + MIN_DATE + '"' : '') + (currentMaxDate() ? ' max="' + currentMaxDate() + '"' : '') + ' title="Data específica (opcional) — até a data máxima informada acima"></td>' +
            '<td><button type="button" class="btn btn-sm btn-outline-danger" data-remove="' + idx + '"><i class="bi bi-trash"></i></button></td>';
        document.getElementById('itemsBodyDesktop').appendChild(tr);

        const matSS = new SearchableSelect(document.getElementById('mat-select-' + idx), {
            placeholder: itemSearchPlaceholder(),
            onSelect: function (value, text, dataset) {
                document.getElementById('mid-' + idx).value = String(value).indexOf('epi-') === 0 ? '' : value;
                document.getElementById('mname-' + idx).value = (dataset && dataset.name) || text || '';
                document.getElementById('spec-' + idx).value = (dataset && dataset.spec) || '';
                document.getElementById('class-' + idx).value = (dataset && dataset.class) || '';
                document.getElementById('unit-' + idx).value = (dataset && dataset.unit) || '';
                updateMobileDetails(idx, dataset);
            }
        });
        if (prefill && prefill.id) matSS.setValue(prefill.id);

        const card = document.createElement('div');
        card.className = 'item-card';
        card.id = 'item-card-' + idx;
        card.innerHTML =
            '<span class="item-number">#' + idx + '</span>' +
            '<div class="d-flex gap-2 align-items-center mb-2">' +
                '<select class="material-select-raw-m" id="mat-select-m-' + idx + '" style="display:none;">' + opts + '</select>' +
                '<div class="flex-grow-1" id="mat-ss-m-' + idx + '"></div>' +
                '<button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0" data-remove="' + idx + '"><i class="bi bi-trash"></i></button>' +
            '</div>' +
            '<div class="item-details" id="details-m-' + idx + '">' +
                (prefill ? '<span class="badge bg-light text-dark">' + (prefill.specification || '') + '</span><span class="badge bg-light text-dark">' + (prefill.classification || '') + '</span>' : '<span class="text-muted" style="font-size:0.75rem;">' + itemSearchHint() + '</span>') +
            '</div>' +
            '<div class="d-flex align-items-center gap-2 mt-2">' +
                '<label class="form-label mb-0 small fw-bold">Qtd:</label>' +
                '<input type="number" class="form-control form-control-sm qty-mobile" style="max-width:100px;" data-idx="' + idx + '" min="0.01" step="0.01" value="' + (prefill && prefill.quantity || 1) + '">' +
            '</div>' +
            '<div class="d-flex align-items-center gap-2 mt-2">' +
                '<label class="form-label mb-0 small fw-bold">Data (opc.):</label>' +
                '<input type="date" class="form-control form-control-sm date-mobile" style="max-width:170px;" data-idx="' + idx + '"' + (MIN_DATE ? ' min="' + MIN_DATE + '"' : '') + (currentMaxDate() ? ' max="' + currentMaxDate() + '"' : '') + '>' +
            '</div>';
        document.getElementById('itemsBodyMobile').appendChild(card);

        const matSSM = new SearchableSelect(document.getElementById('mat-select-m-' + idx), {
            placeholder: itemSearchPlaceholder(),
            onSelect: function (value, text, dataset) {
                document.getElementById('mid-' + idx).value = String(value).indexOf('epi-') === 0 ? '' : value;
                document.getElementById('mname-' + idx).value = (dataset && dataset.name) || text || '';
                document.getElementById('spec-' + idx).value = (dataset && dataset.spec) || '';
                document.getElementById('class-' + idx).value = (dataset && dataset.class) || '';
                document.getElementById('unit-' + idx).value = (dataset && dataset.unit) || '';
                updateMobileDetails(idx, dataset);
            }
        });
        if (prefill && prefill.id) matSSM.setValue(prefill.id);

        card.querySelector('.qty-mobile').addEventListener('input', function () {
            const d = document.querySelector('#item-row-' + idx + ' [name="items[' + idx + '][quantity]"]');
            if (d) d.value = this.value;
        });
        // Sincroniza data (mobile → desktop, que é o campo submetido)
        const dm = card.querySelector('.date-mobile');
        if (dm) dm.addEventListener('input', function () {
            const di = document.getElementById('idate-' + idx);
            if (di) di.value = this.value;
        });
        const di = document.getElementById('idate-' + idx);
        if (di) di.addEventListener('input', function () {
            if (dm) dm.value = this.value;
        });
        tr.querySelector('[name="items[' + idx + '][quantity]"]').addEventListener('input', function () {
            const m = card.querySelector('.qty-mobile');
            if (m) m.value = this.value;
        });

        updateItemCount();
    }

    function removeItem(idx) {
        const r = document.getElementById('item-row-' + idx);
        const c = document.getElementById('item-card-' + idx);
        if (r) r.remove();
        if (c) c.remove();
        updateItemCount();
    }

    // Delegação para botões de remover (desktop e mobile)
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-remove]');
        if (btn) removeItem(parseInt(btn.getAttribute('data-remove'), 10));
    });

    function updateMobileDetails(idx, ds) {
        const el = document.getElementById('details-m-' + idx);
        if (!el) return;
        ds = ds || {};
        if (ds.spec || ds.class) {
            el.innerHTML = '';
            if (ds.spec) el.innerHTML += '<span class="badge bg-light text-dark">' + ds.spec + '</span>';
            if (ds.class) el.innerHTML += '<span class="badge bg-light text-dark">' + ds.class + '</span>';
        } else {
            el.innerHTML = '<span class="text-muted" style="font-size:0.75rem;">' + itemSearchHint() + '</span>';
        }
    }

    const addBtn = document.getElementById('addItemBtn');
    if (addBtn) addBtn.addEventListener('click', function () { addItem(); });
    const addBtnInline = document.getElementById('addItemBtnInline');
    if (addBtnInline) addBtnInline.addEventListener('click', function () { addItem(); });

    // ─── Aplicar lista de materiais pré-definida ─────────────────────────
    // Carrega de uma vez os materiais de uma lista, já com quantidades
    // sugeridas, reaproveitando o addItem(prefill) acima. É só uma sugestão:
    // o responsável revisa, remove o que não precisa e ajusta.
    async function applyMaterialList() {
        const sel = document.getElementById('materialListSelect');
        const statusEl = document.getElementById('listApplyStatus');
        const btn = document.getElementById('applyListBtn');
        if (!sel || !sel.value) { alert('Selecione uma lista primeiro.'); return; }

        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
            let url = '/lista-semanal/lista-predefinida/' + encodeURIComponent(TOKEN) +
                '?template_id=' + encodeURIComponent(sel.value);
            if (OBRA_TYPE) url += '&obra_type=' + encodeURIComponent(OBRA_TYPE);

            const resp = await fetch(url);
            const data = await resp.json();

            if (!data.success || !Array.isArray(data.items)) {
                statusEl.style.display = 'block';
                statusEl.innerHTML = '<div class="alert alert-danger small py-2 mb-0"><i class="bi bi-x-circle"></i> ' + (data.error || 'Não foi possível carregar a lista.') + '</div>';
                return;
            }

            if (data.items.length === 0) {
                statusEl.style.display = 'block';
                let msg = 'Esta lista não tem itens ativos.';
                if (OBRA_TYPE) {
                    const tipoLabel = OBRA_TYPE === 'construction' ? 'Construção' : 'Reforma';
                    msg = 'Esta lista não tem itens ativos compatíveis com o tipo da obra (' + tipoLabel + ').';
                }
                statusEl.innerHTML = '<div class="alert alert-info small py-2 mb-0"><i class="bi bi-info-circle"></i> ' + msg + '</div>';
                return;
            }

            let added = 0;
            data.items.forEach(function (it) {
                addItem({
                    id: it.id || '',
                    name: it.name || '',
                    specification: it.specification || '',
                    classification: it.classification || '',
                    unit: it.unit || '',
                    quantity: it.quantity || 1
                });
                added++;
            });

            let filterMsg = '';
            if (data.filtered_by_obra_type) {
                const tipoLabel = data.filtered_by_obra_type === 'construction' ? 'Construção' : 'Reforma';
                filterMsg = ' <small class="text-primary">(filtrado para ' + tipoLabel + ')</small>';
            }

            statusEl.style.display = 'block';
            statusEl.innerHTML = '<div class="alert alert-success small py-2 mb-0"><i class="bi bi-check-circle"></i> <strong>' + added + ' item(ns)</strong> carregados da lista <strong>' + ((data.template && data.template.name) || '') + '</strong>' + filterMsg + '. Revise, remova o que não precisa e ajuste as quantidades antes de enviar.</div>';
        } catch (e) {
            statusEl.style.display = 'block';
            statusEl.innerHTML = '<div class="alert alert-danger small py-2 mb-0"><i class="bi bi-x-circle"></i> Erro de conexão ao carregar a lista.</div>';
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }

    const applyListBtn = document.getElementById('applyListBtn');
    if (applyListBtn) applyListBtn.addEventListener('click', applyMaterialList);

    // ─── Novo material inline (endpoint público) ─────────────────────────
    const saveMatBtn = document.getElementById('saveMaterialBtn');
    if (saveMatBtn) saveMatBtn.addEventListener('click', async function () {
        const name = document.getElementById('newMatName').value.trim();
        if (!name) { alert('Nome é obrigatório'); return; }
        const specSelect = document.getElementById('newMatSpec');
        const unitSelect = document.getElementById('newMatUnit');
        try {
            const resp = await fetch('/lista-semanal/novo-material/' + TOKEN, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    name: name,
                    specification: specSelect.value,
                    category_id: (specSelect.selectedOptions[0] && specSelect.selectedOptions[0].dataset.id) || '',
                    unit_id: unitSelect.value || '',
                    classification: document.getElementById('newMatClassification').value
                })
            });
            const data = await resp.json();
            if (data.success) {
                const unitAbbr = (unitSelect.selectedOptions[0] && unitSelect.selectedOptions[0].dataset.abbr) || '';
                materials.push({ id: data.material.id, name: data.material.name, specification: data.material.specification || specSelect.value, classification: data.material.classification || '', unit_abbr: unitAbbr, unit_name: '', category_name: specSelect.value });
                addItem({ id: data.material.id, name: data.material.name, specification: specSelect.value, classification: data.material.classification || '', unit: unitAbbr, quantity: 1 });
                bootstrap.Modal.getInstance(document.getElementById('newMaterialModal')).hide();
                document.getElementById('newMatName').value = '';
                document.getElementById('newMatClassification').value = '';
                specSelect.value = ''; unitSelect.value = '';
            } else { alert(data.error || 'Erro'); }
        } catch (e) { alert('Erro de conexão'); }
    });

    // ─── Importar PDF/imagem com IA (REMOVIDO nesta tela) ────────────────
    /* Bloco de importação por PDF/IA removido da solicitação semanal.
    window.parsePdfFile = async function () {
        const fileInput = document.getElementById('pdfUpload');
        const statusEl = document.getElementById('pdfStatus');
        if (!fileInput.files.length) { alert('Selecione um arquivo primeiro.'); return; }

        const btn = document.getElementById('parsePdfBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        statusEl.style.display = 'block';
        statusEl.innerHTML = '<div class="alert alert-info small py-2 mb-0"><i class="bi bi-hourglass-split"></i> Analisando documento com IA...</div>';

        const formData = new FormData();
        formData.append('pdf', fileInput.files[0]);

        try {
            const resp = await fetch('/lista-semanal/parse-pdf/' + TOKEN, { method: 'POST', body: formData });
            const data = await resp.json();
            if (data.success && data.materials) {
                let found = 0, notFound = 0; const notFoundItems = [];
                const normalize = function (str) { return (str || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9\s]/g, ' ').replace(/\s+/g, ' ').trim(); };
                data.materials.forEach(function (m) {
                    const mNorm = normalize(m.name);
                    const mWords = mNorm.split(' ').filter(function (w) { return w.length > 2; });
                    let bestMatch = null, bestScore = 0;
                    materials.forEach(function (mat) {
                        const matNorm = normalize(mat.name);
                        if (matNorm === mNorm) { bestMatch = mat; bestScore = 100; return; }
                        if (matNorm.indexOf(mNorm) !== -1 || mNorm.indexOf(matNorm) !== -1) { if (bestScore < 80) { bestMatch = mat; bestScore = 80; } return; }
                        const matWords = matNorm.split(' ').filter(function (w) { return w.length > 2; });
                        let common = 0; mWords.forEach(function (w) { if (matWords.indexOf(w) !== -1) common++; });
                        const score = mWords.length ? (common / mWords.length) * 70 : 0;
                        if (score > bestScore && score >= 40) { bestMatch = mat; bestScore = score; }
                    });
                    if (bestMatch) {
                        found++;
                        addItem({ id: bestMatch.id, name: bestMatch.name, specification: bestMatch.specification || m.specification || '', classification: bestMatch.classification || m.classification || '', unit: bestMatch.unit_abbr || bestMatch.unit_name || m.unit || '', quantity: m.quantity || 1 });
                    } else {
                        notFound++; notFoundItems.push(m);
                    }
                });

                let html = '<div class="alert alert-success small py-2 mb-2"><i class="bi bi-check-circle"></i> <strong>' + data.materials.length + ' materiais</strong> identificados.';
                if (found > 0) html += ' <span class="text-success">' + found + ' vinculados</span>.';
                if (notFound > 0) html += ' <span class="text-warning">' + notFound + ' não encontrados</span>.';
                html += '</div>';

                if (notFound > 0) {
                    html += '<div class="card border-warning mb-2"><div class="card-header bg-warning bg-opacity-10 py-2"><strong class="small">Não encontrados (' + notFound + ')</strong><br><small class="text-muted">Cadastre os que precisar:</small></div><div class="card-body p-0"><div class="table-responsive"><table class="table table-sm mb-0" style="font-size:0.8rem;"><thead><tr><th>Nome</th><th>Espec.</th><th>Class.</th><th>Qtd</th><th></th></tr></thead><tbody>';
                    notFoundItems.forEach(function (m, i) {
                        html += '<tr id="nf-row-' + i + '"><td><input type="text" class="form-control form-control-sm" value="' + m.name + '" id="nf-name-' + i + '"></td><td><input type="text" class="form-control form-control-sm" value="' + (m.specification || '') + '" id="nf-spec-' + i + '" style="width:100px;"></td><td><input type="text" class="form-control form-control-sm" value="' + (m.classification || '') + '" id="nf-class-' + i + '" style="width:80px;"></td><td><input type="number" class="form-control form-control-sm" value="' + (m.quantity || 1) + '" id="nf-qty-' + i + '" style="width:60px;"></td><td><button type="button" class="btn btn-sm btn-outline-success" data-nf="' + i + '"><i class="bi bi-plus-circle"></i></button></td></tr>';
                    });
                    html += '</tbody></table></div></div><div class="card-footer py-2 text-end"><button type="button" class="btn btn-sm btn-success" id="nfRegisterAll"><i class="bi bi-check-all"></i> Cadastrar Todos</button></div></div>';
                }
                statusEl.innerHTML = html;
            } else {
                statusEl.innerHTML = '<div class="alert alert-danger small py-2 mb-0"><i class="bi bi-x-circle"></i> ' + (data.error || 'Erro ao analisar') + '</div>';
            }
        } catch (e) {
            statusEl.innerHTML = '<div class="alert alert-danger small py-2 mb-0"><i class="bi bi-x-circle"></i> Erro de conexão</div>';
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-magic"></i> <span class="d-none d-sm-inline">Analisar</span>';
        fileInput.value = '';
    };

    async function quickRegisterFromPdf(i) {
        const name = (document.getElementById('nf-name-' + i) || {}).value;
        const spec = (document.getElementById('nf-spec-' + i) || {}).value;
        const cls = (document.getElementById('nf-class-' + i) || {}).value;
        const qty = (document.getElementById('nf-qty-' + i) || {}).value || 1;
        if (!name || !name.trim()) { alert('Nome é obrigatório'); return; }
        try {
            const resp = await fetch('/lista-semanal/novo-material/' + TOKEN, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ name: name.trim(), specification: spec || '', classification: cls || '', unit_id: '', category_id: '' })
            });
            const data = await resp.json();
            if (data.success) {
                materials.push({ id: data.material.id, name: data.material.name, specification: spec || '', classification: cls || '', unit_abbr: '', unit_name: '', category_name: spec || '' });
                addItem({ id: data.material.id, name: data.material.name, specification: spec || '', classification: cls || '', unit: '', quantity: parseFloat(qty) || 1 });
                const row = document.getElementById('nf-row-' + i);
                if (row) { row.style.opacity = '0.4'; row.style.textDecoration = 'line-through'; const b = row.querySelector('button'); b.innerHTML = '<i class="bi bi-check text-success"></i>'; b.disabled = true; }
            } else { alert(data.error || 'Erro'); }
        } catch (e) { alert('Erro de conexão'); }
    }

    document.addEventListener('click', async function (e) {
        const nfBtn = e.target.closest('[data-nf]');
        if (nfBtn) { await quickRegisterFromPdf(parseInt(nfBtn.getAttribute('data-nf'), 10)); return; }
        if (e.target.closest('#nfRegisterAll')) {
            const rows = document.querySelectorAll('[id^="nf-row-"]');
            for (const row of rows) {
                const btn = row.querySelector('button');
                if (btn && !btn.disabled) { await quickRegisterFromPdf(parseInt(row.id.replace('nf-row-', ''), 10)); }
            }
        }
    });
    */

    // ─── Áudio inline ────────────────────────────────────────────────────
    const audioWidget = document.getElementById('audio-recorder-weekly');
    let mediaRecorder = null, audioChunks = [], timerInt = null, recordingStart = 0;
    if (audioWidget) {
        audioWidget.innerHTML =
            '<div class="audio-recorder-widget">' +
                '<div id="audioPlayback"></div>' +
                '<div id="audioIdle" class="d-flex align-items-center gap-2">' +
                    '<button type="button" class="btn btn-outline-danger btn-sm" id="btnRec" style="width:100%; padding:0.6rem;"><i class="bi bi-mic-fill"></i> Toque aqui para gravar áudio</button>' +
                '</div>' +
                '<div id="audioRecording" class="d-none align-items-center gap-2">' +
                    '<span class="recording-indicator"><span class="recording-dot"></span> <span id="recTimer">0:00</span></span>' +
                    '<button type="button" class="btn btn-danger btn-sm" id="btnStop"><i class="bi bi-stop-fill"></i> Parar</button>' +
                    '<button type="button" class="btn btn-outline-secondary btn-sm" id="btnCancel"><i class="bi bi-x-lg"></i></button>' +
                '</div>' +
            '</div>';
        document.getElementById('btnRec').addEventListener('click', startRec);
        document.getElementById('btnStop').addEventListener('click', stopRec);
        document.getElementById('btnCancel').addEventListener('click', cancelRec);
    }

    async function startRec() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            audioChunks = [];
            const mimeTypes = ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus'];
            let mimeType = '';
            for (let i = 0; i < mimeTypes.length; i++) { if (MediaRecorder.isTypeSupported(mimeTypes[i])) { mimeType = mimeTypes[i]; break; } }
            mediaRecorder = new MediaRecorder(stream, mimeType ? { mimeType: mimeType } : {});
            mediaRecorder.ondataavailable = function (e) { if (e.data.size > 0) audioChunks.push(e.data); };
            mediaRecorder.onstop = function () {
                stream.getTracks().forEach(function (t) { t.stop(); });
                audioBlob = new Blob(audioChunks, { type: mediaRecorder.mimeType || 'audio/webm' });
                showPlayback();
            };
            mediaRecorder.start(1000);
            recordingStart = Date.now();
            timerInt = setInterval(function () {
                const s = Math.floor((Date.now() - recordingStart) / 1000);
                document.getElementById('recTimer').textContent = Math.floor(s / 60) + ':' + (s % 60 < 10 ? '0' : '') + s % 60;
            }, 500);
            document.getElementById('audioIdle').classList.add('d-none');
            document.getElementById('audioRecording').classList.remove('d-none');
            document.getElementById('audioRecording').classList.add('d-flex');
        } catch (e) { alert('Não foi possível acessar o microfone.'); }
    }
    function stopRec() {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') mediaRecorder.stop();
        clearInterval(timerInt);
        document.getElementById('audioRecording').classList.add('d-none');
        document.getElementById('audioRecording').classList.remove('d-flex');
    }
    function cancelRec() {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.ondataavailable = null;
            mediaRecorder.onstop = function () { mediaRecorder.stream.getTracks().forEach(function (t) { t.stop(); }); };
            mediaRecorder.stop();
        }
        audioChunks = []; audioBlob = null; clearInterval(timerInt);
        document.getElementById('audioRecording').classList.add('d-none');
        document.getElementById('audioRecording').classList.remove('d-flex');
        document.getElementById('audioIdle').classList.remove('d-none');
        document.getElementById('audioPlayback').innerHTML = '';
        document.getElementById('audioUploaded').value = '0';
    }
    function showPlayback() {
        const url = URL.createObjectURL(audioBlob);
        document.getElementById('audioPlayback').innerHTML =
            '<div class="d-flex align-items-center gap-2 p-2 border rounded mb-2 bg-white">' +
                '<audio controls src="' + url + '" style="height:32px; flex-grow:1;"></audio>' +
                '<button type="button" class="btn btn-sm btn-outline-danger" id="btnRemoveAudio"><i class="bi bi-trash"></i></button>' +
            '</div>';
        document.getElementById('audioIdle').classList.remove('d-none');
        document.getElementById('audioUploaded').value = '1';
        document.getElementById('btnRemoveAudio').addEventListener('click', function () {
            audioBlob = null;
            document.getElementById('audioPlayback').innerHTML = '';
            document.getElementById('audioUploaded').value = '0';
        });
    }

    // ─── Revisão e envio ─────────────────────────────────────────────────
    window.showReview = function () {
        const rows = document.querySelectorAll('#itemsBodyDesktop tr');
        if (rows.length === 0) { alert('Adicione pelo menos um item.'); return; }

        const siteSelect = document.getElementById('constructionSiteSelect');
        if (siteSelect && !siteSelect.value) { alert('Selecione a obra.'); siteSelect.focus(); return; }
        if (!neededDate.value) { alert('Informe a data em que precisa do material.'); neededDate.focus(); return; }

        // Valida material por linha e aponta exatamente qual está sem seleção,
        // rolando até ela e destacando. Evita o caso da pessoa não perceber que
        // um item ficou sem material e o envio "não ir".
        var firstInvalidRow = null, invalidCount = 0;
        rows.forEach(function (row) {
            var nameInput = row.querySelector('[id^="mname-"]');
            var hasName = nameInput && nameInput.value && nameInput.value.trim();
            var idx = (row.id || '').replace('item-row-', '');
            var card = document.getElementById('item-card-' + idx);
            if (!hasName) {
                invalidCount++;
                if (!firstInvalidRow) firstInvalidRow = card || row;
                row.style.outline = '2px solid #dc3545';
                if (card) card.style.outline = '2px solid #dc3545';
            } else {
                row.style.outline = '';
                if (card) card.style.outline = '';
            }
        });
        if (invalidCount > 0) {
            alert(invalidCount === 1
                ? 'Um item está sem material selecionado. Escolha o material (ou remova a linha destacada em vermelho) e tente novamente.'
                : invalidCount + ' itens estão sem material selecionado. Escolha o material (ou remova as linhas destacadas em vermelho) e tente novamente.');
            if (firstInvalidRow && firstInvalidRow.scrollIntoView) {
                firstInvalidRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        let qtyValid = true, invalidName = '';
        rows.forEach(function (row) {
            const q = parseFloat(row.querySelector('[name*="[quantity]"]') && row.querySelector('[name*="[quantity]"]').value) || 0;
            if (q < 0.01) { qtyValid = false; invalidName = (row.querySelector('[id^="mname-"]') || {}).value || 'Item'; }
        });
        if (!qtyValid) { alert('A quantidade de "' + invalidName + '" deve ser no mínimo 0,01.'); return; }

        let html = '<h6 class="mb-3">Itens da solicitação:</h6>';
        if (siteSelect) {
            html += '<div class="alert alert-light py-2 mb-2"><i class="bi bi-buildings"></i> <strong>Obra:</strong> ' + siteSelect.options[siteSelect.selectedIndex].text + '</div>';
        }
        html += '<div class="alert alert-light py-2 mb-2"><i class="bi bi-calendar-check"></i> <strong>Necessário até:</strong> ' + new Date(neededDate.value + 'T00:00:00').toLocaleDateString('pt-BR') + '</div>';

        const colName = currentOrderType() === 'service' ? 'Serviço' : 'Material';
        html += '<table class="table table-sm table-bordered"><thead><tr><th>#</th><th>' + colName + '</th><th>Espec.</th><th>Class.</th><th class="text-center">Qtd</th><th>Data</th></tr></thead><tbody>';
        let count = 0;
        rows.forEach(function (row) {
            count++;
            const name = (row.querySelector('[id^="mname-"]') || {}).value || '-';
            const spec = (row.querySelector('[id^="spec-"]') || {}).value || '-';
            const cls = (row.querySelector('[id^="class-"]') || {}).value || '-';
            const qty = (row.querySelector('[name*="[quantity]"]') || {}).value || '1';
            const idate = (row.querySelector('[name*="[needed_date]"]') || {}).value || '';
            const idateFmt = idate ? new Date(idate + 'T00:00:00').toLocaleDateString('pt-BR') : '<span class="text-muted">—</span>';
            html += '<tr><td>' + count + '</td><td><strong>' + name + '</strong></td><td>' + spec + '</td><td>' + cls + '</td><td class="text-center">' + qty + '</td><td>' + idateFmt + '</td></tr>';
        });
        html += '</tbody></table>';

        const obs = (document.querySelector('[name="notes"]') || {}).value;
        if (obs) html += '<div class="alert alert-light mt-2"><strong>Observações:</strong> ' + obs + '</div>';
        const tipoLabel = currentOrderType() === 'service' ? 'Serviço' : 'Material';
        html += '<div class="alert alert-info mt-2 small"><i class="bi bi-info-circle"></i> Ao confirmar, será criado um Pedido de ' + tipoLabel + ' no sistema e enviado para cotação.</div>';

        document.getElementById('reviewBody').innerHTML = html;
        // getOrCreateInstance reaproveita a instância já associada ao elemento.
        // Usar `new bootstrap.Modal(...)` a cada clique cria instâncias duplicadas
        // sobre o mesmo elemento, o que deixa o modal travado a partir do 2º clique.
        bootstrap.Modal.getOrCreateInstance(document.getElementById('reviewModal')).show();
    };

    window.confirmSubmit = function () {
        const form = document.getElementById('orderForm');
        // Garante o modo normal (gera pedido)
        const modeEl = document.getElementById('submitModeInput');
        if (modeEl) modeEl.value = 'normal';
        if (audioBlob) {
            const dt = new DataTransfer();
            dt.items.add(new File([audioBlob], 'audio.webm', { type: audioBlob.type }));
            let audioInput = document.getElementById('audioFileInput');
            if (!audioInput) {
                audioInput = document.createElement('input');
                audioInput.type = 'file'; audioInput.name = 'audio'; audioInput.id = 'audioFileInput';
                audioInput.style.display = 'none';
                form.appendChild(audioInput);
            }
            audioInput.files = dt.files;
        }
        form.submit();
    };

    // ─── Encerrar sem itens (stand-by) ───────────────────────────────────
    // Abre o modal de confirmação. Não valida itens (o objetivo é justamente
    // encerrar sem nenhum). A ação é irreversível pelo próprio link.
    window.showNoItems = function () {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('noItemsModal')).show();
    };

    window.confirmNoItems = function () {
        const form = document.getElementById('orderForm');
        const modeEl = document.getElementById('submitModeInput');
        if (modeEl) modeEl.value = 'no_items';
        // Encerramento sem itens não envia áudio nem materiais.
        clearDraft();
        form.submit();
    };

    // ─── Autosave (localStorage) ─────────────────────────────────────────
    const SAVE_KEY = 'wm_draft_' + TOKEN;
    let saveTimer = null;

    function getFormSnapshot() {
        const rows = document.querySelectorAll('#itemsBodyDesktop tr');
        const items = [];
        rows.forEach(function (row) {
            items.push({
                id:             (row.querySelector('[id^="mid-"]') || {}).value || '',
                name:           (row.querySelector('[id^="mname-"]') || {}).value || '',
                specification:  (row.querySelector('[id^="spec-"]') || {}).value || '',
                classification: (row.querySelector('[id^="class-"]') || {}).value || '',
                unit:           (row.querySelector('[id^="unit-"]') || {}).value || '',
                quantity:       (row.querySelector('[name*="[quantity]"]') || {}).value || '1',
                needed_date:    (row.querySelector('[name*="[needed_date]"]') || {}).value || '',
            });
        });
        const siteEl = document.getElementById('constructionSiteSelect');
        return {
            items:       items,
            order_type:  currentOrderType(),
            needed_date: neededDate ? neededDate.value : '',
            site_id:     siteEl ? siteEl.value : '',
            notes:       (document.querySelector('[name="notes"]') || {}).value || '',
            // Marca de tempo (epoch ms) usada para decidir qual rascunho é o mais
            // recente ao mesclar o localStorage com a cópia salva no servidor.
            _savedAt:    Date.now(),
        };
    }

    function draftHasContent(draft) {
        if (!draft || !Array.isArray(draft.items)) return false;
        return draft.items.some(function (it) {
            return (it.name && it.name.trim())
                || (it.id && String(it.id).trim())
                || (it.specification && it.specification.trim());
        });
    }

    function showSaveIndicator(status) {
        let el = document.getElementById('autosaveIndicator');
        if (!el) return;
        if (status === 'saving') {
            el.innerHTML = '<i class="bi bi-cloud-upload text-muted"></i> <span class="text-muted">Salvando...</span>';
        } else {
            el.innerHTML = '<i class="bi bi-cloud-check text-success"></i> <span class="text-success">Rascunho salvo</span>';
            setTimeout(function () {
                if (el) el.innerHTML = '<i class="bi bi-cloud-check text-muted opacity-50"></i> <span class="text-muted opacity-50">Rascunho salvo</span>';
            }, 2500);
        }
    }

    function saveDraft() {
        var snapshot = getFormSnapshot();
        // 1) localStorage (fonte imediata, offline-first). Preserva o
        //    comportamento atual — não perde os rascunhos já existentes.
        try {
            showSaveIndicator('saving');
            localStorage.setItem(SAVE_KEY, JSON.stringify(snapshot));
        } catch (e) { /* quota ou aba privada */ }
        // 2) servidor (para funcionar em qualquer dispositivo). Só envia se
        //    tiver algum conteúdo, para não sobrescrever com rascunho vazio.
        if (draftHasContent(snapshot)) {
            pushDraftToServer(snapshot);
        } else {
            showSaveIndicator('saved');
        }
    }

    function pushDraftToServer(snapshot) {
        try {
            fetch('/lista-semanal/rascunho/salvar/' + TOKEN, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(snapshot),
                keepalive: true,
            }).then(function () {
                showSaveIndicator('saved');
            }).catch(function () {
                // Falha de rede não é crítica: o localStorage já guardou.
                showSaveIndicator('saved');
            });
        } catch (e) {
            showSaveIndicator('saved');
        }
    }

    function fetchServerDraft() {
        return fetch('/lista-semanal/rascunho/' + TOKEN, { method: 'GET' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (res) {
                return (res && res.success && res.draft) ? res.draft : null;
            })
            .catch(function () { return null; });
    }

    function scheduleSave() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(saveDraft, 800);
    }

    function clearDraft() {
        try { localStorage.removeItem(SAVE_KEY); } catch (e) {}
        // Limpa também no servidor (não bloqueia; best-effort).
        try {
            fetch('/lista-semanal/rascunho/salvar/' + TOKEN, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ items: [], _cleared: true }),
                keepalive: true,
            }).catch(function () {});
        } catch (e) {}
    }

    function readLocalDraft() {
        var raw;
        try { raw = localStorage.getItem(SAVE_KEY); } catch (e) { return null; }
        if (!raw) return null;
        try { return JSON.parse(raw); } catch (e) { return null; }
    }

    // Restauração: mescla o rascunho local (localStorage) com o do servidor,
    // escolhendo o MAIS RECENTE pelo carimbo _savedAt. Assim:
    //  - quem já tinha rascunho no navegador não perde nada;
    //  - o rascunho passa a estar disponível em qualquer dispositivo;
    //  - se o local existir e o servidor não, migra o local para o servidor.
    function restoreDraft() {
        var local = readLocalDraft();

        fetchServerDraft().then(function (server) {
            var chosen = pickMostRecent(local, server);
            if (!draftHasContent(chosen)) return;

            // Se o escolhido foi o LOCAL (e o servidor está vazio/mais antigo),
            // sobe a cópia local para o servidor — sem esperar resposta.
            if (chosen === local) {
                try { pushDraftToServer(local); } catch (e) {}
            }

            var banner = document.getElementById('draftRestoreBanner');
            if (banner) {
                banner.classList.remove('d-none');
                document.getElementById('draftRestoreBtn').addEventListener('click', function () {
                    applyDraft(chosen);
                    banner.classList.add('d-none');
                });
                document.getElementById('draftDiscardBtn').addEventListener('click', function () {
                    clearDraft();
                    banner.classList.add('d-none');
                });
            } else {
                applyDraft(chosen);
            }
        });
    }

    function pickMostRecent(a, b) {
        var aOk = draftHasContent(a);
        var bOk = draftHasContent(b);
        if (aOk && !bOk) return a;
        if (bOk && !aOk) return b;
        if (!aOk && !bOk) return null;
        // Ambos têm conteúdo: escolhe pelo carimbo de tempo (_savedAt em ms).
        var at = (a && a._savedAt) || 0;
        var bt = (b && b._savedAt) || 0;
        return bt > at ? b : a;
    }

    function applyDraft(draft) {
        // Tipo de solicitação (material/serviço)
        if (draft.order_type === 'service') {
            const svcTab = document.querySelector('#orderTypeTabs [data-order-type="service"]');
            if (svcTab) svcTab.click();
        }

        // Obra
        const siteEl = document.getElementById('constructionSiteSelect');
        if (siteEl && draft.site_id) siteEl.value = draft.site_id;

        // Data de necessidade
        if (neededDate && draft.needed_date) {
            neededDate.value = draft.needed_date;
            enforceMinDate();
        }

        // Observações
        const notesEl = document.querySelector('[name="notes"]');
        if (notesEl && draft.notes) notesEl.value = draft.notes;

        // Itens
        draft.items.forEach(function (item) {
            // Só ignora linhas COMPLETAMENTE vazias. Se tiver nome, id ou até só
            // quantidade, restaura mesmo assim — assim a pessoa vê o item e pode
            // corrigir, em vez de ele sumir silenciosamente (o que fazia o envio
            // acabar sem itens válidos e "não ir").
            var hasContent = (item.name && item.name.trim())
                || (item.id && String(item.id).trim())
                || (item.specification && item.specification.trim());
            if (!hasContent) return;
            addItem({
                id:             item.id || '',
                name:           item.name,
                specification:  item.specification || '',
                classification: item.classification || '',
                unit:           item.unit || '',
                quantity:       item.quantity || 1,
            });
            // Restaura data do item após addItem (precisa do índice inserido)
            if (item.needed_date) {
                const lastIdx = itemCount;
                const dateEl = document.getElementById('idate-' + lastIdx);
                const dateMobEl = document.querySelector('#item-card-' + lastIdx + ' .date-mobile');
                if (dateEl) dateEl.value = item.needed_date;
                if (dateMobEl) dateMobEl.value = item.needed_date;
            }
        });

        showSaveIndicator('saved');
    }

    // Observar mudanças no formulário para disparar autosave
    function attachSaveListeners() {
        document.getElementById('orderForm').addEventListener('input', scheduleSave);
        document.getElementById('orderForm').addEventListener('change', scheduleSave);
        // Observer para capturar itens adicionados/removidos dinamicamente
        const observer = new MutationObserver(scheduleSave);
        observer.observe(document.getElementById('itemsBodyDesktop'), { childList: true, subtree: true });
    }

    // Limpar rascunho ao submeter com sucesso
    const origConfirmSubmit = window.confirmSubmit;
    window.confirmSubmit = function () {
        clearDraft();
        origConfirmSubmit();
    };

    attachSaveListeners();
    restoreDraft();

    // Estado inicial
    enforceMinDate();
})();
