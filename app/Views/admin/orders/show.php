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
            <div class="card-body d-flex align-items-center gap-3 flex-wrap">
                <i class="bi <?= $statusInfo[2] ?> text-<?= $statusInfo[1] ?>" style="font-size:2rem;"></i>
                <div>
                    <h5 class="mb-0"><?= $statusInfo[0] ?></h5>
                    <small class="text-muted">Código: <strong><?= $order['code'] ?></strong></small>
                    <?php if ($order['status'] === 'approved'): ?>
                        <?php
                        $totalPayments = count($payments ?? []);
                        $paidPayments = count(array_filter($payments ?? [], fn($p) => $p['paid']));
                        $pendingPayments = $totalPayments - $paidPayments;
                        ?>
                        <?php if ($totalPayments === 0): ?>
                        <span class="badge bg-secondary ms-2">Sem NF/Boleto</span>
                        <?php elseif ($pendingPayments > 0): ?>
                        <span class="badge bg-warning ms-2"><?= $pendingPayments ?> pgto pendente</span>
                        <?php else: ?>
                        <span class="badge bg-success ms-2">Pagamentos OK</span>
                        <?php endif; ?>
                    <?php endif; ?>
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
                        <small class="text-muted d-block">Fornecedor(es) Aprovado(s)</small>
                        <?php
                        $approvedSuppliers = \App\Models\PurchaseOrderSupplier::getAllApproved($order['id']);
                        if (!empty($approvedSuppliers)):
                            $names = array_column($approvedSuppliers, 'supplier_name');
                        ?>
                        <strong><?= htmlspecialchars(implode(', ', $names)) ?></strong>
                        <?php else: ?>
                        <strong><?= htmlspecialchars($order['supplier_name'] ?? 'Pendente') ?></strong>
                        <?php endif; ?>
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
                            <th>Fornecedor</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Montar mapa de supplier_id => nome para exibir fornecedor por item
                        $supplierNamesMap = [];
                        if (!empty($orderSuppliers)) {
                            foreach ($orderSuppliers as $os) {
                                $supplierNamesMap[$os['supplier_id']] = $os['supplier_name'];
                            }
                        }
                        ?>
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
                            <td><small class="text-muted"><?= htmlspecialchars($supplierNamesMap[$item['approved_supplier_id'] ?? 0] ?? '-') ?></small></td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php if ($order['total_estimated'] > 0): ?>
                        <tr class="table-light">
                            <td colspan="8" class="text-end fw-bold">TOTAL:</td>
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
                    
                    <?php if (!empty($os['payment_method'])): ?>
                    <div class="small mb-2">
                        <?php
                        $paymentLabels = ['pix'=>'PIX','boleto'=>'Boleto','cartao'=>'Cartão','transferencia'=>'Transferência','dinheiro'=>'Dinheiro','outro'=>'Outro'];
                        ?>
                        <span class="me-3"><strong>Pagamento:</strong> <?= $paymentLabels[$os['payment_method']] ?? $os['payment_method'] ?></span>
                        <?php if (!empty($os['payment_condition'])): ?>
                        <span class="me-3"><?= htmlspecialchars($os['payment_condition']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($os['payment_first_due'])): ?>
                        <span class="me-3"><i class="bi bi-calendar"></i> 1ª parcela: <?= date('d/m/Y', strtotime($os['payment_first_due'])) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($os['payment_notes'])): ?>
                        <span class="text-muted">(<?= htmlspecialchars($os['payment_notes']) ?>)</span>
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

        <!-- Checklist de Entrega -->
        <?php if ($order['status'] === 'approved'): ?>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><i class="bi bi-clipboard-check"></i> Checklist de Entrega</span>
                <div class="d-flex gap-2 align-items-center">
                    <?php if (!empty($deliveries) && !empty($order['delivery_token'])): ?>
                    <div class="input-group input-group-sm" style="max-width:250px;">
                        <input type="text" class="form-control" value="<?= $baseUrl ?>/pedido/entrega/<?= $order['delivery_token'] ?>" readonly id="deliveryLink" style="font-size:0.65rem;">
                        <button class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('deliveryLink').value); this.innerHTML='<i class=\'bi bi-check\'></i>'" title="Copiar link público"><i class="bi bi-clipboard"></i></button>
                    </div>
                    <?php endif; ?>
                    <?php if (empty($deliveries)): ?>
                    <form method="POST" action="/admin/orders/delivery-init" class="d-inline">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-plus-circle"></i> Criar Checklist</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($deliveries)): ?>
            <div class="card-body p-0">
                <?php
                $statusLabelsDelivery = \App\Models\PurchaseOrderDelivery::$statusLabels;
                $today = date('Y-m-d');
                // Contar atrasados
                $lateCount = 0;
                foreach ($deliveries as $d) {
                    if ($d['status'] !== 'checked' && $d['status'] !== 'delivered' && $d['status'] !== 'replacement_delivered') {
                        if ($d['expected_date'] && $d['expected_date'] < $today) $lateCount++;
                        elseif ($d['status'] === 'replacement_requested' && $d['replacement_expected_date'] && $d['replacement_expected_date'] < $today) $lateCount++;
                    }
                }
                // Agrupar por fornecedor
                $deliveriesBySupplier = [];
                foreach ($deliveries as $d) {
                    $key = $d['supplier_name'] ?? 'Sem fornecedor';
                    $deliveriesBySupplier[$key][] = $d;
                }
                ?>
                <?php if ($lateCount > 0): ?>
                <div class="px-3 py-2 bg-danger bg-opacity-10 border-bottom">
                    <strong class="small text-danger"><i class="bi bi-exclamation-triangle-fill"></i> <?= $lateCount ?> item(ns) em atraso</strong>
                </div>
                <?php endif; ?>
                <?php foreach ($deliveriesBySupplier as $supplierName => $supplierDeliveries): ?>
                <div class="border-bottom">
                    <div class="px-3 py-2 bg-light d-flex justify-content-between align-items-center">
                        <strong class="small"><i class="bi bi-building"></i> <?= htmlspecialchars($supplierName) ?></strong>
                        <div class="d-flex align-items-center gap-2">
                            <label class="small text-muted mb-0">Entrega prevista:</label>
                            <input type="date" class="form-control form-control-sm" style="width:140px;"
                                value="<?= $supplierDeliveries[0]['expected_date'] ?? '' ?>"
                                onchange="setExpectedDate(<?= $order['id'] ?>, <?= $supplierDeliveries[0]['supplier_id'] ?? 0 ?>, 0, this.value)">
                        </div>
                    </div>
                    <?php foreach ($supplierDeliveries as $d): ?>
                    <?php
                    $si = $statusLabelsDelivery[$d['status']] ?? ['?', 'secondary', 'bi-question'];
                    $isLate = false;
                    if ($d['status'] !== 'checked' && $d['status'] !== 'delivered' && $d['status'] !== 'replacement_delivered') {
                        if ($d['expected_date'] && $d['expected_date'] < $today) $isLate = true;
                        if ($d['status'] === 'replacement_requested' && $d['replacement_expected_date'] && $d['replacement_expected_date'] < $today) $isLate = true;
                    }
                    ?>
                    <div class="px-3 py-2 border-top <?= $isLate ? 'bg-danger bg-opacity-10' : '' ?>" id="delivery-<?= $d['id'] ?>">
                        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-<?= $si[1] ?>" style="font-size:0.65rem;"><i class="bi <?= $si[2] ?>"></i> <?= $si[0] ?></span>
                                    <?php if ($isLate): ?>
                                    <span class="badge bg-danger" style="font-size:0.6rem;"><i class="bi bi-clock"></i> ATRASADO</span>
                                    <?php endif; ?>
                                    <strong class="small"><?= htmlspecialchars($d['material_name']) ?></strong>
                                </div>
                                <div class="d-flex flex-wrap gap-2" style="font-size:0.7rem; color:#6c757d;">
                                    <span>Qtd: <?= number_format($d['quantity'], $d['quantity'] == (int)$d['quantity'] ? 0 : 2) ?> <?= htmlspecialchars($d['unit'] ?? '') ?></span>
                                    <?php if ($d['expected_date']): ?>
                                    <span><i class="bi bi-calendar"></i> Prev: <?= date('d/m/Y', strtotime($d['expected_date'])) ?></span>
                                    <?php endif; ?>
                                    <?php if ($d['delivered_at']): ?>
                                    <span class="text-success"><i class="bi bi-check"></i> Entregue: <?= date('d/m/Y H:i', strtotime($d['delivered_at'])) ?></span>
                                    <?php endif; ?>
                                    <?php if ($d['checked_by']): ?>
                                    <span class="text-success"><i class="bi bi-person-check"></i> Por: <?= htmlspecialchars($d['checked_by']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($d['divergence_notes']): ?>
                                    <span class="text-danger"><i class="bi bi-exclamation"></i> <?= htmlspecialchars($d['divergence_notes']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($d['replacement_requested_at']): ?>
                                    <span class="text-warning"><i class="bi bi-arrow-repeat"></i> Troca: <?= date('d/m/Y', strtotime($d['replacement_requested_at'])) ?></span>
                                    <?php endif; ?>
                                    <?php if ($d['replacement_expected_date']): ?>
                                    <span>Nova entrega: <?= date('d/m/Y', strtotime($d['replacement_expected_date'])) ?></span>
                                    <?php endif; ?>
                                    <?php if ($d['notes']): ?>
                                    <span><i class="bi bi-sticky"></i> <?= htmlspecialchars($d['notes']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="d-flex gap-1 flex-shrink-0">
                                <?php if ($d['status'] === 'pending'): ?>
                                <button class="btn btn-sm btn-outline-primary" title="Marcar Entregue" onclick="deliveryAction(<?= $d['id'] ?>, 'mark_delivered')"><i class="bi bi-box-seam"></i></button>
                                <?php elseif ($d['status'] === 'delivered'): ?>
                                <button class="btn btn-sm btn-outline-success" title="Conferido OK" onclick="deliveryAction(<?= $d['id'] ?>, 'mark_checked')"><i class="bi bi-check-lg"></i></button>
                                <button class="btn btn-sm btn-outline-danger" title="Divergência" onclick="showDivergenceModal(<?= $d['id'] ?>)"><i class="bi bi-exclamation-triangle"></i></button>
                                <?php elseif ($d['status'] === 'divergence'): ?>
                                <button class="btn btn-sm btn-outline-warning" title="Solicitar Troca" onclick="showReplacementModal(<?= $d['id'] ?>)"><i class="bi bi-arrow-repeat"></i></button>
                                <?php elseif ($d['status'] === 'replacement_requested'): ?>
                                <button class="btn btn-sm btn-outline-success" title="Troca Entregue" onclick="deliveryAction(<?= $d['id'] ?>, 'mark_replacement_delivered')"><i class="bi bi-check-all"></i></button>
                                <?php endif; ?>
                                <?php if (!in_array($d['status'], ['pending'])): ?>
                                <button class="btn btn-sm btn-outline-secondary" title="Resetar" onclick="if(confirm('Resetar para pendente?'))deliveryAction(<?= $d['id'] ?>, 'reset')"><i class="bi bi-arrow-counterclockwise"></i></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="card-body text-center text-muted py-4">
                <i class="bi bi-clipboard-check" style="font-size:2rem;"></i>
                <p class="mb-0 mt-2 small">Clique em "Criar Checklist" para iniciar o controle de entregas.</p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Modal Divergência -->
        <div class="modal fade" id="divergenceModal" tabindex="-1">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header py-2"><h6 class="modal-title">Registrar Divergência</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <input type="hidden" id="divergenceId">
                        <textarea class="form-control" id="divergenceNotes" rows="3" placeholder="Descreva o problema..."></textarea>
                    </div>
                    <div class="modal-footer py-2">
                        <button class="btn btn-sm btn-danger" onclick="submitDivergence()">Registrar</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Troca -->
        <div class="modal fade" id="replacementModal" tabindex="-1">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <div class="modal-header py-2"><h6 class="modal-title">Solicitar Troca</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <input type="hidden" id="replacementId">
                        <div class="mb-2">
                            <label class="form-label small">Previsão da nova entrega</label>
                            <input type="date" class="form-control form-control-sm" id="replacementDate">
                        </div>
                        <div>
                            <label class="form-label small">Observações</label>
                            <textarea class="form-control form-control-sm" id="replacementNotes" rows="2" placeholder="Detalhes da troca..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button class="btn btn-sm btn-warning" onclick="submitReplacement()">Solicitar Troca</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Itens Sobressalentes -->
        <?php if ($order['status'] === 'approved'): ?>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-bag-plus"></i> Itens Sobressalentes</span>
                <a href="/admin/orders/spare-items" class="btn btn-sm btn-outline-secondary"><i class="bi bi-gear"></i> Gerenciar</a>
            </div>
            <?php if (!empty($spareItems)): ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0" style="font-size:0.8rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Item</th>
                            <th class="text-center">Qtd</th>
                            <th class="text-end">Valor</th>
                            <th>Onde</th>
                            <th>Por</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $spareTotal = 0; ?>
                        <?php foreach ($spareItems as $si): ?>
                        <?php $spareTotal += $si['total_price']; ?>
                        <tr>
                            <td><?= $si['purchased_at'] ? date('d/m', strtotime($si['purchased_at'])) : '-' ?></td>
                            <td><strong><?= htmlspecialchars($si['description']) ?></strong></td>
                            <td class="text-center"><?= number_format($si['quantity'], $si['quantity'] == (int)$si['quantity'] ? 0 : 2) ?></td>
                            <td class="text-end">R$ <?= number_format($si['total_price'], 2, ',', '.') ?></td>
                            <td><?= htmlspecialchars($si['supplier_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($si['purchased_by'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-warning">
                            <td colspan="3" class="text-end fw-bold">Total sobressalentes:</td>
                            <td class="text-end fw-bold">R$ <?= number_format($spareTotal, 2, ',', '.') ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="card-body text-center text-muted py-3 small">
                Nenhum item sobressalente vinculado a este pedido.
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Histórico de Notificações -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center" role="button" data-bs-toggle="collapse" data-bs-target="#notificationsCollapse">
                <span><i class="bi bi-bell"></i> Histórico de Notificações</span>
                <?php
                $notifications = \App\Models\NotificationQueue::getByOrder($order['id']);
                $nSent = count(array_filter($notifications, fn($n) => $n['status'] === 'sent'));
                $nFailed = count(array_filter($notifications, fn($n) => $n['status'] === 'failed'));
                $nPending = count(array_filter($notifications, fn($n) => in_array($n['status'], ['pending', 'processing'])));
                ?>
                <span>
                    <?php if ($nSent): ?><span class="badge bg-success"><?= $nSent ?> enviadas</span><?php endif; ?>
                    <?php if ($nFailed): ?><span class="badge bg-danger"><?= $nFailed ?> com erro</span><?php endif; ?>
                    <?php if ($nPending): ?><span class="badge bg-warning"><?= $nPending ?> pendentes</span><?php endif; ?>
                </span>
            </div>
            <div class="collapse" id="notificationsCollapse">
                <!-- Botões de reenvio por fase -->
                <div class="card-body border-bottom py-2">
                    <small class="text-muted d-block mb-2">Reenviar notificações (gera e envia novamente):</small>
                    <div class="d-flex flex-wrap gap-1">
                        <?php if (in_array($order['status'], ['pending_quote', 'quoted', 'pending_approval', 'approved'])): ?>
                        <button class="btn btn-sm btn-outline-warning" onclick="resendPhase(<?= $order['id'] ?>, 'quote_requested')"><i class="bi bi-arrow-repeat"></i> Cotação</button>
                        <?php endif; ?>
                        <?php if (in_array($order['status'], ['pending_approval', 'approved'])): ?>
                        <button class="btn btn-sm btn-outline-info" onclick="resendPhase(<?= $order['id'] ?>, 'approval_requested')"><i class="bi bi-arrow-repeat"></i> Aprovação</button>
                        <?php endif; ?>
                        <?php if ($order['status'] === 'approved'): ?>
                        <button class="btn btn-sm btn-outline-success" onclick="resendPhase(<?= $order['id'] ?>, 'order_approved')"><i class="bi bi-arrow-repeat"></i> Conclusão</button>
                        <?php endif; ?>
                        <?php if ($order['status'] === 'rejected'): ?>
                        <button class="btn btn-sm btn-outline-danger" onclick="resendPhase(<?= $order['id'] ?>, 'order_rejected')"><i class="bi bi-arrow-repeat"></i> Rejeição</button>
                        <?php endif; ?>
                        <?php if (!empty($order['delivery_token'])): ?>
                        <button class="btn btn-sm btn-outline-dark" onclick="resendPhase(<?= $order['id'] ?>, 'delivery_ready')"><i class="bi bi-arrow-repeat"></i> Entrega</button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (empty($notifications)): ?>
                <div class="card-body text-center text-muted py-3 small">Nenhuma notificação registrada para este pedido.<br><small>Use os botões acima para enviar/reenviar.</small></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0" style="font-size:0.75rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Fase</th>
                                <th>Destino</th>
                                <th>Status</th>
                                <th>Erro</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $eventLabels = [
                                'quote_requested' => 'Cotação',
                                'approval_requested' => 'Aprovação',
                                'order_approved' => 'Aprovado',
                                'order_rejected' => 'Rejeitado',
                                'payment_uploaded' => 'Pagamento',
                                'delivery_ready' => 'Entrega',
                                'delivery_checklist_ready' => 'Entrega',
                                'spare_item' => 'Sobressalente',
                            ];
                            ?>
                            <?php foreach ($notifications as $n): ?>
                            <tr class="<?= $n['status'] === 'failed' ? 'table-danger' : '' ?>" id="notif-row-<?= $n['id'] ?>">
                                <td class="text-nowrap"><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></td>
                                <td>
                                    <?php if ($n['type'] === 'email'): ?>
                                    <span class="badge bg-info" style="font-size:0.6rem;"><i class="bi bi-envelope"></i> E-mail</span>
                                    <?php else: ?>
                                    <span class="badge bg-dark" style="font-size:0.6rem;"><i class="bi bi-broadcast"></i> Webhook</span>
                                    <?php endif; ?>
                                </td>
                                <td><small><?= $eventLabels[$n['event_type'] ?? ''] ?? ($n['event_type'] ?? '-') ?></small></td>
                                <td>
                                    <?php if ($n['type'] === 'email'): ?>
                                    <small><?= htmlspecialchars($n['to_email'] ?? '') ?></small>
                                    <?php else: ?>
                                    <small><?= htmlspecialchars($n['recipient_name'] ?? 'Webhook') ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($n['status'] === 'sent'): ?>
                                    <span class="badge bg-success" style="font-size:0.6rem;">Enviado<?= $n['sent_at'] ? ' ' . date('H:i', strtotime($n['sent_at'])) : '' ?></span>
                                    <?php elseif ($n['status'] === 'failed'): ?>
                                    <span class="badge bg-danger" style="font-size:0.6rem;">Falhou (<?= $n['attempts'] ?>x)</span>
                                    <?php elseif ($n['status'] === 'processing'): ?>
                                    <span class="badge bg-warning" style="font-size:0.6rem;">Processando...</span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary" style="font-size:0.6rem;">Pendente</span>
                                    <?php endif; ?>
                                </td>
                                <td><small class="text-danger"><?= htmlspecialchars($n['last_error'] ?? '') ?></small></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-secondary p-0 px-1" onclick="resendSingle(<?= $n['id'] ?>)" title="Reenviar esta"><i class="bi bi-arrow-repeat"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

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

<script>
function deliveryAction(id, action, extraData) {
    const fd = new FormData();
    fd.append('id', id);
    fd.append('delivery_action', action);
    fd.append('performed_by', '<?= htmlspecialchars(\App\Core\Auth::user()['name'] ?? 'Admin') ?>');
    if (extraData) {
        for (const k in extraData) fd.append(k, extraData[k]);
    }
    fetch('/admin/orders/delivery-update', {method:'POST', body:fd})
        .then(r => r.json())
        .then(d => { if(d.success) location.reload(); else alert(d.error || 'Erro'); })
        .catch(() => alert('Erro de conexão'));
}

function setExpectedDate(orderId, supplierId, itemId, date) {
    const fd = new FormData();
    fd.append('order_id', orderId);
    fd.append('supplier_id', supplierId);
    fd.append('item_id', itemId);
    fd.append('expected_date', date);
    fetch('/admin/orders/delivery-expected-date', {method:'POST', body:fd})
        .then(r => r.json())
        .then(d => { if(d.success) location.reload(); else alert(d.error || 'Erro'); })
        .catch(() => alert('Erro de conexão'));
}

function showDivergenceModal(id) {
    document.getElementById('divergenceId').value = id;
    document.getElementById('divergenceNotes').value = '';
    new bootstrap.Modal(document.getElementById('divergenceModal')).show();
}

function submitDivergence() {
    const id = document.getElementById('divergenceId').value;
    const notes = document.getElementById('divergenceNotes').value;
    if (!notes.trim()) { alert('Descreva o problema.'); return; }
    deliveryAction(id, 'mark_divergence', {divergence_notes: notes});
}

function showReplacementModal(id) {
    document.getElementById('replacementId').value = id;
    document.getElementById('replacementDate').value = '';
    document.getElementById('replacementNotes').value = '';
    new bootstrap.Modal(document.getElementById('replacementModal')).show();
}

function submitReplacement() {
    const id = document.getElementById('replacementId').value;
    const date = document.getElementById('replacementDate').value;
    const notes = document.getElementById('replacementNotes').value;
    deliveryAction(id, 'request_replacement', {replacement_expected_date: date, replacement_notes: notes});
}

function resendSingle(id) {
    if (!confirm('Reenviar esta notificação?')) return;
    fetch('/admin/orders/resend-notification', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + id
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            alert('Notificação reenviada!');
            location.reload();
        } else {
            alert(d.error || 'Erro ao reenviar.');
        }
    })
    .catch(() => alert('Erro de conexão'));
}

function resendPhase(orderId, phase) {
    const labels = {quote_requested:'Cotação',approval_requested:'Aprovação',order_approved:'Conclusão',order_rejected:'Rejeição',delivery_ready:'Entrega'};
    if (!confirm('Reenviar todas as notificações de "' + (labels[phase] || phase) + '" para este pedido?')) return;
    fetch('/admin/orders/resend-all-phase', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'order_id=' + orderId + '&phase=' + phase
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            alert(d.message || 'Notificações reenviadas!');
            location.reload();
        } else {
            alert(d.error || 'Erro ao reenviar.');
        }
    })
    .catch(() => alert('Erro de conexão'));
}
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
