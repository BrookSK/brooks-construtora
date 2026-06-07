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

<!-- Modal de Progresso da Geração -->
<div class="modal fade" id="generationModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">
                    <i class="bi bi-magic"></i> Gerando Revista
                </h5>
            </div>
            <div class="modal-body">
                <!-- Status geral -->
                <div id="gen-status" class="mb-3">
                    <div class="d-flex align-items-center mb-2">
                        <span id="gen-status-icon" class="me-2">
                            <span class="spinner-border spinner-border-sm text-primary"></span>
                        </span>
                        <span id="gen-status-text" class="fw-semibold">Gerando conteúdo da revista...</span>
                    </div>
                    <small id="gen-status-detail" class="text-muted">Aguarde enquanto a IA cria o conteúdo das páginas.</small>
                </div>

                <!-- Barra de progresso -->
                <div class="progress mb-3" style="height: 24px;">
                    <div id="gen-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                        0%
                    </div>
                </div>

                <!-- Log de etapas -->
                <div id="gen-log" class="border rounded p-2" style="max-height: 200px; overflow-y: auto; background: #f8f9fa; font-size: 0.8rem;">
                    <div class="gen-log-entry text-muted">
                        <i class="bi bi-clock"></i> Iniciando geração...
                    </div>
                </div>

                <!-- Resumo final (escondido até finalizar) -->
                <div id="gen-summary" class="alert alert-success mt-3 d-none">
                    <i class="bi bi-check-circle-fill"></i>
                    <strong>Revista pronta!</strong>
                    <p class="mb-0 small mt-1" id="gen-summary-text"></p>
                </div>

                <!-- Erro (escondido até falhar) -->
                <div id="gen-error" class="alert alert-danger mt-3 d-none">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span id="gen-error-text"></span>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary d-none" id="gen-btn-close" data-bs-dismiss="modal">Fechar</button>
                <a href="#" class="btn btn-primary d-none" id="gen-btn-edit">
                    <i class="bi bi-pencil"></i> Editar Revista
                </a>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const modal = new bootstrap.Modal(document.getElementById('generationModal'));
    const statusIcon = document.getElementById('gen-status-icon');
    const statusText = document.getElementById('gen-status-text');
    const statusDetail = document.getElementById('gen-status-detail');
    const progressBar = document.getElementById('gen-progress-bar');
    const logContainer = document.getElementById('gen-log');
    const summary = document.getElementById('gen-summary');
    const summaryText = document.getElementById('gen-summary-text');
    const errorDiv = document.getElementById('gen-error');
    const errorText = document.getElementById('gen-error-text');
    const btnClose = document.getElementById('gen-btn-close');
    const btnEdit = document.getElementById('gen-btn-edit');

    let magazineId = null;
    let totalImages = 0;
    let generatedImages = 0;
    let failedImages = 0;

    function addLog(message, type = 'info') {
        const icons = {
            'info': 'bi-info-circle text-primary',
            'success': 'bi-check-circle text-success',
            'error': 'bi-x-circle text-danger',
            'warning': 'bi-exclamation-circle text-warning',
            'loading': 'spinner-border spinner-border-sm text-primary',
        };
        const iconClass = icons[type] || icons['info'];
        const entry = document.createElement('div');
        entry.className = 'gen-log-entry mb-1';
        
        if (type === 'loading') {
            entry.innerHTML = '<span class="' + iconClass + '" style="width:12px;height:12px;"></span> ' + message;
        } else {
            entry.innerHTML = '<i class="bi ' + iconClass + '"></i> ' + message;
        }
        
        logContainer.appendChild(entry);
        logContainer.scrollTop = logContainer.scrollHeight;
    }

    function updateProgress(current, total) {
        const pct = total > 0 ? Math.round((current / total) * 100) : 0;
        progressBar.style.width = pct + '%';
        progressBar.textContent = pct + '%';
        progressBar.setAttribute('aria-valuenow', pct);
    }

    function showComplete() {
        statusIcon.innerHTML = '<i class="bi bi-check-circle-fill text-success fs-5"></i>';
        statusText.textContent = 'Revista gerada com sucesso!';
        statusDetail.textContent = '';
        progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
        progressBar.classList.add('bg-success');
        
        let msg = 'Conteúdo gerado. ';
        if (totalImages > 0) {
            msg += generatedImages + ' de ' + totalImages + ' imagens geradas.';
            if (failedImages > 0) {
                msg += ' (' + failedImages + ' falharam — você pode gerar manualmente na edição)';
            }
        }
        summaryText.textContent = msg;
        summary.classList.remove('d-none');
        btnClose.classList.remove('d-none');
        btnEdit.classList.remove('d-none');
        btnEdit.href = '/admin/magazines/edit/' + magazineId;
    }

    function showError(message) {
        statusIcon.innerHTML = '<i class="bi bi-x-circle-fill text-danger fs-5"></i>';
        statusText.textContent = 'Erro na geração';
        statusDetail.textContent = '';
        errorText.textContent = message;
        errorDiv.classList.remove('d-none');
        btnClose.classList.remove('d-none');
        progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
        progressBar.classList.add('bg-danger');
    }

    async function generateContent(topicId) {
        addLog('Enviando tema para a IA gerar o conteúdo...', 'loading');
        
        const fd = new FormData();
        fd.append('topic_id', topicId);

        try {
            const response = await fetch('/admin/magazines/generate', { method: 'POST', body: fd });
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.error || 'Erro ao gerar conteúdo.');
            }

            magazineId = data.magazine_id;
            addLog('Conteúdo gerado: "' + data.title + '"', 'success');
            updateProgress(10, 100);

            return data.magazine_id;
        } catch (err) {
            throw err;
        }
    }

    async function getPendingImages(magId) {
        const response = await fetch('/admin/magazines/pending-images?magazine_id=' + magId);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Erro ao buscar imagens pendentes.');
        }

        return data.images || [];
    }

    async function generateOneImage(image, index) {
        const label = 'Página ' + image.page_number + ' — ' + (image.field === 'image_url_2' ? 'Imagem 2' : 'Imagem 1');
        
        statusText.textContent = 'Gerando imagem: ' + label;
        statusDetail.textContent = image.description.substring(0, 80) + '...';
        addLog('Gerando ' + label + '...', 'loading');

        const fd = new FormData();
        fd.append('page_id', image.page_id);
        fd.append('field', image.field);
        fd.append('description', image.description);

        try {
            const response = await fetch('/admin/magazines/generate-single-image', { method: 'POST', body: fd });
            const data = await response.json();

            if (data.success) {
                generatedImages++;
                addLog(label + ' — Gerada com sucesso!', 'success');
            } else {
                failedImages++;
                addLog(label + ' — Falhou: ' + (data.error || 'erro desconhecido'), 'warning');
            }
        } catch (err) {
            failedImages++;
            addLog(label + ' — Erro de conexão', 'error');
        }

        // Atualiza progresso (10% para conteúdo, 90% para imagens)
        const imgProgress = ((index + 1) / totalImages) * 90;
        updateProgress(Math.round(10 + imgProgress), 100);
    }

    async function startGeneration(topicId, topicTitle) {
        // Reset estado
        magazineId = null;
        totalImages = 0;
        generatedImages = 0;
        failedImages = 0;
        logContainer.innerHTML = '';
        progressBar.style.width = '0%';
        progressBar.textContent = '0%';
        progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated';
        summary.classList.add('d-none');
        errorDiv.classList.add('d-none');
        btnClose.classList.add('d-none');
        btnEdit.classList.add('d-none');
        statusIcon.innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span>';
        statusText.textContent = 'Gerando conteúdo da revista...';
        statusDetail.textContent = 'Tema: ' + topicTitle;

        modal.show();
        addLog('Iniciando geração da revista...', 'info');
        addLog('Tema: ' + topicTitle, 'info');

        try {
            // Passo 1: Gerar conteúdo
            const magId = await generateContent(topicId);

            // Passo 2: Buscar imagens pendentes
            statusText.textContent = 'Preparando geração de imagens...';
            statusDetail.textContent = 'Verificando quais imagens precisam ser geradas.';
            addLog('Buscando imagens para gerar...', 'loading');

            const images = await getPendingImages(magId);
            totalImages = images.length;

            if (totalImages === 0) {
                addLog('Nenhuma imagem para gerar.', 'info');
                updateProgress(100, 100);
                showComplete();
                return;
            }

            addLog(totalImages + ' imagens para gerar. Iniciando...', 'info');
            updateProgress(10, 100);

            // Passo 3: Gerar imagens uma por uma (sequencial para não sobrecarregar a API)
            for (let i = 0; i < images.length; i++) {
                await generateOneImage(images[i], i);
            }

            // Passo 4: Finalizar
            showComplete();

        } catch (err) {
            showError(err.message || 'Erro inesperado durante a geração.');
            addLog('Processo interrompido: ' + err.message, 'error');
        }
    }

    // Bind dos botões
    document.querySelectorAll('.btn-generate-magazine').forEach(btn => {
        btn.addEventListener('click', function() {
            const topicId = this.dataset.topicId;
            const topicTitle = this.dataset.topicTitle;
            
            if (!confirm('Gerar revista com o tema "' + topicTitle + '"?\n\nIsso vai gerar o conteúdo e todas as imagens automaticamente.')) {
                return;
            }

            // Desabilita todos os botões
            document.querySelectorAll('.btn-generate-magazine').forEach(b => {
                b.disabled = true;
            });

            startGeneration(topicId, topicTitle);
        });
    });
})();
</script>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
