<?php $pageTitle = 'Lista de Compras — Semana ' . date('d/m/Y', strtotime($weekStart)); $currentPage = 'weekly_materials'; ?>
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
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <a href="/admin/weekly-materials/week/<?= htmlspecialchars($weekStart) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar</a>
    <span class="text-muted small">
        <i class="bi bi-cart"></i>
        Lista de Compras da semana <strong><?= date('d/m/Y', strtotime($weekStart)) ?></strong> a <strong><?= date('d/m/Y', strtotime($weekStart . ' +6 days')) ?></strong>
    </span>
</div>

<!-- Filtros (PARTE 36) -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="/admin/weekly-materials/purchases" class="row g-2 align-items-end">
            <input type="hidden" name="week" value="<?= htmlspecialchars($weekStart) ?>">
            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Obra</label>
                <select name="construction_site_id" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <?php foreach ($sites as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= ($filters['construction_site_id'] == $s['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Urgência</label>
                <select name="urgency" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <?php foreach ($urgencyMap as $k => $v): ?>
                    <option value="<?= $k ?>" <?= ($filters['urgency'] === $k) ? 'selected' : '' ?>><?= $v[0] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Status do Pedido</label>
                <select name="order_status" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($statusMap as $k => $v): ?>
                    <option value="<?= $k ?>" <?= ($filters['order_status'] === $k) ? 'selected' : '' ?>><?= $v[0] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small mb-0">Ordenar por</label>
                <select name="sort" class="form-select form-select-sm">
                    <option value="urgency_date" <?= ($filters['sort'] ?? '') === 'urgency_date' ? 'selected' : '' ?>>Urgência + data</option>
                    <option value="urgency" <?= ($filters['sort'] ?? '') === 'urgency' ? 'selected' : '' ?>>Urgência</option>
                    <option value="date" <?= ($filters['sort'] ?? '') === 'date' ? 'selected' : '' ?>>Data da solicitação</option>
                </select>
            </div>
            <div class="col-12 col-md-12 mt-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Filtrar</button>
                <a href="/admin/weekly-materials/purchases/<?= htmlspecialchars($weekStart) ?>" class="btn btn-sm btn-outline-secondary">Limpar</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-cart-check"></i> Materiais Solicitados (<?= count($items) ?>)</div>
    <div class="card-body p-0">
        <?php if (empty($items)): ?>
        <div class="text-center py-4 text-muted">
            <p class="mb-0">Nenhum material solicitado nesta semana com os filtros atuais.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Urgência</th>
                        <th>Material</th>
                        <th class="text-center">Qtd</th>
                        <th>Obra</th>
                        <th>Solicitado por</th>
                        <th>Pedido</th>
                        <th>Necessário em</th>
                        <th class="text-center">Antec.</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $it): ?>
                    <?php
                    [$uLabel, $uClass] = $urgencyMap[$it['urgency']] ?? ['—', 'bg-light text-dark'];
                    [$sLabel, $sClass] = $statusMap[$it['order_status']] ?? [$it['order_status'], 'bg-light text-dark'];
                    // Prioriza a data específica do item, quando informada
                    $effNeeded = !empty($it['item_needed_date']) ? $it['item_needed_date'] : $it['needed_date'];
                    $isItemDate = !empty($it['item_needed_date']);
                    $antec = \App\Models\WeeklyMaterialRequest::calcAntecedence($effNeeded, $it['filled_at']);
                    $isCritical = in_array($it['urgency'], ['high', 'critical']);
                    ?>
                    <tr>
                        <td><span class="badge <?= $uClass ?>"><?= $uLabel ?></span></td>
                        <td>
                            <strong><?= htmlspecialchars($it['material_name']) ?></strong>
                            <?php if (!empty($it['specification'])): ?>
                            <small class="text-muted d-block"><?= htmlspecialchars($it['specification']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center fw-bold"><?= number_format($it['quantity'], $it['quantity'] == (int)$it['quantity'] ? 0 : 2) ?> <?= htmlspecialchars($it['unit'] ?? '') ?></td>
                        <td><small><?= htmlspecialchars($it['construction_site_name'] ?? '—') ?></small></td>
                        <td><small><?= htmlspecialchars($it['manager_name']) ?></small></td>
                        <td>
                            <a href="/admin/orders/show/<?= (int) $it['po_id'] ?>" class="text-decoration-none">
                                #<?= htmlspecialchars($it['order_code']) ?>
                            </a>
                        </td>
                        <td>
                            <small><?= !empty($effNeeded) ? date('d/m/Y', strtotime($effNeeded)) : '—' ?></small>
                            <?php if ($isItemDate): ?><span class="badge bg-info text-dark ms-1" title="Data específica deste item">item</span><?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($antec !== null): ?>
                            <span class="badge <?= $antec >= 15 ? 'bg-success' : 'bg-warning text-dark' ?>"><?= $antec ?>d</span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $sClass ?>"><?= $sLabel ?></span>
                            <?php if ($isCritical): ?>
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-1" data-bs-toggle="collapse" data-bs-target="#pr<?= $it['request_id'] ?>" title="Ver motivo">
                                <i class="bi bi-info-circle"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($isCritical): ?>
                    <tr class="collapse" id="pr<?= $it['request_id'] ?>">
                        <td colspan="9" class="bg-danger bg-opacity-10">
                            <small>
                                <strong class="text-danger">Motivo da urgência:</strong>
                                <?php
                                $reasons = [];
                                if (!empty($it['urgency_reason_no_advance'])) $reasons[] = 'Não houve solicitação antecipada';
                                if (!empty($it['urgency_reason_site_occurrence'])) $reasons[] = 'Ocorrência em obra';
                                echo htmlspecialchars(implode(' + ', $reasons ?: ['não informado']));
                                ?>
                                <?php if (!empty($it['urgency_description'])): ?>
                                — <?= htmlspecialchars($it['urgency_description']) ?>
                                <?php endif; ?>
                            </small>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
