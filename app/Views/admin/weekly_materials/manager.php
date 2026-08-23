<?php $pageTitle = 'Detalhes — ' . $manager['name']; $currentPage = 'weekly_materials'; ?>
<?php ob_start(); ?>

<?php
$urgencyMap = [
    'critical' => ['Crítica', 'bg-danger'],
    'high'     => ['Alta', 'bg-warning text-dark'],
    'medium'   => ['Média', 'bg-info text-dark'],
    'low'      => ['Baixa', 'bg-secondary'],
];
$statusMap = [
    'pending_quote' => ['Em cotação', 'bg-secondary'],
    'quoted'        => ['Cotado', 'bg-info text-dark'],
    'approved'      => ['Aprovado', 'bg-primary'],
    'purchased'     => ['Comprado', 'bg-warning text-dark'],
    'delivered'     => ['Entregue', 'bg-success'],
    'cancelled'     => ['Cancelado', 'bg-danger'],
];
$totalSent = (int) ($summary['total_sent'] ?? 0);
$totalResp = (int) ($summary['total_responses'] ?? 0);
$rate = $totalSent > 0 ? round($totalResp / $totalSent * 100) : 0;
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <a href="/admin/weekly-materials" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar</a>
    <h5 class="mb-0"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($manager['name']) ?></h5>
    <span class="text-muted small">
        <?php if (!empty($manager['phone'])): ?><i class="bi bi-telephone"></i> <?= htmlspecialchars($manager['phone']) ?><?php endif; ?>
    </span>
</div>

<!-- Resumo agregado (PARTE 25) -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card stat-card"><div class="card-body py-2 px-3">
            <small class="text-muted">Total de envios</small>
            <div class="stat-number" style="font-size:1.5rem;"><?= $totalSent ?></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card"><div class="card-body py-2 px-3">
            <small class="text-muted">Total de respostas</small>
            <div class="stat-number text-success" style="font-size:1.5rem;"><?= $totalResp ?> <small class="text-muted" style="font-size:.9rem;">(<?= $rate ?>%)</small></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card"><div class="card-body py-2 px-3">
            <small class="text-muted">Atrasos</small>
            <div class="stat-number text-danger" style="font-size:1.5rem;"><?= (int) ($summary['total_overdue'] ?? 0) ?></div>
        </div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card"><div class="card-body py-2 px-3">
            <small class="text-muted">Materiais solicitados</small>
            <div class="stat-number" style="font-size:1.5rem;"><?= (int) ($summary['total_items'] ?? 0) ?></div>
        </div></div>
    </div>
</div>

<!-- Histórico de semanas / pedidos gerados -->
<div class="card">
    <div class="card-header"><i class="bi bi-clock-history"></i> Últimas Semanas</div>
    <div class="card-body p-0">
        <?php if (empty($requests)): ?>
        <div class="text-center py-4 text-muted"><p class="mb-0">Nenhuma solicitação registrada para este responsável.</p></div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Semana</th>
                        <th>Obra</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Urgência</th>
                        <th class="text-center">Itens</th>
                        <th>Necessário em</th>
                        <th>Pedido gerado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                    <?php
                    $statusBadge = $req['status'] === 'filled'
                        ? '<span class="badge bg-success">Preenchido</span>'
                        : ($req['status'] === 'overdue' ? '<span class="badge bg-danger">Atrasado</span>' : '<span class="badge bg-warning text-dark">Pendente</span>');
                    [$uLabel, $uClass] = $urgencyMap[$req['urgency'] ?? ''] ?? ['—', 'bg-light text-dark'];
                    ?>
                    <tr>
                        <td><strong><?= date('d/m/Y', strtotime($req['week_start'])) ?></strong></td>
                        <td><small><?= htmlspecialchars($req['construction_site_name'] ?? '—') ?></small></td>
                        <td class="text-center"><?= $statusBadge ?></td>
                        <td class="text-center">
                            <?php if ($req['status'] === 'filled'): ?><span class="badge <?= $uClass ?>"><?= $uLabel ?></span><?php else: ?>—<?php endif; ?>
                        </td>
                        <td class="text-center"><?= (int) ($req['items_count'] ?? count($req['items'] ?? [])) ?></td>
                        <td><small><?= !empty($req['needed_date']) ? date('d/m/Y', strtotime($req['needed_date'])) : '—' ?></small></td>
                        <td>
                            <?php if (!empty($req['order_code'])): ?>
                            <a href="/admin/orders/show/<?= (int) $req['po_id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-receipt"></i> Pedido #<?= htmlspecialchars($req['order_code']) ?>
                            </a>
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

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
