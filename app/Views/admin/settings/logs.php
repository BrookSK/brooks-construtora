<?php $pageTitle = 'Logs do Sistema'; $currentPage = 'settings'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h5 class="mb-0"><i class="bi bi-terminal"></i> Logs do Sistema</h5>
    <a href="/admin/settings" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Configurações
    </a>
</div>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="/admin/logs" class="d-flex gap-2 flex-wrap align-items-center">
            <label class="small text-muted">Data:</label>
            <input type="date" name="date" class="form-control form-control-sm" style="max-width:160px;" value="<?= htmlspecialchars($currentDate) ?>">
            <label class="small text-muted">Linhas:</label>
            <select name="lines" class="form-select form-select-sm" style="max-width:90px;">
                <option value="50" <?= $lines == 50 ? 'selected' : '' ?>>50</option>
                <option value="100" <?= $lines == 100 ? 'selected' : '' ?>>100</option>
                <option value="200" <?= $lines == 200 ? 'selected' : '' ?>>200</option>
                <option value="500" <?= $lines == 500 ? 'selected' : '' ?>>500</option>
            </select>
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i> Ver</button>
            <a href="/admin/logs" class="btn btn-sm btn-outline-secondary">Hoje</a>
        </form>
    </div>
</div>

<div class="row">
    <!-- Log content -->
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <small class="text-muted">Log: app-<?= htmlspecialchars($currentDate) ?>.log (últimas <?= $lines ?> linhas)</small>
                <button class="btn btn-sm btn-outline-primary" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i> Atualizar</button>
            </div>
            <div class="card-body p-0">
                <pre style="margin:0; padding:1rem; background:#1e1e2e; color:#cdd6f4; font-size:0.72rem; line-height:1.6; max-height:70vh; overflow:auto; border-radius:0 0 8px 8px; white-space:pre-wrap; word-break:break-all;"><?php
                    $lines_arr = explode("\n", $logContent);
                    foreach ($lines_arr as $line) {
                        if (empty(trim($line))) continue;
                        // Colorir por nível
                        $color = '#cdd6f4';
                        if (str_contains($line, '[ERROR]')) $color = '#f38ba8';
                        elseif (str_contains($line, '[WARNING]')) $color = '#fab387';
                        elseif (str_contains($line, '[ACTION]')) $color = '#a6e3a1';
                        elseif (str_contains($line, '[SQL]')) $color = '#89b4fa';
                        elseif (str_contains($line, '[REQUEST]')) $color = '#b4befe';
                        elseif (str_contains($line, '[DEBUG]')) $color = '#9399b2';
                        echo '<span style="color:' . $color . ';">' . htmlspecialchars($line) . '</span>' . "\n";
                    }
                ?></pre>
            </div>
        </div>
    </div>

    <!-- Sidebar com arquivos -->
    <div class="col-lg-3">
        <div class="card">
            <div class="card-header py-2"><small class="fw-bold">Arquivos de Log</small></div>
            <div class="list-group list-group-flush" style="max-height:300px; overflow-y:auto;">
                <?php if (empty($logFiles)): ?>
                <div class="list-group-item text-muted small">Nenhum arquivo de log.</div>
                <?php else: ?>
                <?php foreach ($logFiles as $lf): ?>
                <a href="/admin/logs?date=<?= $lf['date'] ?>&lines=<?= $lines ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 <?= $lf['date'] === $currentDate ? 'active' : '' ?>">
                    <small><?= $lf['date'] ?></small>
                    <span class="badge bg-secondary"><?= number_format($lf['size'] / 1024, 1) ?> KB</span>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header py-2"><small class="fw-bold">Legenda</small></div>
            <div class="card-body py-2" style="font-size:0.75rem;">
                <div><span style="color:#b4befe;">&#9632;</span> REQUEST - Requisição HTTP</div>
                <div><span style="color:#a6e3a1;">&#9632;</span> ACTION - Ação do usuário</div>
                <div><span style="color:#89b4fa;">&#9632;</span> SQL - Query ao banco</div>
                <div><span style="color:#fab387;">&#9632;</span> WARNING - Aviso</div>
                <div><span style="color:#f38ba8;">&#9632;</span> ERROR - Erro</div>
                <div><span style="color:#9399b2;">&#9632;</span> DEBUG - Debug</div>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
