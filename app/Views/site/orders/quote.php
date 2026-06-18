<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotação - Pedido <?= htmlspecialchars($order['code']) ?> | Brooks Construtora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
        .page-header { background: #3a3b4e; color: #fff; padding: 1rem 0; }
        .main-card { border: none; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .supplier-section { border: 1px solid #dee2e6; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; }
        .supplier-section h6 { margin-bottom: 0.75rem; }
        .price-input { font-weight: 600; text-align: right; }
        .supplier-total { font-weight: 700; font-size: 1.1rem; }
        @media (max-width: 768px) {
            .main-card .card-body { padding: 1rem; }
            .main-card .card-header { padding: 1rem; }
        }
    </style>
</head>
<body>
    <div class="page-header text-center">
        <div class="container">
            <h4 class="mb-1">BROOKS CONSTRUTORA</h4>
            <p class="mb-0 opacity-75 small">Cotação de Materiais</p>
        </div>
    </div>

    <div class="container py-4">
        <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card main-card">
            <div class="card-header bg-warning bg-opacity-10 border-0 p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">Pedido <strong><?= htmlspecialchars($order['code']) ?></strong></h5>
                        <p class="mb-0 text-muted small">Solicitado por: <?= htmlspecialchars($order['created_by_name']) ?> em <?= date('d/m/Y', strtotime($order['created_at'])) ?></p>
                    </div>
                    <span class="badge bg-warning text-dark p-2">Aguardando Cotação</span>
                </div>
                <?php if (!empty($order['description'])): ?>
                <div class="mt-2 p-2 bg-white rounded small">
                    <strong>Obs:</strong> <?= nl2br(htmlspecialchars($order['description'])) ?>
                </div>
                <?php endif; ?>
            </div>

            <form method="POST" action="/pedido/cotacao/enviar/<?= $token ?>" id="quoteForm">
                <div class="card-body p-4">
                    <h6 class="mb-3"><i class="bi bi-person"></i> Identificação</h6>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Seu Nome *</label>
                            <input type="text" class="form-control" name="quoted_by_name" required placeholder="Informe seu nome completo">
                        </div>
                    </div>

                    <!-- Lista de itens (referência) -->
                    <h6 class="mb-2"><i class="bi bi-list-check"></i> Itens do Pedido</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Material</th>
                                    <th>Espec.</th>
                                    <th>Class.</th>
                                    <th>Unid.</th>
                                    <th class="text-center">Qtd</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $i => $item): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($item['material_name']) ?></strong></td>
                                    <td class="text-muted small"><?= htmlspecialchars($item['specification'] ?? '-') ?></td>
                                    <td class="text-muted small"><?= htmlspecialchars($item['classification'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($item['unit'] ?? '-') ?></td>
                                    <td class="text-center fw-bold"><?= number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Preços por fornecedor -->
                    <?php if (!empty($orderSuppliers)): ?>
                    <h6 class="mb-3"><i class="bi bi-currency-dollar"></i> Informe os valores por fornecedor</h6>

                    <?php foreach ($orderSuppliers as $os): ?>
                    <div class="supplier-section">
                        <h6 class="text-primary"><i class="bi bi-building"></i> <?= htmlspecialchars($os['supplier_name']) ?></h6>
                        
                        <?php foreach ($items as $item): ?>
                        <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                            <span class="small"><?= htmlspecialchars($item['material_name']) ?> <span class="text-muted">(x<?= number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2) ?>)</span></span>
                            <div style="max-width:130px;">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text" style="font-size:0.7rem;">R$</span>
                                    <input type="text" inputmode="decimal" class="form-control price-input price-supplier-<?= $os['supplier_id'] ?>" 
                                        name="supplier_prices[<?= $os['supplier_id'] ?>][<?= $item['id'] ?>]" 
                                        data-qty="<?= $item['quantity'] ?>"
                                        data-supplier="<?= $os['supplier_id'] ?>"
                                        placeholder="0,00" required>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <div class="text-end mt-2">
                            <strong>Total: <span class="supplier-total text-success" id="supplier-total-<?= $os['supplier_id'] ?>">R$ 0,00</span></strong>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php else: ?>
                    <!-- Sem fornecedores: preço único por item (legado) -->
                    <h6 class="mb-3"><i class="bi bi-currency-dollar"></i> Informe os valores unitários</h6>
                    <?php foreach ($items as $item): ?>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <strong class="small"><?= htmlspecialchars($item['material_name']) ?></strong>
                            <span class="text-muted small ms-1">(x<?= number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2) ?> <?= htmlspecialchars($item['unit'] ?? '') ?>)</span>
                        </div>
                        <div style="max-width:140px;">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="font-size:0.7rem;">R$</span>
                                <input type="text" inputmode="decimal" class="form-control price-input price-legacy" 
                                    name="items[<?= $item['id'] ?>][unit_price]" 
                                    data-qty="<?= $item['quantity'] ?>"
                                    placeholder="0,00" required>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="text-end mt-2">
                        <strong>Total: <span class="supplier-total text-success" id="legacy-total">R$ 0,00</span></strong>
                    </div>
                    <?php endif; ?>

                    <div class="mt-3">
                        <label class="form-label">Observações da Cotação</label>
                        <textarea class="form-control" name="quote_notes" rows="2" placeholder="Observações sobre preços, prazos, etc."></textarea>
                    </div>
                </div>

                <div class="card-footer p-4 text-center">
                    <button type="submit" class="btn btn-success btn-lg px-5">
                        <i class="bi bi-check-lg"></i> Enviar Cotação
                    </button>
                    <p class="text-muted small mt-2 mb-0">Ao enviar, os valores serão registrados e encaminhados para aprovação.</p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.querySelectorAll('.price-input').forEach(input => {
        input.addEventListener('input', calculateTotals);
        input.addEventListener('blur', function() {
            let val = this.value.replace(/[^\d,\.]/g, '').replace(',', '.');
            if (val && !isNaN(parseFloat(val))) {
                this.value = parseFloat(val).toFixed(2).replace('.', ',');
            }
            calculateTotals();
        });
    });

    function calculateTotals() {
        // Por fornecedor
        const supplierTotals = {};
        document.querySelectorAll('.price-input').forEach(input => {
            const val = parseFloat(input.value.replace(/\./g, '').replace(',', '.')) || 0;
            const qty = parseFloat(input.dataset.qty) || 0;
            const total = val * qty;
            const sid = input.dataset.supplier || 'legacy';
            
            if (!supplierTotals[sid]) supplierTotals[sid] = 0;
            supplierTotals[sid] += total;
        });

        // Atualizar totais exibidos
        for (const [sid, total] of Object.entries(supplierTotals)) {
            const el = document.getElementById('supplier-total-' + sid) || document.getElementById('legacy-total');
            if (el) el.textContent = 'R$ ' + total.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
    }
    </script>
</body>
</html>
