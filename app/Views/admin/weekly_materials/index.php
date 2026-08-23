<?php $pageTitle = 'Lista Semanal de Materiais'; $currentPage = 'weekly_materials'; ?>
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
$totalMgr = count($managerControl);
$critical = (int) ($stats['critical_count'] ?? 0);
?>

<!-- Cabeçalho: seletor de semana + ações -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <form method="GET" action="/admin/weekly-materials" class="d-flex align-items-center gap-2">
        <label class="small text-muted mb-0">Semana:</label>
        <select name="week" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
            <?php if (empty($weeks)): ?>
            <option><?= date('d/m/Y', strtotime($selectedWeek)) ?></option>
            <?php else: foreach ($weeks as $w): ?>
            <option value="<?= $w['week_start'] ?>" <?= $w['week_start'] === $selectedWeek ? 'selected' : '' ?>>
                Semana <?= date('d/m/Y', strtotime($w['week_start'])) ?> a <?= date('d/m/Y', strtotime($w['week_start'] . ' +6 days')) ?>
            </option>
            <?php endforeach; endif; ?>
        </select>
    </form>
    <div class="d-flex gap-2 flex-wrap">
        <a href="/admin/weekly-materials/monitoring" class="btn btn-sm btn-outline-info"><i class="bi bi-graph-up"></i> Monitoramento</a>
        <a href="/admin/weekly-materials/cycles" class="btn btn-sm btn-outline-dark"><i class="bi bi-arrow-repeat"></i> Gerenciar Ciclos</a>
        <a href="/admin/orders/pin-users" class="btn btn-sm btn-outline-secondary"><i class="bi bi-people"></i> Gerenciar Responsáveis</a>
        <form method="POST" action="/admin/weekly-materials/generate" class="d-inline">
            <?php $nextCycle = \App\Models\WeeklyMaterialRequest::nextCycleStart(); $cycleDays = \App\Models\WeeklyMaterialRequest::cycleIntervalDays(); ?>
            <input type="hidden" name="week_start" value="<?= $nextCycle ?>">
            <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Gerar ciclo de <?= $cycleDays ?> dias iniciando em <?= date('d/m/Y', strtotime($nextCycle)) ?>?')">
                <i class="bi bi-plus-lg"></i> Gerar Ciclo
            </button>
        </form>
        <form method="POST" action="/admin/weekly-materials/send-now" class="d-inline">
            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Enviar links AGORA para os responsáveis pendentes?')">
                <i class="bi bi-send"></i> Enviar Agora
            </button>
        </form>
        <form method="POST" action="/admin/weekly-materials/send-reminder" class="d-inline">
            <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Enviar COBRANÇA para quem não preencheu?')">
                <i class="bi bi-bell"></i> Cobrar Pendentes
            </button>
        </form>
    </div>
</div>

<!-- Cards de indicadores (PARTE 22) -->
<div class="row g-2 mb-3">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card h-100"><div class="card-body py-2 px-3">
            <small class="text-muted d-block">Links enviados</small>
            <div class="fw-bold text-info" style="font-size:1.8rem; line-height:1;"><?= (int) ($stats['links_sent'] ?? 0) ?></div>
            <small class="text-muted">de <?= (int) ($stats['total'] ?? 0) ?> responsáveis</small>
        </div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card h-100"><div class="card-body py-2 px-3">
            <small class="text-muted d-block">Formulários preenchidos</small>
            <div class="fw-bold text-success" style="font-size:1.8rem; line-height:1;"><?= (int) ($stats['filled'] ?? 0) ?></div>
            <small class="text-muted">com pedido gerado</small>
        </div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card h-100"><div class="card-body py-2 px-3">
            <small class="text-muted d-block">Pendentes</small>
            <div class="fw-bold text-warning" style="font-size:1.8rem; line-height:1;"><?= (int) ($stats['pending'] ?? 0) ?></div>
            <small class="text-muted">sem resposta</small>
        </div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card h-100"><div class="card-body py-2 px-3">
            <small class="text-muted d-block">Atrasados</small>
            <div class="fw-bold text-danger" style="font-size:1.8rem; line-height:1;"><?= (int) ($stats['overdue'] ?? 0) ?></div>
            <small class="text-muted">prazo expirado</small>
        </div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card h-100"><div class="card-body py-2 px-3">
            <small class="text-muted d-block">Itens solicitados</small>
            <div class="fw-bold" style="font-size:1.8rem; line-height:1;"><?= (int) ($stats['items_total'] ?? 0) ?></div>
            <small class="text-muted">na semana</small>
        </div></div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card h-100"><div class="card-body py-2 px-3">
            <small class="text-muted d-block">Compras críticas</small>
            <div class="fw-bold text-danger" style="font-size:1.8rem; line-height:1;"><?= $critical ?></div>
            <small class="text-muted">alta/crítica</small>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <!-- COLUNA PRINCIPAL -->
    <div class="col-12 col-xl-8">
        <!-- Controle por Responsável (PARTE 23) -->
        <div class="card mb-3">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span><i class="bi bi-people"></i> Controle de preenchimento por responsável</span>
                <div class="d-flex gap-2">
                    <form method="POST" action="/admin/weekly-materials/notify-all" class="d-inline">
                        <input type="hidden" name="week_start" value="<?= htmlspecialchars($selectedWeek) ?>">
                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Notificar TODOS os responsáveis deste ciclo? Cada um recebe uma única mensagem com todos os links.')">
                            <i class="bi bi-send"></i> Notificar Todos
                        </button>
                    </form>
                    <a href="/admin/weekly-materials/export-control/<?= htmlspecialchars($selectedWeek) ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-download"></i> Exportar CSV
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtros -->
                <div class="row g-2 mb-3">
                    <div class="col-12 col-md-6">
                        <input type="text" id="mgrSearch" class="form-control form-control-sm" placeholder="Buscar responsável...">
                    </div>
                    <div class="col-12 col-md-6">
                        <select id="mgrStatusFilter" class="form-select form-select-sm">
                            <option value="">Todos os status</option>
                            <option value="filled">Com preenchidas</option>
                            <option value="pending">Com pendentes</option>
                            <option value="overdue">Com atrasadas</option>
                        </select>
                    </div>
                </div>

                <?php if (empty($managerControl)): ?>
                <div class="text-center py-4 text-muted">
                    <p class="mb-0">Nenhum responsável nesta semana. Clique em "Gerar Ciclo" para criar as solicitações.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0" id="mgrTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width:32px;"></th>
                                <th>Responsável</th>
                                <th class="text-center">Obras</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Últimos 4 ciclos</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($managerControl as $mc): ?>
                            <?php
                            // Chave de status agregada para o filtro
                            $statusFlags = [];
                            if ($mc['filled'] > 0) $statusFlags[] = 'filled';
                            if ($mc['pending'] > 0) $statusFlags[] = 'pending';
                            if ($mc['overdue'] > 0) $statusFlags[] = 'overdue';
                            $resumo = [];
                            if ($mc['filled'] > 0)   $resumo[] = '<span class="badge bg-success">' . $mc['filled'] . ' preench.</span>';
                            if ($mc['pending'] > 0)  $resumo[] = '<span class="badge bg-warning text-dark">' . $mc['pending'] . ' pend.</span>';
                            if ($mc['overdue'] > 0)  $resumo[] = '<span class="badge bg-danger">' . $mc['overdue'] . ' atras.</span>';
                            if ($mc['not_sent'] > 0) $resumo[] = '<span class="badge bg-secondary">' . $mc['not_sent'] . ' não env.</span>';
                            $baseUrlCtrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
                            ?>
                            <tr class="mgr-row" data-status="<?= implode(' ', $statusFlags) ?>" data-name="<?= htmlspecialchars(strtolower($mc['manager_name'])) ?>"
                                role="button" data-bs-toggle="collapse" data-bs-target="#imgr<?= (int) $mc['manager_id'] ?>" style="cursor:pointer;">
                                <td class="text-center"><i class="bi bi-chevron-down text-muted"></i></td>
                                <td><strong><?= htmlspecialchars($mc['manager_name']) ?></strong></td>
                                <td class="text-center"><span class="badge bg-light text-dark border"><?= (int) $mc['total'] ?></span></td>
                                <td class="text-center"><?= implode(' ', $resumo) ?: '—' ?></td>
                                <td class="text-center" style="white-space:nowrap;">
                                    <?php foreach (array_reverse($mc['recent_cycles']) as $c): ?>
                                    <?php $dot = $c['status'] === 'filled' ? 'text-success' : ($c['status'] === 'overdue' ? 'text-danger' : 'text-warning'); ?>
                                    <i class="bi bi-circle-fill <?= $dot ?>" style="font-size:.6rem;" title="<?= date('d/m', strtotime($c['week_start'])) ?>: <?= $c['status'] ?>"></i>
                                    <?php endforeach; ?>
                                </td>
                                <td class="text-end" onclick="event.stopPropagation();">
                                    <form method="POST" action="/admin/weekly-materials/notify-manager" class="d-inline">
                                        <input type="hidden" name="week_start" value="<?= htmlspecialchars($selectedWeek) ?>">
                                        <input type="hidden" name="manager_id" value="<?= (int) $mc['manager_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Notificar este responsável"
                                            onclick="return confirm('Notificar <?= htmlspecialchars($mc['manager_name']) ?> com uma única mensagem contendo todos os links?')">
                                            <i class="bi bi-send"></i>
                                        </button>
                                    </form>
                                    <a href="/admin/weekly-materials/manager/<?= (int) $mc['manager_id'] ?>" class="btn btn-sm btn-outline-primary" title="Ver detalhes">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr class="collapse" id="imgr<?= (int) $mc['manager_id'] ?>">
                                <td colspan="6" class="p-0">
                                    <div class="p-2 bg-light">
                                        <table class="table table-sm mb-0 align-middle bg-white">
                                            <thead>
                                                <tr class="small text-muted">
                                                    <th>Obra</th>
                                                    <th class="text-center">Link enviado</th>
                                                    <th class="text-center">Status</th>
                                                    <th>Último pedido</th>
                                                    <th>Próxima necessidade</th>
                                                    <th class="text-end">Link</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($mc['sites'] as $s):
                                                    $sBadge = $s['status'] === 'filled'
                                                        ? '<span class="badge bg-success">Preenchido</span>'
                                                        : ($s['status'] === 'overdue'
                                                            ? '<span class="badge bg-danger">Atrasado</span>'
                                                            : (empty($s['notified_at']) ? '<span class="badge bg-secondary">Não enviado</span>' : '<span class="badge bg-warning text-dark">Pendente</span>'));
                                                    $formUrl = $baseUrlCtrl . '/lista-semanal/' . $s['token'];
                                                ?>
                                                <tr>
                                                    <td><small><strong><?= htmlspecialchars($s['construction_site_name'] ?? 'Sem obra') ?></strong></small></td>
                                                    <td class="text-center"><small><?= !empty($s['notified_at']) ? date('d/m H:i', strtotime($s['notified_at'])) : '—' ?></small></td>
                                                    <td class="text-center"><?= $sBadge ?></td>
                                                    <td>
                                                        <?php if (!empty($s['order_code'])): ?>
                                                        <a href="/admin/orders/show/<?= (int) $s['po_id'] ?>" class="text-decoration-none small">#<?= htmlspecialchars($s['order_code']) ?></a>
                                                        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                                                    </td>
                                                    <td><small><?= !empty($s['needed_date']) ? date('d/m/Y', strtotime($s['needed_date'])) : '—' ?></small></td>
                                                    <td class="text-end">
                                                        <div class="input-group input-group-sm justify-content-end" style="max-width:220px; margin-left:auto;">
                                                            <button type="button" class="btn btn-outline-secondary" title="Copiar link"
                                                                onclick="navigator.clipboard.writeText('<?= htmlspecialchars($formUrl) ?>'); this.innerHTML='<i class=\'bi bi-check\'></i>'; setTimeout(()=>this.innerHTML='<i class=\'bi bi-clipboard\'></i>',1500)">
                                                                <i class="bi bi-clipboard"></i>
                                                            </button>
                                                            <a href="<?= htmlspecialchars($formUrl) ?>" target="_blank" class="btn btn-outline-primary" title="Abrir formulário"><i class="bi bi-box-arrow-up-right"></i></a>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <div class="mt-2 d-flex gap-3 small">
                    <a href="/admin/weekly-materials/purchases/<?= htmlspecialchars($selectedWeek) ?>" class="text-decoration-none"><i class="bi bi-cart"></i> Lista de compras completa</a>
                    <a href="#historicoSemanas" class="text-decoration-none" data-bs-toggle="collapse"><i class="bi bi-clock-history"></i> Histórico por semana</a>
                    <a href="#logNotif" class="text-decoration-none" data-bs-toggle="collapse"><i class="bi bi-journal-text"></i> Log de notificações</a>
                </div>
            </div>
        </div>

        <!-- Histórico por semana (PARTE 30) -->
        <div class="collapse" id="historicoSemanas">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-clock-history"></i> Histórico por Semana</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Semana</th>
                                    <th class="text-center">Resp.</th>
                                    <th class="text-center">Preench.</th>
                                    <th class="text-center">Pend.</th>
                                    <th class="text-center">Atras.</th>
                                    <th class="text-center">Taxa</th>
                                    <th class="text-center">Pedidos</th>
                                    <th class="text-center">Itens</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($weeks as $w): ?>
                                <?php $rate = $w['total_managers'] > 0 ? round($w['filled_count'] / $w['total_managers'] * 100) : 0; ?>
                                <tr>
                                    <td><strong><?= date('d/m/Y', strtotime($w['week_start'])) ?></strong></td>
                                    <td class="text-center"><?= $w['total_managers'] ?></td>
                                    <td class="text-center"><span class="badge bg-success"><?= $w['filled_count'] ?></span></td>
                                    <td class="text-center"><span class="badge bg-warning text-dark"><?= $w['pending_count'] ?></span></td>
                                    <td class="text-center"><span class="badge bg-danger"><?= $w['overdue_count'] ?></span></td>
                                    <td class="text-center"><small class="fw-bold"><?= $rate ?>%</small></td>
                                    <td class="text-center"><span class="badge bg-primary"><?= $w['orders_count'] ?></span></td>
                                    <td class="text-center"><?= $w['items_total'] ?></td>
                                    <td class="text-end">
                                        <a href="/admin/weekly-materials/week/<?= $w['week_start'] ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Log de notificações (PARTE 31) -->
        <div class="collapse" id="logNotif">
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-journal-text"></i> Log de Notificações — Semana <?= date('d/m/Y', strtotime($selectedWeek)) ?></div>
                <div class="card-body p-0" style="max-height:300px; overflow-y:auto;">
                    <?php if (empty($logs)): ?>
                    <div class="text-center py-3 text-muted small">Nenhum registro para esta semana.</div>
                    <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($logs as $log): ?>
                        <li class="list-group-item py-2 small d-flex justify-content-between">
                            <span><i class="bi bi-dot"></i> <?= htmlspecialchars($log['description'] ?? $log['action']) ?></span>
                            <span class="text-muted"><?= date('d/m H:i', strtotime($log['created_at'])) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Lista consolidada de compras (PARTE 26) -->
        <div class="card mb-3">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span><i class="bi bi-cart-check"></i> Lista de compras consolidada</span>
                <div class="d-flex gap-2">
                    <span class="badge bg-secondary">Itens: <?= count($purchaseItems) ?></span>
                    <span class="badge bg-danger">Críticos: <?= count(array_filter($purchaseItems, fn($i) => in_array($i['urgency'], ['high','critical']))) ?></span>
                </div>
            </div>
            <div class="card-body">
                <!-- Filtros da lista de compras (PARTE 36) -->
                <form method="GET" action="/admin/weekly-materials" class="row g-2 mb-3">
                    <input type="hidden" name="week" value="<?= htmlspecialchars($selectedWeek) ?>">
                    <div class="col-6 col-md-3">
                        <select name="sort" class="form-select form-select-sm">
                            <option value="urgency_date" <?= ($organization['sort'] === 'urgency_date') ? 'selected' : '' ?>>Urgência + data</option>
                            <option value="urgency" <?= ($organization['sort'] === 'urgency') ? 'selected' : '' ?>>Urgência</option>
                            <option value="date" <?= ($organization['sort'] === 'date') ? 'selected' : '' ?>>Data da solicitação</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <?php $curUrg = $_GET['purchase_urgency'] ?? ''; ?>
                        <select name="purchase_urgency" class="form-select form-select-sm">
                            <option value="">Todas as urgências</option>
                            <?php foreach ($urgencyMap as $k => $v): ?>
                            <option value="<?= $k ?>" <?= $curUrg === $k ? 'selected' : '' ?>><?= $v[0] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <select name="purchase_site" class="form-select form-select-sm">
                            <option value="">Todas as obras</option>
                            <?php foreach ($sites as $s): ?>
                            <option value="<?= (int) $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-funnel"></i> Filtrar</button>
                    </div>
                </form>

                <?php if (empty($purchaseItems)): ?>
                <div class="text-center py-4 text-muted">
                    <p class="mb-0">Nenhum material solicitado nesta semana ainda.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
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
                            <?php foreach ($purchaseItems as $it): ?>
                            <?php
                            [$uLabel, $uClass] = $urgencyMap[$it['urgency']] ?? ['—', 'bg-light text-dark'];
                            [$sLabel, $sClass] = $statusMap[$it['order_status']] ?? [$it['order_status'], 'bg-light text-dark'];
                            $antec = \App\Models\WeeklyMaterialRequest::calcAntecedence($it['needed_date'], $it['filled_at']);
                            $isCritical = in_array($it['urgency'], ['high', 'critical']);
                            ?>
                            <tr>
                                <td><span class="badge <?= $uClass ?>"><?= $uLabel ?></span></td>
                                <td>
                                    <strong><?= htmlspecialchars($it['material_name']) ?></strong>
                                    <?php if (!empty($it['specification'])): ?><small class="text-muted d-block"><?= htmlspecialchars($it['specification']) ?></small><?php endif; ?>
                                </td>
                                <td class="text-center fw-bold"><?= number_format($it['quantity'], $it['quantity'] == (int)$it['quantity'] ? 0 : 2) ?> <?= htmlspecialchars($it['unit'] ?? '') ?></td>
                                <td><small><?= htmlspecialchars($it['construction_site_name'] ?? '—') ?></small></td>
                                <td><small><?= htmlspecialchars($it['manager_name']) ?></small></td>
                                <td><a href="/admin/orders/show/<?= (int) $it['po_id'] ?>" class="text-decoration-none small">#<?= htmlspecialchars($it['order_code']) ?></a></td>
                                <td><small><?= !empty($it['needed_date']) ? date('d/m/Y', strtotime($it['needed_date'])) : '—' ?></small></td>
                                <td class="text-center">
                                    <?php if ($antec !== null): ?>
                                    <span class="badge <?= $antec >= 15 ? 'bg-success' : 'bg-warning text-dark' ?>"><?= $antec ?>d</span>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $sClass ?>"><?= $sLabel ?></span>
                                    <?php if ($isCritical): ?>
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-1" data-bs-toggle="collapse" data-bs-target="#pri<?= $it['request_id'] ?>_<?= md5($it['material_name']) ?>" title="Ver motivo"><i class="bi bi-info-circle"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if ($isCritical): ?>
                            <tr class="collapse" id="pri<?= $it['request_id'] ?>_<?= md5($it['material_name']) ?>">
                                <td colspan="9" class="bg-danger bg-opacity-10">
                                    <small>
                                        <strong class="text-danger">Motivo:</strong>
                                        <?php
                                        $reasons = [];
                                        if (!empty($it['urgency_reason_no_advance'])) $reasons[] = 'Não houve solicitação antecipada';
                                        if (!empty($it['urgency_reason_site_occurrence'])) $reasons[] = 'Ocorrência em obra';
                                        echo htmlspecialchars(implode(' + ', $reasons ?: ['não informado']));
                                        ?>
                                        <?php if (!empty($it['urgency_description'])): ?> — <?= htmlspecialchars($it['urgency_description']) ?><?php endif; ?>
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
    </div>
    <!-- FIM COLUNA PRINCIPAL -->

    <!-- COLUNA LATERAL (Automação + Organização) -->
    <div class="col-12 col-xl-4">
        <!-- Automação Semanal (PARTE 17) -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-robot"></i> Automação Semanal</span>
                <span class="badge <?= $automation['auto_reminder'] === '1' ? 'bg-success' : 'bg-secondary' ?>">
                    <?= $automation['auto_reminder'] === '1' ? 'Ativa' : 'Manual' ?>
                </span>
            </div>
            <form method="POST" action="/admin/weekly-materials/save-automation">
                <div class="card-body">
                    <p class="text-muted small mb-3">Configure a frequência e quando os responsáveis recebem o formulário.</p>

                    <div class="mb-3">
                        <label class="form-label small mb-1">1. Frequência do ciclo (X)</label>
                        <select name="cycle_interval_days" class="form-select form-select-sm">
                            <?php
                            $intervals = [1=>'Todos os dias (teste)', 7=>'A cada 7 dias', 10=>'A cada 10 dias', 14=>'A cada 14 dias', 15=>'A cada 15 dias', 21=>'A cada 21 dias', 30=>'A cada 30 dias'];
                            $curInterval = (int) $automation['cycle_interval_days'];
                            foreach ($intervals as $d => $lbl): ?>
                            <option value="<?= $d ?>" <?= $curInterval === $d ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted d-block mt-1">A cada quantos dias um novo ciclo de solicitação é aberto.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small mb-1">2. Antecedência do envio (Y)</label>
                        <select name="notify_advance_days" class="form-select form-select-sm">
                            <?php
                            $curNotify = (int) $automation['notify_advance_days'];
                            foreach ([0,2,3,5,7,10] as $d): ?>
                            <option value="<?= $d ?>" <?= $curNotify === $d ? 'selected' : '' ?>><?= $d === 0 ? 'No início do ciclo' : $d . ' dias antes do ciclo' ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted d-block mt-1">Quantos dias antes do próximo ciclo o link é gerado e enviado. Ex.: ciclo de 15 dias com envio 5 dias antes → o link sai no dia 10.</small>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label small mb-1">Dia do envio</label>
                            <select name="send_day" class="form-select form-select-sm">
                                <?php
                                $days = ['1'=>'Segunda-feira','2'=>'Terça-feira','3'=>'Quarta-feira','4'=>'Quinta-feira','5'=>'Sexta-feira','6'=>'Sábado','7'=>'Domingo'];
                                foreach ($days as $k => $lbl): ?>
                                <option value="<?= $k ?>" <?= $automation['send_day'] === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="form-label small mb-1">Horário</label>
                            <input type="time" name="send_time" class="form-control form-control-sm" value="<?= htmlspecialchars($automation['send_time']) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small mb-1">Prazo para resposta</label>
                        <select name="response_deadline" class="form-select form-select-sm">
                            <option value="same_day_18" <?= $automation['response_deadline'] === 'same_day_18' ? 'selected' : '' ?>>Mesmo dia até 18:00</option>
                            <option value="next_day" <?= $automation['response_deadline'] === 'next_day' ? 'selected' : '' ?>>Até o dia seguinte</option>
                            <option value="two_days" <?= $automation['response_deadline'] === 'two_days' ? 'selected' : '' ?>>Até 2 dias depois</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small mb-1">3. Antecedência mínima da necessidade (Z)</label>
                        <select name="min_need_days" class="form-select form-select-sm">
                            <?php $curNeed = (int) $automation['min_need_days']; foreach ([3,5,7,10,15] as $d): ?>
                            <option value="<?= $d ?>" <?= $curNeed === $d ? 'selected' : '' ?>><?= $d ?> dias</option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted d-block mt-1">A data "preciso até" no formulário vem no mínimo com essa antecedência a partir do dia do preenchimento. Ex.: 5 dias.</small>
                    </div>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="auto_reminder" id="autoReminder" <?= $automation['auto_reminder'] === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="autoReminder">
                            Lembrete automático
                            <span class="text-muted d-block" style="font-size:.75rem;">Cobra automaticamente quem não respondeu</span>
                        </label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="auto_overdue" id="autoOverdue" <?= $automation['auto_overdue'] === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="autoOverdue">
                            Marcar atraso automático
                            <span class="text-muted d-block" style="font-size:.75rem;">Marca como atrasado após o prazo</span>
                        </label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="notify_supervisor" id="notifySup" <?= $automation['notify_supervisor'] === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="notifySup">
                            Notificar supervisor
                            <span class="text-muted d-block" style="font-size:.75rem;">Avisa o supervisor sobre pendentes</span>
                        </label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small mb-1">Canal de envio</label>
                        <select name="channel" class="form-select form-select-sm">
                            <option value="whatsapp" <?= $automation['channel'] === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option>
                            <option value="email" <?= $automation['channel'] === 'email' ? 'selected' : '' ?>>E-mail</option>
                            <option value="both" <?= $automation['channel'] === 'both' ? 'selected' : '' ?>>WhatsApp + E-mail</option>
                        </select>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-dark w-100"><i class="bi bi-check-lg"></i> Salvar automação</button>
                </div>
            </form>
        </div>

        <!-- Organização da lista de compras (PARTE 27) -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-sort-down"></i> Organizar lista de compras</div>
            <form method="POST" action="/admin/weekly-materials/save-organization">
                <div class="card-body">
                    <p class="text-muted small mb-3">Define a regra principal de priorização.</p>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="sort" id="sortDate" value="date" <?= $organization['sort'] === 'date' ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="sortDate">
                            Data da solicitação
                            <span class="text-muted d-block" style="font-size:.75rem;">Mais antigos aparecem primeiro</span>
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="sort" id="sortUrg" value="urgency" <?= $organization['sort'] === 'urgency' ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="sortUrg">
                            Nível de urgência
                            <span class="text-muted d-block" style="font-size:.75rem;">Crítico → Alto → Médio → Baixo</span>
                        </label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="sort" id="sortBoth" value="urgency_date" <?= $organization['sort'] === 'urgency_date' ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="sortBoth">
                            Urgência + data
                            <span class="text-muted d-block" style="font-size:.75rem;">Prioriza urgência, depois necessidade e solicitação</span>
                        </label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small mb-1">Agrupar itens por</label>
                        <select name="group_by" class="form-select form-select-sm">
                            <option value="site_category" <?= $organization['group_by'] === 'site_category' ? 'selected' : '' ?>>Obra → Categoria</option>
                            <option value="site" <?= $organization['group_by'] === 'site' ? 'selected' : '' ?>>Obra</option>
                            <option value="urgency" <?= $organization['group_by'] === 'urgency' ? 'selected' : '' ?>>Urgência</option>
                            <option value="none" <?= $organization['group_by'] === 'none' ? 'selected' : '' ?>>Sem agrupamento</option>
                        </select>
                    </div>

                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="flag_outside_15" id="flag15" <?= $organization['flag_outside_15'] === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="flag15">
                            Sinalizar pedidos fora dos 15 dias
                            <span class="text-muted d-block" style="font-size:.75rem;">Destaca solicitações fora da antecedência recomendada</span>
                        </label>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-dark w-100"><i class="bi bi-check-lg"></i> Aplicar organização</button>
                </div>
            </form>
        </div>
    </div>
    <!-- FIM COLUNA LATERAL -->
</div>

<script>
// Filtros locais da tabela de controle por responsável (linhas agrupadas)
(function() {
    const search = document.getElementById('mgrSearch');
    const statusF = document.getElementById('mgrStatusFilter');
    const table = document.getElementById('mgrTable');
    if (!table) return;
    // Filtra apenas as linhas-resumo (.mgr-row); as linhas de detalhe seguem o collapse
    const rows = Array.from(table.querySelectorAll('tbody tr.mgr-row'));

    function apply() {
        const q = (search.value || '').toLowerCase();
        const st = statusF.value;
        rows.forEach(r => {
            const okName = !q || (r.dataset.name || '').includes(q);
            const okStatus = !st || (r.dataset.status || '').split(' ').includes(st);
            const show = okName && okStatus;
            r.style.display = show ? '' : 'none';
            // Esconde/mostra a linha de detalhe associada (próxima irmã)
            const detail = r.nextElementSibling;
            if (detail && !show) {
                detail.style.display = 'none';
                detail.classList.remove('show');
            } else if (detail) {
                detail.style.display = '';
            }
        });
    }
    [search, statusF].forEach(el => {
        el.addEventListener('input', apply);
        el.addEventListener('change', apply);
    });
})();
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
