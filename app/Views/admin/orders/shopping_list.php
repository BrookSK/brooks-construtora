<?php $pageTitle = 'Lista de Compras'; $currentPage = 'orders'; ?>
<?php ob_start(); ?>

<style>
.spec-card { border: 1px solid #dee2e6; border-radius: 8px; margin-bottom: 1.2rem; overflow: hidden; }
.spec-card-header { background: #3a3b4e; color: #fff; padding: 10px 16px; font-weight: 700; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; display: flex; justify-content: space-between; align-items: center; }
.spec-card-header .badge { font-size: 0.7rem; font-weight: 500; }
.spec-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
.spec-table th { background: #f0f1f3; color: #555; padding: 8px 10px; text-align: left; font-size: 0.72rem; text-transform: uppercase; border-bottom: 1px solid #ddd; }
.spec-table td { padding: 8px 10px; border-bottom: 1px solid #eee; vertical-align: middle; }
.spec-table tr:last-child td { border-bottom: none; }
.spec-table tr:hover td { background: #f8f9fa; }
.order-link { font-size: 0.72rem; font-weight: 600; text-decoration: none; }
.summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 1.5rem; }
.summary-card { background: #fff; border: 1px solid #e8e8e8; border-radius: 8px; padding: 14px 16px; text-align: center; }
.summary-card .number { font-size: 1.5rem; font-weight: 700; color: #3a3b4e; }
.summary-card .label { font-size: 0.72rem; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
.empty-state { text-align: center; padding: 3rem 1rem; color: #888; }
.empty-state i { font-size: 3rem; margin-bottom: 0.5rem; display: block; }
@media (max-width: 767px) {
    .spec-table { font-size: 0.75rem; }
    .spec-table th, .spec-table td { padding: 6px 8px; }
    .summary-cards { grid-template-columns: 1fr 1fr; gap: 8px; }
}
</style>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <a href="/admin/orders" class="btn btn-sm btn-outline-secondary me-2">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>
    <div class="d-flex flex-wrap gap-1">
        <button class="btn btn-sm btn-outline-primary" onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimir
        </button>
    </div>
</div>

<?php if (empty($items)): ?>
<div class="empty-state">
    <i class="bi bi-cart-check"></i>
    <h5>Nenhum item pendente de compra</h5>
    <p class="text-muted">Todos os pedidos aprovados já foram marcados como comprados.</p>
    <a href="/admin/orders" class="btn btn-primary mt-2">Voltar aos Pedidos</a>
</div>

<?php else: ?>

<!-- Resumo -->
<div class="summary-cards">
    <div class="summary-card">
        <div class="number"><?= count($orders) ?></div>
        <div class="label">Pedidos pendentes</div>
    </div>
    <div class="summary-card">
        <div class="number"><?= count($items) ?></div>
        <div class="label">Itens para comprar</div>
    </div>
    <div class="summary-card">
        <div class="number"><?= count($grouped) ?></div>
        <div class="label">Especificações</div>
    </div>
</div>

<!-- Filtro de busca -->
<div class="mb-3">
    <input type="text" id="searchItems" class="form-control form-control-sm" placeholder="Buscar material, especificação, pedido, obra..." style="max-width:400px;">
</div>

<!-- Grupos por especificação -->
<?php foreach ($grouped as $key => $group): ?>
<div class="spec-card spec-group-card" data-spec="<?= htmlspecialchars($key) ?>">
    <div class="spec-card-header">
        <span><?= htmlspecialchars(mb_convert_case($group['label'], MB_CASE_TITLE, 'UTF-8')) ?></span>
        <span class="badge bg-light text-dark"><?= count($group['items']) ?> <?= count($group['items']) === 1 ? 'item' : 'itens' ?></span>
    </div>
    <table class="spec-table">
        <thead>
            <tr>
                <th style="width:30%;">Material</th>
                <th style="width:14%;">Classificação</th>
                <th style="width:10%; text-align:center;">Qtd</th>
                <th style="width:8%; text-align:center;">Und</th>
                <th style="width:14%;">Pedido</th>
                <th style="width:24%;">Obra</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($group['items'] as $item): ?>
            <tr class="item-row"
                data-search="<?= htmlspecialchars(mb_strtolower(($item['material_name'] ?? '') . ' ' . ($item['specification'] ?? '') . ' ' . ($item['classification'] ?? '') . ' ' . ($item['order_code'] ?? '') . ' ' . ($item['construction_site_name'] ?? ''), 'UTF-8')) ?>">
                <td><strong><?= htmlspecialchars($item['material_name']) ?></strong></td>
                <td><?= htmlspecialchars($item['classification'] ?? '-') ?></td>
                <td style="text-align:center;"><?= number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2, ',', '.') ?></td>
                <td style="text-align:center;"><?= htmlspecialchars($item['unit'] ?? '-') ?></td>
                <td>
                    <a href="/admin/orders/show/<?= $item['order_id'] ?>" class="order-link text-primary">
                        <?= htmlspecialchars($item['order_code']) ?>
                    </a>
                </td>
                <td>
                    <?php if (!empty($item['construction_site_name'])): ?>
                    <small><?= htmlspecialchars(($item['construction_site_code'] ?? '') . ' - ' . $item['construction_site_name']) ?></small>
                    <?php else: ?>
                    <small class="text-muted">-</small>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endforeach; ?>

<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchItems');
    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        const term = this.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.spec-group-card');

        cards.forEach(card => {
            const rows = card.querySelectorAll('.item-row');
            let hasVisible = false;

            rows.forEach(row => {
                const data = row.getAttribute('data-search') || '';
                const match = !term || data.includes(term);
                row.style.display = match ? '' : 'none';
                if (match) hasVisible = true;
            });

            card.style.display = hasVisible ? '' : 'none';
        });
    });
});
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
