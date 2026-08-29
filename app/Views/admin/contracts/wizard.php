<?php $pageTitle = 'Novo Contrato'; $currentPage = 'contracts'; ?>
<?php ob_start(); ?>

<style>
    .wiz-steps { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1.25rem; }
    .wiz-step { flex:1; min-width:140px; padding:.6rem .8rem; border-radius:8px; background:#fff; border:1px solid #e3e6ea; display:flex; align-items:center; gap:.5rem; font-size:.85rem; color:#8a8f98; }
    .wiz-step .num { width:24px; height:24px; border-radius:50%; background:#e3e6ea; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.8rem; flex-shrink:0; }
    .wiz-step.active { border-color:var(--color-primary); color:var(--color-primary); font-weight:600; }
    .wiz-step.active .num { background:var(--color-primary); }
    .wiz-step.done .num { background:#28a745; }
    .wiz-pane { display:none; }
    .wiz-pane.active { display:block; }
    .dropzone { border:2px dashed #c7ccd3; border-radius:12px; padding:2.5rem 1rem; text-align:center; cursor:pointer; transition:all .2s; background:#fff; }
    .dropzone.dragover { border-color:var(--color-primary); background:#eef2f7; }
    .origin-badge { font-size:.62rem; padding:1px 6px; border-radius:4px; }
    .origin-extracted { background:#e7f1ff; color:#1c5fb5; }
    .origin-manual { background:#f0f0f0; color:#666; }
    .field-low-conf { background:#fff8e1 !important; border-color:#ffca2c !important; }
    .grp-card { border:1px solid #e3e6ea; border-radius:8px; margin-bottom:.6rem; }
    .grp-card .grp-head { padding:.5rem .8rem; cursor:pointer; display:flex; justify-content:space-between; align-items:center; }
    .grp-zero { opacity:.7; }
    .progress-log { font-size:.8rem; color:#666; }
</style>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h5 class="mb-0">
        <i class="bi bi-magic"></i> Elaboração de Contrato
        <?php if (!empty($draft)): ?>
            <span class="badge bg-secondary">Retomando <?= htmlspecialchars($draft['proposal']['capa']['projeto_codigo'] ?? 'rascunho') ?></span>
        <?php endif; ?>
    </h5>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-success btn-sm" id="saveDraftBtn" style="display:none;">
            <i class="bi bi-save"></i> Salvar rascunho
        </button>
        <a href="/admin/contracts" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar</a>
    </div>
</div>

<div class="wiz-steps">
    <div class="wiz-step active" data-step="1"><span class="num">1</span> Upload</div>
    <div class="wiz-step" data-step="2"><span class="num">2</span> Dados extraídos</div>
    <div class="wiz-step" data-step="3"><span class="num">3</span> Dados complementares</div>
    <div class="wiz-step" data-step="4"><span class="num">4</span> Geração</div>
</div>

<div id="wiz-alert"></div>

<!-- ETAPA 1 — UPLOAD -->
<div class="wiz-pane active" data-pane="1">
    <div class="card">
        <div class="card-body">
            <div class="dropzone" id="dropzone">
                <i class="bi bi-cloud-arrow-up" style="font-size:2.5rem; color:#9aa1ab;"></i>
                <p class="mb-1 mt-2"><strong>Arraste o PDF da Proposta Comercial</strong> ou clique para selecionar</p>
                <p class="text-muted small mb-0">Aceita .pdf, até 60 MB</p>
                <input type="file" id="pdfInput" accept="application/pdf,.pdf" hidden>
            </div>
            <div id="uploadProgress" class="mt-3" style="display:none;">
                <div class="progress" style="height:8px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" id="progBar" style="width:0%"></div>
                </div>
                <div class="progress-log mt-2" id="progLog">Lendo proposta…</div>
            </div>
        </div>
    </div>
</div>

<!-- ETAPA 2 — DADOS EXTRAÍDOS -->
<div class="wiz-pane" data-pane="2">
    <div class="alert alert-warning small" id="lowConfAlert" style="display:none;">
        <i class="bi bi-exclamation-triangle"></i>
        Campos destacados em amarelo tiveram <strong>baixa confiança de extração</strong>. Revise com atenção.
    </div>
    <div class="card mb-3">
        <div class="card-header py-2"><i class="bi bi-hash"></i> Identificação do Projeto</div>
        <div class="card-body" id="grp-identificacao"></div>
    </div>
    <div class="card mb-3">
        <div class="card-header py-2"><i class="bi bi-cash-stack"></i> Valores &amp; Forma de Pagamento</div>
        <div class="card-body" id="grp-valores"></div>
    </div>
    <div class="card mb-3">
        <div class="card-header py-2"><i class="bi bi-percent"></i> Segregação Fiscal (Notas de Negociação)</div>
        <div class="card-body" id="grp-fiscal"></div>
    </div>
    <div class="card mb-3">
        <div class="card-header py-2"><i class="bi bi-calendar3"></i> Projeção de Desembolso</div>
        <div class="card-body" id="grp-desembolso"></div>
    </div>
    <div class="card mb-3">
        <div class="card-header py-2"><i class="bi bi-list-check"></i> Escopo por Grupo</div>
        <div class="card-body" id="grp-escopo"></div>
    </div>
    <div class="card mb-3">
        <div class="card-header py-2"><i class="bi bi-x-circle"></i> Exclusões</div>
        <div class="card-body" id="grp-exclusoes"></div>
    </div>
    <div class="d-flex justify-content-between">
        <button class="btn btn-outline-secondary" data-goto="1"><i class="bi bi-arrow-left"></i> Voltar</button>
        <button class="btn btn-primary" data-goto="3">Continuar <i class="bi bi-arrow-right"></i></button>
    </div>
</div>

<!-- ETAPA 3 — DADOS COMPLEMENTARES -->
<div class="wiz-pane" data-pane="3">
    <form id="complementaryForm">
        <div class="card mb-3">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <span><i class="bi bi-person"></i> Contratante</span>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addContratante">
                    <i class="bi bi-plus-lg"></i> Adicionar 2º contratante
                </button>
            </div>
            <div class="card-body" id="contratantesWrap">
                <!-- blocos de contratante injetados via JS -->
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header py-2"><i class="bi bi-geo-alt"></i> Endereço da Obra</div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6"><label class="form-label small">Logradouro</label><input class="form-control form-control-sm" name="obra_logradouro"></div>
                    <div class="col-md-2"><label class="form-label small">Número</label><input class="form-control form-control-sm" name="obra_numero"></div>
                    <div class="col-md-4"><label class="form-label small">Apto/Unidade</label><input class="form-control form-control-sm" name="obra_unidade"></div>
                    <div class="col-md-4"><label class="form-label small">Bairro</label><input class="form-control form-control-sm" name="obra_bairro"></div>
                    <div class="col-md-4"><label class="form-label small">Cidade/UF</label><input class="form-control form-control-sm" name="obra_cidade_uf" placeholder="São Paulo/SP"></div>
                    <div class="col-md-4"><label class="form-label small">CEP</label><input class="form-control form-control-sm" name="obra_cep"></div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header py-2"><i class="bi bi-building"></i> Condomínio &amp; Assinatura</div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small">Nome do Condomínio <span class="text-muted">(opcional)</span></label>
                        <input class="form-control form-control-sm" name="condominio_nome">
                        <div class="form-text">Deixe em branco para suprimir as menções ao condomínio (Cl. 5.2.f e 6.1).</div>
                    </div>
                    <div class="col-md-3"><label class="form-label small">Cidade de assinatura</label><input class="form-control form-control-sm" name="assinatura_cidade" placeholder="São Paulo/SP"></div>
                    <div class="col-md-3"><label class="form-label small">Data de assinatura</label><input type="date" class="form-control form-control-sm" name="assinatura_data"></div>
                    <div class="col-md-6"><label class="form-label small">Foro (comarca)</label><input class="form-control form-control-sm" name="foro_comarca" placeholder="São Paulo/SP" value="<?= htmlspecialchars($conditionDefaults['foro_comarca'] ?? '') ?>"></div>
                </div>
                <hr>
                <div class="row g-2">
                    <div class="col-md-6"><label class="form-label small">Testemunha 1 — Nome</label><input class="form-control form-control-sm" name="test1_nome"></div>
                    <div class="col-md-6"><label class="form-label small">Testemunha 1 — CPF</label><input class="form-control form-control-sm" name="test1_cpf"></div>
                    <div class="col-md-6"><label class="form-label small">Testemunha 2 — Nome</label><input class="form-control form-control-sm" name="test2_nome"></div>
                    <div class="col-md-6"><label class="form-label small">Testemunha 2 — CPF</label><input class="form-control form-control-sm" name="test2_cpf"></div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header py-2"><i class="bi bi-shield-check"></i> Condições Contratuais <span class="text-muted small">(padrões da empresa — ajuste se necessário)</span></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-md-3"><label class="form-label small">Multa por mora (Cl. 3.5)</label><input class="form-control form-control-sm" name="multa_mora_pct" value="<?= htmlspecialchars($conditionDefaults['multa_mora_pct'] ?? '') ?>"></div>
                    <div class="col-md-3"><label class="form-label small">Juros de mora ao mês</label><input class="form-control form-control-sm" name="multa_juros_pct" value="<?= htmlspecialchars($conditionDefaults['multa_juros_pct'] ?? '') ?>"></div>
                    <div class="col-md-3"><label class="form-label small">Multa diária por atraso</label><input class="form-control form-control-sm" name="multa_atraso_diario_pct" value="<?= htmlspecialchars($conditionDefaults['multa_atraso_diario_pct'] ?? '') ?>"></div>
                    <div class="col-md-3"><label class="form-label small">Teto da multa</label><input class="form-control form-control-sm" name="multa_teto_pct" value="<?= htmlspecialchars($conditionDefaults['multa_teto_pct'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label small">Prazo garantia de solidez (Cl. 4.2)</label><input class="form-control form-control-sm" name="garantia_solidez_prazo" value="<?= htmlspecialchars($conditionDefaults['garantia_solidez_prazo'] ?? '') ?>"></div>
                    <div class="col-md-6"><label class="form-label small">Nome do sistema (Cl. 4.5/6.2/10.2)</label><input class="form-control form-control-sm" name="sistema_nome" value="<?= htmlspecialchars($conditionDefaults['sistema_nome'] ?? '') ?>"></div>
                </div>
                <div class="form-text">
                    Estes campos não vêm da proposta. Os padrões são definidos em <a href="/admin/contracts/settings" target="_blank">Configurações</a>.
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header py-2"><i class="bi bi-image"></i> Logo do contrato</div>
            <div class="card-body">
                <p class="text-muted small mb-2">
                    A logo aparece no topo esquerdo do contrato. Formatos: PNG, WEBP, JPG ou SVG (até 5 MB).
                </p>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div id="wizLogoPreviewWrap" style="<?= empty($conditionDefaults['logo_url']) ? 'display:none;' : '' ?>">
                        <img id="wizLogoPreview" src="<?= htmlspecialchars($conditionDefaults['logo_url'] ?? '') ?>" alt="Logo"
                             style="max-height:52px; max-width:200px; border:1px solid #e3e6ea; border-radius:6px; padding:6px; background:#fff;">
                    </div>
                    <div>
                        <input type="file" id="wizLogoInput" accept="image/png,image/webp,image/jpeg,image/svg+xml" hidden>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="wizBtnUploadLogo">
                            <i class="bi bi-upload"></i> Enviar logo
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" id="wizBtnRemoveLogo" style="<?= empty($conditionDefaults['logo_url']) ? 'display:none;' : '' ?>">
                            <i class="bi bi-trash"></i> Remover
                        </button>
                        <div class="small text-muted mt-1" id="wizLogoMsg"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header py-2"><i class="bi bi-briefcase"></i> Contratada (empresa)</div>
            <div class="card-body">
                <div class="mb-2">
                    <label class="form-label small">Selecione uma empresa cadastrada <span class="text-muted">(opcional — preenche os campos automaticamente)</span></label>
                    <select class="form-select form-select-sm" id="contractorSelect" name="contratada_id">
                        <option value="">— selecione ou preencha manualmente abaixo —</option>
                        <?php foreach ($contractors as $co): ?>
                            <option value="<?= (int)$co['id'] ?>"
                                data-razao="<?= htmlspecialchars($co['company_name'] ?? '') ?>"
                                data-fantasia="<?= htmlspecialchars($co['trade_name'] ?? '') ?>"
                                data-cnpj="<?= htmlspecialchars($co['cnpj'] ?? '') ?>"
                                data-sede="<?= htmlspecialchars(trim(($co['address'] ?? '') . ', ' . ($co['address_number'] ?? '') . ' ' . ($co['neighborhood'] ?? '') . ' ' . ($co['city'] ?? '') . '/' . ($co['state'] ?? ''), ', ')) ?>">
                                <?= htmlspecialchars($co['company_name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2">
                    <div class="col-md-6"><label class="form-label small">Razão Social</label><input class="form-control form-control-sm" name="contratada_razao_social"></div>
                    <div class="col-md-6"><label class="form-label small">Nome Fantasia</label><input class="form-control form-control-sm" name="contratada_nome_fantasia"></div>
                    <div class="col-md-4"><label class="form-label small">CNPJ</label><input class="form-control form-control-sm" name="contratada_cnpj"></div>
                    <div class="col-md-8"><label class="form-label small">Endereço da sede</label><input class="form-control form-control-sm" name="contratada_endereco_sede"></div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-outline-secondary" data-goto="2"><i class="bi bi-arrow-left"></i> Voltar</button>
            <button type="button" class="btn btn-primary" data-goto="4">Continuar <i class="bi bi-arrow-right"></i></button>
        </div>
    </form>
</div>

<!-- ETAPA 4 — GERAÇÃO -->
<div class="wiz-pane" data-pane="4">
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label small">Modelo-base do contrato</label>
                    <select class="form-select form-select-sm" id="templateSelect">
                        <?php foreach ($templates as $t): ?>
                            <option value="<?= (int)$t['id'] ?>" data-type="<?= htmlspecialchars($t['contract_type']) ?>">
                                <?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['contract_type']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text" id="templateHint"></div>
                </div>
                <div class="col-md-4 text-md-end">
                    <button class="btn btn-primary" id="generateBtn"><i class="bi bi-magic"></i> Gerar Contrato</button>
                </div>
            </div>
            <div id="genProgress" class="mt-3" style="display:none;">
                <div class="progress" style="height:8px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-dark" style="width:100%"></div>
                </div>
                <div class="progress-log mt-2">Montando contrato… isso pode levar alguns segundos.</div>
            </div>
        </div>
    </div>
    <div id="genResult"></div>
    <div class="d-flex justify-content-start">
        <button class="btn btn-outline-secondary" data-goto="3"><i class="bi bi-arrow-left"></i> Voltar</button>
    </div>
</div>

<script>
    // Dados de retomada (rascunho / contrato iniciado), quando houver
    window.CONTRACT_DRAFT = <?= json_encode($draft ?? null, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>
<script>
(function () {
    'use strict';
    let proposal = null;      // JSON extraído da proposta
    let sourcePdf = '';
    let contratanteCount = 0;
    let contractId = 0;       // id do rascunho/contrato em edição

    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

    function showAlert(msg, type = 'danger') {
        $('#wiz-alert').innerHTML =
            '<div class="alert alert-' + type + ' alert-dismissible fade show">' +
            msg + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }

    function goStep(n) {
        $$('.wiz-pane').forEach(p => p.classList.toggle('active', +p.dataset.pane === n));
        $$('.wiz-step').forEach(s => {
            const step = +s.dataset.step;
            s.classList.toggle('active', step === n);
            s.classList.toggle('done', step < n);
        });
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // ------- navegação por botões [data-goto] -------
    document.addEventListener('click', e => {
        const btn = e.target.closest('[data-goto]');
        if (!btn) return;
        const target = +btn.dataset.goto;
        if (target === 3) { syncObraFromProposal(); }
        if (target === 4) { collectComplementary(); }
        goStep(target);
    });

    // =================================================================
    // ETAPA 1 — UPLOAD
    // =================================================================
    const dz = $('#dropzone'), input = $('#pdfInput');
    dz.addEventListener('click', () => input.click());
    dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('dragover'); });
    dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
    dz.addEventListener('drop', e => {
        e.preventDefault(); dz.classList.remove('dragover');
        if (e.dataTransfer.files.length) handleFile(e.dataTransfer.files[0]);
    });
    input.addEventListener('change', () => { if (input.files.length) handleFile(input.files[0]); });

    function handleFile(file) {
        if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
            showAlert('Envie um arquivo PDF.'); return;
        }
        if (file.size > 60 * 1024 * 1024) { showAlert('Tamanho máximo: 60 MB.'); return; }

        const fd = new FormData();
        fd.append('pdf', file);
        $('#uploadProgress').style.display = 'block';
        setProg(15, 'Lendo proposta…');

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/admin/contracts/extract');
        xhr.upload.onprogress = ev => {
            if (ev.lengthComputable) setProg(15 + Math.round(ev.loaded / ev.total * 40), 'Enviando PDF…');
        };
        xhr.onload = () => {
            setProg(85, 'Extraindo dados…');
            let res;
            try { res = JSON.parse(xhr.responseText); } catch (_) { res = { error: 'Resposta inválida do servidor.' }; }
            if (xhr.status !== 200 || res.error) {
                $('#uploadProgress').style.display = 'none';
                showAlert(res.error || 'Falha na extração.'); return;
            }
            proposal = res.proposal || {};
            sourcePdf = res.source_pdf || file.name;
            setProg(100, 'Montando formulário…');
            renderReview(res.low_confidence || []);
            showDraftButton();
            if (res.suggested_template_id) {
                const opt = $('#templateSelect').querySelector('option[value="' + res.suggested_template_id + '"]');
                if (opt) $('#templateSelect').value = res.suggested_template_id;
            }
            setTimeout(() => { $('#uploadProgress').style.display = 'none'; goStep(2); }, 300);
        };
        xhr.onerror = () => { $('#uploadProgress').style.display = 'none'; showAlert('Erro de rede no upload.'); };
        xhr.send(fd);
    }

    function setProg(pct, msg) {
        $('#progBar').style.width = pct + '%';
        if (msg) $('#progLog').textContent = msg;
    }

    // =================================================================
    // ETAPA 2 — REVISÃO (formulário editável a partir do JSON)
    // =================================================================
    const lowConf = new Set();

    function field(label, path, value, opts = {}) {
        const low = lowConf.has(path) || (opts.low || false);
        const origin = opts.manual
            ? '<span class="origin-badge origin-manual">Manual</span>'
            : '<span class="origin-badge origin-extracted">Extraído' + (opts.page ? ' (pág. ' + opts.page + ')' : '') + '</span>';
        const cls = 'form-control form-control-sm' + (low ? ' field-low-conf' : '');
        return '<div class="col-md-' + (opts.col || 4) + ' mb-2">' +
            '<label class="form-label small d-flex justify-content-between">' +
            '<span>' + label + '</span>' + origin + '</label>' +
            '<input class="' + cls + '" data-path="' + path + '" value="' + escapeAttr(value) + '"></div>';
    }

    function escapeAttr(v) { return String(v == null ? '' : v).replace(/"/g, '&quot;'); }
    function get(obj, path) {
        return path.split('.').reduce((o, k) => (o && o[k] != null ? o[k] : ''), obj);
    }

    function renderReview(lowList) {
        lowConf.clear();
        (lowList || []).forEach(x => lowConf.add(x));
        $('#lowConfAlert').style.display = lowConf.size ? 'block' : 'none';

        const capaPage = get(proposal, 'capa.pagina');
        // Identificação
        $('#grp-identificacao').innerHTML = '<div class="row">' +
            field('Código do Projeto', 'capa.projeto_codigo', get(proposal, 'capa.projeto_codigo'), { page: capaPage }) +
            field('Nome do Projeto', 'capa.projeto_nome', get(proposal, 'capa.projeto_nome'), { page: capaPage }) +
            field('Tipo de Contrato', 'capa.contrato_tipo', get(proposal, 'capa.contrato_tipo'), { page: capaPage }) +
            field('Prazo (meses)', 'capa.prazo_meses', get(proposal, 'capa.prazo_meses'), { page: capaPage, col: 2 }) +
            field('Data', 'capa.data', get(proposal, 'capa.data'), { page: capaPage, col: 3 }) +
            field('Revisão', 'capa.revisao', get(proposal, 'capa.revisao'), { page: capaPage, col: 3 }) +
            field('Área Total (m²)', 'capa.area_total', get(proposal, 'capa.area_total'), { page: capaPage, col: 2 }) +
            field('Custo/m²', 'capa.custo_m2', get(proposal, 'capa.custo_m2'), { page: capaPage, col: 2 }) +
            field('Responsável', 'capa.responsavel', get(proposal, 'capa.responsavel'), { col: 4 }) +
            field('Arquiteto', 'capa.arquiteto', get(proposal, 'capa.arquiteto'), { col: 4 }) +
            '</div>';

        // Valores
        $('#grp-valores').innerHTML = '<div class="row">' +
            field('Valor Total (R$)', 'valor_total', get(proposal, 'valor_total'), { col: 4 }) +
            field('Entrada (R$)', 'forma_pagamento.entrada_valor', get(proposal, 'forma_pagamento.entrada_valor'), { col: 4 }) +
            field('Entrada (%)', 'forma_pagamento.entrada_pct', get(proposal, 'forma_pagamento.entrada_pct'), { col: 4 }) +
            field('Parcelas Total (R$)', 'forma_pagamento.parcelas_total', get(proposal, 'forma_pagamento.parcelas_total'), { col: 4 }) +
            field('Parcelas (%)', 'forma_pagamento.parcelas_pct', get(proposal, 'forma_pagamento.parcelas_pct'), { col: 2 }) +
            field('Qtd. Parcelas', 'forma_pagamento.parcelas_quantidade', get(proposal, 'forma_pagamento.parcelas_quantidade'), { col: 2 }) +
            field('Valor Unitário (R$)', 'forma_pagamento.parcelas_valor_unitario', get(proposal, 'forma_pagamento.parcelas_valor_unitario'), { col: 4 }) +
            field('Entrega (R$)', 'forma_pagamento.entrega_valor', get(proposal, 'forma_pagamento.entrega_valor'), { col: 4 }) +
            field('Entrega (%)', 'forma_pagamento.entrega_pct', get(proposal, 'forma_pagamento.entrega_pct'), { col: 4 }) +
            '</div>';

        // Fiscal
        $('#grp-fiscal').innerHTML = '<div class="row">' +
            field('% Construtora (NF)', 'notas_negociacao.pct_construtora', get(proposal, 'notas_negociacao.pct_construtora'), { col: 4 }) +
            field('% Material', 'notas_negociacao.pct_material', get(proposal, 'notas_negociacao.pct_material'), { col: 4 }) +
            field('% Fornecedores', 'notas_negociacao.pct_fornecedores', get(proposal, 'notas_negociacao.pct_fornecedores'), { col: 4 }) +
            '</div>' +
            '<label class="form-label small mt-2">Notas de negociação (texto livre)</label>' +
            '<textarea class="form-control form-control-sm" rows="2" data-path="notas_negociacao.texto_livre">' +
            (get(proposal, 'notas_negociacao.texto_livre') || '') + '</textarea>';

        // Desembolso
        renderDesembolso();
        // Escopo
        renderGrupos();
        // Exclusões
        renderExclusoes();
    }

    function renderDesembolso() {
        const arr = proposal.projecao_desembolso || [];
        let html = '<div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Mês</th><th>Valor (R$)</th></tr></thead><tbody>';
        if (!arr.length) html += '<tr><td colspan="2" class="text-muted small">Nenhuma projeção extraída.</td></tr>';
        arr.forEach((d, i) => {
            html += '<tr>' +
                '<td><input class="form-control form-control-sm" data-path="projecao_desembolso.' + i + '.mes" value="' + escapeAttr(d.mes) + '"></td>' +
                '<td><input class="form-control form-control-sm" data-path="projecao_desembolso.' + i + '.valor" value="' + escapeAttr(d.valor) + '"></td>' +
                '</tr>';
        });
        html += '</tbody></table></div>';
        $('#grp-desembolso').innerHTML = html;
    }

    function renderGrupos() {
        const arr = proposal.grupos || [];
        let html = '';
        if (!arr.length) html = '<p class="text-muted small mb-0">Nenhum grupo extraído.</p>';
        arr.forEach((g, i) => {
            const zero = (parseFloat(String(g.subtotal).replace(/[^\d.-]/g, '')) || 0) === 0;
            html += '<div class="grp-card ' + (zero ? 'grp-zero' : '') + '">' +
                '<div class="grp-head" data-bs-toggle="collapse" data-bs-target="#grp' + i + '">' +
                '<span><strong>' + (g.nome || 'Grupo ' + (i + 1)) + '</strong> ' +
                '<span class="text-muted small">' + (g.fase || '') + '</span></span>' +
                '<span>' + (zero ? '<span class="badge bg-secondary">R$ 0,00 → exclusão</span>' : '<span class="badge bg-success">R$ ' + (g.subtotal || '0') + '</span>') +
                ' <i class="bi bi-chevron-down"></i></span></div>' +
                '<div class="collapse" id="grp' + i + '"><div class="p-2 border-top">' +
                '<div class="row g-2">' +
                '<div class="col-md-6"><label class="form-label small">Nome</label><input class="form-control form-control-sm" data-path="grupos.' + i + '.nome" value="' + escapeAttr(g.nome) + '"></div>' +
                '<div class="col-md-3"><label class="form-label small">Fase</label><input class="form-control form-control-sm" data-path="grupos.' + i + '.fase" value="' + escapeAttr(g.fase) + '"></div>' +
                '<div class="col-md-3"><label class="form-label small">Subtotal</label><input class="form-control form-control-sm" data-path="grupos.' + i + '.subtotal" value="' + escapeAttr(g.subtotal) + '"></div>' +
                '</div>' +
                '<label class="form-label small mt-2">Itens (um por linha)</label>' +
                '<textarea class="form-control form-control-sm" rows="3" data-path="grupos.' + i + '.itens" data-list="1">' + (Array.isArray(g.itens) ? g.itens.join('\n') : (g.itens || '')) + '</textarea>' +
                '<label class="form-label small mt-2">Notas técnicas (uma por linha)</label>' +
                '<textarea class="form-control form-control-sm" rows="2" data-path="grupos.' + i + '.notas" data-list="1">' + (Array.isArray(g.notas) ? g.notas.join('\n') : (g.notas || '')) + '</textarea>' +
                '</div></div></div>';
        });
        $('#grp-escopo').innerHTML = html;
    }

    function renderExclusoes() {
        const naoIncluem = proposal.exclusoes?.nao_incluem || [];
        const zerados = proposal.exclusoes?.itens_zerados || [];
        $('#grp-exclusoes').innerHTML =
            '<label class="form-label small">"Não incluem:" (uma por linha)</label>' +
            '<textarea class="form-control form-control-sm" rows="3" data-path="exclusoes.nao_incluem" data-list="1">' + (naoIncluem.join('\n')) + '</textarea>' +
            '<label class="form-label small mt-2">Itens zerados (uma por linha)</label>' +
            '<textarea class="form-control form-control-sm" rows="2" data-path="exclusoes.itens_zerados" data-list="1">' + (zerados.join('\n')) + '</textarea>' +
            '<div class="row mt-2">' +
            field('Limite de peça (acabamentos)', 'acabamentos.limite_peca', get(proposal, 'acabamentos.limite_peca'), { col: 6, manual: false }) +
            field('Ambientes (acabamentos)', 'acabamentos.ambientes', get(proposal, 'acabamentos.ambientes'), { col: 6 }) +
            '</div>';
    }

    // Escreve os inputs editados de volta no objeto proposal
    function commitReview() {
        $$('[data-path]').forEach(el => {
            const path = el.dataset.path;
            let val = el.value;
            if (el.dataset.list === '1') {
                val = val.split('\n').map(s => s.trim()).filter(Boolean);
            }
            setPath(proposal, path, val);
        });
    }

    function setPath(obj, path, value) {
        const keys = path.split('.');
        let o = obj;
        for (let i = 0; i < keys.length - 1; i++) {
            const k = keys[i];
            const nextIsIndex = /^\d+$/.test(keys[i + 1]);
            if (o[k] == null) o[k] = nextIsIndex ? [] : {};
            o = o[k];
        }
        o[keys[keys.length - 1]] = value;
    }

    // =================================================================
    // ETAPA 3 — COMPLEMENTARES
    // =================================================================
    function contratanteBlock(idx, prefill = {}) {
        return '<div class="border rounded p-2 mb-2 contratante-block" data-idx="' + idx + '">' +
            '<div class="d-flex justify-content-between align-items-center mb-2">' +
            '<strong class="small">Contratante ' + (idx + 1) + '</strong>' +
            (idx > 0 ? '<button type="button" class="btn btn-sm btn-outline-danger remove-contratante"><i class="bi bi-x"></i></button>' : '') +
            '</div>' +
            '<div class="row g-2">' +
            '<div class="col-md-6"><label class="form-label small">Nome completo</label><input class="form-control form-control-sm c-nome" value="' + escapeAttr(prefill.nome) + '"></div>' +
            '<div class="col-md-3"><label class="form-label small">Nacionalidade</label><input class="form-control form-control-sm c-nac" value="brasileiro(a)"></div>' +
            '<div class="col-md-3"><label class="form-label small">Estado civil</label><input class="form-control form-control-sm c-civil"></div>' +
            '<div class="col-md-4"><label class="form-label small">Profissão (opcional)</label><input class="form-control form-control-sm c-prof"></div>' +
            '<div class="col-md-4"><label class="form-label small">CPF</label><input class="form-control form-control-sm c-cpf"><div class="form-text cpf-msg"></div></div>' +
            '<div class="col-md-4"><label class="form-label small">RG</label><input class="form-control form-control-sm c-rg"></div>' +
            '<div class="col-md-6"><label class="form-label small">Logradouro</label><input class="form-control form-control-sm c-log"></div>' +
            '<div class="col-md-2"><label class="form-label small">Número</label><input class="form-control form-control-sm c-num"></div>' +
            '<div class="col-md-4"><label class="form-label small">Apto/Unidade</label><input class="form-control form-control-sm c-uni"></div>' +
            '<div class="col-md-4"><label class="form-label small">Bairro</label><input class="form-control form-control-sm c-bairro"></div>' +
            '<div class="col-md-4"><label class="form-label small">Cidade</label><input class="form-control form-control-sm c-cidade"></div>' +
            '<div class="col-md-2"><label class="form-label small">UF</label><input class="form-control form-control-sm c-uf"></div>' +
            '<div class="col-md-2"><label class="form-label small">CEP</label><input class="form-control form-control-sm c-cep"></div>' +
            '<div class="col-md-6"><label class="form-label small">E-mail</label><input class="form-control form-control-sm c-email"></div>' +
            '<div class="col-md-6"><label class="form-label small">Telefone</label><input class="form-control form-control-sm c-tel"></div>' +
            '</div></div>';
    }

    function addContratante(prefill) {
        $('#contratantesWrap').insertAdjacentHTML('beforeend', contratanteBlock(contratanteCount, prefill || {}));
        contratanteCount++;
    }

    $('#addContratante').addEventListener('click', () => {
        if (contratanteCount >= 2) { showAlert('O modelo suporta até 2 contratantes.', 'warning'); return; }
        addContratante();
    });

    document.addEventListener('click', e => {
        if (e.target.closest('.remove-contratante')) {
            e.target.closest('.contratante-block').remove();
        }
    });

    // Validação de CPF ao sair do campo
    document.addEventListener('blur', e => {
        if (e.target.classList && e.target.classList.contains('c-cpf')) {
            const cpf = e.target.value.replace(/\D/g, '');
            const msg = e.target.closest('.row').querySelector('.cpf-msg');
            if (!cpf) { msg.textContent = ''; e.target.classList.remove('is-invalid', 'is-valid'); return; }
            fetch('/admin/contracts/validate-cpf', {
                method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'cpf=' + encodeURIComponent(cpf)
            }).then(r => r.json()).then(res => {
                e.target.classList.toggle('is-invalid', !res.valid);
                e.target.classList.toggle('is-valid', res.valid);
                msg.innerHTML = res.valid ? '<span class="text-success">CPF válido</span>' : '<span class="text-danger">CPF inválido</span>';
            });
        }
    }, true);

    // Contratada — preencher campos travados a partir do select
    $('#contractorSelect').addEventListener('change', function () {
        const o = this.options[this.selectedIndex];
        $('[name=contratada_razao_social]').value = o.dataset.razao || '';
        $('[name=contratada_nome_fantasia]').value = o.dataset.fantasia || '';
        $('[name=contratada_cnpj]').value = o.dataset.cnpj || '';
        $('[name=contratada_endereco_sede]').value = o.dataset.sede || '';
    });

    // Pré-preenche endereço da obra a partir da proposta (se houver)
    function syncObraFromProposal() {
        commitReview();
        // nome do projeto costuma trazer os nomes dos contratantes (ex.: "JUAN E YASMINI")
        if (contratanteCount === 0) {
            const nome = get(proposal, 'capa.projeto_nome') || '';
            addContratante({ nome: nome });
        }
    }

    let complementary = {};
    function collectComplementary() {
        const contratantes = $$('.contratante-block').map(b => ({
            nome: b.querySelector('.c-nome').value.trim(),
            nacionalidade: b.querySelector('.c-nac').value.trim(),
            estado_civil: b.querySelector('.c-civil').value.trim(),
            profissao: b.querySelector('.c-prof').value.trim(),
            cpf: b.querySelector('.c-cpf').value.trim(),
            rg: b.querySelector('.c-rg').value.trim(),
            logradouro: b.querySelector('.c-log').value.trim(),
            numero: b.querySelector('.c-num').value.trim(),
            unidade: b.querySelector('.c-uni').value.trim(),
            bairro: b.querySelector('.c-bairro').value.trim(),
            cidade: b.querySelector('.c-cidade').value.trim(),
            uf: b.querySelector('.c-uf').value.trim(),
            cep: b.querySelector('.c-cep').value.trim(),
            email: b.querySelector('.c-email').value.trim(),
            telefone: b.querySelector('.c-tel').value.trim(),
        }));
        const f = n => ($('[name=' + n + ']') ? $('[name=' + n + ']').value.trim() : '');
        complementary = {
            contratantes: contratantes,
            contratante: contratantes[0] || {},
            obra: {
                logradouro: f('obra_logradouro'), numero: f('obra_numero'), unidade: f('obra_unidade'),
                bairro: f('obra_bairro'), cidade_uf: f('obra_cidade_uf'), cep: f('obra_cep'),
            },
            condominio: { nome: f('condominio_nome') },
            assinatura: { cidade: f('assinatura_cidade'), data: f('assinatura_data') },
            foro: { comarca: f('foro_comarca') },
            multa: {
                mora_pct: f('multa_mora_pct'),
                juros_pct: f('multa_juros_pct'),
                atraso_diario_pct: f('multa_atraso_diario_pct'),
                teto_pct: f('multa_teto_pct'),
            },
            garantia: { solidez_prazo: f('garantia_solidez_prazo') },
            sistema: { nome: f('sistema_nome') },
            testemunhas: [
                { nome: f('test1_nome'), cpf: f('test1_cpf') },
                { nome: f('test2_nome'), cpf: f('test2_cpf') },
            ],
            contratada: {
                razao_social: f('contratada_razao_social'), nome_fantasia: f('contratada_nome_fantasia'),
                cnpj: f('contratada_cnpj'), endereco_sede: f('contratada_endereco_sede'),
            },
        };
    }

    // =================================================================
    // ETAPA 4 — GERAÇÃO
    // =================================================================
    function updateTemplateHint() {
        const opt = $('#templateSelect').options[$('#templateSelect').selectedIndex];
        const type = opt ? opt.dataset.type : '';
        const capaType = (get(proposal || {}, 'capa.contrato_tipo') || '').toLowerCase();
        let hint = 'Selecionado automaticamente pelo campo "Contrato" da capa do orçamento.';
        if (capaType && type && !capaType.includes(type.substring(0, 5))) {
            hint = '⚠ A capa indica "' + capaType + '", diferente do tipo do modelo escolhido.';
        }
        $('#templateHint').textContent = hint;
    }
    $('#templateSelect').addEventListener('change', updateTemplateHint);

    $('#generateBtn').addEventListener('click', function () {
        collectComplementary();
        this.disabled = true;
        $('#genProgress').style.display = 'block';
        $('#genResult').innerHTML = '';

        const fd = new FormData();
        fd.append('proposal', JSON.stringify(proposal));
        fd.append('complementary', JSON.stringify(complementary));
        fd.append('template_id', $('#templateSelect').value);
        fd.append('source_pdf', sourcePdf);
        fd.append('contract_id', contractId || '');

        fetch('/admin/contracts/generate', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                $('#genProgress').style.display = 'none';
                this.disabled = false;
                if (res.error) { showAlert(res.error); return; }
                renderGenResult(res);
            })
            .catch(() => { $('#genProgress').style.display = 'none'; this.disabled = false; showAlert('Erro ao gerar o contrato.'); });
    });

    function renderGenResult(res) {
        const v = res.validation || { issues: [], blocked: false };
        let issuesHtml = '';
        if (v.issues && v.issues.length) {
            issuesHtml = '<ul class="mb-0">' + v.issues.map(i =>
                '<li class="' + (i.level === 'block' ? 'text-danger' : 'text-warning-emphasis') + '">' +
                '<i class="bi bi-' + (i.level === 'block' ? 'x-octagon' : 'exclamation-triangle') + '"></i> ' + i.message + '</li>'
            ).join('') + '</ul>';
        } else {
            issuesHtml = '<span class="text-success"><i class="bi bi-check2-circle"></i> Nenhuma inconsistência detectada.</span>';
        }
        const alertClass = v.blocked ? 'alert-danger' : (v.issues && v.issues.length ? 'alert-warning' : 'alert-success');

        $('#genResult').innerHTML =
            '<div class="alert ' + alertClass + '"><strong>Checklist de validação (v' + res.version + '):</strong>' + issuesHtml + '</div>' +
            (res.report ? '<div class="card mb-3"><div class="card-header py-2"><i class="bi bi-clipboard-data"></i> Relatório da IA</div>' +
                '<div class="card-body"><pre class="mb-0 small" style="white-space:pre-wrap;">' + escapeHtml(res.report) + '</pre></div></div>' : '') +
            '<div class="d-flex gap-2"><a href="' + res.show_url + '" class="btn btn-primary"><i class="bi bi-eye"></i> Abrir contrato (v' + res.version + ')</a>' +
            '<a href="/admin/contracts/export/' + res.contract_id + '" class="btn btn-outline-secondary"><i class="bi bi-download"></i> Exportar</a></div>';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

    // =================================================================
    // SALVAR RASCUNHO
    // =================================================================
    const saveDraftBtn = $('#saveDraftBtn');

    function showDraftButton() { if (saveDraftBtn) saveDraftBtn.style.display = ''; }

    if (saveDraftBtn) {
        saveDraftBtn.addEventListener('click', function () {
            if (!proposal) { showAlert('Faça o upload da proposta antes de salvar.', 'warning'); return; }
            commitReview();
            collectComplementary();
            this.disabled = true;
            const original = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvando…';

            const fd = new FormData();
            fd.append('proposal', JSON.stringify(proposal));
            fd.append('complementary', JSON.stringify(complementary));
            fd.append('template_id', $('#templateSelect') ? $('#templateSelect').value : '');
            fd.append('source_pdf', sourcePdf);
            fd.append('contract_id', contractId || '');

            fetch('/admin/contracts/save-draft', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    this.disabled = false;
                    this.innerHTML = original;
                    if (res.error) { showAlert(res.error); return; }
                    contractId = res.contract_id;
                    showAlert('Rascunho salvo. Você pode continuar depois pela listagem de contratos.', 'success');
                })
                .catch(() => { this.disabled = false; this.innerHTML = original; showAlert('Erro ao salvar rascunho.'); });
        });
    }

    // =================================================================
    // RETOMADA DE RASCUNHO / CONTRATO INICIADO
    // =================================================================
    function resumeDraft(d) {
        if (!d || !d.proposal) return;
        proposal = d.proposal;
        sourcePdf = d.source_pdf || '';
        contractId = d.id || 0;

        // Etapa 2 já preenchida a partir do JSON salvo
        renderReview(proposal.confianca_baixa || []);

        // Etapa 3 — dados complementares
        prefillComplementary(d.complementary || {});

        // Etapa 4 — modelo-base
        if (d.base_template_id && $('#templateSelect')) {
            const opt = $('#templateSelect').querySelector('option[value="' + d.base_template_id + '"]');
            if (opt) $('#templateSelect').value = d.base_template_id;
        }
        if (typeof updateTemplateHint === 'function') updateTemplateHint();

        showDraftButton();
        goStep(2); // pula o upload — já temos os dados
    }

    function prefillComplementary(c) {
        // Contratantes
        $('#contratantesWrap').innerHTML = '';
        contratanteCount = 0;
        const list = (c.contratantes && c.contratantes.length) ? c.contratantes
                    : (c.contratante ? [c.contratante] : []);
        if (!list.length) { addContratante({}); }
        list.forEach(ct => {
            addContratante({ nome: ct.nome || '' });
            const block = $$('.contratante-block').slice(-1)[0];
            if (!block) return;
            const set = (sel, val) => { const el = block.querySelector(sel); if (el) el.value = val || ''; };
            set('.c-nome', ct.nome); set('.c-nac', ct.nacionalidade); set('.c-civil', ct.estado_civil);
            set('.c-prof', ct.profissao); set('.c-cpf', ct.cpf); set('.c-rg', ct.rg);
            set('.c-log', ct.logradouro); set('.c-num', ct.numero); set('.c-uni', ct.unidade);
            set('.c-bairro', ct.bairro); set('.c-cidade', ct.cidade); set('.c-uf', ct.uf);
            set('.c-cep', ct.cep); set('.c-email', ct.email); set('.c-tel', ct.telefone);
        });

        const setName = (n, v) => { const el = $('[name=' + n + ']'); if (el) el.value = v || ''; };
        const o = c.obra || {};
        setName('obra_logradouro', o.logradouro); setName('obra_numero', o.numero); setName('obra_unidade', o.unidade);
        setName('obra_bairro', o.bairro); setName('obra_cidade_uf', o.cidade_uf); setName('obra_cep', o.cep);
        setName('condominio_nome', (c.condominio || {}).nome);
        setName('assinatura_cidade', (c.assinatura || {}).cidade); setName('assinatura_data', (c.assinatura || {}).data);
        setName('foro_comarca', (c.foro || {}).comarca);
        const mu = c.multa || {};
        setName('multa_mora_pct', mu.mora_pct); setName('multa_juros_pct', mu.juros_pct);
        setName('multa_atraso_diario_pct', mu.atraso_diario_pct); setName('multa_teto_pct', mu.teto_pct);
        setName('garantia_solidez_prazo', (c.garantia || {}).solidez_prazo);
        setName('sistema_nome', (c.sistema || {}).nome);
        const t = c.testemunhas || [];
        if (t[0]) { setName('test1_nome', t[0].nome); setName('test1_cpf', t[0].cpf); }
        if (t[1]) { setName('test2_nome', t[1].nome); setName('test2_cpf', t[1].cpf); }
        const cd = c.contratada || {};
        setName('contratada_razao_social', cd.razao_social); setName('contratada_nome_fantasia', cd.nome_fantasia);
        setName('contratada_cnpj', cd.cnpj); setName('contratada_endereco_sede', cd.endereco_sede);
    }

    // =================================================================
    // LOGO DO CONTRATO (upload dentro do formulário)
    // =================================================================
    const wizLogoInput = $('#wizLogoInput');
    const wizBtnUpload = $('#wizBtnUploadLogo');
    const wizBtnRemove = $('#wizBtnRemoveLogo');
    const wizLogoWrap = $('#wizLogoPreviewWrap');
    const wizLogoPreview = $('#wizLogoPreview');
    const wizLogoMsg = $('#wizLogoMsg');

    if (wizBtnUpload) {
        wizBtnUpload.addEventListener('click', () => wizLogoInput.click());
        wizLogoInput.addEventListener('change', function () {
            if (!this.files.length) return;
            const fd = new FormData();
            fd.append('contract_logo', this.files[0]);
            wizLogoMsg.textContent = 'Enviando…';
            fetch('/admin/contracts/settings/upload-logo', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.error) { wizLogoMsg.innerHTML = '<span class="text-danger">' + res.error + '</span>'; return; }
                    wizLogoPreview.src = res.url + '?t=' + Date.now();
                    wizLogoWrap.style.display = '';
                    wizBtnRemove.style.display = '';
                    wizLogoMsg.innerHTML = '<span class="text-success">Logo atualizada.</span>';
                })
                .catch(() => { wizLogoMsg.innerHTML = '<span class="text-danger">Erro no upload.</span>'; });
        });
    }
    if (wizBtnRemove) {
        wizBtnRemove.addEventListener('click', function () {
            if (!confirm('Remover a logo do contrato?')) return;
            fetch('/admin/contracts/settings/remove-logo', { method: 'POST' })
                .then(r => r.json())
                .then(res => {
                    if (res.error) { wizLogoMsg.innerHTML = '<span class="text-danger">' + res.error + '</span>'; return; }
                    wizLogoWrap.style.display = 'none';
                    wizBtnRemove.style.display = 'none';
                    wizLogoMsg.innerHTML = '<span class="text-muted">Logo removida.</span>';
                })
                .catch(() => { wizLogoMsg.innerHTML = '<span class="text-danger">Erro ao remover.</span>'; });
        });
    }

    // Boot: se veio rascunho, retoma; senão, fluxo normal de upload
    if (window.CONTRACT_DRAFT) {
        resumeDraft(window.CONTRACT_DRAFT);
    }
})();
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
