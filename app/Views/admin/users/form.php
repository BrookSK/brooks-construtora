<?php $pageTitle = $editUser ? 'Editar Usuário' : 'Novo Usuário'; $currentPage = 'users'; ob_start(); ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= $editUser ? '/admin/users/update' : '/admin/users/store' ?>">
            <?php if ($editUser): ?>
                <input type="hidden" name="id" value="<?= $editUser['id'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nome *</label>
                    <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($editUser['name'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">E-mail *</label>
                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($editUser['email'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Senha <?= $editUser ? '(deixe em branco para manter)' : '*' ?></label>
                    <input type="password" class="form-control" name="password" <?= !$editUser ? 'required' : '' ?>>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Permissão *</label>
                    <select class="form-select" name="role" required>
                        <?php if (\App\Core\Auth::isSuperAdmin()): ?>
                            <option value="super_admin" <?= ($editUser['role'] ?? '') === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                        <?php endif; ?>
                        <option value="admin" <?= ($editUser['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="designer" <?= ($editUser['role'] ?? '') === 'designer' ? 'selected' : '' ?>>Designer</option>
                        <option value="editor" <?= ($editUser['role'] ?? '') === 'editor' ? 'selected' : '' ?>>Editor</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="active">
                        <option value="1" <?= ($editUser['active'] ?? 1) == 1 ? 'selected' : '' ?>>Ativo</option>
                        <option value="0" <?= ($editUser['active'] ?? 1) == 0 ? 'selected' : '' ?>>Inativo</option>
                    </select>
                </div>
            </div>

            <div class="mt-3 text-muted small">
                <strong>Permissões por cargo:</strong><br>
                <strong>Super Admin:</strong> Acesso total ao sistema.<br>
                <strong>Admin:</strong> Dashboard, configurações, newsletter, usuários, revistas (editar e publicar).<br>
                <strong>Designer:</strong> Dashboard, revistas (editar - upload de capa e imagens, revisão de texto).<br>
                <strong>Editor:</strong> Dashboard, visualizar revistas.
            </div>

            <div class="mt-4 d-flex justify-content-between">
                <a href="/admin/users" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> <?= $editUser ? 'Atualizar' : 'Criar Usuário' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
