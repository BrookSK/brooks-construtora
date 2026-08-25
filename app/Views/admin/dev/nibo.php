<?php $pageTitle = 'Testador API Nibo'; $currentPage = 'dev_nibo'; ?>
<?php ob_start(); ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h5 class="mb-0"><i class="bi bi-plug"></i> Testador da API Nibo</h5>
    <a href="https://nibo.readme.io/reference/como-utilizar-a-api" target="_blank" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-book"></i> Documentação
    </a>
</div>

<!-- Configuração do token -->
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-key"></i> Chave de API (ApiToken)</span>
        <span class="badge <?= $hasToken ? 'bg-success' : 'bg-secondary' ?>"><?= $hasToken ? 'Configurado' : 'Não configurado' ?></span>
    </div>
    <div class="card-body">
        <form method="POST" action="/admin/dev/nibo/save-token" class="row g-2 align-items-end">
            <div class="col-12 col-md-9">
                <label class="form-label small mb-1">Token</label>
                <input type="text" name="nibo_api_token" class="form-control form-control-sm" placeholder="Cole aqui o ApiToken do Nibo (Empresa &gt; Configurações &gt; API)" value="">
                <small class="text-muted">Fica salvo no sistema. Você também pode informar um token só para o teste, no campo de cada rota.</small>
            </div>
            <div class="col-12 col-md-3">
                <button type="submit" class="btn btn-sm btn-dark w-100"><i class="bi bi-save"></i> Salvar token</button>
            </div>
        </form>
        <div class="mt-2 small text-muted">
            <i class="bi bi-hdd-network"></i> Base: <code><?= htmlspecialchars($baseUrl) ?></code>
        </div>
    </div>
</div>

<!-- Token de teste (aplicado a todos os botões desta sessão) -->
<div class="card mb-3">
    <div class="card-body py-2">
        <label class="form-label small mb-1">Token para testes (opcional — sobrescreve o salvo)</label>
        <input type="text" id="testToken" class="form-control form-control-sm" placeholder="Se vazio, usa o token salvo acima">
    </div>
</div>

<?php foreach ($groups as $groupName => $endpoints): ?>
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-collection"></i> <?= htmlspecialchars($groupName) ?></div>
    <div class="card-body p-0">
        <div class="accordion accordion-flush" id="acc-<?= md5($groupName) ?>">
            <?php foreach ($endpoints as $ep): ?>
            <?php
            $mColors = ['GET' => 'success', 'POST' => 'primary', 'PUT' => 'warning', 'DELETE' => 'danger'];
            $mColor = $mColors[$ep['method']] ?? 'secondary';
            $accId = 'ep-' . $ep['key'];
            ?>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $accId ?>">
                        <span class="badge bg-<?= $mColor ?> me-2" style="min-width:58px;"><?= $ep['method'] ?></span>
                        <span class="fw-bold me-2"><?= htmlspecialchars($ep['label']) ?></span>
                        <code class="small text-muted"><?= htmlspecialchars($ep['path']) ?></code>
                    </button>
                </h2>
                <div id="<?= $accId ?>" class="accordion-collapse collapse" data-bs-parent="#acc-<?= md5($groupName) ?>">
                    <div class="accordion-body">
                        <p class="text-muted small mb-3"><?= htmlspecialchars($ep['description'] ?? '') ?>
                            <?php if (!empty($ep['doc'])): ?>
                            · <a href="<?= htmlspecialchars($ep['doc']) ?>" target="_blank">doc</a>
                            <?php endif; ?>
                        </p>

                        <div class="row g-3">
                            <div class="col-12 col-lg-5">
                                <?php if (!empty($ep['needs_id'])): ?>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">ID (obrigatório)</label>
                                    <input type="text" class="form-control form-control-sm ep-id" data-key="<?= $ep['key'] ?>" placeholder="ID do registro">
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($ep['params'])): ?>
                                <?php foreach ($ep['params'] as $pName => $pLabel): ?>
                                <div class="mb-2">
                                    <label class="form-label small mb-1"><?= htmlspecialchars($pLabel) ?> (obrigatório)</label>
                                    <input type="text" class="form-control form-control-sm ep-param" data-key="<?= $ep['key'] ?>" data-param="<?= htmlspecialchars($pName) ?>" placeholder="<?= htmlspecialchars($pName) ?>">
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>

                                <?php if ($ep['method'] === 'GET'): ?>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Query (OData) — JSON</label>
                                    <textarea class="form-control form-control-sm font-monospace ep-query" data-key="<?= $ep['key'] ?>" rows="4"><?= htmlspecialchars(json_encode($ep['sample_query'] ?? new stdClass(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></textarea>
                                </div>
                                <?php endif; ?>

                                <?php if (in_array($ep['method'], ['POST', 'PUT'])): ?>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Body — JSON</label>
                                    <textarea class="form-control form-control-sm font-monospace ep-body" data-key="<?= $ep['key'] ?>" rows="8"><?= htmlspecialchars(json_encode($ep['sample_body'] ?? new stdClass(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></textarea>
                                </div>
                                <?php endif; ?>

                                <button type="button" class="btn btn-sm btn-<?= $mColor ?> ep-test" data-key="<?= $ep['key'] ?>" data-method="<?= $ep['method'] ?>" data-needsid="<?= !empty($ep['needs_id']) ? '1' : '0' ?>">
                                    <i class="bi bi-play-fill"></i> Testar
                                </button>
                            </div>

                            <div class="col-12 col-lg-7">
                                <label class="form-label small mb-1 d-flex justify-content-between">
                                    <span>Resposta</span>
                                    <span class="ep-status small" data-key="<?= $ep['key'] ?>"></span>
                                </label>
                                <pre class="bg-dark text-light p-2 rounded ep-result" data-key="<?= $ep['key'] ?>" style="max-height:340px; overflow:auto; font-size:.78rem; min-height:80px;">Clique em "Testar" para ver a resposta…</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
(function () {
    document.querySelectorAll('.ep-test').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const key = this.dataset.key;
            const method = this.dataset.method;
            const needsId = this.dataset.needsid === '1';
            const resultEl = document.querySelector('.ep-result[data-key="' + key + '"]');
            const statusEl = document.querySelector('.ep-status[data-key="' + key + '"]');
            const idEl = document.querySelector('.ep-id[data-key="' + key + '"]');
            const queryEl = document.querySelector('.ep-query[data-key="' + key + '"]');
            const bodyEl = document.querySelector('.ep-body[data-key="' + key + '"]');
            const token = (document.getElementById('testToken').value || '').trim();

            if (needsId && (!idEl || !idEl.value.trim())) {
                statusEl.innerHTML = '<span class="text-danger">Informe o ID</span>';
                return;
            }

            const params = new URLSearchParams();
            params.set('key', key);
            if (token) params.set('token', token);
            if (idEl) params.set('id', idEl.value.trim());
            if (queryEl) params.set('query', queryEl.value.trim());
            if (bodyEl) params.set('body', bodyEl.value.trim());

            // Parâmetros de path múltiplos ({scheduleId}, {fileId}, {annotationId}...)
            const paramEls = document.querySelectorAll('.ep-param[data-key="' + key + '"]');
            if (paramEls.length) {
                const obj = {};
                let missing = false;
                paramEls.forEach(function (el) {
                    const v = (el.value || '').trim();
                    if (!v) missing = true;
                    obj[el.dataset.param] = v;
                });
                if (missing) {
                    statusEl.innerHTML = '<span class="text-danger">Preencha os parâmetros</span>';
                    return;
                }
                params.set('params', JSON.stringify(obj));
            }

            this.disabled = true;
            const original = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Testando...';
            statusEl.innerHTML = '';
            resultEl.textContent = 'Aguardando resposta...';

            try {
                const resp = await fetch('/admin/dev/nibo/test', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: params.toString()
                });
                const data = await resp.json();

                const ok = data.ok;
                const st = data.status || resp.status;
                statusEl.innerHTML = (ok ? '<span class="text-success">' : '<span class="text-danger">')
                    + 'HTTP ' + st + (data.duration_ms != null ? ' · ' + data.duration_ms + 'ms' : '') + '</span>';

                let out = '';
                out += '▶ REQUEST\n';
                out += (data.request ? JSON.stringify(data.request, null, 2) : method) + '\n';
                out += 'URL: ' + (data.url || '') + '\n\n';
                out += '◀ RESPONSE\n';
                out += (typeof data.response === 'string') ? data.response : JSON.stringify(data.response, null, 2);
                if (data.error) out += '\n\n⚠ ' + data.error;
                resultEl.textContent = out;
            } catch (e) {
                statusEl.innerHTML = '<span class="text-danger">Erro</span>';
                resultEl.textContent = 'Falha na requisição de teste: ' + e.message;
            } finally {
                this.disabled = false;
                this.innerHTML = original;
            }
        });
    });
})();
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
