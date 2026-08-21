<?php $pageTitle = 'Semana ' . date('d/m/Y', strtotime($weekStart)); $currentPage = 'weekly_materials'; ?>
<?php ob_start(); ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <a href="/admin/weekly-materials" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar</a>
    <span class="text-muted small">
        <i class="bi bi-calendar-week"></i> 
        <strong><?= date('d/m/Y', strtotime($weekStart)) ?></strong> a <strong><?= date('d/m/Y', strtotime($weekStart . ' +6 days')) ?></strong>
    </span>
</div>

<?php if (empty($requests)): ?>
<div class="card">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-inbox" style="font-size:2rem;"></i>
        <p class="mt-2 mb-0">Nenhum registro para esta semana.</p>
    </div>
</div>
<?php else: ?>

<!-- Resumo -->
<?php
$filled = count(array_filter($requests, fn($r) => $r['status'] === 'filled'));
$pending = count(array_filter($requests, fn($r) => $r['status'] === 'pending'));
$overdue = count(array_filter($requests, fn($r) => $r['status'] === 'overdue'));
$totalItems = 0;
foreach ($requests as $r) { $totalItems += count($r['items'] ?? []); }
?>
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Preenchidos</small>
                <div class="stat-number text-success" style="font-size:1.5rem;"><?= $filled ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Pendentes</small>
                <div class="stat-number text-warning" style="font-size:1.5rem;"><?= $pending ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Não preencheu</small>
                <div class="stat-number text-danger" style="font-size:1.5rem;"><?= $overdue ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Total de Materiais</small>
                <div class="stat-number" style="font-size:1.5rem;"><?= $totalItems ?></div>
            </div>
        </div>
    </div>
</div>

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
        <div>
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
