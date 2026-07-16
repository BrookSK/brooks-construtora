<?php
$pageTitle = 'Nossa Cultura';
$pageDescription = 'Conheça a cultura da Brooks Construtora. Nossos valores, princípios e o compromisso com a excelência na construção civil de alto padrão.';
$currentPage = 'cultura';
$bodyClass = 'page-cultura';
include ROOT_PATH . '/app/Views/site/layouts/new-header.php';
?>

<!-- Hero -->
<section style="padding-top: calc(var(--header-height) + var(--space-4xl)); padding-bottom: var(--space-5xl); background: linear-gradient(160deg, #0a0a14 0%, #111827 40%, #1e293b 100%); position: relative; overflow: hidden;">
    <div style="position: absolute; inset: 0; opacity: 0.03; background-image: repeating-linear-gradient(0deg, transparent, transparent 80px, rgba(255,255,255,0.4) 80px, rgba(255,255,255,0.4) 81px), repeating-linear-gradient(90deg, transparent, transparent 80px, rgba(255,255,255,0.4) 80px, rgba(255,255,255,0.4) 81px);"></div>
    
    <div class="container" style="position: relative; z-index: 1;">
        <div class="reveal" style="max-width: 700px;">
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; background: rgba(212, 175, 55, 0.12); border: 1px solid rgba(212, 175, 55, 0.3); border-radius: var(--radius-full); margin-bottom: var(--space-xl);">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: #d4af37;"></span>
                <span style="font-size: 11px; font-weight: 600; color: #d4af37; text-transform: uppercase; letter-spacing: 0.08em;">Manifesto de Cultura</span>
            </div>
            <h1 style="font-size: clamp(2.5rem, 5vw, 3.8rem); font-weight: 800; color: white; line-height: 1.08; margin-bottom: var(--space-lg);">
                Antes de construir obras,<br><span style="background: linear-gradient(90deg, #d4af37, #f5d76e); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">construímos cultura.</span>
            </h1>
            <p style="font-size: var(--text-lg); color: rgba(255,255,255,0.6); line-height: 1.8; margin-bottom: var(--space-xl);">
                Na Brooks Construtora, acreditamos que grandes obras são consequência de uma cultura forte. Antes de construir residências, construímos pessoas, processos e padrões capazes de transformar a construção civil.
            </p>
            <div style="display: flex; gap: var(--space-md); flex-wrap: wrap;">
                <a href="#nucleo" class="btn btn--lg" style="background: linear-gradient(135deg, #d4af37, #b8960c); color: #0a0a14; font-weight: 700;">Conheça o N.U.C.L.E.O</a>
                <a href="#manifesto" class="btn btn--lg" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.15); color: white;">Ver Manifesto</a>
            </div>
        </div>
    </div>
</section>

<!-- Origem e Convicção -->
<section class="section">
    <div class="container">
        <div class="grid grid--2 reveal" style="gap: var(--space-3xl); align-items: start;">
            <div>
                <span class="label" style="color: #d4af37;">Nossa Origem</span>
                <h2 class="headline-section">Nascemos no canteiro de obras.</h2>
                <p style="font-size: var(--text-lg); color: var(--brooks-gray-600); line-height: 1.8; margin-bottom: var(--space-md);">
                    Vivemos a realidade da construção civil e decidimos profissionalizar aquilo que, durante muitos anos, foi tratado de forma improvisada.
                </p>
                <p style="color: var(--brooks-gray-500); line-height: 1.8;">
                    Nossa história foi construída em campo, entendendo as dores do mercado e criando soluções que elevam o padrão da engenharia.
                </p>
            </div>
            <div>
                <span class="label" style="color: #d4af37;">Nossa Convicção</span>
                <h2 class="headline-section">Processo, controle e disciplina.</h2>
                <p style="font-size: var(--text-lg); color: var(--brooks-gray-600); line-height: 1.8; margin-bottom: var(--space-md);">
                    Acreditamos que crescimento sustentável somente acontece quando existem processos, controle, método e disciplina.
                </p>
                <p style="color: var(--brooks-gray-500); line-height: 1.8;">
                    A excelência não depende de talento isolado, mas de uma cultura vivida diariamente por toda a equipe.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Pilares - Grid de ícones similar ao manifesto visual -->
<section class="section" style="background: #f8f9fa;">
    <div class="container">
        <div class="section-header section-header--centered reveal">
            <span class="label" style="color: #d4af37;">Nossos Pilares</span>
            <h2 class="headline-section">O que nos sustenta.</h2>
        </div>
        
        <style>.pilares-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--space-md); } @media (max-width: 768px) { .pilares-grid { grid-template-columns: repeat(2, 1fr); } }</style>
        <div class="reveal pilares-grid">
            <div style="background: white; border-radius: var(--radius-lg); padding: var(--space-xl); text-align: center; border: 1px solid var(--brooks-gray-200);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-md); background: #111827; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-md); color: #d4af37;">
                    <i data-lucide="crosshair" style="width:22px;height:22px;"></i>
                </div>
                <h4 style="font-size: var(--text-sm); font-weight: 700; color: var(--brooks-navy); margin-bottom: 6px;">Tudo Tem Propósito</h4>
                <p style="font-size: var(--text-xs); color: var(--brooks-gray-500); line-height: 1.5;">Nada é por acaso. Tudo comunica construção.</p>
            </div>
            <div style="background: white; border-radius: var(--radius-lg); padding: var(--space-xl); text-align: center; border: 1px solid var(--brooks-gray-200);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-md); background: #111827; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-md); color: #d4af37;">
                    <i data-lucide="heart" style="width:22px;height:22px;"></i>
                </div>
                <h4 style="font-size: var(--text-sm); font-weight: 700; color: var(--brooks-navy); margin-bottom: 6px;">Deus no Centro</h4>
                <p style="font-size: var(--text-xs); color: var(--brooks-gray-500); line-height: 1.5;">Ele é a base, direção e sustentação da Brooks.</p>
            </div>
            <div style="background: white; border-radius: var(--radius-lg); padding: var(--space-xl); text-align: center; border: 1px solid var(--brooks-gray-200);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-md); background: #111827; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-md); color: #d4af37;">
                    <i data-lucide="users" style="width:22px;height:22px;"></i>
                </div>
                <h4 style="font-size: var(--text-sm); font-weight: 700; color: var(--brooks-navy); margin-bottom: 6px;">Nós Parceiros</h4>
                <p style="font-size: var(--text-xs); color: var(--brooks-gray-500); line-height: 1.5;">Honramos cada pessoa que constrói conosco.</p>
            </div>
            <div style="background: white; border-radius: var(--radius-lg); padding: var(--space-xl); text-align: center; border: 1px solid var(--brooks-gray-200);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-md); background: #111827; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-md); color: #d4af37;">
                    <i data-lucide="target" style="width:22px;height:22px;"></i>
                </div>
                <h4 style="font-size: var(--text-sm); font-weight: 700; color: var(--brooks-navy); margin-bottom: 6px;">O Que Vendemos</h4>
                <p style="font-size: var(--text-xs); color: var(--brooks-gray-500); line-height: 1.5;">Previsibilidade, controle, tempo e clareza operacional.</p>
            </div>
            <div style="background: white; border-radius: var(--radius-lg); padding: var(--space-xl); text-align: center; border: 1px solid var(--brooks-gray-200);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-md); background: #111827; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-md); color: #d4af37;">
                    <i data-lucide="alert-triangle" style="width:22px;height:22px;"></i>
                </div>
                <h4 style="font-size: var(--text-sm); font-weight: 700; color: var(--brooks-navy); margin-bottom: 6px;">Sobre Erro</h4>
                <p style="font-size: var(--text-xs); color: var(--brooks-gray-500); line-height: 1.5;">Devem ser rápidos. Não podem se tornar recorrentes.</p>
            </div>
            <div style="background: white; border-radius: var(--radius-lg); padding: var(--space-xl); text-align: center; border: 1px solid var(--brooks-gray-200);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-md); background: #111827; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-md); color: #d4af37;">
                    <i data-lucide="settings" style="width:22px;height:22px;"></i>
                </div>
                <h4 style="font-size: var(--text-sm); font-weight: 700; color: var(--brooks-navy); margin-bottom: 6px;">Gestão Brooks</h4>
                <p style="font-size: var(--text-xs); color: var(--brooks-gray-500); line-height: 1.5;">Método, registro e rastreabilidade acima do improviso.</p>
            </div>
            <div style="background: white; border-radius: var(--radius-lg); padding: var(--space-xl); text-align: center; border: 1px solid var(--brooks-gray-200);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-md); background: #111827; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-md); color: #d4af37;">
                    <i data-lucide="monitor" style="width:22px;height:22px;"></i>
                </div>
                <h4 style="font-size: var(--text-sm); font-weight: 700; color: var(--brooks-navy); margin-bottom: 6px;">Tecnologia</h4>
                <p style="font-size: var(--text-xs); color: var(--brooks-gray-500); line-height: 1.5;">Não é estética. É estrutura operacional.</p>
            </div>
            <div style="background: white; border-radius: var(--radius-lg); padding: var(--space-xl); text-align: center; border: 1px solid var(--brooks-gray-200);">
                <div style="width: 48px; height: 48px; border-radius: var(--radius-md); background: #111827; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-md); color: #d4af37;">
                    <i data-lucide="check-circle" style="width:22px;height:22px;"></i>
                </div>
                <h4 style="font-size: var(--text-sm); font-weight: 700; color: var(--brooks-navy); margin-bottom: 6px;">Vétriks</h4>
                <p style="font-size: var(--text-xs); color: var(--brooks-gray-500); line-height: 1.5;">O padrão que sustenta a cultura e a operação.</p>
            </div>
        </div>
    </div>
</section>

<!-- Limpeza e Organização -->
<section class="section" style="background: #111827; color: white;">
    <div class="container">
        <div class="reveal" style="text-align: center; max-width: 800px; margin: 0 auto;">
            <div style="display: inline-flex; align-items: center; gap: 10px; padding: 12px 24px; background: rgba(212, 175, 55, 0.1); border: 1px solid rgba(212, 175, 55, 0.25); border-radius: var(--radius-lg); margin-bottom: var(--space-xl);">
                <i data-lucide="sparkles" style="width:20px;height:20px;color:#d4af37;"></i>
                <span style="font-size: var(--text-sm); font-weight: 700; color: #d4af37; text-transform: uppercase; letter-spacing: 0.05em;">Pilar Fundamental</span>
            </div>
            <h2 style="font-size: clamp(1.8rem, 3vw, 2.5rem); font-weight: 800; margin-bottom: var(--space-lg); color: white;">Limpeza e Organização.</h2>
            <p style="font-size: var(--text-lg); color: rgba(255,255,255,0.65); line-height: 1.8; margin-bottom: var(--space-lg);">
                A organização do canteiro é um reflexo da organização da empresa. Uma obra limpa protege pessoas, aumenta a produtividade, reduz desperdícios, melhora a qualidade, diminui riscos e demonstra respeito pelo cliente e pela equipe.
            </p>
            <div style="display: flex; gap: var(--space-lg); justify-content: center; flex-wrap: wrap; margin-top: var(--space-xl);">
                <div style="text-align: center;">
                    <i data-lucide="shield" style="width:28px;height:28px;color:#d4af37;margin-bottom:8px;"></i>
                    <p style="font-size: var(--text-xs); color: rgba(255,255,255,0.6);">Protege Pessoas</p>
                </div>
                <div style="text-align: center;">
                    <i data-lucide="trending-up" style="width:28px;height:28px;color:#d4af37;margin-bottom:8px;"></i>
                    <p style="font-size: var(--text-xs); color: rgba(255,255,255,0.6);">Aumenta Produtividade</p>
                </div>
                <div style="text-align: center;">
                    <i data-lucide="award" style="width:28px;height:28px;color:#d4af37;margin-bottom:8px;"></i>
                    <p style="font-size: var(--text-xs); color: rgba(255,255,255,0.6);">Eleva a Qualidade</p>
                </div>
                <div style="text-align: center;">
                    <i data-lucide="minus-circle" style="width:28px;height:28px;color:#d4af37;margin-bottom:8px;"></i>
                    <p style="font-size: var(--text-xs); color: rgba(255,255,255,0.6);">Reduz Riscos</p>
                </div>
                <div style="text-align: center;">
                    <i data-lucide="thumbs-up" style="width:28px;height:28px;color:#d4af37;margin-bottom:8px;"></i>
                    <p style="font-size: var(--text-xs); color: rgba(255,255,255,0.6);">Reflete Nossa Cultura</p>
                </div>
            </div>
            <p style="font-size: var(--text-lg); font-weight: 700; color: #d4af37; margin-top: var(--space-xl);">
                Obra limpa, obra segura, obra que entrega mais.
            </p>
        </div>
    </div>
</section>

<!-- Manifesto Visual (imagem) -->
<section class="section" id="manifesto" style="background: #111827; padding: var(--space-4xl) 0;">
    <div class="container">
        <div class="section-header section-header--centered reveal">
            <span class="label" style="color: #d4af37;">Manifesto</span>
            <h2 class="headline-section" style="color: white;">Nosso Manifesto de Cultura.</h2>
            <p class="subtitle subtitle--centered" style="color: rgba(255,255,255,0.5);">O documento que guia cada decisão, cada obra e cada pessoa dentro da Brooks.</p>
        </div>
        
        <div class="reveal" style="max-width: 900px; margin: 0 auto; border-radius: var(--radius-xl); overflow: hidden; border: 1px solid rgba(212, 175, 55, 0.2); box-shadow: 0 25px 60px -12px rgba(0,0,0,0.5);">
            <img src="/assets/images/cultura/manifesto-cultura.jpeg" alt="Manifesto de Cultura Brooks Construtora" style="width: 100%; height: auto; display: block;" loading="lazy">
        </div>
    </div>
</section>

<!-- N.U.C.L.E.O -->
<section class="section" id="nucleo" style="background: linear-gradient(160deg, #0a0a14 0%, #111827 40%, #1e293b 100%); color: white; position: relative; overflow: hidden;">
    <div style="position: absolute; inset: 0; opacity: 0.03; background-image: repeating-linear-gradient(0deg, transparent, transparent 80px, rgba(255,255,255,0.4) 80px, rgba(255,255,255,0.4) 81px), repeating-linear-gradient(90deg, transparent, transparent 80px, rgba(255,255,255,0.4) 80px, rgba(255,255,255,0.4) 81px);"></div>
    
    <div class="container" style="position: relative; z-index: 1;">
        <div class="grid grid--2 reveal" style="gap: var(--space-3xl); align-items: center;">
            <div>
                <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; background: rgba(212, 175, 55, 0.12); border: 1px solid rgba(212, 175, 55, 0.3); border-radius: var(--radius-full); margin-bottom: var(--space-xl);">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #d4af37;"></span>
                    <span style="font-size: 11px; font-weight: 600; color: #d4af37; text-transform: uppercase; letter-spacing: 0.08em;">Diferencial Brooks</span>
                </div>
                <h2 style="font-size: clamp(2rem, 4vw, 3rem); font-weight: 800; color: white; line-height: 1.1; margin-bottom: var(--space-sm);">
                    N.U.C.L.E.O
                </h2>
                <p style="font-size: var(--text-sm); color: #d4af37; font-weight: 600; letter-spacing: 0.5px; margin-bottom: var(--space-lg);">
                    Núcleo de Unificação Criativa, Logística, Estratégia e Obra
                </p>
                <p style="font-size: var(--text-lg); color: rgba(255,255,255,0.7); line-height: 1.8; margin-bottom: var(--space-md);">
                    O N.U.C.L.E.O é o departamento de inteligência pré-construtiva da Brooks. Uma mesa de engenheiros especializados que atua antes do primeiro tijolo ser assentado, garantindo que cada projeto nasça viável, otimizado e previsível.
                </p>
                <p style="color: rgba(255,255,255,0.5); line-height: 1.8; margin-bottom: var(--space-lg); font-style: italic;">
                    "Onde o sonho encontra a viabilidade antes do primeiro traço."
                </p>
                <a href="#nucleo-processo" class="btn btn--lg" style="background: linear-gradient(135deg, #d4af37, #b8960c); color: #0a0a14; font-weight: 700;">
                    Ver processo completo <i data-lucide="arrow-down" style="width:16px;height:16px;"></i>
                </a>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md);">
                <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(212, 175, 55, 0.15); border-radius: var(--radius-lg); padding: var(--space-lg); text-align: center;">
                    <i data-lucide="hard-hat" style="width:28px;height:28px;color:#d4af37;margin-bottom:var(--space-sm);"></i>
                    <p style="font-size: var(--text-xs); color: rgba(255,255,255,0.8); font-weight: 600;">Eng. Operacional Sênior</p>
                    <p style="font-size: 10px; color: rgba(255,255,255,0.4); margin-top: 4px;">Viabilidade construtiva</p>
                </div>
                <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(212, 175, 55, 0.15); border-radius: var(--radius-lg); padding: var(--space-lg); text-align: center;">
                    <i data-lucide="columns" style="width:28px;height:28px;color:#d4af37;margin-bottom:var(--space-sm);"></i>
                    <p style="font-size: var(--text-xs); color: rgba(255,255,255,0.8); font-weight: 600;">Eng. Estrutural Calculista</p>
                    <p style="font-size: 10px; color: rgba(255,255,255,0.4); margin-top: 4px;">Fundações e sistemas</p>
                </div>
                <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(212, 175, 55, 0.15); border-radius: var(--radius-lg); padding: var(--space-lg); text-align: center;">
                    <i data-lucide="calendar" style="width:28px;height:28px;color:#d4af37;margin-bottom:var(--space-sm);"></i>
                    <p style="font-size: var(--text-xs); color: rgba(255,255,255,0.8); font-weight: 600;">Eng. de Planejamento</p>
                    <p style="font-size: 10px; color: rgba(255,255,255,0.4); margin-top: 4px;">Cronograma e logística</p>
                </div>
                <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(212, 175, 55, 0.15); border-radius: var(--radius-lg); padding: var(--space-lg); text-align: center;">
                    <i data-lucide="calculator" style="width:28px;height:28px;color:#d4af37;margin-bottom:var(--space-sm);"></i>
                    <p style="font-size: var(--text-xs); color: rgba(255,255,255,0.8); font-weight: 600;">Analista Financeiro</p>
                    <p style="font-size: 10px; color: rgba(255,255,255,0.4); margin-top: 4px;">Custos e previsibilidade</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- N.U.C.L.E.O Cenários -->
<section class="section" id="nucleo-processo">
    <div class="container">
        <div class="section-header section-header--centered reveal">
            <span class="label" style="color: #d4af37;">Cenários de Atuação</span>
            <h2 class="headline-section">O N.U.C.L.E.O atua em três momentos da jornada do cliente.</h2>
        </div>
        
        <div class="grid grid--3 reveal" style="gap: var(--space-lg);">
            <div style="background: white; border-radius: var(--radius-lg); padding: var(--space-xl); border: 1px solid var(--brooks-gray-200); border-top: 3px solid #d4af37;">
                <div style="display: flex; align-items: center; gap: var(--space-sm); margin-bottom: var(--space-md);">
                    <span style="width: 32px; height: 32px; border-radius: var(--radius-full); background: #111827; color: #d4af37; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: var(--text-sm);">A</span>
                    <h3 style="font-weight: 700; font-size: var(--text-base); color: var(--brooks-navy);">Pré-Aquisição</h3>
                </div>
                <p style="font-size: var(--text-sm); color: var(--brooks-gray-500); line-height: 1.7;">Cliente ainda não adquiriu o terreno. O N.U.C.L.E.O analisa topografia, viabilidade construtiva, restrições legais e estima custos antes da compra.</p>
            </div>
            <div style="background: white; border-radius: var(--radius-lg); padding: var(--space-xl); border: 1px solid var(--brooks-gray-200); border-top: 3px solid #d4af37;">
                <div style="display: flex; align-items: center; gap: var(--space-sm); margin-bottom: var(--space-md);">
                    <span style="width: 32px; height: 32px; border-radius: var(--radius-full); background: #111827; color: #d4af37; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: var(--text-sm);">B</span>
                    <h3 style="font-weight: 700; font-size: var(--text-base); color: var(--brooks-navy);">Pré-Projeto</h3>
                </div>
                <p style="font-size: var(--text-sm); color: var(--brooks-gray-500); line-height: 1.7;">Terreno adquirido, projeto ainda não iniciado. O N.U.C.L.E.O alinha com o arquiteto condicionantes técnicas, sistema construtivo e premissas financeiras.</p>
            </div>
            <div style="background: white; border-radius: var(--radius-lg); padding: var(--space-xl); border: 1px solid var(--brooks-gray-200); border-top: 3px solid #d4af37;">
                <div style="display: flex; align-items: center; gap: var(--space-sm); margin-bottom: var(--space-md);">
                    <span style="width: 32px; height: 32px; border-radius: var(--radius-full); background: #111827; color: #d4af37; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: var(--text-sm);">C</span>
                    <h3 style="font-weight: 700; font-size: var(--text-base); color: var(--brooks-navy);">Pré-Orçamento</h3>
                </div>
                <p style="font-size: var(--text-sm); color: var(--brooks-gray-500); line-height: 1.7;">Projeto já elaborado. Revisão técnica, compatibilização, análise estrutural, identificação de riscos e custos ocultos antes do orçamento executivo.</p>
            </div>
        </div>
    </div>
</section>

<!-- N.U.C.L.E.O Fluxo de 8 Etapas -->
<section class="section" style="background: #f8f9fa;">
    <div class="container">
        <div class="section-header section-header--centered reveal">
            <span class="label" style="color: #d4af37;">Processo</span>
            <h2 class="headline-section">8 etapas de inteligência pré-construtiva.</h2>
            <p class="subtitle subtitle--centered">Do recebimento da demanda até o encaminhamento para orçamento executivo.</p>
        </div>
        
        <div class="reveal" style="max-width: 680px; margin: 0 auto; display: grid; gap: var(--space-md);">
            <div style="display: flex; gap: var(--space-md); align-items: center; background: white; border-radius: var(--radius-md); padding: 16px 20px; border: 1px solid var(--brooks-gray-200);">
                <span style="width: 36px; height: 36px; border-radius: var(--radius-full); background: #111827; color: #d4af37; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px; flex-shrink: 0;">01</span>
                <div>
                    <p style="font-weight: 600; color: var(--brooks-navy); font-size: var(--text-sm);">Recebimento da Demanda</p>
                    <p style="font-size: 12px; color: var(--brooks-gray-500);">Identificação do cenário e registro na VÉTRIKS</p>
                </div>
            </div>
            <div style="display: flex; gap: var(--space-md); align-items: center; background: white; border-radius: var(--radius-md); padding: 16px 20px; border: 1px solid var(--brooks-gray-200);">
                <span style="width: 36px; height: 36px; border-radius: var(--radius-full); background: #111827; color: #d4af37; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px; flex-shrink: 0;">02</span>
                <div>
                    <p style="font-weight: 600; color: var(--brooks-navy); font-size: var(--text-sm);">Abertura do Dossiê</p>
                    <p style="font-size: 12px; color: var(--brooks-gray-500);">Criação do dossiê completo na plataforma VÉTRIKS</p>
                </div>
            </div>
            <div style="display: flex; gap: var(--space-md); align-items: center; background: white; border-radius: var(--radius-md); padding: 16px 20px; border: 1px solid var(--brooks-gray-200);">
                <span style="width: 36px; height: 36px; border-radius: var(--radius-full); background: #111827; color: #d4af37; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px; flex-shrink: 0;">03</span>
                <div>
                    <p style="font-weight: 600; color: var(--brooks-navy); font-size: var(--text-sm);">Coleta de Informações Técnicas</p>
                    <p style="font-size: 12px; color: var(--brooks-gray-500);">Topográfico, matrícula, projeto e programa de necessidades</p>
                </div>
            </div>
            <div style="display: flex; gap: var(--space-md); align-items: center; background: white; border-radius: var(--radius-md); padding: 16px 20px; border: 1px solid var(--brooks-gray-200);">
                <span style="width: 36px; height: 36px; border-radius: var(--radius-full); background: #111827; color: #d4af37; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px; flex-shrink: 0;">04</span>
                <div>
                    <p style="font-weight: 600; color: var(--brooks-navy); font-size: var(--text-sm);">Reunião com Arquiteto</p>
                    <p style="font-size: 12px; color: var(--brooks-gray-500);">Alinhamento técnico e premissas em até 5 dias úteis</p>
                </div>
            </div>
            <div style="display: flex; gap: var(--space-md); align-items: center; background: white; border-radius: var(--radius-md); padding: 16px 20px; border: 1px solid var(--brooks-gray-200);">
                <span style="width: 36px; height: 36px; border-radius: var(--radius-full); background: #111827; color: #d4af37; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px; flex-shrink: 0;">05</span>
                <div>
                    <p style="font-weight: 600; color: var(--brooks-navy); font-size: var(--text-sm);">Estudo Técnico Preliminar</p>
                    <p style="font-size: 12px; color: var(--brooks-gray-500);">Análise multidisciplinar: terreno, sistema construtivo, riscos e custos ocultos</p>
                </div>
            </div>
            <div style="display: flex; gap: var(--space-md); align-items: center; background: white; border-radius: var(--radius-md); padding: 16px 20px; border: 1px solid var(--brooks-gray-200);">
                <span style="width: 36px; height: 36px; border-radius: var(--radius-full); background: #111827; color: #d4af37; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px; flex-shrink: 0;">06</span>
                <div>
                    <p style="font-weight: 600; color: var(--brooks-navy); font-size: var(--text-sm);">Apresentação ao Cliente</p>
                    <p style="font-size: 12px; color: var(--brooks-gray-500);">Resultados, alternativas e ajustes com cliente e arquiteto</p>
                </div>
            </div>
            <div style="display: flex; gap: var(--space-md); align-items: center; background: white; border-radius: var(--radius-md); padding: 16px 20px; border: 1px solid var(--brooks-gray-200);">
                <span style="width: 36px; height: 36px; border-radius: var(--radius-full); background: #111827; color: #d4af37; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px; flex-shrink: 0;">07</span>
                <div>
                    <p style="font-weight: 600; color: var(--brooks-navy); font-size: var(--text-sm);">Parecer Técnico N.U.C.L.E.O</p>
                    <p style="font-size: 12px; color: var(--brooks-gray-500);">Documento formal com diretrizes, riscos e estimativa financeira</p>
                </div>
            </div>
            <div style="display: flex; gap: var(--space-md); align-items: center; background: white; border-radius: var(--radius-md); padding: 16px 20px; border: 1px solid #d4af37;">
                <span style="width: 36px; height: 36px; border-radius: var(--radius-full); background: #d4af37; color: #111827; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px; flex-shrink: 0;">08</span>
                <div>
                    <p style="font-weight: 600; color: var(--brooks-navy); font-size: var(--text-sm);">Encaminhamento para Orçamento</p>
                    <p style="font-size: 12px; color: var(--brooks-gray-500);">Pacote completo com premissas que garantem precisão e previsibilidade</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Propósito e Compromisso -->
<section class="section">
    <div class="container">
        <div class="reveal" style="text-align: center; max-width: 750px; margin: 0 auto;">
            <span class="label" style="color: #d4af37;">Nosso Propósito</span>
            <h2 class="headline-section">Não levantamos apenas edificações. Construímos padrão.</h2>
            <p style="font-size: var(--text-lg); color: var(--brooks-gray-600); line-height: 1.8; margin-bottom: var(--space-lg);">
                Construímos confiança, relacionamentos, processos e um novo padrão para a construção civil. A Brooks não busca ser apenas mais uma construtora. Trabalhamos diariamente para ser referência em gestão, tecnologia, organização e excelência operacional.
            </p>
            <p style="font-size: var(--text-xl); font-weight: 800; color: var(--brooks-navy);">
                Brooks não é apenas uma empresa. É um padrão de engenharia.
            </p>
        </div>
    </div>
</section>

<!-- Compromisso -->
<section style="background: #111827; color: white; padding: var(--space-4xl) 0;">
    <div class="container">
        <div class="reveal" style="text-align: center; max-width: 700px; margin: 0 auto;">
            <i data-lucide="heart" style="width:36px;height:36px;color:#d4af37;margin-bottom:var(--space-lg);"></i>
            <p style="font-size: var(--text-xl); font-weight: 700; color: white; margin-bottom: var(--space-lg); line-height: 1.4;">
                Se você está aqui, existe um motivo que te fez ficar. E a partir de agora, você carrega uma responsabilidade.
            </p>
            <p style="color: rgba(255,255,255,0.6); line-height: 1.8; font-size: var(--text-base);">
                Proteger nossa cultura, honrar nossos valores e contribuir para que cada obra represente o mais alto padrão de qualidade, organização e respeito pelas pessoas. Porque, para nós, construir vai muito além de erguer estruturas: é deixar um legado de excelência.
            </p>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section section--gray" style="padding: var(--space-4xl) 0;">
    <div class="container text-center reveal">
        <h2 class="headline-subsection">Quer fazer parte dessa cultura?</h2>
        <p class="subtitle subtitle--centered" style="margin-bottom: var(--space-xl);">Entre em contato e descubra como a Brooks pode transformar o seu projeto com excelência, tecnologia e gestão de alto padrão.</p>
        <a href="/contato" class="btn btn--primary btn--lg">Solicitar Orçamento</a>
    </div>
</section>

<?php include ROOT_PATH . '/app/Views/site/layouts/new-footer.php'; ?>
