<?php
$pageTitle = 'Nossa Cultura';
$pageDescription = 'Conheça a cultura da Brooks Construtora. Nossos valores, princípios e o compromisso com a excelência na construção civil de alto padrão.';
$currentPage = 'cultura';
$bodyClass = 'page-cultura';
include ROOT_PATH . '/app/Views/site/layouts/new-header.php';
?>

<style>
.cultura-hero { padding-top: calc(var(--header-height) + var(--space-4xl)); padding-bottom: var(--space-5xl); background: linear-gradient(160deg, #0a0a14 0%, #111827 50%, #1e293b 100%); position: relative; overflow: hidden; }
.cultura-hero::before { content:''; position:absolute; inset:0; background: radial-gradient(ellipse at 30% 20%, rgba(212,175,55,0.04) 0%, transparent 60%); }
.gold-accent { color: #d4af37; }
.gold-gradient { background: linear-gradient(135deg, #d4af37, #f5d76e); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.pilares-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--space-md); }
@media (max-width: 768px) { .pilares-grid { grid-template-columns: repeat(2, 1fr); } }
.pilar-card { background: #fafafa; border-radius: var(--radius-lg); padding: var(--space-lg); text-align: center; border: 1px solid #e5e7eb; transition: transform 0.2s, box-shadow 0.2s; }
.pilar-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.06); }
.pilar-icon { width: 44px; height: 44px; border-radius: 10px; background: #111827; display: flex; align-items: center; justify-content: center; margin: 0 auto var(--space-sm); color: #d4af37; }
.dark-section { background: #111827; color: white; }
.etapa-item { display: flex; gap: var(--space-md); align-items: center; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); border-radius: var(--radius-md); padding: 14px 18px; transition: border-color 0.2s; }
.etapa-item:hover { border-color: rgba(212,175,55,0.3); }
.etapa-num { width: 32px; height: 32px; border-radius: var(--radius-full); background: rgba(212,175,55,0.1); border: 1px solid rgba(212,175,55,0.3); color: #d4af37; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px; flex-shrink: 0; }
.nucleo-card { background: rgba(255,255,255,0.03); border: 1px solid rgba(212,175,55,0.12); border-radius: var(--radius-lg); padding: var(--space-lg); text-align: center; transition: border-color 0.2s; }
.nucleo-card:hover { border-color: rgba(212,175,55,0.35); }
</style>

<!-- Hero -->
<section class="cultura-hero">
    <div class="container" style="position: relative; z-index: 1;">
        <div class="reveal" style="max-width: 680px;">
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 12px; background: rgba(212,175,55,0.08); border: 1px solid rgba(212,175,55,0.2); border-radius: var(--radius-full); margin-bottom: var(--space-xl);">
                <span style="width: 6px; height: 6px; border-radius: 50%; background: #d4af37;"></span>
                <span style="font-size: 10px; font-weight: 600; color: #d4af37; text-transform: uppercase; letter-spacing: 0.1em;">Manifesto de Cultura</span>
            </div>
            <h1 style="font-size: clamp(2.5rem, 5vw, 3.8rem); font-weight: 800; color: white; line-height: 1.08; margin-bottom: var(--space-lg);">
                Antes de construir obras,<br><span class="gold-gradient">construímos cultura.</span>
            </h1>
            <p style="font-size: var(--text-lg); color: rgba(255,255,255,0.55); line-height: 1.8; margin-bottom: var(--space-xl);">
                Na Brooks Construtora, acreditamos que grandes obras são consequência de uma cultura forte. Antes de construir residências, construímos pessoas, processos e padrões capazes de transformar a construção civil.
            </p>
            <div style="display: flex; gap: var(--space-md); flex-wrap: wrap;">
                <a href="#nucleo" class="btn btn--lg" style="background: #d4af37; color: #111827; font-weight: 700; border: none;">Conheça o N.U.C.L.E.O</a>
                <a href="#manifesto" class="btn btn--lg" style="background: transparent; border: 1px solid rgba(255,255,255,0.12); color: rgba(255,255,255,0.7);">Ver Manifesto</a>
            </div>
        </div>
    </div>
</section>

<!-- Origem e Convicção -->
<section class="section">
    <div class="container">
        <div class="grid grid--2 reveal" style="gap: var(--space-3xl); align-items: start;">
            <div>
                <span class="label gold-accent">Nossa Origem</span>
                <h2 class="headline-section">Nascemos no canteiro de obras.</h2>
                <p style="font-size: var(--text-lg); color: var(--brooks-gray-600); line-height: 1.8; margin-bottom: var(--space-md);">
                    Vivemos a realidade da construção civil e decidimos profissionalizar aquilo que, durante muitos anos, foi tratado de forma improvisada.
                </p>
                <p style="color: var(--brooks-gray-500); line-height: 1.8;">
                    Nossa história foi construída em campo, entendendo as dores do mercado e criando soluções que elevam o padrão da engenharia.
                </p>
            </div>
            <div>
                <span class="label gold-accent">Nossa Convicção</span>
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

<!-- Pilares -->
<section class="section" style="background: #f5f5f5;">
    <div class="container">
        <div class="section-header section-header--centered reveal">
            <span class="label gold-accent">Nossos Pilares</span>
            <h2 class="headline-section">O que nos sustenta.</h2>
        </div>
        
        <div class="reveal pilares-grid">
            <div class="pilar-card">
                <div class="pilar-icon"><i data-lucide="crosshair" style="width:20px;height:20px;"></i></div>
                <h4 style="font-size: 13px; font-weight: 700; color: #111827; margin-bottom: 4px;">Tudo Tem Propósito</h4>
                <p style="font-size: 11px; color: #6b7280; line-height: 1.5;">Nada é por acaso. Tudo comunica construção.</p>
            </div>
            <div class="pilar-card">
                <div class="pilar-icon"><i data-lucide="heart" style="width:20px;height:20px;"></i></div>
                <h4 style="font-size: 13px; font-weight: 700; color: #111827; margin-bottom: 4px;">Deus no Centro</h4>
                <p style="font-size: 11px; color: #6b7280; line-height: 1.5;">Ele é a base, direção e sustentação da Brooks.</p>
            </div>
            <div class="pilar-card">
                <div class="pilar-icon"><i data-lucide="users" style="width:20px;height:20px;"></i></div>
                <h4 style="font-size: 13px; font-weight: 700; color: #111827; margin-bottom: 4px;">Nós Parceiros</h4>
                <p style="font-size: 11px; color: #6b7280; line-height: 1.5;">Honramos cada pessoa que constrói conosco.</p>
            </div>
            <div class="pilar-card">
                <div class="pilar-icon"><i data-lucide="target" style="width:20px;height:20px;"></i></div>
                <h4 style="font-size: 13px; font-weight: 700; color: #111827; margin-bottom: 4px;">O Que Vendemos</h4>
                <p style="font-size: 11px; color: #6b7280; line-height: 1.5;">Previsibilidade, controle, tempo e clareza.</p>
            </div>
            <div class="pilar-card">
                <div class="pilar-icon"><i data-lucide="alert-triangle" style="width:20px;height:20px;"></i></div>
                <h4 style="font-size: 13px; font-weight: 700; color: #111827; margin-bottom: 4px;">Sobre Erro</h4>
                <p style="font-size: 11px; color: #6b7280; line-height: 1.5;">Rápidos na correção. Jamais recorrentes.</p>
            </div>
            <div class="pilar-card">
                <div class="pilar-icon"><i data-lucide="settings" style="width:20px;height:20px;"></i></div>
                <h4 style="font-size: 13px; font-weight: 700; color: #111827; margin-bottom: 4px;">Gestão Brooks</h4>
                <p style="font-size: 11px; color: #6b7280; line-height: 1.5;">Método e rastreabilidade acima do improviso.</p>
            </div>
            <div class="pilar-card">
                <div class="pilar-icon"><i data-lucide="monitor" style="width:20px;height:20px;"></i></div>
                <h4 style="font-size: 13px; font-weight: 700; color: #111827; margin-bottom: 4px;">Tecnologia</h4>
                <p style="font-size: 11px; color: #6b7280; line-height: 1.5;">Não é estética. É estrutura operacional.</p>
            </div>
            <div class="pilar-card">
                <div class="pilar-icon"><i data-lucide="check-circle" style="width:20px;height:20px;"></i></div>
                <h4 style="font-size: 13px; font-weight: 700; color: #111827; margin-bottom: 4px;">Vétriks</h4>
                <p style="font-size: 11px; color: #6b7280; line-height: 1.5;">O padrão que sustenta cultura e operação.</p>
            </div>
        </div>
    </div>
</section>

<!-- Limpeza e Organização - Card escuro isolado em fundo claro -->
<section class="section">
    <div class="container">
        <div class="reveal" style="background: #111827; border-radius: var(--radius-xl); padding: var(--space-3xl); text-align: center; max-width: 900px; margin: 0 auto;">
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 14px; background: rgba(212,175,55,0.1); border: 1px solid rgba(212,175,55,0.2); border-radius: var(--radius-full); margin-bottom: var(--space-lg);">
                <i data-lucide="sparkles" style="width:14px;height:14px;color:#d4af37;"></i>
                <span style="font-size: 10px; font-weight: 600; color: #d4af37; text-transform: uppercase; letter-spacing: 0.08em;">Pilar Fundamental</span>
            </div>
            <h2 style="font-size: clamp(1.5rem, 3vw, 2rem); font-weight: 800; color: white; margin-bottom: var(--space-md);">Limpeza e Organização.</h2>
            <p style="font-size: var(--text-base); color: rgba(255,255,255,0.55); line-height: 1.8; max-width: 600px; margin: 0 auto var(--space-xl);">
                A organização do canteiro é um reflexo da organização da empresa. Uma obra limpa protege pessoas, aumenta a produtividade, reduz desperdícios e demonstra respeito pelo cliente.
            </p>
            <div style="display: flex; gap: var(--space-xl); justify-content: center; flex-wrap: wrap;">
                <div style="text-align: center;"><i data-lucide="shield" style="width:22px;height:22px;color:#d4af37;margin-bottom:4px;"></i><p style="font-size: 10px; color: rgba(255,255,255,0.5);">Protege</p></div>
                <div style="text-align: center;"><i data-lucide="trending-up" style="width:22px;height:22px;color:#d4af37;margin-bottom:4px;"></i><p style="font-size: 10px; color: rgba(255,255,255,0.5);">Produtividade</p></div>
                <div style="text-align: center;"><i data-lucide="award" style="width:22px;height:22px;color:#d4af37;margin-bottom:4px;"></i><p style="font-size: 10px; color: rgba(255,255,255,0.5);">Qualidade</p></div>
                <div style="text-align: center;"><i data-lucide="minus-circle" style="width:22px;height:22px;color:#d4af37;margin-bottom:4px;"></i><p style="font-size: 10px; color: rgba(255,255,255,0.5);">Reduz Riscos</p></div>
                <div style="text-align: center;"><i data-lucide="thumbs-up" style="width:22px;height:22px;color:#d4af37;margin-bottom:4px;"></i><p style="font-size: 10px; color: rgba(255,255,255,0.5);">Cultura</p></div>
            </div>
            <p style="font-size: var(--text-sm); font-weight: 600; color: #d4af37; margin-top: var(--space-lg);">Obra limpa, obra segura, obra que entrega mais.</p>
        </div>
    </div>
</section>

<!-- Manifesto -->
<section class="section dark-section" id="manifesto">
    <div class="container">
        <div class="section-header section-header--centered reveal">
            <span class="label gold-accent">Manifesto</span>
            <h2 class="headline-section" style="color: white;">Nosso Manifesto de Cultura.</h2>
            <p class="subtitle subtitle--centered" style="color: rgba(255,255,255,0.4);">O documento que guia cada decisão, cada obra e cada pessoa dentro da Brooks.</p>
        </div>
        
        <div class="reveal" style="max-width: 880px; margin: 0 auto;">
            <div style="border-radius: 16px; overflow: hidden; box-shadow: 0 30px 80px rgba(0,0,0,0.5);">
                <img src="/assets/images/cultura/manifesto-cultura.jpeg" alt="Manifesto de Cultura Brooks Construtora" style="width: 100%; height: auto; display: block;" loading="lazy">
            </div>
        </div>
    </div>
</section>

<!-- N.U.C.L.E.O -->
<section class="section dark-section" id="nucleo" style="border-top: 1px solid rgba(255,255,255,0.04);">
    <div class="container">
        <div class="grid grid--2 reveal" style="gap: var(--space-3xl); align-items: center;">
            <div>
                <div style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 12px; background: rgba(212,175,55,0.08); border: 1px solid rgba(212,175,55,0.2); border-radius: var(--radius-full); margin-bottom: var(--space-lg);">
                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #d4af37;"></span>
                    <span style="font-size: 10px; font-weight: 600; color: #d4af37; text-transform: uppercase; letter-spacing: 0.1em;">Diferencial Brooks</span>
                </div>
                <h2 style="font-size: clamp(2rem, 4vw, 2.8rem); font-weight: 800; color: white; line-height: 1.1; margin-bottom: var(--space-sm);">N.U.C.L.E.O</h2>
                <p style="font-size: var(--text-xs); color: #d4af37; font-weight: 600; letter-spacing: 0.3px; margin-bottom: var(--space-lg);">Núcleo de Unificação Criativa, Logística, Estratégia e Obra</p>
                <p style="font-size: var(--text-base); color: rgba(255,255,255,0.6); line-height: 1.8; margin-bottom: var(--space-md);">
                    O N.U.C.L.E.O é o departamento de inteligência pré-construtiva da Brooks. Uma mesa de engenheiros especializados que atua antes do primeiro tijolo ser assentado, garantindo que cada projeto nasça viável, otimizado e previsível.
                </p>
                <p style="color: rgba(255,255,255,0.35); line-height: 1.7; font-style: italic; margin-bottom: var(--space-lg); font-size: var(--text-sm);">"Onde o sonho encontra a viabilidade antes do primeiro traço."</p>
                <a href="#nucleo-processo" class="btn btn--lg" style="background: #d4af37; color: #111827; font-weight: 700; border: none;">Ver processo completo</a>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md);">
                <div class="nucleo-card">
                    <i data-lucide="hard-hat" style="width:24px;height:24px;color:#d4af37;margin-bottom:var(--space-sm);"></i>
                    <p style="font-size: 11px; color: rgba(255,255,255,0.8); font-weight: 600;">Eng. Operacional Sênior</p>
                    <p style="font-size: 10px; color: rgba(255,255,255,0.35); margin-top: 3px;">Viabilidade construtiva</p>
                </div>
                <div class="nucleo-card">
                    <i data-lucide="columns" style="width:24px;height:24px;color:#d4af37;margin-bottom:var(--space-sm);"></i>
                    <p style="font-size: 11px; color: rgba(255,255,255,0.8); font-weight: 600;">Eng. Estrutural Calculista</p>
                    <p style="font-size: 10px; color: rgba(255,255,255,0.35); margin-top: 3px;">Fundações e sistemas</p>
                </div>
                <div class="nucleo-card">
                    <i data-lucide="calendar" style="width:24px;height:24px;color:#d4af37;margin-bottom:var(--space-sm);"></i>
                    <p style="font-size: 11px; color: rgba(255,255,255,0.8); font-weight: 600;">Eng. de Planejamento</p>
                    <p style="font-size: 10px; color: rgba(255,255,255,0.35); margin-top: 3px;">Cronograma e logística</p>
                </div>
                <div class="nucleo-card">
                    <i data-lucide="calculator" style="width:24px;height:24px;color:#d4af37;margin-bottom:var(--space-sm);"></i>
                    <p style="font-size: 11px; color: rgba(255,255,255,0.8); font-weight: 600;">Analista Financeiro</p>
                    <p style="font-size: 10px; color: rgba(255,255,255,0.35); margin-top: 3px;">Custos e previsibilidade</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Cenários -->
<section class="section" id="nucleo-processo">
    <div class="container">
        <div class="section-header section-header--centered reveal">
            <span class="label gold-accent">Cenários de Atuação</span>
            <h2 class="headline-section">O N.U.C.L.E.O atua em três momentos.</h2>
        </div>
        
        <div class="grid grid--3 reveal" style="gap: var(--space-lg);">
            <div style="background: white; border-radius: var(--radius-lg); padding: var(--space-xl); border: 1px solid #e5e7eb; border-left: 3px solid #d4af37;">
                <div style="display: flex; align-items: center; gap: var(--space-sm); margin-bottom: var(--space-md);">
                    <span style="width: 28px; height: 28px; border-radius: var(--radius-full); background: #111827; color: #d4af37; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px;">A</span>
                    <h3 style="font-weight: 700; font-size: var(--text-sm); color: #111827;">Pré-Aquisição</h3>
                </div>
                <p style="font-size: var(--text-xs); color: #6b7280; line-height: 1.7;">Cliente ainda não adquiriu o terreno. O N.U.C.L.E.O analisa topografia, viabilidade construtiva, restrições legais e estima custos antes da compra.</p>
            </div>
            <div style="background: white; border-radius: var(--radius-lg); padding: var(--space-xl); border: 1px solid #e5e7eb; border-left: 3px solid #d4af37;">
                <div style="display: flex; align-items: center; gap: var(--space-sm); margin-bottom: var(--space-md);">
                    <span style="width: 28px; height: 28px; border-radius: var(--radius-full); background: #111827; color: #d4af37; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px;">B</span>
                    <h3 style="font-weight: 700; font-size: var(--text-sm); color: #111827;">Pré-Projeto</h3>
                </div>
                <p style="font-size: var(--text-xs); color: #6b7280; line-height: 1.7;">Terreno adquirido, projeto ainda não iniciado. Alinha com o arquiteto condicionantes técnicas, sistema construtivo e premissas financeiras.</p>
            </div>
            <div style="background: white; border-radius: var(--radius-lg); padding: var(--space-xl); border: 1px solid #e5e7eb; border-left: 3px solid #d4af37;">
                <div style="display: flex; align-items: center; gap: var(--space-sm); margin-bottom: var(--space-md);">
                    <span style="width: 28px; height: 28px; border-radius: var(--radius-full); background: #111827; color: #d4af37; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px;">C</span>
                    <h3 style="font-weight: 700; font-size: var(--text-sm); color: #111827;">Pré-Orçamento</h3>
                </div>
                <p style="font-size: var(--text-xs); color: #6b7280; line-height: 1.7;">Projeto já elaborado. Revisão técnica, compatibilização, análise estrutural, riscos e custos ocultos antes do orçamento executivo.</p>
            </div>
        </div>
    </div>
</section>

<!-- 8 Etapas -->
<section class="section dark-section">
    <div class="container">
        <div class="section-header section-header--centered reveal">
            <span class="label gold-accent">Processo</span>
            <h2 class="headline-section" style="color: white;">8 etapas de inteligência pré-construtiva.</h2>
            <p class="subtitle subtitle--centered" style="color: rgba(255,255,255,0.4);">Do recebimento da demanda ao orçamento executivo.</p>
        </div>
        
        <div class="reveal" style="max-width: 640px; margin: 0 auto; display: grid; gap: 10px;">
            <div class="etapa-item">
                <span class="etapa-num">01</span>
                <div><p style="font-weight: 600; color: white; font-size: 13px;">Recebimento da Demanda</p><p style="font-size: 11px; color: rgba(255,255,255,0.4);">Identificação do cenário e registro na VÉTRIKS</p></div>
            </div>
            <div class="etapa-item">
                <span class="etapa-num">02</span>
                <div><p style="font-weight: 600; color: white; font-size: 13px;">Abertura do Dossiê</p><p style="font-size: 11px; color: rgba(255,255,255,0.4);">Dossiê completo na plataforma VÉTRIKS</p></div>
            </div>
            <div class="etapa-item">
                <span class="etapa-num">03</span>
                <div><p style="font-weight: 600; color: white; font-size: 13px;">Coleta de Informações Técnicas</p><p style="font-size: 11px; color: rgba(255,255,255,0.4);">Topográfico, matrícula, projeto e necessidades</p></div>
            </div>
            <div class="etapa-item">
                <span class="etapa-num">04</span>
                <div><p style="font-weight: 600; color: white; font-size: 13px;">Reunião com Arquiteto</p><p style="font-size: 11px; color: rgba(255,255,255,0.4);">Alinhamento técnico em até 5 dias úteis</p></div>
            </div>
            <div class="etapa-item">
                <span class="etapa-num">05</span>
                <div><p style="font-weight: 600; color: white; font-size: 13px;">Estudo Técnico Preliminar</p><p style="font-size: 11px; color: rgba(255,255,255,0.4);">Terreno, sistema construtivo, riscos e custos ocultos</p></div>
            </div>
            <div class="etapa-item">
                <span class="etapa-num">06</span>
                <div><p style="font-weight: 600; color: white; font-size: 13px;">Apresentação ao Cliente</p><p style="font-size: 11px; color: rgba(255,255,255,0.4);">Resultados, alternativas e ajustes</p></div>
            </div>
            <div class="etapa-item">
                <span class="etapa-num">07</span>
                <div><p style="font-weight: 600; color: white; font-size: 13px;">Parecer Técnico N.U.C.L.E.O</p><p style="font-size: 11px; color: rgba(255,255,255,0.4);">Diretrizes, riscos e estimativa financeira</p></div>
            </div>
            <div class="etapa-item" style="border-color: rgba(212,175,55,0.3);">
                <span class="etapa-num" style="background: #d4af37; color: #111827; border-color: #d4af37;">08</span>
                <div><p style="font-weight: 600; color: white; font-size: 13px;">Encaminhamento para Orçamento</p><p style="font-size: 11px; color: rgba(255,255,255,0.4);">Pacote completo com precisão e previsibilidade</p></div>
            </div>
        </div>
    </div>
</section>

<!-- Propósito -->
<section class="section">
    <div class="container">
        <div class="reveal" style="text-align: center; max-width: 700px; margin: 0 auto;">
            <span class="label gold-accent">Nosso Propósito</span>
            <h2 class="headline-section">Não levantamos apenas edificações. Construímos padrão.</h2>
            <p style="font-size: var(--text-lg); color: var(--brooks-gray-600); line-height: 1.8; margin-bottom: var(--space-lg);">
                Construímos confiança, relacionamentos, processos e um novo padrão para a construção civil. Trabalhamos diariamente para ser referência em gestão, tecnologia, organização e excelência operacional.
            </p>
            <p style="font-size: var(--text-xl); font-weight: 800; color: #111827;">
                Brooks não é apenas uma empresa. É um padrão de engenharia.
            </p>
        </div>
    </div>
</section>

<!-- Compromisso -->
<section style="background: #111827; color: white; padding: var(--space-3xl) 0;">
    <div class="container">
        <div class="reveal" style="text-align: center; max-width: 650px; margin: 0 auto;">
            <i data-lucide="heart" style="width:28px;height:28px;color:#d4af37;margin-bottom:var(--space-lg);"></i>
            <p style="font-size: var(--text-lg); font-weight: 700; color: white; margin-bottom: var(--space-md); line-height: 1.4;">
                Se você está aqui, existe um motivo que te fez ficar.
            </p>
            <p style="color: rgba(255,255,255,0.5); line-height: 1.8; font-size: var(--text-sm);">
                A partir de agora, você carrega uma responsabilidade: proteger nossa cultura, honrar nossos valores e contribuir para que cada obra represente o mais alto padrão de qualidade, organização e respeito pelas pessoas.
            </p>
            <p style="color: #d4af37; font-weight: 600; margin-top: var(--space-lg); font-size: var(--text-sm);">
                Construir vai muito além de erguer estruturas: é deixar um legado de excelência.
            </p>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section" style="padding: var(--space-4xl) 0;">
    <div class="container text-center reveal">
        <h2 class="headline-subsection">Quer fazer parte dessa cultura?</h2>
        <p class="subtitle subtitle--centered" style="margin-bottom: var(--space-xl);">Entre em contato e descubra como a Brooks pode transformar o seu projeto.</p>
        <a href="/contato" class="btn btn--primary btn--lg">Solicitar Orçamento</a>
    </div>
</section>

<?php include ROOT_PATH . '/app/Views/site/layouts/new-footer.php'; ?>
