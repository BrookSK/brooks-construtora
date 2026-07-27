<?php $pageTitle = 'Newsletter - Inscritos'; $currentPage = 'newsletter'; ob_start(); ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Lista de Inscritos</h6>
        <a href="/admin/newsletter/export" class="btn btn-sm btn-success">
            <i class="bi bi-download"></i> Exportar CSV
        </a>
    </div>
    <div class="card-body">
        <?php if (empty($subscribers)): ?>
            <p class="text-muted text-center py-3">Nenhum inscrito ainda.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>WhatsApp</th>
                            <th>Data de Inscrição</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscribers as $index => $sub): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($sub['name'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($sub['email']) ?></td>
                            <td><?= htmlspecialchars($sub['phone'] ?? '-') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($sub['subscribed_at'])) ?></td>
                            <td>
                                <span class="badge bg-<?= $sub['active'] ? 'success' : 'secondary' ?>">
                                    <?= $sub['active'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="/admin/newsletter/delete" class="d-inline" onsubmit="return confirm('Remover este inscrito?')">
                                    <input type="hidden" name="id" value="<?= $sub['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-muted small mt-2">
                Total: <?= count($subscribers) ?> inscrito(s)
            </div>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
