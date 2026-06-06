<?php include __DIR__ . '/../layouts/header.php'; ?>

<div id="content" role="main" class="content-area">

	<!-- Page Title -->
	<section class="section" id="section_projects_title">
		<div class="bg section-bg fill bg-fill bg-loaded"></div>
		<div class="section-content relative">
			<div class="row align-center">
				<div class="col medium-10 small-12 large-10">
					<div class="col-inner text-center">
						<div class="gap-element clearfix" style="display:block; height:auto; padding-top: 45px;"></div>
						<h4 class="uppercase" style="text-align: left;"><em><strong><span style="font-size: 150%;">Nossos Projetos</span></strong></em></h4>
						<div class="text" style="font-size: 0.8rem; color: rgb(0, 0, 0); text-align: left;">
							<p>Conheça alguns dos projetos realizados pela Brooks Construtora. Reformas completas de alto padrão em São Paulo e região.</p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<style>
			#section_projects_title { padding-top: 30px; padding-bottom: 0px; }
		</style>
	</section>

	<!-- Projects Grid -->
	<section class="section" id="section_projects_listing">
		<div class="bg section-bg fill bg-fill bg-loaded"></div>
		<div class="section-content relative">
			<div class="row row-small align-center">
				<div class="col small-12 large-12">
					<div class="col-inner">

						<div class="row">
							<?php if (!empty($projects)): ?>
								<?php foreach ($projects as $project): ?>
								<div class="col medium-6 small-12 large-4">
									<div class="col-inner" style="padding: 0; margin: 0 0 20px 0;">
										<div class="box has-hover box-default box-text-bottom">
											<div class="box-image">
												<a href="/projetos/<?= htmlspecialchars($project['slug']) ?>">
													<div class="image-zoom image-cover" style="padding-top:335px;">
														<img src="<?= htmlspecialchars($project['cover_image'] ?? '/assets/images/wp/2024/11/IMG_2477-1-jpg.webp') ?>" class="attachment- size-" alt="<?= htmlspecialchars($project['title']) ?>" />
													</div>
												</a>
											</div>
											<div class="box-text text-left" style="background-color:rgb(58, 59, 78);padding:5px 5px 5px 15px;">
												<div class="box-text-inner">
													<div class="text" style="font-size: 0.8rem; color: rgb(255,255,255);">
														<h3 class="uppercase" style="margin-bottom: 5px;"><span style="font-size: 90%;"><?= htmlspecialchars($project['title']) ?></span></h3>
														<p><span style="font-size: 100%;"><?= htmlspecialchars($project['short_description'] ?? '') ?></span></p>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<?php endforeach; ?>
							<?php else: ?>
								<!-- Static projects when no dynamic data -->
								<div class="col medium-6 small-12 large-4">
									<div class="col-inner" style="padding: 0; margin: 0 0 20px 0;">
										<div class="box has-hover box-default box-text-bottom">
											<div class="box-image">
												<a href="/projetos/projeto-rocha-andrade">
													<div class="image-zoom image-cover" style="padding-top:335px;">
														<img src="/assets/images/wp/2024/11/IMG_2477-1-jpg.webp" alt="Projeto Rocha Andrade" />
													</div>
												</a>
											</div>
											<div class="box-text text-left" style="background-color:rgb(58, 59, 78);padding:5px 5px 5px 15px;">
												<div class="box-text-inner">
													<div class="text" style="font-size: 0.8rem; color: rgb(255,255,255);">
														<h3 class="uppercase" style="margin-bottom: 5px;"><span style="font-size: 90%;">PROJETO ROCHA ANDRADE</span></h3>
														<p>Reforma completa de apartamento de 300m2.</p>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="col medium-6 small-12 large-4">
									<div class="col-inner" style="padding: 0; margin: 0 0 20px 0;">
										<div class="box has-hover box-default box-text-bottom">
											<div class="box-image">
												<a href="/projetos/projeto-norah-carneiro">
													<div class="image-zoom image-cover" style="padding-top:335px;">
														<img src="/assets/images/wp/2024/11/NorahCarneiro_Av.Prof_.AscendinoReis_RafaelRenzo-51-1-scaled.webp" alt="Projeto Norah Carneiro" />
													</div>
												</a>
											</div>
											<div class="box-text text-left" style="background-color:rgb(58, 59, 78);padding:5px 5px 5px 15px;">
												<div class="box-text-inner">
													<div class="text" style="font-size: 0.8rem; color: rgb(255,255,255);">
														<h3 class="uppercase" style="margin-bottom: 5px;"><span style="font-size: 90%;">PROJETO NORAH CARNEIRO</span></h3>
														<p>Reforma completa de apartamento de 250m2.</p>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="col medium-6 small-12 large-4">
									<div class="col-inner" style="padding: 0; margin: 0 0 20px 0;">
										<div class="box has-hover box-default box-text-bottom">
											<div class="box-image">
												<a href="/projetos/projeto-joia-bergamo">
													<div class="image-zoom image-cover" style="padding-top:335px;">
														<img src="/assets/images/wp/2023/01/GUR1123-HDR-2-scaled.jpg" alt="Projeto Jóia Bergamo" />
													</div>
												</a>
											</div>
											<div class="box-text text-left" style="background-color:rgb(58, 59, 78);padding:5px 5px 5px 15px;">
												<div class="box-text-inner">
													<div class="text" style="font-size: 0.8rem; color: rgb(255,255,255);">
														<h3 class="uppercase" style="margin-bottom: 5px;"><span style="font-size: 90%;">PROJETO JOIA BERGAMO</span></h3>
														<p>Reforma completa de apartamento de 270m2.</p>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="col medium-6 small-12 large-4">
									<div class="col-inner" style="padding: 0; margin: 0 0 20px 0;">
										<div class="box has-hover box-default box-text-bottom">
											<div class="box-image">
												<a href="/projetos/reforma-corporativa-palacio">
													<div class="image-zoom image-cover" style="padding-top:335px;">
														<img src="/assets/images/wp/2024/11/palacio-bandeirantes-jpg.webp" alt="Reforma Corporativa" />
													</div>
												</a>
											</div>
											<div class="box-text text-left" style="background-color:rgb(58, 59, 78);padding:5px 5px 5px 15px;">
												<div class="box-text-inner">
													<div class="text" style="font-size: 0.8rem; color: rgb(255,255,255);">
														<h3 class="uppercase" style="margin-bottom: 5px;"><span style="font-size: 90%;">Reforma Corporativa</span></h3>
														<p>Cafeteria do Palácio dos Bandeirantes</p>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="col medium-6 small-12 large-4">
									<div class="col-inner" style="padding: 0; margin: 0 0 20px 0;">
										<div class="box has-hover box-default box-text-bottom">
											<div class="box-image">
												<a href="/projetos/reforma-escritorio-itaim">
													<div class="image-zoom image-cover" style="padding-top:335px;">
														<img src="/assets/images/wp/2024/11/escritorio-itaim-jpeg.webp" alt="Escritório Itaim" />
													</div>
												</a>
											</div>
											<div class="box-text text-left" style="background-color:rgb(58, 59, 78);padding:5px 5px 5px 15px;">
												<div class="box-text-inner">
													<div class="text" style="font-size: 0.8rem; color: rgb(255,255,255);">
														<h3 class="uppercase" style="margin-bottom: 5px;"><span style="font-size: 90%;">Reforma Corporativa</span></h3>
														<p>Reforma completa de escritório no Itaim Bibi</p>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="col medium-6 small-12 large-4">
									<div class="col-inner" style="padding: 0; margin: 0 0 20px 0;">
										<div class="box has-hover box-default box-text-bottom">
											<div class="box-image">
												<a href="/projetos/mansao-alphaville">
													<div class="image-zoom image-cover" style="padding-top:335px;">
														<img src="/assets/images/wp/2024/11/mansao-alphaville-jpeg.webp" alt="Mansão Alphaville" />
													</div>
												</a>
											</div>
											<div class="box-text text-left" style="background-color:rgb(58, 59, 78);padding:5px 5px 5px 15px;">
												<div class="box-text-inner">
													<div class="text" style="font-size: 0.8rem; color: rgb(255,255,255);">
														<h3 class="uppercase" style="margin-bottom: 5px;"><span style="font-size: 90%;">Reforma de mansão</span></h3>
														<p>Reforma completa de mansão no Alphaville</p>
													</div>
												</div>
											</div>
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
			#section_projects_listing { padding-top: 30px; padding-bottom: 60px; }
		</style>
	</section>

</div><!-- #content -->

<?php include __DIR__ . '/../layouts/footer.php'; ?>
