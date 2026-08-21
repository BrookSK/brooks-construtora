<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista Enviada | Brooks Construtora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
        .page-header { background: #3a3b4e; color: #fff; padding: 1rem 0; }
    </style>
</head>
<body>
    <div class="page-header text-center">
        <div class="container">
            <h5 class="mb-1">BROOKS CONSTRUTORA</h5>
            <p class="mb-0 opacity-75 small">Lista Semanal de Materiais</p>
        </div>
    </div>

    <div class="container py-4" style="max-width:600px;">
        <div class="card text-center">
            <div class="card-body py-5">
                <div class="mb-3">
                    <i class="bi bi-check-circle-fill text-success" style="font-size:3rem;"></i>
                </div>
                <h5 class="mb-2">Lista já enviada!</h5>
                <p class="text-muted">
                    <?= htmlspecialchars($request['manager_name']) ?>, sua lista da semana de
                    <strong><?= date('d/m/Y', strtotime($request['week_start'])) ?></strong>
                    foi preenchida em <?= date('d/m/Y H:i', strtotime($request['filled_at'])) ?>.
                </p>

                <?php if (!empty($items)): ?>
                <hr>
                <h6 class="text-start">Materiais informados:</h6>
                <ul class="list-group list-group-flush text-start">
                    <?php foreach ($items as $item): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= htmlspecialchars($item['material_name']) ?></span>
                        <span class="badge bg-primary"><?= number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2) ?> <?= htmlspecialchars($item['unit'] ?? '') ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <?php if (!empty($request['notes'])): ?>
                <div class="alert alert-light mt-3 text-start small">
                    <strong>Observações:</strong> <?= nl2br(htmlspecialchars($request['notes'])) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
