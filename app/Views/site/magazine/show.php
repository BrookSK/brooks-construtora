<?php include __DIR__ . '/../layouts/header.php'; ?>

<div id="content" role="main" class="content-area">

	<!-- Page Title -->
	<section class="section" id="section_magazine_show_title">
		<div class="bg section-bg fill bg-fill bg-loaded"></div>
		<div class="section-content relative">
			<div class="row align-center">
				<div class="col medium-10 small-12 large-10">
					<div class="col-inner text-center">
						<div class="gap-element clearfix" style="display:block; height:auto; padding-top: 45px;"></div>
						<h4 class="uppercase" style="text-align: left;">
							<em><strong><span style="font-size: 150%;"><?= htmlspecialchars($magazine['title'] ?? 'Revista Brooks') ?></span></strong></em>
						</h4>
						<?php if (!empty($magazine['published_at'])): ?>
						<div class="text" style="font-size: 0.8rem; color: rgb(100, 100, 100); text-align: left;">
							<p>Publicado em <?= date('d/m/Y', strtotime($magazine['published_at'])) ?></p>
						</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<style>
			#section_magazine_show_title { padding-top: 30px; padding-bottom: 0px; }
		</style>
	</section>

	<!-- Magazine Content -->
	<section class="section" id="section_magazine_content">
		<div class="bg section-bg fill bg-fill bg-loaded"></div>
		<div class="section-content relative">
			<div class="row align-center">
				<div class="col medium-10 small-12 large-10">
					<div class="col-inner">

						<?php if (!empty($pages)): ?>
							<?php foreach ($pages as $page): ?>
							<article class="magazine-topic" style="margin-bottom: 40px; padding-bottom: 40px; border-bottom: 1px solid #eee;">
								<h2 style="font-size: 1.4rem; font-weight: 400; margin-bottom: 15px; color: #3a3b4e;">
									<?= htmlspecialchars($page['title'] ?? '') ?>
								</h2>
								<?php if (!empty($page['image'])): ?>
								<div style="margin-bottom: 20px;">
									<img src="<?= htmlspecialchars($page['image']) ?>" alt="<?= htmlspecialchars($page['title'] ?? '') ?>" style="width: 100%; max-height: 400px; object-fit: cover; border-radius: 4px;" />
								</div>
								<?php endif; ?>
								<div class="text" style="font-size: 0.9rem; line-height: 1.7; color: rgb(50, 50, 50);">
									<?= $page['content'] ?? '' ?>
								</div>
							</article>
							<?php endforeach; ?>
						<?php elseif (!empty($magazine['content'])): ?>
							<div class="text" style="font-size: 0.9rem; line-height: 1.7; color: rgb(50, 50, 50);">
								<?= $magazine['content'] ?>
							</div>
						<?php endif; ?>

					</div>
				</div>
			</div>
		</div>
		<style>
			#section_magazine_content { padding-top: 30px; padding-bottom: 30px; }
		</style>
	</section>

	<!-- Newsletter CTA -->
	<section class="section" id="section_magazine_newsletter">
		<div class="bg section-bg fill bg-fill bg-loaded" style="background-color: #3a3b4e;"></div>
		<div class="section-content relative">
			<div class="row align-center">
				<div class="col medium-8 small-12 large-6">
					<div class="col-inner text-center" style="padding: 40px 20px;">
						<div class="text" style="color: #fff;">
							<h3 style="font-weight: 400; margin-bottom: 10px;">Gostou do conteúdo?</h3>
							<p style="opacity: 0.8; margin-bottom: 20px;">Cadastre-se para receber as próximas edições da Revista Brooks diretamente no seu e-mail.</p>
						</div>
						<form action="/newsletter/subscribe" method="POST" class="newsletter-form">
							<div style="display: flex; gap: 8px; max-width: 400px; margin: 0 auto;">
								<input type="email" name="email" placeholder="Seu e-mail" required
									style="flex: 1; padding: 10px 15px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.1); color: #fff; border-radius: 4px; font-size: 0.9rem;">
								<button type="submit" class="button secondary" style="padding: 10px 20px;">
									<span>Assinar</span>
								</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<style>
			#section_magazine_newsletter { padding-top: 0px; padding-bottom: 0px; }
		</style>
	</section>

	<!-- Back to magazine list -->
	<section class="section" id="section_magazine_back">
		<div class="section-content relative">
			<div class="row align-center">
				<div class="col medium-10 small-12 large-10">
					<div class="col-inner text-center">
						<a href="/revista" class="button secondary" style="padding: 10px 30px;">
							<span>&larr; Voltar para Revista</span>
						</a>
					</div>
				</div>
			</div>
		</div>
		<style>
			#section_magazine_back { padding-top: 30px; padding-bottom: 60px; }
		</style>
	</section>

</div><!-- #content -->

<?php include __DIR__ . '/../layouts/footer.php'; ?>
