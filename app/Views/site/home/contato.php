<?php $pageTitle = 'Contato'; $currentPage = 'contato'; include ROOT_PATH . '/app/Views/site/layouts/header.php'; ?>

<div id="content" role="main" class="content-area">

<!-- Banner -->
<section class="section" id="section_contato_banner">
	<div class="bg section-bg fill bg-fill bg-loaded"></div>
	<div class="section-content relative">
		<div class="row align-center">
			<div class="col medium-6 small-12 large-6">
				<div class="col-inner text-center dark" style="padding: 150px 0px 50px 0px;">
					<div class="text texto-banners">
						<h1 style="font-size: 150%;">CONTATO</h1>
						<p><span style="font-size: 120%;">Entre em contato conosco</span></p>
					</div>
				</div>
			</div>
		</div>
	</div>
	<style>
		#section_contato_banner { padding-top: 30px; padding-bottom: 30px; }
		#section_contato_banner .section-bg.bg-loaded { background-image: url(/assets/images/wp/2023/01/fundo-3.jpg); }
	</style>
</section>

<!-- Formulário de Contato -->
<section class="section" id="section_contato_form">
	<div class="bg section-bg fill bg-fill bg-loaded"></div>
	<div class="section-content relative">
		<div class="row">
			<div class="col medium-7 small-12 large-7">
				<div class="col-inner" style="padding: 40px 30px;">
					<h4 class="uppercase" style="margin-bottom: 20px;"><em><strong>Envie sua mensagem</strong></em></h4>

					<?php if (!empty($flash)): ?>
						<div style="padding: 12px 20px; margin-bottom: 20px; border-radius: 4px; background: <?= $flash['type'] === 'success' ? '#d4edda' : '#f8d7da' ?>; color: <?= $flash['type'] === 'success' ? '#155724' : '#721c24' ?>;">
							<?= htmlspecialchars($flash['message']) ?>
						</div>
					<?php endif; ?>

					<form method="POST" action="/contato/enviar">
						<div class="row">
							<div class="col medium-6 small-12 large-6">
								<div class="col-inner" style="margin-bottom: 15px;">
									<input type="text" name="name" placeholder="Seu nome *" required style="width:100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
								</div>
							</div>
							<div class="col medium-6 small-12 large-6">
								<div class="col-inner" style="margin-bottom: 15px;">
									<input type="email" name="email" placeholder="Seu e-mail *" required style="width:100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
								</div>
							</div>
							<div class="col small-12 large-12">
								<div class="col-inner" style="margin-bottom: 15px;">
									<input type="text" name="phone" placeholder="Telefone / WhatsApp" style="width:100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
								</div>
							</div>
							<div class="col small-12 large-12">
								<div class="col-inner" style="margin-bottom: 15px;">
									<textarea name="message" placeholder="Sua mensagem *" required rows="6" style="width:100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; resize: vertical;"></textarea>
								</div>
							</div>
							<div class="col small-12 large-12">
								<div class="col-inner">
									<button type="submit" class="button secondary" style="padding: 12px 30px;">Enviar Mensagem</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>

			<div class="col medium-5 small-12 large-5">
				<div class="col-inner" style="padding: 40px 30px; background: #f9f9f9; border-radius: 8px;">
					<h4 class="uppercase" style="margin-bottom: 20px;"><em><strong>Informações</strong></em></h4>

					<?php $whatsappContato = $settings['site_whatsapp'] ?? '5511993392659'; ?>

					<p><strong>WhatsApp</strong><br>
					<a href="https://api.whatsapp.com/send?phone=<?= $whatsappContato ?>&text=Oi!" target="_blank"><?= $settings['site_phone'] ?? '(11) 99339-2659' ?></a><br>
					(Mariana ou Kauê)</p>

					<p><strong>E-mail</strong><br>
					<a href="mailto:<?= $settings['site_email'] ?? 'contato@brooksconstrutora.com.br' ?>"><?= $settings['site_email'] ?? 'contato@brooksconstrutora.com.br' ?></a></p>

					<p><strong>Endereço</strong><br>
					Avenida Brigadeiro Faria Lima, 1811<br>
					Conjunto 910 - Jardim Paulistano<br>
					CEP 01452-001 - São Paulo/SP</p>

					<p><strong>Horário de Atendimento</strong><br>
					Segunda a Sexta: 8h às 18h</p>

					<div style="margin-top: 20px;">
						<a href="https://www.instagram.com/brooksconstrutora/" target="_blank" class="icon primary button circle instagram" style="margin-right: 5px;" aria-label="Instagram">
							<i class="icon-instagram"></i>
						</a>
					</div>
				</div>
			</div>
		</div>
	</div>
	<style>
		#section_contato_form { padding-top: 30px; padding-bottom: 30px; }
	</style>
</section>

</div><!-- #content -->

<?php include ROOT_PATH . '/app/Views/site/layouts/footer.php'; ?>
