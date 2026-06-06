<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Brooks Construtora' ?> - Reformas e Construções de Alto Padrão</title>
    <meta name="description" content="A Brooks Construtora é uma empresa especializada em reformas e construções de alto padrão, inserida no mercado de engenharia civil.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="site-header" id="header">
        <div class="header-inner">
            <div class="container">
                <div class="header-content">
                    <a href="/" class="logo">
                        <img src="/assets/images/logo-brooks.svg" alt="Brooks Construtora" class="logo-img">
                    </a>

                    <nav class="main-nav d-none d-lg-flex">
                        <a href="/" class="nav-link">Home</a>
                        <a href="/sobre" class="nav-link">Sobre</a>
                        <a href="/projetos" class="nav-link">Projetos</a>
                        <a href="/revista" class="nav-link">Revista</a>
                        <a href="/contato" class="nav-link">Contato</a>
                    </nav>

                    <div class="header-actions">
                        <?php $whatsapp = $settings['site_whatsapp'] ?? ''; ?>
                        <?php if (!empty($whatsapp)): ?>
                            <a href="https://wa.me/<?= preg_replace('/\D/', '', $whatsapp) ?>" target="_blank" class="btn btn-whatsapp d-none d-md-inline-flex">
                                <i class="bi bi-whatsapp"></i> Fale Conosco
                            </a>
                        <?php endif; ?>
                        <button class="btn btn-menu d-lg-none" id="menu-toggle">
                            <i class="bi bi-list"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div class="mobile-menu" id="mobile-menu">
            <nav class="mobile-nav">
                <a href="/" class="mobile-nav-link">Home</a>
                <a href="/sobre" class="mobile-nav-link">Sobre</a>
                <a href="/projetos" class="mobile-nav-link">Projetos</a>
                <a href="/revista" class="mobile-nav-link">Revista</a>
                <a href="/contato" class="mobile-nav-link">Contato</a>
            </nav>
        </div>
    </header>
