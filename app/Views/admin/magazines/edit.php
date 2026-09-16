<?php $pageTitle = 'Editar Revista: ' . htmlspecialchars($magazine['title']); $currentPage = 'magazines'; ob_start(); ?>

<div class="row g-4">
    <!-- Sidebar -->
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Status & Ações</h6></div>
            <div class="card-body">
                <?php
                    $statusLabels = ['draft'=>'Rascunho','generated'=>'Gerada pela IA','review'=>'Em Revisão','approved'=>'Aprovada','published'=>'Publicada','test'=>'Publicada (Teste)'];
                ?>
                <p class="mb-1"><strong>Status:</strong> <?= $statusLabels[$magazine['status']] ?? $magazine['status'] ?>
                    <?php if ($magazine['status'] === 'test'): ?>
                    <span class="badge bg-warning text-dark ms-1"><i class="bi bi-flask"></i> Teste</span>
                    <?php endif; ?>
                </p>
                <p class="mb-3"><strong>Criada:</strong> <?= date('d/m/Y H:i', strtotime($magazine['created_at'])) ?></p>

                <?php if ($magazine['status'] === 'test'): ?>
                <div class="alert alert-warning small py-2 px-2 mb-2">
                    <i class="bi bi-flask"></i> Esta revista já foi <strong>testada</strong> (notificação enviada só aos contatos de teste). Ela ainda NÃO foi publicada oficialmente — publique quando estiver pronta.
                </div>
                <?php endif; ?>

                <?php
                    $canPublish = \App\Core\Auth::hasPermission('magazines.publish');
                    $canEdit = \App\Core\Auth::hasPermission('magazines.edit');
                    $hasPdf = \App\Services\BrowserlessPdfService::existingPdfUrl((int) $magazine['id']) !== null;
                    $hasGuestColumn = false; $hasStories = false;
                    foreach ($pages as $p) {
                        if (($p['layout_type'] ?? '') === 'guest_column') $hasGuestColumn = true;
                        if (($p['layout_type'] ?? '') === 'construction_stories') $hasStories = true;
                    }
                ?>

                <?php if ($magazine['status'] !== 'published'): ?>

                <!-- Passo 1: preparar (aprovar quando vier da IA) -->
                <?php if (in_array($magazine['status'], ['generated', 'review'])): ?>
                <form method="POST" action="/admin/magazines/approve" class="d-grid mb-2">
                    <input type="hidden" name="magazine_id" value="<?= $magazine['id'] ?>">
                    <button type="submit" class="btn btn-success" onclick="return confirm('Aprovar?')"><i class="bi bi-check-circle"></i> Aprovar</button>
                </form>
                <?php endif; ?>

                <!-- Passo 2: conferir (preview + gerar o PDF que os leitores veem) -->
                <div class="d-grid gap-2 mb-2">
                    <a href="/admin/magazines/preview/<?= $magazine['id'] ?>" class="btn btn-outline-info btn-sm" target="_blank"><i class="bi bi-eye"></i> Ver Preview</a>
                    <?php if ($canEdit): ?>
                    <form method="POST" action="/admin/magazines/generate-pdf" id="genPdfForm">
                        <input type="hidden" name="magazine_id" value="<?= $magazine['id'] ?>">
                        <button type="submit" class="btn <?= $hasPdf ? 'btn-outline-dark' : 'btn-dark' ?> btn-sm w-100" id="genPdfBtn">
                            <i class="bi bi-file-earmark-pdf"></i> <?= $hasPdf ? 'Regerar PDF' : 'Gerar PDF da Revista' ?>
                        </button>
                    </form>
                    <small class="text-muted" style="font-size:0.72rem;">Gera o PDF no servidor (fica igual em qualquer aparelho). <?= $hasPdf ? 'Já existe um PDF gerado.' : 'Necessário antes de publicar/testar.' ?></small>
                    <?php endif; ?>
                </div>

                <!-- Passo 3: distribuir (teste e publicação oficial) -->
                <?php if (in_array($magazine['status'], ['approved', 'test']) && $canPublish): ?>
                <div class="d-grid gap-2 mb-1">
                    <form method="POST" action="/admin/magazines/publish-test">
                        <input type="hidden" name="magazine_id" value="<?= $magazine['id'] ?>">
                        <button type="submit" class="btn btn-warning w-100" onclick="return confirm('Enviar TESTE?\n\nA notificação (e-mail + WhatsApp) será enviada somente aos contatos de teste configurados. A revista fica como \'Publicada (Teste)\' e NÃO vai para o público.')"><i class="bi bi-flask"></i> Enviar Teste</button>
                    </form>
                    <form method="POST" action="/admin/magazines/publish">
                        <input type="hidden" name="magazine_id" value="<?= $magazine['id'] ?>">
                        <button type="submit" class="btn btn-primary w-100" onclick="return confirm('Publicar de verdade e enviar newsletter para TODOS os assinantes?')"><i class="bi bi-send"></i> Publicar (Oficial)</button>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Estrutura de páginas (opcional) — recolhido para não poluir -->
                <?php if (!$hasGuestColumn || !$hasStories): ?>
                <details class="mt-2">
                    <summary class="text-muted small" style="cursor:pointer;">Adicionar páginas especiais</summary>
                    <div class="d-grid gap-2 mt-2">
                        <?php if (!$hasGuestColumn): ?>
                        <form method="POST" action="/admin/magazines/add-guest-column">
                            <input type="hidden" name="magazine_id" value="<?= $magazine['id'] ?>">
                            <button type="submit" class="btn btn-outline-secondary btn-sm w-100" onclick="return confirm('Adicionar página de Coluna do Convidado?')"><i class="bi bi-person-plus"></i> Coluna do Convidado</button>
                        </form>
                        <?php endif; ?>
                        <?php if (!$hasStories): ?>
                        <form method="POST" action="/admin/magazines/add-construction-stories">
                            <input type="hidden" name="magazine_id" value="<?= $magazine['id'] ?>">
                            <button type="submit" class="btn btn-outline-secondary btn-sm w-100" onclick="return confirm('Adicionar página de Causos de Obra?')"><i class="bi bi-chat-quote"></i> Causos de Obra</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </details>
                <?php endif; ?>

                <script>
                (function () {
                    var form = document.getElementById('genPdfForm');
                    if (!form) return;
                    form.addEventListener('submit', function (e) {
                        e.preventDefault();
                        var btn = document.getElementById('genPdfBtn');
                        var original = btn.innerHTML;
                        btn.disabled = true;
                        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Gerando PDF...';
                        fetch(form.action, {
                            method: 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                            body: new FormData(form)
                        })
                        .then(function (r) { return r.json(); })
                        .then(function (res) {
                            if (res && res.success) {
                                btn.innerHTML = '<i class="bi bi-check-lg"></i> PDF gerado!';
                                btn.classList.add('btn-success');
                                setTimeout(function () { window.location.reload(); }, 1200);
                            } else {
                                alert((res && res.error) || 'Não foi possível gerar o PDF.');
                                btn.disabled = false; btn.innerHTML = original;
                            }
                        })
                        .catch(function () {
                            alert('Erro de conexão ao gerar o PDF.');
                            btn.disabled = false; btn.innerHTML = original;
                        });
                    });
                })();
                </script>

                <?php else: ?>

                <!-- Revista PUBLICADA: reverter para poder editar/regerar -->
                <?php if ($canPublish): ?>
                <form method="POST" action="/admin/magazines/unpublish" class="d-grid mb-2">
                    <input type="hidden" name="magazine_id" value="<?= $magazine['id'] ?>">
                    <button type="submit" class="btn btn-outline-secondary btn-sm" onclick="return confirm('Reverter para Aprovada? A revista sai do ar para o público até publicar de novo.')"><i class="bi bi-arrow-counterclockwise"></i> Reverter Publicação</button>
                </form>
                <?php endif; ?>
                <div class="d-grid gap-2">
                    <a href="/admin/magazines/preview/<?= $magazine['id'] ?>" class="btn btn-outline-info btn-sm" target="_blank"><i class="bi bi-eye"></i> Ver Preview</a>
                    <?php if ($canEdit): ?>
                    <form method="POST" action="/admin/magazines/generate-pdf" id="genPdfForm">
                        <input type="hidden" name="magazine_id" value="<?= $magazine['id'] ?>">
                        <button type="submit" class="btn btn-outline-dark btn-sm w-100" id="genPdfBtn"><i class="bi bi-file-earmark-pdf"></i> Regerar PDF</button>
                    </form>
                    <script>
                    (function () {
                        var form = document.getElementById('genPdfForm');
                        if (!form) return;
                        form.addEventListener('submit', function (e) {
                            e.preventDefault();
                            var btn = document.getElementById('genPdfBtn');
                            var original = btn.innerHTML;
                            btn.disabled = true;
                            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Gerando...';
                            fetch(form.action, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: new FormData(form) })
                            .then(function (r) { return r.json(); })
                            .then(function (res) {
                                if (res && res.success) { btn.innerHTML = '<i class="bi bi-check-lg"></i> PDF gerado!'; btn.classList.add('btn-success'); setTimeout(function(){ window.location.reload(); }, 1200); }
                                else { alert((res && res.error) || 'Não foi possível gerar o PDF.'); btn.disabled = false; btn.innerHTML = original; }
                            })
                            .catch(function () { alert('Erro de conexão ao gerar o PDF.'); btn.disabled = false; btn.innerHTML = original; });
                        });
                    })();
                    </script>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Capa -->
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Capa da Revista</h6></div>
            <div class="card-body">
                <div id="cover-preview" class="mb-2 text-center" style="background:#0a1628;border-radius:6px;padding:10px;min-height:120px;display:flex;align-items:center;justify-content:center;">
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
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" id="toggleCharLimit" onchange="toggleCharLimits(this.checked)">
                        <label class="form-check-label small" for="toggleCharLimit">Desativar limite de caracteres</label>
                        <small class="text-muted d-block">Permite editar textos que ultrapassaram o limite recomendado.</small>
                    </div>
                </div>
            </div>

            <!-- Páginas -->
            <?php foreach ($pages as $page): ?>
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <h6 class="mb-0 small">Página <?= $page['page_number'] ?> — <span class="text-muted"><?= $page['layout_type'] === 'guest_column' ? 'Coluna do Convidado' : ($page['layout_type'] === 'construction_stories' ? 'Causos de Obra' : $page['layout_type']) ?></span></h6>
                    <?php if (!in_array($page['layout_type'], ['cover', 'subcover', 'backcover'])): ?>
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size:0.7rem;" onclick="deletePage(<?= $page['id'] ?>)" title="Excluir página"><i class="bi bi-trash"></i></button>
                    <?php endif; ?>
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
                        <?php
                        $guestMaxChars = 3000;
                        $guestCurrentChars = mb_strlen($page['content'] ?? '');
                        ?>
                        <textarea class="form-control form-control-sm content-textarea" name="pages[<?= $page['id'] ?>][content]" rows="8" maxlength="<?= $guestMaxChars ?>" data-max="<?= $guestMaxChars ?>" oninput="updateCharCount(this)" placeholder="Texto escrito pelo convidado... Use [imagem] para posicionar o gráfico/imagem no meio do texto."><?= htmlspecialchars($page['content'] ?? '') ?></textarea>
                        <small class="text-muted d-flex justify-content-between mt-1">
                            <span>Máx: <?= $guestMaxChars ?> caracteres — Use <code>[imagem]</code> no texto para posicionar a imagem</span>
                            <span class="char-counter <?= $guestCurrentChars > $guestMaxChars ? 'text-danger' : '' ?>"><?= $guestCurrentChars ?>/<?= $guestMaxChars ?></span>
                        </small>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Imagem / Gráfico da Coluna</label>
                        <div class="border rounded p-2" style="min-height:80px;background:#f9f9f9;">
                            <?php if ($page['image_url_2'] ?? null): ?>
                                <img src="<?= $page['image_url_2'] ?>" alt="" style="width:100%;max-height:120px;object-fit:contain;border-radius:4px;margin-bottom:5px;">
                                <button type="button" class="btn btn-sm btn-outline-danger w-100 mb-1" style="font-size:0.6rem;" onclick="deletePageImage(<?= $page['id'] ?>, 'image_url_2', this)"><i class="bi bi-trash"></i> Remover Imagem</button>
                            <?php else: ?>
                                <div class="text-center text-muted small py-2"><i class="bi bi-bar-chart"></i> Nenhuma imagem/gráfico</div>
                            <?php endif; ?>
                            <input type="file" class="form-control form-control-sm mt-1" name="page_image_<?= $page['id'] ?>_2" accept="image/*" style="font-size:0.65rem;">
                            <small class="text-muted">Aparece onde você colocar <code>[imagem]</code> no texto, ou no final se não usar o marcador.</small>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Legenda da Imagem</label>
                        <input type="text" class="form-control form-control-sm" name="pages[<?= $page['id'] ?>][image_caption]" value="<?= htmlspecialchars($page['image_caption'] ?? '') ?>" placeholder="Ex: Fonte: IBGE, 2026 — Crescimento do setor de construção civil">
                    </div>
                    <input type="hidden" name="pages[<?= $page['id'] ?>][show_images]" value="1">
                    <?php elseif ($page['layout_type'] === 'construction_stories'): ?>
                    <!-- Campos específicos: Causos de Obra -->
                    <div class="row g-2 mb-2">
                        <div class="col-md-4">
                            <label class="form-label small">Label</label>
                            <input type="text" class="form-control form-control-sm" name="pages[<?= $page['id'] ?>][caption]" value="<?= htmlspecialchars($page['caption'] ?? 'Histórias da Obra') ?>" placeholder="Ex: Histórias da Obra">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Título da Página</label>
                            <input type="text" class="form-control form-control-sm" name="pages[<?= $page['id'] ?>][title]" value="<?= htmlspecialchars($page['title'] ?? 'Causos de Obra') ?>" placeholder="Ex: Causos de Obra">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Subtítulo</label>
                            <input type="text" class="form-control form-control-sm" name="pages[<?= $page['id'] ?>][subtitle]" value="<?= htmlspecialchars($page['subtitle'] ?? '') ?>" placeholder="Ex: Histórias reais dos bastidores">
                        </div>
                    </div>
                    <hr class="my-2">
                    <label class="form-label small fw-bold"><i class="bi bi-chat-quote"></i> Causos (máx. 4)</label>
                    <small class="text-muted d-block mb-2">Cada causo pode ter um título curto e um texto de até 400 caracteres.</small>
                    <?php
                    $existingStories = array_filter(explode('|||', $page['content'] ?? ''));
                    $maxStories = 4;
                    $maxStoryChars = 400;
                    // Garante pelo menos 1 causo vazio para edição
                    while (count($existingStories) < 1) { $existingStories[] = ''; }
                    foreach ($existingStories as $si => $storyRaw):
                        if ($si >= $maxStories) break;
                        $storyLines = explode("\n", trim($storyRaw), 2);
                        $sTitle = trim($storyLines[0] ?? '');
                        $sText = trim($storyLines[1] ?? '');
                    ?>
                    <div class="card card-body p-2 mb-2 story-entry" data-index="<?= $si ?>">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge bg-success text-white">Causo <?= $si + 1 ?></span>
                            <?php if ($si > 0): ?><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1 remove-story-btn" style="font-size:0.65rem;" onclick="this.closest('.story-entry').remove()"><i class="bi bi-x"></i></button><?php endif; ?>
                        </div>
                        <input type="text" class="form-control form-control-sm mb-1" name="stories_title_<?= $page['id'] ?>[]" value="<?= htmlspecialchars($sTitle) ?>" placeholder="Título do causo (ex: O dia que o cimento sumiu)" maxlength="80">
                        <textarea class="form-control form-control-sm story-textarea" name="stories_text_<?= $page['id'] ?>[]" rows="3" maxlength="<?= $maxStoryChars ?>" data-max="<?= $maxStoryChars ?>" oninput="updateStoryCharCount(this)" placeholder="Conte o causo aqui..."><?= htmlspecialchars($sText) ?></textarea>
                        <small class="text-muted text-end mt-1 story-char-counter"><?= mb_strlen($sText) ?>/<?= $maxStoryChars ?></small>
                    </div>
                    <?php endforeach; ?>
                    <?php if (count($existingStories) < $maxStories): ?>
                    <button type="button" class="btn btn-sm btn-outline-success mt-1" id="add-story-btn-<?= $page['id'] ?>" onclick="addStoryEntry(<?= $page['id'] ?>, <?= $maxStories ?>, <?= $maxStoryChars ?>)"><i class="bi bi-plus-circle"></i> Adicionar Causo</button>
                    <?php endif; ?>
                    <input type="hidden" name="pages[<?= $page['id'] ?>][show_images]" value="1">
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
                        <?php
                        $charLimits = [
                            'cover' => 100, 'subcover' => 100, 'backcover' => 200,
                            'internal_01' => 500, 'internal_02' => 600, 'internal_03' => 1800,
                            'internal_04' => 900, 'internal_05' => 1500, 'internal_06' => 600,
                            'internal_07' => 800, 'guest_column' => 2000,
                        ];
                        $maxChars = $charLimits[$page['layout_type']] ?? 1500;
                        $currentChars = mb_strlen($page['content'] ?? '');
                        ?>
                        <textarea class="form-control form-control-sm content-textarea" name="pages[<?= $page['id'] ?>][content]" rows="4" maxlength="<?= $maxChars ?>" data-max="<?= $maxChars ?>" oninput="updateCharCount(this)"><?= htmlspecialchars($page['content'] ?? '') ?></textarea>
                        <small class="text-muted d-flex justify-content-between mt-1">
                            <span>Máx: <?= $maxChars ?> caracteres (layout <?= $page['layout_type'] ?>)</span>
                            <span class="char-counter <?= $currentChars > $maxChars ? 'text-danger' : '' ?>"><?= $currentChars ?>/<?= $maxChars ?></span>
                        </small>
                    </div>

                    <!-- Opção: Mostrar/esconder imagens nesta página -->
                    <?php if (!in_array($page['layout_type'], ['cover', 'subcover', 'backcover'])): ?>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="pages[<?= $page['id'] ?>][show_images]" value="1" id="showImages<?= $page['id'] ?>" <?= ($page['show_images'] ?? '1') !== '0' ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="showImages<?= $page['id'] ?>">Mostrar imagens nesta página</label>
                    </div>
                    <?php endif; ?>
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
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#bulkSourcesBox"><i class="bi bi-clipboard-plus"></i> Colar lista</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addSource()"><i class="bi bi-plus"></i> Adicionar</button>
                    </div>
                </div>
                <div class="collapse border-bottom" id="bulkSourcesBox">
                    <div class="p-3 bg-light">
                        <label class="form-label small mb-1">Cole a lista de fontes (uma por linha)</label>
                        <textarea id="bulkSourcesText" class="form-control form-control-sm" rows="5" placeholder="IBGE — SINAPI/Índice Nacional da Construção Civil&#10;CBIC — Qualificação, produtividade e mão de obra 2026-2029&#10;Fonte com link | https://exemplo.com"></textarea>
                        <small class="text-muted d-block mt-1">Uma por linha. <strong>Importar</strong> adiciona só os títulos. <strong>Preencher com IA</strong> separa autor/título e sugere URL e data de acesso.</small>
                        <div class="text-end mt-2 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnEnrichSources" onclick="enrichBulkSources()"><i class="bi bi-stars"></i> Preencher com IA</button>
                            <button type="button" class="btn btn-sm btn-primary" onclick="importBulkSources()"><i class="bi bi-check2"></i> Importar</button>
                        </div>
                    </div>
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

// Toggle de limite de caracteres
function toggleCharLimits(disabled) {
    var textareas = document.querySelectorAll('textarea[data-max]');
    var inputs = document.querySelectorAll('input[maxlength]');
    if (disabled) {
        textareas.forEach(function(ta) { ta.removeAttribute('maxlength'); });
        inputs.forEach(function(inp) {
            inp.dataset.originalMaxlength = inp.getAttribute('maxlength');
            inp.removeAttribute('maxlength');
        });
        localStorage.setItem('magazine_char_limit_off', '1');
    } else {
        textareas.forEach(function(ta) {
            if (ta.dataset.max) ta.setAttribute('maxlength', ta.dataset.max);
        });
        inputs.forEach(function(inp) {
            if (inp.dataset.originalMaxlength) inp.setAttribute('maxlength', inp.dataset.originalMaxlength);
        });
        localStorage.removeItem('magazine_char_limit_off');
    }
    document.querySelectorAll('.content-textarea').forEach(function(ta) { updateCharCount(ta); });
    document.querySelectorAll('.story-textarea').forEach(function(ta) { updateStoryCharCount(ta); });
}

// Restaura estado do toggle ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    if (localStorage.getItem('magazine_char_limit_off') === '1') {
        var toggle = document.getElementById('toggleCharLimit');
        if (toggle) {
            toggle.checked = true;
            toggleCharLimits(true);
        }
    }
});

// Contador de caracteres
function updateCharCount(textarea) {
    const max = parseInt(textarea.dataset.max) || 1500;
    const current = textarea.value.length;
    const counter = textarea.parentElement.querySelector('.char-counter');
    if (counter) {
        counter.textContent = current + '/' + max;
        counter.className = 'char-counter ' + (current > max ? 'text-danger fw-bold' : current > max * 0.9 ? 'text-warning' : '');
    }
}

// Contador de caracteres para causos
function updateStoryCharCount(textarea) {
    const max = parseInt(textarea.dataset.max) || 400;
    const current = textarea.value.length;
    const counter = textarea.closest('.story-entry')?.querySelector('.story-char-counter');
    if (counter) {
        counter.textContent = current + '/' + max;
        counter.className = 'text-muted text-end mt-1 story-char-counter ' + (current > max ? 'text-danger fw-bold' : current > max * 0.9 ? 'text-warning' : '');
    }
}

// Adicionar novo causo
function addStoryEntry(pageId, maxStories, maxChars) {
    const container = document.querySelector('#add-story-btn-' + pageId)?.parentElement;
    if (!container) return;
    const entries = container.querySelectorAll('.story-entry');
    const idx = entries.length;
    if (idx >= maxStories) {
        alert('Máximo de ' + maxStories + ' causos por página.');
        return;
    }
    const div = document.createElement('div');
    div.className = 'card card-body p-2 mb-2 story-entry';
    div.dataset.index = idx;
    div.innerHTML = '<div class="d-flex justify-content-between align-items-center mb-1">' +
        '<span class="badge bg-success text-white">Causo ' + (idx + 1) + '</span>' +
        '<button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" style="font-size:0.65rem;" onclick="this.closest(\'.story-entry\').remove()"><i class="bi bi-x"></i></button>' +
        '</div>' +
        '<input type="text" class="form-control form-control-sm mb-1" name="stories_title_' + pageId + '[]" value="" placeholder="Título do causo (ex: O dia que o cimento sumiu)" maxlength="80">' +
        '<textarea class="form-control form-control-sm story-textarea" name="stories_text_' + pageId + '[]" rows="3" maxlength="' + maxChars + '" data-max="' + maxChars + '" oninput="updateStoryCharCount(this)" placeholder="Conte o causo aqui..."></textarea>' +
        '<small class="text-muted text-end mt-1 story-char-counter">0/' + maxChars + '</small>';
    const btn = document.querySelector('#add-story-btn-' + pageId);
    container.insertBefore(div, btn);
    if (container.querySelectorAll('.story-entry').length >= maxStories) {
        btn.style.display = 'none';
    }
}
// Inicializar contadores
document.querySelectorAll('.content-textarea').forEach(ta => updateCharCount(ta));

// Toggle de imagens por página
document.querySelectorAll('[id^="showImages"]').forEach(chk => {
    const pageCard = chk.closest('.card');
    const imageSection = pageCard?.querySelector('.row.g-2:has([name*="page_image"])') || pageCard?.querySelectorAll('.row.g-2')[1];
    if (chk && imageSection) {
        const toggle = () => { imageSection.style.display = chk.checked ? '' : 'none'; };
        toggle();
        chk.addEventListener('change', toggle);
    }
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

// Deletar página inteira
async function deletePage(pageId) {
    if (!confirm('Tem certeza que deseja excluir esta página? Esta ação não pode ser desfeita.')) return;
    const resp = await fetch('/admin/magazines/delete-page', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ page_id: pageId, magazine_id: <?= $magazine['id'] ?> })
    });
    const data = await resp.json();
    if (data.success) {
        location.reload();
    } else {
        alert(data.error || 'Erro ao excluir página.');
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
                <div id="addPageOptions" style="display:grid;gap:8px;">
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
    const optionsDiv = document.getElementById('addPageOptions');
    if (optionsDiv) {
        optionsDiv.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted small">Salvando alterações e adicionando página...</p></div>';
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
        document.querySelector('[style*="position:fixed"][style*="z-index:9999"]')?.remove();
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

function escapeAttr(str) {
    return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

// Cria uma linha de fonte, opcionalmente pré-preenchida com {title, url, author, accessed_at}
function addSource(data) {
    data = data || {};
    document.getElementById('noSourcesMsg')?.remove();
    const container = document.getElementById('sourcesContainer');
    const title = escapeAttr(data.title || '');
    const url = escapeAttr(data.url || '');
    const author = escapeAttr(data.author || '');
    const accessedAt = escapeAttr(data.accessed_at || '');
    const html = `<div class="source-row row g-2 mb-2 align-items-center" data-index="${sourceIndex}">
        <div class="col-md-4"><input type="text" class="form-control form-control-sm" name="sources[${sourceIndex}][title]" value="${title}" placeholder="Título *"></div>
        <div class="col-md-4"><input type="url" class="form-control form-control-sm" name="sources[${sourceIndex}][url]" value="${url}" placeholder="URL"></div>
        <div class="col-md-2"><input type="text" class="form-control form-control-sm" name="sources[${sourceIndex}][author]" value="${author}" placeholder="Autor"></div>
        <div class="col-md-1"><input type="date" class="form-control form-control-sm" name="sources[${sourceIndex}][accessed_at]" value="${accessedAt}" title="Data de acesso"></div>
        <div class="col-md-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.source-row').remove()"><i class="bi bi-trash"></i></button></div>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
    sourceIndex++;
}

// Envia a lista colada para a IA estruturar (autor, título, URL, data) e cria as linhas
async function enrichBulkSources() {
    const ta = document.getElementById('bulkSourcesText');
    const text = ta.value.trim();
    if (!text) { alert('Cole a lista de fontes primeiro.'); return; }

    const btn = document.getElementById('btnEnrichSources');
    const original = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processando...';

    try {
        const fd = new FormData();
        fd.append('sources_text', text);
        const resp = await fetch('/admin/magazines/enrich-sources', { method: 'POST', body: fd });
        const data = await resp.json();

        if (!data.success || !data.sources || data.sources.length === 0) {
            alert(data.error || 'A IA não retornou fontes. Tente novamente.');
            return;
        }

        data.sources.forEach(function(s) { addSource(s); });
        ta.value = '';
        bootstrap.Collapse.getOrCreateInstance(document.getElementById('bulkSourcesBox')).hide();
        alert(data.sources.length + ' fonte(s) preenchida(s) pela IA. Revise os dados e clique em "Salvar Alterações".');
    } catch (e) {
        alert('Erro ao processar com IA: ' + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = original;
    }
}

// Importa várias fontes coladas em texto (uma por linha; "Título | URL" opcional)
function importBulkSources() {
    const ta = document.getElementById('bulkSourcesText');
    const lines = ta.value.split('\n');
    let added = 0;

    lines.forEach(function(raw) {
        const line = raw.trim();
        if (!line) return;

        let title = line;
        let url = '';
        // Separa "Título | URL" — ou extrai URL solta no fim da linha
        if (line.includes('|')) {
            const parts = line.split('|');
            title = parts[0].trim();
            url = (parts[1] || '').trim();
        } else {
            const m = line.match(/\s(https?:\/\/\S+)$/i);
            if (m) {
                url = m[1].trim();
                title = line.slice(0, m.index).trim();
            }
        }

        if (title) {
            addSource({ title: title, url: url });
            added++;
        }
    });

    if (added > 0) {
        ta.value = '';
        bootstrap.Collapse.getOrCreateInstance(document.getElementById('bulkSourcesBox')).hide();
        alert(added + ' fonte(s) adicionada(s). Não esqueça de clicar em "Salvar Alterações".');
    } else {
        alert('Nenhuma fonte válida encontrada. Verifique o texto colado.');
    }
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
