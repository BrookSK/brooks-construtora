<?php
$pageTitle = 'Vetriks — Sistema de Gestão de Obras';
$pageDescription = 'Vetriks - Sistema integrado que revoluciona a gestão de obras com IA e automação avançada. Criado no campo, para resolver problemas reais.';
$currentPage = 'vetriks';
$bodyClass = 'page-vetriks';
include ROOT_PATH . '/app/Views/site/layouts/new-header.php';
?>

<!-- Hero - Visual tech/dark com gradiente próprio da Vetriks -->
<section style="padding-top: calc(var(--header-height) + var(--space-4xl)); padding-bottom: var(--space-5xl); background: linear-gradient(160deg, #0a0f1a 0%, #0d1b2a 40%, #1b3a5c 100%); position: relative; overflow: hidden;">
    <!-- Grid pattern background -->
    <div style="position: absolute; inset: 0; opacity: 0.03; background-image: repeating-linear-gradient(0deg, transparent, transparent 60px, rgba(255,255,255,0.5) 60px, rgba(255,255,255,0.5) 61px), repeating-linear-gradient(90deg, transparent, transparent 60px, rgba(255,255,255,0.5) 60px, rgba(255,255,255,0.5) 61px);"></div>
    
    <div class="container" style="position: relative; z-index: 1;">
        <div class="reveal" style="max-width: 680px;">
            <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; background: rgba(52, 152, 219, 0.15); border: 1px solid rgba(52, 152, 219, 0.3); border-radius: var(--radius-full); margin-bottom: var(--space-xl);">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: #3498db; animation: pulse 2s infinite;"></span>
                <span style="font-size: 11px; font-weight: 600; color: #3498db; text-transform: uppercase; letter-spacing: 0.08em;">Tecnologia Brooks</span>
            </div>
            <h1 style="font-size: clamp(2.5rem, 5vw, 4rem); font-weight: 800; color: white; line-height: 1.1; margin-bottom: var(--space-lg);">
                Sistema integrado que <span style="background: linear-gradient(90deg, #3498db, #2ecc71); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">revoluciona</span> a gestão de obras.
            </h1>
            <p style="font-size: var(--text-lg); color: rgba(255,255,255,0.6); line-height: 1.8; margin-bottom: var(--space-xl);">
                Criado pela Brooks, e utilizada por diversas outras construtoras. IA e automação avançada. Criado em 10 anos de campo, forjado na operação, validado por dezenas de construtoras. Economize 180 horas/mês em retrabalho.
            </p>
            <div style="display: flex; gap: var(--space-md); flex-wrap: wrap;">
                <a href="https://app.vetriks.com.br/auth/register" target="_blank" rel="noopener" class="btn btn--primary btn--lg" style="background: linear-gradient(135deg, #3498db, #2980b9);">Começar agora</a>
                <a href="#funcionalidades" class="btn btn--secondary btn--lg">Ver funcionalidades</a>
            </div>
        </div>
        
        <!-- Stats inline -->
        <div class="reveal" style="display: flex; gap: var(--space-2xl); margin-top: var(--space-4xl); flex-wrap: wrap;">
            <div>
                <div style="font-size: var(--text-3xl); font-weight: 800; color: white;">10+</div>
                <div style="font-size: var(--text-xs); color: rgba(255,255,255,0.4);">Anos de experiência</div>
            </div>
            <div>
                <div style="font-size: var(--text-3xl); font-weight: 800; color: white;">180h</div>
                <div style="font-size: var(--text-xs); color: rgba(255,255,255,0.4);">Economizadas por mês</div>
            </div>
            <div>
                <div style="font-size: var(--text-3xl); font-weight: 800; color: white;">-12%</div>
                <div style="font-size: var(--text-xs); color: rgba(255,255,255,0.4);">Redução de atrasos</div>
            </div>
            <div>
                <div style="font-size: var(--text-3xl); font-weight: 800; color: white;">3 meses</div>
                <div style="font-size: var(--text-xs); color: rgba(255,255,255,0.4);">ROI garantido</div>
            </div>
        </div>
    </div>
</section>

<!-- Dores / Problemas -->
<section class="section" id="problemas">
    <div class="container">
        <div class="section-header section-header--centered reveal">
            <span class="label">Você conhece essas frustrações?</span>
            <h2 class="headline-section">Problemas que a Vetriks resolve.</h2>
        </div>
        
        <div class="grid grid--4 reveal" style="gap: var(--space-lg);">
            <div style="padding: var(--space-xl); background: #fef2f2; border-radius: var(--radius-lg); border-left: 4px solid #ef4444;">
                <h4 style="font-size: var(--text-sm); font-weight: 700; color: #991b1b; margin-bottom: var(--space-sm);">Atrasos inexplicáveis</h4>
                <p style="font-size: var(--text-sm); color: var(--brooks-gray-600); line-height: 1.6;">Obras que estouram prazos sem você saber o porquê.</p>
            </div>
            <div style="padding: var(--space-xl); background: #fef2f2; border-radius: var(--radius-lg); border-left: 4px solid #ef4444;">
                <h4 style="font-size: var(--text-sm); font-weight: 700; color: #991b1b; margin-bottom: var(--space-sm);">Orçamentos descontrolados</h4>
                <p style="font-size: var(--text-sm); color: var(--brooks-gray-600); line-height: 1.6;">Custos que fogem do planejado e comprometem margens.</p>
            </div>
            <div style="padding: var(--space-xl); background: #fef2f2; border-radius: var(--radius-lg); border-left: 4px solid #ef4444;">
                <h4 style="font-size: var(--text-sm); font-weight: 700; color: #991b1b; margin-bottom: var(--space-sm);">Informações fragmentadas</h4>
                <p style="font-size: var(--text-sm); color: var(--brooks-gray-600); line-height: 1.6;">Planilhas, WhatsApp, papéis… informação em todo lugar, menos onde precisa.</p>
            </div>
            <div style="padding: var(--space-xl); background: #fef2f2; border-radius: var(--radius-lg); border-left: 4px solid #ef4444;">
                <h4 style="font-size: var(--text-sm); font-weight: 700; color: #991b1b; margin-bottom: var(--space-sm);">Decisões no escuro</h4>
                <p style="font-size: var(--text-sm); color: var(--brooks-gray-600); line-height: 1.6;">Tomar decisões críticas sem dados confiáveis em tempo real.</p>
            </div>
        </div>
    </div>
</section>

<!-- Telas do Sistema -->
<section class="section" style="background: var(--brooks-off-white);">
    <div class="container">
        <div class="section-header section-header--centered reveal">
            <span class="label">Conheça as telas</span>
            <h2 class="headline-section">O sistema por dentro.</h2>
        </div>
        
        <div class="grid grid--2 reveal" style="gap: var(--space-xl);">
            <div style="background: white; border: 1px solid var(--brooks-gray-200); border-bottom: 3px solid #3498db; border-radius: var(--radius-lg); overflow: hidden; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                <img src="/assets/images/projects/vetriks/VETRIKS.jpeg" alt="Tela Principal Vetriks" style="width: 100%; height: 280px; object-fit: cover;" loading="lazy">
                <p style="padding: var(--space-md); margin: 0; color: var(--brooks-gray-600); font-size: var(--text-sm); font-weight: 500;">Painel Principal</p>
            </div>
            <div style="background: white; border: 1px solid var(--brooks-gray-200); border-bottom: 3px solid #3498db; border-radius: var(--radius-lg); overflow: hidden; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                <img src="/assets/images/projects/vetriks/CRONOGRAMA.jpeg" alt="Tela Cronograma" style="width: 100%; height: 280px; object-fit: cover;" loading="lazy">
                <p style="padding: var(--space-md); margin: 0; color: var(--brooks-gray-600); font-size: var(--text-sm); font-weight: 500;">Gráfico em Gantt</p>
            </div>
            <div style="background: white; border: 1px solid var(--brooks-gray-200); border-bottom: 3px solid #3498db; border-radius: var(--radius-lg); overflow: hidden; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                <img src="/assets/images/projects/vetriks/CALENDÁRIO.jpeg" alt="Tela Calendário" style="width: 100%; height: 280px; object-fit: cover;" loading="lazy">
                <p style="padding: var(--space-md); margin: 0; color: var(--brooks-gray-600); font-size: var(--text-sm); font-weight: 500;">Calendário</p>
            </div>
            <div style="background: white; border: 1px solid var(--brooks-gray-200); border-bottom: 3px solid #3498db; border-radius: var(--radius-lg); overflow: hidden; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                <img src="/assets/images/projects/vetriks/RDO.jpeg" alt="Tela RDO" style="width: 100%; height: 280px; object-fit: cover;" loading="lazy">
                <p style="padding: var(--space-md); margin: 0; color: var(--brooks-gray-600); font-size: var(--text-sm); font-weight: 500;">RDO - Relatório Diário de Obra</p>
            </div>
        </div>
    </div>
</section>

<!-- Funcionalidades -->
<section class="section section--gray" id="funcionalidades">
    <div class="container">
        <div class="section-header section-header--centered reveal">
            <span class="label">Funcionalidades</span>
            <h2 class="headline-section">Existe uma forma mais inteligente de construir.</h2>
            <p class="subtitle subtitle--centered">Sistema vertical, integrado, com IA, tudo em um único lugar.</p>
        </div>
        
        <div style="display: grid; gap: var(--space-3xl);">
            <!-- Feature 1 -->
            <div class="grid grid--2 reveal" style="align-items: center; gap: var(--space-3xl);">
                <div>
                    <div style="display: inline-flex; align-items: center; gap: 8px; padding: 4px 12px; background: rgba(52,152,219,0.1); border-radius: var(--radius-full); margin-bottom: var(--space-md);">
                        <i data-lucide="layout-dashboard" style="width:14px;height:14px;color:#3498db;"></i>
                        <span style="font-size: 11px; font-weight: 600; color: #3498db;">VISÃO COMPLETA</span>
                    </div>
                    <h3 class="headline-subsection">Visão vertical completa</h3>
                    <p style="color: var(--brooks-gray-500); line-height: 1.8; margin-bottom: var(--space-lg);">Enxergue tudo, de qualquer lugar. Múltiplas obras em uma única tela.</p>
                    <ul style="display: grid; gap: var(--space-sm);">
                        <li style="display: flex; align-items: center; gap: 10px; font-size: var(--text-sm); color: var(--brooks-gray-600);"><i data-lucide="check" style="width:16px;height:16px;color:#2ecc71;flex-shrink:0;"></i> Múltiplas obras em uma única tela</li>
                        <li style="display: flex; align-items: center; gap: 10px; font-size: var(--text-sm); color: var(--brooks-gray-600);"><i data-lucide="check" style="width:16px;height:16px;color:#2ecc71;flex-shrink:0;"></i> Resumos e análises em tempo real</li>
                        <li style="display: flex; align-items: center; gap: 10px; font-size: var(--text-sm); color: var(--brooks-gray-600);"><i data-lucide="check" style="width:16px;height:16px;color:#2ecc71;flex-shrink:0;"></i> Compara tamanho, prazos e custos</li>
                        <li style="display: flex; align-items: center; gap: 10px; font-size: var(--text-sm); color: var(--brooks-gray-600);"><i data-lucide="check" style="width:16px;height:16px;color:#2ecc71;flex-shrink:0;"></i> Painéis personalizáveis</li>
                    </ul>
                </div>
                <div style="background: linear-gradient(135deg, #0d1b2a, #1b3a5c); border-radius: var(--radius-xl); padding: var(--space-3xl); display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="monitor" style="width:80px;height:80px;color:rgba(52,152,219,0.6);"></i>
                </div>
            </div>
            
            <!-- Feature 2 -->
            <div class="grid grid--2 reveal" style="align-items: center; gap: var(--space-3xl);">
                <div style="background: linear-gradient(135deg, #0d1b2a, #1b3a5c); border-radius: var(--radius-xl); padding: var(--space-3xl); display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="bot" style="width:80px;height:80px;color:rgba(52,152,219,0.6);"></i>
                </div>
                <div>
                    <div style="display: inline-flex; align-items: center; gap: 8px; padding: 4px 12px; background: rgba(52,152,219,0.1); border-radius: var(--radius-full); margin-bottom: var(--space-md);">
                        <i data-lucide="zap" style="width:14px;height:14px;color:#3498db;"></i>
                        <span style="font-size: 11px; font-weight: 600; color: #3498db;">AUTOMAÇÃO</span>
                    </div>
                    <h3 class="headline-subsection">Automação de Processos</h3>
                    <p style="color: var(--brooks-gray-500); line-height: 1.8; margin-bottom: var(--space-lg);">Alertas, bots e integrações que trabalham por você.</p>
                    <ul style="display: grid; gap: var(--space-sm);">
                        <li style="display: flex; align-items: center; gap: 10px; font-size: var(--text-sm); color: var(--brooks-gray-600);"><i data-lucide="check" style="width:16px;height:16px;color:#2ecc71;flex-shrink:0;"></i> Cliente notificado por bot automaticamente</li>
                        <li style="display: flex; align-items: center; gap: 10px; font-size: var(--text-sm); color: var(--brooks-gray-600);"><i data-lucide="check" style="width:16px;height:16px;color:#2ecc71;flex-shrink:0;"></i> Envio automático de documentos</li>
                        <li style="display: flex; align-items: center; gap: 10px; font-size: var(--text-sm); color: var(--brooks-gray-600);"><i data-lucide="check" style="width:16px;height:16px;color:#2ecc71;flex-shrink:0;"></i> Lembretes diários automatizados</li>
                        <li style="display: flex; align-items: center; gap: 10px; font-size: var(--text-sm); color: var(--brooks-gray-600);"><i data-lucide="check" style="width:16px;height:16px;color:#2ecc71;flex-shrink:0;"></i> RDO digital com transcrição de áudio por IA</li>
                    </ul>
                </div>
            </div>
            
            <!-- Feature 3 -->
            <div class="grid grid--2 reveal" style="align-items: center; gap: var(--space-3xl);">
                <div>
                    <div style="display: inline-flex; align-items: center; gap: 8px; padding: 4px 12px; background: rgba(52,152,219,0.1); border-radius: var(--radius-full); margin-bottom: var(--space-md);">
                        <i data-lucide="dollar-sign" style="width:14px;height:14px;color:#3498db;"></i>
                        <span style="font-size: 11px; font-weight: 600; color: #3498db;">FINANCEIRO</span>
                    </div>
                    <h3 class="headline-subsection">Controle financeiro absoluto</h3>
                    <p style="color: var(--brooks-gray-500); line-height: 1.8; margin-bottom: var(--space-lg);">Cada centavo rastreado e otimizado em tempo real.</p>
                    <ul style="display: grid; gap: var(--space-sm);">
                        <li style="display: flex; align-items: center; gap: 10px; font-size: var(--text-sm); color: var(--brooks-gray-600);"><i data-lucide="check" style="width:16px;height:16px;color:#2ecc71;flex-shrink:0;"></i> Custos vs orçamento em tempo real</li>
                        <li style="display: flex; align-items: center; gap: 10px; font-size: var(--text-sm); color: var(--brooks-gray-600);"><i data-lucide="check" style="width:16px;height:16px;color:#2ecc71;flex-shrink:0;"></i> Pagamentos e recibos integrados</li>
                        <li style="display: flex; align-items: center; gap: 10px; font-size: var(--text-sm); color: var(--brooks-gray-600);"><i data-lucide="check" style="width:16px;height:16px;color:#2ecc71;flex-shrink:0;"></i> Fluxo de caixa completo</li>
                        <li style="display: flex; align-items: center; gap: 10px; font-size: var(--text-sm); color: var(--brooks-gray-600);"><i data-lucide="check" style="width:16px;height:16px;color:#2ecc71;flex-shrink:0;"></i> Relatórios de custos por obra</li>
                    </ul>
                </div>
                <div style="background: linear-gradient(135deg, #0d1b2a, #1b3a5c); border-radius: var(--radius-xl); padding: var(--space-3xl); display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="bar-chart-3" style="width:80px;height:80px;color:rgba(52,152,219,0.6);"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- More modules -->
<section class="section">
    <div class="container">
        <div class="section-header section-header--centered reveal">
            <span class="label">Módulos</span>
            <h2 class="headline-section">Tudo que você precisa, integrado.</h2>
        </div>
        
        <div class="grid grid--3 reveal" style="gap: var(--space-lg);">
            <div class="card" style="border-top: 3px solid #3498db;">
                <i data-lucide="clipboard-list" style="width:28px;height:28px;color:#3498db;margin-bottom:var(--space-md);"></i>
                <h3 class="card__title">Gestão de Recursos</h3>
                <p class="card__text">Materiais, equipamentos e pessoas sincronizados. Controle de estoque inteligente e solicitação digital.</p>
            </div>
            <div class="card" style="border-top: 3px solid #3498db;">
                <i data-lucide="file-signature" style="width:28px;height:28px;color:#3498db;margin-bottom:var(--space-md);"></i>
                <h3 class="card__title">Jurídico Automatizado</h3>
                <p class="card__text">Contratos gerados por BOT, cadastro de prestadores, consulta de antecedentes criminais integrada.</p>
            </div>
            <div class="card" style="border-top: 3px solid #3498db;">
                <i data-lucide="mic" style="width:28px;height:28px;color:#3498db;margin-bottom:var(--space-md);"></i>
                <h3 class="card__title">RDO por Áudio com IA</h3>
                <p class="card__text">Transcrição de áudio com inteligência artificial. Até 30 horas de transcrição por mês no plano Enterprise.</p>
            </div>
            <div class="card" style="border-top: 3px solid #3498db;">
                <i data-lucide="calendar-check" style="width:28px;height:28px;color:#3498db;margin-bottom:var(--space-md);"></i>
                <h3 class="card__title">Cronograma Inteligente</h3>
                <p class="card__text">Controle de tarefas, planejamento de equipes e calendário integrado com todos os responsáveis.</p>
            </div>
            <div class="card" style="border-top: 3px solid #3498db;">
                <i data-lucide="users" style="width:28px;height:28px;color:#3498db;margin-bottom:var(--space-md);"></i>
                <h3 class="card__title">RH Inteligente</h3>
                <p class="card__text">Gestão completa de colaboradores, planejamento de equipes e documentação automatizada.</p>
            </div>
            <div class="card" style="border-top: 3px solid #3498db;">
                <i data-lucide="smartphone" style="width:28px;height:28px;color:#3498db;margin-bottom:var(--space-md);"></i>
                <h3 class="card__title">Mobile First</h3>
                <p class="card__text">Interface pensada para o canteiro de obras. Acesso completo pelo celular, de qualquer lugar.</p>
            </div>
        </div>
    </div>
</section>

<!-- Origin Story -->
<section class="section" style="background: linear-gradient(160deg, #0a0f1a, #1b3a5c); color: white;">
    <div class="container">
        <div class="grid grid--2 reveal" style="align-items: center; gap: var(--space-3xl);">
            <div>
                <span class="label" style="color: #3498db;">A Origem</span>
                <h2 class="headline-section" style="color: white;">Nasceu dentro da Brooks. Criada nas dores reais.</h2>
                <p style="color: rgba(255,255,255,0.6); line-height: 1.8; margin-bottom: var(--space-lg);">
                    Após os proprietários tentarem outros sistemas e perceberem que eram fragmentados e robustos demais, decidiram criar algo diferente. Vertical, integrado, didático e intuitivo.
                </p>
                <p style="color: rgba(255,255,255,0.5); line-height: 1.8; margin-bottom: var(--space-lg);">
                    Ficou tão exemplar que outras construtoras começaram a usar. O Força Estrutural validou a ferramenta, comprovando sua excelência. Hoje, a Vetriks é referência em São Paulo.
                </p>
                <p style="font-style: italic; color: rgba(255,255,255,0.7); border-left: 3px solid #3498db; padding-left: var(--space-md); font-size: var(--text-sm);">
                    "O custo da Vetriks é menor que o retrabalho de uma semana de obra."
                </p>
            </div>
            <div style="text-align: center;">
                <div style="display: inline-grid; grid-template-columns: 1fr 1fr; gap: var(--space-md);">
                    <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: var(--radius-lg); padding: var(--space-xl); text-align: center;">
                        <i data-lucide="brain" style="width:32px;height:32px;color:#3498db;margin-bottom:var(--space-sm);"></i>
                        <p style="font-size: var(--text-xs); color: rgba(255,255,255,0.6);">IA Integrada</p>
                    </div>
                    <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: var(--radius-lg); padding: var(--space-xl); text-align: center;">
                        <i data-lucide="shield-check" style="width:32px;height:32px;color:#3498db;margin-bottom:var(--space-sm);"></i>
                        <p style="font-size: var(--text-xs); color: rgba(255,255,255,0.6);">Validada em campo</p>
                    </div>
                    <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: var(--radius-lg); padding: var(--space-xl); text-align: center;">
                        <i data-lucide="zap" style="width:32px;height:32px;color:#3498db;margin-bottom:var(--space-sm);"></i>
                        <p style="font-size: var(--text-xs); color: rgba(255,255,255,0.6);">Automação total</p>
                    </div>
                    <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: var(--radius-lg); padding: var(--space-xl); text-align: center;">
                        <i data-lucide="trending-up" style="width:32px;height:32px;color:#3498db;margin-bottom:var(--space-sm);"></i>
                        <p style="font-size: var(--text-xs); color: rgba(255,255,255,0.6);">ROI comprovado</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- White Label -->
<section class="section">
    <div class="container">
        <div class="reveal" style="background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%); border-radius: var(--radius-xl); padding: var(--space-4xl); position: relative; overflow: hidden;">
            <!-- Decorative elements -->
            <div style="position: absolute; top: -50px; right: -50px; width: 200px; height: 200px; background: radial-gradient(circle, rgba(52, 152, 219, 0.15), transparent 70%); border-radius: 50%;"></div>
            <div style="position: absolute; bottom: -30px; left: -30px; width: 150px; height: 150px; background: radial-gradient(circle, rgba(46, 204, 113, 0.1), transparent 70%); border-radius: 50%;"></div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-3xl); align-items: center; position: relative; z-index: 1;">
                <div>
                    <div style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; background: linear-gradient(135deg, rgba(52, 152, 219, 0.2), rgba(46, 204, 113, 0.2)); border: 1px solid rgba(52, 152, 219, 0.3); border-radius: var(--radius-full); margin-bottom: var(--space-lg);">
                        <i data-lucide="tag" style="width:14px;height:14px;color:#3498db;"></i>
                        <span style="font-size: 11px; font-weight: 600; color: #3498db; text-transform: uppercase; letter-spacing: 0.08em;">White Label</span>
                    </div>
                    <h2 style="font-size: clamp(1.8rem, 3vw, 2.5rem); font-weight: 800; color: white; line-height: 1.2; margin-bottom: var(--space-lg);">
                        Sua construtora com tecnologia própria.
                    </h2>
                    <p style="font-size: var(--text-lg); color: rgba(255,255,255,0.7); line-height: 1.8; margin-bottom: var(--space-xl);">
                        Vendemos a Vetriks como marca própria White Label para outras construtoras. Tenha o sistema completo com a sua marca, sua identidade visual e seu domínio.
                    </p>
                    <a href="/contato" class="btn btn--lg" style="background: linear-gradient(135deg, #3498db, #2ecc71); color: white; border: none;">Solicitar proposta White Label</a>
                </div>
                <div style="display: grid; gap: var(--space-md);">
                    <div style="display: flex; align-items: center; gap: var(--space-md); padding: var(--space-lg); background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: var(--radius-lg);">
                        <div style="width: 44px; height: 44px; border-radius: var(--radius-full); background: rgba(52, 152, 219, 0.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i data-lucide="palette" style="width:20px;height:20px;color:#3498db;"></i>
                        </div>
                        <div>
                            <p style="font-weight: 600; color: white; font-size: var(--text-sm); margin: 0;">Sua marca, sua identidade</p>
                            <p style="font-size: var(--text-xs); color: rgba(255,255,255,0.5); margin: 4px 0 0;">Logo, cores e domínio personalizados</p>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: var(--space-md); padding: var(--space-lg); background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: var(--radius-lg);">
                        <div style="width: 44px; height: 44px; border-radius: var(--radius-full); background: rgba(46, 204, 113, 0.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i data-lucide="rocket" style="width:20px;height:20px;color:#2ecc71;"></i>
                        </div>
                        <div>
                            <p style="font-weight: 600; color: white; font-size: var(--text-sm); margin: 0;">Pronto para usar</p>
                            <p style="font-size: var(--text-xs); color: rgba(255,255,255,0.5); margin: 4px 0 0;">Implantação rápida, sem desenvolvimento</p>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: var(--space-md); padding: var(--space-lg); background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: var(--radius-lg);">
                        <div style="width: 44px; height: 44px; border-radius: var(--radius-full); background: rgba(52, 152, 219, 0.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i data-lucide="headphones" style="width:20px;height:20px;color:#3498db;"></i>
                        </div>
                        <div>
                            <p style="font-weight: 600; color: white; font-size: var(--text-sm); margin: 0;">Suporte dedicado</p>
                            <p style="font-size: var(--text-xs); color: rgba(255,255,255,0.5); margin: 4px 0 0;">Treinamento incluso</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section" style="padding: var(--space-4xl) 0;">
    <div class="container text-center reveal">
        <h2 class="headline-section">Pronto para transformar sua gestão?</h2>
        <p class="subtitle subtitle--centered" style="margin-bottom: var(--space-xl);">Em um mês, você paga a ferramenta com o tempo economizado em planilhas.</p>
        <div style="display: flex; gap: var(--space-md); justify-content: center; flex-wrap: wrap;">
            <a href="https://app.vetriks.com.br/auth/register" target="_blank" rel="noopener" class="btn btn--primary btn--lg" style="background: linear-gradient(135deg, #3498db, #2980b9);">Começar agora</a>
            <a href="https://vetriks.com.br/" target="_blank" rel="noopener" class="btn btn--outline btn--lg">Ver planos e preços</a>
        </div>
    </div>
</section>

<?php include ROOT_PATH . '/app/Views/site/layouts/new-footer.php'; ?>
