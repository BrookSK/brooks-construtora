<?php $pageTitle = 'Fornecedores'; $currentPage = 'suppliers'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="badge bg-secondary"><?= count($suppliers) ?> fornecedores</span>
    <a href="/admin/suppliers/create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> Novo Fornecedor
    </a>
</div>

<?php if (empty($suppliers)): ?>
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <i class="bi bi-building" style="font-size:2.5rem;"></i>
        <p class="mt-2 mb-0">Nenhum fornecedor cadastrado.</p>
        <a href="/admin/suppliers/create" class="btn btn-primary mt-3">Cadastrar Primeiro</a>
    </div>
</div>
<?php else: ?>

<!-- Desktop -->
<div class="card d-none d-md-block">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>CNPJ</th>
                    <th>E-mail</th>
                    <th>Telefone</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suppliers as $s): ?>
                <tr class="<?= !$s['active'] ? 'opacity-50' : '' ?>">
                    <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                    <td><?= htmlspecialchars($s['cnpj'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($s['email'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($s['phone'] ?? '-') ?></td>
                    <td><?= $s['active'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>' ?></td>
                    <td class="text-end">
                        <a href="/admin/suppliers/contacts/<?= $s['id'] ?>" class="btn btn-sm btn-outline-success" title="Vendedores"><i class="bi bi-people"></i></a>
                        <a href="/admin/suppliers/edit/<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <?php if ($s['active']): ?>
                        <form method="POST" action="/admin/suppliers/delete" class="d-inline" onsubmit="return confirm('Desativar?')">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        <?php elseif (\App\Core\Auth::isSuperAdmin()): ?>
                        <form method="POST" action="/admin/suppliers/delete" class="d-inline" onsubmit="return confirm('EXCLUIR permanentemente este fornecedor? Não pode ser desfeito.')">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <input type="hidden" name="action" value="permanent">
                            <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Mobile -->
<div class="d-md-none">
    <?php foreach ($suppliers as $s): ?>
    <div class="card mb-2 <?= !$s['active'] ? 'opacity-50' : '' ?>">
        <div class="card-body py-2 px-3">
            <div class="d-flex justify-content-between align-items-center">
                <strong><?= htmlspecialchars($s['name']) ?></strong>
                <?= $s['active'] ? '<span class="badge bg-success" style="font-size:0.65rem;">Ativo</span>' : '<span class="badge bg-secondary" style="font-size:0.65rem;">Inativo</span>' ?>
            </div>
            <?php if ($s['phone'] || $s['email']): ?>
            <div class="text-muted small mt-1">
                <?= $s['phone'] ? '<i class="bi bi-telephone"></i> ' . htmlspecialchars($s['phone']) : '' ?>
                <?= $s['email'] ? ' <i class="bi bi-envelope ms-2"></i> ' . htmlspecialchars($s['email']) : '' ?>
            </div>
            <?php endif; ?>
            <div class="d-flex gap-2 mt-2">
                <a href="/admin/suppliers/edit/<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary flex-grow-1"><i class="bi bi-pencil"></i> Editar</a>
                <a href="/admin/suppliers/contacts/<?= $s['id'] ?>" class="btn btn-sm btn-outline-success" title="Vendedores"><i class="bi bi-people"></i></a>
                <?php if ($s['active']): ?>
                <form method="POST" action="/admin/suppliers/delete" onsubmit="return confirm('Desativar?')">
                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
                <?php elseif (\App\Core\Auth::isSuperAdmin()): ?>
                <form method="POST" action="/admin/suppliers/delete" onsubmit="return confirm('EXCLUIR permanentemente?')">
                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                    <input type="hidden" name="action" value="permanent">
                    <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
