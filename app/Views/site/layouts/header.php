<!DOCTYPE html>
<html lang="pt-BR" class="loading-site no-js">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title><?= $pageTitle ?? 'Brooks Construtora' ?> &ndash; Reformas e Construções de Alto Padrão</title>
	<meta name="description" content="A Brooks Construtora é uma empresa especializada em reformas e construções de alto padrão, inserida no mercado de engenharia civil.">
	<script>document.documentElement.className = document.documentElement.className + ' yes-js js_active js'</script>
	<script>(function(html){html.className = html.className.replace(/\bno-js\b/,'js')})(document.documentElement);</script>

	<!-- Favicon -->
	<link rel="icon" href="/assets/images/wp/2023/01/cropped-favicon-1-32x32.png" sizes="32x32" />
	<link rel="icon" href="/assets/images/wp/2023/01/cropped-favicon-1-192x192.png" sizes="192x192" />
	<link rel="apple-touch-icon" href="/assets/images/wp/2023/01/cropped-favicon-1-180x180.png" />

	<!-- Flatsome CSS -->
	<link rel='stylesheet' href='/assets/flatsome/assets/css/flatsome.css' media='all' />
	<link rel='stylesheet' href='/assets/flatsome/assets/css/flatsome-shop.css' media='all' />
	<link rel='stylesheet' href='/assets/css/flatsome-custom.css' media='all' />

	<!-- WhatsApp Chat Plugin CSS -->
	<style>
		:root {
			--qlwapp-scheme-font-family:inherit;
			--qlwapp-scheme-font-size:18px;
			--qlwapp-scheme-icon-size:60px;
			--qlwapp-scheme-icon-font-size:24px;
			--qlwapp-button-animation-name:none;
		}
	</style>

	<!-- Flatsome Icons -->
	<style>
		@font-face {
			font-family: "fl-icons";
			font-display: block;
			src: url(/assets/flatsome/assets/css/icons/fl-icons.woff2) format("woff2"),
				url(/assets/flatsome/assets/css/icons/fl-icons.woff) format("woff"),
				url(/assets/flatsome/assets/css/icons/fl-icons.ttf) format("truetype");
		}
	</style>

	<!-- Google Fonts -->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&family=Dancing+Script:wght@400&display=swap" rel="stylesheet">

	<!-- Custom Theme Styles -->
	<style id="custom-css" type="text/css">
		:root {--primary-color: #446084;}
		.header-main{height: 120px}
		#logo img{max-height: 120px}
		#logo{width:222px;}
		#logo img{padding:6px 0;}
		.header-top{min-height: 30px}
		.transparent .header-main{height: 90px}
		.transparent #logo img{max-height: 90px}
		.has-transparent + .page-title:first-of-type,
		.has-transparent + #main > .page-title,
		.has-transparent + #main > div > .page-title,
		.has-transparent + #main .page-header-wrapper:first-of-type .page-title{padding-top: 90px;}
		.header.show-on-scroll,.stuck .header-main{height:70px!important}
		.stuck #logo img{max-height: 70px!important}
		.header-bg-color {background-color: #3a3b4e}
		.header-bottom {background-color: #f1f1f1}
		.header-main .nav > li > a{line-height: 51px }
		.header-wrapper:not(.stuck) .header-main .header-nav{margin-top: -3px }
		.stuck .header-main .nav > li > a{line-height: 50px }
		@media (max-width: 549px) {
			.header-main{height: 70px}
			#logo img{max-height: 70px}
		}
		@media screen and (max-width: 549px){body{font-size: 100%;}}
		body{font-family: Lato, sans-serif;}
		body {font-weight: 400;font-style: normal;}
		.nav > li > a {font-family: Lato, sans-serif;}
		.mobile-sidebar-levels-2 .nav > li > ul > li > a {font-family: Lato, sans-serif;}
		.nav > li > a,.mobile-sidebar-levels-2 .nav > li > ul > li > a {font-weight: 700;font-style: normal;}
		h1,h2,h3,h4,h5,h6,.heading-font, .off-canvas-center .nav-sidebar.nav-vertical > li > a{font-family: Lato, sans-serif;}
		h1,h2,h3,h4,h5,h6,.heading-font,.banner h1,.banner h2 {font-weight: 300;font-style: normal;}
		.alt-font{font-family: "Dancing Script", sans-serif;}
		.alt-font {font-weight: 400!important;font-style: normal!important;}
		.header:not(.transparent) .header-nav-main.nav > li > a {color: #ffffff;}
		.header:not(.transparent) .header-nav-main.nav > li > a:hover,
		.header:not(.transparent) .header-nav-main.nav > li.active > a,
		.header:not(.transparent) .header-nav-main.nav > li.current > a,
		.header:not(.transparent) .header-nav-main.nav > li > a.active,
		.header:not(.transparent) .header-nav-main.nav > li > a.current{color: #dd3333;}
		.header-nav-main.nav-line-bottom > li > a:before,
		.header-nav-main.nav-line-grow > li > a:before,
		.header-nav-main.nav-line > li > a:before,
		.header-nav-main.nav-box > li > a:hover,
		.header-nav-main.nav-box > li.active > a,
		.header-nav-main.nav-pills > li > a:hover,
		.header-nav-main.nav-pills > li.active > a{color:#FFF!important;background-color: #dd3333;}
		.footer-2{background-color: #3a3b4e}
		.absolute-footer, html{background-color: #192228}
		.nav-vertical-fly-out > li + li {border-top-width: 1px; border-top-style: solid;}
		/* Custom CSS */
		.texto-banners {color: rgb(255,255,255);BACKGROUND-COLOR: #0000006b;PADDING: 5px;margin-bottom: 15px;}
		.absolute-footer.dark {color: hsla(0,0%,100%,.5);padding-bottom: 5px;}
		h1, h2, h3, h4, h5, h6, .heading-font, .banner h1, .banner h2 {font-weight: 400;font-style: normal;}
		.copyright-footer {margin-top: 7px;}
		.bg{opacity: 0; transition: opacity 1s; -webkit-transition: opacity 1s;}
		.bg-loaded{opacity: 1;}
		/* Custom CSS Mobile */
		@media (max-width: 549px){
			.absolute-footer.dark {color: hsla(0,0%,100%,.5);padding-bottom: 85px;}
			#logo img {max-height: 90px;}
			.header-main {height: 90px;}
			.header .flex-row {height: 100%;}
		}
	</style>

	<!-- jQuery -->
	<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>

<body class="home page-template-default page">

<div id="wrapper">

	<header id="header" class="header">
		<div class="header-wrapper">
			<div id="masthead" class="header-main hide-for-sticky">
				<div class="header-inner flex-row container logo-left medium-logo-center" role="navigation">

					<!-- Logo -->
					<div id="logo" class="flex-col logo">
						<a href="/" title="Brooks Construtora - Reformas e Construções de Alto Padrão" rel="home">
							<img width="1020" height="289" src="/assets/images/wp/2024/11/logo-brooks-1400x396.webp" class="header_logo header-logo" alt="Brooks Construtora"/>
							<img width="1020" height="289" src="/assets/images/wp/2024/11/logo-brooks-1400x396.webp" class="header-logo-dark" alt="Brooks Construtora"/>
						</a>
					</div>

					<!-- Mobile Left Elements -->
					<div class="flex-col show-for-medium flex-left">
						<ul class="mobile-nav nav nav-left ">
						</ul>
					</div>

					<!-- Left Elements -->
					<div class="flex-col hide-for-medium flex-left flex-grow">
						<ul class="header-nav header-nav-main nav nav-left nav-size-xlarge nav-spacing-xlarge nav-uppercase">
							<li class="menu-item <?= ($currentPage ?? '') === 'home' ? 'current' : '' ?>"><a href="/" class="nav-top-link">Home</a></li>
							<li class="menu-item <?= ($currentPage ?? '') === 'sobre' ? 'current' : '' ?>"><a href="/sobre" class="nav-top-link">Sobre</a></li>
							<li class="menu-item <?= ($currentPage ?? '') === 'projetos' ? 'current' : '' ?>"><a href="/projetos" class="nav-top-link">Projetos</a></li>
							<li class="menu-item <?= ($currentPage ?? '') === 'revista' ? 'current' : '' ?>"><a href="/revista" class="nav-top-link">Revista</a></li>
							<li class="menu-item <?= ($currentPage ?? '') === 'contato' ? 'current' : '' ?>"><a href="/contato" class="nav-top-link">Contato</a></li>
						</ul>
					</div>

					<!-- Right Elements -->
					<div class="flex-col hide-for-medium flex-right">
						<ul class="header-nav header-nav-main nav nav-right nav-size-xlarge nav-spacing-xlarge nav-uppercase">
							<?php $whatsapp = $settings['site_whatsapp'] ?? '5511993392659'; ?>
							<li class="menu-item menu-item-design-default has-icon-left">
								<a href="https://api.whatsapp.com/send?phone=<?= $whatsapp ?>&text=Oi!" class="nav-top-link" target="_blank">
									<img class="ux-menu-icon" width="35" height="35" src="/assets/images/wp/2023/01/whatsapp.png" alt="WhatsApp" /><?= $settings['site_phone'] ?? '(11) 99339-2659' ?>
								</a>
							</li>
						</ul>
					</div>

					<!-- Mobile Right Elements -->
					<div class="flex-col show-for-medium flex-right">
						<ul class="mobile-nav nav nav-right ">
							<li class="nav-icon has-icon">
								<a href="#main-menu" data-open="#main-menu" data-pos="left" data-bg="main-menu-overlay" data-color="" class="is-small" aria-label="Menu">
									<i class="icon-menu"></i>
								</a>
							</li>
						</ul>
					</div>

				</div>

				<div class="container"><div class="top-divider full-width"></div></div>
			</div>

			<div class="header-bg-container fill"><div class="header-bg-image fill"></div><div class="header-bg-color fill"></div></div>
		</div>
	</header>

	<main id="main" class="">
