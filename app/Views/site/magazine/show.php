<?php $pageTitle = htmlspecialchars($magazine['title']); $currentPage = 'revista'; include ROOT_PATH . '/app/Views/site/layouts/header.php'; ?>

<div id="content" role="main" class="content-area">

<section class="section" id="section_magazine_view">
	<div class="bg section-bg fill bg-fill bg-loaded"></div>
	<div class="section-content relative">
		<div class="row align-center">
			<div class="col medium-10 small-12 large-8">
				<div class="col-inner" style="padding: 40px 0;">

					<!-- Título -->
					<div class="text-center" style="margin-bottom: 40px;">
						<h1 style="color: #3a3b4e;"><?= htmlspecialchars($magazine['title']) ?></h1>
						<?php if ($magazine['subtitle']): ?>
							<p style="font-size: 1.1rem; color: #666;"><?= htmlspecialchars($magazine['subtitle']) ?></p>
						<?php endif; ?>
						<p style="font-size: 0.85rem; color: #999;">Publicada em <?= date('d/m/Y', strtotime($magazine['published_at'])) ?></p>
					</div>

					<!-- Capa -->
					<?php if ($magazine['cover_image']): ?>
					<div style="text-align: center; margin-bottom: 40px;">
						<img src="<?= $magazine['cover_image'] ?>" alt="Capa" style="max-width: 100%; border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
					</div>
					<?php endif; ?>

					<!-- Páginas -->
					<?php foreach ($pages as $page): ?>
						<?php if ($page['layout_type'] === 'cover' || $page['layout_type'] === 'subcover'): continue; endif; ?>

						<?php if ($page['layout_type'] === 'backcover'): ?>
							<div style="background: #3a3b4e; color: #fff; padding: 40px; border-radius: 8px; text-align: center; margin-top: 40px;">
								<h3>BROOKS CONSTRUTORA</h3>
								<p><?= nl2br(htmlspecialchars($page['content'] ?? 'Construção consciente do zero ao acabamento.')) ?></p>
								<p style="margin-top: 20px; font-size: 0.85rem; opacity: 0.7;">&copy; <?= date('Y') ?> Brooks Construtora | www.brooksconstrutora.com.br</p>
							</div>
						<?php else: ?>
							<div style="margin-bottom: 40px; padding-bottom: 30px; border-bottom: 1px solid #eee;">
								<?php if ($page['image_url']): ?>
									<img src="<?= $page['image_url'] ?>" alt="" style="width: 100%; border-radius: 8px; margin-bottom: 20px;">
								<?php endif; ?>

								<?php if ($page['title']): ?>
									<h3 style="color: #3a3b4e; font-style: italic; margin-bottom: 15px;"><?= htmlspecialchars($page['title']) ?></h3>
								<?php endif; ?>

								<?php if ($page['content']): ?>
									<?php foreach (explode("\n", $page['content']) as $p): ?>
										<?php if (trim($p)): ?>
											<p style="line-height: 1.8; text-align: justify; margin-bottom: 10px;"><?= htmlspecialchars(trim($p)) ?></p>
										<?php endif; ?>
									<?php endforeach; ?>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>

					<!-- Voltar -->
					<div class="text-center" style="margin-top: 40px;">
						<a href="/revista" class="button secondary" style="padding: 10px 25px;">← Voltar para Revistas</a>
					</div>

				</div>
			</div>
		</div>
	</div>
	<style>
		#section_magazine_view { padding-top: 20px; padding-bottom: 30px; }
	</style>
</section>

</div><!-- #content -->

<?php include ROOT_PATH . '/app/Views/site/layouts/footer.php'; ?>
