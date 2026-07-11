<?php
$pageTitle = 'Brooks Academy';
$pageDescription = 'Brooks Academy - Escola profissionalizante nos canteiros de obra. Plano de carreira, capacitação, treinamentos e bolsas de engenharia civil.';
$currentPage = 'academy';
$bodyClass = 'page-academy';
include ROOT_PATH . '/app/Views/site/layouts/new-header.php';
?>

<!-- Hero -->
<section class="section section--dark" style="padding-top: calc(var(--header-height) + var(--space-5xl)); padding-bottom: var(--space-5xl); background: linear-gradient(145deg, #4a2c0a, #7f3d00);">
    <div class="container">
        <div class="reveal" style="max-width: 700px;">
            <span class="label" style="color: #f39c12;">Educação & Impacto Social</span>
            <h1 class="headline-hero" style="color: white;">Brooks Academy</h1>
            <p style="font-size: var(--text-xl); color: rgba(255,255,255,0.7); line-height: 1.7; margin-bottom: var(--space-xl);">
                A própria escola profissionalizante da Brooks. Dentro dos canteiros de obra, transformando vidas através da educação e do conhecimento.
            </p>
            <p style="color: rgba(255,255,255,0.5); line-height: 1.8;">
                A Brooks oferece planos de carreira nos seus canteiros de obras. Colaboradores que desejam têm acesso a cursos, e aqueles que se destacam podem concorrer a bolsas de até um ano de engenharia civil EAD.
            </p>
        </div>
    </div>
</section>

<!-- What is -->
<section class="section">
    <div class="container">
        <div class="grid grid--2" style="align-items: center; gap: var(--space-4xl);">
            <div class="reveal-left">
                <span class="label">Impacto Real</span>
                <h2 class="headline-section">Educação dentro do canteiro de obras.</h2>
                <p style="font-size: var(--text-lg); color: var(--brooks-gray-600); line-height: 1.8; margin-bottom: var(--space-lg);">
                    Nos canteiros de obras de construção de residências de alto padrão, entre as paredes que estão sendo erguidas, existe uma escola da Brooks. Colaboradores que desejam têm acesso a cursos técnicos e profissionalizantes.
                </p>
                <p style="color: var(--brooks-gray-500); line-height: 1.8;">
                    Por mais que o filho do colaborador não deseje seguir o mesmo caminho, ainda assim ele tem a chance de fazer um estágio aprendendo na prática — e podendo levar a teoria a sério através de bolsas de engenharia civil oferecidas pela Brooks.
                </p>
            </div>
            <div class="reveal-right">
                <img src="/assets/images/wp/2024/11/IMG_2586-HDR-2-jpg.webp" alt="Brooks Academy - Canteiro de obras" style="border-radius: var(--radius-xl); width: 100%; box-shadow: var(--shadow-2xl);" loading="lazy">
            </div>
        </div>
    </div>
</section>

<!-- Offerings -->
<section class="section section--gray">
    <div class="container">
        <div class="section-header section-header--centered reveal">
            <span class="label">O que oferecemos</span>
            <h2 class="headline-section">Crescimento profissional real.</h2>
        </div>
        
        <div class="grid grid--3 reveal">
            <div class="card delay-1" style="text-align: center;">
                <div class="card__icon" style="margin: 0 auto var(--space-lg); background: linear-gradient(135deg, #f39c12, #e67e22);"><i data-lucide="graduation-cap"></i></div>
                <h3 class="card__title">Escola Profissionalizante</h3>
                <p class="card__text">Cursos técnicos dentro do próprio canteiro de obras. Aprender na prática com acompanhamento especializado.</p>
            </div>
            <div class="card delay-2" style="text-align: center;">
                <div class="card__icon" style="margin: 0 auto var(--space-lg); background: linear-gradient(135deg, #f39c12, #e67e22);"><i data-lucide="trending-up"></i></div>
                <h3 class="card__title">Plano de Carreira</h3>
                <p class="card__text">Crescimento interno com oportunidades reais. As pessoas entram na Brooks e permanecem — porque crescem.</p>
            </div>
            <div class="card delay-3" style="text-align: center;">
                <div class="card__icon" style="margin: 0 auto var(--space-lg); background: linear-gradient(135deg, #f39c12, #e67e22);"><i data-lucide="award"></i></div>
                <h3 class="card__title">Bolsas de Engenharia</h3>
                <p class="card__text">Colaboradores que se destacam podem concorrer a bolsas de até um ano de engenharia civil EAD pela Brooks.</p>
            </div>
            <div class="card delay-4" style="text-align: center;">
                <div class="card__icon" style="margin: 0 auto var(--space-lg); background: linear-gradient(135deg, #f39c12, #e67e22);"><i data-lucide="book-open"></i></div>
                <h3 class="card__title">Capacitação Contínua</h3>
                <p class="card__text">Treinamentos regulares em segurança, técnicas construtivas, qualidade e boas práticas.</p>
            </div>
            <div class="card delay-5" style="text-align: center;">
                <div class="card__icon" style="margin: 0 auto var(--space-lg); background: linear-gradient(135deg, #f39c12, #e67e22);"><i data-lucide="wrench"></i></div>
                <h3 class="card__title">Estágio Prático</h3>
                <p class="card__text">Oportunidade de aprender engenharia civil na prática, diretamente no campo, com acompanhamento profissional.</p>
            </div>
            <div class="card delay-6" style="text-align: center;">
                <div class="card__icon" style="margin: 0 auto var(--space-lg); background: linear-gradient(135deg, #f39c12, #e67e22);"><i data-lucide="heart"></i></div>
                <h3 class="card__title">Impacto Social</h3>
                <p class="card__text">Transformando vidas através da educação e do trabalho. Oportunidades reais para quem quer crescer.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section" style="padding: var(--space-4xl) 0;">
    <div class="container text-center reveal">
        <h2 class="headline-subsection">Acreditamos que a educação transforma.</h2>
        <p class="subtitle subtitle--centered" style="margin-bottom: var(--space-xl);">A Brooks Academy é nosso compromisso com as pessoas e com o futuro da construção civil brasileira.</p>
        <a href="/contato" class="btn btn--primary btn--lg">Saiba mais</a>
    </div>
</section>

<?php include ROOT_PATH . '/app/Views/site/layouts/new-footer.php'; ?>
