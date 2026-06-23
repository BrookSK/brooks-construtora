<?php $pageTitle = 'Criar Revista Manual'; $currentPage = 'magazines'; ob_start(); ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-pencil-square"></i> Criar Revista Manualmente</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Crie uma revista do zero, preenchendo todo o conteúdo você mesmo. As 10 páginas padrão serão criadas em branco para você editar.</p>
                
                <form method="POST" action="/admin/magazines/store-manual">
                    <div class="mb-3">
                        <label class="form-label">Título da Revista *</label>
                        <input type="text" class="form-control" name="title" required placeholder="Ex: Tendências de Construção Sustentável 2026">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subtítulo / Tema</label>
                        <input type="text" class="form-control" name="subtitle" placeholder="Breve descrição do tema abordado">
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-plus-circle"></i> Criar Revista
                        </button>
                        <a href="/admin/magazines/topics" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Voltar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
