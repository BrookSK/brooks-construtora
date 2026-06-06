<?php $pageTitle = htmlspecialchars($magazine['title']); include ROOT_PATH . '/app/Views/site/layouts/header.php'; ?>

<section class="magazine-viewer py-5">
    <div class="container">
        <div class="text-center mb-4">
            <h1><?= htmlspecialchars($magazine['title']) ?></h1>
            <?php if ($magazine['subtitle']): ?>
                <p class="lead text-muted"><?= htmlspecialchars($magazine['subtitle']) ?></p>
            <?php endif; ?>
            <span class="text-muted">Publicada em <?= date('d/m/Y', strtotime($magazine['published_at'])) ?></span>
        </div>

        <div class="magazine-pages">
            <?php foreach ($pages as $page): ?>
                <?php if ($page['layout_type'] === 'cover'): ?>
                    <div class="mag-page mag-cover">
                        <?php if ($magazine['cover_image']): ?>
                            <img src="<?= $magazine['cover_image'] ?>" alt="Capa" class="img-fluid rounded">
                        <?php else: ?>
                            <div class="mag-cover-text">
                                <h2><?= htmlspecialchars($magazine['title']) ?></h2>
                                <p><?= htmlspecialchars($magazine['subtitle'] ?? '') ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($page['layout_type'] === 'backcover'): ?>
                    <div class="mag-page mag-backcover">
                        <div class="mag-backcover-content">
                            <h3>BROOKS CONSTRUTORA</h3>
                            <p><?= nl2br(htmlspecialchars($page['content'] ?? '')) ?></p>
                            <p class="mt-4"><small>&copy; <?= date('Y') ?> Brooks Construtora. Todos os direitos reservados.</small></p>
                            <p><small>www.brooksconstrutora.com.br</small></p>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="mag-page mag-content">
                        <div class="mag-page-header">
                            <span class="mag-logo">BROOKS <small>CONSTRUTORA</small></span>
                            <span class="mag-page-number"><?= str_pad($page['page_number'], 2, '0', STR_PAD_LEFT) ?></span>
                        </div>

                        <?php if ($page['image_url']): ?>
                            <div class="mag-page-image">
                                <img src="<?= $page['image_url'] ?>" alt="" class="img-fluid rounded">
                            </div>
                        <?php endif; ?>

                        <?php if ($page['title']): ?>
                            <h3 class="mag-page-title"><?= htmlspecialchars($page['title']) ?></h3>
                        <?php endif; ?>

                        <?php if ($page['content']): ?>
                            <div class="mag-page-text">
                                <?php foreach (explode("\n", $page['content']) as $p): ?>
                                    <?php if (trim($p)): ?>
                                        <p><?= htmlspecialchars(trim($p)) ?></p>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5">
            <a href="/revista" class="btn btn-outline-dark">← Voltar para Revistas</a>
        </div>
    </div>
</section>

<?php include ROOT_PATH . '/app/Views/site/layouts/footer.php'; ?>
