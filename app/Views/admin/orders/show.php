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

<div class="mb-3">
    <a href="#" onclick="history.back(); return false;" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

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
                    <?php if (($order['order_type'] ?? 'material') === 'service'): ?>
                    <span class="badge bg-dark ms-2"><i class="bi bi-wrench"></i> Serviço</span>
                    <?php endif; ?>
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
                    <a href="/pedido/pdf-especificacao/<?= $order['id'] ?>" class="btn btn-sm btn-outline-success" target="_blank" title="PDF agrupado por especificação">
                        <i class="bi bi-list-nested"></i> PDF Espec.
                    </a>
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
                    <?php if (!empty($order['construction_site_name'])): ?>
                    <div class="col-sm-6 mb-2">
                        <small class="text-muted d-block">Obra</small>
                        <strong>
                            <a href="/admin/obras/edit/<?= $order['construction_site_id'] ?>" class="text-decoration-none">
                                <i class="bi bi-buildings"></i> <?= htmlspecialchars($order['construction_site_code'] . ' - ' . $order['construction_site_name']) ?>
                            </a>
                        </strong>
                        <?php if (!empty($order['construction_site_client'])): ?>
                        <br><small class="text-muted">Cliente: <?= htmlspecialchars($order['construction_site_client']) ?></small>
                        <?php endif; ?>
                        <?php if (!empty($order['construction_site_address'])): ?>
                        <br><small class="text-muted"><?= htmlspecialchars($order['construction_site_address']) ?><?= !empty($order['construction_site_city']) ? ' - ' . $order['construction_site_city'] . '/' . ($order['construction_site_state'] ?? '') : '' ?></small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div class="col-sm-6 mb-2">
                        <small class="text-muted d-block">Data de Criação</small>
                        <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                    </div>
                    <?php if ($order['total_estimated'] > 0): ?>
                    <div class="col-sm-6 mb-2">
                        <small class="text-muted d-block">Valor Total</small>
                        <?php
                        // Usar subtotal_final do fornecedor aprovado se disponível (mais preciso)
                        $displayTotal = $order['total_estimated'];
                        if (!empty($orderSuppliers)) {
                            foreach ($orderSuppliers as $os) {
                                if ($os['approved'] && $os['subtotal_final'] > 0) {
                                    $displayTotal = $os['subtotal_final'];
                                    break;
                                }
                            }
                        }
                        ?>
                        <strong class="text-success fs-5">R$ <?= number_format($displayTotal, 2, ',', '.') ?></strong>
                        <?php
                        $nfTotal = 0;
                        if (!empty($payments)) {
                            foreach ($payments as $p) {
                                $nfTotal += (float)($p['amount'] ?? 0);
                            }
                        }
                        ?>
                        <?php if ($nfTotal > 0 && $nfTotal != $displayTotal): ?>
                        <br><small class="text-muted"><i class="bi bi-receipt"></i> NF: <strong>R$ <?= number_format($nfTotal, 2, ',', '.') ?></strong></small>
                        <?php endif; ?>
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
                <small class="text-muted d-block"><i class="bi bi-chat-text"></i> Observações do Pedido</small>
                <p class="mb-0"><?= nl2br(htmlspecialchars($order['description'])) ?></p>
                <?php endif; ?>
                <?php if (!empty($order['quote_notes'])): ?>
                <hr>
                <small class="text-muted d-block"><i class="bi bi-pencil-square"></i> Observações da Cotação</small>
                <p class="mb-0"><?= nl2br(htmlspecialchars($order['quote_notes'])) ?></p>
                <?php endif; ?>
                <?php if (!empty($order['approval_notes'])): ?>
                <hr>
                <small class="text-muted d-block"><i class="bi bi-check-circle"></i> Observações da Aprovação</small>
                <p class="mb-0"><?= nl2br(htmlspecialchars($order['approval_notes'])) ?></p>
                <?php endif; ?>
                <?php if (!empty($order['financial_notes'])): ?>
                <hr>
                <small class="text-muted d-block"><i class="bi bi-cash-stack"></i> Observações do Financeiro</small>
                <p class="mb-0"><?= nl2br(htmlspecialchars($order['financial_notes'])) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Itens -->
        <div class="card mb-3">
            <div class="card-header">Itens do Pedido</div>
            <?php
            // Só exibir colunas de preço se algum item realmente tem unit_price preenchido
            $hasItemPrices = !empty(array_filter($items, fn($it) => !empty($it['unit_price'])));
            $showPriceColumns = $order['total_estimated'] > 0 && $hasItemPrices;
            ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Material</th>
                            <th>Espec.</th>
                            <th>Class.</th>
                            <th class="text-center">Qtd</th>
                            <th>Origem</th>
                            <?php if ($showPriceColumns): ?>
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
                        <tr class="<?= !empty($item['source_type']) && $item['source_type'] !== 'purchase' ? 'table-success' : (!empty($item['already_purchased']) ? 'table-info' : '') ?>">
                            <td><?= $i + 1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($item['material_name']) ?></strong>
                                <?php if (!empty($item['already_purchased'])): ?>
                                <br><span class="badge bg-info" style="font-size:0.6rem;"><i class="bi bi-bag-check"></i> Já comprado <?= !empty($item['already_purchased_qty']) ? number_format($item['already_purchased_qty'], $item['already_purchased_qty'] == (int)$item['already_purchased_qty'] ? 0 : 2) : '' ?><?= $item['already_purchased_price'] ? ' — R$ ' . number_format($item['already_purchased_price'], 2, ',', '.') : '' ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($item['specification'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($item['classification'] ?? '-') ?></td>
                            <td class="text-center">
                                <?= number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2) ?>
                                <?php if (!empty($item['already_purchased']) && !empty($item['already_purchased_qty']) && (float)$item['already_purchased_qty'] < (float)$item['quantity']): ?>
                                <br><small class="text-muted" style="font-size:0.65rem;">Cotado: <?= number_format((float)$item['quantity'] - (float)$item['already_purchased_qty'], ((float)$item['quantity'] - (float)$item['already_purchased_qty']) == (int)((float)$item['quantity'] - (float)$item['already_purchased_qty']) ? 0 : 2) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($item['source_type']) && $item['source_type'] !== 'purchase'): ?>
                                    <?php if ($item['source_type'] === 'stock_transfer'): ?>
                                        <span class="badge bg-primary" style="font-size:0.65rem;"><i class="bi bi-arrow-left-right"></i> Transferência</span>
                                    <?php else: ?>
                                        <span class="badge bg-success" style="font-size:0.65rem;"><i class="bi bi-box-seam"></i> Estoque</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark" style="font-size:0.65rem;"><i class="bi bi-cart"></i> Compra</span>
                                <?php endif; ?>
                            </td>
                            <?php if ($showPriceColumns): ?>
                            <td class="text-end"><?= $item['unit_price'] ? 'R$ ' . number_format($item['unit_price'], 2, ',', '.') : '-' ?></td>
                            <td class="text-end fw-bold"><?= $item['total_price'] ? 'R$ ' . number_format($item['total_price'], 2, ',', '.') : '-' ?></td>
                            <td><small class="text-muted"><?= htmlspecialchars($supplierNamesMap[$item['approved_supplier_id'] ?? 0] ?? '-') ?></small></td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                        <?php if ($showPriceColumns): ?>
                        <tr class="table-light">
                            <td colspan="7" class="text-end fw-bold">TOTAL:</td>
                            <td class="text-end fw-bold text-success">R$ <?= number_format($displayTotal, 2, ',', '.') ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php
                        // Calcular total de estoque (itens de transferência com valor)
                        $totalEstoque = 0;
                        foreach ($items as $it) {
                            if (!empty($it['source_type']) && $it['source_type'] !== 'purchase' && !empty($it['total_price'])) {
                                $totalEstoque += (float) $it['total_price'];
                            }
                        }
                        ?>
                        <?php if ($totalEstoque > 0): ?>
                        <tr class="table-light">
                            <td colspan="7" class="text-end">Total Estoque:</td>
                            <td class="text-end fw-bold" style="color:#6f42c1;">R$ <?= number_format($totalEstoque, 2, ',', '.') ?></td>
                        </tr>
                        <?php if ($displayTotal > 0): ?>
                        <tr class="table-light">
                            <td colspan="7" class="text-end fw-bold">TOTAL GERAL:</td>
                            <td class="text-end fw-bold" style="color:#3a3b4e;">R$ <?= number_format($displayTotal + $totalEstoque, 2, ',', '.') ?></td>
                        </tr>
                        <?php endif; ?>
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
                <div class="p-3 <?= $os['approved'] ? 'bg-success bg-opacity-10' : '' ?> border-bottom">
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

                    <!-- Dados do fornecedor -->
                    <?php if (!empty($os['cnpj']) || !empty($os['email']) || !empty($os['phone'])): ?>
                    <div class="small mb-2 text-muted">
                        <?php if ($os['cnpj']): ?><span class="me-3"><i class="bi bi-building"></i> CNPJ: <?= htmlspecialchars($os['cnpj']) ?></span><?php endif; ?>
                        <?php if ($os['phone']): ?><span class="me-3"><i class="bi bi-telephone"></i> <?= htmlspecialchars($os['phone']) ?></span><?php endif; ?>
                        <?php if ($os['email']): ?><span class="me-3"><i class="bi bi-envelope"></i> <?= htmlspecialchars($os['email']) ?></span><?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($os['vendor_name'] || $os['delivery_days']): ?>
                    <div class="small mb-2">
                        <?php if ($os['vendor_name']): ?>
                        <span class="me-3"><strong>Vendedor:</strong> <?= htmlspecialchars($os['vendor_name']) ?></span>
                        <?php endif; ?>
                        <?php if ($os['vendor_phone']): ?>
                        <span class="me-3"><i class="bi bi-telephone-fill"></i> <?= htmlspecialchars($os['vendor_phone']) ?></span>
                        <?php endif; ?>
                        <?php if ($os['vendor_email']): ?>
                        <span class="me-3"><i class="bi bi-envelope-fill"></i> <?= htmlspecialchars($os['vendor_email']) ?></span>
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

        <!-- PDFs e Materiais de Serviço -->
        <?php if (($order['order_type'] ?? 'material') === 'service' && !empty($orderSuppliers)): ?>
        <?php
        $supplierPdfs = \App\Models\PurchaseOrderSupplierPdf::getByOrder($order['id']);
        $supplierMaterials = \App\Models\PurchaseOrderSupplierMaterial::getByOrder($order['id']);
        ?>
        <?php if (!empty($supplierPdfs) || !empty($supplierMaterials)): ?>
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-file-earmark-pdf text-danger"></i> Materiais de Serviço (PDFs dos Fornecedores)</div>
            <div class="card-body p-0">
                <?php
                // Agrupar por fornecedor
                $pdfsBySupplier = [];
                foreach ($supplierPdfs as $pdf) {
                    $pdfsBySupplier[$pdf['supplier_id']][] = $pdf;
                }
                $matsBySupplier = [];
                foreach ($supplierMaterials as $mat) {
                    $matsBySupplier[$mat['supplier_id']][] = $mat;
                }
                $allSupplierIds = array_unique(array_merge(array_keys($pdfsBySupplier), array_keys($matsBySupplier)));
                ?>
                <?php foreach ($allSupplierIds as $supId): ?>
                <?php
                $isApprovedSup = false;
                $supName = '-';
                foreach ($orderSuppliers as $os) {
                    if ($os['supplier_id'] == $supId) {
                        $supName = $os['supplier_name'];
                        if (!empty($os['approved'])) $isApprovedSup = true;
                        break;
                    }
                }
                ?>
                <div class="p-3 border-bottom <?= $isApprovedSup ? 'bg-success bg-opacity-10' : '' ?>">
                    <h6 class="mb-2">
                        <i class="bi bi-building"></i>
                        <?= htmlspecialchars($supName) ?>
                        <?php if ($isApprovedSup): ?>
                        <span class="badge bg-success ms-1">Aprovado</span>
                        <?php endif; ?>
                    </h6>

                    <!-- PDFs -->
                    <?php if (!empty($pdfsBySupplier[$supId])): ?>
                    <div class="mb-2">
                        <?php foreach ($pdfsBySupplier[$supId] as $pdf): ?>
                        <a href="<?= htmlspecialchars($pdf['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-danger me-1 mb-1">
                            <i class="bi bi-file-pdf"></i> <?= htmlspecialchars($pdf['original_name'] ?? 'PDF') ?>
                        </a>
                        <small class="text-muted">(<?= $pdf['uploaded_at'] ? date('d/m/Y H:i', strtotime($pdf['uploaded_at'])) : '' ?>)</small>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Materiais extraídos -->
                    <?php if (!empty($matsBySupplier[$supId])): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" style="font-size:0.8rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Material</th>
                                    <th class="text-center">Qtd</th>
                                    <th class="text-end">Unit.</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $subTotal = 0; ?>
                                <?php foreach ($matsBySupplier[$supId] as $mat): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($mat['material_name']) ?></strong>
                                        <?php if ($mat['material_id']): ?>
                                        <i class="bi bi-link-45deg text-success" title="Vinculado ao cadastro"></i>
                                        <?php endif; ?>
                                        <?php if ($mat['specification']): ?>
                                        <small class="text-muted d-block"><?= htmlspecialchars($mat['specification']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?= $mat['quantity'] ? number_format($mat['quantity'], $mat['quantity'] == (int)$mat['quantity'] ? 0 : 3) : '-' ?></td>
                                    <td class="text-end"><?= $mat['unit_price'] ? 'R$ ' . number_format($mat['unit_price'], 2, ',', '.') : '-' ?></td>
                                    <td class="text-end fw-bold"><?= $mat['total_price'] ? 'R$ ' . number_format($mat['total_price'], 2, ',', '.') : '-' ?></td>
                                </tr>
                                <?php $subTotal += (float)($mat['total_price'] ?? 0); ?>
                                <?php endforeach; ?>
                                <?php if ($subTotal > 0): ?>
                                <tr class="table-light">
                                    <td colspan="3" class="text-end fw-bold">Total materiais:</td>
                                    <td class="text-end fw-bold text-success">R$ <?= number_format($subTotal, 2, ',', '.') ?></td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
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
                                <span class="badge bg-<?= $p['type'] === 'nf' ? 'info' : ($p['type'] === 'boleto' ? 'warning' : 'primary') ?> me-1"><?= strtoupper($p['type']) ?></span>
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
                <form method="POST" action="/admin/orders/upload-payment" enctype="multipart/form-data" id="paymentForm">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <?php
                    // Pegar CNPJ do fornecedor aprovado para validação
                    $approvedCnpj = '';
                    $approvedSupplierName = '';
                    if (!empty($orderSuppliers)) {
                        foreach ($orderSuppliers as $os) {
                            if ($os['approved'] && !empty($os['cnpj'])) {
                                $approvedCnpj = $os['cnpj'];
                                $approvedSupplierName = $os['supplier_name'];
                                break;
                            }
                        }
                    }
                    ?>
                    <input type="hidden" id="approvedCnpj" value="<?= htmlspecialchars(preg_replace('/\D/', '', $approvedCnpj)) ?>">
                    <input type="hidden" id="approvedSupplierName" value="<?= htmlspecialchars($approvedSupplierName) ?>">
                    <div class="row g-2">
                        <div class="col-6 col-md-2">
                            <select class="form-select form-select-sm" name="type" required>
                                <option value="nf">NF</option>
                                <option value="boleto">Boleto</option>
                                <option value="pedido">Pedido</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <input type="text" class="form-control form-control-sm" name="number" placeholder="CPF/CNPJ" id="cpfCnpjInput" maxlength="18">
                        </div>
                        <div class="col-6 col-md-2">
                            <input type="text" inputmode="decimal" class="form-control form-control-sm" name="amount" placeholder="Valor (R$)">
                        </div>
                        <div class="col-6 col-md-2">
                            <input type="date" class="form-control form-control-sm" name="due_date">
                        </div>
                        <div class="col-12 col-md-3">
                            <input type="file" class="form-control form-control-sm" name="file" id="paymentFile" accept=".pdf,.jpg,.jpeg,.png,.webp">
                        </div>
                        <div class="col-12 col-md-1">
                            <button type="submit" class="btn btn-sm btn-primary w-100" id="paymentSubmitBtn"><i class="bi bi-upload"></i></button>
                        </div>
                    </div>
                    <div id="cnpjValidationResult" class="mt-2" style="display:none;"></div>
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
                    <form method="POST" action="/admin/orders/delivery-init" class="d-inline">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <?php if (empty($deliveries)): ?>
                        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-plus-circle"></i> Criar Checklist</button>
                        <?php else: ?>
                        <button type="submit" class="btn btn-sm btn-outline-warning" onclick="return confirm('Recriar checklist? Isso vai resetar todos os status de entrega.')"><i class="bi bi-arrow-repeat"></i> Recriar</button>
                        <?php endif; ?>
                    </form>
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
                                    <span>Qtd: <?= number_format($d['quantity'], $d['quantity'] == (int)$d['quantity'] ? 0 : 2) ?></span>
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
                            <th>Justificativa</th>
                            <th>Por</th>
                            <th></th>
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
                            <td><small><?= htmlspecialchars($si['justification'] ?? $si['notes'] ?? '-') ?></small></td>
                            <td>
                                <?= htmlspecialchars($si['purchased_by'] ?? '-') ?>
                                <?php if (!empty($si['receipt_path'])): ?>
                                <a href="<?= $si['receipt_path'] ?>" target="_blank" class="ms-1" title="Ver comprovante"><i class="bi bi-paperclip text-primary"></i></a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" action="/admin/orders/spare-items/delete" class="d-inline" onsubmit="return confirm('Excluir?')">
                                    <input type="hidden" name="id" value="<?= $si['id'] ?>">
                                    <input type="hidden" name="redirect" value="/admin/orders/show/<?= $order['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger p-0 px-1"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-warning">
                            <td colspan="3" class="text-end fw-bold">Total:</td>
                            <td class="text-end fw-bold">R$ <?= number_format($spareTotal, 2, ',', '.') ?></td>
                            <td colspan="4"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            <!-- Formulário inline para adicionar -->
            <div class="card-body border-top">
                <form method="POST" action="/admin/orders/spare-items/add" id="spareItemForm" enctype="multipart/form-data">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <input type="hidden" name="redirect" value="/admin/orders/show/<?= $order['id'] ?>">
                    <div class="row g-2">
                        <div class="col-12 col-md-4" id="spare-mat-wrapper">
                            <input type="hidden" name="description" id="spare-description">
                            <select id="spare-mat-select" style="display:none;">
                                <option value="">-- Selecione ou digite --</option>
<?php foreach ($materials as $m): ?><option value="<?= htmlspecialchars($m['name'] . ($m['classification'] ? ' (' . $m['classification'] . ')' : '')) ?>" data-unit="<?= htmlspecialchars($m['unit_abbr'] ?? $m['unit_name'] ?? '') ?>"><?= htmlspecialchars($m['name'] . ($m['classification'] ? ' - ' . $m['classification'] : '') . ($m['specification'] ? ' (' . $m['specification'] . ')' : '')) ?></option>
<?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-4 col-md-1">
                            <input type="text" class="form-control form-control-sm" name="quantity" value="1" placeholder="Qtd" inputmode="decimal">
                        </div>
                        <div class="col-4 col-md-1">
                            <input type="text" class="form-control form-control-sm" name="unit" id="spare-unit" placeholder="un">
                        </div>
                        <div class="col-4 col-md-2">
                            <input type="text" class="form-control form-control-sm" name="unit_price" required placeholder="Preço R$" inputmode="decimal">
                        </div>
                        <div class="col-6 col-md-2">
                            <input type="text" class="form-control form-control-sm" name="supplier_name" placeholder="Onde comprou">
                        </div>
                        <div class="col-6 col-md-2">
                            <select class="form-select form-select-sm" name="payment_method">
                                <option value="">Pgto</option>
                                <option value="pix">PIX</option>
                                <option value="cartao">Cartão</option>
                                <option value="dinheiro">Dinheiro</option>
                                <option value="boleto">Boleto</option>
                                <option value="transferencia">Transf.</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-6 col-md-2">
                            <input type="text" class="form-control form-control-sm" name="purchased_by" value="<?= htmlspecialchars($user['name'] ?? '') ?>" placeholder="Comprado por">
                        </div>
                        <div class="col-6 col-md-2">
                            <input type="date" class="form-control form-control-sm" name="purchased_at" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12 col-md-3">
                            <input type="text" class="form-control form-control-sm" name="justification" required placeholder="Justificativa (obrigatório) *">
                        </div>
                        <div class="col-12 col-md-3">
                            <input type="file" class="form-control form-control-sm" name="receipt" accept=".pdf,.jpg,.jpeg,.png,.webp">
                            <small class="text-muted" style="font-size:0.65rem;">Comprovante/foto (opcional)</small>
                        </div>
                        <div class="col-12 col-md-2">
                            <button type="submit" class="btn btn-sm btn-warning w-100"><i class="bi bi-plus-lg"></i> Adicionar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Conversas (Aprovação ↔ Cotação) -->
        <?php
        $comments = \App\Core\Database::fetchAll("SELECT * FROM purchase_order_comments WHERE order_id = ? ORDER BY created_at ASC", [$order['id']]);
        ?>
        <?php if (!empty($comments)): ?>
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-chat-dots"></i> Conversas (Aprovação ↔ Cotação) <span class="badge bg-secondary"><?= count($comments) ?></span></div>
            <div class="card-body p-2">
                <?php foreach ($comments as $c): ?>
                <div class="p-2 mb-1 rounded <?= $c['author_role'] === 'approver' ? 'bg-warning bg-opacity-10 border-start border-warning border-3' : 'bg-info bg-opacity-10 border-start border-info border-3' ?>" style="font-size:0.8rem;">
                    <strong><?= htmlspecialchars($c['author_name']) ?></strong>
                    <span class="text-muted small">(<?= $c['author_role'] === 'approver' ? 'Aprovação' : 'Cotação' ?>) · <?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></span>
                    <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($c['message'])) ?></p>
                </div>
                <?php endforeach; ?>
            </div>
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
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        <?php if (in_array($order['status'], ['pending_quote', 'quoted', 'pending_approval', 'approved'])): ?>
                        <button class="btn btn-sm btn-outline-warning" onclick="resendPhase(<?= $order['id'] ?>, 'quote_requested')"><i class="bi bi-arrow-repeat"></i> Cotação</button>
                        <?php endif; ?>
                        <?php if (in_array($order['status'], ['pending_approval', 'approved'])): ?>
                        <button class="btn btn-sm btn-outline-info" onclick="resendPhase(<?= $order['id'] ?>, 'approval_requested')"><i class="bi bi-arrow-repeat"></i> Aprovação</button>
                        <?php endif; ?>
                        <?php if ($order['status'] === 'approved'): ?>
                        <button class="btn btn-sm btn-outline-success" onclick="resendPhase(<?= $order['id'] ?>, 'order_approved')"><i class="bi bi-arrow-repeat"></i> Conclusão</button>
                        <button class="btn btn-sm btn-outline-primary" onclick="resendPhase(<?= $order['id'] ?>, 'payment_uploaded')"><i class="bi bi-arrow-repeat"></i> NF/Pagamento</button>
                        <?php endif; ?>
                        <?php if ($order['status'] === 'rejected'): ?>
                        <button class="btn btn-sm btn-outline-danger" onclick="resendPhase(<?= $order['id'] ?>, 'order_rejected')"><i class="bi bi-arrow-repeat"></i> Rejeição</button>
                        <?php endif; ?>
                        <?php if (!empty($order['delivery_token'])): ?>
                        <button class="btn btn-sm btn-outline-dark" onclick="resendPhase(<?= $order['id'] ?>, 'delivery_ready')"><i class="bi bi-arrow-repeat"></i> Entrega</button>
                        <?php endif; ?>
                        <?php
                        // Verificar se tem itens de estoque/transferência neste pedido
                        $hasStockItems = !empty(array_filter($items ?? [], fn($i) => !empty($i['source_type']) && $i['source_type'] !== 'purchase'));
                        ?>
                        <?php if ($hasStockItems): ?>
                        <button class="btn btn-sm btn-outline-secondary" onclick="resendPhase(<?= $order['id'] ?>, 'stock_transport')"><i class="bi bi-truck"></i> Transporte</button>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <small class="text-muted">Enviar:</small>
                        <div class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="checkbox" id="resendEmail" checked>
                            <label class="form-check-label small" for="resendEmail">E-mail</label>
                        </div>
                        <div class="form-check form-check-inline mb-0">
                            <input class="form-check-input" type="checkbox" id="resendWebhook" checked>
                            <label class="form-check-label small" for="resendWebhook">Webhook</label>
                        </div>
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

        <!-- Histórico de Edições -->
        <?php
        $edits = \App\Core\Database::fetchAll("SELECT * FROM purchase_order_edits WHERE order_id = ? ORDER BY created_at DESC", [$order['id']]);
        ?>
        <?php if (!empty($edits)): ?>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center" role="button" data-bs-toggle="collapse" data-bs-target="#editsCollapse">
                <span><i class="bi bi-pencil-square"></i> Edições do Pedido</span>
                <span class="badge bg-warning text-dark"><?= count($edits) ?> edição(ões)</span>
            </div>
            <div class="collapse" id="editsCollapse">
                <div class="card-body p-0">
                    <?php foreach ($edits as $edit): ?>
                    <?php $ch = json_decode($edit['changes'], true) ?: []; ?>
                    <div class="list-group-item px-3 py-2 border-bottom">
                        <div class="d-flex justify-content-between">
                            <strong class="small"><?= htmlspecialchars($edit['edited_by_name']) ?></strong>
                            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($edit['created_at'])) ?></small>
                        </div>
                        <?php if (!empty($ch['added'])): ?>
                        <div class="small text-success mt-1">
                            <?php foreach ($ch['added'] as $a): ?>
                            <div>+ <?= htmlspecialchars($a['material_name']) ?> (Qtd: <?= $a['quantity'] ?>)</div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($ch['removed'])): ?>
                        <div class="small text-danger mt-1">
                            <?php foreach ($ch['removed'] as $r): ?>
                            <div>- <?= htmlspecialchars($r['material_name']) ?> (Qtd: <?= $r['quantity'] ?>)</div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($ch['changed'])): ?>
                        <div class="small text-primary mt-1">
                            <?php foreach ($ch['changed'] as $c): ?>
                            <div>• <?= htmlspecialchars($c['material_name']) ?>: Qtd <?= $c['old_quantity'] ?> → <?= $c['new_quantity'] ?></div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
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
        <?php if (!\App\Core\Auth::hasPermission('transport') || \App\Core\Auth::hasPermission('orders.create')): ?>
        <div class="card mb-3">
            <div class="card-header">Ações</div>
            <div class="card-body d-grid gap-2">
                <?php if ($order['status'] === 'pending_quote'): ?>
                <?php if (empty($order['quote_started_at'])): ?>
                <a href="/admin/orders/edit-items/<?= $order['id'] ?>" class="btn btn-outline-primary w-100">
                    <i class="bi bi-pencil-square"></i> Editar Itens do Pedido
                </a>
                <?php else: ?>
                <div class="alert alert-info py-2 px-3 small mb-2">
                    <i class="bi bi-lock"></i> Cotação iniciada por <strong><?= htmlspecialchars($order['quote_started_by'] ?? '') ?></strong>
                    <br><small class="text-muted"><?= date('d/m/Y H:i', strtotime($order['quote_started_at'])) ?></small>
                </div>
                <?php endif; ?>
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
                <hr>
                <button type="button" class="btn btn-outline-warning w-100" data-bs-toggle="collapse" data-bs-target="#reopenSection">
                    <i class="bi bi-arrow-repeat"></i> Reenviar p/ Reaprovação
                </button>
                <div class="collapse mt-2" id="reopenSection">
                    <form method="POST" action="/admin/orders/reopen-approval" onsubmit="return confirm('Reabrir para reaprovação? O fornecedor aprovado será desfeito.')">
                        <input type="hidden" name="id" value="<?= $order['id'] ?>">
                        <textarea class="form-control form-control-sm mb-2" name="reason" rows="2" placeholder="Motivo (opcional): ex: Fornecedor aprovado errado"></textarea>
                        <button type="submit" class="btn btn-warning btn-sm w-100"><i class="bi bi-send"></i> Confirmar e Enviar</button>
                    </form>
                </div>
                <?php endif; ?>

                <?php if (!in_array($order['status'], ['cancelled'])): ?>
                <hr>
                <?php if ($order['status'] === 'approved' && \App\Core\Auth::hasPermission('orders.payment')): ?>
                <a href="/admin/orders/financial-edit/<?= $order['id'] ?>" class="btn w-100 mb-2" style="border-color:#8b5cf6; color:#8b5cf6; background-color: rgba(139, 92, 246, 0.05);" onmouseover="this.style.backgroundColor='#8b5cf6';this.style.color='#fff'" onmouseout="this.style.backgroundColor='rgba(139, 92, 246, 0.05)';this.style.color='#8b5cf6'">
                    <i class="bi bi-pencil-square"></i> Editar Itens (Financeiro)
                </a>
                <?php if (empty($order['financial_reviewed_at'])): ?>
                <form method="POST" action="/admin/orders/financial-review">
                    <input type="hidden" name="id" value="<?= $order['id'] ?>">
                    <button type="submit" class="btn w-100" style="border-color:#8b5cf6; color:#8b5cf6;" onmouseover="this.style.backgroundColor='#8b5cf6';this.style.color='#fff'" onmouseout="this.style.backgroundColor='transparent';this.style.color='#8b5cf6'" onclick="return confirm('Marcar este pedido como revisado pelo financeiro?')">
                        <i class="bi bi-check2-square"></i> Revisado pelo Financeiro
                    </button>
                </form>
                <?php else: ?>
                <div class="alert py-2 px-3 small mb-2" style="background-color: rgba(139, 92, 246, 0.1); border-color: #8b5cf6; color: #6d28d9;">
                    <i class="bi bi-check-circle-fill"></i> Revisado por <strong><?= htmlspecialchars($order['financial_reviewed_by'] ?? '') ?></strong>
                    <br><small class="text-muted"><?= date('d/m/Y H:i', strtotime($order['financial_reviewed_at'])) ?></small>
                </div>
                <form method="POST" action="/admin/orders/financial-unreview">
                    <input type="hidden" name="id" value="<?= $order['id'] ?>">
                    <button type="submit" class="btn btn-outline-secondary btn-sm w-100" onclick="return confirm('Desmarcar revisão financeira?')">
                        <i class="bi bi-arrow-counterclockwise"></i> Desmarcar Revisão
                    </button>
                </form>
                <?php endif; ?>
                <?php endif; ?>

                <?php if ($order['status'] === 'approved'): ?>
                <!-- Comprado -->
                <?php if (empty($order['purchased_at'])): ?>
                <form method="POST" action="/admin/orders/mark-purchased" class="mt-2">
                    <input type="hidden" name="id" value="<?= $order['id'] ?>">
                    <button type="submit" class="btn w-100" style="border-color:#e67e22; color:#e67e22;" onmouseover="this.style.backgroundColor='#e67e22';this.style.color='#fff'" onmouseout="this.style.backgroundColor='transparent';this.style.color='#e67e22'" onclick="return confirm('Marcar este pedido como comprado?')">
                        <i class="bi bi-bag-check"></i> Marcar como Comprado
                    </button>
                </form>
                <?php else: ?>
                <div class="alert py-2 px-3 small mb-2 mt-2" style="background-color: rgba(230, 126, 34, 0.1); border-color: #e67e22; color: #d35400;">
                    <i class="bi bi-bag-check-fill"></i> Comprado por <strong><?= htmlspecialchars($order['purchased_by'] ?? '') ?></strong>
                    <br><small class="text-muted"><?= date('d/m/Y H:i', strtotime($order['purchased_at'])) ?></small>
                </div>
                <form method="POST" action="/admin/orders/unmark-purchased">
                    <input type="hidden" name="id" value="<?= $order['id'] ?>">
                    <button type="submit" class="btn btn-outline-secondary btn-sm w-100" onclick="return confirm('Desmarcar como comprado?')">
                        <i class="bi bi-arrow-counterclockwise"></i> Desmarcar Comprado
                    </button>
                </form>
                <?php endif; ?>
                <?php endif; ?>

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
        <?php endif; ?>

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
                    <a href="<?= $baseUrl ?>/pedido/cotacao/<?= $order['quote_token'] ?>" target="_blank" class="btn btn-outline-primary btn-sm w-100 mt-2">
                        <i class="bi bi-pencil-square"></i> Editar Cotação
                    </a>
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

<script src="/assets/js/searchable-select.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // SearchableSelect para materiais no formulário de sobressalentes
    const selectEl = document.getElementById('spare-mat-select');
    if (selectEl) {
        const ss = new SearchableSelect(selectEl, {
            placeholder: 'Buscar material ou digitar...',
            onSelect: function(value, text, dataset) {
                document.getElementById('spare-description').value = value || text;
                if (dataset && dataset.unit) {
                    document.getElementById('spare-unit').value = dataset.unit;
                }
            }
        });

        // Permitir texto livre no submit
        const form = document.getElementById('spareItemForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                const desc = document.getElementById('spare-description');
                if (!desc.value && ss.input && ss.input.value.trim()) {
                    desc.value = ss.input.value.trim();
                }
                if (!desc.value) {
                    e.preventDefault();
                    alert('Informe a descrição do item.');
                    if (ss.input) ss.input.focus();
                }
            });
        }
    }
});
</script>
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
    const labels = {quote_requested:'Cotação',approval_requested:'Aprovação',order_approved:'Conclusão',order_rejected:'Rejeição',payment_uploaded:'NF/Pagamento',delivery_ready:'Entrega'};
    const sendEmail = document.getElementById('resendEmail').checked;
    const sendWebhook = document.getElementById('resendWebhook').checked;

    if (!sendEmail && !sendWebhook) {
        alert('Selecione pelo menos uma opção: E-mail ou Webhook.');
        return;
    }

    const channels = [];
    if (sendEmail) channels.push('e-mail');
    if (sendWebhook) channels.push('webhook');

    if (!confirm('Reenviar ' + channels.join(' e ') + ' de "' + (labels[phase] || phase) + '" para este pedido?')) return;
    fetch('/admin/orders/resend-all-phase', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'order_id=' + orderId + '&phase=' + phase + '&send_email=' + (sendEmail ? '1' : '0') + '&send_webhook=' + (sendWebhook ? '1' : '0')
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

<script>
// Máscara e validação CPF/CNPJ
(function() {
    const input = document.getElementById('cpfCnpjInput');
    if (!input) return;

    input.addEventListener('input', function() {
        let v = this.value.replace(/\D/g, '');
        if (v.length <= 11) {
            v = v.replace(/(\d{3})(\d)/, '$1.$2');
            v = v.replace(/(\d{3})(\d)/, '$1.$2');
            v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        } else {
            v = v.substring(0, 14);
            v = v.replace(/^(\d{2})(\d)/, '$1.$2');
            v = v.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            v = v.replace(/\.(\d{3})(\d)/, '.$1/$2');
            v = v.replace(/(\d{4})(\d)/, '$1-$2');
        }
        this.value = v;
    });

    // Validação no submit
    const form = document.getElementById('paymentForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const raw = input.value.replace(/\D/g, '');
            if (raw.length === 0) return;
            if (raw.length !== 11 && raw.length !== 14) {
                e.preventDefault();
                alert('CPF/CNPJ inválido. CPF deve ter 11 dígitos e CNPJ 14 dígitos.');
                input.focus();
                return;
            }
            if (raw.length === 11 && !validarCPF(raw)) {
                e.preventDefault();
                alert('CPF inválido.');
                input.focus();
                return;
            }
            if (raw.length === 14 && !validarCNPJ(raw)) {
                e.preventDefault();
                alert('CNPJ inválido.');
                input.focus();
                return;
            }
            // Validar contra fornecedor aprovado
            const approvedCnpj = document.getElementById('approvedCnpj')?.value || '';
            if (approvedCnpj && raw !== approvedCnpj) {
                const supplierName = document.getElementById('approvedSupplierName')?.value || 'fornecedor aprovado';
                if (!confirm('⚠️ ATENÇÃO: O CPF/CNPJ informado (' + input.value + ') é diferente do fornecedor aprovado ' + supplierName + '.\n\nDeseja continuar mesmo assim?')) {
                    e.preventDefault();
                    input.focus();
                    return;
                }
            }
        });
    }

    // Validação com IA ao selecionar arquivo
    const fileInput = document.getElementById('paymentFile');
    const resultDiv = document.getElementById('cnpjValidationResult');
    
    if (fileInput && resultDiv) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;
            
            const approvedCnpj = document.getElementById('approvedCnpj')?.value || '';
            if (!approvedCnpj) return; // sem CNPJ de referência, não valida

            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<small class="text-muted"><i class="bi bi-hourglass-split"></i> Analisando documento com IA para validar CPF/CNPJ...</small>';

            const formData = new FormData();
            formData.append('file', file);
            formData.append('approved_cnpj', approvedCnpj);

            fetch('/admin/orders/validate-payment-cnpj', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        resultDiv.innerHTML = '<small class="text-warning"><i class="bi bi-exclamation-triangle"></i> ' + data.error + '</small>';
                        return;
                    }
                    if (data.extracted_cnpj) {
                        const extracted = data.extracted_cnpj.replace(/\D/g, '');
                        const match = extracted === approvedCnpj;
                        
                        if (match) {
                            resultDiv.innerHTML = '<small class="text-success"><i class="bi bi-check-circle-fill"></i> CPF/CNPJ do documento confere com o fornecedor aprovado (' + data.extracted_cnpj + ')</small>';
                            // Preencher o campo automaticamente
                            if (!input.value) input.value = data.extracted_cnpj;
                        } else {
                            resultDiv.innerHTML = '<small class="text-danger"><i class="bi bi-x-circle-fill"></i> CPF/CNPJ do documento (' + data.extracted_cnpj + ') <strong>NÃO confere</strong> com o fornecedor aprovado.</small>';
                        }
                        
                        // Também validar contra o campo digitado
                        const typed = input.value.replace(/\D/g, '');
                        if (typed && typed !== extracted) {
                            resultDiv.innerHTML += '<br><small class="text-warning"><i class="bi bi-exclamation-triangle"></i> O CPF/CNPJ digitado no campo também difere do encontrado no documento.</small>';
                        }
                    } else {
                        resultDiv.innerHTML = '<small class="text-muted"><i class="bi bi-info-circle"></i> Não foi possível extrair CPF/CNPJ do documento.</small>';
                    }
                })
                .catch(() => {
                    resultDiv.innerHTML = '<small class="text-muted"><i class="bi bi-info-circle"></i> Não foi possível validar o documento automaticamente.</small>';
                });
        });
    }

    function validarCPF(cpf) {
        if (/^(\d)\1{10}$/.test(cpf)) return false;
        let soma = 0, resto;
        for (let i = 1; i <= 9; i++) soma += parseInt(cpf[i-1]) * (11 - i);
        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf[9])) return false;
        soma = 0;
        for (let i = 1; i <= 10; i++) soma += parseInt(cpf[i-1]) * (12 - i);
        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        return resto === parseInt(cpf[10]);
    }

    function validarCNPJ(cnpj) {
        if (/^(\d)\1{13}$/.test(cnpj)) return false;
        let tamanho = cnpj.length - 2, numeros = cnpj.substring(0, tamanho), digitos = cnpj.substring(tamanho), soma = 0, pos = tamanho - 7;
        for (let i = tamanho; i >= 1; i--) { soma += numeros[tamanho - i] * pos--; if (pos < 2) pos = 9; }
        let resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        if (resultado != digitos[0]) return false;
        tamanho++; numeros = cnpj.substring(0, tamanho); soma = 0; pos = tamanho - 7;
        for (let i = tamanho; i >= 1; i--) { soma += numeros[tamanho - i] * pos--; if (pos < 2) pos = 9; }
        resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        return resultado == digitos[1];
    }
})();
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
