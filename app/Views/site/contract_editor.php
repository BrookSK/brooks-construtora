<?php
// =====================================================================
// Editor público de contrato (link compartilhável, sem login)
// =====================================================================
$contract = $contract ?? [];
$token = $token ?? '';
$mk = (string)($contract['contract_markdown'] ?? '');
$validation = $validation ?? null;
$issues = $validation['issues'] ?? [];
$blocked = !empty($validation['blocked']);
$title = ($contract['project_code'] ?? 'Contrato') . ' v' . ($contract['version'] ?? '');
function e_($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES); }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar contrato — <?= e_($title) ?></title>
    <link rel="icon" href="/assets/images/wp/2023/01/cropped-favicon-1-32x32.png" sizes="32x32" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        html, body { height:100%; margin:0; }
        body { display:flex; flex-direction:column; background:#f4f6f9; font-family:'Segoe UI', sans-serif; }
        .ed-topbar { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.6rem 1rem; background:#3a3b4e; color:#fff; flex-wrap:wrap; }
        .ed-topbar h6 { margin:0; font-size:.95rem; }
        .ed-alert { margin:0; border-radius:0; }
        .ed-main { flex:1; display:flex; min-height:0; }
        #markdownEditor { flex:1; width:100%; border:none; resize:none; outline:none; padding:1.5rem 2rem; font-family:ui-monospace, Consolas, "Courier New", monospace; font-size:16px; line-height:1.6; color:#222; }
        .ed-footer { padding:.4rem 1rem; background:#fff; border-top:1px solid #e3e6ea; font-size:.8rem; color:#666; }
        .save-status { font-size:.8rem; }
        .pendente-tag { background:#ffe08a; color:#8a5a00; padding:0 4px; border-radius:3px; }
        @media (max-width: 640px) { #markdownEditor { padding:1rem; } }
    </style>
</head>
<body>
    <div class="ed-topbar">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-pencil-square"></i>
            <h6><?= e_($contract['project_code'] ?? 'Contrato') ?>
                <span class="opacity-75">— <?= e_($contract['project_name'] ?? '') ?></span>
                <span class="badge bg-light text-dark">v<?= (int)($contract['version'] ?? 1) ?></span>
            </h6>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="save-status" id="saveStatus"></span>
            <button class="btn btn-sm btn-light" id="fontMinus" title="Diminuir fonte"><i class="bi bi-dash-lg"></i></button>
            <button class="btn btn-sm btn-light" id="fontPlus" title="Aumentar fonte"><i class="bi bi-plus-lg"></i></button>
            <button class="btn btn-sm btn-success" id="saveBtn"><i class="bi bi-save"></i> Salvar</button>
        </div>
    </div>

    <div class="alert ed-alert alert-info py-2 small mb-0">
        <i class="bi bi-info-circle"></i>
        Você está editando um contrato compartilhado. As alterações que você salvar ficam registradas para quem gerou o documento.
        Ajuste apenas o texto necessário e clique em <strong>Salvar</strong>.
    </div>

    <div id="issuesBar">
        <?php if (!empty($issues)): ?>
        <div class="alert ed-alert <?= $blocked ? 'alert-danger' : 'alert-warning' ?> py-2 small mb-0">
            <strong><?= $blocked ? 'Pendências:' : 'Alertas:' ?></strong>
            <?= count($issues) ?> item(ns). <span class="d-none d-md-inline"><?= e_($issues[0]['message'] ?? '') ?><?= count($issues) > 1 ? '…' : '' ?></span>
        </div>
        <?php endif; ?>
    </div>

    <div class="ed-main">
        <textarea id="markdownEditor" spellcheck="false"><?= e_($mk) ?></textarea>
    </div>

    <div class="ed-footer">
        Marcadores <span class="pendente-tag">[[PENDENTE: ...]]</span> indicam campos ainda não preenchidos.
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    (function () {
        const token = <?= json_encode($token) ?>;
        const editor = document.getElementById('markdownEditor');
        const saveBtn = document.getElementById('saveBtn');
        const status = document.getElementById('saveStatus');
        let dirty = false;

        editor.addEventListener('input', () => { dirty = true; status.textContent = 'Alterações não salvas'; status.className = 'save-status text-warning'; });

        let fontSize = parseFloat(localStorage.getItem('contract_pub_font') || '16');
        function applyFont() { editor.style.fontSize = fontSize + 'px'; localStorage.setItem('contract_pub_font', fontSize); }
        applyFont();
        document.getElementById('fontPlus').addEventListener('click', () => { fontSize = Math.min(28, fontSize + 1); applyFont(); });
        document.getElementById('fontMinus').addEventListener('click', () => { fontSize = Math.max(11, fontSize - 1); applyFont(); });

        function save() {
            saveBtn.disabled = true;
            status.textContent = 'Salvando…'; status.className = 'save-status text-light';
            const fd = new FormData();
            fd.append('token', token);
            fd.append('markdown', editor.value);
            fetch('/contrato/editar/salvar', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    saveBtn.disabled = false;
                    if (res.error) { status.textContent = res.error; status.className = 'save-status text-danger'; return; }
                    dirty = false;
                    const v = res.validation || {};
                    const n = (v.issues || []).length;
                    status.innerHTML = n > 0
                        ? '<i class="bi bi-check2"></i> Salvo — ' + n + ' pendência(s)/alerta(s)'
                        : '<i class="bi bi-check2-circle"></i> Salvo';
                    status.className = n > 0 ? 'save-status text-warning' : 'save-status text-light';
                    renderIssues(v);
                })
                .catch(() => { saveBtn.disabled = false; status.textContent = 'Erro ao salvar'; status.className = 'save-status text-danger'; });
        }

        function renderIssues(v) {
            const bar = document.getElementById('issuesBar');
            const issues = v.issues || [];
            if (!issues.length) { bar.innerHTML = ''; return; }
            const cls = v.blocked ? 'alert-danger' : 'alert-warning';
            const label = v.blocked ? 'Pendências:' : 'Alertas:';
            const first = (issues[0].message || '').replace(/</g, '&lt;');
            bar.innerHTML = '<div class="alert ed-alert ' + cls + ' py-2 small mb-0"><strong>' + label + '</strong> ' +
                issues.length + ' item(ns). <span class="d-none d-md-inline">' + first + (issues.length > 1 ? '…' : '') + '</span></div>';
        }

        saveBtn.addEventListener('click', save);
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') { e.preventDefault(); save(); }
        });
        window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
    })();
    </script>
</body>
</html>
