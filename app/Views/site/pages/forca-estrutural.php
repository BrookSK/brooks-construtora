<?php
$pageTitle = 'Força Estrutural';
$pageDescription = 'Força Estrutural - Grupo de empresários da construção civil. Networking, eventos, conhecimento e parcerias com as melhores marcas e lojas de São Paulo.';
$currentPage = 'forca';
$bodyClass = 'page-forca';
include ROOT_PATH . '/app/Views/site/layouts/new-header.php';
?>

<!-- Hero -->
<section class="section section--dark" style="padding-top: calc(var(--header-height) + var(--space-5xl)); padding-bottom: var(--space-5xl); background: linear-gradient(145deg, var(--forca-ocean), var(--forca-teal));">
    <div class="container">
        <div class="reveal" style="max-width: 700px;">
            <span class="label" style="color: rgba(255,255,255,0.7);">Comunidade</span>
            <h1 class="headline-hero" style="color: white;">Força Estrutural</h1>
            <p style="font-size: var(--text-xl); color: rgba(255,255,255,0.7); line-height: 1.7; margin-bottom: var(--space-xl);">
                Nasceu para combater a solidão do empresário da construção civil. Um grupo onde concorrentes se tornam parceiros.
            </p>
            <p style="color: rgba(255,255,255,0.5); line-height: 1.8; margin-bottom: var(--space-2xl);">
                A Brooks descobriu que a maior concorrência não eram as outras empresas — era a solidão do empresário. Tudo o que a Brooks desejou ter no início — conhecimento, conversar com quem já passou pelos percalços — ela decidiu replicar para outros empresários.
            </p>
            <a href="https://www.instagram.com/forcaestrutural/" target="_blank" rel="noopener" class="btn btn--secondary btn--lg">
                <i data-lucide="instagram" style="width:18px;height:18px;"></i> Siga no Instagram
            </a>
        </div>
    </div>
</section>

<!-- O que é -->
<section class="section">
    <div class="container">
        <div class="section-header section-header--centered reveal">
            <span class="label">O Grupo</span>
            <h2 class="headline-section">Muito mais que networking.</h2>
            <p class="subtitle subtitle--centered">O Força Estrutural ganhou uma força no mercado da construção civil, atraindo parceiros renomados, as melhores lojas e marcas de São Paulo.</p>
        </div>
        
        <div class="grid grid--3 reveal">
            <div class="card delay-1" style="text-align: center;">
                <div class="card__icon" style="margin: 0 auto var(--space-lg); background: linear-gradient(135deg, var(--forca-teal), var(--forca-ocean));"><i data-lucide="users"></i></div>
                <h3 class="card__title">Networking</h3>
                <p class="card__text">Empresários da construção civil trocando experiências, desafios e soluções em um ambiente de confiança mútua.</p>
            </div>
            <div class="card delay-2" style="text-align: center;">
                <div class="card__icon" style="margin: 0 auto var(--space-lg); background: linear-gradient(135deg, var(--forca-teal), var(--forca-ocean));"><i data-lucide="calendar-days"></i></div>
                <h3 class="card__title">Eventos</h3>
                <p class="card__text">Eventos exclusivos em 2025 e 2026, conectando profissionais e criando oportunidades reais de negócio.</p>
            </div>
            <div class="card delay-3" style="text-align: center;">
                <div class="card__icon" style="margin: 0 auto var(--space-lg); background: linear-gradient(135deg, var(--forca-teal), var(--forca-ocean));"><i data-lucide="lightbulb"></i></div>
                <h3 class="card__title">Conhecimento</h3>
                <p class="card__text">Compartilhamento de estratégias, processos e aprendizados que aceleraram o crescimento de cada membro.</p>
            </div>
            <div class="card delay-4" style="text-align: center;">
                <div class="card__icon" style="margin: 0 auto var(--space-lg); background: linear-gradient(135deg, var(--forca-teal), var(--forca-ocean));"><i data-lucide="handshake"></i></div>
                <h3 class="card__title">Parcerias</h3>
                <p class="card__text">Parcerias com os melhores arquitetos, lojas e marcas de São Paulo. Benefícios exclusivos para membros.</p>
            </div>
            <div class="card delay-5" style="text-align: center;">
                <div class="card__icon" style="margin: 0 auto var(--space-lg); background: linear-gradient(135deg, var(--forca-teal), var(--forca-ocean));"><i data-lucide="building-2"></i></div>
                <h3 class="card__title">Marcas & Lojas</h3>
                <p class="card__text">Acesso privilegiado às melhores marcas e fornecedores do mercado de construção e design de interiores.</p>
            </div>
            <div class="card delay-6" style="text-align: center;">
                <div class="card__icon" style="margin: 0 auto var(--space-lg); background: linear-gradient(135deg, var(--forca-teal), var(--forca-ocean));"><i data-lucide="heart-handshake"></i></div>
                <h3 class="card__title">Comunidade</h3>
                <p class="card__text">Onde concorrentes se tornam aliados. O grupo foi validador da ferramenta Vetrix, comprovando sua excelência.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section section--dark" style="padding: var(--space-4xl) 0; background: linear-gradient(145deg, var(--forca-ocean), var(--forca-teal));">
    <div class="container text-center reveal">
        <h2 class="headline-subsection" style="color: white;">O Força Estrutural segue cada dia mais forte.</h2>
        <p style="color: rgba(255,255,255,0.6); max-width: 560px; margin: 0 auto var(--space-xl); line-height: 1.7;">Nasceu da inspiração que a Brooks se tornou. Hoje é referência em comunidade para o setor da construção civil.</p>
        <a href="https://www.instagram.com/forcaestrutural/" target="_blank" rel="noopener" class="btn btn--primary btn--lg">Acompanhar o Força Estrutural</a>
    </div>
</section>

<?php include ROOT_PATH . '/app/Views/site/layouts/new-footer.php'; ?>
