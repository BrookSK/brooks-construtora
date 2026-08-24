<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Listas Semanais | Brooks Construtora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
        .page-header { background: #3a3b4e; color: #fff; padding: 1rem 0; }
        @media (max-width: 576px) {
            .site-card .btn { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="page-header text-center">
        <div class="container">
            <h5 class="mb-1">BROOKS CONSTRUTORA</h5>
            <p class="mb-0 opacity-75 small">Lista Semanal de Materiais</p>
        </div>
    </div>

    <div class="container py-3" style="max-width:700px;">
        <div class="card mb-3">
            <div class="card-body">
                <h5 class="mb-1">Olá, <?= htmlspecialchars($managerName) ?>!</h5>
                <p class="text-muted small mb-0">
                    Estas são as suas listas do ciclo <strong><?= htmlspecialchars($cycleLabel['start']) ?> a <?= htmlspecialchars($cycleLabel['end']) ?></strong>.
                    Preencha a lista de materiais de cada obra que você é responsável.
                </p>
            </div>
        </div>

        <?php
        $pending = array_filter($requests, fn($r) => $r['status'] !== 'filled' && empty($r['order_id']));
        $filled = array_filter($requests, fn($r) => $r['status'] === 'filled' || !empty($r['order_id']));
        ?>

        <?php if (!empty($pending)): ?>
        <h6 class="text-muted mb-2"><i class="bi bi-hourglass-split"></i> Pendentes (<?= count($pending) ?>)</h6>
        <?php foreach ($pending as $req): ?>
        <div class="card mb-2 site-card border-warning border-opacity-50">
            <div class="card-body py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <strong>🏗️ <?= htmlspecialchars(!empty($req['construction_site_name']) ? (($req['construction_site_code'] ? $req['construction_site_code'] . ' - ' : '') . $req['construction_site_name']) : 'Obra não especificada') ?></strong>
                    <br><span class="badge bg-warning text-dark mt-1">Pendente</span>
                </div>
                <a href="/lista-semanal/<?= htmlspecialchars($req['token']) ?>" class="btn btn-primary">
                    <i class="bi bi-pencil-square"></i> Preencher Lista
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($filled)): ?>
        <h6 class="text-muted mb-2 mt-4"><i class="bi bi-check-circle"></i> Já Preenchidas (<?= count($filled) ?>)</h6>
        <?php foreach ($filled as $req): ?>
        <div class="card mb-2 site-card border-success border-opacity-50">
            <div class="card-body py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <strong>🏗️ <?= htmlspecialchars(!empty($req['construction_site_name']) ? (($req['construction_site_code'] ? $req['construction_site_code'] . ' - ' : '') . $req['construction_site_name']) : 'Obra não especificada') ?></strong>
                    <br><span class="badge bg-success mt-1">Preenchida<?= !empty($req['order_code']) ? ' — Pedido ' . htmlspecialchars($req['order_code']) : '' ?></span>
                </div>
                <a href="/lista-semanal/<?= htmlspecialchars($req['token']) ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-eye"></i> Ver
                </a>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if (empty($requests)): ?>
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-inbox" style="font-size:2.5rem;"></i>
                <p class="mt-2 mb-0">Nenhuma lista para este ciclo.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
