<?php $pageTitle = 'Semana ' . date('d/m/Y', strtotime($weekStart)); $currentPage = 'weekly_materials'; ?>
<?php ob_start(); ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <a href="/admin/weekly-materials" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar</a>
    <span class="text-muted small">
        <i class="bi bi-calendar-week"></i> 
        <strong><?= date('d/m/Y', strtotime($weekStart)) ?></strong> a <strong><?= date('d/m/Y', strtotime($weekStart . ' +6 days')) ?></strong>
    </span>
    <a href="/admin/weekly-materials/purchases/<?= htmlspecialchars($weekStart) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-cart"></i> Lista de Compras</a>
</div>

<?php if (empty($requests)): ?>
<div class="card">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-inbox" style="font-size:2rem;"></i>
        <p class="mt-2 mb-0">Nenhum registro para esta semana.</p>
    </div>
</div>
<?php else: ?>

<!-- Resumo / Dashboard gerencial (PARTE 22) -->
<?php $stats = $stats ?? []; ?>
<div class="row g-3 mb-3">
    <div class="col-6 col-md-2">
        <div class="card stat-card">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Links enviados</small>
                <div class="stat-number text-info" style="font-size:1.5rem;"><?= (int) ($stats['links_sent'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card stat-card">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Preenchidos</small>
                <div class="stat-number text-success" style="font-size:1.5rem;"><?= (int) ($stats['filled'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card stat-card">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Pendentes</small>
                <div class="stat-number text-warning" style="font-size:1.5rem;"><?= (int) ($stats['pending'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card stat-card">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Atrasados</small>
                <div class="stat-number text-danger" style="font-size:1.5rem;"><?= (int) ($stats['overdue'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card stat-card">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Itens solicitados</small>
                <div class="stat-number" style="font-size:1.5rem;"><?= (int) ($stats['items_total'] ?? 0) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card stat-card">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Compras críticas</small>
                <div class="stat-number text-danger" style="font-size:1.5rem;"><?= (int) ($stats['critical_count'] ?? 0) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Controle por Responsável (PARTE 23) -->
<?php if (!empty($managerControl)): ?>
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-people"></i> Controle por Responsável</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Responsável</th>
                        <th>Obra</th>
                        <th class="text-center">Link enviado</th>
                        <th class="text-center">Status</th>
                        <th>Último pedido</th>
                        <th>Próxima necessidade</th>
                        <th class="text-center">Últimos 4 ciclos</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($managerControl as $mc): ?>
                    <?php
                    $statusBadge = $mc['status'] === 'filled'
                        ? '<span class="badge bg-success">Preenchido</span>'
                        : ($mc['status'] === 'overdue'
                            ? '<span class="badge bg-danger">Atrasado</span>'
                            : (empty($mc['notified_at']) ? '<span class="badge bg-secondary">Não enviado</span>' : '<span class="badge bg-warning text-dark">Pendente</span>'));
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($mc['manager_name']) ?></strong></td>
                        <td><small><?= htmlspecialchars($mc['construction_site_name'] ?? '—') ?></small></td>
                        <td class="text-center"><small><?= !empty($mc['notified_at']) ? date('d/m H:i', strtotime($mc['notified_at'])) : '—' ?></small></td>
                        <td class="text-center"><?= $statusBadge ?></td>
                        <td>
                            <?php if (!empty($mc['order_code'])): ?>
                            <a href="/admin/orders/show/<?= (int) $mc['po_id'] ?>" class="text-decoration-none">#<?= htmlspecialchars($mc['order_code']) ?></a>
                            <small class="text-muted d-block"><?= !empty($mc['order_created_at']) ? date('d/m/Y', strtotime($mc['order_created_at'])) : '' ?></small>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td><small><?= !empty($mc['needed_date']) ? date('d/m/Y', strtotime($mc['needed_date'])) : '—' ?></small></td>
                        <td class="text-center">
                            <?php foreach (array_reverse($mc['recent_cycles']) as $c): ?>
                            <?php
                            $dot = $c['status'] === 'filled' ? 'text-success' : ($c['status'] === 'overdue' ? 'text-danger' : 'text-warning');
                            ?>
                            <i class="bi bi-circle-fill <?= $dot ?>" style="font-size:.6rem;" title="<?= date('d/m', strtotime($c['week_start'])) ?>: <?= $c['status'] ?>"></i>
                            <?php endforeach; ?>
                        </td>
                        <td class="text-end">
                            <a href="/admin/weekly-materials/manager/<?= (int) $mc['manager_id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> Ver detalhes
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php foreach ($requests as $req): ?>
<?php
$borderClass = $req['status'] === 'filled' ? 'border-success border-opacity-50' : ($req['status'] === 'overdue' ? 'border-danger border-opacity-50' : 'border-warning border-opacity-50');
$headerBg = $req['status'] === 'filled' ? 'bg-success bg-opacity-10' : ($req['status'] === 'overdue' ? 'bg-danger bg-opacity-10' : 'bg-warning bg-opacity-10');
?>
<div class="card mb-3 <?= $borderClass ?>">
    <div class="card-header <?= $headerBg ?> d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2">
            <?php if ($req['status'] === 'filled'): ?>
            <i class="bi bi-check-circle-fill text-success"></i>
            <?php elseif ($req['status'] === 'overdue'): ?>
            <i class="bi bi-x-circle-fill text-danger"></i>
            <?php else: ?>
            <i class="bi bi-clock-fill text-warning"></i>
            <?php endif; ?>
            <div>
                <strong><?= htmlspecialchars($req['manager_name']) ?></strong>
                <?php if (!empty($req['manager_phone'])): ?>
                <small class="text-muted d-block"><?= htmlspecialchars($req['manager_phone']) ?></small>
                <?php endif; ?>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?php
            // Urgência (PARTE 9)
            $urgencyMap = [
                'critical' => ['Crítica', 'bg-danger'],
                'high'     => ['Alta', 'bg-warning text-dark'],
                'medium'   => ['Média', 'bg-info text-dark'],
                'low'      => ['Baixa', 'bg-secondary'],
            ];
            if ($req['status'] === 'filled' && !empty($req['urgency']) && isset($urgencyMap[$req['urgency']])):
                [$uLabel, $uClass] = $urgencyMap[$req['urgency']];
            ?>
            <span class="badge <?= $uClass ?>"><?= $uLabel ?></span>
            <?php endif; ?>

            <?php if ($req['status'] === 'filled' && !empty($req['order_code'])): ?>
            <a href="/admin/orders/show/<?= (int) $req['order_id'] ?>" class="badge bg-primary text-decoration-none" title="Abrir Pedido">
                <i class="bi bi-receipt"></i> Pedido #<?= htmlspecialchars($req['order_code']) ?>
            </a>
            <?php endif; ?>

            <?php if ($req['status'] === 'filled'): ?>
            <span class="badge bg-success">Preenchido em <?= date('d/m H:i', strtotime($req['filled_at'])) ?></span>
            <?php elseif ($req['status'] === 'overdue'): ?>
            <span class="badge bg-danger">Não preencheu</span>
            <?php else: ?>
            <span class="badge bg-warning text-dark">Pendente</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($req['status'] === 'filled' && !empty($req['items'])): ?>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Material</th>
                        <th class="text-center" style="width:80px;">Qtd</th>
                        <th style="width:80px;">Unidade</th>
                        <th>Obs</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($req['items'] as $i => $item): ?>
                    <tr>
                        <td class="text-muted"><?= $i + 1 ?></td>
                        <td><strong><?= htmlspecialchars($item['material_name']) ?></strong></td>
                        <td class="text-center fw-bold"><?= number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2) ?></td>
                        <td><?= htmlspecialchars($item['unit'] ?? '-') ?></td>
                        <td><small class="text-muted"><?= htmlspecialchars($item['notes'] ?? '') ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="border-top p-3 d-flex flex-wrap gap-3 align-items-center small">
            <?php if (!empty($req['needed_date'])): ?>
            <span><i class="bi bi-calendar-check text-muted"></i> Necessário em <strong><?= date('d/m/Y', strtotime($req['needed_date'])) ?></strong></span>
            <?php
            $antec = \App\Models\WeeklyMaterialRequest::calcAntecedence($req['needed_date'], $req['filled_at'] ?? null);
            if ($antec !== null):
                if ($antec >= 15): ?>
                <span class="badge bg-success"><i class="bi bi-check-circle"></i> <?= $antec ?> dias — dentro do prazo</span>
                <?php else: ?>
                <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> <?= $antec ?> dia(s) — fora dos 15 dias</span>
                <?php endif;
            endif; ?>
            <?php endif; ?>

            <?php if (in_array($req['urgency'] ?? '', ['high', 'critical'])): ?>
            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#reason<?= $req['id'] ?>">
                <i class="bi bi-info-circle"></i> Ver motivo
            </button>
            <?php endif; ?>
        </div>

        <?php if (in_array($req['urgency'] ?? '', ['high', 'critical'])): ?>
        <div class="collapse" id="reason<?= $req['id'] ?>">
            <div class="border-top p-3 bg-danger bg-opacity-10">
                <strong class="text-danger small"><i class="bi bi-exclamation-triangle-fill"></i> Motivo da urgência</strong>
                <ul class="mb-2 mt-1 small">
                    <?php if (!empty($req['urgency_reason_no_advance'])): ?><li>Não houve solicitação antecipada</li><?php endif; ?>
                    <?php if (!empty($req['urgency_reason_site_occurrence'])): ?><li>Ocorrência em obra</li><?php endif; ?>
                </ul>
                <?php if (!empty($req['urgency_description'])): ?>
                <p class="mb-0 small"><strong>Justificativa:</strong> <?= nl2br(htmlspecialchars($req['urgency_description'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($req['notes']) || !empty($req['audio_filename'])): ?>
        <div class="border-top p-3">
            <?php if (!empty($req['notes'])): ?>
            <div class="mb-2">
                <small class="text-muted fw-bold"><i class="bi bi-chat-left-text"></i> Observações:</small>
                <p class="mb-0 small"><?= nl2br(htmlspecialchars($req['notes'])) ?></p>
            </div>
            <?php endif; ?>
            <?php if (!empty($req['audio_filename'])): ?>
            <div>
                <small class="text-muted fw-bold"><i class="bi bi-mic-fill text-danger"></i> Áudio gravado:</small>
                <div class="mt-1">
                    <audio controls style="height:36px; width:100%; max-width:400px;">
                        <source src="/uploads/weekly-materials/<?= htmlspecialchars($req['audio_filename']) ?>" type="audio/webm">
                    </audio>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php elseif ($req['status'] !== 'filled'): ?>
    <div class="card-body">
        <p class="small text-muted mb-2">Link do formulário para enviar ao gerente:</p>
        <?php
        $baseUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
        $formUrl = $baseUrl . '/lista-semanal/' . $req['token'];
        ?>
        <div class="input-group input-group-sm" style="max-width:500px;">
            <input type="text" class="form-control" value="<?= htmlspecialchars($formUrl) ?>" readonly>
            <button class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(this.previousElementSibling.value); this.innerHTML='<i class=\'bi bi-check\'></i>'; setTimeout(() => this.innerHTML='<i class=\'bi bi-clipboard\'></i>', 2000)"><i class="bi bi-clipboard"></i></button>
            <a href="<?= htmlspecialchars($formUrl) ?>" target="_blank" class="btn btn-outline-primary" title="Abrir formulário"><i class="bi bi-box-arrow-up-right"></i></a>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
