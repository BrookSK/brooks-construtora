<?php
$pageTitle = 'Brooks Academy — Escola Profissionalizante';
$pageDescription = 'Brooks Academy - Escola profissionalizante dentro dos canteiros de obra. Plano de carreira, capacitação e bolsas de engenharia civil para colaboradores.';
$currentPage = 'academy';
$bodyClass = 'page-academy';
include ROOT_PATH . '/app/Views/site/layouts/new-header.php';
?>

<!-- Hero - visual humano e quente, tons amber -->
<section style="padding-top: calc(var(--header-height) + var(--space-4xl)); padding-bottom: var(--space-5xl); background: linear-gradient(160deg, #1a0f00 0%, #4a2c0a 40%, #7f5517 100%); position: relative; overflow: hidden;">
    <!-- Warm glow -->
    <div style="position: absolute; top: 50%; right: 0; width: 500px; height: 500px; background: radial-gradient(circle, rgba(243, 156, 18, 0.08), transparent 70%); transform: translateY(-50%);"></div>
    
    <div class="container" style="position: relative; z-index: 1;">
        <div class="grid grid--2 reveal" style="align-items: center; gap: var(--space-3xl);">
            <div>
                <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; background: rgba(243, 156, 18, 0.15); border: 1px solid rgba(243, 156, 18, 0.3); border-radius: var(--radius-full); margin-bottom: var(--space-xl);">
                    <i data-lucide="graduation-cap" style="width:14px;height:14px;color:#f39c12;"></i>
                    <span style="font-size: 11px; font-weight: 600; color: #f39c12; text-transform: uppercase; letter-spacing: 0.08em;">Educação & Impacto Social</span>
                </div>
                <h1 style="font-size: clamp(2.5rem, 5vw, 3.5rem); font-weight: 800; color: white; line-height: 1.1; margin-bottom: var(--space-lg);">
                    A escola que existe <span style="color: #f39c12;">dentro do canteiro de obras.</span>
                </h1>
                <p style="font-size: var(--text-lg); color: rgba(255,255,255,0.65); line-height: 1.8;">
                    A Brooks Academy oferece plano de carreira, cursos profissionalizantes e bolsas de engenharia civil — tudo dentro dos nossos canteiros de obra de alto padrão.
                </p>
            </div>
            <div>
                <img src="/assets/images/wp/2024/11/IMG_2586-HDR-2-jpg.webp" alt="Canteiro de obras Brooks" style="border-radius: var(--radius-xl); width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.4);" loading="lazy">
            </div>
        </div>
    </div>
</section>

<!-- Por quê? -->
<section class="section">
    <div class="container">
        <div class="grid grid--2 reveal" style="align-items: center; gap: var(--space-3xl);">
            <div>
                <span class="label" style="color: #e67e22;">O propósito</span>
                <h2 class="headline-section">As pessoas que entram na Brooks, permanecem.</h2>
                <p style="font-size: var(--text-lg); color: var(--brooks-gray-600); line-height: 1.8; margin-bottom: var(--space-lg);">
                    Isso não é por acaso. A Brooks investe em cada colaborador como se fosse o primeiro dia. A Academy é a materialização dessa crença: quem quer crescer, vai encontrar o caminho aqui dentro.
                </p>
                <p style="color: var(--brooks-gray-500); line-height: 1.8;">
                    Nos canteiros de obras de residências de alto padrão, entre paredes sendo erguidas, existe uma escola. Colaboradores que desejam têm acesso a cursos técnicos e profissionalizantes. E os que se destacam, concorrem a bolsas de engenharia civil.
                </p>
            </div>
            <div>
                <div style="background: linear-gradient(135deg, #fef3e2, #fde8c8); border-radius: var(--radius-xl); padding: var(--space-2xl);">
                    <div style="display: grid; gap: var(--space-xl);">
                        <div style="display: flex; align-items: center; gap: var(--space-md);">
                            <div style="width: 48px; height: 48px; border-radius: var(--radius-full); background: #f39c12; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; font-weight: 800; font-size: var(--text-lg);">1</div>
                            <div>
                                <p style="font-weight: 600; color: var(--brooks-navy); font-size: var(--text-sm);">Entra na Brooks</p>
                                <p style="font-size: var(--text-xs); color: var(--brooks-gray-500);">Colaborador com vontade de crescer</p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: var(--space-md);">
                            <div style="width: 48px; height: 48px; border-radius: var(--radius-full); background: #e67e22; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; font-weight: 800; font-size: var(--text-lg);">2</div>
                            <div>
                                <p style="font-weight: 600; color: var(--brooks-navy); font-size: var(--text-sm);">Acessa os cursos</p>
                                <p style="font-size: var(--text-xs); color: var(--brooks-gray-500);">Capacitação técnica dentro do canteiro</p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: var(--space-md);">
                            <div style="width: 48px; height: 48px; border-radius: var(--radius-full); background: #d35400; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; font-weight: 800; font-size: var(--text-lg);">3</div>
                            <div>
                                <p style="font-weight: 600; color: var(--brooks-navy); font-size: var(--text-sm);">Se destaca</p>
                                <p style="font-size: var(--text-xs); color: var(--brooks-gray-500);">Performance reconhecida pela liderança</p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: var(--space-md);">
                            <div style="width: 48px; height: 48px; border-radius: var(--radius-full); background: #a04000; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0; font-weight: 800; font-size: var(--text-lg);">4</div>
                            <div>
                                <p style="font-weight: 600; color: var(--brooks-navy); font-size: var(--text-sm);">Conquista a bolsa</p>
                                <p style="font-size: var(--text-xs); color: var(--brooks-gray-500);">Até 1 ano de engenharia civil EAD</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- O que oferecemos -->
<section class="section section--gray">
    <div class="container">
        <div class="section-header section-header--centered reveal">
            <span class="label" style="color: #e67e22;">Programas</span>
            <h2 class="headline-section">Crescimento profissional real dentro da obra.</h2>
        </div>
        
        <div class="grid grid--3 reveal" style="gap: var(--space-lg);">
            <div class="card" style="text-align: center; border-top: 3px solid #f39c12;">
                <div style="width: 64px; height: 64px; border-radius: var(--radius-full); background: linear-gradient(135deg, #f39c12, #e67e22); display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-lg); color: white;">
                    <i data-lucide="book-open" style="width:28px;height:28px;"></i>
                </div>
                <h3 class="card__title">Cursos Profissionalizantes</h3>
                <p class="card__text">Capacitação técnica direto no canteiro. Segurança do trabalho, técnicas construtivas, leitura de projetos e mais.</p>
            </div>
            <div class="card" style="text-align: center; border-top: 3px solid #e67e22;">
                <div style="width: 64px; height: 64px; border-radius: var(--radius-full); background: linear-gradient(135deg, #e67e22, #d35400); display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-lg); color: white;">
                    <i data-lucide="trending-up" style="width:28px;height:28px;"></i>
                </div>
                <h3 class="card__title">Plano de Carreira</h3>
                <p class="card__text">Crescimento interno estruturado. De ajudante a encarregado, de encarregado a mestre de obras. Caminhos claros.</p>
            </div>
            <div class="card" style="text-align: center; border-top: 3px solid #d35400;">
                <div style="width: 64px; height: 64px; border-radius: var(--radius-full); background: linear-gradient(135deg, #d35400, #a04000); display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-lg); color: white;">
                    <i data-lucide="award" style="width:28px;height:28px;"></i>
                </div>
                <h3 class="card__title">Bolsas de Engenharia</h3>
                <p class="card__text">Colaboradores que se destacam concorrem a bolsas de até 1 ano de engenharia civil EAD financiada pela Brooks.</p>
            </div>
        </div>
    </div>
</section>

<!-- Impacto social -->
<section class="section">
    <div class="container">
        <div class="grid grid--2 reveal" style="align-items: center; gap: var(--space-3xl);">
            <div style="background: linear-gradient(160deg, #4a2c0a, #7f5517); border-radius: var(--radius-xl); padding: var(--space-3xl); color: white;">
                <i data-lucide="heart" style="width:40px;height:40px;color:#f39c12;margin-bottom:var(--space-lg);"></i>
                <h3 style="font-size: var(--text-2xl); font-weight: 700; margin-bottom: var(--space-md);">Impacto que vai além da obra.</h3>
                <p style="color: rgba(255,255,255,0.7); line-height: 1.8; margin-bottom: var(--space-lg);">
                    Por mais que o filho do colaborador não deseje seguir o mesmo caminho — o filho do pedreiro não quer ser pedreiro — ainda assim ele tem a chance de fazer um estágio, aprender na prática, e ter acesso à teoria através da bolsa.
                </p>
                <p style="color: rgba(255,255,255,0.5); font-size: var(--text-sm); line-height: 1.7;">
                    A Brooks Academy é nosso compromisso com a transformação social através do trabalho digno e da educação acessível.
                </p>
            </div>
            <div>
                <h3 class="headline-subsection">O que acreditamos</h3>
                <div style="display: grid; gap: var(--space-lg); margin-top: var(--space-lg);">
                    <div style="display: flex; gap: var(--space-md); align-items: flex-start;">
                        <i data-lucide="check-circle" style="width:20px;height:20px;color:#f39c12;flex-shrink:0;margin-top:2px;"></i>
                        <p style="font-size: var(--text-sm); color: var(--brooks-gray-600); line-height: 1.6;"><strong>Toda pessoa merece crescer</strong> — independente de onde começou. A porta está aberta.</p>
                    </div>
                    <div style="display: flex; gap: var(--space-md); align-items: flex-start;">
                        <i data-lucide="check-circle" style="width:20px;height:20px;color:#f39c12;flex-shrink:0;margin-top:2px;"></i>
                        <p style="font-size: var(--text-sm); color: var(--brooks-gray-600); line-height: 1.6;"><strong>Prática e teoria juntas</strong> — aprender construindo, com suporte acadêmico para quem quer ir além.</p>
                    </div>
                    <div style="display: flex; gap: var(--space-md); align-items: flex-start;">
                        <i data-lucide="check-circle" style="width:20px;height:20px;color:#f39c12;flex-shrink:0;margin-top:2px;"></i>
                        <p style="font-size: var(--text-sm); color: var(--brooks-gray-600); line-height: 1.6;"><strong>Cultura forte retém talentos</strong> — as pessoas ficam na Brooks porque compartilham do mesmo propósito.</p>
                    </div>
                    <div style="display: flex; gap: var(--space-md); align-items: flex-start;">
                        <i data-lucide="check-circle" style="width:20px;height:20px;color:#f39c12;flex-shrink:0;margin-top:2px;"></i>
                        <p style="font-size: var(--text-sm); color: var(--brooks-gray-600); line-height: 1.6;"><strong>Investir em pessoas é investir no futuro</strong> — da construção civil brasileira como um todo.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section section--gray" style="padding: var(--space-4xl) 0;">
    <div class="container text-center reveal">
        <h2 class="headline-section">Educação que transforma vidas.</h2>
        <p class="subtitle subtitle--centered" style="margin-bottom: var(--space-xl);">A Brooks Academy é nosso compromisso com as pessoas e com o futuro da construção civil brasileira.</p>
        <a href="/contato" class="btn btn--lg" style="background: linear-gradient(135deg, #f39c12, #e67e22); color: white;">Quero saber mais</a>
    </div>
</section>

<?php include ROOT_PATH . '/app/Views/site/layouts/new-footer.php'; ?>
