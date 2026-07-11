<?php
$pageTitle = 'Vetriks';
$pageDescription = 'Vetriks - Sistema de gestão de obra completo, vertical e integrado. Criado no campo, nas dores reais da construção civil. Tecnologia própria da Brooks.';
$currentPage = 'vetriks';
$bodyClass = 'page-vetriks';
include ROOT_PATH . '/app/Views/site/layouts/new-header.php';
?>

<!-- Hero -->
<section class="section section--dark" style="padding-top: calc(var(--header-height) + var(--space-5xl)); padding-bottom: var(--space-5xl); background: linear-gradient(145deg, var(--vetriks-dark), var(--vetriks-blue));">
    <div class="container">
        <div class="grid grid--2" style="align-items: center; gap: var(--space-4xl);">
            <div class="reveal-left">
                <span class="label" style="color: var(--vetriks-accent);">Tecnologia Brooks</span>
                <h1 class="headline-hero" style="color: white;">Vetriks</h1>
                <p style="font-size: var(--text-xl); color: rgba(255,255,255,0.7); line-height: 1.7; margin-bottom: var(--space-xl);">
                    Sistema de gestão de obra completo, vertical e integrado. Criado no campo, forjado na operação, validado por dezenas de construtoras.
                </p>
                <p style="color: rgba(255,255,255,0.5); line-height: 1.8; margin-bottom: var(--space-2xl);">
                    Nasceu dentro da Brooks, nas dores reais da construção. Criado após os proprietários tentarem outros sistemas e perceberem que eram fragmentados e robustos. O Vetriks é vertical, didático, intuitivo e prático — com muita IA.
                </p>
                <div style="display: flex; gap: var(--space-md); flex-wrap: wrap;">
                    <a href="https://vetriks.com.br/" target="_blank" rel="noopener" class="btn btn--primary btn--lg">Acessar a Vetriks</a>
                    <a href="/contato" class="btn btn--secondary btn--lg">Saber mais</a>
                </div>
            </div>
            <div class="reveal-right" style="text-align: center;">
                <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: var(--radius-2xl); padding: var(--space-3xl);">
                    <i data-lucide="monitor-smartphone" style="width:120px;height:120px;color:var(--vetriks-accent);opacity:0.8;"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features -->
<section class="section">
    <div class="container">
        <div class="section-header section-header--centered reveal">
            <span class="label">Funcionalidades</span>
            <h2 class="headline-section">Tudo integrado em um só lugar.</h2>
            <p class="subtitle subtitle--centered">Gestão completa desde o orçamento até a entrega. Sem fragmentação, sem complexidade.</p>
        </div>
        
        <div class="grid grid--3 reveal">
            <div class="card delay-1">
                <div class="card__icon" style="background: linear-gradient(135deg, var(--vetriks-accent), var(--vetriks-blue));"><i data-lucide="brain"></i></div>
                <h3 class="card__title">Inteligência Artificial</h3>
                <p class="card__text">IA integrada para otimizar processos, prever gargalos e automatizar tarefas repetitivas.</p>
            </div>
            <div class="card delay-2">
                <div class="card__icon" style="background: linear-gradient(135deg, var(--vetriks-accent), var(--vetriks-blue));"><i data-lucide="layout-dashboard"></i></div>
                <h3 class="card__title">Gestão Integrada</h3>
                <p class="card__text">Sistema vertical que conecta todas as fases da obra em uma interface única e intuitiva.</p>
            </div>
            <div class="card delay-3">
                <div class="card__icon" style="background: linear-gradient(135deg, var(--vetriks-accent), var(--vetriks-blue));"><i data-lucide="smartphone"></i></div>
                <h3 class="card__title">Mobilidade</h3>
                <p class="card__text">Acesso completo pelo celular. Controle a obra de qualquer lugar, a qualquer momento.</p>
            </div>
            <div class="card delay-4">
                <div class="card__icon" style="background: linear-gradient(135deg, var(--vetriks-accent), var(--vetriks-blue));"><i data-lucide="bar-chart-3"></i></div>
                <h3 class="card__title">Controle Total</h3>
                <p class="card__text">Dashboards em tempo real, indicadores de performance e visibilidade completa do projeto.</p>
            </div>
            <div class="card delay-5">
                <div class="card__icon" style="background: linear-gradient(135deg, var(--vetriks-accent), var(--vetriks-blue));"><i data-lucide="plug"></i></div>
                <h3 class="card__title">Integração</h3>
                <p class="card__text">Conecta fornecedores, equipes e clientes em um ambiente colaborativo e transparente.</p>
            </div>
            <div class="card delay-6">
                <div class="card__icon" style="background: linear-gradient(135deg, var(--vetriks-accent), var(--vetriks-blue));"><i data-lucide="trending-up"></i></div>
                <h3 class="card__title">Produtividade</h3>
                <p class="card__text">Obras desorganizadas causam dispersar do progresso. O Vetriks garante foco e eficiência.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section section--gray" style="padding: var(--space-4xl) 0;">
    <div class="container text-center reveal">
        <h2 class="headline-subsection">Ficou tão exemplar que outras construtoras utilizam.</h2>
        <p class="subtitle subtitle--centered" style="margin-bottom: var(--space-xl);">O Vetriks nasceu para uso interno, mas se tornou referência em São Paulo. Hoje, dezenas de construtoras confiam na plataforma.</p>
        <a href="https://vetriks.com.br/" target="_blank" rel="noopener" class="btn btn--primary btn--lg">Conhecer a Vetriks</a>
    </div>
</section>

<?php include ROOT_PATH . '/app/Views/site/layouts/new-footer.php'; ?>
