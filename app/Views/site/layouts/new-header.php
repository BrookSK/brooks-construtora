<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Brooks Construtora' ?> — Ecossistema de Inovação para Construção Civil</title>
    <meta name="description" content="<?= $pageDescription ?? 'A Brooks Construtora é um ecossistema completo de inovação para a construção civil de alto padrão. Tecnologia, processos e excelência há mais de 10 anos.' ?>">
    <meta name="keywords" content="construtora alto padrão, reformas São Paulo, construção civil, tecnologia construção, Brooks Construtora">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= $pageTitle ?? 'Brooks Construtora' ?> — Ecossistema de Inovação">
    <meta property="og:description" content="<?= $pageDescription ?? 'Ecossistema completo de inovação para a construção civil de alto padrão.' ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://www.brooksconstrutora.com.br<?= $_SERVER['REQUEST_URI'] ?? '/' ?>">
    <meta property="og:image" content="https://www.brooksconstrutora.com.br/assets/images/wp/2024/11/logo-brooks-1400x396.webp">
    <meta property="og:locale" content="pt_BR">
    
    <!-- Favicon -->
    <link rel="icon" href="/assets/images/wp/2023/01/cropped-favicon-1-32x32.png" sizes="32x32">
    <link rel="icon" href="/assets/images/wp/2023/01/cropped-favicon-1-192x192.png" sizes="192x192">
    <link rel="apple-touch-icon" href="/assets/images/wp/2023/01/cropped-favicon-1-180x180.png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="/assets/js/lucide.min.js"></script>
    
    <!-- Styles -->
    <link rel="stylesheet" href="/assets/css/brooks.css?v=6">
    <link rel="stylesheet" href="/assets/css/brooks-header.css?v=6">
    <link rel="stylesheet" href="/assets/css/brooks-footer.css?v=6">
    <link rel="stylesheet" href="/assets/css/brooks-components.css?v=6">
    
    <!-- Schema.org -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "Brooks Construtora",
        "url": "https://www.brooksconstrutora.com.br",
        "logo": "https://www.brooksconstrutora.com.br/assets/images/wp/2024/11/logo-brooks-1400x396.webp",
        "description": "Ecossistema de inovação para a construção civil de alto padrão",
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "Avenida Brigadeiro Faria Lima, 1811 - Conjunto 910",
            "addressLocality": "São Paulo",
            "addressRegion": "SP",
            "postalCode": "01452-001",
            "addressCountry": "BR"
        },
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+55-11-99339-2659",
            "contactType": "customer service"
        },
        "sameAs": [
            "https://www.instagram.com/brooksconstrutora/"
        ]
    }
    </script>
</head>
<body class="<?= $bodyClass ?? '' ?>">

<!-- Header -->
<header class="site-header" id="site-header" role="banner">
    <div class="header-inner">
        <!-- Logo -->
        <a href="/" class="header-logo" aria-label="Brooks Construtora - Página Inicial">
            <img src="/assets/images/wp/2024/11/logo-brooks-1400x396.webp" alt="Brooks Construtora" width="180" height="51">
        </a>
        
        <!-- Navigation -->
        <ul class="header-nav" role="navigation" aria-label="Navegação principal">
            <li class="header-nav__item">
                <a href="/" class="header-nav__link <?= ($currentPage ?? '') === 'home' ? 'active' : '' ?>">Home</a>
            </li>
            <li class="header-nav__item">
                <a href="/sobre" class="header-nav__link <?= ($currentPage ?? '') === 'sobre' ? 'active' : '' ?>">Sobre</a>
            </li>
            <li class="header-nav__item">
                <a href="/cultura" class="header-nav__link <?= ($currentPage ?? '') === 'cultura' ? 'active' : '' ?>">Cultura</a>
            </li>
            <li class="header-nav__item">
                <a href="/cultura#nucleo" class="header-nav__link">Núcleo</a>
            </li>
            <li class="header-nav__item">
                <a href="/projetos" class="header-nav__link <?= ($currentPage ?? '') === 'projetos' ? 'active' : '' ?>">Projetos</a>
            </li>
            
            <li class="header-nav__item">
                <a href="/vetriks" class="header-nav__link <?= ($currentPage ?? '') === 'vetriks' ? 'active' : '' ?>">Tecnologia Vétriks</a>
            </li>
            <li class="header-nav__item">
                <a href="/forca-estrutural" class="header-nav__link <?= ($currentPage ?? '') === 'forca' ? 'active' : '' ?>">Força Estrutural</a>
            </li>
            <li class="header-nav__item">
                <a href="/academy" class="header-nav__link <?= ($currentPage ?? '') === 'academy' ? 'active' : '' ?>">Brooks Academy</a>
            </li>
            <li class="header-nav__item">
                <a href="/matterport" class="header-nav__link <?= ($currentPage ?? '') === 'matterport' ? 'active' : '' ?>">Matterport</a>
            </li>
            
            <li class="header-nav__item">
                <a href="/revista" class="header-nav__link <?= ($currentPage ?? '') === 'revista' ? 'active' : '' ?>">Revista</a>
            </li>
            <li class="header-nav__item">
                <a href="/contato" class="header-nav__link <?= ($currentPage ?? '') === 'contato' ? 'active' : '' ?>">Contato</a>
            </li>
        </ul>
        
        <!-- CTA -->
        <div class="header-cta">
            <a href="/contato" class="btn">Solicitar Orçamento</a>
        </div>
        
        <!-- Mobile Toggle -->
        <button class="mobile-toggle" id="mobile-toggle" aria-label="Abrir menu" aria-expanded="false">
            <span class="mobile-toggle__bar"></span>
        </button>
    </div>
</header>

<!-- Mobile Navigation -->
<div class="mobile-nav__overlay" id="mobile-overlay"></div>
<nav class="mobile-nav" id="mobile-nav" role="navigation" aria-label="Menu mobile">
    <button class="mobile-nav__close" id="mobile-close" aria-label="Fechar menu">&times;</button>
    <ul class="mobile-nav__list">
        <li><a href="/" class="mobile-nav__link">Home</a></li>
        <li><a href="/sobre" class="mobile-nav__link">Sobre</a></li>
        <li><a href="/cultura" class="mobile-nav__link">Cultura</a></li>
        <li><a href="/cultura#nucleo" class="mobile-nav__link">Núcleo</a></li>
        <li><a href="/projetos" class="mobile-nav__link">Projetos</a></li>
        <li><a href="/vetriks" class="mobile-nav__link">Vetriks</a></li>
        <li><a href="/forca-estrutural" class="mobile-nav__link">Força Estrutural</a></li>
        <li><a href="/academy" class="mobile-nav__link">Brooks Academy</a></li>
        <li><a href="/matterport" class="mobile-nav__link">Matterport</a></li>
        <li><a href="/revista" class="mobile-nav__link">Revista</a></li>
        <li><a href="/contato" class="mobile-nav__link">Contato</a></li>
    </ul>
    <div class="mobile-nav__cta">
        <a href="/contato" class="btn btn--primary" style="width:100%;">Solicitar Orçamento</a>
    </div>
</nav>

<!-- Main Content -->
<main id="main-content" role="main">
