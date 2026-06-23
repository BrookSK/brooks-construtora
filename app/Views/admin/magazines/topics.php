<?php $pageTitle = 'Temas de Revista'; $currentPage = 'topics'; ob_start(); ?>

<div class="row g-4">
    <div class="col-md-4">
        <!-- Gerar temas com IA -->
        <div class="card mb-3">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-stars"></i> Gerar Temas com IA</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/magazines/generate-topics">
                    <div class="mb-3">
                        <label class="form-label small">Quantidade de temas</label>
                        <select class="form-select" name="quantity">
                            <option value="5">5 temas</option>
                            <option value="10" selected>10 temas</option>
                            <option value="15">15 temas</option>
                            <option value="20">20 temas</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Prompt complementar (opcional)</label>
                        <textarea class="form-control form-control-sm" name="custom_prompt" rows="3" placeholder="Ex: Foque nos temas mais relevantes da semana, ou escreva sobre tendências de 2026..."><?= htmlspecialchars(\App\Models\Setting::get('magazine_custom_prompt', '')) ?></textarea>
                        <small class="text-muted">Instruções adicionais para a IA ao gerar os temas.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">URLs de fontes (opcional)</label>
                        <textarea class="form-control form-control-sm" name="source_urls" rows="3" placeholder="Cole links de referência, um por linha. A IA usará como base para os temas."></textarea>
                        <small class="text-muted">A IA vai criar temas baseados nessas fontes.</small>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" onclick="this.disabled=true; this.innerHTML='<span class=\'spinner-border spinner-border-sm\'></span> Gerando...'; this.form.submit();">
                        <i class="bi bi-lightbulb"></i> Gerar Temas com IA
                    </button>
                </form>
            </div>
        </div>

        <!-- Criar tema manualmente -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-pencil"></i> Criar Tema Manual</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="/admin/magazines/add-topic">
                    <div class="mb-3">
                        <label class="form-label small">Título do tema *</label>
                        <input type="text" class="form-control form-control-sm" name="title" required placeholder="Ex: Tendências de Sustentabilidade 2026">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Descrição</label>
                        <textarea class="form-control form-control-sm" name="description" rows="2" placeholder="Breve descrição do que a revista deve abordar"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">URLs de fontes (opcional)</label>
                        <textarea class="form-control form-control-sm" name="source_urls" rows="2" placeholder="Links de referência (um por linha)"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-plus-circle"></i> Adicionar Tema
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Temas Disponíveis</h6>
            <a href="/admin/magazines" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
        </div>

        <!-- Botão criar revista do zero -->
        <div class="card mb-3 border-success">
            <div class="card-body py-2 d-flex justify-content-between align-items-center">
                <span class="small"><i class="bi bi-journal-plus text-success"></i> Quer criar uma revista sem IA?</span>
                <a href="/admin/magazines/create-manual" class="btn btn-sm btn-success"><i class="bi bi-pencil-square"></i> Criar Revista Manual</a>
            </div>
        </div>

        <div class="card">
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
