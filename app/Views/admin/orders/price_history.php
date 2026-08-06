<?php $pageTitle = 'Histórico de Preços'; $currentPage = 'price_history'; ?>
<?php ob_start(); ?>

<style>
.pagination .page-link {
    background-color: #fff;
    border: 1px solid #dee2e6;
    color: #6c757d;
    font-size: 0.8rem;
    padding: 0.35rem 0.65rem;
}
.pagination .page-link:hover {
    background-color: #f8f9fa;
    border-color: #adb5bd;
    color: #3a3b4e;
}
.pagination .page-item.active .page-link {
    background-color: var(--color-primary);
    border-color: var(--color-primary);
    color: #fff;
}
.pagination .page-item.disabled .page-link {
    background-color: #f8f9fa;
    border-color: #e9ecef;
    color: #ced4da;
}
</style>

<!-- Filtros -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <span class="badge bg-secondary" style="font-size:0.75rem;"><?= $totalRecords ?> registros</span>
    <?php if ($totalPages > 1): ?>
    <small class="text-muted">Página <?= $paginaAtual ?> de <?= $totalPages ?></small>
    <?php endif; ?>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <form method="GET" action="/admin/orders/price-history" class="col-md-10">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small mb-0">Filtrar por Material</label>
                        <select class="form-select form-select-sm" name="material_id">
                            <option value="">Todos os materiais</option>
                            <?php foreach ($materials as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= $filterMaterial == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['name']) ?> <?= $m['classification'] ? '(' . $m['classification'] . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small mb-0">Filtrar por Fornecedor</label>
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
                </div>
            </form>
            <?php if (\App\Core\Auth::isSuperAdmin()): ?>
            <div class="col-md-2">
                <form method="POST" action="/admin/orders/clear-price-history" onsubmit="return confirm('ATENÇÃO: Isso vai APAGAR todo o histórico de preços permanentemente. Confirma?')">
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                        <i class="bi bi-trash"></i> Limpar
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
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

<?php
// Montar query string para paginação (mantém filtros ativos)
$paginationParams = [];
if ($filterMaterial) $paginationParams['material_id'] = $filterMaterial;
if ($filterSupplier) $paginationParams['supplier_id'] = $filterSupplier;
$paginationQuery = http_build_query($paginationParams);
$paginationBase = '/admin/orders/price-history?' . ($paginationQuery ? $paginationQuery . '&' : '') . 'page=';
?>

<!-- Desktop -->
<div class="d-none d-md-block">
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
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
                    <?php foreach ($records as $i => $r): ?>
                    <tr class="<?= $r['was_approved'] ? 'table-success' : '' ?>">
                        <td class="text-muted"><?= $i + 1 ?></td>
                        <td><strong><?= htmlspecialchars($r['material_name']) ?></strong></td>
                        <td><?= htmlspecialchars($r['supplier_name']) ?></td>
                        <td class="text-end"><strong>R$ <?= number_format($r['unit_price'], 2, ',', '.') ?></strong></td>
                        <td class="text-center"><?= number_format($r['quantity'], $r['quantity'] == (int)$r['quantity'] ? 0 : 2) ?></td>
                        <td>
                            <a href="/admin/orders/show/<?= $r['order_id'] ?>" class="fw-bold text-decoration-none">
                                <?= htmlspecialchars($r['order_code']) ?>
                            </a>
                        </td>
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
</div>

<!-- Mobile Cards -->
<div class="d-md-none">
    <?php foreach ($records as $i => $r): ?>
    <a href="/admin/orders/show/<?= $r['order_id'] ?>" class="text-decoration-none">
        <div class="card mb-2 <?= $r['was_approved'] ? 'border-success' : '' ?>">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small me-1"><?= $i + 1 ?>.</span>
                        <strong class="text-dark small"><?= htmlspecialchars($r['material_name']) ?></strong>
                    </div>
                    <strong class="text-<?= $r['was_approved'] ? 'success' : 'dark' ?>">R$ <?= number_format($r['unit_price'], 2, ',', '.') ?></strong>
                </div>
                <div class="d-flex justify-content-between mt-1">
                    <span class="text-muted" style="font-size:0.7rem;"><?= htmlspecialchars($r['supplier_name']) ?></span>
                    <span class="text-muted" style="font-size:0.7rem;"><?= htmlspecialchars($r['order_code']) ?> · <?= date('d/m/Y', strtotime($r['created_at'])) ?></span>
                </div>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<?php if ($totalPages > 1): ?>
<!-- Paginação -->
<div class="d-flex justify-content-center mt-3">
    <nav>
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $paginaAtual <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $paginationBase . ($paginaAtual - 1) ?>">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
            <?php
            $startPage = max(1, $paginaAtual - 3);
            $endPage = min($totalPages, $paginaAtual + 3);
            if ($startPage > 1): ?>
                <li class="page-item"><a class="page-link" href="<?= $paginationBase ?>1">1</a></li>
                <?php if ($startPage > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
            <?php endif; ?>
            <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                <li class="page-item <?= $p == $paginaAtual ? 'active' : '' ?>">
                    <a class="page-link" href="<?= $paginationBase . $p ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($endPage < $totalPages): ?>
                <?php if ($endPage < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                <li class="page-item"><a class="page-link" href="<?= $paginationBase . $totalPages ?>"><?= $totalPages ?></a></li>
            <?php endif; ?>
            <li class="page-item <?= $paginaAtual >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= $paginationBase . ($paginaAtual + 1) ?>">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
</div>
<?php endif; ?>

<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
