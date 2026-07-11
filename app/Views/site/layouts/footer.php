	</main>

<!-- Bloco Newsletter - Assine a Revista -->
<section class="section" id="section_newsletter_cta" style="background-color: #3a3b4e; padding: 40px 0;">
	<div class="container">
		<div class="row align-center">
			<div class="col medium-8 small-12 large-6" style="width: 100%; max-width: 600px; margin: 0 auto;">
				<div class="text-center">
					<?php $prefix = defined('ANTIGO_PREFIX') ? ANTIGO_PREFIX : ''; ?>
					<h3 style="color: #fff; margin-bottom: 8px; font-weight: 400;">Assine a Revista Brooks</h3>
					<p style="color: rgba(255,255,255,0.7); margin-bottom: 20px; font-size: 0.9rem;">Receba edições exclusivas sobre construção sustentável, reformas de alto padrão e tendências de arquitetura.</p>
					<form action="<?= $prefix ?>/newsletter/subscribe" method="POST" class="newsletter-form">
						<div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;">
							<input type="text" name="name" placeholder="Seu nome" style="flex: 1; min-width: 140px; padding: 10px 15px; border: none; border-radius: 4px; font-size: 14px;">
							<input type="email" name="email" placeholder="Seu e-mail" required style="flex: 1; min-width: 180px; padding: 10px 15px; border: none; border-radius: 4px; font-size: 14px;">
							<button type="submit" class="button secondary" style="padding: 10px 22px; white-space: nowrap;">Assinar Grátis</button>
						</div>
						<p style="font-size: 0.75rem; color: rgba(255,255,255,0.5); margin-top: 10px;">Sem spam. Cancele quando quiser.</p>
					</form>
				</div>
			</div>
		</div>
	</div>
</section>

<footer id="footer" class="footer-wrapper">

<!-- FOOTER 2 -->
<div class="footer-widgets footer footer-2 dark">
	<div class="row dark large-columns-2 mb-0">

		<div id="block_widget-2" class="col pb-0 widget block_widget">
			<span class="widget-title">Portfólio</span><div class="is-divider small"></div>
			<div id="text-footer-portfolio" class="text">
				<p style="text-align: left;">Faça o download do nosso portfólio digital</p>
			</div>

			<div class="img has-hover hide-for-medium x md-x lg-x y md-y lg-y" id="image_footer_pdf">
				<a class="" href="/assets/docs/portfolio.pdf" target="_blank" rel="noopener noreferrer">
					<div class="img-inner dark" style="margin:0px 0px 0px 3px;">
						<img width="453" height="497" src="/assets/images/wp/2023/01/icone-pdf-1.png" class="attachment-original size-original" alt="Download PDF" />
					</div>
				</a>
				<style>
					#image_footer_pdf { width: 13%; }
				</style>
			</div>

			<div class="img has-hover show-for-medium hide-for-small x md-x lg-x y md-y lg-y" id="image_footer_pdf_tablet">
				<a class="" href="/assets/docs/portfolio.pdf" target="_blank" rel="noopener noreferrer">
					<div class="img-inner dark" style="margin:0px 0px 0px 0px;">
						<img width="453" height="497" src="/assets/images/wp/2023/01/icone-pdf-1.png" class="attachment-original size-original" alt="Download PDF" />
					</div>
				</a>
				<style>
					#image_footer_pdf_tablet { width: 8%; }
					@media (min-width:850px) { #image_footer_pdf_tablet { width: 17%; } }
				</style>
			</div>

			<div class="img has-hover show-for-small x md-x lg-x y md-y lg-y" id="image_footer_pdf_mobile">
				<a class="" href="/assets/docs/portfolio.pdf" target="_blank" rel="noopener noreferrer">
					<div class="img-inner dark" style="margin:0px 0px 0px 3px;">
						<img width="453" height="497" src="/assets/images/wp/2023/01/icone-pdf-1.png" class="attachment-original size-original" alt="Download PDF" />
					</div>
				</a>
				<style>
					#image_footer_pdf_mobile { width: 19%; }
					@media (min-width:550px) { #image_footer_pdf_mobile { width: 11%; } }
					@media (min-width:850px) { #image_footer_pdf_mobile { width: 17%; } }
				</style>
			</div>

			<div class="gap-element clearfix" style="display:block; height:auto; padding-top: 30px;"></div>

			<div id="text-footer-instagram" class="text">
				<p style="text-align: left;">Acompanhe nossos trabalhos no Instagram</p>
			</div>
			<div class="social-icons follow-icons full-width text-left" style="font-size:165%">
				<a href="https://www.instagram.com/brooksconstrutora/" target="_blank" rel="noopener noreferrer nofollow" data-label="Instagram" class="icon primary button circle instagram tooltip" title="Follow on Instagram" aria-label="Siga no Instagram">
					<i class="icon-instagram"></i>
				</a>
			</div>
		</div>

		<div id="block_widget-3" class="col pb-0 widget block_widget">
			<span class="widget-title">Atendimento</span><div class="is-divider small"></div>
			<?php $whatsapp = !empty($settings['site_whatsapp']) ? $settings['site_whatsapp'] : '5511993392659'; ?>
			<?php $phone = !empty($settings['site_phone']) ? $settings['site_phone'] : '(11) 99339-2659'; ?>
			<?php $email = !empty($settings['site_email']) ? $settings['site_email'] : 'contato@brooksconstrutora.com.br'; ?>
			<p>WhatsApp <a href="https://api.whatsapp.com/send?phone=<?= $whatsapp ?>&amp;text=Oi!" target="_blank"><?= $phone ?></a><br>(Mariana ou Kauê)</p>
			<p>E-mail<br><a href="mailto:<?= $email ?>"><?= $email ?></a></p>
			<p>Avenida Brigadeiro Faria Lima, 1811<br>Conjunto 910 - Jardim Paulistano<br>CEP 1452-001 - São Paulo/SP</p>
			<p>BROOKS CONSTRUTORA<br>CNPJ 24.811.527/0001-64</p>

			<!-- Newsletter - link para página da revista -->
			<!-- <div style="margin-top: 20px;">
				<span class="widget-title">Revista Digital</span><div class="is-divider small"></div>
				<p style="font-size: 0.85rem;">Assine nossa revista gratuita sobre construção e reformas.</p>
				<a href="/revista" class="button secondary" style="padding: 6px 16px; font-size: 0.8rem;">Ver Revista</a>
			</div> -->
		</div>

	</div>
</div>

<div class="absolute-footer dark medium-text-center small-text-center">
	<div class="container clearfix">
		<div class="footer-secondary pull-right">
			<div class="footer-text inline-block small-block">
				<p><a href="/admin/login" style="opacity:0.5;">Área restrita</a> | Desenvolvido por <a href="https://www.brooksconstrutora.com.br" target="_blank" rel="noopener noreferrer">Brooks Construtora</a></p>
			</div>
		</div>
		<div class="footer-primary pull-left">
			<div class="copyright-footer">
				<p>Copyright <?= date('Y') ?> &copy; Brooks Construtora</p>
			</div>
		</div>
	</div>
</div>

<a href="#top" class="back-to-top button icon invert plain fixed bottom z-1 is-outline hide-for-medium circle" id="top-link" aria-label="Voltar ao topo"><i class="icon-angle-up"></i></a>

</footer>

</div><!-- #wrapper -->

<!-- Mobile Menu -->
<div id="main-menu" class="mobile-sidebar no-scrollbar mfp-hide">
	<div class="sidebar-menu no-scrollbar">
		<?php $prefix = defined('ANTIGO_PREFIX') ? ANTIGO_PREFIX : ''; ?>
		<ul class="nav nav-sidebar nav-vertical nav-uppercase" data-tab="1">
			<li class="menu-item"><a href="<?= $prefix ?>/">Home</a></li>
			<li class="menu-item"><a href="<?= $prefix ?>/sobre">Sobre</a></li>
			<li class="menu-item"><a href="<?= $prefix ?>/projetos">Projetos</a></li>
			<li class="menu-item"><a href="<?= $prefix ?>/revista">Revista</a></li>
			<li class="menu-item"><a href="<?= $prefix ?>/contato">Contato</a></li>
			<?php $whatsappFooter = !empty($settings['site_whatsapp']) ? $settings['site_whatsapp'] : '5511993392659'; ?>
			<li class="menu-item has-icon-left">
				<a href="https://api.whatsapp.com/send?phone=<?= $whatsappFooter ?>&amp;text=Oi!" target="_blank">
					<img class="ux-sidebar-menu-icon" width="35" height="35" src="/assets/images/wp/2023/01/whatsapp.png" alt="WhatsApp" /><?= !empty($settings['site_phone']) ? $settings['site_phone'] : '(11) 99339-2659' ?>
				</a>
			</li>
			<li class="html header-social-icons ml-0">
				<div class="social-icons follow-icons">
					<a href="https://www.instagram.com/brooksconstrutora/" target="_blank" rel="noopener noreferrer nofollow" data-label="Instagram" class="icon plain instagram tooltip" title="Siga no Instagram" aria-label="Siga no Instagram"><i class="icon-instagram"></i></a>
				</div>
			</li>
		</ul>
	</div>
</div>

<!-- WhatsApp Float Button -->
<div id="qlwapp" class="qlwapp-free qlwapp-button qlwapp-bottom-right qlwapp-all qlwapp-rounded">
	<div class="qlwapp-container">
		<?php $whatsappBtn = !empty($settings['site_whatsapp']) ? $settings['site_whatsapp'] : '5511993392659'; ?>
		<a class="qlwapp-toggle" data-action="open" data-phone="<?= $whatsappBtn ?>" data-message="Olá, estou acessando o site da Brooks e gostaria de algumas informações." href="https://api.whatsapp.com/send?phone=<?= $whatsappBtn ?>&amp;text=Ol%C3%A1%2C%20estou%20acessando%20o%20site%20da%20Brooks%20e%20gostaria%20de%20algumas%20informa%C3%A7%C3%B5es." target="_blank">
			<i class="qlwapp-icon qlwapp-whatsapp-icon"></i>
			<i class="qlwapp-close" data-action="close">&times;</i>
			<span class="qlwapp-text">Podemos ajudar?</span>
		</a>
	</div>
</div>

<!-- Flatsome JS -->
<script>
var flatsomeVars = {
    "ajaxurl": "/",
    "rtl": "",
    "sticky_height": "70",
    "lightbox": {"close_markup": "<button title=\"Close (Esc)\" type=\"button\" class=\"mfp-close\"><svg xmlns=\"http://www.w3.org/2000/svg\" width=\"28\" height=\"28\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" class=\"feather feather-x\"><line x1=\"18\" y1=\"6\" x2=\"6\" y2=\"18\"></line><line x1=\"6\" y1=\"6\" x2=\"18\" y2=\"18\"></line></svg></button>","close_btn_inside": false},
    "user": {"can_edit_pages": false},
    "i18n": {"mainMenu": "Menu principal","toggleButton": "Toggle"},
    "options": {"cookie_notice_version": "1","swatches_layout": false,"swatches_disable_deselect": false,"swatches_box_reset": false,"swatches_box_reset_extent": false,"search_result_type": false}
};
</script>
<script src="/assets/flatsome/assets/js/chunk.vendors-slider.js"></script>
<script src="/assets/flatsome/assets/js/flatsome.js"></script>
<script>
// Inicializa backgrounds e slider após carregamento
document.addEventListener('DOMContentLoaded', function() {
    // Marca backgrounds como loaded
    document.querySelectorAll('.bg.section-bg').forEach(function(el) {
        el.classList.add('bg-loaded');
    });
    
    // Accordion toggle
    document.querySelectorAll('.accordion-title').forEach(function(title) {
        title.addEventListener('click', function(e) {
            e.preventDefault();
            var item = this.closest('.accordion-item');
            var wasActive = item.classList.contains('active');
            item.parentElement.querySelectorAll('.accordion-item').forEach(function(s) { s.classList.remove('active'); });
            if (!wasActive) item.classList.add('active');
        });
    });

    // Mobile menu
    document.querySelectorAll('[data-open="#main-menu"]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('main-menu').classList.toggle('active');
        });
    });

    // Back to top
    var topLink = document.getElementById('top-link');
    if (topLink) {
        window.addEventListener('scroll', function() {
            topLink.style.display = window.scrollY > 300 ? 'flex' : 'none';
        });
        topLink.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({top: 0, behavior: 'smooth'});
        });
    }

    // Simple slider auto-rotation (fallback if Flickity not loaded)
    document.querySelectorAll('.slider').forEach(function(slider) {
        var slides = slider.querySelectorAll(':scope > .section');
        if (slides.length <= 1) return;
        // Check if Flickity is available
        if (window.Flickity) return; // Flickity handles it
        var current = 0;
        slides.forEach(function(s, i) { s.style.display = i === 0 ? 'block' : 'none'; });
        setInterval(function() {
            slides[current].style.display = 'none';
            current = (current + 1) % slides.length;
            slides[current].style.display = 'block';
        }, 3000);
    });

    // Newsletter AJAX
    document.querySelectorAll('.newsletter-form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var fd = new FormData(this);
            var f = this;
            fetch('/newsletter/subscribe', {method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'}})
            .then(function(r){return r.json()})
            .then(function(d){ alert(d.message || 'OK'); if(d.success) f.reset(); })
            .catch(function(){ alert('Erro. Tente novamente.'); });
        });
    });
});
</script>
<!-- WhatsApp Chat CSS (inline) -->
<style>
	#qlwapp .qlwapp-toggle {
		display: flex;
		align-items: center;
		gap: 8px;
		background-color: #25d366;
		color: #fff;
		padding: 10px 20px;
		border-radius: 50px;
		text-decoration: none;
		font-size: 14px;
		box-shadow: 0 2px 10px rgba(0,0,0,0.2);
		transition: all 0.3s ease;
	}
	#qlwapp .qlwapp-toggle:hover {
		transform: scale(1.05);
		box-shadow: 0 4px 15px rgba(0,0,0,0.3);
	}
	#qlwapp .qlwapp-whatsapp-icon:before {
		content: "\f232";
		font-family: "fl-icons";
	}
	#qlwapp .qlwapp-close { display: none; }
	#qlwapp.qlwapp-bottom-right {
		position: fixed;
		bottom: 45px;
		right: 20px;
		z-index: 9999;
	}
	@media (max-width: 549px) {
		#qlwapp.qlwapp-bottom-right { bottom: 0px; }
		#qlwapp .qlwapp-text { display: none; }
	}
</style>

</body>
</html>
