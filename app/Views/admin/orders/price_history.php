<?php $pageTitle = 'Histórico de Preços'; $currentPage = 'price_history'; ?>
<?php ob_start(); ?>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <form method="GET" action="/admin/orders/price-history" class="col-md-10">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small">Filtrar por Material</label>
                        <select class="form-select form-select-sm" name="material_id">
                            <option value="">Todos os materiais</option>
                            <?php foreach ($materials as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= $filterMaterial == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['name']) ?> <?= $m['classification'] ? '(' . $m['classification'] . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
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

<!-- Info de paginação -->
<div class="d-flex justify-content-between align-items-center mb-2">
    <small class="text-muted">
        Mostrando <strong><?= $offset + 1 ?></strong> a <strong><?= min($offset + $perPage, $totalRecords) ?></strong> de <strong><?= $totalRecords ?></strong> registros
    </small>
    <?php if ($totalPages > 1): ?>
    <small class="text-muted">Página <strong><?= $currentPage ?></strong> de <strong><?= $totalPages ?></strong></small>
    <?php endif; ?>
</div>

<!-- Tabela Desktop -->
<div class="card d-none d-md-block">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="text-center" style="width:45px;">#</th>
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
                    <td class="text-center text-muted fw-bold"><?= $i + 1 ?></td>
                    <td><strong><?= htmlspecialchars($r['material_name']) ?></strong></td>
                    <td><?= htmlspecialchars($r['supplier_name']) ?></td>
                    <td class="text-end">R$ <?= number_format($r['unit_price'], 2, ',', '.') ?></td>
                    <td class="text-center"><?= number_format($r['quantity'], $r['quantity'] == (int)$r['quantity'] ? 0 : 2) ?></td>
                    <td>
                        <a href="/admin/orders/show/<?= $r['order_id'] ?>" class="text-decoration-none">
                            <span class="badge bg-primary"><?= htmlspecialchars($r['order_code']) ?></span>
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

    <?php if ($totalPages > 1): ?>
    <!-- Paginação -->
    <div class="card-footer d-flex justify-content-center py-2">
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $paginationBase . ($currentPage - 1) ?>" title="Anterior">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
                <?php
                $startPage = max(1, $currentPage - 3);
                $endPage = min($totalPages, $currentPage + 3);
                if ($startPage > 1): ?>
                    <li class="page-item"><a class="page-link" href="<?= $paginationBase ?>1">1</a></li>
                    <?php if ($startPage > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                <?php endif; ?>
                <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                    <li class="page-item <?= $p == $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="<?= $paginationBase . $p ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                    <li class="page-item"><a class="page-link" href="<?= $paginationBase . $totalPages ?>"><?= $totalPages ?></a></li>
                <?php endif; ?>
                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $paginationBase . ($currentPage + 1) ?>" title="Próxima">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Cards Mobile -->
<div class="d-md-none">
    <?php foreach ($records as $i => $r): ?>
    <div class="card mb-2 <?= $r['was_approved'] ? 'border-success' : '' ?>">
        <div class="card-body py-2 px-3">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <span class="badge bg-light text-dark me-1"><?= $i + 1 ?></span>
                    <strong class="small"><?= htmlspecialchars($r['material_name']) ?></strong>
                </div>
                <strong class="text-<?= $r['was_approved'] ? 'success' : 'dark' ?>">R$ <?= number_format($r['unit_price'], 2, ',', '.') ?></strong>
            </div>
            <div class="d-flex justify-content-between mt-1" style="font-size:0.7rem; color:#888;">
                <span><?= htmlspecialchars($r['supplier_name']) ?> · <a href="/admin/orders/show/<?= $r['order_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($r['order_code']) ?></a> · <?= date('d/m/Y', strtotime($r['created_at'])) ?></span>
                <?= $r['was_approved'] ? '<span class="text-success fw-bold">Aprovado</span>' : '' ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php if ($totalPages > 1): ?>
    <!-- Paginação Mobile -->
    <div class="d-flex justify-content-center mt-3 mb-3">
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $paginationBase . ($currentPage - 1) ?>"><i class="bi bi-chevron-left"></i></a>
                </li>
                <?php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                if ($startPage > 1): ?>
                    <li class="page-item"><a class="page-link" href="<?= $paginationBase ?>1">1</a></li>
                    <?php if ($startPage > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                <?php endif; ?>
                <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                    <li class="page-item <?= $p == $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="<?= $paginationBase . $p ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                    <li class="page-item"><a class="page-link" href="<?= $paginationBase . $totalPages ?>"><?= $totalPages ?></a></li>
                <?php endif; ?>
                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $paginationBase . ($currentPage + 1) ?>"><i class="bi bi-chevron-right"></i></a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
