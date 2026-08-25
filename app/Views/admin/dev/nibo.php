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

<!-- Testar todas as rotas (apenas leitura/GET seguras) -->
<div class="card mb-3 border-primary border-opacity-50">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <strong><i class="bi bi-lightning-charge"></i> Testar todas as rotas</strong>
                <div class="text-muted small">
                    Percorre <strong>todas</strong> as rotas. Por padrão executa só as de <strong>consulta (GET)</strong>.
                    Rotas que exigem ID/parâmetros só são testadas se os campos estiverem preenchidos (senão são <em>puladas</em>).
                </div>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="includeWrite">
                    <label class="form-check-label small text-danger" for="includeWrite">
                        Incluir rotas de escrita (POST/PUT/DELETE) — <strong>cria/altera/exclui dados reais</strong> na sua conta Nibo
                    </label>
                </div>
            </div>
            <button type="button" id="runAllBtn" class="btn btn-primary">
                <i class="bi bi-play-circle"></i> Testar Todas
            </button>
        </div>
        <div id="runAllSummary" class="mt-3 d-none">
            <div class="d-flex gap-2 flex-wrap mb-2">
                <span class="badge bg-secondary" id="sumTotal">0 testadas</span>
                <span class="badge bg-success" id="sumOk">0 ok</span>
                <span class="badge bg-danger" id="sumFail">0 falhas</span>
                <span class="badge bg-warning text-dark" id="sumSkip">0 puladas</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light"><tr><th style="width:40px;"></th><th>Rota</th><th>Endpoint</th><th class="text-end">Status</th></tr></thead>
                    <tbody id="runAllRows"></tbody>
                </table>
            </div>
        </div>
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
            <?php $needsParams = !empty($ep['needs_id']) || !empty($ep['params']); ?>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $accId ?>">
                        <span class="ep-head-status me-2" data-key="<?= $ep['key'] ?>" style="min-width:22px; text-align:center;"><i class="bi bi-dash-circle text-muted"></i></span>
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

                                <?php $isSafe = ($ep['method'] === 'GET' && empty($ep['needs_id']) && empty($ep['params'])); ?>
                                <button type="button" class="btn btn-sm btn-<?= $mColor ?> ep-test" data-key="<?= $ep['key'] ?>" data-method="<?= $ep['method'] ?>" data-needsid="<?= !empty($ep['needs_id']) ? '1' : '0' ?>" data-safe="<?= $isSafe ? '1' : '0' ?>">
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
    function setHeadStatus(key, kind) {
        const el = document.querySelector('.ep-head-status[data-key="' + key + '"]');
        if (!el) return;
        if (kind === 'ok') el.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
        else if (kind === 'fail') el.innerHTML = '<i class="bi bi-x-circle-fill text-danger"></i>';
        else if (kind === 'loading') el.innerHTML = '<span class="spinner-border spinner-border-sm text-muted"></span>';
        else el.innerHTML = '<i class="bi bi-dash-circle text-muted"></i>';
    }

    // Executa o teste de um endpoint. Retorna {ok, status, error}.
    async function runTest(key) {
        const btn = document.querySelector('.ep-test[data-key="' + key + '"]');
        const method = btn ? btn.dataset.method : 'GET';
        const needsId = btn && btn.dataset.needsid === '1';
        const resultEl = document.querySelector('.ep-result[data-key="' + key + '"]');
        const statusEl = document.querySelector('.ep-status[data-key="' + key + '"]');
        const idEl = document.querySelector('.ep-id[data-key="' + key + '"]');
        const queryEl = document.querySelector('.ep-query[data-key="' + key + '"]');
        const bodyEl = document.querySelector('.ep-body[data-key="' + key + '"]');
        const token = (document.getElementById('testToken').value || '').trim();

        if (needsId && (!idEl || !idEl.value.trim())) {
            if (statusEl) statusEl.innerHTML = '<span class="text-danger">Informe o ID</span>';
            setHeadStatus(key, 'fail');
            return { ok: false, status: 0, error: 'ID ausente' };
        }

        const params = new URLSearchParams();
        params.set('key', key);
        if (token) params.set('token', token);
        if (idEl) params.set('id', idEl.value.trim());
        if (queryEl) params.set('query', queryEl.value.trim());
        if (bodyEl) params.set('body', bodyEl.value.trim());

        const paramEls = document.querySelectorAll('.ep-param[data-key="' + key + '"]');
        if (paramEls.length) {
            const obj = {}; let missing = false;
            paramEls.forEach(function (el) {
                const v = (el.value || '').trim();
                if (!v) missing = true;
                obj[el.dataset.param] = v;
            });
            if (missing) {
                if (statusEl) statusEl.innerHTML = '<span class="text-danger">Preencha os parâmetros</span>';
                setHeadStatus(key, 'fail');
                return { ok: false, status: 0, error: 'Parâmetros ausentes' };
            }
            params.set('params', JSON.stringify(obj));
        }

        if (btn) { btn.disabled = true; }
        setHeadStatus(key, 'loading');
        if (statusEl) statusEl.innerHTML = '';
        if (resultEl) resultEl.textContent = 'Aguardando resposta...';

        try {
            const resp = await fetch('/admin/dev/nibo/test', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params.toString()
            });
            const data = await resp.json();
            const ok = !!data.ok;
            const st = data.status || resp.status;
            if (statusEl) statusEl.innerHTML = (ok ? '<span class="text-success">' : '<span class="text-danger">')
                + 'HTTP ' + st + (data.duration_ms != null ? ' · ' + data.duration_ms + 'ms' : '') + '</span>';
            if (resultEl) {
                let out = '▶ REQUEST\n' + (data.request ? JSON.stringify(data.request, null, 2) : method) + '\n';
                out += 'URL: ' + (data.url || '') + '\n\n◀ RESPONSE\n';
                out += (typeof data.response === 'string') ? data.response : JSON.stringify(data.response, null, 2);
                if (data.error) out += '\n\n⚠ ' + data.error;
                resultEl.textContent = out;
            }
            setHeadStatus(key, ok ? 'ok' : 'fail');
            return { ok: ok, status: st, error: data.error || null };
        } catch (e) {
            if (statusEl) statusEl.innerHTML = '<span class="text-danger">Erro</span>';
            if (resultEl) resultEl.textContent = 'Falha na requisição de teste: ' + e.message;
            setHeadStatus(key, 'fail');
            return { ok: false, status: 0, error: e.message };
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    // Botões individuais
    document.querySelectorAll('.ep-test').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const original = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Testando...';
            const self = this;
            runTest(this.dataset.key).finally(function () { self.innerHTML = original; });
        });
    });

    // Verifica se um endpoint tem todos os campos obrigatórios preenchidos
    function isReady(btn) {
        const key = btn.dataset.key;
        if (btn.dataset.needsid === '1') {
            const idEl = document.querySelector('.ep-id[data-key="' + key + '"]');
            if (!idEl || !idEl.value.trim()) return false;
        }
        const paramEls = document.querySelectorAll('.ep-param[data-key="' + key + '"]');
        for (const el of paramEls) { if (!el.value.trim()) return false; }
        return true;
    }

    // Testar TODAS as rotas
    const runAllBtn = document.getElementById('runAllBtn');
    if (runAllBtn) {
        runAllBtn.addEventListener('click', async function () {
            const includeWrite = document.getElementById('includeWrite').checked;
            const allBtns = Array.from(document.querySelectorAll('.ep-test'));
            const summary = document.getElementById('runAllSummary');
            const rows = document.getElementById('runAllRows');
            summary.classList.remove('d-none');
            rows.innerHTML = '';

            if (includeWrite && !confirm('ATENÇÃO: incluir rotas de escrita vai CRIAR/ALTERAR/EXCLUIR dados reais na sua conta Nibo (apenas as que tiverem parâmetros preenchidos). Continuar?')) {
                return;
            }

            let total = 0, okCount = 0, failCount = 0, skipCount = 0;
            this.disabled = true;
            const original = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Testando...';

            for (const btn of allBtns) {
                const key = btn.dataset.key;
                const method = btn.dataset.method;
                const isWrite = (method !== 'GET');
                const item = btn.closest('.accordion-item');
                const label = item.querySelector('.fw-bold').textContent.trim();
                const path = item.querySelector('code').textContent.trim();

                const tr = document.createElement('tr');
                rows.appendChild(tr);

                // Regras de pulo: escrita desabilitada, ou faltam parâmetros
                let skipReason = '';
                if (isWrite && !includeWrite) skipReason = 'escrita (desativada)';
                else if (!isReady(btn)) skipReason = 'requer parâmetro';

                if (skipReason) {
                    skipCount++; total++;
                    tr.innerHTML = '<td class="text-center"><i class="bi bi-slash-circle text-warning"></i></td>'
                        + '<td class="small">' + label + '</td>'
                        + '<td><code class="small">' + path + '</code> <span class="badge bg-secondary">' + method + '</span></td>'
                        + '<td class="text-end small text-muted">pulada · ' + skipReason + '</td>';
                    document.getElementById('sumTotal').textContent = total + ' testadas';
                    document.getElementById('sumSkip').textContent = skipCount + ' puladas';
                    continue;
                }

                tr.innerHTML = '<td class="text-center"><span class="spinner-border spinner-border-sm text-muted"></span></td>'
                    + '<td class="small">' + label + '</td>'
                    + '<td><code class="small">' + path + '</code> <span class="badge bg-secondary">' + method + '</span></td>'
                    + '<td class="text-end small">...</td>';

                const res = await runTest(key);
                total++;
                if (res.ok) okCount++; else failCount++;

                tr.children[0].innerHTML = res.ok
                    ? '<i class="bi bi-check-circle-fill text-success"></i>'
                    : '<i class="bi bi-x-circle-fill text-danger"></i>';
                tr.children[3].innerHTML = (res.ok ? '<span class="text-success">' : '<span class="text-danger">')
                    + 'HTTP ' + (res.status || '—') + '</span>' + (res.error ? ' <small class="text-muted">' + res.error + '</small>' : '');

                document.getElementById('sumTotal').textContent = total + ' testadas';
                document.getElementById('sumOk').textContent = okCount + ' ok';
                document.getElementById('sumFail').textContent = failCount + ' falhas';
            }

            this.disabled = false;
            this.innerHTML = original;
        });
    }
})();
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
