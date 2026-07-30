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
        .item-card { border: 1px solid #dee2e6; border-radius: 8px; padding: 0.75rem; margin-bottom: 0.75rem; }
        .item-card .item-title { font-weight: 600; font-size: 0.85rem; color: #333; }
        .item-card .item-qty { font-size: 0.75rem; color: #6c757d; }
        .supplier-option { border: 2px solid #e9ecef; border-radius: 6px; padding: 0.5rem 0.75rem;
            margin-bottom: 0.4rem; cursor: pointer; transition: all 0.2s; display: flex;
            align-items: center; justify-content: space-between; gap: 0.5rem; }
        .supplier-option:hover { border-color: #28a745; background: #f8fff8; }
        .supplier-option.selected { border-color: #28a745; background: #f0fff4; }
        .supplier-option .supplier-name { font-size: 0.78rem; font-weight: 500; }
        .supplier-option .supplier-price { font-size: 0.8rem; font-weight: 700; color: #28a745; white-space: nowrap; }
        #approvalMap { background: #fff; border-radius: 8px; border: 1px solid #dee2e6; padding: 0.75rem; }
        #approvalMap table th { font-size: 0.7rem; white-space: nowrap; vertical-align: middle; }
        #approvalMap table td { vertical-align: middle; font-size: 0.75rem; }
        .map-cell-selectable { cursor: pointer; transition: background 0.2s; }
        .map-cell-selectable:hover { background: #e8f5e9 !important; }
        .map-cell-selectable.selected { background: #c8e6c9 !important; }
        .map-supplier-header { min-width: 110px; }
        .total-display { background: #e8f5e9; border: 1px solid #c3e6cb; border-radius: 8px; padding: 1rem;
            text-align: center; margin-top: 1rem; }
        .total-display .total-value { font-size: 1.4rem; font-weight: 700; color: #28a745; }
        .total-display .total-label { font-size: 0.75rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
        .total-display #totalDetail { font-size: 0.75rem; line-height: 1.6; margin-top: 0.5rem; }
        .total-display #totalDetail span { display: inline-block; }
        .btn-select-all { font-size: 0.7rem; padding: 0.2rem 0.5rem; }
        @media (min-width: 769px) {
            .item-card { display: flex; align-items: stretch; gap: 0.75rem; }
            .item-card .item-info { min-width: 180px; flex-shrink: 0; display: flex; flex-direction: column; justify-content: center; }
            .item-card .supplier-options { flex: 1; display: flex; flex-wrap: wrap; gap: 0.4rem; }
            .item-card .supplier-options .supplier-option { flex: 1; min-width: 140px; margin-bottom: 0; }
        }
        @media (max-width: 768px) {
            .main-card .card-body, .main-card .card-header { padding: 0.75rem; }
            .page-header h4 { font-size: 1.1rem; }
            .btn-lg { font-size: 0.85rem; padding: 0.6rem 1rem; }
            input, select, textarea { font-size: 16px !important; }
            #approvalMap { padding: 0.5rem; }
            #approvalMap table { border-collapse: separate; border-spacing: 0; }
            #approvalMap table th { font-size: 0.6rem; padding: 0.35rem 0.3rem; line-height: 1.3; }
            #approvalMap table td { font-size: 0.68rem; padding: 0.35rem 0.3rem; line-height: 1.3; }
            .map-supplier-header { min-width: 85px; padding: 0.35rem 0.25rem !important; }
            .view-toggle-wrap .btn { font-size: 0.8rem; padding: 0.35rem 0.75rem; }
            .item-card { padding: 0.6rem; }
            .supplier-option { padding: 0.4rem 0.6rem; }
            .supplier-option .supplier-name { font-size: 0.72rem; }
            .supplier-option .supplier-price { font-size: 0.75rem; }
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

    <div class="container py-3 py-md-4">
        <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card main-card">
            <div class="card-header bg-info bg-opacity-10 border-0 p-3 p-md-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <h5 class="mb-1">Pedido <strong><?= htmlspecialchars($order['code']) ?></strong><?php if (!empty($order['construction_site_name'])): ?> - <?= htmlspecialchars($order['construction_site_name']) ?><?php endif; ?></h5>
                        <p class="mb-0 text-muted small">Solicitado por: <?= htmlspecialchars($order['created_by_name']) ?></p>
                        <p class="mb-0 text-muted small">Cotado por: <strong><?= htmlspecialchars($order['quoted_by_name']) ?></strong> em <?= date('d/m/Y H:i', strtotime($order['quoted_at'])) ?></p>
                    </div>
                    <span class="badge bg-info text-white p-2">Aguardando Aprovação</span>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                <?php if (!empty($order['construction_site_name'])): ?>
                <div class="alert alert-light small mb-2">
                    <i class="bi bi-buildings"></i> <strong>Obra:</strong> <?= htmlspecialchars($order['construction_site_code'] . ' - ' . $order['construction_site_name']) ?>
                    <?php if (!empty($order['construction_site_address'])): ?>
                    <span class="text-muted ms-1">(<?= htmlspecialchars($order['construction_site_address']) ?><?= !empty($order['construction_site_city']) ? ' - ' . $order['construction_site_city'] . '/' . ($order['construction_site_state'] ?? '') : '' ?>)</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($order['description'])): ?>
                <div class="alert alert-light small mb-2"><strong>Obs pedido:</strong> <?= nl2br(htmlspecialchars($order['description'])) ?></div>
                <?php endif; ?>
                <?php if (!empty($order['quote_notes'])): ?>
                <div class="alert alert-warning small mb-2"><strong>Obs cotação:</strong> <?= nl2br(htmlspecialchars($order['quote_notes'])) ?></div>
                <?php endif; ?>

                <?php if (!empty($orderSuppliers)): ?>
                <!-- Resumo dos fornecedores com detalhes financeiros -->
                <div class="mb-3">
                    <h6 class="small fw-bold mb-2"><i class="bi bi-building"></i> Resumo dos Fornecedores Cotados</h6>
                    <?php
                    $pmLabels = ['pix'=>'PIX','boleto'=>'Boleto','cartao'=>'Cartão','transferencia'=>'Transferência','dinheiro'=>'Dinheiro','outro'=>'Outro'];
                    foreach ($orderSuppliers as $os):
                    ?>
                    <div class="border rounded p-2 mb-2" style="font-size:0.78rem;">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong><?= htmlspecialchars($os['supplier_name']) ?></strong>
                            <span class="fw-bold text-success"><?= $os['total'] ? 'R$ ' . number_format($os['total'], 2, ',', '.') : '-' ?></span>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-1" style="font-size:0.72rem; color:#6c757d;">
                            <?php if (!empty($os['vendor_name'])): ?><span><i class="bi bi-person"></i> <?= htmlspecialchars($os['vendor_name']) ?></span><?php endif; ?>
                            <?php if (!empty($os['vendor_phone'])): ?><span><i class="bi bi-telephone"></i> <?= htmlspecialchars($os['vendor_phone']) ?></span><?php endif; ?>
                            <?php if (!empty($os['delivery_days'])): ?><span><i class="bi bi-truck"></i> <?= $os['delivery_days'] ?> dias</span><?php endif; ?>
                            <?php if (!empty($os['payment_method'])): ?><span><i class="bi bi-credit-card"></i> <?= $pmLabels[$os['payment_method']] ?? $os['payment_method'] ?></span><?php endif; ?>
                            <?php if (!empty($os['payment_condition'])): ?><span><?= htmlspecialchars($os['payment_condition']) ?></span><?php endif; ?>
                            <?php if (!empty($os['payment_first_due'])): ?><span><i class="bi bi-calendar"></i> 1ª: <?= date('d/m/Y', strtotime($os['payment_first_due'])) ?></span><?php endif; ?>
                            <?php if (!empty($os['discount_value']) && $os['discount_value'] > 0): ?><span><i class="bi bi-arrow-down"></i> Desc: <?= $os['discount_value'] ?><?= $os['discount_type'] === 'percent' ? '%' : ' R$' ?></span><?php endif; ?>
                            <?php if (!empty($os['freight']) && $os['freight'] > 0): ?><span><i class="bi bi-box-seam"></i> Frete: R$ <?= number_format($os['freight'], 2, ',', '.') ?></span><?php endif; ?>
                            <?php if (!empty($os['ipi_percent']) && $os['ipi_percent'] > 0): ?><span>IPI: <?= $os['ipi_percent'] ?>%</span><?php endif; ?>
                            <?php if (!empty($os['icms_percent']) && $os['icms_percent'] > 0): ?><span>ICMS: <?= $os['icms_percent'] ?>%</span><?php endif; ?>
                        </div>
                        <?php if (!empty($os['quote_notes'])): ?>
                        <div class="mt-1 small text-muted fst-italic">"<?= htmlspecialchars($os['quote_notes']) ?>"</div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php
                // Agrupar preços por fornecedor e por item
                $pricesBySupplier = [];
                $pricesByItem = [];
                foreach ($itemPrices as $p) {
                    $pricesBySupplier[$p['supplier_id']][$p['item_id']] = $p;
                    $pricesByItem[$p['item_id']][$p['supplier_id']] = $p;
                }
                ?>

                <!-- Título e botões de seleção rápida -->
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
                    <h6 class="mb-0"><i class="bi bi-cart-check"></i> Selecione o fornecedor por item</h6>
                    <?php if (count($orderSuppliers) >= 2): ?>
                    <div class="d-flex gap-1 flex-wrap">
                        <?php foreach ($orderSuppliers as $os): ?>
                        <button type="button" class="btn btn-outline-success btn-select-all" onclick="selectAllFromSupplier(<?= $os['supplier_id'] ?>)">
                            Todos de <?= htmlspecialchars($os['supplier_name']) ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <?php
                // Separar itens por origem
                $stockItems = array_filter($items, fn($i) => !empty($i['source_type']) && $i['source_type'] !== 'purchase');
                $purchaseItems = array_filter($items, fn($i) => empty($i['source_type']) || $i['source_type'] === 'purchase');
                ?>

                <?php if (!empty($stockItems)): ?>
                <div class="alert alert-success small py-2 mb-3">
                    <i class="bi bi-box-seam"></i> <strong><?= count($stockItems) ?> item(ns) de estoque/transferência</strong>
                    <?php if (\App\Models\Setting::get('orders_require_transfer_approval', '1') === '1'): ?>
                        — precisam de aprovação
                    <?php else: ?>
                        — aprovados automaticamente (não precisam de ação)
                    <?php endif; ?>
                </div>
                <div class="mb-3 p-2 rounded" style="background: #e8f5e9;">
                    <small class="fw-bold text-success d-block mb-1"><i class="bi bi-arrow-left-right"></i> Itens de Estoque/Transferência:</small>
                    <?php foreach ($stockItems as $si): ?>
                        <div class="d-flex justify-content-between small py-1 border-bottom" style="border-color:#c8e6c9!important;">
                            <span>
                                <?= htmlspecialchars($si['material_name']) ?>
                                <span class="badge bg-<?= $si['source_type'] === 'stock_transfer' ? 'primary' : 'success' ?>" style="font-size:0.6rem;">
                                    <?= $si['source_type'] === 'stock_transfer' ? 'Transferência' : 'Estoque' ?>
                                </span>
                            </span>
                            <span class="fw-bold"><?= number_format($si['quantity'], $si['quantity'] == (int)$si['quantity'] ? 0 : 2) ?> <?= $si['unit'] ?? '' ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($purchaseItems)): ?>
                <div class="alert alert-warning small py-2 mb-3">
                    <i class="bi bi-cart"></i> <strong><?= count($purchaseItems) ?> item(ns) de compra</strong> — selecione o fornecedor para cada um:
                </div>
                <?php endif; ?>

                <p class="text-muted small mb-3"><i class="bi bi-info-circle"></i> Você pode escolher fornecedores diferentes para cada material.</p>
                <!-- Toggle de visualização -->
                <?php if (count($orderSuppliers) >= 2): ?>
                <div class="mb-3 view-toggle-wrap">
                    <div class="btn-group btn-group-sm w-100">
                        <button type="button" class="btn btn-outline-secondary" id="btnApprovalList" onclick="setApprovalView('list')"><i class="bi bi-list"></i> Lista</button>
                        <button type="button" class="btn btn-outline-secondary" id="btnApprovalMap" onclick="setApprovalView('map')"><i class="bi bi-table"></i> Mapa</button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- VISUALIZAÇÃO LISTA (por item) -->
                <div id="approvalListView" style="display:none;">
                <?php 
                $requireTransferApproval = \App\Models\Setting::get('orders_require_transfer_approval', '1') === '1';
                $itemsToApprove = $requireTransferApproval ? $items : $purchaseItems;
                ?>
                <?php foreach ($itemsToApprove as $item): ?>
                <div class="item-card" id="item-card-<?= $item['id'] ?>">
                    <div class="item-info">
                        <div class="item-title"><?= htmlspecialchars($item['material_name']) ?></div>
                        <div class="item-qty">Qtd: <?= number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2) ?><?= $item['unit'] ? ' ' . $item['unit'] : '' ?></div>
                    </div>
                    <div class="supplier-options">
                        <?php foreach ($orderSuppliers as $os): ?>
                        <?php $p = $pricesByItem[$item['id']][$os['supplier_id']] ?? null; ?>
                        <?php if ($p): ?>
                        <div class="supplier-option" id="opt-<?= $item['id'] ?>-<?= $os['supplier_id'] ?>"
                             onclick="selectItemSupplier(<?= $item['id'] ?>, <?= $os['supplier_id'] ?>)">
                            <div>
                                <div class="supplier-name"><?= htmlspecialchars($os['supplier_name']) ?></div>
                                <div style="font-size:0.68rem; color:#888;">R$ <?= number_format($p['unit_price'], 2, ',', '.') ?>/un</div>
                            </div>
                            <div class="supplier-price">R$ <?= number_format($p['total_price'], 2, ',', '.') ?></div>
                        </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
                <!-- VISUALIZAÇÃO MAPA (tabela comparativa com seleção por célula) -->
                <?php if (count($orderSuppliers) >= 2): ?>
                <div id="approvalMap" class="mb-3" style="display:none;">
                    <p class="text-muted small mb-2"><i class="bi bi-hand-index"></i> Toque no preço de cada item para selecionar o fornecedor daquele material.</p>
                    <div style="overflow-x:auto; -webkit-overflow-scrolling:touch; margin: 0 -0.5rem; padding: 0 0.5rem;">
                    <table class="table table-sm table-bordered mb-0" style="min-width:500px;">
                        <thead>
                            <tr class="table-dark">
                                <th style="min-width:140px; position:sticky; left:0; background:#212529; z-index:1;">Material</th>
                                <th class="text-center" style="width:45px;">Qtd</th>
                                <?php foreach ($orderSuppliers as $os): ?>
                                <th class="text-center map-supplier-header">
                                    <?= htmlspecialchars($os['supplier_name']) ?>
                                    <br><small class="text-success fw-bold"><?= 'R$ ' . number_format($os['total'] ?? 0, 2, ',', '.') ?></small>
                                    <br><button type="button" class="btn btn-outline-light btn-select-all" style="font-size:0.6rem; padding:0.1rem 0.3rem; margin-top:2px;" onclick="selectAllFromSupplier(<?= $os['supplier_id'] ?>)">Selecionar todos</button>
                                </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itemsToApprove as $item): ?>
                            <tr>
                                <td style="position:sticky; left:0; background:#fff; z-index:1;">
                                    <strong style="font-size:0.72rem;"><?= htmlspecialchars($item['material_name']) ?></strong>
                                </td>
                                <td class="text-center"><?= number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2) ?></td>
                                <?php foreach ($orderSuppliers as $os): ?>
                                <?php $p = $pricesBySupplier[$os['supplier_id']][$item['id']] ?? null; ?>
                                <td class="text-center <?= $p ? 'map-cell-selectable' : '' ?>"
                                    id="map-cell-<?= $item['id'] ?>-<?= $os['supplier_id'] ?>"
                                    <?= $p ? 'onclick="selectItemSupplier(' . $item['id'] . ', ' . $os['supplier_id'] . ')"' : '' ?>>
                                    <?php if ($p): ?>
                                    R$ <?= number_format($p['unit_price'], 2, ',', '.') ?>
                                    <br><small class="fw-bold text-dark">= R$ <?= number_format($p['total_price'], 2, ',', '.') ?></small>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
                <?php endif; ?>
                <!-- Total calculado dinamicamente -->
                <div class="total-display" id="totalDisplay">
                    <div class="total-label">Total Aprovado</div>
                    <div class="total-value" id="totalValue">R$ 0,00</div>
                    <div class="small text-muted mt-1" id="totalDetail"></div>
                </div>

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
                            <?php foreach ($itemsToApprove as $item): ?>
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

                <!-- Comentários / Perguntas -->
                <?php if (!empty($comments)): ?>
                <div class="mb-3">
                    <h6 class="small fw-bold mb-2"><i class="bi bi-chat-dots"></i> Conversas sobre este pedido</h6>
                    <?php foreach ($comments as $c): ?>
                    <div class="p-2 mb-1 rounded <?= $c['author_role'] === 'approver' ? 'bg-warning bg-opacity-10 border-start border-warning border-3' : 'bg-info bg-opacity-10 border-start border-info border-3' ?>" style="font-size:0.8rem;">
                        <strong><?= htmlspecialchars($c['author_name']) ?></strong>
                        <span class="text-muted small">(<?= $c['author_role'] === 'approver' ? 'Aprovação' : 'Cotação' ?>) · <?= date('d/m H:i', strtotime($c['created_at'])) ?></span>
                        <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($c['message'])) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Botão de pergunta -->
                <div class="mb-3">
                    <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="collapse" data-bs-target="#commentSection">
                        <i class="bi bi-chat-dots"></i> Fazer Pergunta / Observação
                    </button>
                    <div class="collapse mt-2" id="commentSection">
                        <form method="POST" action="/pedido/aprovacao/comentario/<?= $token ?>">
                            <div class="card card-body p-2">
                                <div class="mb-2">
                                    <input type="text" class="form-control form-control-sm" name="person_name" placeholder="Seu nome *" required>
                                </div>
                                <div class="mb-2">
                                    <textarea class="form-control form-control-sm" name="comment_message" rows="3" placeholder="Escreva sua pergunta ou observação para o responsável pela cotação..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-warning btn-sm w-100"><i class="bi bi-send"></i> Enviar Pergunta</button>
                                <small class="text-muted mt-1">O responsável pela cotação será notificado e poderá responder ou editar o orçamento.</small>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Materiais de Serviço (se aplicável) -->
                <?php if (($order['order_type'] ?? 'material') === 'service'): ?>
                <?php
                $supplierMaterials = \App\Models\PurchaseOrderSupplierMaterial::getByOrder($order['id']);
                $supplierPdfs = \App\Models\PurchaseOrderSupplierPdf::getByOrder($order['id']);
                ?>
                <?php if (!empty($supplierMaterials) || !empty($supplierPdfs)): ?>
                <div class="mb-4">
                    <h6 class="small fw-bold mb-2"><i class="bi bi-file-earmark-pdf text-danger"></i> Materiais de Serviço dos Fornecedores</h6>
                    <?php
                    $matsBySup = [];
                    foreach ($supplierMaterials as $mat) { $matsBySup[$mat['supplier_id']][] = $mat; }
                    $pdfsBySup = [];
                    foreach ($supplierPdfs as $pdf) { $pdfsBySup[$pdf['supplier_id']][] = $pdf; }
                    $allSids = array_unique(array_merge(array_keys($matsBySup), array_keys($pdfsBySup)));
                    ?>
                    <?php foreach ($allSids as $supId): ?>
                    <div class="border rounded p-2 mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="small">
                                <?php
                                $supName = '-';
                                foreach ($orderSuppliers as $os) { if ($os['supplier_id'] == $supId) { $supName = $os['supplier_name']; break; } }
                                echo htmlspecialchars($supName);
                                ?>
                            </strong>
                            <?php if (!empty($pdfsBySup[$supId])): ?>
                            <a href="<?= htmlspecialchars($pdfsBySup[$supId][0]['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-danger py-0 px-1">
                                <i class="bi bi-file-pdf"></i> PDF
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($matsBySup[$supId])): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0" style="font-size:0.72rem;">
                                <thead class="table-light"><tr><th>Material</th><th>Unid.</th><th class="text-center">Qtd</th><th class="text-end">Unit.</th><th class="text-end">Total</th></tr></thead>
                                <tbody>
                                <?php foreach ($matsBySup[$supId] as $mat): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($mat['material_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($mat['unit'] ?? '-') ?></td>
                                    <td class="text-center"><?= $mat['quantity'] ? number_format($mat['quantity'], $mat['quantity'] == (int)$mat['quantity'] ? 0 : 2) : '-' ?></td>
                                    <td class="text-end"><?= $mat['unit_price'] ? 'R$ ' . number_format($mat['unit_price'], 2, ',', '.') : '-' ?></td>
                                    <td class="text-end fw-bold"><?= $mat['total_price'] ? 'R$ ' . number_format($mat['total_price'], 2, ',', '.') : '-' ?></td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <!-- Formulário de decisão -->
                <form method="POST" action="/pedido/aprovacao/enviar/<?= $token ?>" id="approvalForm">
                    <!-- Inputs ocultos para seleção por item (preenchidos via JS) -->
                    <div id="hiddenInputs"></div>

                    <h6 class="mb-3"><i class="bi bi-person-check"></i> Sua Decisão</h6>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Seu Nome *</label>
                            <input type="text" class="form-control" name="person_name" required placeholder="Informe seu nome completo" value="<?= htmlspecialchars($pinUser['name'] ?? '') ?>" <?= !empty($pinUser) ? 'readonly' : '' ?>>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="approval_notes" rows="2" placeholder="Observações (obrigatório em caso de rejeição)"></textarea>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-center pt-3">
                        <button type="submit" name="action" value="approve" class="btn btn-success btn-lg px-4 px-md-5" onclick="return confirmApproval()">
                            <i class="bi bi-check-circle"></i> Aprovar
                        </button>
                        <button type="submit" name="action" value="reject" class="btn btn-outline-danger btn-lg px-4 px-md-5" onclick="return confirm('Confirma a REJEIÇÃO de TODOS os fornecedores deste pedido?')">
                            <i class="bi bi-x-circle"></i> Rejeitar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php
    // Preparar dados financeiros para JS
    $supplierFinancialsJs = [];
    foreach ($orderSuppliers ?? [] as $os) {
        $supplierFinancialsJs[$os['supplier_id']] = [
            'subtotal_items' => (float)($os['subtotal_items'] ?? 0),
            'discount_value' => (float)($os['discount_value'] ?? 0),
            'discount_type' => $os['discount_type'] ?? 'percent',
            'surcharge_value' => (float)($os['surcharge_value'] ?? 0),
            'surcharge_type' => $os['surcharge_type'] ?? 'percent',
            'ipi_percent' => (float)($os['ipi_percent'] ?? 0),
            'icms_percent' => (float)($os['icms_percent'] ?? 0),
            'freight' => (float)($os['freight'] ?? 0),
            'total' => (float)($os['subtotal_final'] ?? $os['total'] ?? 0),
        ];
    }
    ?>
    <script>
    // Dados dos preços por item/fornecedor (para cálculo de total)
    const priceData = <?= json_encode($pricesByItem ?? []) ?>;
    const supplierNames = <?= json_encode(array_column($orderSuppliers ?? [], 'supplier_name', 'supplier_id')) ?>;
    const itemIds = <?= json_encode(array_column($items ?? [], 'id')) ?>;
    const supplierFinancials = <?= json_encode($supplierFinancialsJs) ?>;

    // Estado: qual fornecedor está selecionado para cada item
    const selections = {};

    function selectItemSupplier(itemId, supplierId) {
        // Toggle: se já está selecionado o mesmo fornecedor, desseleciona
        if (selections[itemId] === supplierId) {
            delete selections[itemId];

            // Remover highlights
            document.querySelectorAll('[id^="opt-' + itemId + '-"]').forEach(el => el.classList.remove('selected'));
            document.querySelectorAll('[id^="map-cell-' + itemId + '-"]').forEach(el => el.classList.remove('selected'));

            const itemCard = document.getElementById('item-card-' + itemId);
            if (itemCard) itemCard.style.borderColor = '#dee2e6';

            updateTotal();
            updateHiddenInputs();
            return;
        }

        selections[itemId] = supplierId;

        // Highlight na lista
        document.querySelectorAll('[id^="opt-' + itemId + '-"]').forEach(el => el.classList.remove('selected'));
        const opt = document.getElementById('opt-' + itemId + '-' + supplierId);
        if (opt) opt.classList.add('selected');

        // Highlight no mapa
        document.querySelectorAll('[id^="map-cell-' + itemId + '-"]').forEach(el => el.classList.remove('selected'));
        const cell = document.getElementById('map-cell-' + itemId + '-' + supplierId);
        if (cell) cell.classList.add('selected');

        // Highlight no card do item (borda verde se selecionado)
        const itemCard = document.getElementById('item-card-' + itemId);
        if (itemCard) {
            itemCard.style.borderColor = '#28a745';
        }

        updateTotal();
        updateHiddenInputs();
    }

    function selectAllFromSupplier(supplierId) {
        itemIds.forEach(function(itemId) {
            if (priceData[itemId] && priceData[itemId][supplierId]) {
                selectItemSupplier(itemId, supplierId);
            }
        });
    }
    function updateTotal() {
        let subtotalInsumos = 0;
        let count = 0;
        const supplierItemTotals = {};

        for (const itemId in selections) {
            const sid = selections[itemId];
            if (priceData[itemId] && priceData[itemId][sid]) {
                const price = parseFloat(priceData[itemId][sid]['total_price']) || 0;
                subtotalInsumos += price;
                count++;
                if (!supplierItemTotals[sid]) supplierItemTotals[sid] = 0;
                supplierItemTotals[sid] += price;
            }
        }

        const totalEl = document.getElementById('totalValue');
        const detailEl = document.getElementById('totalDetail');

        if (count === 0) {
            totalEl.textContent = 'R$ 0,00';
            detailEl.innerHTML = '<span class="text-muted">Selecione os fornecedores para cada item</span>';
            return;
        }

        // Se todos os itens são do mesmo fornecedor, usar os financeiros desse fornecedor
        const uniqueSuppliers = Object.keys(supplierItemTotals);
        let totalFinal = subtotalInsumos;
        let detailParts = [];

        detailParts.push('<span>Insumos: <strong>R$ ' + fmtMoney(subtotalInsumos) + '</strong></span>');

        if (uniqueSuppliers.length === 1) {
            const sid = uniqueSuppliers[0];
            const fin = supplierFinancials[sid];

            if (fin) {
                // Desconto
                if (fin.discount_value > 0) {
                    let discountAmt = fin.discount_type === 'percent' ? subtotalInsumos * (fin.discount_value / 100) : fin.discount_value;
                    totalFinal -= discountAmt;
                    detailParts.push('<span>Desconto: <strong>-' + fin.discount_value + (fin.discount_type === 'percent' ? '%' : ' R$') + ' (-R$ ' + fmtMoney(discountAmt) + ')</strong></span>');
                }
                // Acréscimo
                if (fin.surcharge_value > 0) {
                    let surchargeAmt = fin.surcharge_type === 'percent' ? subtotalInsumos * (fin.surcharge_value / 100) : fin.surcharge_value;
                    totalFinal += surchargeAmt;
                    detailParts.push('<span>Acréscimo: <strong>+' + fin.surcharge_value + (fin.surcharge_type === 'percent' ? '%' : ' R$') + ' (+R$ ' + fmtMoney(surchargeAmt) + ')</strong></span>');
                }
                // IPI
                if (fin.ipi_percent > 0) {
                    let ipiAmt = subtotalInsumos * (fin.ipi_percent / 100);
                    totalFinal += ipiAmt;
                    detailParts.push('<span>IPI: <strong>' + fin.ipi_percent + '% (+R$ ' + fmtMoney(ipiAmt) + ')</strong></span>');
                }
                // ICMS
                if (fin.icms_percent > 0) {
                    let icmsAmt = subtotalInsumos * (fin.icms_percent / 100);
                    totalFinal += icmsAmt;
                    detailParts.push('<span>ICMS: <strong>' + fin.icms_percent + '% (+R$ ' + fmtMoney(icmsAmt) + ')</strong></span>');
                }
                // Frete
                if (fin.freight > 0) {
                    totalFinal += fin.freight;
                    detailParts.push('<span>Frete: <strong>+R$ ' + fmtMoney(fin.freight) + '</strong></span>');
                }
            }
        } else {
            // Múltiplos fornecedores selecionados — somar financeiros proporcionalmente
            for (const sid of uniqueSuppliers) {
                const fin = supplierFinancials[sid];
                const itemsTotal = supplierItemTotals[sid];
                if (fin && itemsTotal > 0) {
                    if (fin.discount_value > 0) {
                        let amt = fin.discount_type === 'percent' ? itemsTotal * (fin.discount_value / 100) : fin.discount_value;
                        totalFinal -= amt;
                    }
                    if (fin.surcharge_value > 0) {
                        let amt = fin.surcharge_type === 'percent' ? itemsTotal * (fin.surcharge_value / 100) : fin.surcharge_value;
                        totalFinal += amt;
                    }
                    if (fin.ipi_percent > 0) totalFinal += itemsTotal * (fin.ipi_percent / 100);
                    if (fin.icms_percent > 0) totalFinal += itemsTotal * (fin.icms_percent / 100);
                    if (fin.freight > 0) totalFinal += fin.freight;
                }
            }
            // Mostrar resumo por fornecedor
            for (const sid of uniqueSuppliers) {
                const name = supplierNames[sid] || 'Fornecedor';
                detailParts.push('<span>' + name + ': R$ ' + fmtMoney(supplierItemTotals[sid]) + '</span>');
            }
        }

        totalEl.textContent = 'R$ ' + fmtMoney(totalFinal);
        detailEl.innerHTML = detailParts.join('<br>') + '<br><span class="text-muted">(' + count + '/' + itemIds.length + ' itens selecionados)</span>';
    }

    function fmtMoney(value) {
        return value.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function updateHiddenInputs() {
        const container = document.getElementById('hiddenInputs');
        container.innerHTML = '';
        for (const itemId in selections) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'item_suppliers[' + itemId + ']';
            input.value = selections[itemId];
            container.appendChild(input);
        }
    }
    function confirmApproval() {
        const totalItems = itemIds.length;
        const selectedCount = Object.keys(selections).length;

        if (totalItems > 0 && selectedCount === 0) {
            alert('Selecione o fornecedor para pelo menos um item.');
            return false;
        }

        if (selectedCount < totalItems) {
            if (!confirm('Atenção: ' + (totalItems - selectedCount) + ' item(ns) ainda não têm fornecedor selecionado. Deseja aprovar apenas os itens selecionados?')) {
                return false;
            }
        }

        return confirm('Confirma a APROVAÇÃO deste pedido com os fornecedores selecionados?');
    }

    // Toggle visualização Lista / Mapa
    function setApprovalView(mode) {
        const btnList = document.getElementById('btnApprovalList');
        const btnMap = document.getElementById('btnApprovalMap');
        const listView = document.getElementById('approvalListView');
        const mapView = document.getElementById('approvalMap');

        if (!btnList || !btnMap || !listView || !mapView) return;

        btnList.classList.toggle('active', mode === 'list');
        btnMap.classList.toggle('active', mode === 'map');

        if (mode === 'map') {
            listView.style.display = 'none';
            mapView.style.display = 'block';
        } else {
            listView.style.display = 'block';
            mapView.style.display = 'none';
        }
    }

    // Inicializar: mobile começa em Lista, desktop em Mapa
    document.addEventListener('DOMContentLoaded', function() {
        const mapView = document.getElementById('approvalMap');
        const listView = document.getElementById('approvalListView');

        if (!mapView && listView) {
            listView.style.display = 'block';
            return;
        }

        if (mapView && listView) {
            const isMobile = window.innerWidth <= 768;
            setApprovalView(isMobile ? 'list' : 'map');
        } else if (listView) {
            listView.style.display = 'block';
        }
    });
    </script>
</body>
</html>
