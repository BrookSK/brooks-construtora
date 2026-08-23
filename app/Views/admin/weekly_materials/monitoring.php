<?php $pageTitle = 'Monitoramento de Pontualidade'; $currentPage = 'weekly_materials'; ?>
<?php ob_start(); ?>

<?php
function puncBadge($key, $label, $cls) { return '<span class="badge ' . $cls . '">' . $label . '</span>'; }
$periods = ['4w' => 'Últimas 4 semanas', '8w' => 'Últimas 8 semanas', '12w' => 'Últimas 12 semanas', '24w' => 'Últimas 24 semanas', '52w' => 'Último ano', 'custom' => 'Período personalizado'];
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <a href="/admin/weekly-materials" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar</a>
    <h5 class="mb-0"><i class="bi bi-graph-up"></i> Monitoramento de Pontualidade</h5>
    <span class="text-muted small">
        <?= date('d/m/Y', strtotime($filters['start'])) ?> a <?= date('d/m/Y', strtotime($filters['end'])) ?>
    </span>
</div>

<!-- Filtros por tempo + responsável -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="/admin/weekly-materials/monitoring" class="row g-2 align-items-end" id="monFilters">
            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Período</label>
                <select name="period" class="form-select form-select-sm" id="periodSelect" onchange="document.getElementById('customRange').style.display = this.value==='custom' ? 'flex' : 'none';">
                    <?php foreach ($periods as $k => $lbl): ?>
                    <option value="<?= $k ?>" <?= $filters['period'] === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-4 d-flex gap-2" id="customRange" style="display:<?= $filters['period'] === 'custom' ? 'flex' : 'none' ?>;">
                <div class="flex-fill">
                    <label class="form-label small mb-0">De</label>
                    <input type="date" name="start" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['start']) ?>">
                </div>
                <div class="flex-fill">
                    <label class="form-label small mb-0">Até</label>
                    <input type="date" name="end" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['end']) ?>">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Responsável</label>
                <select name="manager_id" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($managers as $m): ?>
                    <option value="<?= (int) $m['id'] ?>" <?= ($filters['manager_id'] == $m['id']) ? 'selected' : '' ?>><?= htmlspecialchars($m['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-funnel"></i> Filtrar</button>
            </div>
        </form>
    </div>
</div>

<!-- Cards de resumo -->
<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="card h-100"><div class="card-body py-2 px-3">
            <small class="text-muted d-block">Total de solicitações</small>
            <div class="fw-bold" style="font-size:1.8rem; line-height:1;"><?= (int) $totals['total'] ?></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 border-success"><div class="card-body py-2 px-3">
            <small class="text-muted d-block">Em dia</small>
            <div class="fw-bold text-success" style="font-size:1.8rem; line-height:1;"><?= (int) $totals['on_time'] ?></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 border-warning"><div class="card-body py-2 px-3">
            <small class="text-muted d-block">Preencheu com atraso</small>
            <div class="fw-bold text-warning" style="font-size:1.8rem; line-height:1;"><?= (int) $totals['late'] ?></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 border-danger"><div class="card-body py-2 px-3">
            <small class="text-muted d-block">Não preencheu</small>
            <div class="fw-bold text-danger" style="font-size:1.8rem; line-height:1;"><?= (int) $totals['not_filled'] ?></div>
        </div></div>
    </div>
</div>

<!-- Ranking por responsável -->
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-people"></i> Pontualidade por Responsável</div>
    <div class="card-body p-0">
        <?php if (empty($report)): ?>
        <div class="text-center py-4 text-muted"><p class="mb-0">Nenhuma solicitação no período selecionado.</p></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Responsável</th>
                        <th class="text-center">Total</th>
                        <th class="text-center text-success">Em dia</th>
                        <th class="text-center text-warning">Com atraso</th>
                        <th class="text-center text-danger">Não preencheu</th>
                        <th class="text-center">Pendentes</th>
                        <th style="min-width:160px;">Pontualidade</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report as $r): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($r['manager_name']) ?></strong></td>
                        <td class="text-center"><?= $r['total'] ?></td>
                        <td class="text-center"><span class="badge bg-success"><?= $r['on_time'] ?></span></td>
                        <td class="text-center"><?= $r['late'] > 0 ? '<span class="badge bg-warning text-dark">' . $r['late'] . '</span>' : '<span class="text-muted">0</span>' ?></td>
                        <td class="text-center"><?= $r['not_filled'] > 0 ? '<span class="badge bg-danger">' . $r['not_filled'] . '</span>' : '<span class="text-muted">0</span>' ?></td>
                        <td class="text-center"><?= $r['pending'] > 0 ? '<span class="badge bg-secondary">' . $r['pending'] . '</span>' : '<span class="text-muted">0</span>' ?></td>
                        <td>
                            <?php $rate = $r['punctual_rate']; $barCls = $rate >= 80 ? 'bg-success' : ($rate >= 50 ? 'bg-warning' : 'bg-danger'); ?>
                            <div class="progress" style="height:16px;" title="<?= $rate ?>% preencheram em dia">
                                <div class="progress-bar <?= $barCls ?>" style="width:<?= $rate ?>%;"><?= $rate ?>%</div>
                            </div>
                        </td>
                        <td class="text-end">
                            <a href="/admin/weekly-materials/manager/<?= (int) $r['manager_id'] ?>" class="btn btn-sm btn-outline-primary">Detalhes</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Lista detalhada -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-check"></i> Solicitações no período (<?= count($list) ?>)</span>
        <div class="d-flex gap-1">
            <button type="button" class="btn btn-sm btn-outline-secondary monfilter active" data-f="all">Todas</button>
            <button type="button" class="btn btn-sm btn-outline-success monfilter" data-f="on_time">Em dia</button>
            <button type="button" class="btn btn-sm btn-outline-warning monfilter" data-f="late">Com atraso</button>
            <button type="button" class="btn btn-sm btn-outline-danger monfilter" data-f="not_filled">Não preencheu</button>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($list)): ?>
        <div class="text-center py-4 text-muted"><p class="mb-0">Nenhuma solicitação no período.</p></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0" id="monList">
                <thead class="table-light">
                    <tr>
                        <th>Semana</th>
                        <th>Responsável</th>
                        <th>Obra</th>
                        <th>Pontualidade</th>
                        <th>Preenchido em</th>
                        <th>Pedido</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $req): ?>
                    <?php [$pKey, $pLabel, $pCls] = \App\Models\WeeklyMaterialRequest::punctuality($req); ?>
                    <tr data-punc="<?= $pKey ?>">
                        <td><strong><?= date('d/m/Y', strtotime($req['week_start'])) ?></strong></td>
                        <td><?= htmlspecialchars($req['manager_name']) ?></td>
                        <td><small><?= htmlspecialchars($req['construction_site_name'] ?? '—') ?></small></td>
                        <td><span class="badge <?= $pCls ?>"><?= $pLabel ?></span></td>
                        <td><small><?= !empty($req['filled_at']) ? date('d/m/Y H:i', strtotime($req['filled_at'])) : '—' ?></small></td>
                        <td>
                            <?php if (!empty($req['order_code'])): ?>
                            <a href="/admin/orders/show/<?= (int) $req['po_id'] ?>" class="text-decoration-none small">#<?= htmlspecialchars($req['order_code']) ?></a>
                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    const btns = document.querySelectorAll('.monfilter');
    const rows = Array.from(document.querySelectorAll('#monList tbody tr'));
    btns.forEach(b => b.addEventListener('click', function() {
        btns.forEach(x => x.classList.remove('active'));
        this.classList.add('active');
        const f = this.dataset.f;
        rows.forEach(r => { r.style.display = (f === 'all' || r.dataset.punc === f) ? '' : 'none'; });
    }));
})();
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
