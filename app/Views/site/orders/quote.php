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
        .price-input { font-weight: 600; text-align: right; }
        .item-total { font-weight: 700; color: #28a745; }
        .grand-total { font-size: 1.5rem; font-weight: 700; color: #28a745; }
        @media (max-width: 768px) {
            .table-responsive table { font-size: 0.8rem; }
            .price-input { font-size: 0.85rem; }
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

                    <h6 class="mb-3"><i class="bi bi-list-check"></i> Itens - Informe o valor unitário</h6>
                    
                    <!-- Desktop: Tabela -->
                    <div class="d-none d-md-block">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Material</th>
                                        <th>Espec.</th>
                                        <th>Class.</th>
                                        <th>Unid.</th>
                                        <th class="text-center">Qtd</th>
                                        <th class="text-end" style="min-width:120px;">Valor Unit. (R$)</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $i => $item): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><strong><?= htmlspecialchars($item['material_name']) ?></strong></td>
                                        <td class="text-muted"><?= htmlspecialchars($item['specification'] ?? '-') ?></td>
                                        <td class="text-muted"><?= htmlspecialchars($item['classification'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($item['unit'] ?? '-') ?></td>
                                        <td class="text-center fw-bold"><?= number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2) ?></td>
                                        <td>
                                            <input type="text" inputmode="decimal" class="form-control form-control-sm price-input" 
                                                id="price-<?= $item['id'] ?>"
                                                name="items[<?= $item['id'] ?>][unit_price]" 
                                                data-qty="<?= $item['quantity'] ?>"
                                                data-id="<?= $item['id'] ?>"
                                                placeholder="0,00" required>
                                        </td>
                                        <td class="text-end item-total" id="total-<?= $item['id'] ?>">R$ 0,00</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <td colspan="7" class="text-end fw-bold">TOTAL GERAL:</td>
                                        <td class="text-end grand-total" id="grandTotal">R$ 0,00</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Mobile: Cards (inputs referenciam os mesmos do desktop via JS) -->
                    <div class="d-md-none">
                        <?php foreach ($items as $i => $item): ?>
                        <div class="border rounded p-3 mb-2 bg-white">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <strong style="font-size:0.9rem;"><?= htmlspecialchars($item['material_name']) ?></strong>
                                <span class="badge bg-light text-dark">#<?= $i + 1 ?></span>
                            </div>
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                <?php if ($item['specification']): ?><span class="badge bg-light text-muted" style="font-size:0.65rem;"><?= htmlspecialchars($item['specification']) ?></span><?php endif; ?>
                                <?php if ($item['classification']): ?><span class="badge bg-light text-muted" style="font-size:0.65rem;"><?= htmlspecialchars($item['classification']) ?></span><?php endif; ?>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Qtd: <strong><?= number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2) ?></strong> <?= htmlspecialchars($item['unit'] ?? '') ?></span>
                                <div style="max-width:140px;">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text" style="font-size:0.75rem;">R$</span>
                                        <input type="text" inputmode="decimal" class="form-control price-input-mobile" 
                                            data-target="price-<?= $item['id'] ?>"
                                            data-qty="<?= $item['quantity'] ?>"
                                            data-id="<?= $item['id'] ?>"
                                            placeholder="0,00" style="font-size:0.9rem;">
                                    </div>
                                </div>
                            </div>
                            <div class="text-end mt-1">
                                <small class="item-total text-success fw-bold" id="total-m-<?= $item['id'] ?>">R$ 0,00</small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <div class="border-top pt-2 mt-2 text-end">
                            <span class="fw-bold">TOTAL: </span>
                            <span class="grand-total" id="grandTotalMobile">R$ 0,00</span>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12">
                            <label class="form-label">Observações da Cotação</label>
                            <textarea class="form-control" name="quote_notes" rows="2" placeholder="Observações sobre preços, prazos de entrega, etc."></textarea>
                        </div>
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
    // Desktop inputs (são os que vão no form)
    document.querySelectorAll('.price-input').forEach(input => {
        input.addEventListener('input', function() { calculateTotals(); });
        input.addEventListener('blur', function() { formatPrice(this); });
    });

    // Mobile inputs (sincronizam com os desktop)
    document.querySelectorAll('.price-input-mobile').forEach(input => {
        input.addEventListener('input', function() {
            const targetId = this.dataset.target;
            const desktopInput = document.getElementById(targetId);
            if (desktopInput) desktopInput.value = this.value;
            calculateTotals();
        });
        input.addEventListener('blur', function() { 
            formatPrice(this);
            const targetId = this.dataset.target;
            const desktopInput = document.getElementById(targetId);
            if (desktopInput) desktopInput.value = this.value;
        });
    });

    function formatPrice(el) {
        let val = el.value.replace(/[^\d,\.]/g, '').replace(',', '.');
        if (val && !isNaN(parseFloat(val))) {
            el.value = parseFloat(val).toFixed(2).replace('.', ',');
        }
    }

    function calculateTotals() {
        let grandTotal = 0;

        document.querySelectorAll('.price-input').forEach(input => {
            const val = parseFloat(input.value.replace(/\./g, '').replace(',', '.')) || 0;
            const qty = parseFloat(input.dataset.qty) || 0;
            const total = val * qty;
            const itemId = input.dataset.id;
            grandTotal += total;

            const formatted = 'R$ ' + total.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            const desktopEl = document.getElementById('total-' + itemId);
            const mobileEl = document.getElementById('total-m-' + itemId);
            if (desktopEl) desktopEl.textContent = formatted;
            if (mobileEl) mobileEl.textContent = formatted;
        });

        const grandFormatted = 'R$ ' + grandTotal.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        const gtDesktop = document.getElementById('grandTotal');
        const gtMobile = document.getElementById('grandTotalMobile');
        if (gtDesktop) gtDesktop.textContent = grandFormatted;
        if (gtMobile) gtMobile.textContent = grandFormatted;
    }
    </script>
</body>
</html>
