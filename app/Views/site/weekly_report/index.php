<?php
/**
 * View standalone do relatório da lista semanal.
 * Variáveis vindas do controller: $sites, $siteById, $statusCount, $totalSites,
 * $activeCount, $analysis, $weeklyBySite, $noResponsible, $onlyEpi, $strangers, $generatedAt
 */

/** Rótulo amigável e cor do status */
function st_label(string $s): array {
    return match ($s) {
        'active', 'em_andamento' => ['Em andamento', '#16a34a', '#dcfce7'],
        'finalizado', 'completed' => ['Finalizado', '#64748b', '#e2e8f0'],
        'nao_iniciado' => ['Não iniciado', '#d97706', '#fef3c7'],
        'atrasado' => ['Atrasado', '#dc2626', '#fee2e2'],
        'inactive' => ['Inativo', '#64748b', '#e2e8f0'],
        default => [$s ?: 'Sem status', '#64748b', '#e2e8f0'],
    };
}
function badge(string $status): string {
    [$label, $fg, $bg] = st_label($status);
    return '<span class="badge" style="color:' . $fg . ';background:' . $bg . '">' . htmlspecialchars($label) . '</span>';
}
function h($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

/** Rótulo + cor do veredito (certo/errado) de uma obra. */
function verdict_badge(string $status): string {
    [$label, $fg, $bg] = match ($status) {
        'ok'         => ['✔ Certo', '#166534', '#dcfce7'],
        'so_epi'     => ['Só EPI, falta engenheiro', '#b91c1c', '#fee2e2'],
        'falta'      => ['Falta alguém', '#b91c1c', '#fee2e2'],
        'extra'      => ['Tem a mais', '#92400e', '#fef3c7'],
        'divergente' => ['Divergente', '#b91c1c', '#fee2e2'],
        'sem_ninguem'=> ['Sem ninguém', '#b91c1c', '#fee2e2'],
        'sem_regra'  => ['Sem regra', '#64748b', '#e2e8f0'],
        default      => [$status, '#64748b', '#e2e8f0'],
    };
    return '<span class="tag" style="color:' . $fg . ';background:' . $bg . '">' . h($label) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex,nofollow">
<title>Relação — Lista Semanal de Materiais</title>
<style>
    * { box-sizing: border-box; }
    body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background: #f1f5f9; color: #0f172a; line-height: 1.5; }
    .wrap { max-width: 1100px; margin: 0 auto; padding: 24px 16px 64px; }
    header.top { background: #0f172a; color: #fff; padding: 28px 16px; }
    header.top .inner { max-width: 1100px; margin: 0 auto; }
    header.top h1 { margin: 0 0 6px; font-size: 22px; }
    header.top p { margin: 0; color: #cbd5e1; font-size: 13px; }
    .stats { display: flex; flex-wrap: wrap; gap: 12px; margin: 20px 0 8px; }
    .stat { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 18px; flex: 1; min-width: 130px; }
    .stat .num { font-size: 28px; font-weight: 700; }
    .stat .lbl { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
    .badge { display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: 11px; font-weight: 600; white-space: nowrap; }
    .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; margin: 16px 0; overflow: hidden; }
    .card > h2 { margin: 0; padding: 16px 20px; font-size: 17px; border-bottom: 1px solid #eef2f7; display: flex; align-items: center; gap: 10px; }
    .card .count { font-size: 12px; font-weight: 600; color: #64748b; background: #f1f5f9; padding: 2px 10px; border-radius: 999px; }
    .card .body { padding: 6px 20px 18px; }
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th, td { text-align: left; padding: 9px 8px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
    th { font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: #94a3b8; }
    td.code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; color: #475569; white-space: nowrap; font-size: 12px; }
    .tag { display:inline-block; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }
    .tag.ok { color: #166534; background: #dcfce7; }
    .tag.falta { color: #b91c1c; background: #fee2e2; }
    .tag.semmatch { color: #92400e; background: #fef3c7; }
    .tag.extra { color: #475569; background: #e2e8f0; }
    .sub { font-size: 12px; color: #64748b; margin: 14px 0 6px; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; }
    .muted { color: #94a3b8; font-style: italic; padding: 6px 0; }
    .legend { font-size: 12px; color: #64748b; margin-top: 8px; }
    .alert { border-left: 4px solid; padding: 10px 14px; border-radius: 8px; margin: 8px 0; font-size: 13px; }
    .alert.warn { background: #fffbeb; border-color: #f59e0b; }
    .alert.info { background: #eff6ff; border-color: #3b82f6; }
    .refresh { color:#cbd5e1; font-size:12px; text-decoration:none; border:1px solid #334155; padding:5px 12px; border-radius:8px; }
    .refresh:hover { background:#1e293b; }
    /* editor de chips */
    .chips { display:flex; flex-wrap:wrap; gap:6px; }
    .chip { display:inline-flex; align-items:center; gap:5px; padding:3px 9px; border:1px solid #cbd5e1; border-radius:999px; font-size:12px; cursor:pointer; user-select:none; background:#fff; color:#475569; }
    .chip input { margin:0; cursor:pointer; }
    .chip.on { background:#dcfce7; border-color:#86efac; color:#166534; font-weight:600; }
    .chip.disabled { opacity:.45; cursor:not-allowed; }
    .rowactions { margin-top:8px; display:flex; align-items:center; gap:8px; }
    .btn-save, .btn-fix { font-size:12px; font-weight:600; border-radius:8px; padding:5px 12px; cursor:pointer; border:1px solid transparent; }
    .btn-save { background:#0f172a; color:#fff; }
    .btn-save:disabled { background:#e2e8f0; color:#94a3b8; cursor:default; }
    .btn-fix { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
    .btn-fix:hover { background:#dbeafe; }
    .save-status { font-size:12px; }
    .save-status.ok { color:#16a34a; }
    .save-status.err { color:#dc2626; }
    tr.dirty { background:#fffdf5; }
</style>
</head>
<body>
<header class="top">
    <div class="inner" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
        <div>
            <h1>Relação — Lista Semanal de Materiais</h1>
            <p>Dados ao vivo do banco · gerado em <?= h($generatedAt) ?></p>
        </div>
        <a class="refresh" href="">↻ Atualizar</a>
    </div>
</header>

<div class="wrap">

    <div class="stats">
        <div class="stat"><div class="num"><?= (int) $totalSites ?></div><div class="lbl">Obras cadastradas</div></div>
        <div class="stat"><div class="num" style="color:#16a34a"><?= (int) $activeCount ?></div><div class="lbl">Em andamento</div></div>
        <?php foreach ($statusCount as $st => $c): if (in_array($st, ['active','em_andamento'], true)) continue; ?>
            <div class="stat"><div class="num"><?= (int) $c ?></div><div class="lbl"><?= h(st_label($st)[0]) ?></div></div>
        <?php endforeach; ?>
    </div>

    <div class="legend">
        <span class="tag ok">OK</span> marcado na semanal &nbsp;
        <span class="tag falta">FALTA</span> declarou mas não está marcado &nbsp;
        <span class="tag semmatch">SEM MATCH</span> não existe no sistema &nbsp;
        <span class="tag extra">EXTRA</span> marcado mas não declarado
    </div>

    <?php foreach ($analysis as $mg => $entry): ?>
        <div class="card">
            <h2>
                <?= h($mg) ?>
                <?php if (!empty($entry['epi'])): ?>
                    <span class="badge" style="color:#6d28d9;background:#ede9fe">EPI · todas as obras</span>
                <?php endif; ?>
                <span class="count"><?= count($entry['weekly']) ?> na semanal</span>
            </h2>
            <div class="body">

                <?php if (!empty($entry['epi'])): ?>
                    <div class="alert info">Este responsável precisa estar na lista semanal de <strong>todas</strong> as obras (pedidos de EPI).</div>
                    <div class="sub">Obras EM ANDAMENTO em que ele NÃO está na semanal (<?= count($entry['missingActive']) ?>)</div>
                    <?php if (empty($entry['missingActive'])): ?>
                        <div class="muted">Está em todas as obras ativas. ✔</div>
                    <?php else: ?>
                        <table>
                            <tr><th style="width:120px">Código</th><th>Obra</th><th style="width:130px">Status</th></tr>
                            <?php foreach ($entry['missingActive'] as $s): ?>
                                <tr>
                                    <td class="code"><?= h($s['code'] ?: '—') ?></td>
                                    <td><?= h($s['name']) ?></td>
                                    <td><?= badge($s['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="sub">Listagem informada por ele × sistema</div>
                    <table>
                        <tr><th style="width:120px">Situação</th><th>Declarado / Obra no sistema</th><th style="width:130px">Status</th></tr>
                        <?php foreach ($entry['declaredRows'] as $row): ?>
                            <tr>
                                <?php if (!$row['site']): ?>
                                    <td><span class="tag semmatch">SEM MATCH</span></td>
                                    <td><?= h($row['termo']) ?> <span class="muted">— não encontrada no sistema</span></td>
                                    <td>—</td>
                                <?php else: ?>
                                    <td>
                                        <?php if ($row['weekly']): ?><span class="tag ok">OK</span>
                                        <?php else: ?><span class="tag falta">FALTA</span><?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="muted"><?= h($row['termo']) ?></span><br>
                                        <span class="code"><?= h($row['site']['code'] ?: '—') ?></span> · <?= h($row['site']['name']) ?>
                                    </td>
                                    <td><?= badge($row['site']['status']) ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </table>

                    <div class="sub">Marcado na semanal mas NÃO apareceu na listagem dele (<?= count($entry['extra']) ?>)</div>
                    <?php if (empty($entry['extra'])): ?>
                        <div class="muted">Nada a mais — tudo que está marcado foi declarado.</div>
                    <?php else: ?>
                        <table>
                            <tr><th style="width:120px">Código</th><th>Obra</th><th style="width:130px">Status</th></tr>
                            <?php foreach ($entry['extra'] as $s): ?>
                                <tr>
                                    <td class="code"><?= h($s['code'] ?: '—') ?></td>
                                    <td><?= h($s['name']) ?> <span class="tag extra">EXTRA</span></td>
                                    <td><?= badge($s['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                <?php endif; ?>

            </div>
        </div>
    <?php endforeach; ?>

    <!-- Obras ativas sem responsável -->
    <div class="card">
        <h2>Obras em andamento sem NENHUM responsável na semanal <span class="count"><?= count($noResponsible) ?></span></h2>
        <div class="body">
            <?php if (empty($noResponsible)): ?>
                <div class="muted">Nenhuma. ✔</div>
            <?php else: ?>
                <table>
                    <tr><th style="width:120px">Código</th><th>Obra</th><th style="width:130px">Status</th></tr>
                    <?php foreach ($noResponsible as $s): ?>
                        <tr><td class="code"><?= h($s['code'] ?: '—') ?></td><td><?= h($s['name']) ?></td><td><?= badge($s['status']) ?></td></tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Só EPI, sem engenheiro -->
    <div class="card">
        <h2>Obras em andamento só com Eduardo Carvalho (EPI), sem engenheiro <span class="count"><?= count($onlyEpi) ?></span></h2>
        <div class="body">
            <?php if (empty($onlyEpi)): ?>
                <div class="muted">Nenhuma. ✔</div>
            <?php else: ?>
                <table>
                    <tr><th style="width:120px">Código</th><th>Obra</th><th style="width:130px">Status</th></tr>
                    <?php foreach ($onlyEpi as $s): ?>
                        <tr><td class="code"><?= h($s['code'] ?: '—') ?></td><td><?= h($s['name']) ?></td><td><?= badge($s['status']) ?></td></tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Estranhos -->
    <div class="card">
        <h2>Responsáveis na semanal que NÃO são os gerentes informados <span class="count"><?= count($strangers) ?></span></h2>
        <div class="body">
            <?php if (empty($strangers)): ?>
                <div class="muted">Nenhum — todos os responsáveis são gerentes conhecidos.</div>
            <?php else: ?>
                <table>
                    <tr><th style="width:120px">Código</th><th>Obra</th><th>Pessoa</th></tr>
                    <?php foreach ($strangers as $x): ?>
                        <tr>
                            <td class="code"><?= h($x['site']['code'] ?? '—') ?></td>
                            <td><?= h($x['site']['name'] ?? '?') ?></td>
                            <td><?= h($x['name']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabela geral: todas as obras e quem está na semanal -->
    <div class="card">
        <h2>Todas as obras × responsáveis na semanal <span class="count"><?= count($sites) ?></span></h2>
        <div class="body">
            <table>
                <tr>
                    <th style="width:90px">Código</th>
                    <th>Obra</th>
                    <th style="width:100px">Status</th>
                    <th style="width:110px">Situação</th>
                    <th>Certo (esperado)</th>
                    <th style="min-width:360px">Ajustar (marcar quem fica na semanal)</th>
                </tr>
                <?php foreach ($sites as $s):
                    $sid = (int) $s['id'];
                    $current = $weeklyBySite[$sid] ?? [];
                    $v = $verdictBySite[$sid] ?? null;
                    $expected = $v['expected'] ?? [];
                    $missing  = $v['missing'] ?? [];
                    $extra    = $v['extra'] ?? [];
                    // pessoas fora da lista de gerentes (para exibir como aviso)
                    $others = [];
                    foreach (array_keys($current) as $n) {
                        if (strpos($n, '__outro__:') === 0) $others[] = substr($n, strlen('__outro__:'));
                    }
                ?>
                    <tr data-site="<?= $sid ?>" data-expected="<?= h(implode('|', $expected)) ?>">
                        <td class="code"><?= h($s['code'] ?: '—') ?></td>
                        <td><?= h($s['name']) ?></td>
                        <td><?= badge($s['status']) ?></td>
                        <td class="cell-verdict"><?= $v ? verdict_badge($v['status']) : '—' ?></td>
                        <td class="cell-expected">
                            <?php if (empty($expected)): ?>
                                <span class="muted">—</span>
                            <?php else: ?>
                                <?= h(implode(', ', $expected)) ?>
                                <?php if ($missing): ?><br><span class="tag falta" style="margin-top:3px">falta: <?= h(implode(', ', $missing)) ?></span><?php endif; ?>
                                <?php if ($extra): ?><br><span class="tag extra" style="margin-top:3px">tirar: <?= h(implode(', ', $extra)) ?></span><?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="cell-edit">
                            <div class="chips">
                                <?php foreach ($managerNames as $mg):
                                    $pinId = $managerPinId[$mg] ?? 0;
                                    $checked = isset($current[$mg]);
                                    $disabled = $pinId <= 0;
                                ?>
                                    <label class="chip<?= $checked ? ' on' : '' ?><?= $disabled ? ' disabled' : '' ?>"
                                           title="<?= $disabled ? 'Sem PIN cadastrado — não é possível marcar' : '' ?>">
                                        <input type="checkbox"
                                               data-manager="<?= h($mg) ?>"
                                               <?= $checked ? 'checked' : '' ?>
                                               <?= $disabled ? 'disabled' : '' ?>>
                                        <?= h($mg) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($others): ?>
                                <div class="legend" style="margin-top:4px">também marcado (fora da lista): <?= h(implode(', ', $others)) ?></div>
                            <?php endif; ?>
                            <div class="rowactions">
                                <button type="button" class="btn-save" disabled>Salvar</button>
                                <button type="button" class="btn-fix" title="Marca exatamente o esperado">Aplicar o certo</button>
                                <span class="save-status"></span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <div class="legend" style="margin-top:10px">
                <strong>Situação:</strong> compara quem está na semanal hoje com o "certo" (declarações dos gerentes + Eduardo Carvalho/EPI em toda obra ativa).
                <em>Sem regra</em> = obra que nenhum gerente declarou.<br>
                <strong>Ajustar:</strong> marque/desmarque os gerentes e clique <em>Salvar</em>. "Aplicar o certo" marca automaticamente o esperado. As mudanças gravam direto no banco (lista semanal).
            </div>
        </div>
    </div>

</div>

<script>
(function () {
    var SAVE_URL = '/relatorio-lista-semanal/salvar';
    var TOKEN = <?= json_encode($token) ?>;

    function chipsOf(tr) { return Array.prototype.slice.call(tr.querySelectorAll('.chip input[type=checkbox]')); }
    function selectedManagers(tr) {
        return chipsOf(tr).filter(function (c) { return c.checked; }).map(function (c) { return c.getAttribute('data-manager'); });
    }
    function markDirty(tr, dirty) {
        tr.classList.toggle('dirty', dirty);
        var btn = tr.querySelector('.btn-save');
        if (btn) btn.disabled = !dirty;
    }
    function setStatus(tr, msg, kind) {
        var el = tr.querySelector('.save-status');
        if (!el) return;
        el.textContent = msg || '';
        el.className = 'save-status' + (kind ? ' ' + kind : '');
    }

    // Sincroniza o visual do chip (classe .on) com o checkbox
    function syncChip(input) {
        var label = input.closest('.chip');
        if (label) label.classList.toggle('on', input.checked);
    }

    document.querySelectorAll('tr[data-site]').forEach(function (tr) {
        // baseline inicial para saber se houve mudança
        tr._baseline = selectedManagers(tr).sort().join('|');

        chipsOf(tr).forEach(function (input) {
            input.addEventListener('change', function () {
                syncChip(input);
                var now = selectedManagers(tr).sort().join('|');
                markDirty(tr, now !== tr._baseline);
                setStatus(tr, '', '');
            });
        });

        // "Aplicar o certo": marca exatamente o esperado
        var fix = tr.querySelector('.btn-fix');
        if (fix) fix.addEventListener('click', function () {
            var expected = (tr.getAttribute('data-expected') || '').split('|').filter(Boolean);
            chipsOf(tr).forEach(function (input) {
                if (input.disabled) return;
                input.checked = expected.indexOf(input.getAttribute('data-manager')) !== -1;
                syncChip(input);
            });
            var now = selectedManagers(tr).sort().join('|');
            markDirty(tr, now !== tr._baseline);
            setStatus(tr, 'Pré-selecionado o esperado. Clique em Salvar.', '');
        });

        // Salvar
        var save = tr.querySelector('.btn-save');
        if (save) save.addEventListener('click', function () {
            var siteId = parseInt(tr.getAttribute('data-site'), 10);
            var managers = selectedManagers(tr);
            save.disabled = true;
            setStatus(tr, 'Salvando…', '');
            fetch(SAVE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token: TOKEN, site_id: siteId, managers: managers })
            })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (res) {
                if (res.ok && res.j && res.j.ok) {
                    tr._baseline = managers.slice().sort().join('|');
                    markDirty(tr, false);
                    setStatus(tr, '✔ Salvo', 'ok');
                } else {
                    save.disabled = false;
                    setStatus(tr, '✕ ' + ((res.j && res.j.error) || 'Erro ao salvar'), 'err');
                }
            })
            .catch(function () {
                save.disabled = false;
                setStatus(tr, '✕ Falha de conexão', 'err');
            });
        });
    });
})();
</script>
</body>
</html>
