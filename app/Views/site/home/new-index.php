<?php
$pageTitle = 'Brooks Construtora';
$pageDescription = 'A Brooks Construtora é um ecossistema completo de inovação para a construção civil de alto padrão. Tecnologia, processos e excelência há mais de 10 anos em São Paulo.';
$currentPage = 'home';
$bodyClass = 'page-home';
include ROOT_PATH . '/app/Views/site/layouts/new-header.php';
?>

<!-- ===== HERO ===== -->
<section class="hero" id="hero">
    <div class="hero__bg">
        <video autoplay muted loop playsinline poster="/assets/images/projects/katty-kaitazoff/katty-01.png">
            <source src="/assets/images/projects/katty-kaitazoff/katty-video01.mp4" type="video/mp4">
        </video>
    </div>
    <div class="hero__overlay"></div>
    
    <div class="container hero__content">
        <div class="hero__badge">
            <span class="hero__badge__dot"></span>
            Mais de 10 anos de excelência
        </div>
        <h1 class="hero__title">
            Muito além da<br><span>construção civil.</span>
        </h1>
        <p class="hero__subtitle">
            Um ecossistema completo de inovação, tecnologia e processos para a construção de alto padrão. Confiança que se constrói com pessoas, cultura e propósito.
        </p>
        <div class="hero__actions">
            <a href="/projetos" class="btn btn--primary btn--lg">Conheça nossos projetos</a>
            <a href="/contato" class="btn btn--secondary btn--lg">Solicitar orçamento</a>
        </div>
    </div>
    
    <div class="hero__scroll">
        <span>Scroll</span>
        <i data-lucide="chevron-down" style="width:16px;height:16px;"></i>
    </div>
</section>

<!-- ===== ABOUT BROOKS ===== -->
<section class="section" id="sobre-brooks">
    <div class="container">
        <div class="grid grid--2" style="align-items: center; gap: var(--space-4xl);">
            <div class="reveal-left">
                <span class="label">Quem Somos</span>
                <h2 class="headline-section">Uma empresa forjada na excelência e na cultura forte.</h2>
                <p style="font-size: var(--text-lg); color: var(--brooks-gray-600); line-height: 1.8; margin-bottom: var(--space-lg);">
                    A Brooks Construtora atua há mais de 10 anos no mercado de construção civil de alto padrão em São Paulo. Crescemos através da qualidade, da organização, dos processos e, acima de tudo, das pessoas.
                </p>
                <p style="color: var(--brooks-gray-500); line-height: 1.8; margin-bottom: var(--space-xl);">
                    Somos uma das únicas construtoras com setor de tecnologia próprio. Cada erro do campo é corrigido com tecnologia, treinamento e processo. A Brooks não vende apenas obras, vende confiança, previsibilidade e inovação.
                </p>
                <a href="/sobre" class="btn btn--outline">Conheça nossa história</a>
            </div>
            <div class="reveal-right">
                <img src="/assets/images/wp/2024/11/IMG_2477-1-jpg.webp" alt="Projeto Brooks Construtora - Alto Padrão" style="border-radius: var(--radius-xl); box-shadow: var(--shadow-2xl); width: 100%;" loading="lazy">
            </div>
        </div>
    </div>
</section>

<!-- ===== NUMBERS ===== -->
<section class="section section--dark" id="numeros">
    <div class="container">
        <div class="counter-grid reveal">
            <div class="counter-item">
                <div class="counter-item__number" data-counter data-target="10" data-suffix="+">0</div>
                <div class="counter-item__label">Anos de mercado</div>
            </div>
            <div class="counter-item">
                <div class="counter-item__number" data-counter data-target="200" data-suffix="+">0</div>
                <div class="counter-item__label">Obras entregues</div>
            </div>
            <div class="counter-item">
                <div class="counter-item__number" data-counter data-target="5" data-suffix=".0">0</div>
                <div class="counter-item__label">Estrelas no Google</div>
            </div>
            <div class="counter-item">
                <div class="counter-item__number" data-counter data-target="50" data-suffix="+">0</div>
                <div class="counter-item__label">Arquitetos parceiros</div>
            </div>
            <div class="counter-item">
                <div class="counter-item__number" data-counter data-target="0" data-suffix="" data-prefix="">Zero</div>
                <div class="counter-item__label">Reclamações no Reclame Aqui</div>
            </div>
        </div>
    </div>
</section>

<!-- ===== DIFFERENTIALS ===== -->
<section class="section section--gray" id="diferenciais">
    <div class="container">
        <div class="section-header section-header--centered reveal">
            <span class="label">Diferenciais</span>
            <h2 class="headline-section">Por que a Brooks é diferente</h2>
            <p class="subtitle subtitle--centered">Não somos uma construtora comum. Somos um ecossistema onde tecnologia, processos e pessoas trabalham integrados.</p>
        </div>
        
        <div class="grid grid--3 reveal">
            <div class="card delay-1">
                <div class="card__icon"><i data-lucide="cpu"></i></div>
                <h3 class="card__title">Tecnologia Própria</h3>
                <p class="card__text">Setor de tecnologia interno com CTO dedicado. Cada problema do campo gera uma solução tecnológica permanente.</p>
            </div>
            <div class="card delay-2">
                <div class="card__icon"><i data-lucide="shield-check"></i></div>
                <h3 class="card__title">Qualidade Inegociável</h3>
                <p class="card__text">Processos definidos, equipes especializadas e engenheiro calculista em cada obra. Zero reclamações no Reclame Aqui.</p>
            </div>
            <div class="card delay-3">
                <div class="card__icon"><i data-lucide="users"></i></div>
                <h3 class="card__title">Equipe Multidisciplinar</h3>
                <p class="card__text">Financeiro, CFO, Tecnologia, Jurídico, Operacional, Qualidade e Planejamento. Tudo funcionando integrado.</p>
            </div>
            <div class="card delay-4">
                <div class="card__icon"><i data-lucide="leaf"></i></div>
                <h3 class="card__title">Sustentabilidade</h3>
                <p class="card__text">Membro do GBC Brasil (Green Building Council). Construção consciente e responsável com o meio ambiente.</p>
            </div>
            <div class="card delay-5">
                <div class="card__icon"><i data-lucide="target"></i></div>
                <h3 class="card__title">Processos Definidos</h3>
                <p class="card__text">O erro pode acontecer, mas jamais se repete. Processos e tecnologia garantem correções rápidas e definitivas.</p>
            </div>
            <div class="card delay-6">
                <div class="card__icon"><i data-lucide="heart-handshake"></i></div>
                <h3 class="card__title">Arquitetos Parceiros</h3>
                <p class="card__text">Indicados pelos melhores escritórios de arquitetura de São Paulo. Referência no mercado de alto padrão.</p>
            </div>
        </div>
    </div>
</section>

<!-- ===== PROJECTS PREVIEW ===== -->
<section class="section" id="projetos-preview">
    <div class="container">
        <div class="section-header reveal">
            <span class="label">Projetos</span>
            <h2 class="headline-section">Transformamos espaços em experiências.</h2>
            <p class="subtitle">Reformas completas de alto padrão em imóveis desocupados. Residenciais, corporativos e outros.</p>
        </div>
        
        <div class="grid grid--2 reveal" style="gap: var(--space-lg);">
            <a href="/projeto/projeto-rocha-andrade" class="project-card">
                <img src="/assets/images/wp/2024/11/IMG_2477-1-jpg.webp" alt="Projeto Rocha Andrade" class="project-card__image" loading="lazy">
                <div class="project-card__overlay">
                    <div class="project-card__content">
                        <h3 class="project-card__title">Projeto Rocha Andrade</h3>
                        <p class="project-card__subtitle">Execução de Engenharia · Brooks Construtora · Residencial</p>
                        <p class="project-card__subtitle" style="opacity:0.8; margin-top:2px;">São Paulo, SP</p>
                        <span class="project-card__arrow">Ver projeto <i data-lucide="arrow-right" style="width:14px;height:14px;"></i></span>
                    </div>
                </div>
            </a>
            <a href="/projeto/projeto-norah-carneiro" class="project-card">
                <img src="/assets/images/wp/2024/11/NorahCarneiro_Av.Prof_.AscendinoReis_RafaelRenzo-51-1-scaled.webp" alt="Projeto Norah Carneiro" class="project-card__image" loading="lazy">
                <div class="project-card__overlay">
                    <div class="project-card__content">
                        <h3 class="project-card__title">Projeto Norah Carneiro</h3>
                        <p class="project-card__subtitle">Execução de Engenharia · Brooks Construtora · Residencial</p>
                        <p class="project-card__subtitle" style="opacity:0.8; margin-top:2px;">Av. Prof. Ascendino Reis, São Paulo</p>
                        <span class="project-card__arrow">Ver projeto <i data-lucide="arrow-right" style="width:14px;height:14px;"></i></span>
                    </div>
                </div>
            </a>
            <a href="/projeto/projeto-joia-bergamo-rsvp" class="project-card">
                <img src="/assets/images/wp/2024/11/bergamo-jpg.webp" alt="Projeto Jóia Bergamo" class="project-card__image" loading="lazy">
                <div class="project-card__overlay">
                    <div class="project-card__content">
                        <h3 class="project-card__title">Projeto Jóia Bergamo</h3>
                        <p class="project-card__subtitle">Execução de Engenharia · Brooks Construtora · Residencial</p>
                        <p class="project-card__subtitle" style="opacity:0.8; margin-top:2px;">São Paulo, SP</p>
                        <span class="project-card__arrow">Ver projeto <i data-lucide="arrow-right" style="width:14px;height:14px;"></i></span>
                    </div>
                </div>
            </a>
            <a href="/projeto/construcao-corporativa-cafeteria-do-palacio-dos-bandeirantes" class="project-card">
                <img src="/assets/images/wp/2024/11/palacio-bandeirantes-jpg.webp" alt="Palácio dos Bandeirantes" class="project-card__image" loading="lazy">
                <div class="project-card__overlay">
                    <div class="project-card__content">
                        <h3 class="project-card__title">Palácio dos Bandeirantes</h3>
                        <p class="project-card__subtitle">Execução de Engenharia · Brooks Construtora · Corporativo</p>
                        <p class="project-card__subtitle" style="opacity:0.8; margin-top:2px;">Morumbi, São Paulo</p>
                        <span class="project-card__arrow">Ver projeto <i data-lucide="arrow-right" style="width:14px;height:14px;"></i></span>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="text-center reveal" style="margin-top: var(--space-3xl);">
            <a href="/projetos" class="btn btn--outline">Ver todos os projetos</a>
        </div>
    </div>
</section>

<!-- ===== ECOSYSTEM ===== -->
<section class="section section--dark" id="ecossistema">
    <div class="container">
        <div class="section-header section-header--centered reveal">
            <span class="label" style="color: var(--brooks-blue-accent);">Ecossistema</span>
            <h2 class="headline-section" style="color: white;">Muito além de uma construtora.</h2>
            <p class="subtitle subtitle--centered" style="color: rgba(255,255,255,0.5);">A Brooks representa hoje um ecossistema completo, formado por empresas que compartilham o mesmo propósito: inovar na construção civil.</p>
        </div>
        
        <div class="reveal" style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-lg);">
            <!-- Vetriks -->
            <a href="/vetriks" style="display: block; padding: var(--space-2xl); border-radius: var(--radius-xl); background: linear-gradient(135deg, rgba(52, 152, 219, 0.08), rgba(52, 152, 219, 0.02)); border: 1px solid rgba(52, 152, 219, 0.15); transition: all 0.3s; text-decoration: none;">
                <div style="display: flex; align-items: center; gap: var(--space-md); margin-bottom: var(--space-lg);">
                    <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(52, 152, 219, 0.15); display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="cpu" style="width:24px;height:24px;color:#3498db;"></i>
                    </div>
                    <div>
                        <span style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: #3498db;">Tecnologia</span>
                        <h3 style="font-size: var(--text-xl); font-weight: 700; color: white; margin: 0;">Tecnologia Vetriks</h3>
                    </div>
                </div>
                <p style="color: rgba(255,255,255,0.6); line-height: 1.7; font-size: var(--text-sm); margin-bottom: var(--space-lg);">Sistema de gestão de obra completo, vertical e integrado. Criado no campo, nas dores reais da construção. Com IA e interface moderna.</p>
                <span style="font-size: var(--text-sm); font-weight: 600; color: #3498db; display: flex; align-items: center; gap: 6px;">Conhecer a Vetriks <i data-lucide="arrow-right" style="width:14px;height:14px;"></i></span>
            </a>

            <!-- Força Estrutural -->
            <a href="/forca-estrutural" style="display: block; padding: var(--space-2xl); border-radius: var(--radius-xl); background: linear-gradient(135deg, rgba(46, 134, 171, 0.08), rgba(46, 134, 171, 0.02)); border: 1px solid rgba(46, 134, 171, 0.15); transition: all 0.3s; text-decoration: none;">
                <div style="display: flex; align-items: center; gap: var(--space-md); margin-bottom: var(--space-lg);">
                    <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(46, 134, 171, 0.15); display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="users" style="width:24px;height:24px;color:#2e86ab;"></i>
                    </div>
                    <div>
                        <span style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: #2e86ab;">Comunidade</span>
                        <h3 style="font-size: var(--text-xl); font-weight: 700; color: white; margin: 0;">Força Estrutural</h3>
                    </div>
                </div>
                <p style="color: rgba(255,255,255,0.6); line-height: 1.7; font-size: var(--text-sm); margin-bottom: var(--space-lg);">Grupo de empresários da construção civil. Networking, eventos, conhecimento e parcerias com os melhores do mercado.</p>
                <span style="font-size: var(--text-sm); font-weight: 600; color: #2e86ab; display: flex; align-items: center; gap: 6px;">Conhecer o grupo <i data-lucide="arrow-right" style="width:14px;height:14px;"></i></span>
            </a>

            <!-- Brooks Academy -->
            <a href="/academy" style="display: block; padding: var(--space-2xl); border-radius: var(--radius-xl); background: linear-gradient(135deg, rgba(243, 156, 18, 0.08), rgba(243, 156, 18, 0.02)); border: 1px solid rgba(243, 156, 18, 0.15); transition: all 0.3s; text-decoration: none;">
                <div style="display: flex; align-items: center; gap: var(--space-md); margin-bottom: var(--space-lg);">
                    <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(243, 156, 18, 0.15); display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="graduation-cap" style="width:24px;height:24px;color:#f39c12;"></i>
                    </div>
                    <div>
                        <span style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: #f39c12;">Educação</span>
                        <h3 style="font-size: var(--text-xl); font-weight: 700; color: white; margin: 0;">Brooks Academy</h3>
                    </div>
                </div>
                <p style="color: rgba(255,255,255,0.6); line-height: 1.7; font-size: var(--text-sm); margin-bottom: var(--space-lg);">Escola profissionalizante nos canteiros de obra. Plano de carreira, capacitação e bolsas de engenharia civil EAD.</p>
                <span style="font-size: var(--text-sm); font-weight: 600; color: #f39c12; display: flex; align-items: center; gap: 6px;">Conhecer a Academy <i data-lucide="arrow-right" style="width:14px;height:14px;"></i></span>
            </a>

            <!-- Matterport -->
            <a href="/matterport" style="display: block; padding: var(--space-2xl); border-radius: var(--radius-xl); background: linear-gradient(135deg, rgba(123, 97, 255, 0.08), rgba(123, 97, 255, 0.02)); border: 1px solid rgba(123, 97, 255, 0.15); transition: all 0.3s; text-decoration: none;">
                <div style="display: flex; align-items: center; gap: var(--space-md); margin-bottom: var(--space-lg);">
                    <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: rgba(123, 97, 255, 0.15); display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="view" style="width:24px;height:24px;color:#7b61ff;"></i>
                    </div>
                    <div>
                        <span style="font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: #7b61ff;">Tour Virtual 3D</span>
                        <h3 style="font-size: var(--text-xl); font-weight: 700; color: white; margin: 0;">Matterport</h3>
                    </div>
                </div>
                <p style="color: rgba(255,255,255,0.6); line-height: 1.7; font-size: var(--text-sm); margin-bottom: var(--space-lg);">Gêmeos digitais e tours virtuais 3D de todas as nossas obras. O cliente acompanha cada fase da construção de qualquer lugar.</p>
                <span style="font-size: var(--text-sm); font-weight: 600; color: #7b61ff; display: flex; align-items: center; gap: 6px;">Conhecer a Matterport <i data-lucide="arrow-right" style="width:14px;height:14px;"></i></span>
            </a>
        </div>
    </div>
</section>

<!-- ===== CULTURE ===== -->
<section class="section" id="cultura">
    <div class="container">
        <div class="grid grid--2" style="align-items: center; gap: var(--space-4xl);">
            <div class="reveal-left">
                <img src="/assets/images/wp/2024/11/NorahCarneiro_Av.Prof_.AscendinoReis_RafaelRenzo-104-scaled.webp" alt="Norah Arquiteta parceira da Brooks" style="border-radius: var(--radius-xl); width: 100%;" loading="lazy">
                <p style="text-align: center; font-size: var(--text-sm); color: var(--brooks-gray-500); margin-top: var(--space-sm);">Norah Arquiteta parceira da Brooks</p>
            </div>
            <div class="reveal-right">
                <span class="label">Cultura</span>
                <h2 class="headline-section">As pessoas entram e permanecem.</h2>
                <p style="font-size: var(--text-lg); color: var(--brooks-gray-600); line-height: 1.8; margin-bottom: var(--space-lg);">
                    Na Brooks, a cultura forte é o que mantém a empresa em pé e crescente. As pessoas que entram demonstram ter o mesmo propósito, os mesmos valores, e por isso ficam, crescem e evoluem.
                </p>
                <p style="color: var(--brooks-gray-500); line-height: 1.8; margin-bottom: var(--space-xl);">
                    Sabemos que a estratégia muda a cada fase, a cada oscilação do mercado. Mas o propósito e a cultura não mudam. Porque são reflexo do que a alma vive.
                </p>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-lg);">
                    <div>
                        <h4 style="font-size: var(--text-sm); font-weight: 700; color: var(--brooks-navy); margin-bottom: var(--space-xs);">Base</h4>
                        <p style="font-size: var(--text-sm); color: var(--brooks-gray-500);">A excelência é construída sobre uma base sólida de pessoas, processos, tecnologia e governança.</p>
                    </div>
                    <div>
                        <h4 style="font-size: var(--text-sm); font-weight: 700; color: var(--brooks-navy); margin-bottom: var(--space-xs);">Propósito</h4>
                        <p style="font-size: var(--text-sm); color: var(--brooks-gray-500);">Transformar a construção civil através da excelência e inovação.</p>
                    </div>
                    <div>
                        <h4 style="font-size: var(--text-sm); font-weight: 700; color: var(--brooks-navy); margin-bottom: var(--space-xs);">Estratégia</h4>
                        <p style="font-size: var(--text-sm); color: var(--brooks-gray-500);">Evoluir constantemente, investir sempre, mesmo na incerteza.</p>
                    </div>
                    <div>
                        <h4 style="font-size: var(--text-sm); font-weight: 700; color: var(--brooks-navy); margin-bottom: var(--space-xs);">Cultura</h4>
                        <p style="font-size: var(--text-sm); color: var(--brooks-gray-500);">Portas abertas para quem compartilha do mesmo sonho.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== TESTIMONIALS ===== -->
<section class="section section--gray" id="depoimentos">
    <div class="container">
        <div class="section-header section-header--centered reveal">
            <span class="label">Depoimentos</span>
            <h2 class="headline-section">O que dizem sobre a Brooks</h2>
        </div>
        
        <div class="grid grid--2 reveal" style="gap: var(--space-xl); max-width: 960px; margin: 0 auto;">
            <div class="testimonial-card">
                <p class="testimonial-card__quote">Profissionalismo impecável e resultado acima das expectativas. A Brooks entregou nossa reforma no prazo com qualidade extraordinária.</p>
                <div class="testimonial-card__author">
                    <div class="testimonial-card__avatar" style="background: var(--brooks-blue-accent); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">A</div>
                    <div>
                        <div class="testimonial-card__name">Arquiteto Parceiro</div>
                        <div class="testimonial-card__role">São Paulo, SP</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-card">
                <p class="testimonial-card__quote">A organização e a tecnologia que eles usam é de outro nível. Você sente que está em boas mãos do início ao fim.</p>
                <div class="testimonial-card__author">
                    <div class="testimonial-card__avatar" style="background: var(--brooks-navy); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">C</div>
                    <div>
                        <div class="testimonial-card__name">Cliente Residencial</div>
                        <div class="testimonial-card__role">Jardim Paulistano</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== MAGAZINE / NEWSLETTER CTA ===== -->
<section class="section section--dark" id="revista-cta" style="padding: var(--space-4xl) 0;">
    <div class="container">
        <div class="grid grid--2 reveal" style="align-items: center; gap: var(--space-3xl);">
            <div>
                <span class="label" style="color: var(--brooks-blue-accent);">Revista Digital</span>
                <h2 class="headline-subsection" style="color: white;">Assine Gratuitamente a Revista Brooks</h2>
                <p style="color: rgba(255,255,255,0.6); line-height: 1.7;">Receba edições exclusivas sobre construção sustentável, reformas de alto padrão e tendências de arquitetura diretamente no seu e-mail.</p>
            </div>
            <div>
                <form action="/newsletter/subscribe" method="POST" class="newsletter-form-ajax" style="display: flex; flex-direction: column; gap: var(--space-md);">
                    <input type="text" name="name" placeholder="Seu nome" style="padding: 14px 18px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: var(--radius-md); color: white; font-size: var(--text-sm); outline: none;" required>
                    <input type="email" name="email" placeholder="Seu melhor e-mail" style="padding: 14px 18px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: var(--radius-md); color: white; font-size: var(--text-sm); outline: none;" required>
                    <button type="submit" class="btn btn--primary" style="width: 100%;">Assinar Gratuitamente</button>
                    <p style="font-size: var(--text-xs); color: rgba(255,255,255,0.35); text-align: center;">Sem spam. Cancele quando quiser.</p>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- ===== CTA FINAL ===== -->
<section class="section" id="cta-final" style="padding: var(--space-5xl) 0;">
    <div class="container text-center reveal">
        <h2 class="headline-section">Pronto para transformar seu espaço?</h2>
        <p class="subtitle subtitle--centered" style="margin-bottom: var(--space-2xl);">
            Converse com nossa equipe. Sem compromisso, com toda a transparência que você merece.
        </p>
        <div style="display: flex; gap: var(--space-md); justify-content: center; flex-wrap: wrap;">
            <a href="/contato" class="btn btn--primary btn--lg">Solicitar Orçamento</a>
            <a href="https://api.whatsapp.com/send?phone=<?= !empty($settings['site_whatsapp']) ? $settings['site_whatsapp'] : '5511993392659' ?>&text=Ol%C3%A1!" target="_blank" class="btn btn--outline btn--lg">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                WhatsApp
            </a>
        </div>
    </div>
</section>

<?php include ROOT_PATH . '/app/Views/site/layouts/new-footer.php'; ?>
