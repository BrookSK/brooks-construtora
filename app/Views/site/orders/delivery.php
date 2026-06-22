<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist de Entrega - <?= htmlspecialchars($order['code']) ?> | Brooks Construtora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; min-height: 100vh; padding-bottom: 70px; }
        .page-header { background: #3a3b4e; color: #fff; padding: 0.6rem 0; position: sticky; top: 0; z-index: 100; }
        .delivery-item { border: 1px solid #dee2e6; border-radius: 10px; padding: 0.85rem; margin-bottom: 0.5rem; transition: all 0.3s; background: #fff; }
        .delivery-item.status-checked, .delivery-item.status-replacement_delivered { border-color: #28a745; background: #f0fff4; opacity: 0.85; }
        .delivery-item.status-delivered { border-color: #0d6efd; background: #f0f4ff; }
        .delivery-item.status-divergence { border-color: #dc3545; background: #fff5f5; }
        .delivery-item.status-replacement_requested { border-color: #ffc107; background: #fffdf0; }
        .delivery-item.late { border-color: #dc3545 !important; box-shadow: 0 0 0 2px rgba(220,53,69,0.15); }
        .supplier-group { margin-bottom: 1.2rem; }
        .supplier-header { background: linear-gradient(135deg, #3a3b4e, #4a4b5e); color: #fff; border-radius: 8px; padding: 0.6rem 0.85rem; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.8rem; display: flex; justify-content: space-between; align-items: center; }
        .action-btn { min-width: 48px; min-height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
        .name-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; border-top: 2px solid #3a3b4e; padding: 0.6rem 1rem; z-index: 100; box-shadow: 0 -4px 12px rgba(0,0,0,0.1); }
        .toast-container { position: fixed; top: 70px; right: 10px; z-index: 200; }
        .history-item { font-size: 0.72rem; padding: 0.4rem 0; border-bottom: 1px solid #f0f0f0; }
        .progress-ring { width: 60px; height: 60px; }
        .info-pill { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 20px; padding: 0.3rem 0.7rem; font-size: 0.7rem; display: inline-flex; align-items: center; gap: 0.3rem; }
        .spin { animation: spin 1s linear infinite; }
        @keyframes spin { from{transform:rotate(0)} to{transform:rotate(360deg)} }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong style="font-size:0.85rem;">BROOKS</strong>
                    <span class="badge bg-light text-dark ms-2" style="font-size:0.7rem;"><?= htmlspecialchars($order['code']) ?></span>
                </div>
                <span class="badge bg-dark bg-opacity-50 small" id="syncStatus"><i class="bi bi-check-circle"></i> Sincronizado</span>
            </div>
        </div>
    </div>

    <div class="container py-3">
        <!-- Card de informações do pedido -->
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="mb-1 fw-bold">Checklist de Entrega</h6>
                        <p class="mb-0 small text-muted">Pedido <?= htmlspecialchars($order['code']) ?> · Aprovado em <?= $order['approved_at'] ? date('d/m/Y', strtotime($order['approved_at'])) : '-' ?></p>
                    </div>
                    <div id="progressBadges" class="d-flex gap-1 flex-wrap"></div>
                </div>
                <!-- Barra de progresso -->
                <div class="progress mb-2" style="height:6px;" id="progressBar">
                    <div class="progress-bar bg-success" id="progressBarFill" style="width:0%;"></div>
                </div>
                <!-- Info pills -->
                <div class="d-flex flex-wrap gap-1">
                    <?php
                    $totalValue = number_format($order['total_estimated'] ?? 0, 2, ',', '.');
                    $totalItems = count($deliveries);
                    ?>
                    <span class="info-pill"><i class="bi bi-box-seam text-primary"></i> <?= $totalItems ?> itens</span>
                    <span class="info-pill"><i class="bi bi-cash text-success"></i> R$ <?= $totalValue ?></span>
                    <?php if (!empty($order['approved_by_name'])): ?>
                    <span class="info-pill"><i class="bi bi-person-check text-info"></i> <?= htmlspecialchars($order['approved_by_name']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

<?php
$deliveriesBySupplier = [];
foreach ($deliveries as $d) {
    $key = $d['supplier_name'] ?? 'Sem fornecedor';
    $deliveriesBySupplier[$key][] = $d;
}
$today = date('Y-m-d');
$statusLabelsDelivery = \App\Models\PurchaseOrderDelivery::$statusLabels;

// Buscar info de pagamento dos fornecedores
$supplierPaymentInfo = [];
foreach ($orderSuppliers as $os) {
    if ($os['approved']) {
        $supplierPaymentInfo[$os['supplier_name']] = $os;
    }
}
?>
        <!-- Itens agrupados por fornecedor -->
        <div id="deliveryList">
        <?php foreach ($deliveriesBySupplier as $supplierName => $supplierDeliveries): ?>
        <?php $supplierInfo = $supplierPaymentInfo[$supplierName] ?? null; ?>
        <div class="supplier-group">
            <div class="supplier-header">
                <div>
                    <i class="bi bi-building"></i> <?= htmlspecialchars($supplierName) ?>
                    <?php if ($supplierDeliveries[0]['expected_date']): ?>
                    <span class="ms-2 fw-normal opacity-75" style="font-size:0.7rem;"><i class="bi bi-calendar3"></i> <?= date('d/m/Y', strtotime($supplierDeliveries[0]['expected_date'])) ?></span>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2" style="font-size:0.65rem;">
                    <?php if ($supplierInfo): ?>
                    <?php if (!empty($supplierInfo['payment_method'])): ?>
                    <?php $pmLabels = ['pix'=>'PIX','boleto'=>'Boleto','cartao'=>'Cartão','transferencia'=>'Transf.','dinheiro'=>'Dinheiro','outro'=>'Outro']; ?>
                    <span class="badge bg-light text-dark"><?= $pmLabels[$supplierInfo['payment_method']] ?? '' ?></span>
                    <?php endif; ?>
                    <?php if (!empty($supplierInfo['delivery_days'])): ?>
                    <span class="badge bg-light text-dark"><i class="bi bi-truck"></i> <?= $supplierInfo['delivery_days'] ?>d</span>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($supplierInfo && (!empty($supplierInfo['vendor_name']) || !empty($supplierInfo['payment_condition']))): ?>
            <div class="d-flex flex-wrap gap-2 mb-2 px-1" style="font-size:0.68rem; color:#6c757d;">
                <?php if (!empty($supplierInfo['vendor_name'])): ?><span><i class="bi bi-person"></i> <?= htmlspecialchars($supplierInfo['vendor_name']) ?></span><?php endif; ?>
                <?php if (!empty($supplierInfo['vendor_phone'])): ?><span><i class="bi bi-telephone"></i> <?= htmlspecialchars($supplierInfo['vendor_phone']) ?></span><?php endif; ?>
                <?php if (!empty($supplierInfo['payment_condition'])): ?><span><i class="bi bi-credit-card"></i> <?= htmlspecialchars($supplierInfo['payment_condition']) ?></span><?php endif; ?>
                <?php if (!empty($supplierInfo['payment_first_due'])): ?><span><i class="bi bi-calendar-event"></i> 1ª parcela: <?= date('d/m', strtotime($supplierInfo['payment_first_due'])) ?></span><?php endif; ?>
            </div>
            <?php endif; ?>

            <?php foreach ($supplierDeliveries as $d): ?>
            <?php
            $si = $statusLabelsDelivery[$d['status']] ?? ['?', 'secondary', 'bi-question'];
            $isLate = false;
            if ($d['status'] !== 'checked' && $d['status'] !== 'delivered' && $d['status'] !== 'replacement_delivered') {
                if ($d['expected_date'] && $d['expected_date'] < $today) $isLate = true;
                if ($d['status'] === 'replacement_requested' && $d['replacement_expected_date'] && $d['replacement_expected_date'] < $today) $isLate = true;
            }
            ?>
            <div class="delivery-item status-<?= $d['status'] ?> <?= $isLate ? 'late' : '' ?>" id="item-<?= $d['id'] ?>" data-id="<?= $d['id'] ?>" data-status="<?= $d['status'] ?>">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-1 mb-1">
                            <span class="badge bg-<?= $si[1] ?>" style="font-size:0.6rem;"><i class="bi <?= $si[2] ?>"></i> <?= $si[0] ?></span>
                            <?php if ($isLate): ?><span class="badge bg-danger" style="font-size:0.55rem;"><i class="bi bi-clock"></i> ATRASADO</span><?php endif; ?>
                            <?php if ($d['expected_date'] && !$isLate && $d['status'] === 'pending'): ?>
                            <span style="font-size:0.6rem; color:#6c757d;"><i class="bi bi-calendar3"></i> <?= date('d/m', strtotime($d['expected_date'])) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="fw-bold" style="font-size:0.85rem;"><?= htmlspecialchars($d['material_name']) ?></div>
                        <div class="d-flex flex-wrap gap-2 mt-1" style="font-size:0.72rem; color:#6c757d;">
                            <span><i class="bi bi-hash"></i> <?= number_format($d['quantity'], $d['quantity'] == (int)$d['quantity'] ? 0 : 2) ?> <?= htmlspecialchars($d['unit'] ?? '') ?></span>
                            <?php if ($d['delivered_at']): ?><span class="text-primary"><i class="bi bi-box-seam"></i> <?= date('d/m H:i', strtotime($d['delivered_at'])) ?></span><?php endif; ?>
                            <?php if ($d['checked_by']): ?><span class="text-success"><i class="bi bi-person-check"></i> <?= htmlspecialchars($d['checked_by']) ?></span><?php endif; ?>
                        </div>
                        <?php if ($d['divergence_notes']): ?>
                        <div class="mt-1 p-2 rounded" style="font-size:0.72rem; background:#fdeaea; color:#721c24;"><i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($d['divergence_notes']) ?></div>
                        <?php endif; ?>
                        <?php if ($d['replacement_notes']): ?>
                        <div class="mt-1 p-2 rounded" style="font-size:0.72rem; background:#fff3cd; color:#856404;"><i class="bi bi-arrow-repeat"></i> <?= htmlspecialchars($d['replacement_notes']) ?><?php if ($d['replacement_expected_date']): ?> · Prev: <?= date('d/m', strtotime($d['replacement_expected_date'])) ?><?php endif; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-1 flex-shrink-0 flex-column align-items-center">
                        <?php if ($d['status'] === 'pending'): ?>
                        <button class="btn btn-primary action-btn" onclick="doAction(<?= $d['id'] ?>, 'mark_delivered')" title="Marcar como entregue"><i class="bi bi-box-seam"></i></button>
                        <?php elseif ($d['status'] === 'delivered'): ?>
                        <button class="btn btn-success action-btn" onclick="doAction(<?= $d['id'] ?>, 'mark_checked')" title="Tudo certo"><i class="bi bi-check-lg"></i></button>
                        <button class="btn btn-outline-danger action-btn" onclick="showDivergence(<?= $d['id'] ?>)" title="Tem problema"><i class="bi bi-exclamation"></i></button>
                        <?php elseif ($d['status'] === 'divergence'): ?>
                        <button class="btn btn-warning action-btn" onclick="showReplacement(<?= $d['id'] ?>)" title="Solicitar troca"><i class="bi bi-arrow-repeat"></i></button>
                        <?php elseif ($d['status'] === 'replacement_requested'): ?>
                        <button class="btn btn-success action-btn" onclick="doAction(<?= $d['id'] ?>, 'mark_replacement_delivered')" title="Troca recebida"><i class="bi bi-check-all"></i></button>
                        <?php elseif ($d['status'] === 'checked' || $d['status'] === 'replacement_delivered'): ?>
                        <i class="bi bi-check-circle-fill text-success" style="font-size:1.5rem;"></i>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        </div>
        <!-- Instruções -->
        <div class="card mt-3 border-0 bg-light">
            <div class="card-body p-2">
                <p class="small mb-1 fw-bold"><i class="bi bi-lightbulb"></i> Como usar:</p>
                <ul class="small mb-0 ps-3" style="font-size:0.72rem; color:#6c757d;">
                    <li><span class="text-primary"><i class="bi bi-box-seam"></i></span> Material chegou → Toque no botão azul</li>
                    <li><span class="text-success"><i class="bi bi-check-lg"></i></span> Conferiu e está OK → Toque no botão verde</li>
                    <li><span class="text-danger"><i class="bi bi-exclamation"></i></span> Veio errado/faltando → Toque no botão vermelho</li>
                    <li>As alterações são salvas automaticamente</li>
                </ul>
            </div>
        </div>

        <!-- Histórico recente -->
        <div class="card mt-3 border-0 shadow-sm">
            <div class="card-header py-2 small fw-bold border-0 bg-white"><i class="bi bi-clock-history"></i> Últimas Ações</div>
            <div class="card-body p-2 pt-0" id="historyList">
                <p class="text-muted small text-center mb-0">Carregando...</p>
            </div>
        </div>
    </div>

    <!-- Barra fixa de identificação -->
    <div class="name-bar">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-person-circle text-muted" style="font-size:1.2rem;"></i>
            <input type="text" class="form-control form-control-sm" id="personName" placeholder="Seu nome" style="max-width:200px; font-weight:500;">
            <span class="small text-muted flex-shrink-0" id="nameHint">← Identifique-se</span>
        </div>
    </div>

    <!-- Toast -->
    <div class="toast-container">
        <div id="toast" class="toast align-items-center text-bg-success border-0" role="alert">
            <div class="d-flex"><div class="toast-body" id="toastMsg">Salvo!</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>
        </div>
    </div>

    <!-- Modal Divergência -->
    <div class="modal fade" id="divModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2 bg-danger text-white"><h6 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Registrar Problema</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" id="divId">
                    <p class="small text-muted">Descreva o que está errado com este material:</p>
                    <textarea class="form-control" id="divNotes" rows="3" placeholder="Ex: Veio quantidade errada, material danificado, modelo diferente do pedido..."></textarea>
                </div>
                <div class="modal-footer py-2"><button class="btn btn-danger w-100" onclick="submitDivergence()"><i class="bi bi-exclamation-triangle"></i> Registrar Divergência</button></div>
            </div>
        </div>
    </div>

    <!-- Modal Troca -->
    <div class="modal fade" id="repModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2 bg-warning"><h6 class="modal-title"><i class="bi bi-arrow-repeat"></i> Solicitar Troca/Reposição</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" id="repId">
                    <div class="mb-3"><label class="form-label small fw-bold">Previsão de nova entrega</label><input type="date" class="form-control" id="repDate"></div>
                    <div><label class="form-label small fw-bold">Detalhes da troca</label><textarea class="form-control" id="repNotes" rows="2" placeholder="O que foi combinado com o fornecedor..."></textarea></div>
                </div>
                <div class="modal-footer py-2"><button class="btn btn-warning w-100" onclick="submitReplacement()"><i class="bi bi-arrow-repeat"></i> Solicitar Troca</button></div>
            </div>
        </div>
    </div>

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
        if (saved) {
            document.getElementById('personName').value = saved;
            document.getElementById('nameHint').style.display = 'none';
        }
        document.getElementById('personName').addEventListener('input', function() {
            localStorage.setItem('delivery_person_name', this.value);
            document.getElementById('nameHint').style.display = this.value ? 'none' : '';
        });
        loadData();
        setInterval(loadData, 8000);
    });

    function getName() {
        const name = document.getElementById('personName').value.trim();
        if (!name) {
            document.getElementById('personName').classList.add('border-danger');
            document.getElementById('personName').focus();
            showToast('Informe seu nome primeiro!', 'warning');
            return null;
        }
        document.getElementById('personName').classList.remove('border-danger');
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
                if (d.success) {
                    showToast('✓ Salvo com sucesso!', 'success');
                    loadData();
                } else {
                    showToast(d.error || 'Erro ao salvar', 'danger');
                }
                document.getElementById('syncStatus').innerHTML = '<i class="bi bi-check-circle"></i> Sincronizado';
            })
            .catch(() => {
                showToast('Sem conexão com o servidor', 'danger');
                document.getElementById('syncStatus').innerHTML = '<i class="bi bi-exclamation-circle text-warning"></i> Offline';
            });
    }

    function showDivergence(id) { document.getElementById('divId').value = id; document.getElementById('divNotes').value = ''; new bootstrap.Modal(document.getElementById('divModal')).show(); }
    function submitDivergence() {
        const notes = document.getElementById('divNotes').value.trim();
        if (!notes) { alert('Descreva o problema encontrado.'); return; }
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
        const statusLabels = {pending:['Pendente','secondary','bi-clock'],delivered:['Entregue','primary','bi-box-seam'],checked:['Conferido','success','bi-check-circle-fill'],divergence:['Divergência','danger','bi-exclamation-triangle'],replacement_requested:['Troca Solic.','warning','bi-arrow-repeat'],replacement_delivered:['Troca OK','success','bi-check-all']};
        const today = new Date().toISOString().split('T')[0];
        let counts = {};

        deliveries.forEach(d => {
            counts[d.status] = (counts[d.status] || 0) + 1;
            const el = document.getElementById('item-' + d.id);
            if (!el) return;
            const si = statusLabels[d.status] || ['?','secondary','bi-question'];
            const isLate = (d.status !== 'checked' && d.status !== 'delivered' && d.status !== 'replacement_delivered') && ((d.expected_date && d.expected_date < today) || (d.status === 'replacement_requested' && d.replacement_expected_date && d.replacement_expected_date < today));

            el.className = 'delivery-item status-' + d.status + (isLate ? ' late' : '');

            // Atualizar botões
            let btns = '';
            if (d.status === 'pending') btns = '<button class="btn btn-primary action-btn" onclick="doAction('+d.id+',\'mark_delivered\')"><i class="bi bi-box-seam"></i></button>';
            else if (d.status === 'delivered') btns = '<button class="btn btn-success action-btn" onclick="doAction('+d.id+',\'mark_checked\')"><i class="bi bi-check-lg"></i></button><button class="btn btn-outline-danger action-btn mt-1" onclick="showDivergence('+d.id+')"><i class="bi bi-exclamation"></i></button>';
            else if (d.status === 'divergence') btns = '<button class="btn btn-warning action-btn" onclick="showReplacement('+d.id+')"><i class="bi bi-arrow-repeat"></i></button>';
            else if (d.status === 'replacement_requested') btns = '<button class="btn btn-success action-btn" onclick="doAction('+d.id+',\'mark_replacement_delivered\')"><i class="bi bi-check-all"></i></button>';
            else btns = '<i class="bi bi-check-circle-fill text-success" style="font-size:1.5rem;"></i>';

            const btnContainer = el.querySelector('.flex-column');
            if (btnContainer) btnContainer.innerHTML = btns;

            // Atualizar badges
            const badgeContainer = el.querySelector('.d-flex.align-items-center.gap-1');
            if (badgeContainer) {
                let badges = '<span class="badge bg-'+si[1]+'" style="font-size:0.6rem;"><i class="bi '+si[2]+'"></i> '+si[0]+'</span>';
                if (isLate) badges += '<span class="badge bg-danger" style="font-size:0.55rem;"><i class="bi bi-clock"></i> ATRASADO</span>';
                badgeContainer.innerHTML = badges;
            }
        });

        // Progress
        const total = deliveries.length;
        const done = (counts.checked || 0) + (counts.replacement_delivered || 0);
        const pct = total > 0 ? Math.round((done / total) * 100) : 0;

        document.getElementById('progressBarFill').style.width = pct + '%';
        const badges = document.getElementById('progressBadges');
        let html = '<span class="badge bg-success" style="font-size:0.65rem;">' + done + '/' + total + ' ✓</span>';
        if (counts.pending) html += '<span class="badge bg-secondary" style="font-size:0.65rem;">' + counts.pending + ' pend.</span>';
        if (counts.delivered) html += '<span class="badge bg-primary" style="font-size:0.65rem;">' + counts.delivered + ' entregue</span>';
        if (counts.divergence) html += '<span class="badge bg-danger" style="font-size:0.65rem;">' + counts.divergence + ' problema</span>';
        if (counts.replacement_requested) html += '<span class="badge bg-warning text-dark" style="font-size:0.65rem;">' + counts.replacement_requested + ' troca</span>';
        badges.innerHTML = html;
    }

    function updateHistory(history) {
        const el = document.getElementById('historyList');
        if (!history.length) { el.innerHTML = '<p class="text-muted small text-center mb-0">Nenhuma ação registrada ainda.</p>'; return; }
        el.innerHTML = history.slice(0, 15).map(h => {
            const icon = h.action === 'mark_checked' ? '✓' : h.action === 'mark_delivered' ? '📦' : h.action === 'mark_divergence' ? '⚠️' : h.action === 'request_replacement' ? '🔄' : '•';
            return '<div class="history-item">' + icon + ' <strong>' + (h.performed_by||'') + '</strong> <span class="text-muted float-end">' + formatDate(h.created_at) + '</span><br><span class="text-muted">' + (h.description||'') + '</span></div>';
        }).join('');
    }

    function formatDate(d) { if (!d) return ''; const dt = new Date(d); return dt.toLocaleDateString('pt-BR',{day:'2-digit',month:'2-digit'}) + ' ' + dt.toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'}); }
    </script>
</body>
</html>
