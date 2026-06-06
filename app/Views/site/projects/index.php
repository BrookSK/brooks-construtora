<?php $pageTitle = 'Projetos'; include ROOT_PATH . '/app/Views/site/layouts/header.php'; ?>

<!-- Page Header -->
<section class="page-header" style="background-image: url('/assets/images/projetos-header.jpg');">
    <div class="page-header-overlay"></div>
    <div class="container">
        <h1>Nossos Projetos</h1>
        <p>Conheça alguns dos nossos trabalhos realizados</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <?php if (empty($projects)): ?>
            <p class="text-center text-muted py-5">Nenhum projeto disponível no momento.</p>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($projects as $project): ?>
                <div class="col-md-4">
                    <a href="/projeto/<?= htmlspecialchars($project['slug']) ?>" class="project-card">
                        <div class="project-image">
                            <?php if ($project['featured_image']): ?>
                                <img src="<?= $project['featured_image'] ?>" alt="<?= htmlspecialchars($project['title']) ?>">
                            <?php else: ?>
                                <div class="project-placeholder">
                                    <i class="bi bi-building"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="project-info">
                            <h5><?= htmlspecialchars($project['title']) ?></h5>
                            <?php if ($project['category']): ?>
                                <span class="project-category"><?= htmlspecialchars($project['category']) ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include ROOT_PATH . '/app/Views/site/layouts/footer.php'; ?>
