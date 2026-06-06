<?php include __DIR__ . '/../layouts/header.php'; ?>

<div id="content" role="main" class="content-area">

	<!-- Page Title -->
	<section class="section" id="section_contato_title">
		<div class="bg section-bg fill bg-fill bg-loaded"></div>
		<div class="section-content relative">
			<div class="row align-center">
				<div class="col medium-10 small-12 large-10">
					<div class="col-inner text-center">
						<div class="gap-element clearfix" style="display:block; height:auto; padding-top: 45px;"></div>
						<h4 class="uppercase" style="text-align: left;"><em><strong><span style="font-size: 150%;">Contato</span></strong></em></h4>
					</div>
				</div>
			</div>
		</div>
		<style>
			#section_contato_title { padding-top: 30px; padding-bottom: 0px; }
		</style>
	</section>

	<!-- Flash Messages -->
	<?php if (!empty($flash)): ?>
	<section class="section" style="padding: 0;">
		<div class="section-content relative">
			<div class="row align-center">
				<div class="col medium-10 small-12 large-10">
					<div class="col-inner">
						<?php if (!empty($flash['success'])): ?>
							<div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
								<?= htmlspecialchars($flash['success']) ?>
							</div>
						<?php endif; ?>
						<?php if (!empty($flash['error'])): ?>
							<div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
								<?= htmlspecialchars($flash['error']) ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<!-- Contact Content -->
	<section class="section" id="section_contato_content">
		<div class="bg section-bg fill bg-fill bg-loaded"></div>
		<div class="section-content relative">
			<div class="row align-center">
				<div class="col medium-10 small-12 large-10">
					<div class="col-inner">

						<div class="row">
							<!-- Contact Info -->
							<div class="col medium-6 small-12 large-5">
								<div class="col-inner">
									<div class="text" style="font-size: 0.9rem; color: rgb(0, 0, 0);">
										<h3>Fale Conosco</h3>
										<p>&nbsp;</p>
										<?php $whatsappContact = $settings['site_whatsapp'] ?? '5511993392659'; ?>
										<p><strong>WhatsApp:</strong><br>
										<a href="https://api.whatsapp.com/send?phone=<?= $whatsappContact ?>&text=Oi!" target="_blank" style="color: #446084;">
											<?= $settings['site_phone'] ?? '(11) 99339-2659' ?>
										</a><br>(Mariana ou Kauê)</p>
										<p>&nbsp;</p>
										<p><strong>E-mail:</strong><br>
										<a href="mailto:<?= $settings['site_email'] ?? 'contato@brooksconstrutora.com.br' ?>" style="color: #446084;">
											<?= $settings['site_email'] ?? 'contato@brooksconstrutora.com.br' ?>
										</a></p>
										<p>&nbsp;</p>
										<p><strong>Endereço:</strong><br>
										Avenida Brigadeiro Faria Lima, 1811<br>
										Conjunto 910 - Jardim Paulistano<br>
										CEP 1452-001 - São Paulo/SP</p>
										<p>&nbsp;</p>
										<p><strong>BROOKS CONSTRUTORA</strong><br>
										CNPJ 24.811.527/0001-64</p>
										<p>&nbsp;</p>
										<div class="social-icons follow-icons" style="font-size:165%">
											<a href="https://www.instagram.com/brooksconstrutora/" target="_blank" rel="noopener noreferrer nofollow" class="icon primary button circle instagram tooltip" title="Siga no Instagram" aria-label="Siga no Instagram"><i class="icon-instagram"></i></a>
										</div>
									</div>
								</div>
							</div>

							<!-- Contact Form -->
							<div class="col medium-6 small-12 large-7">
								<div class="col-inner">
									<h3>Envie sua mensagem</h3>
									<p>&nbsp;</p>
									<form action="/contato/enviar" method="POST" class="contact-form">
										<div style="margin-bottom: 15px;">
											<label for="contact-name" style="display: block; margin-bottom: 5px; font-weight: 700; font-size: 0.85rem;">Nome *</label>
											<input type="text" id="contact-name" name="name" required
												style="width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; font-family: Lato, sans-serif;">
										</div>
										<div style="margin-bottom: 15px;">
											<label for="contact-email" style="display: block; margin-bottom: 5px; font-weight: 700; font-size: 0.85rem;">E-mail *</label>
											<input type="email" id="contact-email" name="email" required
												style="width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; font-family: Lato, sans-serif;">
										</div>
										<div style="margin-bottom: 15px;">
											<label for="contact-phone" style="display: block; margin-bottom: 5px; font-weight: 700; font-size: 0.85rem;">Telefone</label>
											<input type="tel" id="contact-phone" name="phone"
												style="width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; font-family: Lato, sans-serif;">
										</div>
										<div style="margin-bottom: 15px;">
											<label for="contact-message" style="display: block; margin-bottom: 5px; font-weight: 700; font-size: 0.85rem;">Mensagem *</label>
											<textarea id="contact-message" name="message" rows="6" required
												style="width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; font-family: Lato, sans-serif; resize: vertical;"></textarea>
										</div>
										<div>
											<button type="submit" class="button secondary" style="padding: 10px 30px;">
												<span>Enviar mensagem</span>
											</button>
										</div>
									</form>
								</div>
							</div>
						</div>

					</div>
				</div>
			</div>
		</div>
		<style>
			#section_contato_content { padding-top: 30px; padding-bottom: 60px; }
		</style>
	</section>

</div><!-- #content -->

<?php include __DIR__ . '/../layouts/footer.php'; ?>
