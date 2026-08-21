<?php
$pageTitle = $pageTitle ?? 'Painel de Transporte';
$currentPage = 'transport';
ob_start();
?>

<style>
.kanban-container { display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 1rem; }
.kanban-column { min-width: 300px; flex: 1; }
.kanban-header { padding: 0.75rem 1rem; border-radius: 8px 8px 0 0; font-weight: 600; font-size: 0.9rem; }
.kanban-body { background: #f8f9fa; border-radius: 0 0 8px 8px; padding: 0.75rem; min-height: 200px; }
.kanban-card {
    background: #fff;
    border-radius: 8px;
    padding: 0.85rem;
    margin-bottom: 0.75rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    border-left: 4px solid transparent;
    transition: transform 0.1s, box-shadow 0.1s;
}
.kanban-card:active { transform: scale(0.98); }
.kanban-card.type-transfer { border-left-color: #0d6efd; }
.kanban-card.type-exit { border-left-color: #dc3545; }
.kanban-card .card-material { font-weight: 600; font-size: 0.95rem; margin-bottom: 0.25rem; }
.kanban-card .card-meta { font-size: 0.78rem; color: #6c757d; }
.kanban-card .card-meta i { width: 16px; text-align: center; }
.kanban-card .card-actions { margin-top: 0.6rem; padding-top: 0.6rem; border-top: 1px solid #eee; }
.kanban-badge { font-size: 0.7rem; padding: 2px 8px; border-radius: 10px; }
.empty-column { text-align: center; padding: 2rem 1rem; color: #adb5bd; }
.empty-column i { font-size: 2rem; display: block; margin-bottom: 0.5rem; }

@media (max-width: 768px) {
    .kanban-container { flex-direction: column; }
    .kanban-column { min-width: 100%; }
    .kanban-card { padding: 0.75rem; }
    .kanban-header { font-size: 0.85rem; padding: 0.6rem 0.85rem; }
}
</style>

<!-- Filtro por obra -->
<div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    <select id="filterSite" class="form-select form-select-sm" style="max-width:250px;" onchange="filterKanban()">
        <option value="">Todas as obras</option>
        <?php
        $sites = array_unique(array_filter(array_merge(
            array_column($pending, 'from_site_name'),
            array_column($pending, 'to_site_name'),
            array_column($inTransit, 'from_site_name'),
            array_column($inTransit, 'to_site_name'),
            array_column($delivered, 'from_site_name'),
            array_column($delivered, 'to_site_name')
        )));
        sort($sites);
        foreach ($sites as $site): ?>
            <option value="<?= htmlspecialchars($site) ?>"><?= htmlspecialchars($site) ?></option>
        <?php endforeach; ?>
    </select>
    <small class="text-muted">Filtrar por obra/estoque</small>
</div>

<!-- Resumo rápido -->
<div class="row g-2 mb-3">
    <div class="col-4">
        <div class="card text-center py-2">
            <div class="fw-bold text-warning" style="font-size:1.5rem;"><?= count($pending) ?></div>
            <small class="text-muted">Pendentes</small>
        </div>
    </div>
    <div class="col-4">
        <div class="card text-center py-2">
            <div class="fw-bold text-info" style="font-size:1.5rem;"><?= count($inTransit) ?></div>
            <small class="text-muted">Em Trânsito</small>
        </div>
    </div>
    <div class="col-4">
        <div class="card text-center py-2">
            <div class="fw-bold text-success" style="font-size:1.5rem;"><?= count($delivered) ?></div>
            <small class="text-muted">Entregues</small>
        </div>
    </div>
</div>

<!-- Kanban -->
<div class="kanban-container">
    <!-- Coluna: Pendentes -->
    <div class="kanban-column">
        <div class="kanban-header bg-warning bg-opacity-25 text-dark">
            <i class="bi bi-hourglass-split"></i> Pendentes
            <span class="badge bg-warning text-dark float-end"><?= count($pending) ?></span>
        </div>
        <div class="kanban-body">
            <?php if (empty($pending)): ?>
                <div class="empty-column">
                    <i class="bi bi-check-circle"></i>
                    <small>Nenhuma solicitação pendente</small>
                </div>
            <?php else: ?>
                <?php foreach ($pending as $mov): ?>
                    <div class="kanban-card type-<?= $mov['type'] ?>" data-from="<?= htmlspecialchars($mov['from_site_name'] ?? $mov['from_location_name'] ?? '') ?>" data-to="<?= htmlspecialchars($mov['to_site_name'] ?? $mov['to_location_name'] ?? '') ?>">
                        <div class="card-material"><?= htmlspecialchars($mov['material_name']) ?></div>
                        <div class="card-meta">
                            <div><i class="bi bi-hash"></i> <?= number_format($mov['quantity'], 2, ',', '.') ?> <?= $mov['unit_abbr'] ?? '' ?></div>
                            <div><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($mov['from_site_name'] ?? 'N/A') ?></div>
                            <?php if ($mov['to_site_name']): ?>
                                <div><i class="bi bi-arrow-right"></i> <?= htmlspecialchars($mov['to_site_name']) ?></div>
                            <?php endif; ?>
                            <div><i class="bi bi-person"></i> <?= htmlspecialchars($mov['requested_by'] ?? '-') ?></div>
                            <div><i class="bi bi-clock"></i> <?= date('d/m H:i', strtotime($mov['created_at'])) ?></div>
                            <?php if ($mov['order_code']): ?>
                                <div><i class="bi bi-cart"></i> <?= htmlspecialchars($mov['order_code']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-1 mt-1">
                            <span class="kanban-badge bg-<?= $mov['type'] === 'transfer' ? 'primary' : 'danger' ?> text-white">
                                <?= $mov['type'] === 'transfer' ? 'Transferência' : 'Saída' ?>
                            </span>
                        </div>
                        <div class="card-actions d-flex gap-2">
                            <form method="POST" action="/admin/transport/in-transit" class="flex-grow-1">
                                <input type="hidden" name="id" value="<?= $mov['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-info w-100">
                                    <i class="bi bi-truck"></i> Saiu
                                </button>
                            </form>
                            <form method="POST" action="/admin/transport/delivered" class="flex-grow-1">
                                <input type="hidden" name="id" value="<?= $mov['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-success w-100">
                                    <i class="bi bi-check-lg"></i> Entregue
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Coluna: Em Trânsito -->
    <div class="kanban-column">
        <div class="kanban-header bg-info bg-opacity-25 text-dark">
            <i class="bi bi-truck"></i> Em Trânsito
            <span class="badge bg-info text-dark float-end"><?= count($inTransit) ?></span>
        </div>
        <div class="kanban-body">
            <?php if (empty($inTransit)): ?>
                <div class="empty-column">
                    <i class="bi bi-truck"></i>
                    <small>Nenhum material em trânsito</small>
                </div>
            <?php else: ?>
                <?php foreach ($inTransit as $mov): ?>
                    <div class="kanban-card type-<?= $mov['type'] ?>" data-from="<?= htmlspecialchars($mov['from_site_name'] ?? $mov['from_location_name'] ?? '') ?>" data-to="<?= htmlspecialchars($mov['to_site_name'] ?? $mov['to_location_name'] ?? '') ?>">
                        <div class="card-material"><?= htmlspecialchars($mov['material_name']) ?></div>
                        <div class="card-meta">
                            <div><i class="bi bi-hash"></i> <?= number_format($mov['quantity'], 2, ',', '.') ?> <?= $mov['unit_abbr'] ?? '' ?></div>
                            <div><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($mov['from_site_name'] ?? 'N/A') ?></div>
                            <?php if ($mov['to_site_name']): ?>
                                <div><i class="bi bi-arrow-right"></i> <?= htmlspecialchars($mov['to_site_name']) ?></div>
                            <?php endif; ?>
                            <div><i class="bi bi-person"></i> <?= htmlspecialchars($mov['requested_by'] ?? '-') ?></div>
                            <?php if ($mov['transit_at']): ?>
                                <div><i class="bi bi-clock"></i> Saiu: <?= date('d/m H:i', strtotime($mov['transit_at'])) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-1 mt-1">
                            <span class="kanban-badge bg-info text-white">Em trânsito</span>
                        </div>
                        <div class="card-actions">
                            <form method="POST" action="/admin/transport/delivered">
                                <input type="hidden" name="id" value="<?= $mov['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-success w-100">
                                    <i class="bi bi-check-circle"></i> Confirmar Entrega
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Coluna: Entregues -->
    <div class="kanban-column">
        <div class="kanban-header bg-success bg-opacity-25 text-dark">
            <i class="bi bi-check-circle"></i> Entregues (Últimos)
            <span class="badge bg-success float-end"><?= count($delivered) ?></span>
        </div>
        <div class="kanban-body">
            <?php if (empty($delivered)): ?>
                <div class="empty-column">
                    <i class="bi bi-inbox"></i>
                    <small>Nenhuma entrega recente</small>
                </div>
            <?php else: ?>
                <?php foreach ($delivered as $mov): ?>
                    <div class="kanban-card type-<?= $mov['type'] ?>" style="opacity:0.8;" data-from="<?= htmlspecialchars($mov['from_site_name'] ?? $mov['from_location_name'] ?? '') ?>" data-to="<?= htmlspecialchars($mov['to_site_name'] ?? $mov['to_location_name'] ?? '') ?>">
                        <div class="card-material"><?= htmlspecialchars($mov['material_name']) ?></div>
                        <div class="card-meta">
                            <div><i class="bi bi-hash"></i> <?= number_format($mov['quantity'], 2, ',', '.') ?> <?= $mov['unit_abbr'] ?? '' ?></div>
                            <div><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($mov['from_site_name'] ?? 'N/A') ?> → <?= htmlspecialchars($mov['to_site_name'] ?? 'Uso') ?></div>
                            <?php if ($mov['delivered_at']): ?>
                                <div><i class="bi bi-check2"></i> Entregue: <?= date('d/m H:i', strtotime($mov['delivered_at'])) ?></div>
                            <?php endif; ?>
                            <div><i class="bi bi-person-check"></i> <?= htmlspecialchars($mov['delivered_by'] ?? '-') ?></div>
                        </div>
                        <div class="d-flex gap-1 mt-1">
                            <span class="kanban-badge bg-success text-white">Entregue</span>
                            <span class="kanban-badge bg-<?= $mov['type'] === 'transfer' ? 'primary' : 'danger' ?> text-white">
                                <?= $mov['type'] === 'transfer' ? 'Transferência' : 'Saída' ?>
                            </span>
                        </div>
                        <div class="mt-2">
                            <form method="POST" action="/admin/transport/undo-delivered" onsubmit="return confirm('Desfazer entrega? O estoque será revertido.')">
                                <input type="hidden" name="id" value="<?= $mov['id'] ?>">
                                <button type="submit" class="btn btn-outline-warning btn-sm w-100" style="font-size:0.7rem;">
                                    <i class="bi bi-arrow-counterclockwise"></i> Desfazer Entrega
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Botão flutuante para atualizar (mobile) -->
<div class="d-md-none position-fixed" style="bottom:20px; right:20px; z-index:1050;">
    <a href="/admin/transport" class="btn btn-primary rounded-circle shadow-lg" style="width:56px; height:56px; display:flex; align-items:center; justify-content:center;">
        <i class="bi bi-arrow-clockwise" style="font-size:1.4rem;"></i>
    </a>
</div>

<script>
function filterKanban() {
    const filter = document.getElementById('filterSite').value.toLowerCase();
    document.querySelectorAll('.kanban-card').forEach(card => {
        if (!filter) { card.style.display = ''; return; }
        const from = (card.dataset.from || '').toLowerCase();
        const to = (card.dataset.to || '').toLowerCase();
        card.style.display = (from.includes(filter) || to.includes(filter)) ? '' : 'none';
    });
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/Views/admin/layouts/app.php';
?>
