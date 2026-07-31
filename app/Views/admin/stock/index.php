<?php
$pageTitle = $pageTitle ?? 'Estoque';
$currentPage = 'stock';
ob_start();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <form method="GET" action="/admin/stock" class="d-flex align-items-center gap-2">
            <select name="location_id" class="form-select form-select-sm" style="min-width:200px;" onchange="this.form.submit()">
                <option value="">Todos os estoques</option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?= $loc['id'] ?>" <?= ($selectedLocation ?? '') == $loc['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($loc['name']) ?>
                        <?= !empty($loc['construction_site_name']) ? ' (' . $loc['construction_site_name'] . ')' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="/admin/stock/locations" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-geo-alt"></i> Depósitos
        </a>
        <a href="/admin/stock/create" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Cadastrar Item
        </a>
        <a href="/admin/stock/bulk-create" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-collection"></i> Em Massa
        </a>
        <a href="/admin/stock/transfer" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-left-right"></i> Transferir
        </a>
        <a href="/admin/stock/movements" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-clock-history"></i> Movimentações
        </a>
    </div>
</div>

<?php if (empty($locations)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-geo-alt text-muted" style="font-size:3rem;"></i>
            <p class="text-muted mt-3 mb-2">Nenhum estoque/depósito cadastrado.</p>
            <a href="/admin/stock/locations" class="btn btn-primary">Cadastrar Primeiro Estoque</a>
        </div>
    </div>
<?php elseif (empty($items)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-box-seam text-muted" style="font-size:3rem;"></i>
            <p class="text-muted mt-3 mb-0">
                <?= $selectedLocation ? 'Nenhum item cadastrado neste estoque.' : 'Nenhum item no estoque. Cadastre o primeiro!' ?>
            </p>
        </div>
    </div>
<?php else: ?>
    <!-- Resumo rápido -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card stat-card">
                <div class="card-body py-2 px-3">
                    <small class="text-muted">Total de Itens</small>
                    <div class="stat-number" style="font-size:1.5rem;"><?= count($items) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card">
                <div class="card-body py-2 px-3">
                    <small class="text-muted">Depósitos</small>
                    <div class="stat-number" style="font-size:1.5rem;"><?= count(array_unique(array_column($items, 'stock_location_id'))) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card">
                <div class="card-body py-2 px-3">
                    <small class="text-muted">Estoque Baixo</small>
                    <div class="stat-number text-warning" style="font-size:1.5rem;">
                        <?= count(array_filter($items, fn($i) => $i['min_quantity'] > 0 && $i['quantity'] <= $i['min_quantity'])) ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card stat-card">
                <div class="card-body py-2 px-3">
                    <small class="text-muted">Zerados</small>
                    <div class="stat-number text-danger" style="font-size:1.5rem;">
                        <?= count(array_filter($items, fn($i) => $i['quantity'] <= 0)) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Material</th>
                            <th class="d-none d-md-table-cell">Especificação</th>
                            <th>Estoque</th>
                            <th class="text-center">Qtd</th>
                            <th class="text-end d-none d-md-table-cell">Valor Unit.</th>
                            <th class="text-center d-none d-sm-table-cell">Mín</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <?php
                            $isLow = $item['min_quantity'] > 0 && $item['quantity'] <= $item['min_quantity'];
                            $isEmpty = $item['quantity'] <= 0;
                            ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($item['material_name']) ?></strong>
                                    <br><small class="text-muted"><?= htmlspecialchars($item['unit_abbr'] ?? '') ?></small>
                                </td>
                                <td class="d-none d-md-table-cell">
                                    <small class="text-muted"><?= htmlspecialchars($item['specification'] ?? '-') ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($item['location_code'] ?? '') ?></span>
                                    <br><small><?= htmlspecialchars($item['location_name'] ?? '-') ?></small>
                                </td>
                                <td class="text-center">
                                    <strong class="<?= $isEmpty ? 'text-danger' : ($isLow ? 'text-warning' : '') ?>">
                                        <?= number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2, ',', '.') ?>
                                    </strong>
                                </td>
                                <td class="text-end d-none d-md-table-cell">
                                    <?= !empty($item['unit_price']) ? 'R$ ' . number_format($item['unit_price'], 2, ',', '.') : '<span class="text-muted">-</span>' ?>
                                </td>
                                <td class="text-center d-none d-sm-table-cell">
                                    <?= $item['min_quantity'] > 0 ? number_format($item['min_quantity'], 0, ',', '.') : '-' ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($isEmpty): ?>
                                        <span class="badge bg-danger">Zerado</span>
                                    <?php elseif ($isLow): ?>
                                        <span class="badge bg-warning text-dark">Baixo</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">OK</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="/admin/stock/edit/<?= $item['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="/admin/stock/delete" class="d-inline" onsubmit="return confirm('Remover este item do estoque?')">
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/Views/admin/layouts/app.php';
?>
