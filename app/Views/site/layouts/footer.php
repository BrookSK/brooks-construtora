	</main>

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

			<!-- Newsletter Form -->
			<div class="newsletter-footer" style="margin-top: 20px;">
				<span class="widget-title">Revista Digital</span><div class="is-divider small"></div>
				<p>Assine gratuitamente nossa revista digital sobre construção, reformas e arquitetura de alto padrão. Receba edições exclusivas diretamente no seu e-mail.</p>
				<form action="/newsletter/subscribe" method="POST" class="newsletter-form">
					<div style="margin-bottom: 8px;">
						<input type="text" name="name" placeholder="Seu nome" class="search-field mb-0" style="width: 100%; padding: 8px 12px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.1); color: #fff; border-radius: 4px;">
					</div>
					<div class="flex-row" style="gap: 8px;">
						<input type="email" name="email" placeholder="Seu melhor e-mail" required class="search-field mb-0" style="flex: 1; padding: 8px 12px; border: 1px solid rgba(255,255,255,0.3); background: rgba(255,255,255,0.1); color: #fff; border-radius: 4px;">
						<button type="submit" class="button secondary" style="padding: 8px 16px;">
							<span>Assinar</span>
						</button>
					</div>
					<p style="font-size: 0.75rem; opacity: 0.6; margin-top: 8px;">Sem spam. Cancele quando quiser.</p>
				</form>
			</div>
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
		<ul class="nav nav-sidebar nav-vertical nav-uppercase" data-tab="1">
			<li class="menu-item"><a href="/">Home</a></li>
			<li class="menu-item"><a href="/sobre">Sobre</a></li>
			<li class="menu-item"><a href="/projetos">Projetos</a></li>
			<li class="menu-item"><a href="/revista">Revista</a></li>
			<li class="menu-item"><a href="/contato">Contato</a></li>
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
