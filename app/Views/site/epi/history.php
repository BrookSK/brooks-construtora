<?php $pageTitle = 'Histórico de EPIs'; $currentPage = 'epi_history'; $user = $user ?? \App\Core\Auth::user(); ?>
<?php ob_start(); ?>

<style>
    .evt-card { border: 1px solid #e5e7eb; border-radius: 12px; background: #fff; margin-bottom: 0.7rem; }
    .evt-card .evt-body { padding: 0.9rem 1rem; }
    .evt-delivery { border-left: 4px solid #0d6efd; }
    .evt-replacement { border-left: 4px solid #ffc107; }
    .thumb { width: 54px; height: 54px; object-fit: cover; border-radius: 8px; border: 1px solid #dee2e6; cursor: pointer; }
    .thumb-label { font-size: 0.62rem; text-align: center; color: #6c757d; margin-top: 2px; }
</style>

<div style="max-width: 900px;">
    <?php
    // Combina entregas e substituições em uma linha do tempo única
    $events = [];
    foreach ($deliveries as $d) {
        $events[] = ['type' => 'delivery', 'at' => $d['created_at'], 'data' => $d];
    }
    foreach ($replacements as $r) {
        $events[] = ['type' => 'replacement', 'at' => $r['created_at'], 'data' => $r];
    }
    usort($events, fn($a, $b) => strtotime($b['at']) <=> strtotime($a['at']));
    ?>

    <!-- Filtro -->
    <div class="btn-group mb-3" role="group">
        <button type="button" class="btn btn-sm btn-primary" onclick="filterEvents('all', this)">Tudo</button>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="filterEvents('delivery', this)"><i class="bi bi-box-seam"></i> Entregas</button>
        <button type="button" class="btn btn-sm btn-outline-warning" onclick="filterEvents('replacement', this)"><i class="bi bi-arrow-repeat"></i> Substituições</button>
    </div>

    <?php if (empty($events)): ?>
    <div class="card border-0 shadow-sm"><div class="card-body text-center text-muted py-5"><i class="bi bi-clock-history" style="font-size:2rem;"></i><p class="mb-0 mt-2">Nenhum registro ainda.</p></div></div>
    <?php else: ?>

    <div id="eventList">
    <?php foreach ($events as $ev): ?>
        <?php if ($ev['type'] === 'delivery'): $d = $ev['data']; $items = $deliveryItems[$d['id']] ?? []; ?>
        <div class="evt-card evt-delivery" data-type="delivery">
            <div class="evt-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <span class="badge bg-primary mb-1"><i class="bi bi-box-seam"></i> Entrega</span>
                        <div class="fw-bold"><?= htmlspecialchars($d['worker_name']) ?> <span class="text-muted small">· <?= htmlspecialchars($d['worker_role']) ?></span></div>
                        <div class="small text-muted"><i class="bi bi-person-vcard"></i> <?= htmlspecialchars($d['worker_document']) ?> · Responsável: <?= htmlspecialchars($d['delivered_by']) ?></div>
                    </div>
                    <div class="text-end small text-muted"><i class="bi bi-calendar3"></i> <?= date('d/m/Y H:i', strtotime($d['created_at'])) ?></div>
                </div>
                <?php if (!empty($items)): ?>
                <div class="mt-2 d-flex flex-wrap gap-1">
                    <?php foreach ($items as $it): ?>
                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($it['epi_name']) ?><?= $it['ca'] ? ' · CA ' . htmlspecialchars($it['ca']) : '' ?> · <?= rtrim(rtrim(number_format($it['quantity'], 2, ',', '.'), '0'), ',') ?> un</span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="mt-2 d-flex gap-3">
                    <?php if (!empty($d['selfie_path'])): ?><div><img src="<?= htmlspecialchars($d['selfie_path']) ?>" class="thumb" onclick="zoom(this.src)"><div class="thumb-label">Selfie</div></div><?php endif; ?>
                    <?php if (!empty($d['epis_photo_path'])): ?><div><img src="<?= htmlspecialchars($d['epis_photo_path']) ?>" class="thumb" onclick="zoom(this.src)"><div class="thumb-label">Com EPIs</div></div><?php endif; ?>
                    <?php if (!empty($d['signature_path'])): ?><div><img src="<?= htmlspecialchars($d['signature_path']) ?>" class="thumb" style="object-fit:contain;background:#fff;" onclick="zoom(this.src)"><div class="thumb-label">Assinatura</div></div><?php endif; ?>
                </div>
            </div>
        </div>
        <?php else: $r = $ev['data']; ?>
        <div class="evt-card evt-replacement" data-type="replacement">
            <div class="evt-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <span class="badge bg-warning text-dark mb-1"><i class="bi bi-arrow-repeat"></i> Substituição<?= !empty($r['sequence_number']) && $r['sequence_number'] > 1 ? ' · ' . (int)$r['sequence_number'] . 'ª' : '' ?></span>
                        <div class="fw-bold"><?= htmlspecialchars($r['epi_name']) ?><?= $r['ca'] ? ' <span class="text-muted small">· CA ' . htmlspecialchars($r['ca']) . '</span>' : '' ?></div>
                        <div class="small text-muted"><i class="bi bi-person"></i> <?= htmlspecialchars($r['worker_name']) ?> · <?= htmlspecialchars($r['worker_document']) ?> · Qtd: <?= rtrim(rtrim(number_format($r['quantity'], 2, ',', '.'), '0'), ',') ?></div>
                        <div class="small text-muted">Responsável: <?= htmlspecialchars($r['performed_by']) ?></div>
                    </div>
                    <div class="text-end small text-muted"><i class="bi bi-calendar3"></i> <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></div>
                </div>
                <div class="mt-2 d-flex gap-3">
                    <?php if (!empty($r['old_item_photo_path'])): ?><div><img src="<?= htmlspecialchars($r['old_item_photo_path']) ?>" class="thumb" onclick="zoom(this.src)"><div class="thumb-label">Devolvido</div></div><?php endif; ?>
                    <?php if (!empty($r['new_delivery_photo_path'])): ?><div><img src="<?= htmlspecialchars($r['new_delivery_photo_path']) ?>" class="thumb" onclick="zoom(this.src)"><div class="thumb-label">Nova entrega</div></div><?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Zoom -->
<div class="modal fade" id="zoomModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content">
    <div class="modal-body p-0 text-center"><img id="zoomImg" style="max-width:100%; border-radius:8px;"></div>
</div></div></div>

<script>
function zoom(src) {
    document.getElementById('zoomImg').src = src;
    new bootstrap.Modal(document.getElementById('zoomModal')).show();
}
function filterEvents(type, btn) {
    document.querySelectorAll('.btn-group .btn').forEach(b => {
        b.classList.remove('btn-primary', 'btn-warning');
        if (b.textContent.includes('Entregas')) b.classList.add('btn-outline-primary');
        else if (b.textContent.includes('Substituições')) b.classList.add('btn-outline-warning');
        else b.classList.add('btn-outline-primary');
    });
    btn.classList.remove('btn-outline-primary', 'btn-outline-warning');
    btn.classList.add(type === 'replacement' ? 'btn-warning' : 'btn-primary');
    document.querySelectorAll('#eventList .evt-card').forEach(card => {
        card.style.display = (type === 'all' || card.dataset.type === type) ? 'block' : 'none';
    });
}
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
