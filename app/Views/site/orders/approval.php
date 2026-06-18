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
        .page-header { background: #3a3b4e; color: #fff; padding: 1.5rem 0; }
        .main-card { border: none; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .total-badge { font-size: 1.8rem; font-weight: 700; color: #28a745; }
        @media (max-width: 768px) {
            .total-badge { font-size: 1.4rem; }
            .table-responsive table { font-size: 0.8rem; }
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
                    <div class="text-end">
                        <small class="text-muted d-block">Valor Total</small>
                        <span class="total-badge">R$ <?= number_format($order['total_estimated'], 2, ',', '.') ?></span>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <?php if (!empty($order['description'])): ?>
                <div class="alert alert-light">
                    <strong>Observações do pedido:</strong><br>
                    <?= nl2br(htmlspecialchars($order['description'])) ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($order['quote_notes'])): ?>
                <div class="alert alert-warning">
                    <strong>Observações da cotação:</strong><br>
                    <?= nl2br(htmlspecialchars($order['quote_notes'])) ?>
                </div>
                <?php endif; ?>

                <?php if ($supplier): ?>
                <div class="mb-3 p-3 bg-light rounded">
                    <strong>Fornecedor:</strong> <?= htmlspecialchars($supplier['name']) ?>
                    <?= $supplier['cnpj'] ? ' — CNPJ: ' . htmlspecialchars($supplier['cnpj']) : '' ?>
                </div>
                <?php endif; ?>

                <h6 class="mb-3"><i class="bi bi-list-check"></i> Itens Cotados</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Material</th>
                                <th>Espec.</th>
                                <th>Class.</th>
                                <th>Unid.</th>
                                <th class="text-center">Qtd</th>
                                <th class="text-end">Valor Unit.</th>
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
                                <td class="text-center"><?= number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2) ?></td>
                                <td class="text-end">R$ <?= number_format($item['unit_price'] ?? 0, 2, ',', '.') ?></td>
                                <td class="text-end fw-bold">R$ <?= number_format($item['total_price'] ?? 0, 2, ',', '.') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-success">
                                <td colspan="7" class="text-end fw-bold">TOTAL:</td>
                                <td class="text-end fw-bold">R$ <?= number_format($order['total_estimated'], 2, ',', '.') ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

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
                        <textarea class="form-control" name="approval_notes" rows="2" placeholder="Observações sobre a decisão (obrigatório em caso de rejeição)"></textarea>
                    </div>

                    <div class="d-flex flex-wrap gap-3 justify-content-center pt-3">
                        <button type="submit" name="action" value="approve" class="btn btn-success btn-lg px-5" onclick="return confirm('Confirma a APROVAÇÃO deste pedido?')">
                            <i class="bi bi-check-circle"></i> Aprovar Pedido
                        </button>
                        <button type="submit" name="action" value="reject" class="btn btn-outline-danger btn-lg px-5" onclick="return confirm('Confirma a REJEIÇÃO deste pedido?')">
                            <i class="bi bi-x-circle"></i> Rejeitar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
