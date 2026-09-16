<?php $pageTitle = 'Importar Materiais'; $currentPage = 'materials'; ?>
<?php ob_start(); ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-upload"></i> Importar Materiais em Massa
            </div>
            <div class="card-body">
                <div class="alert alert-info small">
                    <strong>Formato esperado do arquivo (CSV separado por ; ou tab):</strong><br>
                    Colunas: <code>Classificação</code> | <code>Código do Insumo</code> | <code>Descrição do Insumo</code> | <code>Unidade</code><br><br>
                    <strong>Dicas:</strong>
                    <ul class="mb-0 mt-1">
                        <li>Se o arquivo for XLSX, salve como CSV (separado por ;) no Excel antes de importar</li>
                        <li>A primeira linha deve ser o cabeçalho</li>
                        <li>Materiais com código já existente serão ignorados (sem duplicar)</li>
                        <li>Classificações e unidades novas serão criadas automaticamente</li>
                        <li>Suporta arquivos com milhares de linhas</li>
                    </ul>
                </div>

                <div class="mb-3">
                    <label class="form-label">Selecione o arquivo</label>
                    <input type="file" class="form-control" id="importFile" accept=".csv,.txt,.xlsx">
                </div>

                <button type="button" class="btn btn-primary w-100" id="importBtn" onclick="startImport()">
                    <i class="bi bi-upload"></i> Importar Materiais
                </button>

                <!-- Progresso -->
                <div id="importProgress" class="mt-3" style="display:none;">
                    <div class="progress mb-2" style="height:8px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" id="progressBar" style="width:0%"></div>
                    </div>
                    <p class="small text-muted text-center mb-0" id="progressLabel">Processando...</p>
                </div>

                <!-- Resultado -->
                <div id="importResult" class="mt-3" style="display:none;"></div>
            </div>
            <div class="card-footer">
                <a href="/admin/materials" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Voltar para Materiais
                </a>
            </div>
        </div>

        <!-- Reimportação / Correção via IA (UPDATE por id, sem perder vínculos) -->
        <div class="card mt-3 border-primary">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-arrow-repeat"></i> Corrigir materiais existentes (via IA)
            </div>
            <div class="card-body">
                <div class="alert alert-info small">
                    <strong>Como funciona:</strong>
                    <ol class="mb-2 mt-1 ps-3">
                        <li>Clique em <a href="/admin/materials/export"><strong>Exportar</strong></a> na tela de materiais para baixar o CSV com todos os materiais.</li>
                        <li>Envie esse arquivo para a IA corrigir <em>nome, especificação, classificação, unidade e categoria</em>.</li>
                        <li>Volte aqui e reimporte o arquivo corrigido. O sistema atualiza cada material <strong>pelo <code>id</code></strong>.</li>
                    </ol>
                    <strong class="text-danger">Importante:</strong> não altere nem remova a coluna <code>id</code>. Ela garante que
                    pedidos, transportes, estoque, histórico de preço e listas <strong>continuem vinculados</strong> — só a informação do material é atualizada.
                </div>

                <div class="mb-3">
                    <label class="form-label">Arquivo corrigido (CSV separado por ;)</label>
                    <input type="file" class="form-control" id="reimportFile" accept=".csv,.txt">
                </div>

                <button type="button" class="btn btn-primary w-100" id="reimportBtn" onclick="startReimport()">
                    <i class="bi bi-arrow-repeat"></i> Reimportar e atualizar materiais
                </button>

                <div id="reimportProgress" class="mt-3" style="display:none;">
                    <div class="progress mb-2" style="height:8px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" id="reimportBar" style="width:0%"></div>
                    </div>
                    <p class="small text-muted text-center mb-0" id="reimportLabel">Processando...</p>
                </div>

                <div id="reimportResult" class="mt-3" style="display:none;"></div>
            </div>
        </div>

        <!-- Exemplo -->
        <div class="card mt-3">
            <div class="card-header">Exemplo de formato</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0" style="font-size:0.8rem;">
                        <thead class="table-dark">
                            <tr><th>Classificação</th><th>Código do Insumo</th><th>Descrição do Insumo</th><th>Unidade</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>SERVIÇOS</td><td>45333</td><td>ABERTURA PARA ENCAIXE DE CUBA OU LAVATORIO...</td><td>UN</td></tr>
                            <tr><td>MATERIAL</td><td>11270</td><td>ABRACADEIRA DE LATAO PARA FIXACAO...</td><td>UN</td></tr>
                            <tr><td>MATERIAL</td><td>412</td><td>ABRACADEIRA DE NYLON PARA AMARRACAO...</td><td>UN</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function startImport() {
    const fileInput = document.getElementById('importFile');
    if (!fileInput.files.length) { alert('Selecione um arquivo.'); return; }

    const btn = document.getElementById('importBtn');
    const progress = document.getElementById('importProgress');
    const result = document.getElementById('importResult');
    const bar = document.getElementById('progressBar');
    const label = document.getElementById('progressLabel');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processando...';
    progress.style.display = 'block';
    result.style.display = 'none';
    bar.style.width = '30%';
    label.textContent = 'Enviando arquivo...';

    const formData = new FormData();
    formData.append('file', fileInput.files[0]);

    try {
        bar.style.width = '60%';
        label.textContent = 'Processando materiais...';

        const resp = await fetch('/admin/materials/import-process', { method: 'POST', body: formData });
        const data = await resp.json();

        bar.style.width = '100%';
        bar.classList.remove('progress-bar-animated');

        if (data.success) {
            label.textContent = 'Concluído!';
            bar.classList.add('bg-success');
            result.style.display = 'block';
            result.innerHTML = `
                <div class="alert alert-success">
                    <strong>Importação concluída!</strong><br>
                    <span class="d-block mt-1">Importados: <strong>${data.imported}</strong></span>
                    <span class="d-block">Ignorados (duplicados): <strong>${data.skipped}</strong></span>
                    <span class="d-block">Total de linhas: <strong>${data.total}</strong></span>
                </div>
                <a href="/admin/materials" class="btn btn-primary btn-sm">Ver Materiais</a>
            `;
        } else {
            bar.classList.add('bg-danger');
            label.textContent = 'Erro!';
            result.style.display = 'block';
            result.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
        }
    } catch (e) {
        bar.classList.add('bg-danger');
        label.textContent = 'Erro de conexão';
        result.style.display = 'block';
        result.innerHTML = `<div class="alert alert-danger">Erro ao processar: ${e.message}</div>`;
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-upload"></i> Importar Materiais';
}

async function startReimport() {
    const fileInput = document.getElementById('reimportFile');
    if (!fileInput.files.length) { alert('Selecione o arquivo corrigido.'); return; }

    const btn = document.getElementById('reimportBtn');
    const progress = document.getElementById('reimportProgress');
    const result = document.getElementById('reimportResult');
    const bar = document.getElementById('reimportBar');
    const label = document.getElementById('reimportLabel');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Atualizando...';
    progress.style.display = 'block';
    result.style.display = 'none';
    bar.className = 'progress-bar progress-bar-striped progress-bar-animated bg-primary';
    bar.style.width = '30%';
    label.textContent = 'Enviando arquivo...';

    const formData = new FormData();
    formData.append('file', fileInput.files[0]);

    try {
        bar.style.width = '60%';
        label.textContent = 'Atualizando materiais...';

        const resp = await fetch('/admin/materials/reimport-process', { method: 'POST', body: formData });
        const data = await resp.json();

        bar.style.width = '100%';
        bar.classList.remove('progress-bar-animated');

        if (data.success) {
            label.textContent = 'Concluído!';
            bar.classList.add('bg-success');
            result.style.display = 'block';
            result.innerHTML = `
                <div class="alert alert-success">
                    <strong>Materiais atualizados!</strong><br>
                    <span class="d-block mt-1">Atualizados: <strong>${data.updated}</strong></span>
                    <span class="d-block">Ignorados (sem id/nome): <strong>${data.skipped}</strong></span>
                    <span class="d-block">id não encontrado: <strong>${data.notFound}</strong></span>
                    <span class="d-block">Total de linhas: <strong>${data.total}</strong></span>
                </div>
                <a href="/admin/materials" class="btn btn-primary btn-sm">Ver Materiais</a>
            `;
        } else {
            bar.classList.add('bg-danger');
            label.textContent = 'Erro!';
            result.style.display = 'block';
            result.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
        }
    } catch (e) {
        bar.classList.add('bg-danger');
        label.textContent = 'Erro de conexão';
        result.style.display = 'block';
        result.innerHTML = `<div class="alert alert-danger">Erro ao processar: ${e.message}</div>`;
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Reimportar e atualizar materiais';
}
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
