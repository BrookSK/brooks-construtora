<?php $pageTitle = htmlspecialchars($project['title']); include ROOT_PATH . '/app/Views/site/layouts/header.php'; ?>

<!-- Page Header -->
<section class="page-header" style="background-image: url('<?= $project['featured_image'] ?: '/assets/images/projetos-header.jpg' ?>');">
    <div class="page-header-overlay"></div>
    <div class="container">
        <h1><?= htmlspecialchars($project['title']) ?></h1>
        <?php if ($project['category']): ?>
            <p><?= htmlspecialchars($project['category']) ?></p>
        <?php endif; ?>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <?php if ($project['description']): ?>
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto">
                <div class="project-description">
                    <?= nl2br(htmlspecialchars($project['description'])) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($images)): ?>
        <div class="row g-3">
            <?php foreach ($images as $img): ?>
            <div class="col-md-6 col-lg-4">
                <div class="project-gallery-item">
                    <img src="<?= htmlspecialchars($img['image_url']) ?>" alt="<?= htmlspecialchars($img['caption'] ?? $project['title']) ?>" class="img-fluid rounded">
                    <?php if ($img['caption']): ?>
                        <p class="text-muted small mt-2"><?= htmlspecialchars($img['caption']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="text-center mt-5">
            <a href="/projetos" class="btn btn-outline-dark">← Voltar para Projetos</a>
        </div>
    </div>
</section>

<?php include ROOT_PATH . '/app/Views/site/layouts/footer.php'; ?>
