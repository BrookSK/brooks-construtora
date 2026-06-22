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
        .supplier-compare .supplier-total { font-size: 1.2rem; font-weight: 700; }
        .supplier-item-row { padding: 0.5rem 0; border-bottom: 1px solid #f0f0f0; font-size: 0.78rem; }
        .supplier-item-row:last-child { border-bottom: none; }
        .supplier-item-name { color: #333; line-height: 1.3; }
        .supplier-item-price { font-weight: 600; color: #333; }
        #approvalMap { background: #fff; border-radius: 8px; border: 1px solid #dee2e6; padding: 0.75rem; }
        #approvalMap table th { font-size: 0.7rem; white-space: nowrap; vertical-align: middle; }
        #approvalMap table td { vertical-align: middle; font-size: 0.75rem; }
        .map-supplier-header { cursor: pointer; transition: background 0.2s; min-width: 110px; }
        .map-supplier-header:hover { background: #e8f5e9 !important; }
        .map-supplier-header.selected { background: #c8e6c9 !important; }
        .financial-detail { border: 2px solid #e9ecef; border-radius: 6px; padding: 0.5rem 0.75rem; margin-bottom: 0.5rem; background: #f8f9fa; cursor: pointer; transition: border-color 0.2s, background 0.2s; }
        .financial-detail:hover { border-color: #28a745; }
        @media (min-width: 769px) {
            .supplier-item-row { display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; }
            .supplier-item-name { flex: 1; min-width: 0; }
            .supplier-item-price { white-space: nowrap; text-align: right; flex-shrink: 0; }
        }
        @media (max-width: 768px) {
            .main-card .card-body, .main-card .card-header { padding: 0.75rem; }
            .page-header h4 { font-size: 1.1rem; }
            .supplier-compare { padding: 0.75rem; }
            .supplier-compare .supplier-total { font-size: 1rem; }
            .supplier-item-row { font-size: 0.72rem; }
            .supplier-item-price { display: block; text-align: right; margin-top: 2px; font-size: 0.72rem; }
            .btn-lg { font-size: 0.85rem; padding: 0.6rem 1rem; }
            input, select, textarea { font-size: 16px !important; }
            #approvalMap { padding: 0.5rem; }
            #approvalMap table { border-collapse: separate; border-spacing: 0; }
            #approvalMap table th { font-size: 0.65rem; padding: 0.4rem 0.35rem; line-height: 1.3; }
            #approvalMap table td { font-size: 0.7rem; padding: 0.4rem 0.35rem; line-height: 1.3; }
            #approvalMap table td[style*="sticky"] { max-width: 120px; overflow: hidden; text-overflow: ellipsis; }
            .map-supplier-header { min-width: 90px; padding: 0.4rem 0.3rem !important; }
            .map-supplier-header small { font-size: 0.65rem; }
            .financial-detail { padding: 0.6rem; font-size: 0.75rem; margin-bottom: 0.6rem; }
            .financial-detail .badge { font-size: 0.7rem; }
            .view-toggle-wrap .btn { font-size: 0.8rem; padding: 0.35rem 0.75rem; }
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
                        <h5 class="mb-1">Pedido <strong><?= htmlspecialchars($order['code']) ?></strong></h5>
                        <p class="mb-0 text-muted small">Solicitado por: <?= htmlspecialchars($order['created_by_name']) ?></p>
                        <p class="mb-0 text-muted small">Cotado por: <strong><?= htmlspecialchars($order['quoted_by_name']) ?></strong> em <?= date('d/m/Y H:i', strtotime($order['quoted_at'])) ?></p>
                    </div>
                    <span class="badge bg-info text-white p-2">Aguardando Aprovação</span>
                </div>
            </div>

            <div class="card-body p-3 p-md-4">
                <?php if (!empty($order['description'])): ?>
                <div class="alert alert-light small mb-2"><strong>Obs pedido:</strong> <?= nl2br(htmlspecialchars($order['description'])) ?></div>
                <?php endif; ?>
                <?php if (!empty($order['quote_notes'])): ?>
                <div class="alert alert-warning small mb-2"><strong>Obs cotação:</strong> <?= nl2br(htmlspecialchars($order['quote_notes'])) ?></div>
                <?php endif; ?>

                <?php if (!empty($orderSuppliers)): ?>
                <!-- Multi-fornecedor: Comparação -->
                <h6 class="mb-2"><i class="bi bi-building"></i> Fornecedores Cotados — Selecione o aprovado</h6>

                <?php
                // Agrupar preços por fornecedor
                $pricesBySupplier = [];
                foreach ($itemPrices as $p) {
                    $pricesBySupplier[$p['supplier_id']][$p['item_id']] = $p;
                }
                ?>

                <!-- Toggle de visualização (disponível em todas as telas) -->
                <?php if (count($orderSuppliers) >= 2): ?>
                <div class="mb-3 view-toggle-wrap">
                    <div class="btn-group btn-group-sm w-100">
                        <button type="button" class="btn btn-outline-secondary" id="btnApprovalList" onclick="setApprovalView('list')"><i class="bi bi-list"></i> Lista</button>
                        <button type="button" class="btn btn-outline-secondary active" id="btnApprovalMap" onclick="setApprovalView('map')"><i class="bi bi-table"></i> Mapa</button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Visualização Lista -->
                <div id="approvalListView" style="display:none;">
                <?php foreach ($orderSuppliers as $os): ?>
                <div class="supplier-compare" onclick="selectSupplier(<?= $os['supplier_id'] ?>)" id="supplier-card-<?= $os['supplier_id'] ?>">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center">
                            <input type="radio" name="approved_supplier_id" value="<?= $os['supplier_id'] ?>" id="radio-<?= $os['supplier_id'] ?>" form="approvalForm" class="form-check-input me-2 flex-shrink-0">
                            <label for="radio-<?= $os['supplier_id'] ?>" class="fw-bold mb-0"><?= htmlspecialchars($os['supplier_name']) ?></label>
                        </div>
                        <span class="supplier-total text-success"><?= 'R$ ' . number_format($os['total'] ?? 0, 2, ',', '.') ?></span>
                    </div>

                    <!-- Info do vendedor/financeiro -->
                    <?php if (!empty($os['vendor_name']) || !empty($os['delivery_days']) || (!empty($os['discount_value']) && $os['discount_value'] > 0)): ?>
                    <div class="d-flex flex-wrap gap-2 mb-2" style="font-size:0.7rem; color:#6c757d;">
                        <?php if (!empty($os['vendor_name'])): ?>
                        <span><i class="bi bi-person"></i> <?= htmlspecialchars($os['vendor_name']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($os['delivery_days'])): ?>
                        <span><i class="bi bi-truck"></i> <?= $os['delivery_days'] ?>d</span>
                        <?php endif; ?>
                        <?php if (!empty($os['discount_value']) && $os['discount_value'] > 0): ?>
                        <span><i class="bi bi-arrow-down"></i> Desc: <?= $os['discount_value'] ?><?= $os['discount_type'] === 'percent' ? '%' : ' R$' ?></span>
                        <?php endif; ?>
                        <?php if (!empty($os['freight']) && $os['freight'] > 0): ?>
                        <span><i class="bi bi-box-seam"></i> Frete: R$ <?= number_format($os['freight'], 2, ',', '.') ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Detalhes dos itens deste fornecedor -->
                    <div>
                        <?php if (isset($pricesBySupplier[$os['supplier_id']])): ?>
                        <?php foreach ($items as $item): ?>
                            <?php $p = $pricesBySupplier[$os['supplier_id']][$item['id']] ?? null; ?>
                            <?php if ($p): ?>
                            <div class="supplier-item-row">
                                <span class="supplier-item-name"><?= htmlspecialchars($item['material_name']) ?> (x<?= number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2) ?>)</span>
                                <span class="supplier-item-price">R$ <?= number_format($p['unit_price'], 2, ',', '.') ?> = <strong>R$ <?= number_format($p['total_price'], 2, ',', '.') ?></strong></span>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>

                <!-- Visualização Mapa (colunas lado a lado) -->
                <?php if (count($orderSuppliers) >= 2): ?>
                <div id="approvalMap" class="mb-3" style="display:none;">
                    <p class="text-muted small mb-2"><i class="bi bi-hand-index"></i> Toque no fornecedor para selecionar.</p>
                    <div style="overflow-x:auto; -webkit-overflow-scrolling:touch; margin: 0 -0.5rem; padding: 0 0.5rem;">
                    <table class="table table-sm table-bordered mb-0" style="min-width:500px;">
                        <thead>
                            <tr class="table-dark">
                                <th style="min-width:160px; position:sticky; left:0; background:#212529; z-index:1;">Material</th>
                                <th class="text-center" style="width:45px;">Qtd</th>
                                <?php foreach ($orderSuppliers as $os): ?>
                                <th class="text-center map-supplier-header" onclick="selectSupplier(<?= $os['supplier_id'] ?>)" id="map-header-<?= $os['supplier_id'] ?>">
                                    <?= htmlspecialchars($os['supplier_name']) ?>
                                    <br><small class="text-success fw-bold"><?= 'R$ ' . number_format($os['total'] ?? 0, 2, ',', '.') ?></small>
                                </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td style="position:sticky; left:0; background:#fff; z-index:1;">
                                    <strong style="font-size:0.75rem;"><?= htmlspecialchars($item['material_name']) ?></strong>
                                </td>
                                <td class="text-center"><?= number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2) ?></td>
                                <?php foreach ($orderSuppliers as $os): ?>
                                <?php $p = $pricesBySupplier[$os['supplier_id']][$item['id']] ?? null; ?>
                                <td class="text-center">
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
                        <tfoot>
                            <tr class="table-success fw-bold">
                                <td style="position:sticky; left:0; background:#d1e7dd; z-index:1;">TOTAL</td>
                                <td></td>
                                <?php foreach ($orderSuppliers as $os): ?>
                                <td class="text-center">R$ <?= number_format($os['total'] ?? 0, 2, ',', '.') ?></td>
                                <?php endforeach; ?>
                            </tr>
                        </tfoot>
                    </table>
                    </div>

                    <!-- Detalhes financeiros por fornecedor (com seleção) -->
                    <div class="mt-3">
                        <p class="text-muted small mb-2"><i class="bi bi-hand-index"></i> Selecione o fornecedor aprovado:</p>
                        <?php foreach ($orderSuppliers as $os): ?>
                        <div class="financial-detail" onclick="selectSupplier(<?= $os['supplier_id'] ?>)" id="map-card-<?= $os['supplier_id'] ?>" style="cursor:pointer; transition: border-color 0.2s, background 0.2s;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div class="d-flex align-items-center">
                                    <input type="radio" name="approved_supplier_id_map" value="<?= $os['supplier_id'] ?>" id="radio-map-<?= $os['supplier_id'] ?>" class="form-check-input me-2 flex-shrink-0" onchange="selectSupplier(<?= $os['supplier_id'] ?>)">
                                    <strong class="small"><i class="bi bi-building"></i> <?= htmlspecialchars($os['supplier_name']) ?></strong>
                                </div>
                                <span class="badge bg-success">R$ <?= number_format($os['total'] ?? 0, 2, ',', '.') ?></span>
                            </div>
                            <div class="d-flex flex-wrap gap-2" style="font-size:0.75rem; color:#6c757d;">
                                <?php if (!empty($os['vendor_name'])): ?>
                                <span><i class="bi bi-person"></i> <?= htmlspecialchars($os['vendor_name']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($os['vendor_phone'])): ?>
                                <span><i class="bi bi-telephone"></i> <?= htmlspecialchars($os['vendor_phone']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($os['vendor_email'])): ?>
                                <span><i class="bi bi-envelope"></i> <?= htmlspecialchars($os['vendor_email']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($os['delivery_days'])): ?>
                                <span><i class="bi bi-truck"></i> <?= $os['delivery_days'] ?> dias</span>
                                <?php endif; ?>
                                <?php if (!empty($os['discount_value']) && $os['discount_value'] > 0): ?>
                                <span><i class="bi bi-arrow-down-circle"></i> Desc: <?= $os['discount_value'] ?><?= ($os['discount_type'] ?? 'percent') === 'percent' ? '%' : ' R$' ?></span>
                                <?php endif; ?>
                                <?php if (!empty($os['surcharge_value']) && $os['surcharge_value'] > 0): ?>
                                <span><i class="bi bi-arrow-up-circle"></i> Acrésc: <?= $os['surcharge_value'] ?><?= ($os['surcharge_type'] ?? 'percent') === 'percent' ? '%' : ' R$' ?></span>
                                <?php endif; ?>
                                <?php if (!empty($os['ipi_percent']) && $os['ipi_percent'] > 0): ?>
                                <span>IPI: <?= $os['ipi_percent'] ?>%</span>
                                <?php endif; ?>
                                <?php if (!empty($os['icms_percent']) && $os['icms_percent'] > 0): ?>
                                <span>ICMS: <?= $os['icms_percent'] ?>%</span>
                                <?php endif; ?>
                                <?php if (!empty($os['freight']) && $os['freight'] > 0): ?>
                                <span><i class="bi bi-box-seam"></i> Frete: R$ <?= number_format($os['freight'], 2, ',', '.') ?></span>
                                <?php endif; ?>
                                <?php if (empty($os['vendor_name']) && empty($os['delivery_days']) && (empty($os['discount_value']) || $os['discount_value'] == 0) && (empty($os['freight']) || $os['freight'] == 0)): ?>
                                <span class="text-muted fst-italic">Sem dados adicionais</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

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
    <script>
    function selectSupplier(sid) {
        // Highlight list view
        document.querySelectorAll('.supplier-compare').forEach(el => el.classList.remove('selected'));
        const card = document.getElementById('supplier-card-' + sid);
        if (card) card.classList.add('selected');
        
        // Check radio (list)
        const radio = document.getElementById('radio-' + sid);
        if (radio) radio.checked = true;

        // Check radio (map cards)
        const radioMap = document.getElementById('radio-map-' + sid);
        if (radioMap) radioMap.checked = true;

        // Highlight map headers
        document.querySelectorAll('.map-supplier-header').forEach(el => el.classList.remove('selected'));
        const mapHeader = document.getElementById('map-header-' + sid);
        if (mapHeader) mapHeader.classList.add('selected');

        // Highlight map financial cards
        document.querySelectorAll('.financial-detail').forEach(el => {
            el.style.borderColor = '#e9ecef';
            el.style.background = '#f8f9fa';
        });
        const mapCard = document.getElementById('map-card-' + sid);
        if (mapCard) {
            mapCard.style.borderColor = '#28a745';
            mapCard.style.background = '#f0fff4';
        }
    }

    function confirmApproval() {
        const hasSuppliers = document.querySelectorAll('.supplier-compare').length > 0;
        if (hasSuppliers) {
            const selected = document.querySelector('input[name="approved_supplier_id"]:checked') || document.querySelector('input[name="approved_supplier_id_map"]:checked');
            if (!selected) {
                alert('Selecione qual fornecedor está aprovando.');
                return false;
            }
            // Garantir que o radio do form principal está checado
            if (!document.querySelector('input[name="approved_supplier_id"]:checked')) {
                const mapRadio = document.querySelector('input[name="approved_supplier_id_map"]:checked');
                if (mapRadio) {
                    const listRadio = document.getElementById('radio-' + mapRadio.value);
                    if (listRadio) listRadio.checked = true;
                }
            }
        }
        return confirm('Confirma a APROVAÇÃO deste pedido?');
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
        
        // Se não tem mapa (apenas 1 fornecedor), mostra lista direto
        if (!mapView && listView) {
            listView.style.display = 'block';
            return;
        }
        
        if (mapView && listView) {
            const isMobile = window.innerWidth <= 768;
            if (isMobile) {
                setApprovalView('list');
            } else {
                setApprovalView('map');
            }
        }
    });
    </script>
</body>
</html>
