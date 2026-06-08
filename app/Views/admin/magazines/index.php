<?php $pageTitle = 'Revistas'; $currentPage = 'magazines'; ob_start(); ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Todas as Revistas</h6>
        <a href="/admin/magazines/topics" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> Gerar Nova Revista
        </a>
    </div>
    <div class="card-body">
        <?php if (empty($magazines)): ?>
            <div class="text-center py-5">
                <i class="bi bi-journal-richtext display-4 text-muted"></i>
                <p class="text-muted mt-3">Nenhuma revista criada ainda.</p>
                <a href="/admin/magazines/topics" class="btn btn-primary">Gerar primeira revista</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Capa</th>
                            <th>Tema</th>
                            <th>Status</th>
                            <th>Criada em</th>
                            <th>Publicada em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($magazines as $mag): ?>
                        <tr>
                            <td>
                                <?php if ($mag['cover_image']): ?>
                                    <img src="<?= $mag['cover_image'] ?>" alt="Capa" style="width: 50px; height: 70px; object-fit: cover; border-radius: 4px;">
                                <?php else: ?>
                                    <div style="width: 50px; height: 70px; background: #e9ecef; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-image text-muted"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($mag['topic_title'] ?? $mag['title']) ?></strong>
                                <?php if ($mag['topic_description'] ?? null): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars(mb_strimwidth($mag['topic_description'], 0, 80, '...')) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                    $statusColors = [
                                        'draft' => 'secondary',
                                        'generated' => 'warning',
                                        'review' => 'info',
                                        'approved' => 'primary',
                                        'published' => 'success',
                                    ];
                                    $statusLabels = [
                                        'draft' => 'Rascunho',
                                        'generated' => 'Gerada',
                                        'review' => 'Em Revisão',
                                        'approved' => 'Aprovada',
                                        'published' => 'Publicada',
                                    ];
                                ?>
                                <span class="badge bg-<?= $statusColors[$mag['status']] ?? 'secondary' ?>">
                                    <?= $statusLabels[$mag['status']] ?? $mag['status'] ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($mag['created_at'])) ?></td>
                            <td><?= $mag['published_at'] ? date('d/m/Y', strtotime($mag['published_at'])) : '-' ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if (\App\Core\Auth::hasPermission('magazines.edit')): ?>
                                    <a href="/admin/magazines/edit/<?= $mag['id'] ?>" class="btn btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="/admin/magazines/preview/<?= $mag['id'] ?>" class="btn btn-outline-info" title="Preview" target="_blank">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (\App\Core\Auth::isSuperAdmin()): ?>
                                    <form method="POST" action="/admin/magazines/delete" class="d-inline" onsubmit="return confirm('Excluir esta revista?')">
                                        <input type="hidden" name="magazine_id" value="<?= $mag['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Excluir">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
