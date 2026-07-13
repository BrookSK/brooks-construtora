<?php $pageTitle = 'Obras'; $currentPage = 'obras'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h5 class="mb-0"><i class="bi bi-buildings"></i> Obras</h5>
    <a href="/admin/obras/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nova Obra
    </a>
</div>

<?php if (!empty($flash)): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
    <?= $flash['message'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <span class="small text-muted">Status:</span>
            <a href="/admin/obras" class="btn btn-sm <?= empty($currentStatus) ? 'btn-primary' : 'btn-outline-secondary' ?>">Todas</a>
            <a href="/admin/obras?status=active" class="btn btn-sm <?= ($currentStatus ?? '') === 'active' ? 'btn-success' : 'btn-outline-success' ?>">Ativas</a>
            <a href="/admin/obras?status=completed" class="btn btn-sm <?= ($currentStatus ?? '') === 'completed' ? 'btn-info' : 'btn-outline-info' ?>">Concluídas</a>
            <a href="/admin/obras?status=inactive" class="btn btn-sm <?= ($currentStatus ?? '') === 'inactive' ? 'btn-secondary' : 'btn-outline-secondary' ?>">Inativas</a>
        </div>
    </div>
</div>

<!-- Lista de Obras -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nome da Obra</th>
                    <th class="d-none d-md-table-cell">Endereço</th>
                    <th class="d-none d-md-table-cell">Responsável</th>
                    <th class="text-center">Pedidos</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sites)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-buildings d-block mb-2" style="font-size:2rem;"></i>
                        Nenhuma obra cadastrada.
                        <br><a href="/admin/obras/create" class="btn btn-sm btn-primary mt-2">Cadastrar primeira obra</a>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($sites as $site): ?>
                <tr>
                    <td><span class="badge bg-dark"><?= htmlspecialchars($site['code'] ?? '-') ?></span></td>
                    <td>
                        <a href="/admin/obras/edit/<?= $site['id'] ?>" class="text-decoration-none fw-bold">
                            <?= htmlspecialchars($site['name']) ?>
                        </a>
                        <?php if (!empty($site['client_name'])): ?>
                        <br><small class="text-muted"><?= htmlspecialchars($site['client_name']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <?php if (!empty($site['address'])): ?>
                        <small><?= htmlspecialchars($site['address']) ?></small>
                        <?php if (!empty($site['city'])): ?>
                        <br><small class="text-muted"><?= htmlspecialchars($site['city']) ?><?= !empty($site['state']) ? '/' . $site['state'] : '' ?></small>
                        <?php endif; ?>
                        <?php else: ?>
                        <small class="text-muted">-</small>
                        <?php endif; ?>
                    </td>
                    <td class="d-none d-md-table-cell">
                        <small><?= htmlspecialchars($site['responsible_name'] ?? '-') ?></small>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-primary"><?= $site['orders_count'] ?? 0 ?></span>
                    </td>
                    <td>
                        <?php
                        $statusLabels = [
                            'active' => ['Ativa', 'success'],
                            'inactive' => ['Inativa', 'secondary'],
                            'completed' => ['Concluída', 'info'],
                        ];
                        $st = $statusLabels[$site['status']] ?? ['?', 'dark'];
                        ?>
                        <span class="badge bg-<?= $st[1] ?>"><?= $st[0] ?></span>
                    </td>
                    <td>
                        <a href="/admin/obras/edit/<?= $site['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
