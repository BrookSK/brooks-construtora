<?php $pageTitle = 'Revista Digital'; include ROOT_PATH . '/app/Views/site/layouts/header.php'; ?>

<!-- Page Header -->
<section class="page-header" style="background-image: url('/assets/images/revista-header.jpg');">
    <div class="page-header-overlay"></div>
    <div class="container">
        <h1>Revista Digital</h1>
        <p>Conteúdo exclusivo sobre construção, reformas e arquitetura</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <?php if (empty($magazines)): ?>
            <div class="text-center py-5">
                <i class="bi bi-journal-richtext display-3 text-muted"></i>
                <h4 class="mt-3 text-muted">Nenhuma edição disponível ainda</h4>
                <p class="text-muted">Inscreva-se na nossa newsletter para ser avisado quando publicarmos a primeira edição.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($magazines as $mag): ?>
                <div class="col-md-4 col-sm-6">
                    <a href="/revista/ver/<?= $mag['id'] ?>" class="magazine-card">
                        <div class="magazine-cover">
                            <?php if ($mag['cover_image']): ?>
                                <img src="<?= $mag['cover_image'] ?>" alt="<?= htmlspecialchars($mag['title']) ?>">
                            <?php else: ?>
                                <div class="magazine-cover-placeholder">
                                    <i class="bi bi-journal-richtext"></i>
                                    <span><?= htmlspecialchars($mag['title']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="magazine-info">
                            <h5><?= htmlspecialchars($mag['title']) ?></h5>
                            <span class="magazine-date">Publicada em <?= date('d/m/Y', strtotime($mag['published_at'])) ?></span>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include ROOT_PATH . '/app/Views/site/layouts/footer.php'; ?>
