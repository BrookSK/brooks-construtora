<?php $pageTitle = 'Home'; include ROOT_PATH . '/app/Views/site/layouts/header.php'; ?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-slider">
        <div class="hero-slide active" style="background-image: url('/assets/images/hero-1.jpg');">
            <div class="hero-overlay"></div>
            <div class="container">
                <div class="hero-content">
                    <h1>Construção de <span>Alto Padrão</span></h1>
                    <p>Reformas e construções com excelência, qualidade e compromisso com o resultado.</p>
                    <a href="/projetos" class="btn btn-hero">Conheça nossos projetos</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Sobre Resumo -->
<section class="section-about py-5">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="about-image">
                    <img src="/assets/images/about-home.jpg" alt="Brooks Construtora" class="img-fluid rounded">
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-header">
                    <span class="section-tag">Sobre Nós</span>
                    <h2>Excelência em Construção e Reformas</h2>
                </div>
                <p>A Brooks Construtora é uma empresa especializada em reformas e construções de alto padrão. Nossa empresa está inserida no mercado de engenharia civil, oferecendo soluções completas do projeto ao acabamento.</p>
                <p>Com compromisso com a qualidade, sustentabilidade e satisfação do cliente, executamos cada projeto com atenção aos detalhes e padrões elevados de excelência.</p>
                <a href="/sobre" class="btn btn-outline-dark mt-3">Saiba mais</a>
            </div>
        </div>
    </div>
</section>

<!-- Números -->
<section class="section-numbers">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-md-3 col-6">
                <div class="number-item">
                    <span class="number">150+</span>
                    <span class="label">Projetos Realizados</span>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="number-item">
                    <span class="number">15+</span>
                    <span class="label">Anos de Experiência</span>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="number-item">
                    <span class="number">98%</span>
                    <span class="label">Clientes Satisfeitos</span>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="number-item">
                    <span class="number">50+</span>
                    <span class="label">Profissionais</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Serviços -->
<section class="section-services py-5">
    <div class="container">
        <div class="section-header text-center mb-5">
            <span class="section-tag">Nossos Serviços</span>
            <h2>Soluções completas para sua obra</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon"><i class="bi bi-house-door"></i></div>
                    <h4>Reformas Residenciais</h4>
                    <p>Reformas completas de alto padrão para residências, apartamentos e mansões.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon"><i class="bi bi-building"></i></div>
                    <h4>Reformas Corporativas</h4>
                    <p>Escritórios, cafeterias e espaços comerciais com design moderno e funcional.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card">
                    <div class="service-icon"><i class="bi bi-bricks"></i></div>
                    <h4>Construções</h4>
                    <p>Construção do zero com acompanhamento completo do projeto ao acabamento.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Projetos Destaque -->
<section class="section-projects py-5 bg-light">
    <div class="container">
        <div class="section-header text-center mb-5">
            <span class="section-tag">Portfólio</span>
            <h2>Projetos em Destaque</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <a href="/projeto/reforma-completa-de-mansao-no-alphaville" class="project-card">
                    <div class="project-image">
                        <img src="/assets/images/projects/alphaville-thumb.jpg" alt="Reforma Alphaville">
                    </div>
                    <div class="project-info">
                        <h5>Reforma de Mansão no Alphaville</h5>
                        <span class="project-category">Reforma Residencial</span>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="/projeto/reforma-corporativa-cafeteria-do-palacio-dos-bandeirantes" class="project-card">
                    <div class="project-image">
                        <img src="/assets/images/projects/palacio-thumb.jpg" alt="Cafeteria Palácio">
                    </div>
                    <div class="project-info">
                        <h5>Cafeteria do Palácio dos Bandeirantes</h5>
                        <span class="project-category">Reforma Corporativa</span>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="/projeto/projeto-joia-bergamo-2" class="project-card">
                    <div class="project-image">
                        <img src="/assets/images/projects/joia-bergamo-thumb.jpg" alt="Joia Bergamo">
                    </div>
                    <div class="project-info">
                        <h5>Projeto Joia Bergamo</h5>
                        <span class="project-category">Reforma Residencial</span>
                    </div>
                </a>
            </div>
        </div>
        <div class="text-center mt-4">
            <a href="/projetos" class="btn btn-dark">Ver todos os projetos</a>
        </div>
    </div>
</section>

<!-- Revista Destaque -->
<?php if (!empty($magazines)): ?>
<section class="section-magazine py-5">
    <div class="container">
        <div class="section-header text-center mb-5">
            <span class="section-tag">Revista Digital</span>
            <h2>Últimas Edições</h2>
        </div>
        <div class="row g-4">
            <?php foreach ($magazines as $mag): ?>
            <div class="col-md-4">
                <a href="/revista/ver/<?= $mag['id'] ?>" class="magazine-card">
                    <div class="magazine-cover">
                        <?php if ($mag['cover_image']): ?>
                            <img src="<?= $mag['cover_image'] ?>" alt="<?= htmlspecialchars($mag['title']) ?>">
                        <?php else: ?>
                            <div class="magazine-cover-placeholder">
                                <i class="bi bi-journal-richtext"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="magazine-info">
                        <h5><?= htmlspecialchars($mag['title']) ?></h5>
                        <span class="magazine-date"><?= date('d/m/Y', strtotime($mag['published_at'])) ?></span>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="/revista" class="btn btn-outline-dark">Ver todas as edições</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- CTA -->
<section class="section-cta">
    <div class="container text-center">
        <h2>Pronto para transformar seu espaço?</h2>
        <p>Entre em contato conosco e solicite um orçamento sem compromisso.</p>
        <a href="/contato" class="btn btn-hero">Solicitar Orçamento</a>
    </div>
</section>

<?php include ROOT_PATH . '/app/Views/site/layouts/footer.php'; ?>
