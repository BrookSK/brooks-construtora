<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist de Entrega - <?= htmlspecialchars($order['code']) ?> | Brooks Construtora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; min-height: 100vh; padding-bottom: 75px; }
        .page-header { background: #3a3b4e; color: #fff; padding: 0.6rem 0; position: sticky; top: 0; z-index: 100; }
        .delivery-item { border: 1px solid #dee2e6; border-radius: 12px; padding: 1rem; margin-bottom: 0.6rem; background: #fff; transition: all 0.3s; }
        .delivery-item.status-checked, .delivery-item.status-replacement_delivered { border-color: #28a745; background: #f0fff4; }
        .delivery-item.status-delivered { border-color: #0d6efd; background: #f0f4ff; }
        .delivery-item.status-divergence { border-color: #dc3545; background: #fff5f5; }
        .delivery-item.status-replacement_requested { border-color: #ffc107; background: #fffdf0; }
        .delivery-item.late { border-color: #dc3545 !important; box-shadow: 0 0 0 2px rgba(220,53,69,0.15); }
        .supplier-group { margin-bottom: 1.2rem; }
        .supplier-header { background: linear-gradient(135deg, #3a3b4e, #4a4b5e); color: #fff; border-radius: 10px; padding: 0.7rem 1rem; margin-bottom: 0.5rem; }
        .action-buttons { display: flex; flex-direction: column; gap: 0.4rem; width: 100%; margin-top: 0.6rem; }
        .action-btn { border-radius: 8px; padding: 0.6rem 1rem; font-size: 0.8rem; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 0.4rem; width: 100%; }
        .undo-btn { font-size: 0.68rem; padding: 0.3rem 0.6rem; border-radius: 6px; opacity: 0.7; }
        .undo-btn:hover { opacity: 1; }
        .name-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; border-top: 2px solid #3a3b4e; padding: 0.6rem 1rem; z-index: 100; box-shadow: 0 -4px 12px rgba(0,0,0,0.1); }
        .toast-container { position: fixed; top: 70px; right: 10px; z-index: 200; }
        .history-item { font-size: 0.75rem; padding: 0.5rem 0; border-bottom: 1px solid #f0f0f0; }
        .info-pill { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 20px; padding: 0.3rem 0.7rem; font-size: 0.72rem; display: inline-flex; align-items: center; gap: 0.3rem; }
        .done-overlay { position: relative; }
        .done-overlay::after { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.4); border-radius: 12px; pointer-events: none; }
        .spin { animation: spin 1s linear infinite; }
        @keyframes spin { from{transform:rotate(0)} to{transform:rotate(360deg)} }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div><strong style="font-size:0.85rem;">BROOKS</strong> <span class="badge bg-light text-dark ms-1" style="font-size:0.7rem;"><?= htmlspecialchars($order['code']) ?></span></div>
                <span class="badge bg-dark bg-opacity-50 small" id="syncStatus"><i class="bi bi-check-circle"></i> Salvo</span>
            </div>
        </div>
    </div>

    <div class="container py-3">
        <!-- Resumo do pedido -->
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-clipboard-check"></i> Checklist de Entrega</h6>
                    <div id="progressBadges" class="d-flex gap-1 flex-wrap"></div>
                </div>
                <div class="progress mb-2" style="height:8px; border-radius:4px;">
                    <div class="progress-bar bg-success" id="progressBarFill" style="width:0%; transition:width 0.5s;"></div>
                </div>
                <div class="d-flex flex-wrap gap-1 mb-1">
                    <?php if (!empty($order['construction_site_name'])): ?>
                    <span class="info-pill"><i class="bi bi-buildings text-dark"></i> <?= htmlspecialchars($order['construction_site_code'] . ' - ' . $order['construction_site_name']) ?></span>
                    <?php endif; ?>
                    <span class="info-pill"><i class="bi bi-box-seam text-primary"></i> <?= count($deliveries) ?> itens</span>
                    <span class="info-pill"><i class="bi bi-cash-stack text-success"></i> R$ <?= number_format($order['total_estimated'] ?? 0, 2, ',', '.') ?></span>
                    <?php if ($order['approved_by_name']): ?><span class="info-pill"><i class="bi bi-person-check text-info"></i> <?= htmlspecialchars($order['approved_by_name']) ?></span><?php endif; ?>
                    <?php if ($order['approved_at']): ?><span class="info-pill"><i class="bi bi-calendar-check"></i> <?= date('d/m/Y', strtotime($order['approved_at'])) ?></span><?php endif; ?>
                </div>
            </div>
        </div>

<?php
$deliveriesBySupplier = [];
foreach ($deliveries as $d) {
    // Separar itens de estoque/transferência dos comprados
    if (!empty($d['source_type']) && $d['source_type'] !== 'purchase') {
        $groupName = $d['source_type'] === 'stock_transfer' ? '↔ Transferência de Estoque' : '✓ Saída de Estoque';
    } else {
        $groupName = $d['supplier_name'] ?? 'Sem fornecedor';
    }
    $deliveriesBySupplier[$groupName][] = $d;
}
$today = date('Y-m-d');
$statusLabelsDelivery = \App\Models\PurchaseOrderDelivery::$statusLabels;
$supplierPaymentInfo = [];
foreach ($orderSuppliers as $os) { if ($os['approved']) $supplierPaymentInfo[$os['supplier_name']] = $os; }
$pmLabels = ['pix'=>'PIX','boleto'=>'Boleto','cartao'=>'Cartão','transferencia'=>'Transf.','dinheiro'=>'Dinheiro','outro'=>'Outro'];
?>

        <div id="deliveryList">
        <?php foreach ($deliveriesBySupplier as $supplierName => $supplierDeliveries): ?>
        <?php $si_info = $supplierPaymentInfo[$supplierName] ?? null; ?>
        <div class="supplier-group">
            <div class="supplier-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                    <div>
                        <i class="bi bi-building"></i> <strong><?= htmlspecialchars($supplierName) ?></strong>
                        <?php if ($supplierDeliveries[0]['expected_date']): ?>
                        <span class="ms-2 opacity-75" style="font-size:0.72rem;"><i class="bi bi-calendar3"></i> Entrega: <?= date('d/m/Y', strtotime($supplierDeliveries[0]['expected_date'])) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($si_info): ?>
                <div class="d-flex flex-wrap gap-2 mt-1" style="font-size:0.68rem; opacity:0.85;">
                    <?php if (!empty($si_info['vendor_name'])): ?><span><i class="bi bi-person"></i> <?= htmlspecialchars($si_info['vendor_name']) ?></span><?php endif; ?>
                    <?php if (!empty($si_info['vendor_phone'])): ?><span><i class="bi bi-telephone"></i> <?= htmlspecialchars($si_info['vendor_phone']) ?></span><?php endif; ?>
                    <?php if (!empty($si_info['payment_method'])): ?><span><i class="bi bi-credit-card"></i> <?= $pmLabels[$si_info['payment_method']] ?? '' ?><?= !empty($si_info['payment_condition']) ? ' · ' . htmlspecialchars($si_info['payment_condition']) : '' ?></span><?php endif; ?>
                    <?php if (!empty($si_info['delivery_days'])): ?><span><i class="bi bi-truck"></i> <?= $si_info['delivery_days'] ?> dias</span><?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php foreach ($supplierDeliveries as $d): ?>
            <?php
            $si = $statusLabelsDelivery[$d['status']] ?? ['?', 'secondary', 'bi-question'];
            $isLate = false;
            if (!in_array($d['status'], ['checked','delivered','replacement_delivered'])) {
                if ($d['expected_date'] && $d['expected_date'] < $today) $isLate = true;
                if ($d['status'] === 'replacement_requested' && $d['replacement_expected_date'] && $d['replacement_expected_date'] < $today) $isLate = true;
            }
            $isDone = in_array($d['status'], ['checked', 'replacement_delivered']);
            ?>
            <div class="delivery-item status-<?= $d['status'] ?> <?= $isLate ? 'late' : '' ?> <?= $isDone ? 'done-overlay' : '' ?>" id="item-<?= $d['id'] ?>" data-id="<?= $d['id'] ?>" data-status="<?= $d['status'] ?>">
                <!-- Info do material -->
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-<?= $si[1] ?>" style="font-size:0.65rem;"><i class="bi <?= $si[2] ?>"></i> <?= $si[0] ?></span>
                    <?php if ($isLate): ?><span class="badge bg-danger" style="font-size:0.6rem;"><i class="bi bi-clock"></i> ATRASADO</span><?php endif; ?>
                </div>
                <div class="fw-bold" style="font-size:0.9rem;">
                    <?= htmlspecialchars($d['material_name']) ?>
                    <?php if (!empty($d['source_type']) && $d['source_type'] !== 'purchase'): ?>
                        <span class="badge bg-<?= $d['source_type'] === 'stock_transfer' ? 'primary' : 'success' ?>" style="font-size:0.6rem; margin-left:4px;">
                            <?= $d['source_type'] === 'stock_transfer' ? '↔ TRANSF.' : '✓ ESTOQUE' ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-1" style="font-size:0.75rem; color:#6c757d;">
                    <span><i class="bi bi-hash"></i> <?= number_format($d['quantity'], $d['quantity'] == (int)$d['quantity'] ? 0 : 2) ?></span>
                    <?php if ($d['expected_date']): ?><span><i class="bi bi-calendar3"></i> <?= date('d/m', strtotime($d['expected_date'])) ?></span><?php endif; ?>
                    <?php if ($d['delivered_at']): ?><span class="text-primary"><i class="bi bi-box-seam"></i> Chegou <?= date('d/m H:i', strtotime($d['delivered_at'])) ?></span><?php endif; ?>
                    <?php if ($d['checked_by']): ?><span class="text-success"><i class="bi bi-person-check"></i> <?= htmlspecialchars($d['checked_by']) ?></span><?php endif; ?>
                </div>

                <?php if ($d['divergence_notes']): ?>
                <div class="mt-2 p-2 rounded" style="font-size:0.75rem; background:#fdeaea; color:#721c24;"><i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($d['divergence_notes']) ?></div>
                <?php endif; ?>
                <?php if ($d['replacement_notes']): ?>
                <div class="mt-2 p-2 rounded" style="font-size:0.75rem; background:#fff3cd; color:#856404;"><i class="bi bi-arrow-repeat"></i> <?= htmlspecialchars($d['replacement_notes']) ?><?php if ($d['replacement_expected_date']): ?> · Nova entrega: <?= date('d/m', strtotime($d['replacement_expected_date'])) ?><?php endif; ?></div>
                <?php endif; ?>

                <!-- Botões de ação -->
                <div class="action-buttons">
                    <?php if ($d['status'] === 'pending'): ?>
                    <button class="btn btn-primary action-btn" onclick="showDeliveredModal(<?= $d['id'] ?>, <?= $d['quantity'] ?>)"><i class="bi bi-box-seam"></i> Marcar como Entregue</button>
                    <?php elseif ($d['status'] === 'delivered'): ?>
                    <button class="btn btn-success action-btn" onclick="doAction(<?= $d['id'] ?>, 'mark_checked')"><i class="bi bi-check-circle"></i> Conferido - Tudo OK</button>
                    <button class="btn btn-outline-danger action-btn" onclick="showDivergence(<?= $d['id'] ?>)"><i class="bi bi-exclamation-triangle"></i> Tem Problema</button>
                    <button class="btn btn-outline-secondary undo-btn" onclick="doAction(<?= $d['id'] ?>, 'reset')"><i class="bi bi-arrow-counterclockwise"></i> Desfazer (não chegou)</button>
                    <?php elseif ($d['status'] === 'divergence'): ?>
                    <button class="btn btn-warning action-btn" onclick="showReplacement(<?= $d['id'] ?>)"><i class="bi bi-arrow-repeat"></i> Solicitar Troca</button>
                    <button class="btn btn-outline-secondary undo-btn" onclick="doAction(<?= $d['id'] ?>, 'reset')"><i class="bi bi-arrow-counterclockwise"></i> Desfazer</button>
                    <?php elseif ($d['status'] === 'replacement_requested'): ?>
                    <button class="btn btn-success action-btn" onclick="doAction(<?= $d['id'] ?>, 'mark_replacement_delivered')"><i class="bi bi-check-all"></i> Troca Recebida - OK</button>
                    <button class="btn btn-outline-secondary undo-btn" onclick="doAction(<?= $d['id'] ?>, 'reset')"><i class="bi bi-arrow-counterclockwise"></i> Desfazer</button>
                    <?php elseif ($isDone): ?>
                    <div class="text-center py-1">
                        <i class="bi bi-check-circle-fill text-success" style="font-size:1.3rem;"></i>
                        <span class="small text-success fw-bold ms-1">Concluído</span>
                    </div>
                    <button class="btn btn-outline-secondary undo-btn" onclick="if(confirm('Desmarcar este item?')) doAction(<?= $d['id'] ?>, 'reset')"><i class="bi bi-arrow-counterclockwise"></i> Desfazer conferência</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        </div>
        <!-- Como usar -->
        <div class="card mt-3 border-0 bg-light">
            <div class="card-body p-3">
                <p class="small mb-2 fw-bold"><i class="bi bi-lightbulb text-warning"></i> Como funciona:</p>
                <div class="row g-2" style="font-size:0.75rem;">
                    <div class="col-6"><div class="p-2 bg-white rounded text-center"><i class="bi bi-box-seam text-primary" style="font-size:1.2rem;"></i><br><strong>1. Chegou</strong><br>Material entregue</div></div>
                    <div class="col-6"><div class="p-2 bg-white rounded text-center"><i class="bi bi-check-circle text-success" style="font-size:1.2rem;"></i><br><strong>2. Conferiu</strong><br>Tudo certo</div></div>
                    <div class="col-6"><div class="p-2 bg-white rounded text-center"><i class="bi bi-exclamation-triangle text-danger" style="font-size:1.2rem;"></i><br><strong>Problema?</strong><br>Veio errado</div></div>
                    <div class="col-6"><div class="p-2 bg-white rounded text-center"><i class="bi bi-arrow-counterclockwise text-secondary" style="font-size:1.2rem;"></i><br><strong>Errou?</strong><br>Pode desfazer</div></div>
                </div>
            </div>
        </div>

        <!-- Histórico -->
        <div class="card mt-3 border-0 shadow-sm">
            <div class="card-header py-2 small fw-bold border-0 bg-white"><i class="bi bi-clock-history"></i> Últimas Ações</div>
            <div class="card-body p-2 pt-0" id="historyList"><p class="text-muted small text-center mb-0">Carregando...</p></div>
        </div>
    </div>

    <!-- Barra de identificação fixa -->
    <div class="name-bar">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-person-circle" style="font-size:1.3rem; color:#3a3b4e;"></i>
            <input type="text" class="form-control form-control-sm" id="personName" placeholder="Seu nome" style="font-weight:600; max-width:180px;">
            <span class="small text-muted" id="nameHint">← Identifique-se</span>
        </div>
    </div>

    <!-- Toast -->
    <div class="toast-container"><div id="toast" class="toast align-items-center text-bg-success border-0" role="alert"><div class="d-flex"><div class="toast-body" id="toastMsg">Salvo!</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div></div></div>

    <!-- Modal Entrega (quantidade) -->
    <div class="modal fade" id="deliveredModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header py-2 bg-primary text-white"><h6 class="modal-title"><i class="bi bi-box-seam"></i> Registrar Entrega</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" id="delId">
            <input type="hidden" id="delExpectedQty">
            <p class="small mb-2">Quantidade pedida: <strong id="delExpectedLabel"></strong></p>
            <div class="mb-3">
                <label class="form-label small fw-bold">Quantidade recebida *</label>
                <input type="number" class="form-control" id="delReceivedQty" step="0.01" min="0" inputmode="decimal" placeholder="Informe a quantidade que chegou">
            </div>
            <div id="delQtyWarning" class="alert alert-warning py-2 small" style="display:none;">
                <i class="bi bi-exclamation-triangle"></i> <span id="delQtyWarningText"></span>
            </div>
        </div>
        <div class="modal-footer py-2 flex-column gap-2">
            <button class="btn btn-primary w-100" onclick="submitDelivered()"><i class="bi bi-box-seam"></i> Confirmar Entrega</button>
            <small class="text-muted">Se a quantidade estiver errada, será registrada como divergência automaticamente.</small>
        </div>
    </div></div></div>

    <!-- Modal Divergência -->
    <div class="modal fade" id="divModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header py-2 bg-danger text-white"><h6 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Qual o problema?</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><input type="hidden" id="divId"><p class="small text-muted mb-2">Descreva o que está errado:</p><textarea class="form-control" id="divNotes" rows="3" placeholder="Ex: Quantidade errada, material danificado, modelo diferente..."></textarea></div>
        <div class="modal-footer py-2"><button class="btn btn-danger w-100" onclick="submitDivergence()"><i class="bi bi-exclamation-triangle"></i> Registrar Problema</button></div>
    </div></div></div>

    <!-- Modal Troca -->
    <div class="modal fade" id="repModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header py-2 bg-warning"><h6 class="modal-title"><i class="bi bi-arrow-repeat"></i> Solicitar Troca</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><input type="hidden" id="repId"><div class="mb-3"><label class="form-label small fw-bold">Previsão de nova entrega</label><input type="date" class="form-control" id="repDate"></div><div><label class="form-label small fw-bold">O que foi combinado?</label><textarea class="form-control" id="repNotes" rows="2" placeholder="Detalhes da troca com o fornecedor..."></textarea></div></div>
        <div class="modal-footer py-2"><button class="btn btn-warning w-100" onclick="submitReplacement()"><i class="bi bi-arrow-repeat"></i> Solicitar Troca</button></div>
    </div></div></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const TOKEN = '<?= $token ?>';
    const BASE = '/pedido/entrega/update/' + TOKEN;
    const DATA_URL = '/pedido/entrega/data/' + TOKEN;
    let toastEl, toastInst;

    document.addEventListener('DOMContentLoaded', function() {
        toastEl = document.getElementById('toast');
        toastInst = new bootstrap.Toast(toastEl, {delay: 2500});
        const saved = localStorage.getItem('delivery_person_name');
        if (saved) { document.getElementById('personName').value = saved; document.getElementById('nameHint').style.display = 'none'; }
        document.getElementById('personName').addEventListener('input', function() {
            localStorage.setItem('delivery_person_name', this.value);
            document.getElementById('nameHint').style.display = this.value ? 'none' : '';
            this.classList.remove('border-danger');
        });
        loadData();
        setInterval(loadData, 8000);
    });

    function getName() {
        const el = document.getElementById('personName');
        const name = el.value.trim();
        if (!name) { el.classList.add('border-danger'); el.focus(); showToast('Informe seu nome primeiro!', 'warning'); return null; }
        return name;
    }

    function showToast(msg, type) {
        toastEl.className = 'toast align-items-center text-bg-' + (type || 'success') + ' border-0';
        document.getElementById('toastMsg').textContent = msg;
        toastInst.show();
    }

    function doAction(id, action, extra) {
        const name = getName();
        if (!name) return;
        const fd = new FormData();
        fd.append('id', id);
        fd.append('delivery_action', action);
        fd.append('performed_by', name);
        if (extra) { for (const k in extra) fd.append(k, extra[k]); }

        document.getElementById('syncStatus').innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Salvando...';
        fetch(BASE, {method:'POST', body:fd})
            .then(r => r.json())
            .then(d => {
                if (d.success) { showToast('✓ Salvo!', 'success'); loadData(); }
                else showToast(d.error || 'Erro', 'danger');
                document.getElementById('syncStatus').innerHTML = '<i class="bi bi-check-circle"></i> Salvo';
            })
            .catch(() => { showToast('Sem conexão', 'danger'); document.getElementById('syncStatus').innerHTML = '<i class="bi bi-exclamation-circle text-warning"></i> Offline'; });
    }

    function showDeliveredModal(id, expectedQty) {
        document.getElementById('delId').value = id;
        document.getElementById('delExpectedQty').value = expectedQty;
        document.getElementById('delExpectedLabel').textContent = expectedQty;
        document.getElementById('delReceivedQty').value = expectedQty;
        document.getElementById('delQtyWarning').style.display = 'none';
        new bootstrap.Modal(document.getElementById('deliveredModal')).show();
        // Listener para avisar se quantidade difere
        document.getElementById('delReceivedQty').oninput = function() {
            const received = parseFloat(this.value) || 0;
            const expected = parseFloat(expectedQty) || 0;
            const warn = document.getElementById('delQtyWarning');
            if (received !== expected && this.value) {
                warn.style.display = '';
                if (received > expected) document.getElementById('delQtyWarningText').textContent = 'Recebeu ' + (received - expected) + ' a mais do que o pedido. Será registrado como divergência.';
                else document.getElementById('delQtyWarningText').textContent = 'Recebeu ' + (expected - received) + ' a menos do que o pedido. Será registrado como divergência.';
            } else { warn.style.display = 'none'; }
        };
    }

    function submitDelivered() {
        const name = getName(); if (!name) return;
        const id = document.getElementById('delId').value;
        const expected = parseFloat(document.getElementById('delExpectedQty').value) || 0;
        const received = parseFloat(document.getElementById('delReceivedQty').value) || 0;
        if (!received) { alert('Informe a quantidade recebida.'); return; }

        bootstrap.Modal.getInstance(document.getElementById('deliveredModal')).hide();

        if (received === expected) {
            // Quantidade correta — marca como entregue normalmente
            doAction(id, 'mark_delivered', {received_quantity: received});
        } else {
            // Quantidade diferente — marca como entregue + divergência automática
            const diff = received > expected ? (received - expected) + ' a mais' : (expected - received) + ' a menos';
            const notes = 'Quantidade divergente: pedido ' + expected + ', recebeu ' + received + ' (' + diff + ')';
            doAction(id, 'mark_delivered_divergence', {received_quantity: received, divergence_notes: notes});
        }
    }

    function showDivergence(id) { document.getElementById('divId').value = id; document.getElementById('divNotes').value = ''; new bootstrap.Modal(document.getElementById('divModal')).show(); }
    function submitDivergence() {
        const notes = document.getElementById('divNotes').value.trim();
        if (!notes) { alert('Descreva o problema.'); return; }
        const name = getName(); if (!name) return;
        bootstrap.Modal.getInstance(document.getElementById('divModal')).hide();
        doAction(document.getElementById('divId').value, 'mark_divergence', {divergence_notes: notes});
    }
    function showReplacement(id) { document.getElementById('repId').value = id; document.getElementById('repDate').value = ''; document.getElementById('repNotes').value = ''; new bootstrap.Modal(document.getElementById('repModal')).show(); }
    function submitReplacement() {
        const name = getName(); if (!name) return;
        bootstrap.Modal.getInstance(document.getElementById('repModal')).hide();
        doAction(document.getElementById('repId').value, 'request_replacement', {replacement_expected_date: document.getElementById('repDate').value, replacement_notes: document.getElementById('repNotes').value});
    }

    function loadData() {
        fetch(DATA_URL).then(r => r.json()).then(data => {
            if (data.deliveries) updateUI(data.deliveries);
            if (data.history) updateHistory(data.history);
        }).catch(() => {});
    }

    function updateUI(deliveries) {
        const SL = {pending:['Pendente','secondary','bi-clock'],delivered:['Entregue','primary','bi-box-seam'],checked:['Conferido','success','bi-check-circle-fill'],divergence:['Divergência','danger','bi-exclamation-triangle'],replacement_requested:['Troca Solic.','warning','bi-arrow-repeat'],replacement_delivered:['Troca OK','success','bi-check-all']};
        const today = new Date().toISOString().split('T')[0];
        let counts = {};

        deliveries.forEach(d => {
            counts[d.status] = (counts[d.status] || 0) + 1;
            const el = document.getElementById('item-' + d.id);
            if (!el) return;
            const si = SL[d.status] || ['?','secondary','bi-question'];
            const isLate = !['checked','delivered','replacement_delivered'].includes(d.status) && ((d.expected_date && d.expected_date < today) || (d.status === 'replacement_requested' && d.replacement_expected_date && d.replacement_expected_date < today));
            const isDone = ['checked','replacement_delivered'].includes(d.status);

            el.className = 'delivery-item status-' + d.status + (isLate ? ' late' : '') + (isDone ? ' done-overlay' : '');

            // Atualizar botões com texto
            let btns = '';
            if (d.status === 'pending') btns = '<button class="btn btn-primary action-btn" onclick="showDeliveredModal('+d.id+','+d.quantity+')"><i class="bi bi-box-seam"></i> Marcar como Entregue</button>';
            else if (d.status === 'delivered') btns = '<button class="btn btn-success action-btn" onclick="doAction('+d.id+',\'mark_checked\')"><i class="bi bi-check-circle"></i> Conferido - Tudo OK</button><button class="btn btn-outline-danger action-btn" onclick="showDivergence('+d.id+')"><i class="bi bi-exclamation-triangle"></i> Tem Problema</button><button class="btn btn-outline-secondary undo-btn" onclick="doAction('+d.id+',\'reset\')"><i class="bi bi-arrow-counterclockwise"></i> Desfazer (não chegou)</button>';
            else if (d.status === 'divergence') btns = '<button class="btn btn-warning action-btn" onclick="showReplacement('+d.id+')"><i class="bi bi-arrow-repeat"></i> Solicitar Troca</button><button class="btn btn-outline-secondary undo-btn" onclick="doAction('+d.id+',\'reset\')"><i class="bi bi-arrow-counterclockwise"></i> Desfazer</button>';
            else if (d.status === 'replacement_requested') btns = '<button class="btn btn-success action-btn" onclick="doAction('+d.id+',\'mark_replacement_delivered\')"><i class="bi bi-check-all"></i> Troca Recebida - OK</button><button class="btn btn-outline-secondary undo-btn" onclick="doAction('+d.id+',\'reset\')"><i class="bi bi-arrow-counterclockwise"></i> Desfazer</button>';
            else btns = '<div class="text-center py-1"><i class="bi bi-check-circle-fill text-success" style="font-size:1.3rem;"></i> <span class="small text-success fw-bold">Concluído</span></div><button class="btn btn-outline-secondary undo-btn" onclick="if(confirm(\'Desmarcar?\'))doAction('+d.id+',\'reset\')"><i class="bi bi-arrow-counterclockwise"></i> Desfazer conferência</button>';

            const btnC = el.querySelector('.action-buttons');
            if (btnC) btnC.innerHTML = btns;

            // Atualizar badge status
            const badgeC = el.querySelector('.d-flex.align-items-center.gap-2');
            if (badgeC) {
                let b = '<span class="badge bg-'+si[1]+'" style="font-size:0.65rem;"><i class="bi '+si[2]+'"></i> '+si[0]+'</span>';
                if (isLate) b += '<span class="badge bg-danger" style="font-size:0.6rem;"><i class="bi bi-clock"></i> ATRASADO</span>';
                badgeC.innerHTML = b;
            }
        });

        // Progress bar e badges
        const total = deliveries.length;
        const done = (counts.checked || 0) + (counts.replacement_delivered || 0);
        const pct = total > 0 ? Math.round((done / total) * 100) : 0;
        document.getElementById('progressBarFill').style.width = pct + '%';

        let html = '<span class="badge bg-success" style="font-size:0.68rem;">' + done + '/' + total + ' ✓</span>';
        if (counts.pending) html += '<span class="badge bg-secondary" style="font-size:0.68rem;">' + counts.pending + ' pend.</span>';
        if (counts.delivered) html += '<span class="badge bg-primary" style="font-size:0.68rem;">' + counts.delivered + ' entregue</span>';
        if (counts.divergence) html += '<span class="badge bg-danger" style="font-size:0.68rem;">' + counts.divergence + ' problema</span>';
        document.getElementById('progressBadges').innerHTML = html;
    }

    function updateHistory(history) {
        const el = document.getElementById('historyList');
        if (!history.length) { el.innerHTML = '<p class="text-muted small text-center mb-0">Nenhuma ação ainda.</p>'; return; }
        const icons = {mark_checked:'✅',mark_delivered:'📦',mark_divergence:'⚠️',request_replacement:'🔄',mark_replacement_delivered:'✅',reset:'↩️'};
        el.innerHTML = history.slice(0, 15).map(h => '<div class="history-item">' + (icons[h.action]||'•') + ' <strong>' + (h.performed_by||'') + '</strong><span class="text-muted float-end" style="font-size:0.65rem;">' + formatDate(h.created_at) + '</span><br><span class="text-muted">' + (h.description||'') + '</span></div>').join('');
    }

    function formatDate(d) { if (!d) return ''; const dt = new Date(d); return dt.toLocaleDateString('pt-BR',{day:'2-digit',month:'2-digit'}) + ' ' + dt.toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'}); }
    </script>
</body>
</html>
