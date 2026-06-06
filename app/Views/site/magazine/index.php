<?php include __DIR__ . '/../layouts/header.php'; ?>

<div id="content" role="main" class="content-area">

	<!-- Page Title -->
	<section class="section" id="section_magazine_title">
		<div class="bg section-bg fill bg-fill bg-loaded"></div>
		<div class="section-content relative">
			<div class="row align-center">
				<div class="col medium-10 small-12 large-10">
					<div class="col-inner text-center">
						<div class="gap-element clearfix" style="display:block; height:auto; padding-top: 45px;"></div>
						<h4 class="uppercase" style="text-align: left;"><em><strong><span style="font-size: 150%;">Revista Brooks</span></strong></em></h4>
						<div class="text" style="font-size: 0.8rem; color: rgb(0, 0, 0); text-align: left;">
							<p>Acompanhe nossas edições com conteúdos exclusivos sobre reformas, construção, arquitetura e decoração de alto padrão.</p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<style>
			#section_magazine_title { padding-top: 30px; padding-bottom: 0px; }
		</style>
	</section>

	<!-- Magazine Listing -->
	<section class="section" id="section_magazine_listing">
		<div class="bg section-bg fill bg-fill bg-loaded"></div>
		<div class="section-content relative">
			<div class="row row-small align-center">
				<div class="col small-12 large-12">
					<div class="col-inner">

						<div class="row">
							<?php if (!empty($magazines)): ?>
								<?php foreach ($magazines as $magazine): ?>
								<div class="col medium-6 small-12 large-4">
									<div class="col-inner" style="padding: 0; margin: 0 0 30px 0;">
										<div class="box has-hover box-default box-text-bottom" style="border-radius: 4px; overflow: hidden;">
											<div class="box-image">
												<a href="/revista/<?= htmlspecialchars($magazine['slug'] ?? $magazine['id']) ?>">
													<div class="image-zoom image-cover" style="padding-top: 140%; background-color: #3a3b4e;">
														<?php if (!empty($magazine['cover_image'])): ?>
														<img src="<?= htmlspecialchars($magazine['cover_image']) ?>" alt="<?= htmlspecialchars($magazine['title']) ?>" />
														<?php else: ?>
														<div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: center; background-color: #3a3b4e; color: #fff; font-size: 1.2rem; padding: 20px; text-align: center;">
															<span><?= htmlspecialchars($magazine['title']) ?></span>
														</div>
														<?php endif; ?>
													</div>
												</a>
											</div>
											<div class="box-text text-left" style="background-color:rgb(58, 59, 78);padding:10px 15px;">
												<div class="box-text-inner">
													<div class="text" style="font-size: 0.85rem; color: rgb(255,255,255);">
														<h3 style="margin-bottom: 5px; font-size: 1rem;"><?= htmlspecialchars($magazine['title']) ?></h3>
														<?php if (!empty($magazine['published_at'])): ?>
														<p style="opacity: 0.7; font-size: 0.8rem;">
															<?= date('d/m/Y', strtotime($magazine['published_at'])) ?>
														</p>
														<?php endif; ?>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<?php endforeach; ?>
							<?php else: ?>
								<div class="col small-12 large-12">
									<div class="col-inner text-center" style="padding: 60px 0;">
										<div class="text" style="font-size: 1rem; color: rgb(100, 100, 100);">
											<p>Nenhuma edição disponível no momento.</p>
											<p>Cadastre-se em nossa newsletter para ser avisado sobre novas edições.</p>
										</div>
									</div>
								</div>
							<?php endif; ?>
						</div>

					</div>
				</div>
			</div>
		</div>
		<style>
			#section_magazine_listing { padding-top: 30px; padding-bottom: 60px; }
		</style>
	</section>

</div><!-- #content -->

<?php include __DIR__ . '/../layouts/footer.php'; ?>
