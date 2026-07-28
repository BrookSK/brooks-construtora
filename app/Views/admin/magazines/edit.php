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
                    <?php
                    $hasGuestColumn = false;
                    foreach ($pages as $p) { if ($p['layout_type'] === 'guest_column') { $hasGuestColumn = true; break; } }
                    if (!$hasGuestColumn):
                    ?>
                    <form method="POST" action="/admin/magazines/add-guest-column" class="mt-2">
                        <input type="hidden" name="magazine_id" value="<?= $magazine['id'] ?>">
                        <button type="submit" class="btn btn-outline-secondary w-100" onclick="return confirm('Adicionar página de Coluna do Convidado?')"><i class="bi bi-person-plus"></i> Adicionar Coluna do Convidado</button>
                    </form>
                    <?php endif; ?>
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

        <!-- Gerar Todas as Imagens -->
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-stars"></i> Imagens com IA</h6></div>
            <div class="card-body">
                <p class="small text-muted mb-2">As imagens são geradas automaticamente em segundo plano ao criar a revista. Use o botão abaixo para regenerar imagens pendentes.</p>
                <button type="button" class="btn btn-warning btn-sm w-100" id="btn-generate-all-images" data-magazine-id="<?= $magazine['id'] ?>">
                    <i class="bi bi-images"></i> Regenerar Imagens Pendentes
                </button>
                <div id="gen-all-progress" class="mt-2 d-none">
                    <div class="progress mb-1" style="height: 18px;">
                        <div id="gen-all-bar" class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%">0%</div>
                    </div>
                    <small id="gen-all-status" class="text-muted">Preparando...</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Conteúdo -->
    <div class="col-md-8">
        <form method="POST" action="/admin/magazines/update" enctype="multipart/form-data">
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
                    <h6 class="mb-0 small">Página <?= $page['page_number'] ?> — <span class="text-muted"><?= $page['layout_type'] === 'guest_column' ? 'Coluna do Convidado' : $page['layout_type'] ?></span></h6>
                </div>
                <div class="card-body">
                    <?php if ($page['layout_type'] === 'guest_column'): ?>
                    <!-- Campos específicos: Coluna do Convidado -->
                    <div class="row g-2 mb-2">
                        <div class="col-md-12">
                            <label class="form-label small">Título da Coluna</label>
                            <input type="text" class="form-control form-control-sm" name="pages[<?= $page['id'] ?>][caption]" value="<?= htmlspecialchars($page['caption'] ?? 'Coluna do Convidado') ?>" placeholder="Ex: Coluna do Convidado">
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small">Nome do Convidado</label>
                            <input type="text" class="form-control form-control-sm" name="pages[<?= $page['id'] ?>][title]" value="<?= htmlspecialchars($page['title'] ?? '') ?>" placeholder="Nome completo do autor">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Cargo / Empresa</label>
                            <input type="text" class="form-control form-control-sm" name="pages[<?= $page['id'] ?>][subtitle]" value="<?= htmlspecialchars($page['subtitle'] ?? '') ?>" placeholder="Ex: CEO da Empresa X">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Foto do Convidado</label>
                        <div class="border rounded p-2" style="min-height:80px;background:#f9f9f9;">
                            <?php if ($page['image_url']): ?>
                                <img src="<?= $page['image_url'] ?>" alt="" style="width:70px;height:70px;object-fit:cover;border-radius:50%;margin-bottom:5px;">
                            <?php else: ?>
                                <div class="text-center text-muted small py-2"><i class="bi bi-person-circle"></i> Nenhuma foto</div>
                            <?php endif; ?>
                            <div class="mt-1">
                                <input type="file" class="form-control form-control-sm" name="guest_photo_<?= $page['id'] ?>" accept="image/*" style="font-size:0.65rem;">
                                <small class="text-muted">A foto será salva ao clicar em "Salvar Alterações"</small>
                            </div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Texto da Coluna</label>
                        <textarea class="form-control form-control-sm" name="pages[<?= $page['id'] ?>][content]" rows="8" placeholder="Texto escrito pelo convidado..."><?= htmlspecialchars($page['content'] ?? '') ?></textarea>
                    </div>
                    <?php else: ?>
                    <!-- Campos genéricos para outros layouts -->
                    <?php if (in_array($page['layout_type'], ['cover', 'subcover'])): ?>
                    <!-- Campos específicos: Capa / Subcapa -->
                    <?php
                        $subtitleParts = explode('—', $page['subtitle'] ?? '');
                        $subtitleLeft = trim($subtitleParts[0] ?? '');
                        $subtitleRight = trim($subtitleParts[1] ?? '');
                    ?>
                    <div class="row g-2 mb-2">
                        <div class="col-md-12">
                            <label class="form-label small">Título (texto grande da capa)</label>
                            <input type="text" class="form-control form-control-sm" name="pages[<?= $page['id'] ?>][title]" value="<?= htmlspecialchars($page['title'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label small">Tagline Esquerda</label>
                            <input type="text" class="form-control form-control-sm" name="pages[<?= $page['id'] ?>][subtitle_left]" value="<?= htmlspecialchars($subtitleLeft) ?>" placeholder="Ex: CONSTRUÇÃO">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Tagline Direita</label>
                            <input type="text" class="form-control form-control-sm" name="pages[<?= $page['id'] ?>][subtitle_right]" value="<?= htmlspecialchars($subtitleRight) ?>" placeholder="Ex: SUSTENTÁVEL">
                        </div>
                    </div>
                    <?php else: ?>
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
                    <?php endif; ?>

                    <?php if (!in_array($page['layout_type'], ['cover', 'subcover'])): ?>
                    <div class="mb-2">
                        <label class="form-label small">Conteúdo</label>
                        <textarea class="form-control form-control-sm" name="pages[<?= $page['id'] ?>][content]" rows="4"><?= htmlspecialchars($page['content'] ?? '') ?></textarea>
                    </div>
                    <?php endif; ?>

                    <!-- Imagens -->
                    <?php if (!in_array($page['layout_type'], ['cover', 'subcover', 'backcover', 'guest_column'])): ?>
                    <?php
                        // Layouts que usam 3 imagens
                        $threeImages = in_array($page['layout_type'], ['internal_05', 'internal_06']);
                        // Layouts que usam só 1 imagem
                        $oneImage = in_array($page['layout_type'], ['internal_04', 'internal_07']);
                        $colSize = $threeImages ? '4' : '6';
                    ?>
                    <div class="row g-2">
                        <!-- Imagem 1 -->
                        <div class="col-md-<?= $oneImage ? '12' : $colSize ?>">
                            <label class="form-label small">Imagem 1</label>
                            <div class="border rounded p-2" style="min-height:80px;background:#f9f9f9;">
                                <?php if ($page['image_url']): ?>
                                    <img src="<?= $page['image_url'] ?>" alt="" style="width:100%;max-height:80px;object-fit:cover;border-radius:4px;margin-bottom:5px;">
                                    <button type="button" class="btn btn-sm btn-outline-danger w-100 mb-1" style="font-size:0.6rem;" onclick="deletePageImage(<?= $page['id'] ?>, 'image_url', this)"><i class="bi bi-trash"></i> Remover</button>
                                <?php else: ?>
                                    <div class="text-center text-muted small py-2"><i class="bi bi-image"></i></div>
                                <?php endif; ?>
                                <input type="file" class="form-control form-control-sm mt-1" name="page_image_<?= $page['id'] ?>_1" accept="image/*" style="font-size:0.65rem;">
                            </div>
                        </div>
                        <!-- Imagem 2 -->
                        <?php if (!$oneImage): ?>
                        <div class="col-md-<?= $colSize ?>">
                            <label class="form-label small">Imagem 2</label>
                            <div class="border rounded p-2" style="min-height:80px;background:#f9f9f9;">
                                <?php if ($page['image_url_2'] ?? null): ?>
                                    <img src="<?= $page['image_url_2'] ?>" alt="" style="width:100%;max-height:80px;object-fit:cover;border-radius:4px;margin-bottom:5px;">
                                    <button type="button" class="btn btn-sm btn-outline-danger w-100 mb-1" style="font-size:0.6rem;" onclick="deletePageImage(<?= $page['id'] ?>, 'image_url_2', this)"><i class="bi bi-trash"></i> Remover</button>
                                <?php else: ?>
                                    <div class="text-center text-muted small py-2"><i class="bi bi-image"></i></div>
                                <?php endif; ?>
                                <input type="file" class="form-control form-control-sm mt-1" name="page_image_<?= $page['id'] ?>_2" accept="image/*" style="font-size:0.65rem;">
                            </div>
                        </div>
                        <?php endif; ?>
                        <!-- Imagem 3 (para layouts que precisam) -->
                        <?php if ($threeImages): ?>
                        <div class="col-md-4">
                            <label class="form-label small">Imagem 3</label>
                            <div class="border rounded p-2" style="min-height:80px;background:#f9f9f9;">
                                <?php if ($page['image_url_3'] ?? null): ?>
                                    <img src="<?= $page['image_url_3'] ?>" alt="" style="width:100%;max-height:80px;object-fit:cover;border-radius:4px;margin-bottom:5px;">
                                    <button type="button" class="btn btn-sm btn-outline-danger w-100 mb-1" style="font-size:0.6rem;" onclick="deletePageImage(<?= $page['id'] ?>, 'image_url_3', this)"><i class="bi bi-trash"></i> Remover</button>
                                <?php else: ?>
                                    <div class="text-center text-muted small py-2"><i class="bi bi-image"></i></div>
                                <?php endif; ?>
                                <input type="file" class="form-control form-control-sm mt-1" name="page_image_<?= $page['id'] ?>_3" accept="image/*" style="font-size:0.65rem;">
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!in_array($page['layout_type'], ['cover', 'subcover', 'backcover', 'guest_column'])): ?>
                    <div class="mt-2">
                        <label class="form-label small">Legenda (caption)</label>
                        <input type="text" class="form-control form-control-sm" name="pages[<?= $page['id'] ?>][caption]" value="<?= htmlspecialchars($page['caption'] ?? '') ?>">
                    </div>
                    <?php endif; ?>
                    <?php endif; ?><!-- end guest_column else -->
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Botão adicionar página -->
            <div class="text-center mb-3">
                <button type="button" class="btn btn-outline-primary" onclick="addNewPage()">
                    <i class="bi bi-plus-lg"></i> Adicionar Página
                </button>
                <small class="text-muted d-block mt-1">Nova página será inserida antes da contracapa</small>
            </div>

            <!-- Fontes / Referências -->
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 small"><i class="bi bi-journal-bookmark"></i> Fontes e Referências</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="addSource()"><i class="bi bi-plus"></i> Adicionar</button>
                </div>
                <div class="card-body" id="sourcesContainer">
                    <?php
                    $sources = \App\Core\Database::fetchAll("SELECT * FROM magazine_sources WHERE magazine_id = ? ORDER BY sort_order", [$magazine['id']]);
                    if (empty($sources)):
                    ?>
                    <p class="text-muted small text-center mb-0" id="noSourcesMsg">Nenhuma fonte adicionada. Clique em "Adicionar" para incluir referências.</p>
                    <?php else: ?>
                    <?php foreach ($sources as $i => $src): ?>
                    <div class="source-row row g-2 mb-2 align-items-center" data-index="<?= $i ?>">
                        <div class="col-md-4"><input type="text" class="form-control form-control-sm" name="sources[<?= $i ?>][title]" value="<?= htmlspecialchars($src['title']) ?>" placeholder="Título *"></div>
                        <div class="col-md-4"><input type="url" class="form-control form-control-sm" name="sources[<?= $i ?>][url]" value="<?= htmlspecialchars($src['url'] ?? '') ?>" placeholder="URL"></div>
                        <div class="col-md-2"><input type="text" class="form-control form-control-sm" name="sources[<?= $i ?>][author]" value="<?= htmlspecialchars($src['author'] ?? '') ?>" placeholder="Autor"></div>
                        <div class="col-md-1"><input type="date" class="form-control form-control-sm" name="sources[<?= $i ?>][accessed_at]" value="<?= $src['accessed_at'] ?? '' ?>" title="Data de acesso"></div>
                        <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.source-row').remove()"><i class="bi bi-trash"></i></button></div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

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

// Deletar imagem de página
async function deletePageImage(pageId, field, btn) {
    if (!confirm('Remover esta imagem?')) return;
    const resp = await fetch('/admin/magazines/delete-page-image', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ page_id: pageId, field: field })
    });
    const data = await resp.json();
    if (data.success) {
        const container = btn.parentElement;
        const img = container.querySelector('img');
        if (img) img.remove();
        btn.remove();
    } else {
        alert(data.error || 'Erro ao remover.');
    }
}

// Adicionar nova página
function addNewPage() {
    const modal = document.createElement('div');
    modal.innerHTML = `
        <div style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.6);z-index:9999;display:flex;align-items:center;justify-content:center;padding:1rem;">
            <div style="background:#fff;border-radius:16px;max-width:500px;width:100%;padding:2rem;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
                <h5 style="margin-bottom:0.5rem;"><i class="bi bi-file-earmark-plus"></i> Adicionar Nova Página</h5>
                <p style="color:#666;font-size:0.85rem;margin-bottom:1.5rem;">Escolha o estilo de página que deseja adicionar. Ela será inserida antes da contracapa.</p>
                <div style="display:grid;gap:8px;">
                    <button class="btn btn-light border text-start" style="padding:12px 16px;" onclick="confirmAddPage('internal_01')"><strong>Manchete</strong> <small class="text-muted">— Título grande + 2 fotos</small></button>
                    <button class="btn btn-light border text-start" style="padding:12px 16px;" onclick="confirmAddPage('internal_02')"><strong>Subtema</strong> <small class="text-muted">— Subtítulo + texto + 2 fotos</small></button>
                    <button class="btn btn-light border text-start" style="padding:12px 16px;" onclick="confirmAddPage('internal_03')"><strong>Artigo com legenda</strong> <small class="text-muted">— Texto longo + 2 fotos + legenda</small></button>
                    <button class="btn btn-light border text-start" style="padding:12px 16px;" onclick="confirmAddPage('internal_04')"><strong>Impacto</strong> <small class="text-muted">— Frase de impacto + 1 foto grande</small></button>
                    <button class="btn btn-light border text-start" style="padding:12px 16px;" onclick="confirmAddPage('internal_05')"><strong>Duas colunas</strong> <small class="text-muted">— Texto em 2 colunas + 2 fotos</small></button>
                    <button class="btn btn-light border text-start" style="padding:12px 16px;" onclick="confirmAddPage('internal_06')"><strong>Galeria</strong> <small class="text-muted">— Grid com 3 fotos + texto</small></button>
                    <button class="btn btn-light border text-start" style="padding:12px 16px;" onclick="confirmAddPage('internal_07')"><strong>Citação</strong> <small class="text-muted">— Frase grande de destaque + 1 foto</small></button>
                </div>
                <div style="text-align:center;margin-top:1rem;">
                    <button class="btn btn-outline-secondary btn-sm" onclick="this.closest('[style*=fixed]').remove()">Cancelar</button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal.firstElementChild);
}

async function confirmAddPage(layout) {
    // Mostrar loading nos botões
    const modal = document.querySelector('[style*="position:fixed"][style*="z-index:9999"]');
    if (modal) {
        modal.querySelector('[style*="display:grid"]').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted small">Salvando alterações e adicionando página...</p></div>';
    }
    
    // Salvar alterações do formulário antes de adicionar a página
    const form = document.querySelector('form[action="/admin/magazines/update"]');
    if (form) {
        const formData = new FormData(form);
        await fetch('/admin/magazines/update', { method: 'POST', body: formData });
    }

    const resp = await fetch('/admin/magazines/add-page', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ magazine_id: <?= $magazine['id'] ?>, layout_type: layout })
    });
    const data = await resp.json();
    if (data.success) {
        location.reload();
    } else {
        alert(data.error || 'Erro ao adicionar.');
        modal?.remove();
    }
}

// Gerar imagem com IA
document.querySelectorAll('.generate-img-btn').forEach(btn=>{
    btn.addEventListener('click', function(){
        var pageId = this.dataset.pageId;
        var field = this.dataset.field;
        
        // Pega o título e conteúdo da página para usar como contexto
        var card = this.closest('.card');
        var titleInput = card.querySelector('input[name*="[title]"]');
        var contentInput = card.querySelector('textarea[name*="[content]"]');
        var pageTitle = titleInput ? titleInput.value : '';
        var pageContent = contentInput ? contentInput.value.substring(0, 200) : '';
        
        var autoDesc = 'Foto profissional de construção/arquitetura: ' + pageTitle;
        if (pageContent) autoDesc += '. Contexto: ' + pageContent.split('\n')[0];
        
        var desc = prompt('Descrição da imagem para gerar com IA:', autoDesc);
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

// Fontes
let sourceIndex = <?= count($sources ?? []) ?>;
function addSource() {
    document.getElementById('noSourcesMsg')?.remove();
    const container = document.getElementById('sourcesContainer');
    const html = `<div class="source-row row g-2 mb-2 align-items-center" data-index="${sourceIndex}">
        <div class="col-md-4"><input type="text" class="form-control form-control-sm" name="sources[${sourceIndex}][title]" placeholder="Título *"></div>
        <div class="col-md-4"><input type="url" class="form-control form-control-sm" name="sources[${sourceIndex}][url]" placeholder="URL"></div>
        <div class="col-md-2"><input type="text" class="form-control form-control-sm" name="sources[${sourceIndex}][author]" placeholder="Autor"></div>
        <div class="col-md-1"><input type="date" class="form-control form-control-sm" name="sources[${sourceIndex}][accessed_at]" title="Data de acesso"></div>
        <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.source-row').remove()"><i class="bi bi-trash"></i></button></div>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
    sourceIndex++;
}

// Gerar TODAS as imagens pendentes em background
document.getElementById('btn-generate-all-images').addEventListener('click', async function() {
    const magazineId = this.dataset.magazineId;
    
    if (!confirm('Gerar todas as imagens pendentes com IA?\n\nIsso pode demorar alguns minutos. Você pode continuar usando o sistema normalmente.')) {
        return;
    }

    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Gerando...';
    
    const progressDiv = document.getElementById('gen-all-progress');
    const progressBar = document.getElementById('gen-all-bar');
    const statusEl = document.getElementById('gen-all-status');
    progressDiv.classList.remove('d-none');

    try {
        // Busca imagens pendentes
        const resp = await fetch('/admin/magazines/pending-images?magazine_id=' + magazineId);
        const data = await resp.json();

        if (!data.success || !data.images || data.images.length === 0) {
            statusEl.textContent = 'Nenhuma imagem pendente para gerar.';
            this.disabled = false;
            this.innerHTML = '<i class="bi bi-images"></i> Gerar Todas as Imagens';
            return;
        }

        const total = data.images.length;
        let generated = 0;
        let failed = 0;
        statusEl.textContent = '0 de ' + total + ' imagens...';

        for (let i = 0; i < data.images.length; i++) {
            const img = data.images[i];
            const label = 'Pág. ' + img.page_number + ' - ' + (img.field === 'image_url_2' ? 'Img 2' : 'Img 1');
            statusEl.textContent = label + ' (' + (i+1) + '/' + total + ')';

            const fd = new FormData();
            fd.append('page_id', img.page_id);
            fd.append('field', img.field);
            fd.append('description', img.description);

            try {
                const r = await fetch('/admin/magazines/generate-single-image', {method:'POST', body:fd});
                const d = await r.json();
                if (d.success) generated++;
                else failed++;
            } catch(e) {
                failed++;
            }

            const pct = Math.round(((i+1) / total) * 100);
            progressBar.style.width = pct + '%';
            progressBar.textContent = pct + '%';
        }

        progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
        progressBar.classList.add('bg-success');
        statusEl.innerHTML = '<strong>Concluído!</strong> ' + generated + ' geradas, ' + failed + ' falhas.';
        
        this.innerHTML = '<i class="bi bi-check-circle"></i> Concluído!';
        
        setTimeout(() => location.reload(), 2000);

    } catch(e) {
        statusEl.textContent = 'Erro: ' + e.message;
        this.disabled = false;
        this.innerHTML = '<i class="bi bi-images"></i> Gerar Todas as Imagens';
    }
});
</script>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
