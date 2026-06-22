<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checklist de Entrega - <?= htmlspecialchars($order['code']) ?> | Brooks Construtora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; min-height: 100vh; padding-bottom: 80px; }
        .page-header { background: #3a3b4e; color: #fff; padding: 0.75rem 0; position: sticky; top: 0; z-index: 100; }
        .delivery-item { border: 1px solid #dee2e6; border-radius: 8px; padding: 0.75rem; margin-bottom: 0.6rem; transition: all 0.3s; }
        .delivery-item.status-checked, .delivery-item.status-replacement_delivered { border-color: #28a745; background: #f0fff4; }
        .delivery-item.status-delivered { border-color: #0d6efd; background: #f0f4ff; }
        .delivery-item.status-divergence { border-color: #dc3545; background: #fff5f5; }
        .delivery-item.status-replacement_requested { border-color: #ffc107; background: #fffdf0; }
        .delivery-item.late { border-color: #dc3545 !important; }
        .supplier-group { margin-bottom: 1rem; }
        .supplier-header { background: #e9ecef; border-radius: 6px; padding: 0.5rem 0.75rem; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.8rem; }
        .action-btn { min-width: 44px; min-height: 44px; }
        .name-bar { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; border-top: 1px solid #dee2e6;
            padding: 0.5rem 1rem; z-index: 100; box-shadow: 0 -2px 8px rgba(0,0,0,0.08); }
        .toast-container { position: fixed; top: 70px; right: 10px; z-index: 200; }
        .history-item { font-size: 0.7rem; padding: 0.3rem 0; border-bottom: 1px solid #f0f0f0; }
    </style>
</head>
<body>
    <div class="page-header text-center">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-start">
                    <strong class="small">BROOKS</strong>
                    <span class="small opacity-75 ms-1"><?= htmlspecialchars($order['code']) ?></span>
                </div>
                <span class="badge bg-light text-dark small" id="syncStatus"><i class="bi bi-check-circle text-success"></i> Sincronizado</span>
            </div>
        </div>
    </div>

    <div class="container py-3">
        <!-- Resumo -->
        <div class="card mb-3">
            <div class="card-body p-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                    <span class="small fw-bold">Checklist de Entrega</span>
                    <div id="progressBadges" class="d-flex gap-1 flex-wrap"></div>
                </div>
            </div>
        </div>

        <!-- Itens agrupados por fornecedor -->
        <div id="deliveryList">
        <?php
        $deliveriesBySupplier = [];
        foreach ($deliveries as $d) {
            $key = $d['supplier_name'] ?? 'Sem fornecedor';
            $deliveriesBySupplier[$key][] = $d;
        }
        $today = date('Y-m-d');
        $statusLabelsDelivery = \App\Models\PurchaseOrderDelivery::$statusLabels;
        ?>
        <?php foreach ($deliveriesBySupplier as $supplierName => $supplierDeliveries): ?>
        <div class="supplier-group">
            <div class="supplier-header"><i class="bi bi-building"></i> <?= htmlspecialchars($supplierName) ?>
                <?php if ($supplierDeliveries[0]['expected_date']): ?>
                <span class="text-muted fw-normal ms-2"><i class="bi bi-calendar"></i> <?= date('d/m', strtotime($supplierDeliveries[0]['expected_date'])) ?></span>
                <?php endif; ?>
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
            <div class="delivery-item status-<?= $d['status'] ?> <?= $isLate ? 'late' : '' ?>" id="item-<?= $d['id'] ?>" data-id="<?= $d['id'] ?>" data-status="<?= $d['status'] ?>">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center gap-1 mb-1">
                            <span class="badge bg-<?= $si[1] ?>" style="font-size:0.6rem;"><i class="bi <?= $si[2] ?>"></i> <?= $si[0] ?></span>
                            <?php if ($isLate): ?><span class="badge bg-danger" style="font-size:0.55rem;">ATRASADO</span><?php endif; ?>
                        </div>
                        <div class="fw-bold" style="font-size:0.82rem;"><?= htmlspecialchars($d['material_name']) ?></div>
                        <div style="font-size:0.7rem; color:#6c757d;">
                            Qtd: <?= number_format($d['quantity'], $d['quantity'] == (int)$d['quantity'] ? 0 : 2) ?> <?= htmlspecialchars($d['unit'] ?? '') ?>
                            <?php if ($d['delivered_at']): ?> · <i class="bi bi-check"></i> <?= date('d/m H:i', strtotime($d['delivered_at'])) ?><?php endif; ?>
                            <?php if ($d['checked_by']): ?> · <i class="bi bi-person-check"></i> <?= htmlspecialchars($d['checked_by']) ?><?php endif; ?>
                        </div>
                        <?php if ($d['divergence_notes']): ?>
                        <div class="text-danger mt-1" style="font-size:0.7rem;"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($d['divergence_notes']) ?></div>
                        <?php endif; ?>
                        <?php if ($d['replacement_notes']): ?>
                        <div class="text-warning mt-1" style="font-size:0.7rem;"><i class="bi bi-arrow-repeat"></i> <?= htmlspecialchars($d['replacement_notes']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-1 flex-shrink-0 flex-column">
                        <?php if ($d['status'] === 'pending'): ?>
                        <button class="btn btn-sm btn-primary action-btn" onclick="doAction(<?= $d['id'] ?>, 'mark_delivered')" title="Entregue"><i class="bi bi-box-seam"></i></button>
                        <?php elseif ($d['status'] === 'delivered'): ?>
                        <button class="btn btn-sm btn-success action-btn" onclick="doAction(<?= $d['id'] ?>, 'mark_checked')" title="OK"><i class="bi bi-check-lg"></i></button>
                        <button class="btn btn-sm btn-danger action-btn" onclick="showDivergence(<?= $d['id'] ?>)" title="Problema"><i class="bi bi-exclamation"></i></button>
                        <?php elseif ($d['status'] === 'divergence'): ?>
                        <button class="btn btn-sm btn-warning action-btn" onclick="showReplacement(<?= $d['id'] ?>)" title="Troca"><i class="bi bi-arrow-repeat"></i></button>
                        <?php elseif ($d['status'] === 'replacement_requested'): ?>
                        <button class="btn btn-sm btn-success action-btn" onclick="doAction(<?= $d['id'] ?>, 'mark_replacement_delivered')" title="Troca OK"><i class="bi bi-check-all"></i></button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        </div>
        <!-- Histórico recente -->
        <div class="card mt-3">
            <div class="card-header py-2 small fw-bold"><i class="bi bi-clock-history"></i> Histórico Recente</div>
            <div class="card-body p-2" id="historyList">
                <p class="text-muted small text-center mb-0">Carregando...</p>
            </div>
        </div>
    </div>

    <!-- Barra fixa de nome -->
    <div class="name-bar">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-person-fill text-muted"></i>
            <input type="text" class="form-control form-control-sm" id="personName" placeholder="Seu nome (obrigatório)" style="max-width:250px;">
            <span class="small text-muted d-none d-md-inline">Identifique-se para registrar as conferências</span>
        </div>
    </div>

    <!-- Toast de feedback -->
    <div class="toast-container">
        <div id="toast" class="toast align-items-center text-bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body" id="toastMsg">Salvo!</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <!-- Modal Divergência -->
    <div class="modal fade" id="divModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header py-2"><h6 class="modal-title">Qual o problema?</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" id="divId">
                    <textarea class="form-control" id="divNotes" rows="3" placeholder="Descreva: veio errado, quantidade diferente, danificado..."></textarea>
                </div>
                <div class="modal-footer py-2"><button class="btn btn-sm btn-danger w-100" onclick="submitDivergence()">Registrar Divergência</button></div>
            </div>
        </div>
    </div>

    <!-- Modal Troca -->
    <div class="modal fade" id="repModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header py-2"><h6 class="modal-title">Solicitar Troca</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" id="repId">
                    <div class="mb-2"><label class="form-label small">Previsão nova entrega</label><input type="date" class="form-control form-control-sm" id="repDate"></div>
                    <div><label class="form-label small">Obs</label><textarea class="form-control form-control-sm" id="repNotes" rows="2" placeholder="Detalhes..."></textarea></div>
                </div>
                <div class="modal-footer py-2"><button class="btn btn-sm btn-warning w-100" onclick="submitReplacement()">Solicitar Troca</button></div>
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
        toastInst = new bootstrap.Toast(toastEl, {delay: 2000});
        // Restaurar nome do localStorage
        const saved = localStorage.getItem('delivery_person_name');
        if (saved) document.getElementById('personName').value = saved;
        // Salvar nome ao alterar
        document.getElementById('personName').addEventListener('change', function() {
            localStorage.setItem('delivery_person_name', this.value);
        });
        loadData();
        // Polling a cada 10s para manter sincronizado
        setInterval(loadData, 10000);
    });

    function getName() {
        const name = document.getElementById('personName').value.trim();
        if (!name) { alert('Informe seu nome antes de continuar.'); document.getElementById('personName').focus(); return null; }
        localStorage.setItem('delivery_person_name', name);
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
                    showToast('Salvo!', 'success');
                    loadData(); // Recarrega dados sem recarregar página
                } else {
                    showToast(d.error || 'Erro', 'danger');
                }
                document.getElementById('syncStatus').innerHTML = '<i class="bi bi-check-circle text-success"></i> Sincronizado';
            })
            .catch(() => {
                showToast('Sem conexão', 'danger');
                document.getElementById('syncStatus').innerHTML = '<i class="bi bi-exclamation-circle text-danger"></i> Offline';
            });
    }

    function showDivergence(id) { document.getElementById('divId').value = id; document.getElementById('divNotes').value = ''; new bootstrap.Modal(document.getElementById('divModal')).show(); }
    function submitDivergence() {
        const id = document.getElementById('divId').value;
        const notes = document.getElementById('divNotes').value.trim();
        if (!notes) { alert('Descreva o problema.'); return; }
        bootstrap.Modal.getInstance(document.getElementById('divModal')).hide();
        doAction(id, 'mark_divergence', {divergence_notes: notes});
    }
    function showReplacement(id) { document.getElementById('repId').value = id; new bootstrap.Modal(document.getElementById('repModal')).show(); }
    function submitReplacement() {
        const id = document.getElementById('repId').value;
        bootstrap.Modal.getInstance(document.getElementById('repModal')).hide();
        doAction(id, 'request_replacement', {replacement_expected_date: document.getElementById('repDate').value, replacement_notes: document.getElementById('repNotes').value});
    }
    function loadData() {
        fetch(DATA_URL)
            .then(r => r.json())
            .then(data => {
                if (data.deliveries) updateUI(data.deliveries);
                if (data.history) updateHistory(data.history);
            })
            .catch(() => {});
    }

    function updateUI(deliveries) {
        const statusLabels = {pending:['Pendente','secondary','bi-clock'],delivered:['Entregue','primary','bi-box-seam'],checked:['Conferido','success','bi-check-circle-fill'],divergence:['Divergência','danger','bi-exclamation-triangle'],replacement_requested:['Troca Solicitada','warning','bi-arrow-repeat'],replacement_delivered:['Troca Entregue','success','bi-check-all']};
        const today = new Date().toISOString().split('T')[0];
        let counts = {};

        deliveries.forEach(d => {
            counts[d.status] = (counts[d.status] || 0) + 1;
            const el = document.getElementById('item-' + d.id);
            if (!el) return;
            const si = statusLabels[d.status] || ['?','secondary','bi-question'];
            const isLate = (d.status !== 'checked' && d.status !== 'delivered' && d.status !== 'replacement_delivered') && 
                ((d.expected_date && d.expected_date < today) || (d.status === 'replacement_requested' && d.replacement_expected_date && d.replacement_expected_date < today));

            // Atualizar classe
            el.className = 'delivery-item status-' + d.status + (isLate ? ' late' : '');
            el.dataset.status = d.status;

            // Atualizar botões
            let btns = '';
            if (d.status === 'pending') btns = '<button class="btn btn-sm btn-primary action-btn" onclick="doAction('+d.id+',\'mark_delivered\')" title="Entregue"><i class="bi bi-box-seam"></i></button>';
            else if (d.status === 'delivered') btns = '<button class="btn btn-sm btn-success action-btn" onclick="doAction('+d.id+',\'mark_checked\')" title="OK"><i class="bi bi-check-lg"></i></button><button class="btn btn-sm btn-danger action-btn" onclick="showDivergence('+d.id+')" title="Problema"><i class="bi bi-exclamation"></i></button>';
            else if (d.status === 'divergence') btns = '<button class="btn btn-sm btn-warning action-btn" onclick="showReplacement('+d.id+')" title="Troca"><i class="bi bi-arrow-repeat"></i></button>';
            else if (d.status === 'replacement_requested') btns = '<button class="btn btn-sm btn-success action-btn" onclick="doAction('+d.id+',\'mark_replacement_delivered\')" title="Troca OK"><i class="bi bi-check-all"></i></button>';

            const btnContainer = el.querySelector('.flex-column');
            if (btnContainer) btnContainer.innerHTML = btns;

            // Atualizar badge de status
            const badgeContainer = el.querySelector('.d-flex.align-items-center.gap-1');
            if (badgeContainer) {
                let badges = '<span class="badge bg-'+si[1]+'" style="font-size:0.6rem;"><i class="bi '+si[2]+'"></i> '+si[0]+'</span>';
                if (isLate) badges += '<span class="badge bg-danger" style="font-size:0.55rem;">ATRASADO</span>';
                badgeContainer.innerHTML = badges;
            }
        });

        // Atualizar badges de progresso
        const total = deliveries.length;
        const done = (counts.checked || 0) + (counts.replacement_delivered || 0);
        const badges = document.getElementById('progressBadges');
        badges.innerHTML = '<span class="badge bg-success" style="font-size:0.65rem;">' + done + '/' + total + ' OK</span>' +
            (counts.pending ? '<span class="badge bg-secondary" style="font-size:0.65rem;">' + counts.pending + ' pend.</span>' : '') +
            (counts.divergence ? '<span class="badge bg-danger" style="font-size:0.65rem;">' + counts.divergence + ' div.</span>' : '');
    }

    function updateHistory(history) {
        const el = document.getElementById('historyList');
        if (!history.length) { el.innerHTML = '<p class="text-muted small text-center mb-0">Nenhuma ação registrada.</p>'; return; }
        el.innerHTML = history.slice(0, 20).map(h => '<div class="history-item"><strong>' + (h.performed_by||'') + '</strong> <span class="text-muted">' + formatDate(h.created_at) + '</span><br>' + (h.description||'') + '</div>').join('');
    }

    function formatDate(d) { if (!d) return ''; const dt = new Date(d); return dt.toLocaleDateString('pt-BR') + ' ' + dt.toLocaleTimeString('pt-BR',{hour:'2-digit',minute:'2-digit'}); }
    </script>
    <style>.spin { animation: spin 1s linear infinite; } @keyframes spin { from{transform:rotate(0)} to{transform:rotate(360deg)} }</style>
</body>
</html>
