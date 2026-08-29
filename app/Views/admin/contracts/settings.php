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
                        <strong>redigir o contrato</strong>. Se deixar em branco, o módulo usa o modelo
                        global do sistema (<code><?= htmlspecialchars($globalModel) ?></code>).
                    </p>

                    <label class="form-label small">Modelo para este módulo</label>
                    <select class="form-select" id="modelSelect" name="contract_openai_model">
                        <option value="">— usar modelo global (<?= htmlspecialchars($globalModel) ?>) —</option>
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

                    <button class="btn btn-primary"><i class="bi bi-save"></i> Salvar</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header py-2"><i class="bi bi-info-circle"></i> Modelo em uso</div>
            <div class="card-body small">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Módulo de contratos</span>
                    <strong><?= htmlspecialchars($currentModel !== '' ? $currentModel : $globalModel . ' (global)') ?></strong>
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
})();
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
