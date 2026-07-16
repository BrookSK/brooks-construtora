<?php
$pageTitle = 'Matterport — Tour Virtual 3D';
$pageDescription = 'Matterport - Tecnologia de gêmeos digitais e tours virtuais 3D que a Brooks utiliza em todos os seus projetos. Transparência total para clientes e equipes.';
$currentPage = 'matterport';
$bodyClass = 'page-matterport';
include ROOT_PATH . '/app/Views/site/layouts/new-header.php';
?>

<!-- Hero -->
<section style="padding-top: calc(var(--header-height) + var(--space-4xl)); padding-bottom: var(--space-5xl); background: linear-gradient(160deg, #0a0a1a 0%, #1a1a3e 40%, #2d1b69 100%); position: relative; overflow: hidden;">
    <!-- Glow effect -->
    <div style="position: absolute; top: 30%; left: 50%; width: 600px; height: 600px; background: radial-gradient(circle, rgba(123, 97, 255, 0.08), transparent 70%); transform: translate(-50%, -50%);"></div>
    <div style="position: absolute; bottom: -200px; right: -100px; width: 500px; height: 500px; border-radius: 50%; border: 1px solid rgba(123, 97, 255, 0.06);"></div>
    
    <div class="container" style="position: relative; z-index: 1;">
        <div class="grid grid--2 reveal" style="align-items: center; gap: var(--space-3xl);">
            <div>
                <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; background: rgba(123, 97, 255, 0.15); border: 1px solid rgba(123, 97, 255, 0.3); border-radius: var(--radius-full); margin-bottom: var(--space-xl);">
                    <i data-lucide="scan" style="width:14px;height:14px;color:#7b61ff;"></i>
                    <span style="font-size: 11px; font-weight: 600; color: #7b61ff; text-transform: uppercase; letter-spacing: 0.08em;">Tour Virtual 3D</span>
                </div>
                <h1 style="font-size: clamp(2.5rem, 5vw, 3.5rem); font-weight: 800; color: white; line-height: 1.1; margin-bottom: var(--space-lg);">
                    Seus olhos dentro da obra, <span style="color: #7b61ff;">a qualquer momento.</span>
                </h1>
                <p style="font-size: var(--text-lg); color: rgba(255,255,255,0.65); line-height: 1.8; margin-bottom: var(--space-xl);">
                    Muito mais do que utilizar a tecnologia Matterport, nós temos o equipamento na empresa. A Brooks cria gêmeos digitais de todos os seus projetos com tours virtuais 3D completos que permitem ao cliente acompanhar cada etapa da obra com total transparência, de qualquer lugar do mundo.
                </p>
                <div style="display: flex; gap: var(--space-md); flex-wrap: wrap;">
                    <a href="/projetos" class="btn btn--lg" style="background: linear-gradient(135deg, #7b61ff, #5a3fd4); color: white;">Ver nossos projetos</a>
                    <a href="#como-funciona" class="btn btn--lg" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); color: white;">Como funciona</a>
                </div>
            </div>
            <div style="position: relative;">
                <div style="background: linear-gradient(135deg, rgba(123, 97, 255, 0.1), rgba(45, 27, 105, 0.3)); border: 1px solid rgba(123, 97, 255, 0.2); border-radius: var(--radius-xl); padding: var(--space-2xl); text-align: center; aspect-ratio: 16/10; display: flex; align-items: center; justify-content: center; flex-direction: column; gap: var(--space-md);">
                    <i data-lucide="rotate-3d" style="width:64px;height:64px;color:rgba(123, 97, 255, 0.6);"></i>
                    <p style="color: rgba(255,255,255,0.5); font-size: var(--text-sm);">Modelo 3D interativo</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- O que é Matterport -->
<section class="section" id="como-funciona">
    <div class="container">
        <div class="grid grid--2 reveal" style="align-items: start; gap: var(--space-3xl);">
            <div>
                <span class="label" style="color: #7b61ff;">O que é</span>
                <h2 class="headline-section">Matterport: a realidade capturada em três dimensões.</h2>
                <p style="font-size: var(--text-lg); color: var(--brooks-gray-600); line-height: 1.8; margin-bottom: var(--space-sm);">
                    A Matterport é uma plataforma líder mundial em captura de espaços 3D e criação de gêmeos digitais. Com câmeras especializadas e inteligência artificial, ela transforma qualquer ambiente físico em um modelo digital navegável, preciso e imersivo.
                </p>
                <p style="color: var(--brooks-gray-500); line-height: 1.8; margin-bottom: var(--space-sm);">
                    Na Brooks, utilizamos essa tecnologia em todas as nossas obras de alto padrão. Desde o início da construção até a entrega das chaves, cada etapa é escaneada e disponibilizada em um tour virtual completo.
                </p>
                <p style="color: var(--brooks-gray-500); line-height: 1.8; margin-bottom: var(--space-sm);">
                    Isso significa que nossos clientes podem acompanhar o progresso da sua obra em tempo real, sem precisar visitar o canteiro. Basta abrir o link e navegar livremente pelo espaço em 3D, como se estivesse lá.
                </p>
                <p style="color: var(--brooks-gray-500); line-height: 1.8;">
                    Mais do que transparência, é confiança. O cliente vê cada detalhe, cada acabamento, cada fase executada com a qualidade que a Brooks entrega.
                </p>
            </div>
            <div style="position: sticky; top: 120px; margin-top: 80px;">
                <div style="background: linear-gradient(135deg, #f3f0ff, #ede8ff); border-radius: var(--radius-xl); padding: var(--space-2xl);">
                    <div style="display: grid; gap: var(--space-xl);">
                        <div style="display: flex; align-items: center; gap: var(--space-md);">
                            <div style="width: 48px; height: 48px; border-radius: var(--radius-full); background: #7b61ff; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                                <i data-lucide="camera" style="width:20px;height:20px;"></i>
                            </div>
                            <div>
                                <p style="font-weight: 600; color: var(--brooks-navy); font-size: var(--text-sm);">Escaneamento</p>
                                <p style="font-size: var(--text-xs); color: var(--brooks-gray-500);">Captura 3D com câmera Matterport Pro</p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: var(--space-md);">
                            <div style="width: 48px; height: 48px; border-radius: var(--radius-full); background: #6a4fd6; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                                <i data-lucide="cloud" style="width:20px;height:20px;"></i>
                            </div>
                            <div>
                                <p style="font-weight: 600; color: var(--brooks-navy); font-size: var(--text-sm);">Processamento</p>
                                <p style="font-size: var(--text-xs); color: var(--brooks-gray-500);">IA transforma scans em modelo 3D navegável</p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: var(--space-md);">
                            <div style="width: 48px; height: 48px; border-radius: var(--radius-full); background: #5a3fd4; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                                <i data-lucide="share-2" style="width:20px;height:20px;"></i>
                            </div>
                            <div>
                                <p style="font-weight: 600; color: var(--brooks-navy); font-size: var(--text-sm);">Compartilhamento</p>
                                <p style="font-size: var(--text-xs); color: var(--brooks-gray-500);">Link exclusivo enviado ao cliente</p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: var(--space-md);">
                            <div style="width: 48px; height: 48px; border-radius: var(--radius-full); background: #4a2fb2; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                                <i data-lucide="eye" style="width:20px;height:20px;"></i>
                            </div>
                            <div>
                                <p style="font-weight: 600; color: var(--brooks-navy); font-size: var(--text-sm);">Acompanhamento</p>
                                <p style="font-size: var(--text-xs); color: var(--brooks-gray-500);">Cliente navega pela obra de qualquer lugar</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Vídeo -->
<section class="section section--gray" id="video">
    <div class="container">
        <div class="section-header section-header--centered reveal">
            <span class="label" style="color: #7b61ff;">Veja na prática</span>
            <h2 class="headline-section">Como a Brooks usa a Matterport.</h2>
            <p class="subtitle subtitle--centered">Assista ao vídeo e entenda como essa tecnologia transforma a experiência dos nossos clientes.</p>
        </div>
        
        <div class="reveal" style="max-width: 900px; margin: 0 auto;">
            <div style="position: relative; background: linear-gradient(135deg, #1a1a3e, #2d1b69); border-radius: var(--radius-xl); aspect-ratio: 16/9; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px solid rgba(123, 97, 255, 0.2);">
                <!-- Placeholder para o vídeo — substituir pelo embed quando disponível -->
                <div style="text-align: center; padding: var(--space-2xl);">
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: rgba(123, 97, 255, 0.2); border: 2px solid rgba(123, 97, 255, 0.4); display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-lg);">
                        <i data-lucide="play" style="width:32px;height:32px;color:#7b61ff;margin-left:4px;"></i>
                    </div>
                    <p style="color: rgba(255,255,255,0.6); font-size: var(--text-base); margin-bottom: var(--space-xs);">Vídeo em breve</p>
                    <p style="color: rgba(255,255,255,0.35); font-size: var(--text-sm);">Estamos produzindo um vídeo mostrando como utilizamos a Matterport nos nossos projetos.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Benefícios -->
<section class="section">
    <div class="container">
        <div class="section-header section-header--centered reveal">
            <span class="label" style="color: #7b61ff;">Benefícios</span>
            <h2 class="headline-section">Por que usamos Matterport em todas as nossas obras.</h2>
        </div>
        
        <div class="grid grid--3 reveal" style="gap: var(--space-lg);">
            <div class="card" style="text-align: center; border-top: 3px solid #7b61ff;">
                <div style="width: 64px; height: 64px; border-radius: var(--radius-full); background: linear-gradient(135deg, #7b61ff, #5a3fd4); display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-lg); color: white;">
                    <i data-lucide="eye" style="width:28px;height:28px;"></i>
                </div>
                <h3 class="card__title">Transparência Total</h3>
                <p class="card__text">O cliente acompanha cada fase da obra sem precisar estar presente. Total visibilidade do que está sendo executado.</p>
            </div>
            <div class="card" style="text-align: center; border-top: 3px solid #6a4fd6;">
                <div style="width: 64px; height: 64px; border-radius: var(--radius-full); background: linear-gradient(135deg, #6a4fd6, #4a2fb2); display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-lg); color: white;">
                    <i data-lucide="history" style="width:28px;height:28px;"></i>
                </div>
                <h3 class="card__title">Histórico Completo</h3>
                <p class="card__text">Cada escaneamento fica registrado. O cliente pode voltar no tempo e ver como a obra estava em qualquer data.</p>
            </div>
            <div class="card" style="text-align: center; border-top: 3px solid #5a3fd4;">
                <div style="width: 64px; height: 64px; border-radius: var(--radius-full); background: linear-gradient(135deg, #5a3fd4, #3d2a99); display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-lg); color: white;">
                    <i data-lucide="ruler" style="width:28px;height:28px;"></i>
                </div>
                <h3 class="card__title">Medições Precisas</h3>
                <p class="card__text">Medidas reais dentro do modelo 3D. Útil para arquitetos, projetistas e para o próprio planejamento da obra.</p>
            </div>
            <div class="card" style="text-align: center; border-top: 3px solid #7b61ff;">
                <div style="width: 64px; height: 64px; border-radius: var(--radius-full); background: linear-gradient(135deg, #7b61ff, #5a3fd4); display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-lg); color: white;">
                    <i data-lucide="globe" style="width:28px;height:28px;"></i>
                </div>
                <h3 class="card__title">Acesso de Qualquer Lugar</h3>
                <p class="card__text">Basta um link. Navegue pela obra pelo computador, tablet ou celular, de qualquer parte do mundo.</p>
            </div>
            <div class="card" style="text-align: center; border-top: 3px solid #6a4fd6;">
                <div style="width: 64px; height: 64px; border-radius: var(--radius-full); background: linear-gradient(135deg, #6a4fd6, #4a2fb2); display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-lg); color: white;">
                    <i data-lucide="shield-check" style="width:28px;height:28px;"></i>
                </div>
                <h3 class="card__title">Documentação Técnica</h3>
                <p class="card__text">Registros visuais de instalações elétricas, hidráulicas e estruturais antes do fechamento das paredes.</p>
            </div>
            <div class="card" style="text-align: center; border-top: 3px solid #5a3fd4;">
                <div style="width: 64px; height: 64px; border-radius: var(--radius-full); background: linear-gradient(135deg, #5a3fd4, #3d2a99); display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-lg); color: white;">
                    <i data-lucide="sparkles" style="width:28px;height:28px;"></i>
                </div>
                <h3 class="card__title">Experiência Premium</h3>
                <p class="card__text">A experiência de acompanhar uma obra Brooks é diferenciada. O tour virtual é parte desse cuidado com o cliente.</p>
            </div>
        </div>
    </div>
</section>

<!-- Como aplicamos nos projetos -->
<section class="section" style="background: linear-gradient(160deg, #0a0a1a, #2d1b69); color: white;">
    <div class="container">
        <div class="grid grid--2 reveal" style="align-items: center; gap: var(--space-3xl);">
            <div>
                <span class="label" style="color: #7b61ff;">Na prática</span>
                <h2 class="headline-section" style="color: white;">Aplicado em todos os nossos projetos.</h2>
                <p style="color: rgba(255,255,255,0.6); line-height: 1.8; margin-bottom: var(--space-lg);">
                    Cada projeto da Brooks recebe escaneamentos periódicos com a Matterport. A frequência depende da fase da obra, mas geralmente realizamos capturas mensais ou a cada etapa significativa concluída.
                </p>
                <p style="color: rgba(255,255,255,0.5); line-height: 1.8; margin-bottom: var(--space-lg);">
                    O resultado é um acervo digital completo da evolução do imóvel, do esqueleto estrutural ao acabamento final. Uma garantia visual que fica registrada para sempre.
                </p>
                <a href="/contato" class="btn btn--lg" style="background: rgba(123, 97, 255, 0.2); border: 1px solid rgba(123, 97, 255, 0.4); color: white;">
                    <i data-lucide="mail" style="width:18px;height:18px;"></i> Solicitar orçamento
                </a>
            </div>
            <div style="text-align: center;">
                <div style="display: inline-grid; grid-template-columns: 1fr 1fr; gap: var(--space-md);">
                    <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: var(--radius-lg); padding: var(--space-xl); text-align: center;">
                        <i data-lucide="box" style="width:32px;height:32px;color:#7b61ff;margin-bottom:var(--space-sm);"></i>
                        <p style="font-size: var(--text-xs); color: rgba(255,255,255,0.6);">Gêmeo Digital</p>
                    </div>
                    <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: var(--radius-lg); padding: var(--space-xl); text-align: center;">
                        <i data-lucide="move-3d" style="width:32px;height:32px;color:#7b61ff;margin-bottom:var(--space-sm);"></i>
                        <p style="font-size: var(--text-xs); color: rgba(255,255,255,0.6);">Navegação livre</p>
                    </div>
                    <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: var(--radius-lg); padding: var(--space-xl); text-align: center;">
                        <i data-lucide="layers" style="width:32px;height:32px;color:#7b61ff;margin-bottom:var(--space-sm);"></i>
                        <p style="font-size: var(--text-xs); color: rgba(255,255,255,0.6);">Múltiplas fases</p>
                    </div>
                    <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: var(--radius-lg); padding: var(--space-xl); text-align: center;">
                        <i data-lucide="lock" style="width:32px;height:32px;color:#7b61ff;margin-bottom:var(--space-sm);"></i>
                        <p style="font-size: var(--text-xs); color: rgba(255,255,255,0.6);">Acesso exclusivo</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Diferencial -->
<section class="section">
    <div class="container">
        <div class="grid grid--2 reveal" style="align-items: center; gap: var(--space-3xl);">
            <div style="background: linear-gradient(160deg, #2d1b69, #1a1a3e); border-radius: var(--radius-xl); padding: var(--space-3xl); color: white;">
                <i data-lucide="diamond" style="width:40px;height:40px;color:#7b61ff;margin-bottom:var(--space-lg);"></i>
                <h3 style="font-size: var(--text-2xl); font-weight: 700; margin-bottom: var(--space-md); color: white;">Um diferencial que poucos oferecem.</h3>
                <p style="color: rgba(255,255,255,0.7); line-height: 1.8; margin-bottom: var(--space-lg);">
                    Enquanto a maioria das construtoras ainda trabalha com fotos planas e relatórios genéricos, a Brooks oferece ao cliente uma imersão completa em sua obra. A Matterport eleva o padrão de comunicação e confiança entre construtora e cliente.
                </p>
                <p style="color: rgba(255,255,255,0.5); font-size: var(--text-sm); line-height: 1.7;">
                    Esse investimento em tecnologia de ponta reflete o compromisso da Brooks com a excelência em cada detalhe da experiência do cliente.
                </p>
            </div>
            <div>
                <h3 class="headline-subsection" style="color: var(--brooks-navy);">O que nosso cliente ganha</h3>
                <div style="display: grid; gap: var(--space-lg); margin-top: var(--space-lg);">
                    <div style="display: flex; gap: var(--space-md); align-items: flex-start;">
                        <i data-lucide="check-circle" style="width:20px;height:20px;color:#7b61ff;flex-shrink:0;margin-top:2px;"></i>
                        <p style="font-size: var(--text-sm); color: var(--brooks-gray-700); line-height: 1.6;"><strong style="color: var(--brooks-navy);">Acompanhamento sem visitas</strong>, veja a obra evoluir sem sair de casa ou do escritório.</p>
                    </div>
                    <div style="display: flex; gap: var(--space-md); align-items: flex-start;">
                        <i data-lucide="check-circle" style="width:20px;height:20px;color:#7b61ff;flex-shrink:0;margin-top:2px;"></i>
                        <p style="font-size: var(--text-sm); color: var(--brooks-gray-700); line-height: 1.6;"><strong style="color: var(--brooks-navy);">Confiança e segurança</strong>, cada etapa documentada com precisão milimétrica em 3D.</p>
                    </div>
                    <div style="display: flex; gap: var(--space-md); align-items: flex-start;">
                        <i data-lucide="check-circle" style="width:20px;height:20px;color:#7b61ff;flex-shrink:0;margin-top:2px;"></i>
                        <p style="font-size: var(--text-sm); color: var(--brooks-gray-700); line-height: 1.6;"><strong style="color: var(--brooks-navy);">Compartilhe com quem quiser</strong>, envie o link para familiares, arquitetos ou designers de interiores.</p>
                    </div>
                    <div style="display: flex; gap: var(--space-md); align-items: flex-start;">
                        <i data-lucide="check-circle" style="width:20px;height:20px;color:#7b61ff;flex-shrink:0;margin-top:2px;"></i>
                        <p style="font-size: var(--text-sm); color: var(--brooks-gray-700); line-height: 1.6;"><strong style="color: var(--brooks-navy);">Registro permanente</strong> — mesmo após a entrega, o tour fica disponível como memória digital do imóvel.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Tours Virtuais -->
<section class="section">
    <div class="container">
        <div class="section-header section-header--centered reveal">
            <span class="label" style="color: #7b61ff;">Tours Virtuais</span>
            <h2 class="headline-section">Navegue pelos nossos projetos em 3D.</h2>
            <p class="subtitle subtitle--centered">Clique em um dos tours abaixo e explore nossos projetos como se estivesse dentro da obra.</p>
        </div>
        
        <div class="reveal" style="display: flex; gap: var(--space-lg); justify-content: center; flex-wrap: wrap; margin-top: var(--space-xl);">
            <a href="https://discover.matterport.com/space/kVTh35mWgjR" target="_blank" rel="noopener noreferrer" class="btn btn--lg" style="background: linear-gradient(135deg, #7b61ff, #5a3fd4); color: white; display: inline-flex; align-items: center; gap: 8px;">
                <i data-lucide="rotate-3d" style="width:18px;height:18px;"></i> Tour Virtual 1
            </a>
            <a href="https://discover.matterport.com/space/tUk5LhVhaZM" target="_blank" rel="noopener noreferrer" class="btn btn--lg" style="background: linear-gradient(135deg, #6a4fd6, #4a2fb2); color: white; display: inline-flex; align-items: center; gap: 8px;">
                <i data-lucide="rotate-3d" style="width:18px;height:18px;"></i> Tour Virtual 2
            </a>
            <a href="https://discover.matterport.com/space/qTgNv6b16gp" target="_blank" rel="noopener noreferrer" class="btn btn--lg" style="background: linear-gradient(135deg, #5a3fd4, #3d2a99); color: white; display: inline-flex; align-items: center; gap: 8px;">
                <i data-lucide="rotate-3d" style="width:18px;height:18px;"></i> Tour Virtual 3
            </a>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section section--gray" style="padding: var(--space-4xl) 0;">
    <div class="container text-center reveal">
        <h2 class="headline-section">Quer conhecer a experiência Matterport?</h2>
        <p class="subtitle subtitle--centered" style="margin-bottom: var(--space-xl);">Entre em contato e solicite acesso a um tour virtual de um dos nossos projetos. Navegue em 3D e veja o padrão Brooks por dentro.</p>
        <div style="display: flex; gap: var(--space-md); justify-content: center; flex-wrap: wrap;">
            <a href="/contato" class="btn btn--lg" style="background: linear-gradient(135deg, #7b61ff, #5a3fd4); color: white;">Solicitar acesso ao tour</a>
            <a href="/projetos" class="btn btn--outline btn--lg">Ver nossos projetos</a>
        </div>
    </div>
</section>

<?php include ROOT_PATH . '/app/Views/site/layouts/new-footer.php'; ?>
