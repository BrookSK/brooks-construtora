<?php $pageTitle = 'Configurações — Contratos'; $currentPage = 'contracts'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h5 class="mb-0"><i class="bi bi-file-earmark-ruled"></i> Elaboração de Contrato</h5>
</div>

<?php $contractTab = 'settings'; require __DIR__ . '/_subnav.php'; ?>

<?php if (!$hasKey): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i>
    A chave da API OpenAI ainda não foi configurada. Defina-a em
    <a href="/admin/settings">Configurações do sistema</a> antes de gerar contratos.
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header py-2"><i class="bi bi-cpu"></i> Modelo GPT</div>
            <div class="card-body">
                <form method="POST" action="/admin/contracts/settings/save">
                    <p class="text-muted small">
                        Escolha o modelo usado para <strong>ler o PDF da proposta</strong> e
                        <strong>redigir o contrato</strong>. Se deixar em branco, o módulo usa
                        <code><?= htmlspecialchars($defaultModel) ?></code> — necessário porque a leitura
                        do PDF exige um modelo com suporte a arquivos e contexto grande.
                    </p>

                    <label class="form-label small">Modelo para este módulo</label>
                    <select class="form-select" id="modelSelect" name="contract_openai_model">
                        <option value="">— padrão do módulo (<?= htmlspecialchars($defaultModel) ?>) —</option>
                        <?php
                        $known = false;
                        foreach ($models as $val => $label):
                            $sel = ($currentModel === $val);
                            if ($sel) $known = true;
                        ?>
                            <option value="<?= htmlspecialchars($val) ?>" <?= $sel ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if (!empty($currentModel) && !$known): ?>
                            <option value="<?= htmlspecialchars($currentModel) ?>" selected>
                                <?= htmlspecialchars($currentModel) ?> (personalizado)
                            </option>
                        <?php endif; ?>
                        <option value="__custom__">Outro (digitar manualmente)…</option>
                    </select>

                    <div class="mt-2" id="customWrap" style="display:none;">
                        <label class="form-label small">Nome do modelo (identificador exato da API)</label>
                        <input class="form-control" id="customModel" placeholder="ex.: gpt-4o-2024-11-20"
                               value="<?= (!empty($currentModel) && !$known) ? htmlspecialchars($currentModel) : '' ?>">
                    </div>

                    <div class="alert alert-light border small mt-3 mb-3">
                        <i class="bi bi-lightbulb"></i>
                        A leitura do PDF exige um modelo com suporte a arquivos (família <strong>gpt-4o</strong> / <strong>gpt-4.1</strong>).
                        Modelos "mini" reduzem custo, mas podem extrair com menos precisão em propostas complexas.
                    </div>

                    <hr>
                    <h6 class="small text-uppercase text-muted"><i class="bi bi-shield-check"></i> Condições contratuais padrão</h6>
                    <p class="text-muted small">
                        Valores fixos da empresa que não vêm da proposta (multas, garantia, sistema, foro).
                        São usados como padrão em cada contrato e podem ser ajustados no formulário antes de gerar.
                    </p>
                    <div class="row g-2 mb-3">
                        <div class="col-md-3"><label class="form-label small">Multa por mora (Cl. 3.5)</label><input class="form-control form-control-sm" name="multa_mora_pct" value="<?= htmlspecialchars($conditions['multa_mora_pct'] ?? '') ?>"></div>
                        <div class="col-md-3"><label class="form-label small">Juros de mora / mês</label><input class="form-control form-control-sm" name="multa_juros_pct" value="<?= htmlspecialchars($conditions['multa_juros_pct'] ?? '') ?>"></div>
                        <div class="col-md-3"><label class="form-label small">Multa diária por atraso</label><input class="form-control form-control-sm" name="multa_atraso_diario_pct" value="<?= htmlspecialchars($conditions['multa_atraso_diario_pct'] ?? '') ?>"></div>
                        <div class="col-md-3"><label class="form-label small">Teto da multa</label><input class="form-control form-control-sm" name="multa_teto_pct" value="<?= htmlspecialchars($conditions['multa_teto_pct'] ?? '') ?>"></div>
                        <div class="col-md-4"><label class="form-label small">Prazo garantia de solidez (Cl. 4.2)</label><input class="form-control form-control-sm" name="garantia_solidez_prazo" value="<?= htmlspecialchars($conditions['garantia_solidez_prazo'] ?? '') ?>"></div>
                        <div class="col-md-4"><label class="form-label small">Nome do sistema (Cl. 4.5/6.2/10.2)</label><input class="form-control form-control-sm" name="sistema_nome" value="<?= htmlspecialchars($conditions['sistema_nome'] ?? '') ?>" placeholder="ex.: Portal do Cliente"></div>
                        <div class="col-md-4"><label class="form-label small">Foro padrão (comarca)</label><input class="form-control form-control-sm" name="foro_comarca" value="<?= htmlspecialchars($conditions['foro_comarca'] ?? '') ?>" placeholder="ex.: São Paulo/SP"></div>
                    </div>
                    <div class="alert alert-warning small">
                        <i class="bi bi-exclamation-triangle"></i>
                        O prazo de garantia de solidez tem prazo legal mínimo (art. 618 do Código Civil, 5 anos para edifícios).
                        Confirme com o jurídico antes de reduzir.
                    </div>

                    <button class="btn btn-primary"><i class="bi bi-save"></i> Salvar</button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header py-2"><i class="bi bi-image"></i> Logo do contrato</div>
            <div class="card-body">
                <p class="text-muted small">
                    A logo aparece uma única vez, no <strong>topo esquerdo</strong> da primeira página do contrato.
                    Formatos: PNG, WEBP, JPG ou SVG (até 5 MB). Se nenhuma logo for enviada, o documento é gerado sem logo.
                </p>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div id="logoPreviewWrap" style="<?= empty($logoUrl) ? 'display:none;' : '' ?>">
                        <img id="logoPreview" src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo"
                             style="max-height:56px; max-width:220px; border:1px solid #e3e6ea; border-radius:6px; padding:6px; background:#fff;">
                    </div>
                    <div>
                        <input type="file" id="logoInput" accept="image/png,image/webp,image/jpeg,image/svg+xml" hidden>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="btnUploadLogo">
                            <i class="bi bi-upload"></i> Enviar logo
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" id="btnRemoveLogo" style="<?= empty($logoUrl) ? 'display:none;' : '' ?>">
                            <i class="bi bi-trash"></i> Remover
                        </button>
                        <div class="small text-muted mt-1" id="logoMsg"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header py-2"><i class="bi bi-info-circle"></i> Modelo em uso</div>
            <div class="card-body small">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Módulo de contratos</span>
                    <strong><?= htmlspecialchars($currentModel !== '' ? $currentModel : $defaultModel . ' (padrão)') ?></strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Chave da API</span>
                    <span><?= $hasKey ? '<span class="text-success">configurada</span>' : '<span class="text-danger">ausente</span>' ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const sel = document.getElementById('modelSelect');
    const wrap = document.getElementById('customWrap');
    const custom = document.getElementById('customModel');
    const form = sel.closest('form');

    function sync() {
        wrap.style.display = sel.value === '__custom__' ? 'block' : 'none';
    }
    sel.addEventListener('change', sync);
    // se já havia modelo personalizado, mostra o campo
    if (sel.value && sel.options[sel.selectedIndex] && sel.options[sel.selectedIndex].textContent.includes('personalizado')) {
        // deixa selecionado o próprio valor; nada a fazer
    }
    sync();

    form.addEventListener('submit', function () {
        if (sel.value === '__custom__') {
            // troca o valor enviado pelo texto digitado
            sel.insertAdjacentHTML('beforeend', '<option value="' + custom.value.trim() + '" selected></option>');
            sel.value = custom.value.trim();
        }
    });

    // ---- Logo do contrato ----
    const logoInput = document.getElementById('logoInput');
    const btnUpload = document.getElementById('btnUploadLogo');
    const btnRemove = document.getElementById('btnRemoveLogo');
    const previewWrap = document.getElementById('logoPreviewWrap');
    const preview = document.getElementById('logoPreview');
    const logoMsg = document.getElementById('logoMsg');

    btnUpload.addEventListener('click', () => logoInput.click());

    logoInput.addEventListener('change', function () {
        if (!this.files.length) return;
        const fd = new FormData();
        fd.append('contract_logo', this.files[0]);
        logoMsg.textContent = 'Enviando…';
        fetch('/admin/contracts/settings/upload-logo', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.error) { logoMsg.innerHTML = '<span class="text-danger">' + res.error + '</span>'; return; }
                preview.src = res.url + '?t=' + Date.now();
                previewWrap.style.display = '';
                btnRemove.style.display = '';
                logoMsg.innerHTML = '<span class="text-success">Logo atualizada.</span>';
            })
            .catch(() => { logoMsg.innerHTML = '<span class="text-danger">Erro no upload.</span>'; });
    });

    btnRemove.addEventListener('click', function () {
        if (!confirm('Remover a logo do contrato?')) return;
        fetch('/admin/contracts/settings/remove-logo', { method: 'POST' })
            .then(r => r.json())
            .then(res => {
                if (res.error) { logoMsg.innerHTML = '<span class="text-danger">' + res.error + '</span>'; return; }
                previewWrap.style.display = 'none';
                btnRemove.style.display = 'none';
                logoMsg.innerHTML = '<span class="text-muted">Logo removida.</span>';
            })
            .catch(() => { logoMsg.innerHTML = '<span class="text-danger">Erro ao remover.</span>'; });
    });
})();
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
