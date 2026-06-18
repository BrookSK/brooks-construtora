<?php $pageTitle = 'Histórico de Preços'; $currentPage = 'price_history'; ?>
<?php ob_start(); ?>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="/admin/orders/price-history" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">Filtrar por Material</label>
                <select class="form-select form-select-sm" name="material_id">
                    <option value="">Todos os materiais</option>
                    <?php foreach ($materials as $m): ?>
                    <option value="<?= $m['id'] ?>" <?= $filterMaterial == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['name']) ?> <?= $m['classification'] ? '(' . $m['classification'] . ')' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Filtrar por Fornecedor</label>
                <select class="form-select form-select-sm" name="supplier_id">
                    <option value="">Todos os fornecedores</option>
                    <?php foreach ($suppliers as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $filterSupplier == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-search"></i> Filtrar
                </button>
            </div>
            <?php if (\App\Core\Auth::isSuperAdmin()): ?>
            <div class="col-md-2">
                <form method="POST" action="/admin/orders/clear-price-history" onsubmit="return confirm('ATENÇÃO: Isso vai APAGAR todo o histórico de preços permanentemente. Confirma?')" class="d-inline">
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                        <i class="bi bi-trash"></i> Limpar
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if (empty($records)): ?>
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <i class="bi bi-graph-up" style="font-size:2.5rem;"></i>
        <p class="mt-2 mb-0">Nenhum histórico de preço registrado ainda.</p>
        <p class="small">Os preços são registrados automaticamente quando uma cotação é realizada.</p>
    </div>
</div>
<?php else: ?>

<!-- Desktop -->
<div class="card d-none d-md-block">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>Material</th>
                    <th>Fornecedor</th>
                    <th class="text-end">Valor Unit.</th>
                    <th class="text-center">Qtd</th>
                    <th>Pedido</th>
                    <th>Data</th>
                    <th class="text-center">Aprovado?</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $r): ?>
                <tr class="<?= $r['was_approved'] ? 'table-success' : '' ?>">
                    <td><strong><?= htmlspecialchars($r['material_name']) ?></strong></td>
                    <td><?= htmlspecialchars($r['supplier_name']) ?></td>
                    <td class="text-end">R$ <?= number_format($r['unit_price'], 2, ',', '.') ?></td>
                    <td class="text-center"><?= number_format($r['quantity'], $r['quantity'] == (int)$r['quantity'] ? 0 : 2) ?></td>
                    <td><a href="/admin/orders/show/<?= $r['order_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($r['order_code']) ?></a></td>
                    <td><small class="text-muted"><?= date('d/m/Y', strtotime($r['created_at'])) ?></small></td>
                    <td class="text-center">
                        <?= $r['was_approved'] ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Mobile -->
<div class="d-md-none">
    <?php 
    $currentMaterial = '';
    foreach ($records as $r): 
        if ($r['material_name'] !== $currentMaterial):
            $currentMaterial = $r['material_name'];
    ?>
    <h6 class="mt-3 mb-2 text-muted small text-uppercase"><?= htmlspecialchars($currentMaterial) ?></h6>
    <?php endif; ?>
    <div class="card mb-2 <?= $r['was_approved'] ? 'border-success' : '' ?>">
        <div class="card-body py-2 px-3">
            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-bold small"><?= htmlspecialchars($r['supplier_name']) ?></span>
                <strong class="text-<?= $r['was_approved'] ? 'success' : 'dark' ?>">R$ <?= number_format($r['unit_price'], 2, ',', '.') ?></strong>
            </div>
            <div class="d-flex justify-content-between mt-1" style="font-size:0.7rem; color:#888;">
                <span><?= htmlspecialchars($r['order_code']) ?> · <?= date('d/m/Y', strtotime($r['created_at'])) ?></span>
                <?= $r['was_approved'] ? '<span class="text-success fw-bold">Aprovado</span>' : '' ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
