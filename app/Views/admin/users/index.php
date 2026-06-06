<?php $pageTitle = 'Usuários'; $currentPage = 'users'; ob_start(); ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Gerenciar Usuários</h6>
        <a href="/admin/users/create" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> Novo Usuário
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Permissão</th>
                        <th>Status</th>
                        <th>Último Login</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <span class="badge bg-<?= $u['role'] === 'super_admin' ? 'danger' : ($u['role'] === 'admin' ? 'primary' : 'info') ?>">
                                <?= ucfirst(str_replace('_', ' ', $u['role'])) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $u['active'] ? 'success' : 'secondary' ?>">
                                <?= $u['active'] ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>
                        <td><?= $u['last_login'] ? date('d/m/Y H:i', strtotime($u['last_login'])) : 'Nunca' ?></td>
                        <td>
                            <a href="/admin/users/edit/<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if ($u['id'] != \App\Core\Auth::id()): ?>
                            <form method="POST" action="/admin/users/delete" class="d-inline" onsubmit="return confirm('Excluir este usuário?')">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
