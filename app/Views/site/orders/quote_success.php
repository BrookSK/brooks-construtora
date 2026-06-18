<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotação Enviada | Brooks Construtora</title>
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
                <div class="card border-0 shadow text-center p-5">
                    <div class="mb-3">
                        <i class="bi bi-check-circle-fill text-success" style="font-size:4rem;"></i>
                    </div>
                    <h3 class="mb-2">Cotação Enviada!</h3>
                    <p class="text-muted mb-3">
                        A cotação do pedido <strong><?= htmlspecialchars($order['code']) ?></strong> foi registrada com sucesso.
                    </p>
                    <div class="bg-light rounded p-3 mb-3">
                        <small class="text-muted">Valor Total</small>
                        <h4 class="text-success mb-0">R$ <?= number_format($total, 2, ',', '.') ?></h4>
                    </div>
                    <p class="small text-muted">
                        O pedido será encaminhado para aprovação. Você receberá uma notificação quando for aprovado ou rejeitado.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
