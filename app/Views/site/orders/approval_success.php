<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido <?= $action === 'approved' ? 'Aprovado' : 'Rejeitado' ?> | Brooks Construtora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; min-height: 100vh; display: flex; align-items: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-0 shadow text-center p-4 p-md-5">
                    <?php if ($action === 'approved'): ?>
                    <div class="mb-3">
                        <i class="bi bi-check-circle-fill text-success" style="font-size:3.5rem;"></i>
                    </div>
                    <h3 class="mb-2 text-success">Pedido Aprovado!</h3>
                    <p class="text-muted">
                        O pedido <strong><?= htmlspecialchars($order['code']) ?></strong> foi aprovado com sucesso.
                    </p>

                    <?php if ($approvedSupplier): ?>
                    <div class="bg-light rounded p-3 mb-3 text-start">
                        <small class="text-muted d-block mb-1">Fornecedor Aprovado</small>
                        <strong><?= htmlspecialchars($approvedSupplier['supplier_name']) ?></strong>
                        <div class="text-success fw-bold mt-1">R$ <?= number_format($approvedSupplier['total'] ?? 0, 2, ',', '.') ?></div>
                    </div>
                    <?php endif; ?>

                    <p class="small text-muted mb-0">
                        A formalização em PDF será gerada e enviada aos responsáveis.
                    </p>

                    <?php else: ?>
                    <div class="mb-3">
                        <i class="bi bi-x-circle-fill text-danger" style="font-size:3.5rem;"></i>
                    </div>
                    <h3 class="mb-2 text-danger">Pedido Rejeitado</h3>
                    <p class="text-muted">
                        O pedido <strong><?= htmlspecialchars($order['code']) ?></strong> foi rejeitado.<br>
                        Todos os fornecedores foram descartados nesta cotação.
                    </p>
                    <p class="small text-muted mb-0">
                        Os responsáveis serão notificados.
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
