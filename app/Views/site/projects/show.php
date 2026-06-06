<?php include __DIR__ . '/../layouts/header.php'; ?>

<div id="content" role="main" class="content-area">

	<!-- Page Title -->
	<section class="section" id="section_project_title">
		<div class="bg section-bg fill bg-fill bg-loaded"></div>
		<div class="section-content relative">
			<div class="row align-center">
				<div class="col medium-10 small-12 large-10">
					<div class="col-inner text-center">
						<div class="gap-element clearfix" style="display:block; height:auto; padding-top: 45px;"></div>
						<h4 class="uppercase" style="text-align: left;">
							<em><strong><span style="font-size: 150%;"><?= htmlspecialchars($project['title'] ?? 'Projeto') ?></span></strong></em>
						</h4>
						<?php if (!empty($project['short_description'])): ?>
						<div class="text" style="font-size: 0.9rem; color: rgb(0, 0, 0); text-align: left;">
							<p><?= htmlspecialchars($project['short_description']) ?></p>
						</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<style>
			#section_project_title { padding-top: 30px; padding-bottom: 0px; }
		</style>
	</section>

	<!-- Project Cover Image -->
	<?php if (!empty($project['cover_image'])): ?>
	<section class="section" id="section_project_cover">
		<div class="bg section-bg fill bg-fill"></div>
		<div class="section-content relative">
			<div class="row align-center">
				<div class="col small-12 large-12">
					<div class="col-inner">
						<div class="image-zoom image-cover" style="padding-top: 50%;">
							<img src="<?= htmlspecialchars($project['cover_image']) ?>" alt="<?= htmlspecialchars($project['title'] ?? '') ?>" style="width: 100%; object-fit: cover;" />
						</div>
					</div>
				</div>
			</div>
		</div>
		<style>
			#section_project_cover { padding-top: 30px; padding-bottom: 0px; }
		</style>
	</section>
	<?php endif; ?>

	<!-- Project Description -->
	<section class="section" id="section_project_description">
		<div class="bg section-bg fill bg-fill bg-loaded"></div>
		<div class="section-content relative">
			<div class="row align-center">
				<div class="col medium-10 small-12 large-10">
					<div class="col-inner">
						<div class="text" style="font-size: 0.9rem; color: rgb(0, 0, 0);">
							<?= $project['description'] ?? '' ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<style>
			#section_project_description { padding-top: 30px; padding-bottom: 30px; }
		</style>
	</section>

	<!-- Project Gallery -->
	<?php if (!empty($images)): ?>
	<section class="section dark" id="section_project_gallery">
		<div class="bg section-bg fill bg-fill bg-loaded"></div>
		<div class="section-content relative">
			<div class="container section-title-container">
				<h3 class="section-title section-title-normal"><b></b><span class="section-title-main" style="color:rgb(0, 0, 0);">Galeria do Projeto</span><b></b></h3>
			</div>
			<div class="row large-columns-3 medium-columns-2 small-columns-1">
				<?php foreach ($images as $image): ?>
				<div class="gallery-col col">
					<div class="col-inner">
						<a class="image-lightbox lightbox-gallery" href="<?= htmlspecialchars($image['image_path'] ?? $image['url'] ?? '') ?>" title="<?= htmlspecialchars($image['title'] ?? '') ?>">
							<div class="box has-hover gallery-box box-overlay dark">
								<div class="box-image">
									<img src="<?= htmlspecialchars($image['image_path'] ?? $image['url'] ?? '') ?>" alt="<?= htmlspecialchars($image['title'] ?? '') ?>" />
									<div class="overlay fill" style="background-color: rgba(0,0,0,.15)"></div>
								</div>
							</div>
						</a>
					</div>
				</div>
				<?php endforeach; ?>
			</div>
		</div>
		<style>
			#section_project_gallery { padding-top: 30px; padding-bottom: 30px; background-color: rgb(255,255,255); }
		</style>
	</section>
	<?php endif; ?>

	<!-- Back to projects -->
	<section class="section" id="section_project_back">
		<div class="section-content relative">
			<div class="row align-center">
				<div class="col medium-10 small-12 large-10">
					<div class="col-inner text-center">
						<a href="/projetos" class="button secondary" style="padding: 10px 30px;">
							<span>&larr; Voltar para Projetos</span>
						</a>
					</div>
				</div>
			</div>
		</div>
		<style>
			#section_project_back { padding-top: 30px; padding-bottom: 60px; }
		</style>
	</section>

</div><!-- #content -->

<?php include __DIR__ . '/../layouts/footer.php'; ?>
