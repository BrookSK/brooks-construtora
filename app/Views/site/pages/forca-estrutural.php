<?php
$pageTitle = 'Força Estrutural — Grupo de Empresários';
$pageDescription = 'Força Estrutural - O grupo que combate a solidão do empresário da construção civil. Networking, eventos, parcerias com as melhores marcas de São Paulo.';
$currentPage = 'forca';
$bodyClass = 'page-forca';
include ROOT_PATH . '/app/Views/site/layouts/new-header.php';
?>

<!-- Hero - visual mais humano, focado em comunidade -->
<section style="padding-top: calc(var(--header-height) + var(--space-4xl)); padding-bottom: var(--space-5xl); background: linear-gradient(160deg, #0c2233 0%, #1a5276 50%, #2e86ab 100%); position: relative; overflow: hidden;">
    <!-- Abstract circles pattern -->
    <div style="position: absolute; top: -100px; right: -100px; width: 400px; height: 400px; border-radius: 50%; border: 1px solid rgba(255,255,255,0.05);"></div>
    <div style="position: absolute; bottom: -150px; left: -150px; width: 500px; height: 500px; border-radius: 50%; border: 1px solid rgba(255,255,255,0.03);"></div>
    
    <div class="container" style="position: relative; z-index: 1;">
        <div class="reveal" style="max-width: 640px;">
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; background: rgba(46, 134, 171, 0.2); border: 1px solid rgba(46, 134, 171, 0.4); border-radius: var(--radius-full); margin-bottom: var(--space-xl);">
                <i data-lucide="users" style="width:14px;height:14px;color:#5dade2;"></i>
                <span style="font-size: 11px; font-weight: 600; color: #5dade2; text-transform: uppercase; letter-spacing: 0.08em;">Comunidade</span>
            </div>
            <h1 style="font-size: clamp(2.5rem, 5vw, 4rem); font-weight: 800; color: white; line-height: 1.1; margin-bottom: var(--space-lg);">
                A maior concorrência não são as outras empresas. <span style="color: #5dade2;">É a solidão.</span>
            </h1>
            <p style="font-size: var(--text-lg); color: rgba(255,255,255,0.65); line-height: 1.8; margin-bottom: var(--space-xl);">
                O Força Estrutural nasceu para combater a solidão do empresário da construção civil. Um grupo onde concorrentes se tornam parceiros, compartilham conhecimento e crescem juntos.
            </p>
            <a href="https://www.instagram.com/forcaestrutural/" target="_blank" rel="noopener" class="btn btn--lg" style="background: white; color: var(--forca-ocean); font-weight: 700;">
                <i data-lucide="instagram" style="width:18px;height:18px;"></i> Acompanhar no Instagram
            </a>
        </div>
    </div>
</section>

<!-- A história por trás -->
<section class="section">
    <div class="container">
        <div class="grid grid--2 reveal" style="align-items: center; gap: var(--space-3xl);">
            <div>
                <span class="label" style="color: var(--forca-teal);">A Origem</span>
                <h2 class="headline-section">Tudo o que a Brooks desejou ter e não teve.</h2>
                <p style="font-size: var(--text-lg); color: var(--brooks-gray-600); line-height: 1.8; margin-bottom: var(--space-lg);">
                    No começo da trajetória, os proprietários da Brooks sentiram na pele a falta de apoio. Queriam conversar com alguém que já tivesse passado pelos mesmos percalços da construção civil. Não existia isso.
                </p>
                <p style="color: var(--brooks-gray-500); line-height: 1.8;">
                    Quando a Brooks se tornou inspiração para outros empresários, seus fundadores decidiram criar o que sempre desejaram: um grupo de apoio mútuo. Assim nasceu o Força Estrutural — onde concorrentes viraram aliados.
                </p>
            </div>
            <div style="background: linear-gradient(145deg, var(--forca-ocean), var(--forca-teal)); border-radius: var(--radius-xl); padding: var(--space-3xl); color: white;">
                <p style="font-size: var(--text-2xl); font-weight: 700; line-height: 1.4; margin-bottom: var(--space-xl);">"Descobrimos que a maior concorrência não eram as outras empresas — era a solidão do empresário."</p>
                <p style="font-size: var(--text-sm); opacity: 0.7;">— Kauê e Mariana, fundadores</p>
            </div>
        </div>
    </div>
</section>

<!-- O que oferecemos -->
<section class="section section--gray">
    <div class="container">
        <div class="section-header section-header--centered reveal">
            <span class="label" style="color: var(--forca-teal);">O que oferecemos</span>
            <h2 class="headline-section">Muito mais que networking.</h2>
            <p class="subtitle subtitle--centered">Eventos exclusivos, parcerias estratégicas e conhecimento compartilhado entre os melhores da construção civil paulistana.</p>
        </div>
        
        <div class="grid grid--2 reveal" style="gap: var(--space-lg);">
            <div class="card" style="display: flex; gap: var(--space-lg); align-items: flex-start; padding: var(--space-xl);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: linear-gradient(135deg, var(--forca-teal), var(--forca-ocean)); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                    <i data-lucide="calendar-days" style="width:22px;height:22px;"></i>
                </div>
                <div>
                    <h3 style="font-size: var(--text-base); font-weight: 600; color: var(--brooks-navy); margin-bottom: var(--space-xs);">Eventos Exclusivos</h3>
                    <p style="font-size: var(--text-sm); color: var(--brooks-gray-500); line-height: 1.6;">Eventos presenciais em 2025 e 2026 com os maiores nomes da construção civil. Networking real, negócios reais.</p>
                </div>
            </div>
            <div class="card" style="display: flex; gap: var(--space-lg); align-items: flex-start; padding: var(--space-xl);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: linear-gradient(135deg, var(--forca-teal), var(--forca-ocean)); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                    <i data-lucide="handshake" style="width:22px;height:22px;"></i>
                </div>
                <div>
                    <h3 style="font-size: var(--text-base); font-weight: 600; color: var(--brooks-navy); margin-bottom: var(--space-xs);">Parcerias Premium</h3>
                    <p style="font-size: var(--text-sm); color: var(--brooks-gray-500); line-height: 1.6;">As melhores lojas e marcas de São Paulo como parceiras. Condições especiais exclusivas para membros do grupo.</p>
                </div>
            </div>
            <div class="card" style="display: flex; gap: var(--space-lg); align-items: flex-start; padding: var(--space-xl);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: linear-gradient(135deg, var(--forca-teal), var(--forca-ocean)); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                    <i data-lucide="lightbulb" style="width:22px;height:22px;"></i>
                </div>
                <div>
                    <h3 style="font-size: var(--text-base); font-weight: 600; color: var(--brooks-navy); margin-bottom: var(--space-xs);">Conhecimento Compartilhado</h3>
                    <p style="font-size: var(--text-sm); color: var(--brooks-gray-500); line-height: 1.6;">Troca de experiências, estratégias e soluções entre empresários que vivem os mesmos desafios diariamente.</p>
                </div>
            </div>
            <div class="card" style="display: flex; gap: var(--space-lg); align-items: flex-start; padding: var(--space-xl);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: linear-gradient(135deg, var(--forca-teal), var(--forca-ocean)); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                    <i data-lucide="building-2" style="width:22px;height:22px;"></i>
                </div>
                <div>
                    <h3 style="font-size: var(--text-base); font-weight: 600; color: var(--brooks-navy); margin-bottom: var(--space-xs);">Arquitetos & Marcas</h3>
                    <p style="font-size: var(--text-sm); color: var(--brooks-gray-500); line-height: 1.6;">Conexão direta com os melhores escritórios de arquitetura e marcas de acabamento de São Paulo.</p>
                </div>
            </div>
            <div class="card" style="display: flex; gap: var(--space-lg); align-items: flex-start; padding: var(--space-xl);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: linear-gradient(135deg, var(--forca-teal), var(--forca-ocean)); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                    <i data-lucide="shield" style="width:22px;height:22px;"></i>
                </div>
                <div>
                    <h3 style="font-size: var(--text-base); font-weight: 600; color: var(--brooks-navy); margin-bottom: var(--space-xs);">Validação de Mercado</h3>
                    <p style="font-size: var(--text-sm); color: var(--brooks-gray-500); line-height: 1.6;">O grupo validou a Vetriks, comprovou processos e elevou o padrão de gestão entre todos os membros.</p>
                </div>
            </div>
            <div class="card" style="display: flex; gap: var(--space-lg); align-items: flex-start; padding: var(--space-xl);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-lg); background: linear-gradient(135deg, var(--forca-teal), var(--forca-ocean)); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                    <i data-lucide="heart-handshake" style="width:22px;height:22px;"></i>
                </div>
                <div>
                    <h3 style="font-size: var(--text-base); font-weight: 600; color: var(--brooks-navy); margin-bottom: var(--space-xs);">Suporte Mútuo</h3>
                    <p style="font-size: var(--text-sm); color: var(--brooks-gray-500); line-height: 1.6;">Quando um membro enfrenta um problema, o grupo inteiro se mobiliza. Ninguém caminha sozinho.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Impacto -->
<section class="section" style="background: linear-gradient(160deg, #0c2233, #1a5276); color: white;">
    <div class="container text-center reveal">
        <span class="label" style="color: #5dade2;">Impacto em 2025/2026</span>
        <h2 class="headline-section" style="color: white; max-width: 600px; margin: 0 auto var(--space-2xl);">O Força Estrutural segue cada dia mais forte.</h2>
        
        <div class="counter-grid" style="max-width: 700px; margin: 0 auto;">
            <div class="counter-item">
                <div class="counter-item__number" style="color: white;" data-counter data-target="30" data-suffix="+">0</div>
                <div class="counter-item__label" style="color: rgba(255,255,255,0.5);">Empresários ativos</div>
            </div>
            <div class="counter-item">
                <div class="counter-item__number" style="color: white;" data-counter data-target="12" data-suffix="">0</div>
                <div class="counter-item__label" style="color: rgba(255,255,255,0.5);">Eventos em 2025</div>
            </div>
            <div class="counter-item">
                <div class="counter-item__number" style="color: white;" data-counter data-target="20" data-suffix="+">0</div>
                <div class="counter-item__label" style="color: rgba(255,255,255,0.5);">Marcas parceiras</div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section" style="padding: var(--space-4xl) 0;">
    <div class="container text-center reveal">
        <h2 class="headline-section">Quer fazer parte?</h2>
        <p class="subtitle subtitle--centered" style="margin-bottom: var(--space-xl);">Acompanhe o Força Estrutural nas redes sociais e saiba como participar dos próximos eventos.</p>
        <div style="display: flex; gap: var(--space-md); justify-content: center; flex-wrap: wrap;">
            <a href="https://www.instagram.com/forcaestrutural/" target="_blank" rel="noopener" class="btn btn--lg" style="background: linear-gradient(135deg, var(--forca-teal), var(--forca-ocean)); color: white;">
                <i data-lucide="instagram" style="width:18px;height:18px;"></i> Seguir no Instagram
            </a>
            <a href="/contato" class="btn btn--outline btn--lg">Entrar em contato</a>
        </div>
    </div>
</section>

<?php include ROOT_PATH . '/app/Views/site/layouts/new-footer.php'; ?>
