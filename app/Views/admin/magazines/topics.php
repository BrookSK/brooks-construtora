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
                                        <button type="button" class="btn btn-sm btn-primary btn-generate-magazine" 
                                                data-topic-id="<?= $topic['id'] ?>"
                                                data-topic-title="<?= htmlspecialchars($topic['title']) ?>">
                                            <i class="bi bi-journal-plus"></i> Gerar Revista
                                        </button>
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

<script>
(function() {
    document.querySelectorAll('.btn-generate-magazine').forEach(btn => {
        btn.addEventListener('click', async function() {
            const topicId = this.dataset.topicId;
            const topicTitle = this.dataset.topicTitle;
            
            if (!confirm('Gerar revista com o tema "' + topicTitle + '"?\n\nA geração será feita em segundo plano — você pode continuar navegando normalmente.')) {
                return;
            }

            // Desabilita todos os botões
            document.querySelectorAll('.btn-generate-magazine').forEach(b => {
                b.disabled = true;
            });

            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Iniciando...';

            try {
                const fd = new FormData();
                fd.append('topic_id', topicId);

                const resp = await fetch('/admin/magazines/generate', { method: 'POST', body: fd });
                const data = await resp.json();

                if (data.success) {
                    this.innerHTML = '<i class="bi bi-check-circle"></i> Geração iniciada!';
                    this.className = 'btn btn-sm btn-success';
                    
                    // Força o indicador global a começar o polling rápido
                    if (window.forceJobPoll) window.forceJobPoll();

                    // Mostra mensagem
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show mt-3';
                    alert.innerHTML = '<i class="bi bi-check-circle"></i> <strong>Geração iniciada em segundo plano!</strong> ' +
                        'Você pode continuar navegando normalmente. Acompanhe o progresso no indicador no canto inferior direito.' +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                    this.closest('.card').after(alert);
                } else {
                    alert(data.error || 'Erro ao iniciar geração.');
                    document.querySelectorAll('.btn-generate-magazine').forEach(b => {
                        b.disabled = false;
                    });
                    this.innerHTML = '<i class="bi bi-journal-plus"></i> Gerar Revista';
                }
            } catch(e) {
                alert('Erro de conexão: ' + e.message);
                document.querySelectorAll('.btn-generate-magazine').forEach(b => {
                    b.disabled = false;
                });
                this.innerHTML = '<i class="bi bi-journal-plus"></i> Gerar Revista';
            }
        });
    });
})();
</script>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
