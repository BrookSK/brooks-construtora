<?php $pageTitle = 'Diagnóstico — Contratos'; $currentPage = 'contracts'; ?>
<?php ob_start(); ?>

<?php
function d_ms($v): string { $v = (int)$v; return $v >= 1000 ? number_format($v / 1000, 1, ',', '.') . ' s' : $v . ' ms'; }
function d_op(string $op): string {
    return match ($op) {
        'extract'    => '<span class="badge bg-info text-dark">Extração PDF</span>',
        'generate'   => '<span class="badge bg-primary">Geração</span>',
        'regenerate' => '<span class="badge bg-secondary">Regeração</span>',
        default      => '<span class="badge bg-light text-dark">' . htmlspecialchars($op) . '</span>',
    };
}
$avg = (int)round((float)($stats['avg_ms'] ?? 0));
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h5 class="mb-0"><i class="bi bi-file-earmark-ruled"></i> Elaboração de Contrato</h5>
    <form method="POST" action="/admin/contracts/diagnostics/clear" onsubmit="return confirm('Limpar todos os logs de diagnóstico?');">
        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i> Limpar logs</button>
    </form>
</div>

<?php $contractTab = 'diagnostics'; require __DIR__ . '/_subnav.php'; ?>

<!-- Resumo -->
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="card stat-card"><div class="card-body py-2">
            <div class="text-muted small">Chamadas</div>
            <div class="stat-number"><?= (int)($stats['total'] ?? 0) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card" style="border-left-color:#28a745;"><div class="card-body py-2">
            <div class="text-muted small">Sucesso</div>
            <div class="stat-number text-success"><?= (int)($stats['ok'] ?? 0) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card" style="border-left-color:#dd3333;"><div class="card-body py-2">
            <div class="text-muted small">Erros</div>
            <div class="stat-number text-danger"><?= (int)($stats['fail'] ?? 0) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card" style="border-left-color:#446084;"><div class="card-body py-2">
            <div class="text-muted small">Tempo médio</div>
            <div class="stat-number"><?= $avg > 0 ? d_ms($avg) : '—' ?></div>
        </div></div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-3"><div class="card-body py-2">
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <span class="small text-muted">Filtrar:</span>
        <?php
        $tabs = ['' => 'Todos', 'extract' => 'Extração', 'generate' => 'Geração', 'regenerate' => 'Regeração', 'errors' => 'Somente erros'];
        foreach ($tabs as $key => $label):
            $active = ($filter === $key);
        ?>
            <a href="/admin/contracts/diagnostics<?= $key ? '?filter=' . $key : '' ?>"
               class="btn btn-sm <?= $active ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>
</div></div>

<!-- Tabela de chamadas -->
<div class="card mb-3">
    <div class="card-header py-2"><i class="bi bi-list-columns-reverse"></i> Chamadas à IA</div>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0 align-middle">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Operação</th>
                    <th>Modelo</th>
                    <th>HTTP</th>
                    <th>Duração</th>
                    <th>Status</th>
                    <th class="d-none d-md-table-cell">Contexto</th>
                    <th class="d-none d-lg-table-cell">Usuário</th>
                    <th class="text-end">Detalhes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Nenhuma chamada registrada ainda.</td></tr>
                <?php else: foreach ($logs as $l): ?>
                    <tr>
                        <td class="small text-nowrap"><?= !empty($l['created_at']) ? date('d/m/Y H:i:s', strtotime($l['created_at'])) : '—' ?></td>
                        <td><?= d_op($l['operation'] ?? '') ?></td>
                        <td class="small"><?= htmlspecialchars($l['model'] ?? '—') ?></td>
                        <td>
                            <?php $hs = (int)($l['http_status'] ?? 0); ?>
                            <span class="badge bg-<?= $hs === 200 ? 'success' : ($hs === 0 ? 'secondary' : 'danger') ?>"><?= $hs ?: '—' ?></span>
                        </td>
                        <td class="small"><?= d_ms($l['duration_ms'] ?? 0) ?></td>
                        <td>
                            <?php if (!empty($l['success'])): ?>
                                <span class="text-success"><i class="bi bi-check-circle-fill"></i></span>
                            <?php else: ?>
                                <span class="text-danger" title="<?= htmlspecialchars($l['error_message'] ?? '') ?>"><i class="bi bi-x-circle-fill"></i></span>
                            <?php endif; ?>
                        </td>
                        <td class="d-none d-md-table-cell small text-truncate" style="max-width:180px;" title="<?= htmlspecialchars($l['context'] ?? '') ?>"><?= htmlspecialchars($l['context'] ?? '—') ?></td>
                        <td class="d-none d-lg-table-cell small"><?= htmlspecialchars($l['user_name'] ?? '—') ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary btn-log-detail" data-id="<?= (int)$l['id'] ?>">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Error log do PHP -->
<div class="card">
    <div class="card-header py-2"><i class="bi bi-terminal"></i> Error log do PHP (linhas relacionadas)</div>
    <div class="card-body">
        <?php if (empty($errorLog)): ?>
            <p class="text-muted small mb-0">Nenhuma entrada acessível no error_log do PHP, ou o caminho não está configurado.</p>
        <?php else: ?>
            <pre class="mb-0 small" style="white-space:pre-wrap; max-height:320px; overflow:auto; background:#1e1e1e; color:#d4d4d4; padding:1rem; border-radius:6px;"><?= htmlspecialchars($errorLog) ?></pre>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de detalhes -->
<div class="modal fade" id="logModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-activity"></i> Detalhe da chamada</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="logMeta" class="mb-3 small"></div>
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-req">Requisição</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-res">Resposta</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-err">Erro</button></li>
                </ul>
                <div class="tab-content pt-2">
                    <div class="tab-pane fade show active" id="tab-req"><pre class="small" id="logReq" style="white-space:pre-wrap; max-height:400px; overflow:auto;"></pre></div>
                    <div class="tab-pane fade" id="tab-res"><pre class="small" id="logRes" style="white-space:pre-wrap; max-height:400px; overflow:auto;"></pre></div>
                    <div class="tab-pane fade" id="tab-err"><pre class="small text-danger" id="logErr" style="white-space:pre-wrap; max-height:400px; overflow:auto;"></pre></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const modal = new bootstrap.Modal(document.getElementById('logModal'));
    document.querySelectorAll('.btn-log-detail').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            fetch('/admin/contracts/diagnostics/detail/' + id)
                .then(r => r.json())
                .then(res => {
                    if (res.error) { alert(res.error); return; }
                    const l = res.log;
                    document.getElementById('logMeta').innerHTML =
                        '<div class="row g-2">' +
                        '<div class="col-6"><span class="text-muted">Operação:</span> <strong>' + esc(l.operation) + '</strong></div>' +
                        '<div class="col-6"><span class="text-muted">Modelo:</span> <strong>' + esc(l.model || '—') + '</strong></div>' +
                        '<div class="col-6"><span class="text-muted">HTTP:</span> ' + esc(l.http_status || '—') + '</div>' +
                        '<div class="col-6"><span class="text-muted">Duração:</span> ' + esc(l.duration_ms || 0) + ' ms</div>' +
                        '<div class="col-12"><span class="text-muted">Contexto:</span> ' + esc(l.context || '—') + '</div>' +
                        '</div>';
                    document.getElementById('logReq').textContent = l.request_payload || '(vazio)';
                    document.getElementById('logRes').textContent = l.response_body || '(vazio)';
                    document.getElementById('logErr').textContent = l.error_message || '(sem erro)';
                    modal.show();
                })
                .catch(() => alert('Erro ao carregar detalhe.'));
        });
    });
    function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
})();
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
