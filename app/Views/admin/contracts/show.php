<?php $pageTitle = 'Contrato ' . ($contract['project_code'] ?? '') . ' v' . ($contract['version'] ?? ''); $currentPage = 'contracts'; ?>
<?php ob_start(); ?>

<?php
$report = $contract['report_json'] ?? '';
$mk = $contract['contract_markdown'] ?? '';
$issues = $validation['issues'] ?? [];
$blocked = !empty($validation['blocked']);
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h5 class="mb-0">
        <i class="bi bi-file-earmark-ruled"></i>
        <?= htmlspecialchars($contract['project_code'] ?? 'Contrato') ?>
        <span class="text-muted">— <?= htmlspecialchars($contract['project_name'] ?? '') ?></span>
        <span class="badge bg-dark">v<?= (int)$contract['version'] ?></span>
    </h5>
    <div class="d-flex gap-2">
        <a href="/admin/contracts" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar</a>
        <a href="/admin/contracts/wizard/<?= (int)$contract['id'] ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-ui-checks-grid"></i> Editar informações
        </a>
        <button class="btn btn-outline-primary btn-sm" id="regenBtn"><i class="bi bi-arrow-repeat"></i> Regerar (nova versão)</button>
        <a href="/admin/contracts/export/<?= (int)$contract['id'] ?>" class="btn btn-primary btn-sm" id="exportBtn"><i class="bi bi-download"></i> Exportar</a>
    </div>
</div>

<div class="alert alert-light border small mb-3">
    <i class="bi bi-info-circle"></i>
    Abaixo você edita o <strong>texto final do contrato</strong>. Para alterar os dados extraídos da proposta
    (valores, escopo, contratante, obra) e gerar de novo, use <strong>Editar informações</strong>.
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Pendências no topo -->
        <?php if (!empty($issues)): ?>
        <div class="alert <?= $blocked ? 'alert-danger' : 'alert-warning' ?>">
            <strong><?= $blocked ? 'Exportação bloqueada — corrija:' : 'Alertas de validação:' ?></strong>
            <ul class="mb-0 mt-1">
                <?php foreach ($issues as $i): ?>
                    <li class="<?= ($i['level'] ?? '') === 'block' ? 'text-danger' : '' ?>">
                        <i class="bi bi-<?= ($i['level'] ?? '') === 'block' ? 'x-octagon' : 'exclamation-triangle' ?>"></i>
                        <?= htmlspecialchars($i['message'] ?? '') ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php else: ?>
        <div class="alert alert-success py-2"><i class="bi bi-check2-circle"></i> Nenhuma inconsistência detectada.</div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <span><i class="bi bi-pencil-square"></i> Contrato (edição)</span>
                <button class="btn btn-sm btn-success" id="saveBtn"><i class="bi bi-save"></i> Salvar</button>
            </div>
            <div class="card-body">
                <textarea id="markdownEditor" class="form-control" rows="30" style="font-family:ui-monospace,Consolas,monospace; font-size:.82rem;"><?= htmlspecialchars($mk) ?></textarea>
                <div class="form-text">
                    Apenas os campos variáveis devem mudar. Marcadores <code>[[PENDENTE: ...]]</code> bloqueiam a exportação.
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header py-2"><i class="bi bi-info-circle"></i> Metadados</div>
            <div class="card-body small">
                <div class="d-flex justify-content-between"><span class="text-muted">Modelo-base</span><span><?= htmlspecialchars($contract['template_name'] ?? '—') ?></span></div>
                <div class="d-flex justify-content-between"><span class="text-muted">Tipo</span><span><?= htmlspecialchars($contract['contract_type'] ?? '—') ?></span></div>
                <div class="d-flex justify-content-between"><span class="text-muted">Revisão da proposta</span><span><?= htmlspecialchars($contract['proposal_revision'] ?? '—') ?></span></div>
                <div class="d-flex justify-content-between"><span class="text-muted">PDF de origem</span><span class="text-truncate ms-2" style="max-width:150px;" title="<?= htmlspecialchars($contract['source_pdf'] ?? '') ?>"><?= htmlspecialchars($contract['source_pdf'] ?? '—') ?></span></div>
                <div class="d-flex justify-content-between"><span class="text-muted">Gerado por</span><span><?= htmlspecialchars($contract['created_by_name'] ?? '—') ?></span></div>
                <div class="d-flex justify-content-between"><span class="text-muted">Em</span><span><?= !empty($contract['created_at']) ? date('d/m/Y H:i', strtotime($contract['created_at'])) : '—' ?></span></div>
            </div>
        </div>

        <?php if (!empty($report)): ?>
        <div class="card mb-3">
            <div class="card-header py-2"><i class="bi bi-clipboard-data"></i> Relatório da IA</div>
            <div class="card-body"><pre class="mb-0 small" style="white-space:pre-wrap;"><?= htmlspecialchars($report) ?></pre></div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header py-2"><i class="bi bi-clock-history"></i> Versões</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($versions as $vv): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center py-2 <?= (int)$vv['id'] === (int)$contract['id'] ? 'bg-light' : '' ?>">
                    <a href="/admin/contracts/show/<?= (int)$vv['id'] ?>" class="text-decoration-none">
                        <span class="badge bg-dark">v<?= (int)$vv['version'] ?></span>
                        <span class="small ms-1"><?= !empty($vv['created_at']) ? date('d/m/Y H:i', strtotime($vv['created_at'])) : '' ?></span>
                    </a>
                    <span class="small text-muted"><?= htmlspecialchars($vv['created_by_name'] ?? '') ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<script>
(function () {
    const cid = <?= (int)$contract['id'] ?>;
    const saveBtn = document.getElementById('saveBtn');
    const regenBtn = document.getElementById('regenBtn');

    saveBtn.addEventListener('click', function () {
        this.disabled = true;
        const fd = new FormData();
        fd.append('contract_id', cid);
        fd.append('markdown', document.getElementById('markdownEditor').value);
        fetch('/admin/contracts/save', { method: 'POST', body: fd })
            .then(r => r.json()).then(res => {
                this.disabled = false;
                if (res.error) { alert(res.error); return; }
                const v = res.validation || {};
                if (v.blocked) {
                    alert('Salvo, mas a validação ainda bloqueia a exportação. Verifique as pendências.');
                }
                location.reload();
            }).catch(() => { this.disabled = false; alert('Erro ao salvar.'); });
    });

    regenBtn.addEventListener('click', function () {
        if (!confirm('Gerar uma nova versão a partir dos mesmos dados? A versão atual é preservada.')) return;
        this.disabled = true;
        const fd = new FormData();
        fd.append('contract_id', cid);
        fetch('/admin/contracts/regenerate', { method: 'POST', body: fd })
            .then(r => r.json()).then(res => {
                this.disabled = false;
                if (res.error) { alert(res.error); return; }
                location.href = res.show_url;
            }).catch(() => { this.disabled = false; alert('Erro ao regerar.'); });
    });
})();
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
