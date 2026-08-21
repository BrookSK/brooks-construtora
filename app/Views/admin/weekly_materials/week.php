<?php $pageTitle = 'Semana ' . date('d/m/Y', strtotime($weekStart)); $currentPage = 'weekly_materials'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="/admin/weekly-materials" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar</a>
    <span class="text-muted">Semana: <strong><?= date('d/m/Y', strtotime($weekStart)) ?></strong> a <strong><?= date('d/m/Y', strtotime($weekStart . ' +6 days')) ?></strong></span>
</div>

<?php if (empty($requests)): ?>
<div class="card">
    <div class="card-body text-center py-5 text-muted">
        <p class="mb-0">Nenhum registro para esta semana.</p>
    </div>
</div>
<?php else: ?>

<?php foreach ($requests as $req): ?>
<div class="card mb-3 <?= $req['status'] === 'filled' ? 'border-success' : ($req['status'] === 'overdue' ? 'border-danger' : 'border-warning') ?>">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <strong><?= htmlspecialchars($req['manager_name']) ?></strong>
            <?php if (!empty($req['construction_site_name'])): ?>
            <small class="text-muted ms-2"><i class="bi bi-buildings"></i> <?= htmlspecialchars($req['construction_site_name']) ?></small>
            <?php endif; ?>
        </div>
        <div>
            <?php if ($req['status'] === 'filled'): ?>
            <span class="badge bg-success"><i class="bi bi-check-circle"></i> Preenchido em <?= date('d/m H:i', strtotime($req['filled_at'])) ?></span>
            <?php elseif ($req['status'] === 'overdue'): ?>
            <span class="badge bg-danger"><i class="bi bi-x-circle"></i> Não preencheu</span>
            <?php else: ?>
            <span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> Pendente</span>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($req['status'] === 'filled' && !empty($req['items'])): ?>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr><th>Material</th><th class="text-center">Qtd</th><th>Unidade</th><th>Obs</th></tr>
            </thead>
            <tbody>
                <?php foreach ($req['items'] as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['material_name']) ?></td>
                    <td class="text-center"><?= number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2) ?></td>
                    <td><?= htmlspecialchars($item['unit'] ?? '-') ?></td>
                    <td><small class="text-muted"><?= htmlspecialchars($item['notes'] ?? '-') ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!empty($req['notes'])): ?>
        <div class="p-2 border-top bg-light small"><strong>Obs:</strong> <?= nl2br(htmlspecialchars($req['notes'])) ?></div>
        <?php endif; ?>
        <?php if (!empty($req['audio_filename'])): ?>
        <div class="p-2 border-top">
            <audio controls class="w-100" style="height:32px;">
                <source src="/uploads/weekly-materials/<?= htmlspecialchars($req['audio_filename']) ?>">
            </audio>
        </div>
        <?php endif; ?>
    </div>
    <?php elseif ($req['status'] !== 'filled'): ?>
    <div class="card-body small text-muted">
        <p class="mb-1">Link do formulário:</p>
        <?php
        $baseUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
        $formUrl = $baseUrl . '/lista-semanal/' . $req['token'];
        ?>
        <div class="input-group input-group-sm">
            <input type="text" class="form-control" value="<?= htmlspecialchars($formUrl) ?>" readonly>
            <button class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(this.previousElementSibling.value); this.innerHTML='<i class=\'bi bi-check\'></i>'"><i class="bi bi-clipboard"></i></button>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
