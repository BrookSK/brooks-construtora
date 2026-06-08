<?php $pageTitle = 'Revista Digital'; $currentPage = 'revista'; include ROOT_PATH . '/app/Views/site/layouts/header.php'; ?>

<div id="content" role="main" class="content-area">

<!-- Banner -->
<section class="section" id="section_revista_banner">
	<div class="bg section-bg fill bg-fill bg-loaded"></div>
	<div class="section-content relative">
		<div class="row align-center">
			<div class="col medium-6 small-12 large-6">
				<div class="col-inner text-center dark" style="padding: 150px 0px 50px 0px;">
					<div class="text texto-banners">
						<h1 style="font-size: 150%;">REVISTA DIGITAL</h1>
						<p><span style="font-size: 120%;">Conteúdo exclusivo sobre construção e reformas</span></p>
					</div>
				</div>
			</div>
		</div>
	</div>
	<style>
		#section_revista_banner { padding-top: 30px; padding-bottom: 30px; }
		#section_revista_banner .section-bg.bg-loaded { background-image: url(/assets/images/wp/2023/01/fundo-3.jpg); }
	</style>
</section>

<!-- Conteúdo da Revista -->
<section class="section" id="section_revista_content">
	<div class="bg section-bg fill bg-fill bg-loaded"></div>
	<div class="section-content relative">
		<div class="row align-center">
			<div class="col medium-10 small-12 large-10">
				<div class="col-inner" style="padding: 40px 0;">

					<?php if (empty($magazines)): ?>
						<div class="text-center" style="padding: 60px 20px;">
							<h3 style="color: #3a3b4e; margin-bottom: 15px;">Em breve!</h3>
							<p style="font-size: 1.1rem; color: #666; max-width: 500px; margin: 0 auto;">Estamos preparando a primeira edição da nossa revista digital sobre construção, reformas e arquitetura de alto padrão.</p>
							<p style="font-size: 1rem; color: #666; margin-top: 20px;">Inscreva-se na newsletter no rodapé para ser avisado quando publicarmos.</p>
						</div>
					<?php else: ?>
						<div class="row">
							<?php foreach ($magazines as $mag): ?>
							<div class="col medium-4 small-6 large-4">
								<div class="col-inner">
									<a href="/revista/ver/<?= $mag['id'] ?>" style="text-decoration: none; color: inherit;">
										<div class="box has-hover box-default" style="margin-bottom: 20px;">
											<div class="box-image" style="aspect-ratio: 3/4; overflow: hidden; border-radius: 4px; box-shadow: 0 5px 20px rgba(0,0,0,0.15);">
												<?php if ($mag['cover_image']): ?>
													<img src="<?= $mag['cover_image'] ?>" alt="<?= htmlspecialchars($mag['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;" />
												<?php else: ?>
													<div style="width: 100%; height: 100%; background: linear-gradient(135deg, #3a3b4e, #446084); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1rem; text-align: center; padding: 20px;">
														<?= htmlspecialchars($mag['title']) ?>
													</div>
												<?php endif; ?>
											</div>
											<div class="box-text" style="padding: 15px 5px;">
												<h5 style="margin: 0 0 5px; font-size: 1rem;"><?= htmlspecialchars($mag['topic_title'] ?? $mag['title']) ?></h5>
												<p style="margin: 0; font-size: 0.85rem; color: #888;"><?= date('d/m/Y', strtotime($mag['published_at'])) ?></p>
											</div>
										</div>
									</a>
								</div>
							</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

				</div>
			</div>
		</div>
	</div>
	<style>
		#section_revista_content { padding-top: 0px; padding-bottom: 30px; }
	</style>
</section>

<!-- CTA Newsletter -->
<section class="section" id="section_revista_cta">
	<div class="bg section-bg fill bg-fill bg-loaded"></div>
	<div class="section-content relative">
		<div class="row align-center">
			<div class="col medium-8 small-12 large-6">
				<div class="col-inner text-center" style="padding: 40px 20px;">
					<h3 style="color: #fff; margin-bottom: 10px;">Assine a Revista Brooks</h3>
					<p style="color: rgba(255,255,255,0.8); margin-bottom: 20px;">Receba edições exclusivas sobre construção sustentável, reformas de alto padrão e tendências de arquitetura.</p>
					<form action="/newsletter/subscribe" method="POST" class="newsletter-form">
						<div style="display: flex; gap: 8px; max-width: 450px; margin: 0 auto; flex-wrap: wrap; justify-content: center;">
							<input type="text" name="name" placeholder="Seu nome" style="flex: 1; min-width: 150px; padding: 10px 15px; border: none; border-radius: 4px; font-size: 14px;">
							<input type="email" name="email" placeholder="Seu e-mail" required style="flex: 1; min-width: 200px; padding: 10px 15px; border: none; border-radius: 4px; font-size: 14px;">
							<button type="submit" class="button secondary" style="padding: 10px 25px; white-space: nowrap;">Assinar Grátis</button>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
	<style>
		#section_revista_cta { padding-top: 20px; padding-bottom: 20px; background-color: #3a3b4e; }
	</style>
</section>

</div><!-- #content -->

<?php include ROOT_PATH . '/app/Views/site/layouts/footer.php'; ?>
