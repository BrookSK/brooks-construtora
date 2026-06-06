<?php $pageTitle = 'Temas de Revista'; $currentPage = 'topics'; ob_start(); ?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Gerar Novos Temas com IA</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/magazines/generate-topics">
                    <div class="mb-3">
                        <label class="form-label">Quantidade de temas</label>
                        <select class="form-select" name="quantity">
                            <option value="5">5 temas</option>
                            <option value="10" selected>10 temas</option>
                            <option value="15">15 temas</option>
                            <option value="20">20 temas</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" onclick="this.disabled=true; this.innerHTML='<span class=\'spinner-border spinner-border-sm\'></span> Gerando...'; this.form.submit();">
                        <i class="bi bi-lightbulb"></i> Gerar Temas com IA
                    </button>
                </form>
                <small class="text-muted mt-2 d-block">A IA irá sugerir temas relevantes sobre construção, reformas e arquitetura.</small>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Temas Disponíveis</h6>
            </div>
            <div class="card-body">
                <?php if (empty($topics)): ?>
                    <p class="text-muted text-center py-3">Nenhum tema gerado ainda. Use o formulário ao lado para gerar.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tema</th>
                                    <th>Status</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topics as $topic): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($topic['title']) ?></strong>
                                        <?php if ($topic['description']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($topic['description']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $topic['used'] ? 'secondary' : 'success' ?>">
                                            <?= $topic['used'] ? 'Usado' : 'Disponível' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!$topic['used']): ?>
                                        <form method="POST" action="/admin/magazines/generate" onsubmit="return confirm('Gerar revista com este tema?')">
                                            <input type="hidden" name="topic_id" value="<?= $topic['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="bi bi-journal-plus"></i> Gerar Revista
                                            </button>
                                        </form>
                                        <?php else: ?>
                                            <span class="text-muted small">Usado em <?= date('d/m/Y', strtotime($topic['used_at'])) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
