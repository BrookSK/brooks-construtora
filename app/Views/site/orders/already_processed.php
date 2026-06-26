<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Já Processado | Brooks Construtora</title>
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
                    <div class="mb-3">
                        <?php if ($order['status'] === 'approved'): ?>
                        <i class="bi bi-check-circle-fill text-success" style="font-size:3.5rem;"></i>
                        <?php elseif ($order['status'] === 'rejected'): ?>
                        <i class="bi bi-x-circle-fill text-danger" style="font-size:3.5rem;"></i>
                        <?php elseif ($order['status'] === 'cancelled'): ?>
                        <i class="bi bi-slash-circle-fill text-dark" style="font-size:3.5rem;"></i>
                        <?php else: ?>
                        <i class="bi bi-info-circle-fill text-info" style="font-size:3.5rem;"></i>
                        <?php endif; ?>
                    </div>
                    <h3 class="mb-2"><?= $order['status'] === 'cancelled' ? 'Pedido Cancelado' : 'Pedido Já Processado' ?></h3>
                    <p class="text-muted"><?= htmlspecialchars($message) ?></p>
                    
                    <div class="bg-light rounded p-3 text-start">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Pedido</small>
                            <strong><?= htmlspecialchars($order['code']) ?></strong>
                        </div>
                        <?php if ($order['status'] === 'approved'): ?>
                        <?php
                        $allApprovedSuppliers = \App\Models\PurchaseOrderSupplier::getAllApproved($order['id']);
                        $supplierDisplay = '';
                        if (!empty($allApprovedSuppliers) && count($allApprovedSuppliers) > 1) {
                            $supplierDisplay = implode(', ', array_column($allApprovedSuppliers, 'supplier_name'));
                        } elseif (!empty($order['supplier_name'])) {
                            $supplierDisplay = $order['supplier_name'];
                        }
                        ?>
                        <?php if (!empty($supplierDisplay)): ?>
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Fornecedor(es) aprovado(s)</small>
                            <strong class="text-success"><?= htmlspecialchars($supplierDisplay) ?></strong>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($order['total_estimated'] > 0): ?>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">Valor</small>
                            <strong>R$ <?= number_format($order['total_estimated'], 2, ',', '.') ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
