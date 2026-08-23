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
    const materials = window.WEEKLY_MATERIALS || [];
    let itemCount = 0;
    let audioBlob = null;

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
            placeholder: 'Buscar material...',
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
                (prefill ? '<span class="badge bg-light text-dark">' + (prefill.specification || '') + '</span><span class="badge bg-light text-dark">' + (prefill.classification || '') + '</span>' : '<span class="text-muted" style="font-size:0.75rem;">Busque um material acima</span>') +
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
            placeholder: 'Buscar material...',
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
            el.innerHTML = '<span class="text-muted" style="font-size:0.75rem;">Busque um material acima</span>';
        }
    }

    const addBtn = document.getElementById('addItemBtn');
    if (addBtn) addBtn.addEventListener('click', function () { addItem(); });
    const addBtnInline = document.getElementById('addItemBtnInline');
    if (addBtnInline) addBtnInline.addEventListener('click', function () { addItem(); });

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

        let valid = true;
        document.querySelectorAll('[id^="mname-"]').forEach(function (input) { if (!input.value) valid = false; });
        if (!valid) { alert('Selecione um material para cada item.'); return; }

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

        html += '<table class="table table-sm table-bordered"><thead><tr><th>#</th><th>Material</th><th>Espec.</th><th>Class.</th><th class="text-center">Qtd</th><th>Data</th></tr></thead><tbody>';
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
        html += '<div class="alert alert-info mt-2 small"><i class="bi bi-info-circle"></i> Ao confirmar, será criado um Pedido de Material no sistema e enviado para cotação.</div>';

        document.getElementById('reviewBody').innerHTML = html;
        new bootstrap.Modal(document.getElementById('reviewModal')).show();
    };

    window.confirmSubmit = function () {
        const form = document.getElementById('orderForm');
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

    // Estado inicial
    enforceMinDate();
})();
