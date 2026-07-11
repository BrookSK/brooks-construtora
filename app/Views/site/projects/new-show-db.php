<?php
$pageTitle = htmlspecialchars($project['name'] ?? $project['title'] ?? 'Projeto');
$pageDescription = htmlspecialchars($project['description'] ?? 'Projeto de reforma de alto padrão pela Brooks Construtora.');
$currentPage = 'projetos';
$bodyClass = 'page-projeto-single';
include ROOT_PATH . '/app/Views/site/layouts/new-header.php';
?>

<!-- Project Hero -->
<section class="section section--dark" style="padding-top: calc(var(--header-height) + var(--space-3xl)); padding-bottom: var(--space-3xl);">
    <div class="container">
        <a href="/projetos" style="display: inline-flex; align-items: center; gap: var(--space-xs); font-size: var(--text-sm); color: rgba(255,255,255,0.5); margin-bottom: var(--space-lg);">
            <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Voltar para Projetos
        </a>
        <h1 class="headline-section" style="color: white; margin-bottom: var(--space-xs);"><?= htmlspecialchars($project['name'] ?? $project['title'] ?? '') ?></h1>
        <?php if (!empty($project['subtitle'])): ?>
            <p style="font-size: var(--text-lg); color: rgba(255,255,255,0.6);"><?= htmlspecialchars($project['subtitle']) ?></p>
        <?php endif; ?>
    </div>
</section>

<!-- Cover -->
<?php if (!empty($images[0])): ?>
<section style="margin-top: -1px;">
    <img src="<?= htmlspecialchars($images[0]['url'] ?? $images[0]['image_url'] ?? '') ?>" alt="<?= htmlspecialchars($project['name'] ?? '') ?>" style="width: 100%; max-height: 560px; object-fit: cover;">
</section>
<?php endif; ?>

<!-- Project Info -->
<section class="section">
    <div class="container">
        <div class="grid grid--2" style="gap: var(--space-4xl); align-items: flex-start;">
            <div>
                <span class="label">Sobre o Projeto</span>
                <?php if (!empty($project['description'])): ?>
                    <p style="font-size: var(--text-lg); color: var(--brooks-gray-600); line-height: 1.8; margin-top: var(--space-md);">
                        <?= nl2br(htmlspecialchars($project['description'])) ?>
                    </p>
                <?php endif; ?>
            </div>
            <div>
                <div style="background: var(--brooks-off-white); border-radius: var(--radius-xl); padding: var(--space-xl);">
                    <h3 style="font-size: var(--text-base); font-weight: 600; margin-bottom: var(--space-lg); color: var(--brooks-navy);">Ficha Técnica</h3>
                    <div style="display: grid; gap: var(--space-md);">
                        <?php if (!empty($project['location'])): ?>
                        <div style="display: flex; justify-content: space-between; padding-bottom: var(--space-sm); border-bottom: 1px solid var(--brooks-gray-200);">
                            <span style="font-size: var(--text-sm); color: var(--brooks-gray-500);">Localização</span>
                            <span style="font-size: var(--text-sm); font-weight: 600; color: var(--brooks-navy);"><?= htmlspecialchars($project['location']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($project['area'])): ?>
                        <div style="display: flex; justify-content: space-between; padding-bottom: var(--space-sm); border-bottom: 1px solid var(--brooks-gray-200);">
                            <span style="font-size: var(--text-sm); color: var(--brooks-gray-500);">Área</span>
                            <span style="font-size: var(--text-sm); font-weight: 600; color: var(--brooks-navy);"><?= htmlspecialchars($project['area']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="font-size: var(--text-sm); color: var(--brooks-gray-500);">Construtora</span>
                            <span style="font-size: var(--text-sm); font-weight: 600; color: var(--brooks-navy);">Brooks Construtora</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Gallery -->
<?php if (!empty($images) && count($images) > 1): ?>
<section class="section section--gray">
    <div class="container">
        <div class="section-header">
            <span class="label">Galeria</span>
            <h2 class="headline-subsection">Registros do projeto</h2>
        </div>
        
        <div class="grid grid--2" style="gap: var(--space-md);">
            <?php foreach ($images as $i => $img): ?>
                <div style="border-radius: var(--radius-lg); overflow: hidden; <?= $i === 0 ? 'grid-column: span 2;' : '' ?>">
                    <img src="<?= htmlspecialchars($img['url'] ?? $img['image_url'] ?? '') ?>" alt="<?= htmlspecialchars($project['name'] ?? '') ?> - Foto <?= $i + 1 ?>" style="width: 100%; height: <?= $i === 0 ? '400px' : '300px' ?>; object-fit: cover;" loading="lazy">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA -->
<section class="section" style="padding: var(--space-4xl) 0;">
    <div class="container text-center">
        <h2 class="headline-subsection">Quer um resultado como este?</h2>
        <p class="subtitle subtitle--centered" style="margin-bottom: var(--space-xl);">Entre em contato e solicite um orçamento sem compromisso.</p>
        <div style="display: flex; gap: var(--space-md); justify-content: center; flex-wrap: wrap;">
            <a href="/contato" class="btn btn--primary btn--lg">Solicitar Orçamento</a>
            <a href="/projetos" class="btn btn--outline btn--lg">Ver outros projetos</a>
        </div>
    </div>
</section>

<?php include ROOT_PATH . '/app/Views/site/layouts/new-footer.php'; ?>
