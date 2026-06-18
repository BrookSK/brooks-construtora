<?php $pageTitle = 'Fornecedores'; $currentPage = 'suppliers'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <span class="badge bg-secondary"><?= count($suppliers) ?> fornecedores</span>
    <a href="/admin/suppliers/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Novo Fornecedor
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>CNPJ</th>
                    <th>E-mail</th>
                    <th>Telefone</th>
                    <th>Contato</th>
                    <th>Status</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($suppliers)): ?>
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">Nenhum fornecedor cadastrado.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($suppliers as $s): ?>
                <tr class="<?= !$s['active'] ? 'opacity-50' : '' ?>">
                    <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                    <td><?= htmlspecialchars($s['cnpj'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($s['email'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($s['phone'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($s['contact_person'] ?? '-') ?></td>
                    <td>
                        <?php if ($s['active']): ?>
                            <span class="badge bg-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <a href="/admin/suppliers/edit/<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php if ($s['active']): ?>
                        <form method="POST" action="/admin/suppliers/delete" class="d-inline" onsubmit="return confirm('Desativar este fornecedor?')">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Desativar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
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
