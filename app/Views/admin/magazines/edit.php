<?php $pageTitle = 'Editar Revista: ' . htmlspecialchars($magazine['title']); $currentPage = 'magazines'; ob_start(); ?>

<div class="row g-4">
    <!-- Sidebar -->
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Status & Ações</h6></div>
            <div class="card-body">
                <?php
                    $statusLabels = ['draft'=>'Rascunho','generated'=>'Gerada pela IA','review'=>'Em Revisão','approved'=>'Aprovada','published'=>'Publicada'];
                ?>
                <p class="mb-1"><strong>Status:</strong> <?= $statusLabels[$magazine['status']] ?? $magazine['status'] ?></p>
                <p class="mb-3"><strong>Criada:</strong> <?= date('d/m/Y H:i', strtotime($magazine['created_at'])) ?></p>

                <?php if ($magazine['status'] !== 'published'): ?>
                <div class="d-grid gap-2">
                    <?php if (in_array($magazine['status'], ['generated', 'review'])): ?>
                    <form method="POST" action="/admin/magazines/approve">
                        <input type="hidden" name="magazine_id" value="<?= $magazine['id'] ?>">
                        <button type="submit" class="btn btn-success w-100" onclick="return confirm('Aprovar?')"><i class="bi bi-check-circle"></i> Aprovar</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($magazine['status'] === 'approved' && \App\Core\Auth::hasPermission('magazines.publish')): ?>
                    <form method="POST" action="/admin/magazines/publish">
                        <input type="hidden" name="magazine_id" value="<?= $magazine['id'] ?>">
                        <button type="submit" class="btn btn-primary w-100" onclick="return confirm('Publicar e enviar newsletter?')"><i class="bi bi-send"></i> Publicar</button>
                    </form>
                    <?php endif; ?>
                    <a href="/admin/magazines/preview/<?= $magazine['id'] ?>" class="btn btn-outline-info" target="_blank"><i class="bi bi-eye"></i> Preview</a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Capa -->
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Capa da Revista</h6></div>
            <div class="card-body">
                <div id="cover-preview" class="mb-2 text-center" style="background:#1a472a;border-radius:6px;padding:10px;min-height:120px;display:flex;align-items:center;justify-content:center;">
                    <?php if ($magazine['cover_image']): ?>
                        <img src="<?= $magazine['cover_image'] ?>" alt="Capa" style="max-height:180px;max-width:100%;border-radius:4px;">
                    <?php else: ?>
                        <span style="color:rgba(255,255,255,0.5);font-size:0.8rem;">Nenhuma capa</span>
                    <?php endif; ?>
                </div>
                <form id="cover-form" enctype="multipart/form-data">
                    <input type="hidden" name="magazine_id" value="<?= $magazine['id'] ?>">
                    <input type="file" class="form-control form-control-sm" name="cover" accept="image/*">
                    <button type="submit" class="btn btn-primary btn-sm mt-2 w-100"><i class="bi bi-upload"></i> Enviar Capa</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Conteúdo -->
    <div class="col-md-8">
        <form method="POST" action="/admin/magazines/update">
            <input type="hidden" name="magazine_id" value="<?= $magazine['id'] ?>">

            <div class="card mb-3">
                <div class="card-header"><h6 class="mb-0">Informações Gerais</h6></div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">Título</label>
                            <input type="text" class="form-control form-control-sm" name="title" value="<?= htmlspecialchars($magazine['title']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Subtítulo / Tema</label>
                            <input type="text" class="form-control form-control-sm" name="subtitle" value="<?= htmlspecialchars($magazine['subtitle'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Páginas -->
            <?php foreach ($pages as $page): ?>
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0 small">Página <?= $page['page_number'] ?> — <span class="text-muted"><?= $page['layout_type'] ?></span></h6>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small">Título</label>
                            <input type="text" class="form-control form-control-sm" name="pages[<?= $page['id'] ?>][title]" value="<?= htmlspecialchars($page['title'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Subtítulo</label>
                            <input type="text" class="form-control form-control-sm" name="pages[<?= $page['id'] ?>][subtitle]" value="<?= htmlspecialchars($page['subtitle'] ?? '') ?>">
                        </div>
                    </div>

                    <?php if (!in_array($page['layout_type'], ['cover', 'subcover'])): ?>
                    <div class="mb-2">
                        <label class="form-label small">Conteúdo</label>
                        <textarea class="form-control form-control-sm" name="pages[<?= $page['id'] ?>][content]" rows="4"><?= htmlspecialchars($page['content'] ?? '') ?></textarea>
                    </div>
                    <?php endif; ?>

                    <!-- Imagens -->
                    <?php if (!in_array($page['layout_type'], ['cover', 'subcover', 'backcover'])): ?>
                    <div class="row g-2">
                        <!-- Imagem 1 -->
                        <div class="col-md-6">
                            <label class="form-label small">Imagem 1</label>
                            <div class="border rounded p-2" style="min-height:80px;background:#f9f9f9;">
                                <?php if ($page['image_url']): ?>
                                    <img src="<?= $page['image_url'] ?>" alt="" style="width:100%;max-height:100px;object-fit:cover;border-radius:4px;margin-bottom:5px;">
                                <?php else: ?>
                                    <div class="text-center text-muted small py-3"><i class="bi bi-image"></i> Sem imagem</div>
                                <?php endif; ?>
                                <div class="d-flex gap-1 mt-1">
                                    <form class="upload-img-form flex-grow-1" enctype="multipart/form-data" data-page-id="<?= $page['id'] ?>" data-field="image_url">
                                        <input type="hidden" name="page_id" value="<?= $page['id'] ?>">
                                        <div class="input-group input-group-sm">
                                            <input type="file" class="form-control form-control-sm" name="image" accept="image/*" style="font-size:0.7rem;">
                                            <button type="submit" class="btn btn-outline-primary btn-sm" title="Upload"><i class="bi bi-upload"></i></button>
                                        </div>
                                    </form>
                                    <button type="button" class="btn btn-outline-warning btn-sm generate-img-btn" data-page-id="<?= $page['id'] ?>" data-field="image_url" title="Gerar com IA">
                                        <i class="bi bi-stars"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!-- Imagem 2 -->
                        <div class="col-md-6">
                            <label class="form-label small">Imagem 2</label>
                            <div class="border rounded p-2" style="min-height:80px;background:#f9f9f9;">
                                <?php if ($page['image_url_2'] ?? null): ?>
                                    <img src="<?= $page['image_url_2'] ?>" alt="" style="width:100%;max-height:100px;object-fit:cover;border-radius:4px;margin-bottom:5px;">
                                <?php else: ?>
                                    <div class="text-center text-muted small py-3"><i class="bi bi-image"></i> Sem imagem</div>
                                <?php endif; ?>
                                <div class="d-flex gap-1 mt-1">
                                    <form class="upload-img-form flex-grow-1" enctype="multipart/form-data" data-page-id="<?= $page['id'] ?>" data-field="image_url_2">
                                        <input type="hidden" name="page_id" value="<?= $page['id'] ?>">
                                        <input type="hidden" name="field" value="image_url_2">
                                        <div class="input-group input-group-sm">
                                            <input type="file" class="form-control form-control-sm" name="image" accept="image/*" style="font-size:0.7rem;">
                                            <button type="submit" class="btn btn-outline-primary btn-sm" title="Upload"><i class="bi bi-upload"></i></button>
                                        </div>
                                    </form>
                                    <button type="button" class="btn btn-outline-warning btn-sm generate-img-btn" data-page-id="<?= $page['id'] ?>" data-field="image_url_2" title="Gerar com IA">
                                        <i class="bi bi-stars"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!in_array($page['layout_type'], ['cover', 'subcover', 'backcover'])): ?>
                    <div class="mt-2">
                        <label class="form-label small">Legenda (caption)</label>
                        <input type="text" class="form-control form-control-sm" name="pages[<?= $page['id'] ?>][caption]" value="<?= htmlspecialchars($page['caption'] ?? '') ?>">
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="text-end mb-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<script>
// Upload da capa
document.getElementById('cover-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var fd = new FormData(this);
    fetch('/admin/magazines/upload-cover', {method:'POST', body:fd})
    .then(r=>r.json()).then(d=>{
        if(d.success){alert('Capa atualizada!');location.reload();}
        else alert(d.error||'Erro.');
    }).catch(()=>alert('Erro.'));
});

// Upload de imagens por página
document.querySelectorAll('.upload-img-form').forEach(f=>{
    f.addEventListener('submit', function(e){
        e.preventDefault();
        var fd = new FormData(this);
        fetch('/admin/magazines/upload-image', {method:'POST', body:fd})
        .then(r=>r.json()).then(d=>{
            if(d.success){alert('Imagem enviada!');location.reload();}
            else alert(d.error||'Erro.');
        }).catch(()=>alert('Erro ao enviar.'));
    });
});

// Gerar imagem com IA
document.querySelectorAll('.generate-img-btn').forEach(btn=>{
    btn.addEventListener('click', function(){
        var pageId = this.dataset.pageId;
        var field = this.dataset.field;
        var desc = prompt('Descreva a imagem que deseja gerar:', 'Foto profissional de construção de alto padrão');
        if(!desc) return;
        
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        
        var fd = new FormData();
        fd.append('page_id', pageId);
        fd.append('field', field);
        fd.append('description', desc);
        
        fetch('/admin/magazines/generate-image', {method:'POST', body:fd})
        .then(r=>r.json()).then(d=>{
            if(d.success){alert('Imagem gerada!');location.reload();}
            else alert(d.error||'Erro ao gerar.');
            this.disabled=false; this.innerHTML='<i class="bi bi-stars"></i>';
        }).catch(()=>{alert('Erro.');this.disabled=false;this.innerHTML='<i class="bi bi-stars"></i>';});
    });
});
</script>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
