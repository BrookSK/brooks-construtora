<?php $pageTitle = 'Editar Revista: ' . htmlspecialchars($magazine['title']); $currentPage = 'magazines'; ob_start(); ?>

<div class="row g-4">
    <!-- Sidebar com status e ações -->
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Status & Ações</h6></div>
            <div class="card-body">
                <?php
                    $statusLabels = [
                        'draft' => 'Rascunho',
                        'generated' => 'Gerada pela IA',
                        'review' => 'Em Revisão',
                        'approved' => 'Aprovada',
                        'published' => 'Publicada',
                    ];
                ?>
                <p><strong>Status:</strong> <?= $statusLabels[$magazine['status']] ?? $magazine['status'] ?></p>
                <p><strong>Criada:</strong> <?= date('d/m/Y H:i', strtotime($magazine['created_at'])) ?></p>

                <?php if ($magazine['status'] !== 'published'): ?>
                    <div class="d-grid gap-2">
                        <?php if (in_array($magazine['status'], ['generated', 'review'])): ?>
                        <form method="POST" action="/admin/magazines/approve">
                            <input type="hidden" name="magazine_id" value="<?= $magazine['id'] ?>">
                            <button type="submit" class="btn btn-success w-100" onclick="return confirm('Aprovar esta revista?')">
                                <i class="bi bi-check-circle"></i> Aprovar Revista
                            </button>
                        </form>
                        <?php endif; ?>

                        <?php if ($magazine['status'] === 'approved' && \App\Core\Auth::hasPermission('magazines.publish')): ?>
                        <form method="POST" action="/admin/magazines/publish">
                            <input type="hidden" name="magazine_id" value="<?= $magazine['id'] ?>">
                            <button type="submit" class="btn btn-primary w-100" onclick="return confirm('Publicar e enviar para os assinantes da newsletter?')">
                                <i class="bi bi-send"></i> Publicar & Enviar Newsletter
                            </button>
                        </form>
                        <?php endif; ?>

                        <a href="/admin/magazines/preview/<?= $magazine['id'] ?>" class="btn btn-outline-info" target="_blank">
                            <i class="bi bi-eye"></i> Preview da Revista
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upload da Capa -->
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Capa da Revista</h6></div>
            <div class="card-body">
                <div id="cover-preview" class="mb-3 text-center">
                    <?php if ($magazine['cover_image']): ?>
                        <img src="<?= $magazine['cover_image'] ?>" alt="Capa" class="img-fluid rounded" style="max-height: 300px;">
                    <?php else: ?>
                        <div class="bg-light rounded p-4">
                            <i class="bi bi-image display-4 text-muted"></i>
                            <p class="text-muted mt-2">Nenhuma capa definida</p>
                        </div>
                    <?php endif; ?>
                </div>
                <form id="cover-form" enctype="multipart/form-data">
                    <input type="hidden" name="magazine_id" value="<?= $magazine['id'] ?>">
                    <input type="file" class="form-control" name="cover" id="cover-input" accept="image/*">
                    <button type="submit" class="btn btn-primary btn-sm mt-2 w-100">
                        <i class="bi bi-upload"></i> Enviar Capa
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Conteúdo editável -->
    <div class="col-md-8">
        <form method="POST" action="/admin/magazines/update">
            <input type="hidden" name="magazine_id" value="<?= $magazine['id'] ?>">

            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">Informações Gerais</h6></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Título</label>
                        <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($magazine['title']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subtítulo / Tema</label>
                        <input type="text" class="form-control" name="subtitle" value="<?= htmlspecialchars($magazine['subtitle'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Páginas -->
            <?php foreach ($pages as $page): ?>
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Página <?= $page['page_number'] ?> - <?= ucfirst(str_replace('_', ' ', $page['layout_type'])) ?></h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Título da Seção</label>
                        <input type="text" class="form-control" name="pages[<?= $page['id'] ?>][title]" value="<?= htmlspecialchars($page['title'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Conteúdo</label>
                        <textarea class="form-control" name="pages[<?= $page['id'] ?>][content]" rows="5"><?= htmlspecialchars($page['content'] ?? '') ?></textarea>
                    </div>
                    <div class="row align-items-end">
                        <div class="col-md-8">
                            <label class="form-label">Imagem da Página</label>
                            <form class="page-image-form" enctype="multipart/form-data" data-page-id="<?= $page['id'] ?>">
                                <input type="hidden" name="page_id" value="<?= $page['id'] ?>">
                                <div class="input-group">
                                    <input type="file" class="form-control" name="image" accept="image/*">
                                    <button type="submit" class="btn btn-outline-primary">Enviar</button>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-4 text-center">
                            <?php if ($page['image_url']): ?>
                                <img src="<?= $page['image_url'] ?>" alt="Imagem" class="img-fluid rounded" style="max-height: 100px;">
                            <?php else: ?>
                                <span class="text-muted small">Sem imagem</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-lg"></i> Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Upload da capa via AJAX
document.getElementById('cover-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('/admin/magazines/upload-cover', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('cover-preview').innerHTML = '<img src="' + data.url + '" alt="Capa" class="img-fluid rounded" style="max-height: 300px;">';
            alert('Capa atualizada com sucesso!');
        } else {
            alert(data.error || 'Erro ao enviar capa.');
        }
    })
    .catch(() => alert('Erro ao enviar capa.'));
});

// Upload de imagens das páginas via AJAX
document.querySelectorAll('.page-image-form').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('/admin/magazines/upload-image', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('Imagem atualizada!');
                location.reload();
            } else {
                alert(data.error || 'Erro ao enviar imagem.');
            }
        })
        .catch(() => alert('Erro ao enviar imagem.'));
    });
});
</script>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
