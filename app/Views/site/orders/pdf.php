<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido <?= htmlspecialchars($order['code']) ?> - PDF | Brooks Construtora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .pdf-container {
            background: #fff;
            max-width: 800px;
            margin: 2rem auto;
            padding: 2.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .pdf-header {
            border-bottom: 3px solid #3a3b4e;
            padding-bottom: 1.2rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .pdf-header .company-name { font-size: 1.4rem; font-weight: 700; color: #3a3b4e; letter-spacing: 1px; }
        .pdf-header .doc-title { font-size: 0.8rem; color: #666; text-transform: uppercase; letter-spacing: 1px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; margin-bottom: 1.5rem; }
        .info-grid .info-item label { font-size: 0.65rem; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0; display: block; }
        .info-grid .info-item span { display: block; font-weight: 600; color: #333; font-size: 0.9rem; }
        
        /* Tabela de itens - apenas desktop */
        .items-table-desktop { width:100%; border-collapse:collapse; margin:1.5rem 0; font-size:0.82rem; }
        .items-table-desktop th { background:#3a3b4e; color:#fff; padding:7px 8px; text-align:left; font-size:0.7rem; text-transform:uppercase; }
        .items-table-desktop td { padding:7px 8px; border-bottom:1px solid #eee; }
        .items-table-desktop .total-row { background:#f8f9fa; font-weight:700; }
        .items-table-desktop .total-row td { border-top:2px solid #3a3b4e; }
        
        /* Itens mobile - cards */
        .item-card-pdf { border:1px solid #e8e8e8; border-radius:6px; padding:10px 12px; margin-bottom:8px; }
        .item-card-pdf .item-name { font-weight:600; font-size:0.85rem; color:#333; }
        .item-card-pdf .item-meta { font-size:0.7rem; color:#888; margin-top:2px; }
        .item-card-pdf .item-price { text-align:right; font-weight:700; color:#333; font-size:0.85rem; }
        
        .approval-section { background:#e8f5e9; border:1px solid #c8e6c9; border-radius:6px; padding:1rem; margin-top:1.5rem; }
        .approval-section h6 { color:#2e7d32; margin-bottom:0.5rem; font-size:0.9rem; }
        .history-section { margin-top:1.5rem; font-size:0.75rem; }
        .history-section .timeline-item { padding:0.4rem 0; border-left:2px solid #ddd; padding-left:0.8rem; margin-left:0.5rem; }
        .history-section .timeline-item:last-child { border-left-color:#28a745; }
        .pdf-footer { border-top:1px solid #ddd; padding-top:1rem; margin-top:2rem; text-align:center; font-size:0.65rem; color:#999; }
        .download-bar { text-align:center; margin:1rem 0 2rem; }
        
        @media print {
            .download-bar { display:none; }
            body { background:#fff; }
            .pdf-container { box-shadow:none; margin:0; padding:1.5rem; max-width:100%; }
        }
        @media (max-width: 768px) {
            .pdf-container { padding:1rem; margin:0.5rem; border-radius:6px; }
            .info-grid { grid-template-columns:1fr; gap:0.5rem; }
            .pdf-header { text-align:center; justify-content:center; flex-direction:column; }
            .pdf-header .company-name { font-size:1.1rem; }
            .items-table-desktop { display:none; }
            .download-bar { margin:0.5rem 0 1rem; }
            .download-bar .btn { font-size:0.85rem; padding:0.5rem 1rem; }
        }
        @media (min-width: 769px) {
            .items-mobile-only { display:none; }
        }
    </style>
</head>
<body>
    <div class="download-bar">
        <button class="btn btn-primary" id="downloadPdfBtn">
            <i class="bi bi-download"></i> Baixar PDF
        </button>
        <a href="/pedido/xlsx/<?= $order['id'] ?>" class="btn btn-success ms-2">
            <i class="bi bi-file-earmark-spreadsheet"></i> Baixar Planilha
        </a>
        <button class="btn btn-outline-secondary ms-2" onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimir
        </button>
    </div>

    <div class="pdf-container" id="pdfContent">
        <!-- Header -->
        <div class="pdf-header">
            <div>
                <div class="company-name">BROOKS CONSTRUTORA</div>
                <div class="doc-title">Formalização de Pedido de Materiais</div>
            </div>
            <div class="text-end">
                <div style="font-size:1.3rem; font-weight:700; color:#3a3b4e;"><?= htmlspecialchars($order['code']) ?></div>
                <div style="font-size:0.75rem; color:#28a745; font-weight:600;">✓ APROVADO</div>
            </div>
        </div>

        <!-- Informações -->
        <div class="info-grid">
            <div class="info-item">
                <label>Fornecedor(es) Aprovado(s)</label>
                <span>
                <?php 
                $allApproved = \App\Models\PurchaseOrderSupplier::getAllApproved($order['id']);
                if (!empty($allApproved) && count($allApproved) > 1):
                    echo htmlspecialchars(implode(', ', array_column($allApproved, 'supplier_name')));
                elseif ($approvedSupplier):
                    echo htmlspecialchars($approvedSupplier['supplier_name']);
                else:
                    echo htmlspecialchars($order['supplier_name'] ?? 'N/A');
                endif;
                ?>
                </span>
            </div>
            <div class="info-item">
                <label>Data do Pedido</label>
                <span><?= date('d/m/Y', strtotime($order['created_at'])) ?></span>
            </div>
            <div class="info-item">
                <label>Solicitado por</label>
                <span><?= htmlspecialchars($order['created_by_name']) ?></span>
            </div>
            <?php if (!empty($order['construction_site_name'])): ?>
            <div class="info-item">
                <label>Obra</label>
                <span><?= htmlspecialchars($order['construction_site_code'] . ' - ' . $order['construction_site_name']) ?></span>
            </div>
            <?php if (!empty($order['construction_site_address'])): ?>
            <div class="info-item">
                <label>Local da Obra</label>
                <span><?= htmlspecialchars($order['construction_site_address']) ?><?= !empty($order['construction_site_city']) ? ' - ' . $order['construction_site_city'] . '/' . ($order['construction_site_state'] ?? '') : '' ?></span>
            </div>
            <?php endif; ?>
            <?php endif; ?>
            <div class="info-item">
                <label>Cotado por</label>
                <span><?= htmlspecialchars($order['quoted_by_name'] ?? '-') ?> <?= $order['quoted_at'] ? '(' . date('d/m/Y', strtotime($order['quoted_at'])) . ')' : '' ?></span>
            </div>
            <?php if ($approvedSupplier && $approvedSupplier['vendor_name']): ?>
            <div class="info-item">
                <label>Vendedor</label>
                <span><?= htmlspecialchars($approvedSupplier['vendor_name']) ?><?= $approvedSupplier['vendor_phone'] ? ' - ' . htmlspecialchars($approvedSupplier['vendor_phone']) : '' ?></span>
            </div>
            <?php endif; ?>
            <?php if ($approvedSupplier && $approvedSupplier['delivery_days']): ?>
            <div class="info-item">
                <label>Prazo de Entrega</label>
                <span><?= $approvedSupplier['delivery_days'] ?> dias</span>
            </div>
            <?php endif; ?>
            <?php if ($approvedSupplier && !empty($approvedSupplier['payment_method'])): ?>
            <?php $paymentLabels = ['pix'=>'PIX','boleto'=>'Boleto','cartao'=>'Cartão','transferencia'=>'Transferência','dinheiro'=>'Dinheiro','outro'=>'Outro']; ?>
            <div class="info-item">
                <label>Forma de Pagamento</label>
                <span><?= $paymentLabels[$approvedSupplier['payment_method']] ?? $approvedSupplier['payment_method'] ?><?= !empty($approvedSupplier['payment_condition']) ? ' (' . htmlspecialchars($approvedSupplier['payment_condition']) . ')' : '' ?></span>
            </div>
            <?php if (!empty($approvedSupplier['payment_first_due'])): ?>
            <div class="info-item">
                <label>1ª Parcela</label>
                <span><?= date('d/m/Y', strtotime($approvedSupplier['payment_first_due'])) ?></span>
            </div>
            <?php endif; ?>
            <?php endif; ?>
            <?php if (!empty($order['supplier_cnpj'])): ?>
            <div class="info-item">
                <label>CNPJ Fornecedor</label>
                <span><?= htmlspecialchars($order['supplier_cnpj']) ?></span>
            </div>
            <?php endif; ?>
        <?php
        // Calcular o total real (com financeiros) usando o fornecedor aprovado se disponível
        $totalReal = $order['total_estimated'];
        if ($approvedSupplier && $approvedSupplier['subtotal_final'] > 0) {
            $totalReal = $approvedSupplier['subtotal_final'];
        }
        ?>
            <div class="info-item">
                <label>Valor Total</label>
                <span style="color:#28a745; font-size:1.1rem;">R$ <?= number_format($totalReal, 2, ',', '.') ?></span>
            </div>
        </div>

        <?php if (!empty($orderSuppliers) && count($orderSuppliers) > 1): ?>
        <div style="background:#f8f9fa; border-radius:6px; padding:10px 14px; margin-bottom:1rem; font-size:0.8rem;">
            <strong>Comparação de fornecedores:</strong>
            <div style="margin-top:6px;">
                <?php foreach ($orderSuppliers as $os): ?>
                <div class="d-flex justify-content-between py-1 <?= $os['approved'] ? 'fw-bold' : '' ?>" style="border-bottom:1px solid #eee;">
                    <span>
                        <?= htmlspecialchars($os['supplier_name']) ?> <?= $os['approved'] ? '(APROVADO)' : '' ?>
                        <?php if ($os['subtotal_items'] > 0 && ($os['discount_value'] > 0 || $os['ipi_percent'] > 0 || $os['icms_percent'] > 0 || $os['freight'] > 0)): ?>
                        <br><small class="text-muted fw-normal">
                            Insumos: R$ <?= number_format($os['subtotal_items'], 2, ',', '.') ?>
                            <?= $os['discount_value'] > 0 ? ' | Desc: ' . $os['discount_value'] . ($os['discount_type'] === 'percent' ? '%' : 'R$') : '' ?>
                            <?= $os['surcharge_value'] > 0 ? ' | Acrés: ' . $os['surcharge_value'] . ($os['surcharge_type'] === 'percent' ? '%' : 'R$') : '' ?>
                            <?= $os['ipi_percent'] > 0 ? ' | IPI: ' . $os['ipi_percent'] . '%' : '' ?>
                            <?= $os['icms_percent'] > 0 ? ' | ICMS: ' . $os['icms_percent'] . '%' : '' ?>
                            <?= $os['freight'] > 0 ? ' | Frete: R$ ' . number_format($os['freight'], 2, ',', '.') : '' ?>
                        </small>
                        <?php endif; ?>
                    </span>
                    <span><?= $os['subtotal_final'] ? 'R$ ' . number_format($os['subtotal_final'], 2, ',', '.') : ($os['total'] ? 'R$ ' . number_format($os['total'], 2, ',', '.') : '-') ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php elseif (!empty($orderSuppliers) && count($orderSuppliers) == 1): ?>
        <?php $os = $orderSuppliers[0]; ?>
        <?php if ($os['subtotal_items'] > 0 && ($os['discount_value'] > 0 || $os['ipi_percent'] > 0 || $os['icms_percent'] > 0 || $os['freight'] > 0)): ?>
        <div style="background:#f8f9fa; border-radius:6px; padding:10px 14px; margin-bottom:1rem; font-size:0.8rem;">
            <strong>Detalhamento financeiro:</strong>
            <div class="d-flex flex-wrap gap-3 mt-1">
                <span>Insumos: <strong>R$ <?= number_format($os['subtotal_items'], 2, ',', '.') ?></strong></span>
                <?= $os['discount_value'] > 0 ? '<span>Desconto: <strong>' . $os['discount_value'] . ($os['discount_type'] === 'percent' ? '%' : ' R$') . '</strong></span>' : '' ?>
                <?= $os['surcharge_value'] > 0 ? '<span>Acréscimo: <strong>' . $os['surcharge_value'] . ($os['surcharge_type'] === 'percent' ? '%' : ' R$') . '</strong></span>' : '' ?>
                <?= $os['ipi_percent'] > 0 ? '<span>IPI: <strong>' . $os['ipi_percent'] . '%</strong></span>' : '' ?>
                <?= $os['icms_percent'] > 0 ? '<span>ICMS: <strong>' . $os['icms_percent'] . '%</strong></span>' : '' ?>
                <?= $os['freight'] > 0 ? '<span>Frete: <strong>R$ ' . number_format($os['freight'], 2, ',', '.') . '</strong></span>' : '' ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($order['description'])): ?>
        <div class="mb-3 p-2 bg-light rounded" style="font-size:0.8rem;">
            <strong>Obs:</strong> <?= nl2br(htmlspecialchars($order['description'])) ?>
        </div>
        <?php endif; ?>

        <!-- Itens: Desktop (tabela completa) -->
        <?php
        // Montar mapa de supplier_id => nome para exibir fornecedor por item
        $supplierNamesMap = [];
        if (!empty($orderSuppliers)) {
            foreach ($orderSuppliers as $os2) {
                $supplierNamesMap[$os2['supplier_id']] = $os2['supplier_name'];
            }
        }
        $hasMultiSupplier = !empty($allApproved) && count($allApproved) > 1;
        $showSupplierColumn = !empty($supplierNamesMap);
        ?>
        <table class="items-table-desktop">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Material</th>
                    <th>Espec.</th>
                    <th>Class.</th>
                    <th style="text-align:center;">Qtd</th>
                    <th style="text-align:right;">Unit.</th>
                    <th style="text-align:right;">Total</th>
                    <?php if ($showSupplierColumn): ?><th>Fornecedor</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i => $item): ?>
                <tr<?= !empty($item['source_type']) && $item['source_type'] !== 'purchase' ? ' style="background:#e8f5e9;"' : '' ?>>
                    <td><?= $i + 1 ?></td>
                    <td>
                        <strong><?= htmlspecialchars($item['material_name']) ?></strong>
                        <?php if (!empty($item['source_type']) && $item['source_type'] !== 'purchase'): ?>
                            <br><span style="font-size:0.65rem; color:#2e7d32; font-weight:600;">
                                <?= $item['source_type'] === 'stock_transfer' ? '↔ TRANSFERIDO' : '✓ ESTOQUE' ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($item['already_purchased'])): ?>
                            <br><span style="font-size:0.65rem; color:#0d6efd; font-weight:600;">
                                ✓ JÁ COMPRADO<?= !empty($item['already_purchased_qty']) ? ' (' . number_format($item['already_purchased_qty'], $item['already_purchased_qty'] == (int)$item['already_purchased_qty'] ? 0 : 2) . ')' : '' ?><?= $item['already_purchased_price'] ? ' — R$ ' . number_format($item['already_purchased_price'], 2, ',', '.') : '' ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($item['specification'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($item['classification'] ?? '-') ?></td>
                    <td style="text-align:center;"><?= number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2) ?></td>
                    <td style="text-align:right;">R$ <?= number_format($item['unit_price'] ?? 0, 2, ',', '.') ?></td>
                    <td style="text-align:right;">R$ <?= number_format($item['total_price'] ?? 0, 2, ',', '.') ?></td>
                    <?php if ($showSupplierColumn): ?><td style="font-size:0.7rem;"><?= htmlspecialchars($supplierNamesMap[$item['approved_supplier_id'] ?? 0] ?? '-') ?></td><?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php
                // Calcular subtotal de insumos (soma dos totais unitários)
                $subtotalInsumos = 0;
                foreach ($items as $item) {
                    $subtotalInsumos += ($item['total_price'] ?? 0);
                }
                ?>
                <tr class="total-row">
                    <td colspan="4" style="text-align:right;">
                        <?php if ($subtotalInsumos != $totalReal): ?>
                        <span style="font-size:0.75rem; color:#666; font-weight:normal;">Insumos: R$ <?= number_format($subtotalInsumos, 2, ',', '.') ?></span>
                        <?php endif; ?>
                    </td>
                    <td colspan="2" style="text-align:right;">TOTAL:</td>
                    <td style="text-align:right; color:#28a745;">R$ <?= number_format($totalReal, 2, ',', '.') ?></td>
                </tr>
            </tbody>
        </table>

        <!-- Resumo de Estoque (se houver itens do estoque) -->
        <?php
        $stockItemsList = array_filter($items, fn($it) => !empty($it['source_type']) && $it['source_type'] !== 'purchase');
        $purchaseItemsList = array_filter($items, fn($it) => empty($it['source_type']) || $it['source_type'] === 'purchase');
        ?>
        <?php if (!empty($stockItemsList)): ?>
        <div style="margin:1rem 0; padding:0.75rem; background:#e8f5e9; border-radius:6px; border-left:4px solid #4caf50;">
            <strong style="font-size:0.8rem; color:#2e7d32;">Movimentações de Estoque</strong>
            <table style="width:100%; margin-top:0.5rem; font-size:0.75rem; border-collapse:collapse;">
                <tr style="border-bottom:1px solid #c8e6c9;">
                    <th style="padding:4px; text-align:left;">Material</th>
                    <th style="padding:4px; text-align:center;">Qtd</th>
                    <th style="padding:4px; text-align:left;">Tipo</th>
                    <th style="padding:4px; text-align:left;">Origem</th>
                </tr>
                <?php foreach ($stockItemsList as $si): ?>
                <tr>
                    <td style="padding:3px 4px;"><?= htmlspecialchars($si['material_name']) ?></td>
                    <td style="padding:3px 4px; text-align:center;"><?= number_format($si['quantity'], $si['quantity'] == (int)$si['quantity'] ? 0 : 2) ?></td>
                    <td style="padding:3px 4px;">
                        <?= $si['source_type'] === 'stock_transfer' ? 'Transferência' : 'Uso de estoque' ?>
                    </td>
                    <td style="padding:3px 4px;">
                        <?php if (!empty($stockMovements)): ?>
                            <?php foreach ($stockMovements as $sm): ?>
                                <?php if ($sm['material_id'] == ($si['material_id'] ?? 0)): ?>
                                    <?= htmlspecialchars($sm['from_site_name'] ?? 'N/A') ?>
                                    <?= $si['source_type'] === 'stock_transfer' ? ' → ' . htmlspecialchars($sm['to_site_name'] ?? 'N/A') : '' ?>
                                    <?php break; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <div style="margin-top:0.5rem; font-size:0.7rem; color:#666;">
                <?= count($stockItemsList) ?> item(ns) de estoque | <?= count($purchaseItemsList) ?> item(ns) comprados
            </div>
        </div>
        <?php endif; ?>

        <!-- Itens: Mobile (cards) -->
        <div class="items-mobile-only" style="margin:1rem 0;">
            <?php foreach ($items as $i => $item): ?>
            <div class="item-card-pdf">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="item-name"><?= htmlspecialchars($item['material_name']) ?></div>
                        <div class="item-meta">
                            <?= htmlspecialchars($item['specification'] ?? '') ?>
                            <?= $item['classification'] ? ' · ' . htmlspecialchars($item['classification']) : '' ?>
                            <?php if (!empty($item['already_purchased'])): ?>
                            <br><span style="color:#0d6efd; font-weight:600;">✓ Já comprado<?= $item['already_purchased_price'] ? ' — R$ ' . number_format($item['already_purchased_price'], 2, ',', '.') : '' ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="item-price">R$ <?= number_format($item['total_price'] ?? 0, 2, ',', '.') ?></div>
                </div>
                <div class="d-flex justify-content-between mt-1" style="font-size:0.7rem; color:#666;">
                    <span><?= number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2) ?> × R$ <?= number_format($item['unit_price'] ?? 0, 2, ',', '.') ?></span>
                    <?php if (!empty($item['approved_supplier_id']) && isset($supplierNamesMap[$item['approved_supplier_id']])): ?>
                    <span style="color:#28a745;"><?= htmlspecialchars($supplierNamesMap[$item['approved_supplier_id']]) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <div style="border-top:2px solid #3a3b4e; padding-top:8px; margin-top:8px; text-align:right;">
                <?php if ($subtotalInsumos != $totalReal): ?>
                <div style="font-size:0.75rem; color:#666; margin-bottom:4px;">Insumos: R$ <?= number_format($subtotalInsumos, 2, ',', '.') ?></div>
                <?php endif; ?>
                <strong style="font-size:1rem;">TOTAL: <span style="color:#28a745;">R$ <?= number_format($totalReal, 2, ',', '.') ?></span></strong>
            </div>
        </div>

        <!-- Itens Sobressalentes -->
        <?php
        $spareItems = \App\Models\PurchaseOrderSpareItem::getByOrder($order['id']);
        if (!empty($spareItems)):
        $spareTotal = array_sum(array_column($spareItems, 'total_price'));
        ?>
        <div style="margin-top:1.5rem; border:1px solid #ffc107; border-radius:6px; padding:1rem; background:#fffdf0;">
            <h6 style="color:#856404; margin-bottom:0.5rem; font-size:0.9rem;"><i class="bi bi-bag-plus"></i> Itens Sobressalentes (Comprados na Hora)</h6>
            <table class="table table-sm mb-0" style="font-size:0.75rem;">
                <thead><tr><th>Data</th><th>Item</th><th>Qtd</th><th style="text-align:right;">Valor</th><th>Onde</th><th>Por</th></tr></thead>
                <tbody>
                <?php foreach ($spareItems as $si): ?>
                <tr>
                    <td><?= $si['purchased_at'] ? date('d/m', strtotime($si['purchased_at'])) : '-' ?></td>
                    <td><strong><?= htmlspecialchars($si['description']) ?></strong></td>
                    <td><?= number_format($si['quantity'], $si['quantity'] == (int)$si['quantity'] ? 0 : 2) ?></td>
                    <td style="text-align:right;">R$ <?= number_format($si['total_price'], 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars($si['supplier_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($si['purchased_by'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
                <tr style="border-top:2px solid #ffc107;"><td colspan="3" style="text-align:right; font-weight:700;">Total sobressalentes:</td><td style="text-align:right; font-weight:700;">R$ <?= number_format($spareTotal, 2, ',', '.') ?></td><td colspan="2"></td></tr>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Conversas (últimas 2) -->
        <?php
        $pdfComments = \App\Core\Database::fetchAll("SELECT * FROM purchase_order_comments WHERE order_id = ? ORDER BY created_at DESC LIMIT 4", [$order['id']]);
        $pdfComments = array_reverse($pdfComments);
        if (!empty($pdfComments)):
        ?>
        <div style="margin-top:1.5rem; border:1px solid #ffc107; border-radius:6px; padding:0.8rem; background:#fffdf0;">
            <h6 style="color:#856404; margin-bottom:0.5rem; font-size:0.8rem;"><i class="bi bi-chat-dots"></i> Observações</h6>
            <?php foreach ($pdfComments as $c): ?>
            <div style="padding:4px 8px; margin-bottom:4px; border-left:3px solid <?= $c['author_role'] === 'approver' ? '#ffc107' : '#0dcaf0' ?>; font-size:0.7rem;">
                <strong><?= htmlspecialchars($c['author_name']) ?></strong> <span style="color:#999;">(<?= $c['author_role'] === 'approver' ? 'Aprovação' : 'Cotação' ?>) <?= date('d/m H:i', strtotime($c['created_at'])) ?></span><br>
                <?= htmlspecialchars($c['message']) ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Materiais de Serviço -->
        <?php if (($order['order_type'] ?? 'material') === 'service'): ?>
        <?php
        $supplierMaterials = \App\Models\PurchaseOrderSupplierMaterial::getByOrder($order['id']);
        $supplierPdfs = \App\Models\PurchaseOrderSupplierPdf::getByOrder($order['id']);
        $matsBySup = [];
        foreach ($supplierMaterials as $mat) { $matsBySup[$mat['supplier_id']][] = $mat; }
        $pdfsBySup = [];
        foreach ($supplierPdfs as $pdf) { $pdfsBySup[$pdf['supplier_id']][] = $pdf; }
        // Mostrar apenas do fornecedor aprovado
        $approvedSids = [];
        if (!empty($orderSuppliers)) {
            foreach ($orderSuppliers as $os) {
                if (!empty($os['approved'])) $approvedSids[] = $os['supplier_id'];
            }
        }
        $sidsToShow = !empty($approvedSids) ? $approvedSids : array_keys($matsBySup);
        ?>
        <?php if (!empty($supplierMaterials)): ?>
        <div style="margin-top:1.5rem;">
            <h6 style="font-size:0.85rem;"><i class="bi bi-wrench"></i> Materiais de Serviço</h6>
            <?php foreach ($sidsToShow as $supId): ?>
            <?php if (empty($matsBySup[$supId])) continue; ?>
            <div style="margin-bottom:1rem;">
                <?php
                $supName = '-';
                foreach ($orderSuppliers as $os) { if ($os['supplier_id'] == $supId) { $supName = $os['supplier_name']; break; } }
                ?>
                <p style="font-size:0.75rem; margin-bottom:0.3rem;"><strong><?= htmlspecialchars($supName) ?></strong>
                <?php if (!empty($pdfsBySup[$supId])): ?>
                 — <a href="<?= htmlspecialchars($pdfsBySup[$supId][0]['file_path']) ?>" target="_blank">PDF do orçamento</a>
                <?php endif; ?>
                </p>
                <table class="items-table-desktop" style="font-size:0.75rem;">
                    <thead><tr><th>Material</th><th style="text-align:center;">Qtd</th><th style="text-align:right;">Unit.</th><th style="text-align:right;">Total</th></tr></thead>
                    <tbody>
                    <?php $svcTotal = 0; ?>
                    <?php foreach ($matsBySup[$supId] as $mat): ?>
                    <tr>
                        <td><?= htmlspecialchars($mat['material_name']) ?></td>
                        <td style="text-align:center;"><?= $mat['quantity'] ? number_format($mat['quantity'], $mat['quantity'] == (int)$mat['quantity'] ? 0 : 2) : '-' ?></td>
                        <td style="text-align:right;"><?= $mat['unit_price'] ? 'R$ ' . number_format($mat['unit_price'], 2, ',', '.') : '-' ?></td>
                        <td style="text-align:right;"><?= $mat['total_price'] ? 'R$ ' . number_format($mat['total_price'], 2, ',', '.') : '-' ?></td>
                    </tr>
                    <?php $svcTotal += (float)($mat['total_price'] ?? 0); ?>
                    <?php endforeach; ?>
                    <?php if ($svcTotal > 0): ?>
                    <tr class="total-row"><td colspan="3" style="text-align:right;">Total Materiais:</td><td style="text-align:right;">R$ <?= number_format($svcTotal, 2, ',', '.') ?></td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Aprovação -->
        <div class="approval-section">
            <h6><i class="bi bi-check-circle-fill"></i> Aprovação</h6>
            <div class="row">
                <div class="col-6">
                    <small class="text-muted" style="font-size:0.65rem;">Aprovado por:</small><br>
                    <strong style="font-size:0.85rem;"><?= htmlspecialchars($order['approved_by_name'] ?? '-') ?></strong>
                </div>
                <div class="col-6">
                    <small class="text-muted" style="font-size:0.65rem;">Data:</small><br>
                    <strong style="font-size:0.85rem;"><?= $order['approved_at'] ? date('d/m/Y H:i', strtotime($order['approved_at'])) : '-' ?></strong>
                </div>
            </div>
            <?php if (!empty($order['approval_notes'])): ?>
            <div class="mt-2" style="font-size:0.8rem;">
                <small class="text-muted">Obs:</small> <?= nl2br(htmlspecialchars($order['approval_notes'])) ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Edições -->
        <?php
        $pdfEdits = \App\Core\Database::fetchAll("SELECT * FROM purchase_order_edits WHERE order_id = ? ORDER BY created_at ASC", [$order['id']]);
        ?>
        <?php if (!empty($pdfEdits)): ?>
        <div class="history-section">
            <h6 style="font-size:0.8rem;"><i class="bi bi-pencil-square"></i> Edições do Pedido</h6>
            <?php foreach ($pdfEdits as $edit): ?>
            <?php $ch = json_decode($edit['changes'], true) ?: []; ?>
            <div class="timeline-item">
                <strong><?= htmlspecialchars($edit['edited_by_name']) ?></strong>
                <span class="text-muted ms-1"><?= date('d/m/Y H:i', strtotime($edit['created_at'])) ?></span><br>
                <?php if (!empty($ch['added'])): ?><small class="text-success"><?php foreach ($ch['added'] as $a): ?>+ <?= htmlspecialchars($a['material_name']) ?> (Qtd: <?= $a['quantity'] ?>) <?php endforeach; ?></small><br><?php endif; ?>
                <?php if (!empty($ch['removed'])): ?><small class="text-danger"><?php foreach ($ch['removed'] as $r): ?>- <?= htmlspecialchars($r['material_name']) ?> (Qtd: <?= $r['quantity'] ?>) <?php endforeach; ?></small><br><?php endif; ?>
                <?php if (!empty($ch['changed'])): ?><small class="text-primary"><?php foreach ($ch['changed'] as $c): ?>• <?= htmlspecialchars($c['material_name']) ?>: <?= $c['old_quantity'] ?> → <?= $c['new_quantity'] ?> <?php endforeach; ?></small><?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Histórico -->
        <div class="history-section">
            <h6 style="font-size:0.8rem;"><i class="bi bi-clock-history"></i> Histórico</h6>
            <?php foreach ($history as $entry): ?>
            <div class="timeline-item">
                <strong><?= htmlspecialchars($entry['performed_by_name'] ?? 'Sistema') ?></strong>
                <span class="text-muted ms-1"><?= date('d/m/Y H:i', strtotime($entry['created_at'])) ?></span><br>
                <small><?= htmlspecialchars($entry['description']) ?></small>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Footer -->
        <div class="pdf-footer">
            <p class="mb-1">Brooks Construtora — Av. Brigadeiro Faria Lima, 1811 — São Paulo/SP</p>
            <p class="mb-0">Documento gerado em <?= date('d/m/Y \à\s H:i') ?> | www.brooksconstrutora.com.br</p>
        </div>
    </div>

    <script>
    document.getElementById('downloadPdfBtn').addEventListener('click', async function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Gerando...';

        try {
            const element = document.getElementById('pdfContent');
            
            // Forçar largura fixa para o PDF ficar bonito (simula desktop)
            const originalWidth = element.style.width;
            element.style.width = '800px';
            
            const canvas = await html2canvas(element, { scale: 2, useCORS: true, logging: false, windowWidth: 900 });
            
            element.style.width = originalWidth;

            const { jsPDF } = window.jspdf;
            const imgWidth = 210;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            const pdf = new jsPDF('p', 'mm', 'a4');
            
            const pageHeight = 297;
            let heightLeft = imgHeight;
            let position = 0;

            pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;

            while (heightLeft > 0) {
                position = heightLeft - imgHeight;
                pdf.addPage();
                pdf.addImage(canvas.toDataURL('image/png'), 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
            }

            pdf.save('Pedido_<?= $order['code'] ?>.pdf');
        } catch (e) {
            alert('Erro ao gerar PDF: ' + e.message);
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-download"></i> Baixar PDF';
    });
    </script>
</body>
</html>
