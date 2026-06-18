<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprovação - Pedido <?= htmlspecialchars($order['code']) ?> | Brooks Construtora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
        .page-header { background: #3a3b4e; color: #fff; padding: 1rem 0; }
        .main-card { border: none; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .supplier-compare { border: 2px solid #dee2e6; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; cursor: pointer; transition: all 0.2s; }
        .supplier-compare:hover { border-color: #28a745; }
        .supplier-compare.selected { border-color: #28a745; background: #f0fff4; }
        .supplier-compare .supplier-total { font-size: 1.3rem; font-weight: 700; }
        @media (max-width: 768px) {
            .main-card .card-body { padding: 1rem; }
            .main-card .card-header { padding: 1rem; }
            .btn-lg { font-size: 0.9rem; padding: 0.6rem 1.2rem; }
        }
    </style>
</head>
<body>
    <div class="page-header text-center">
        <div class="container">
            <h4 class="mb-1">BROOKS CONSTRUTORA</h4>
            <p class="mb-0 opacity-75 small">Aprovação de Pedido</p>
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
            <div class="card-header bg-info bg-opacity-10 border-0 p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h5 class="mb-1">Pedido <strong><?= htmlspecialchars($order['code']) ?></strong></h5>
                        <p class="mb-0 text-muted small">Solicitado por: <?= htmlspecialchars($order['created_by_name']) ?></p>
                        <p class="mb-0 text-muted small">Cotado por: <strong><?= htmlspecialchars($order['quoted_by_name']) ?></strong> em <?= date('d/m/Y H:i', strtotime($order['quoted_at'])) ?></p>
                    </div>
                    <span class="badge bg-info text-white p-2">Aguardando Aprovação</span>
                </div>
            </div>

            <div class="card-body p-4">
                <?php if (!empty($order['description'])): ?>
                <div class="alert alert-light small"><strong>Observações do pedido:</strong> <?= nl2br(htmlspecialchars($order['description'])) ?></div>
                <?php endif; ?>
                <?php if (!empty($order['quote_notes'])): ?>
                <div class="alert alert-warning small"><strong>Observações da cotação:</strong> <?= nl2br(htmlspecialchars($order['quote_notes'])) ?></div>
                <?php endif; ?>

                <?php if (!empty($orderSuppliers)): ?>
                <!-- Multi-fornecedor: Comparação -->
                <h6 class="mb-3"><i class="bi bi-building"></i> Fornecedores Cotados — Selecione o aprovado</h6>

                <?php
                // Agrupar preços por fornecedor
                $pricesBySupplier = [];
                foreach ($itemPrices as $p) {
                    $pricesBySupplier[$p['supplier_id']][$p['item_id']] = $p;
                }
                ?>

                <?php foreach ($orderSuppliers as $os): ?>
                <div class="supplier-compare" onclick="selectSupplier(<?= $os['supplier_id'] ?>)" id="supplier-card-<?= $os['supplier_id'] ?>">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <input type="radio" name="approved_supplier_id" value="<?= $os['supplier_id'] ?>" id="radio-<?= $os['supplier_id'] ?>" form="approvalForm" class="form-check-input me-2">
                            <label for="radio-<?= $os['supplier_id'] ?>" class="fw-bold"><?= htmlspecialchars($os['supplier_name']) ?></label>
                        </div>
                        <span class="supplier-total text-success">R$ <?= number_format($os['total'] ?? 0, 2, ',', '.') ?></span>
                    </div>
                    
                    <!-- Detalhes dos itens deste fornecedor -->
                    <div class="small">
                        <?php if (isset($pricesBySupplier[$os['supplier_id']])): ?>
                        <?php foreach ($items as $item): ?>
                            <?php $p = $pricesBySupplier[$os['supplier_id']][$item['id']] ?? null; ?>
                            <?php if ($p): ?>
                            <div class="d-flex justify-content-between py-1 border-bottom" style="font-size:0.8rem;">
                                <span><?= htmlspecialchars($item['material_name']) ?> (x<?= number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2) ?>)</span>
                                <span>R$ <?= number_format($p['unit_price'], 2, ',', '.') ?> = <strong>R$ <?= number_format($p['total_price'], 2, ',', '.') ?></strong></span>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php else: ?>
                <!-- Sem fornecedores: exibe tabela de itens com preços -->
                <h6 class="mb-3"><i class="bi bi-list-check"></i> Itens Cotados</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Material</th>
                                <th class="text-center">Qtd</th>
                                <th class="text-end">Unit.</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['material_name']) ?></td>
                                <td class="text-center"><?= number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2) ?></td>
                                <td class="text-end">R$ <?= number_format($item['unit_price'] ?? 0, 2, ',', '.') ?></td>
                                <td class="text-end fw-bold">R$ <?= number_format($item['total_price'] ?? 0, 2, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="table-success">
                                <td colspan="3" class="text-end fw-bold">TOTAL:</td>
                                <td class="text-end fw-bold">R$ <?= number_format($order['total_estimated'], 2, ',', '.') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <hr>

                <!-- Formulário de decisão -->
                <form method="POST" action="/pedido/aprovacao/enviar/<?= $token ?>" id="approvalForm">
                    <h6 class="mb-3"><i class="bi bi-person-check"></i> Sua Decisão</h6>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Seu Nome *</label>
                            <input type="text" class="form-control" name="person_name" required placeholder="Informe seu nome completo">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="approval_notes" rows="2" placeholder="Observações (obrigatório em caso de rejeição)"></textarea>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-center pt-3">
                        <button type="submit" name="action" value="approve" class="btn btn-success btn-lg px-5" onclick="return confirmApproval()">
                            <i class="bi bi-check-circle"></i> Aprovar Pedido
                        </button>
                        <button type="submit" name="action" value="reject" class="btn btn-outline-danger btn-lg px-5" onclick="return confirm('Confirma a REJEIÇÃO de TODOS os fornecedores deste pedido?')">
                            <i class="bi bi-x-circle"></i> Rejeitar Todos
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function selectSupplier(sid) {
        document.querySelectorAll('.supplier-compare').forEach(el => el.classList.remove('selected'));
        document.getElementById('supplier-card-' + sid).classList.add('selected');
        document.getElementById('radio-' + sid).checked = true;
    }

    function confirmApproval() {
        const hasSuppliers = document.querySelectorAll('.supplier-compare').length > 0;
        if (hasSuppliers) {
            const selected = document.querySelector('input[name="approved_supplier_id"]:checked');
            if (!selected) {
                alert('Selecione qual fornecedor está aprovando.');
                return false;
            }
        }
        return confirm('Confirma a APROVAÇÃO deste pedido?');
    }
    </script>
</body>
</html>
