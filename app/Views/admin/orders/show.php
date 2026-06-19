<?php $pageTitle = 'Pedido ' . $order['code']; $currentPage = 'orders'; ?>
<?php ob_start(); ?>

<?php
$statusLabels = [
    'draft' => ['Rascunho', 'secondary', 'bi-pencil'],
    'pending_quote' => ['Aguardando Cotação', 'warning', 'bi-clock'],
    'quoted' => ['Cotado', 'info', 'bi-check-circle'],
    'pending_approval' => ['Aguardando Aprovação', 'info', 'bi-hourglass-split'],
    'approved' => ['Aprovado', 'success', 'bi-check-circle-fill'],
    'rejected' => ['Rejeitado', 'danger', 'bi-x-circle-fill'],
    'cancelled' => ['Cancelado', 'dark', 'bi-slash-circle'],
];
$statusInfo = $statusLabels[$order['status']] ?? ['Desconhecido', 'secondary', 'bi-question'];
$baseUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'www.brooksconstrutora.com.br');
?>

<div class="row">
    <!-- Coluna principal -->
    <div class="col-lg-8 order-2 order-lg-1">
        <!-- Status card -->
        <div class="card mb-3 border-<?= $statusInfo[1] ?>">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi <?= $statusInfo[2] ?> text-<?= $statusInfo[1] ?>" style="font-size:2rem;"></i>
                <div>
                    <h5 class="mb-0"><?= $statusInfo[0] ?></h5>
                    <small class="text-muted">Código: <strong><?= $order['code'] ?></strong></small>
                </div>
                <div class="ms-auto">
                    <?php if ($order['status'] === 'approved'): ?>
                    <a href="/pedido/pdf/<?= $order['id'] ?>" class="btn btn-sm btn-success" target="_blank">
                        <i class="bi bi-file-pdf"></i> PDF
                    </a>
                    <a href="/pedido/xlsx/<?= $order['id'] ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-file-earmark-spreadsheet"></i> XLSX
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Informações do pedido -->
        <div class="card mb-3">
            <div class="card-header">Informações do Pedido</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-6 mb-2">
                        <small class="text-muted d-block">Fornecedor Aprovado</small>
                        <strong><?= htmlspecialchars($order['supplier_name'] ?? 'Pendente') ?></strong>
                    </div>
                    <div class="col-sm-6 mb-2">
                        <small class="text-muted d-block">Solicitante</small>
                        <strong><?= htmlspecialchars($order['created_by_name'] ?? '-') ?></strong>
                    </div>
                    <div class="col-sm-6 mb-2">
                        <small class="text-muted d-block">Data de Criação</small>
                        <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                    </div>
                    <?php if ($order['total_estimated'] > 0): ?>
                    <div class="col-sm-6 mb-2">
                        <small class="text-muted d-block">Valor Total</small>
                        <strong class="text-success fs-5">R$ <?= number_format($order['total_estimated'], 2, ',', '.') ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($order['quoted_by_name'])): ?>
                    <div class="col-sm-6 mb-2">
                        <small class="text-muted d-block">Cotado por</small>
                        <?= htmlspecialchars($order['quoted_by_name']) ?> 
                        <small class="text-muted">(<?= $order['quoted_at'] ? date('d/m/Y H:i', strtotime($order['quoted_at'])) : '' ?>)</small>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($order['approved_by_name'])): ?>
                    <div class="col-sm-6 mb-2">
                        <small class="text-muted d-block">Aprovado por</small>
                        <?= htmlspecialchars($order['approved_by_name']) ?>
                        <small class="text-muted">(<?= $order['approved_at'] ? date('d/m/Y H:i', strtotime($order['approved_at'])) : '' ?>)</small>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($order['rejected_by_name'])): ?>
                    <div class="col-sm-6 mb-2">
                        <small class="text-muted d-block">Rejeitado por</small>
                        <span class="text-danger"><?= htmlspecialchars($order['rejected_by_name']) ?></span>
                        <small class="text-muted">(<?= $order['rejected_at'] ? date('d/m/Y H:i', strtotime($order['rejected_at'])) : '' ?>)</small>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($order['description'])): ?>
                <hr>
                <small class="text-muted d-block">Observações</small>
                <p class="mb-0"><?= nl2br(htmlspecialchars($order['description'])) ?></p>
                <?php endif; ?>
                <?php if (!empty($order['approval_notes'])): ?>
                <hr>
                <small class="text-muted d-block">Notas da Aprovação/Rejeição</small>
                <p class="mb-0"><?= nl2br(htmlspecialchars($order['approval_notes'])) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Itens -->
        <div class="card mb-3">
            <div class="card-header">Itens do Pedido</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Material</th>
                            <th>Espec.</th>
                            <th>Class.</th>
                            <th>Unid.</th>
                            <th class="text-center">Qtd</th>
                            <?php if ($order['total_estimated'] > 0): ?>
                            <th class="text-end">Unit.</th>
                            <th class="text-end">Total</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $i => $item): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><strong><?= htmlspecialchars($item['material_name']) ?></strong></td>
                            <td><?= htmlspecialchars($item['specification'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($item['classification'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($item['unit'] ?? '-') ?></td>
                            <td class="text-center"><?= number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2) ?></td>
                            <?php if ($order['total_estimated'] > 0): ?>
                            <td class="text-end"><?= $item['unit_price'] ? 'R$ ' . number_format($item['unit_price'], 2, ',', '.') : '-' ?></td>
                            <td class="text-end fw-bold"><?= $item['total_price'] ? 'R$ ' . number_format($item['total_price'], 2, ',', '.') : '-' ?></td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php if ($order['total_estimated'] > 0): ?>
                        <tr class="table-light">
                            <td colspan="7" class="text-end fw-bold">TOTAL:</td>
                            <td class="text-end fw-bold text-success">R$ <?= number_format($order['total_estimated'], 2, ',', '.') ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Fornecedores Cotados -->
        <?php if (!empty($orderSuppliers)): ?>
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-building"></i> Fornecedores Cotados</div>
            <div class="card-body p-0">
                <?php foreach ($orderSuppliers as $os): ?>
                <div class="p-3 <?= $os['approved'] ? 'bg-success bg-opacity-10' : '' ?> <?= !$loop ?? '' ?>border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <strong><?= htmlspecialchars($os['supplier_name']) ?></strong>
                            <?php if ($os['approved']): ?>
                            <span class="badge bg-success ms-1">Aprovado</span>
                            <?php elseif ($os['status'] === 'rejected'): ?>
                            <span class="badge bg-danger ms-1">Não selecionado</span>
                            <?php elseif ($os['status'] === 'quoted'): ?>
                            <span class="badge bg-info ms-1">Cotado</span>
                            <?php else: ?>
                            <span class="badge bg-secondary ms-1">Pendente</span>
                            <?php endif; ?>
                        </div>
                        <strong class="<?= $os['approved'] ? 'text-success' : '' ?> fs-6">
                            <?= $os['subtotal_final'] ? 'R$ ' . number_format($os['subtotal_final'], 2, ',', '.') : ($os['total'] ? 'R$ ' . number_format($os['total'], 2, ',', '.') : '-') ?>
                        </strong>
                    </div>
                    <?php if ($os['quoted_by_name']): ?>
                    <small class="text-muted d-block mb-2">Cotado por <?= htmlspecialchars($os['quoted_by_name']) ?> em <?= $os['quoted_at'] ? date('d/m/Y H:i', strtotime($os['quoted_at'])) : '' ?></small>
                    <?php endif; ?>
                    
                    <?php if ($os['vendor_name'] || $os['delivery_days']): ?>
                    <div class="small mb-2">
                        <?php if ($os['vendor_name']): ?>
                        <span class="me-3"><strong>Vendedor:</strong> <?= htmlspecialchars($os['vendor_name']) ?></span>
                        <?php endif; ?>
                        <?php if ($os['vendor_phone']): ?>
                        <span class="me-3"><i class="bi bi-telephone"></i> <?= htmlspecialchars($os['vendor_phone']) ?></span>
                        <?php endif; ?>
                        <?php if ($os['vendor_email']): ?>
                        <span class="me-3"><i class="bi bi-envelope"></i> <?= htmlspecialchars($os['vendor_email']) ?></span>
                        <?php endif; ?>
                        <?php if ($os['delivery_days']): ?>
                        <span><strong>Prazo:</strong> <?= $os['delivery_days'] ?> dias</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($os['subtotal_items'] > 0): ?>
                    <div class="row small mt-2">
                        <div class="col-6 col-md-3 mb-1">
                            <span class="text-muted">Subtotal insumos:</span><br>
                            <strong>R$ <?= number_format($os['subtotal_items'], 2, ',', '.') ?></strong>
                        </div>
                        <?php if ($os['discount_value'] > 0): ?>
                        <div class="col-6 col-md-2 mb-1">
                            <span class="text-muted">Desconto:</span><br>
                            <strong><?= $os['discount_value'] ?><?= $os['discount_type'] === 'percent' ? '%' : ' R$' ?></strong>
                        </div>
                        <?php endif; ?>
                        <?php if ($os['surcharge_value'] > 0): ?>
                        <div class="col-6 col-md-2 mb-1">
                            <span class="text-muted">Acréscimo:</span><br>
                            <strong><?= $os['surcharge_value'] ?><?= $os['surcharge_type'] === 'percent' ? '%' : ' R$' ?></strong>
                        </div>
                        <?php endif; ?>
                        <?php if ($os['ipi_percent'] > 0): ?>
                        <div class="col-4 col-md-1 mb-1">
                            <span class="text-muted">IPI:</span><br>
                            <strong><?= $os['ipi_percent'] ?>%</strong>
                        </div>
                        <?php endif; ?>
                        <?php if ($os['icms_percent'] > 0): ?>
                        <div class="col-4 col-md-1 mb-1">
                            <span class="text-muted">ICMS:</span><br>
                            <strong><?= $os['icms_percent'] ?>%</strong>
                        </div>
                        <?php endif; ?>
                        <?php if ($os['freight'] > 0): ?>
                        <div class="col-4 col-md-2 mb-1">
                            <span class="text-muted">Frete:</span><br>
                            <strong>R$ <?= number_format($os['freight'], 2, ',', '.') ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- NF e Boleto -->
        <?php if ($order['status'] === 'approved'): ?>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-receipt"></i> NF e Boleto</span>
            </div>
            <div class="card-body">
                <?php if (!empty($payments)): ?>
                <div class="list-group list-group-flush mb-3">
                    <?php foreach ($payments as $p): ?>
                    <div class="list-group-item px-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-<?= $p['type'] === 'nf' ? 'info' : 'warning' ?> me-1"><?= strtoupper($p['type']) ?></span>
                                <strong class="small"><?= $p['number'] ? '#' . htmlspecialchars($p['number']) : '' ?></strong>
                                <?= $p['amount'] ? ' - R$ ' . number_format($p['amount'], 2, ',', '.') : '' ?>
                                <?php if ($p['paid']): ?>
                                <span class="badge bg-success ms-1">Pago <?= $p['paid_at'] ? date('d/m', strtotime($p['paid_at'])) : '' ?></span>
                                <?php elseif ($p['due_date']): ?>
                                <span class="badge bg-<?= strtotime($p['due_date']) < time() ? 'danger' : 'secondary' ?> ms-1">Vence <?= date('d/m', strtotime($p['due_date'])) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="d-flex gap-1">
                                <?php if ($p['file_path']): ?>
                                <a href="<?= $p['file_path'] ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></a>
                                <?php endif; ?>
                                <?php if (!$p['paid']): ?>
                                <form method="POST" action="/admin/orders/mark-paid" class="d-inline">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Marcar pago"><i class="bi bi-check"></i></button>
                                </form>
                                <?php endif; ?>
                                <?php if (\App\Core\Auth::isSuperAdmin()): ?>
                                <form method="POST" action="/admin/orders/delete-payment" class="d-inline" onsubmit="return confirm('Excluir?')">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($p['notes']): ?><small class="text-muted"><?= htmlspecialchars($p['notes']) ?></small><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Formulário de upload -->
                <form method="POST" action="/admin/orders/upload-payment" enctype="multipart/form-data">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <div class="row g-2">
                        <div class="col-6 col-md-2">
                            <select class="form-select form-select-sm" name="type" required>
                                <option value="nf">NF</option>
                                <option value="boleto">Boleto</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <input type="text" class="form-control form-control-sm" name="number" placeholder="Número">
                        </div>
                        <div class="col-6 col-md-2">
                            <input type="text" inputmode="decimal" class="form-control form-control-sm" name="amount" placeholder="Valor (R$)">
                        </div>
                        <div class="col-6 col-md-2">
                            <input type="date" class="form-control form-control-sm" name="due_date">
                        </div>
                        <div class="col-12 col-md-3">
                            <input type="file" class="form-control form-control-sm" name="file" accept=".pdf,.jpg,.jpeg,.png,.webp">
                        </div>
                        <div class="col-12 col-md-1">
                            <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-upload"></i></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Histórico -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-clock-history"></i> Histórico</div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($history as $entry): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong class="small"><?= htmlspecialchars($entry['performed_by_name'] ?? 'Sistema') ?></strong>
                                <p class="mb-0 small"><?= htmlspecialchars($entry['description']) ?></p>
                            </div>
                            <small class="text-muted text-nowrap ms-2"><?= date('d/m/Y H:i', strtotime($entry['created_at'])) ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Coluna lateral -->
    <div class="col-lg-4 order-1 order-lg-2 mb-3">
        <!-- Ações -->
        <div class="card mb-3">
            <div class="card-header">Ações</div>
            <div class="card-body d-grid gap-2">
                <?php if ($order['status'] === 'pending_quote'): ?>
                <form method="POST" action="/admin/orders/resend-quote">
                    <input type="hidden" name="id" value="<?= $order['id'] ?>">
                    <button type="submit" class="btn btn-outline-warning w-100">
                        <i class="bi bi-send"></i> Reenviar p/ Cotação
                    </button>
                </form>
                <?php endif; ?>

                <?php if ($order['status'] === 'pending_approval'): ?>
                <form method="POST" action="/admin/orders/resend-approval">
                    <input type="hidden" name="id" value="<?= $order['id'] ?>">
                    <button type="submit" class="btn btn-outline-info w-100">
                        <i class="bi bi-send"></i> Reenviar p/ Aprovação
                    </button>
                </form>
                <?php endif; ?>

                <?php if ($order['status'] === 'approved'): ?>
                <a href="/pedido/pdf/<?= $order['id'] ?>" class="btn btn-success w-100" target="_blank">
                    <i class="bi bi-file-pdf"></i> Gerar PDF
                </a>
                <a href="/pedido/xlsx/<?= $order['id'] ?>" class="btn btn-primary w-100">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Baixar Planilha
                </a>
                <?php endif; ?>

                <?php if (!in_array($order['status'], ['approved', 'cancelled'])): ?>
                <hr>
                <form method="POST" action="/admin/orders/cancel" onsubmit="return confirm('Tem certeza que deseja cancelar este pedido?')">
                    <input type="hidden" name="id" value="<?= $order['id'] ?>">
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="bi bi-x-circle"></i> Cancelar Pedido
                    </button>
                </form>
                <?php endif; ?>

                <?php if (\App\Core\Auth::isSuperAdmin()): ?>
                <hr>
                <form method="POST" action="/admin/orders/delete" onsubmit="return confirm('ATENÇÃO: Isso vai DELETAR permanentemente este pedido e todo o histórico relacionado. Esta ação NÃO pode ser desfeita. Confirma?')">
                    <input type="hidden" name="id" value="<?= $order['id'] ?>">
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="bi bi-trash"></i> Deletar Pedido
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Links públicos -->
        <div class="card mb-3">
            <div class="card-header">Links Públicos</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Link de Cotação</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" value="<?= $baseUrl ?>/pedido/cotacao/<?= $order['quote_token'] ?>" readonly id="quoteLink">
                        <button class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('quoteLink').value); this.innerHTML='<i class=\'bi bi-check\'></i>'">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="form-label small fw-bold">Link de Aprovação</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" value="<?= $baseUrl ?>/pedido/aprovacao/<?= $order['approval_token'] ?>" readonly id="approvalLink">
                        <button class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('approvalLink').value); this.innerHTML='<i class=\'bi bi-check\'></i>'">
                            <i class="bi bi-clipboard"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <a href="/admin/orders" class="btn btn-outline-secondary w-100">
            <i class="bi bi-arrow-left"></i> Voltar para Lista
        </a>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
