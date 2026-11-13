<?php $pageTitle = 'Painel de Obras'; $currentPage = 'painel_obras'; ?>
<?php ob_start(); ?>
<link rel="stylesheet" href="/assets/css/painel-obras-light.css">
<style>
/* Override do layout para tema escuro nesta página */
.main-content { background: #f8f9fa !important; }
body { background: #f8f9fa !important; }
.top-bar { background: #ffffff !important; color: #1e293b !important; box-shadow: none !important; border-bottom: 1px solid #1e293b !important; }
.top-bar h5 { color: #0f172a !important; }
.top-bar .text-muted { color: #64748b !important; }
.top-bar .badge { background: rgba(59,130,246,.2) !important; color: #93c5fd !important; }

/* Design System */
:root {
  --po-bg:#f8f9fa; --po-card:#ffffff; --po-input:#f1f5f9;
  --po-border:#1e293b; --po-txt:#1e293b; --po-txt2:#64748b;
  --po-blue:#3b82f6; --po-blue-t:#2563eb; --po-cyan:#22d3ee;
  --po-green:#10b981; --po-green-t:#059669;
  --po-yellow:#f59e0b; --po-yellow-t:#d97706;
  --po-red:#ef4444; --po-red-t:#dc2626;
  --po-gray:#64748b; --po-gray2:#334155;
}
#po { font-family:'Inter',system-ui,sans-serif; font-variant-numeric:tabular-nums; color:var(--po-txt); padding-bottom:48px; }
.po-card { background:var(--po-card); border:1px solid var(--po-border); border-radius:16px; padding:20px; box-shadow:0 8px 24px rgba(0,0,0,.25); }
.po-pill { display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px; }
.po-pill .dot { width:6px;height:6px;border-radius:50%; }
.po-pill.ok     { color:var(--po-green-t); background:rgba(16,185,129,.14); }
.po-pill.warn   { color:var(--po-yellow-t);background:rgba(245,158,11,.14); }
.po-pill.danger { color:var(--po-red-t);   background:rgba(239,68,68,.14); }
.po-pill.info   { color:#93c5fd;           background:rgba(59,130,246,.14); }
.po-pill.pending{ color:#cbd5e1;           background:rgba(100,116,139,.14);}
.po-badge-ref { font-size:11px;font-weight:600;padding:2px 8px;border-radius:8px;color:#67e8f9;background:rgba(103,232,249,.12); }
.po-badge-con { font-size:11px;font-weight:600;padding:2px 8px;border-radius:8px;color:#93c5fd;background:rgba(147,197,253,.14); }
.po-btn { display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:12px;font-size:13px;font-weight:600;cursor:pointer;border:none; }
.po-btn-primary { background:linear-gradient(135deg,var(--po-blue),var(--po-cyan));color:#fff;box-shadow:0 4px 12px rgba(59,130,246,.3); }
.po-btn-ghost   { background:var(--po-card);border:1px solid var(--po-border);color:var(--po-txt); }
.po-btn:hover   { opacity:.85; }
.po-input-dark  { background:var(--po-input);border:1px solid var(--po-border);color:var(--po-txt);border-radius:8px;padding:7px 12px;outline:none;font-size:13px; }
.po-input-dark:focus { border-color:var(--po-blue); }
.po-subnav { display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px; }
.po-subnav a { font-size:13px;padding:6px 14px;border-radius:8px;color:var(--po-txt2);text-decoration:none;border:1px solid var(--po-border);transition:all .15s; }
.po-subnav a:hover,.po-subnav a.active { background:rgba(59,130,246,.12);color:var(--po-blue-t);border-color:rgba(59,130,246,.3); }
/* KPIs */
.po-kpis { display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:20px; }
@media(max-width:1280px){.po-kpis{grid-template-columns:repeat(3,1fr);}}
@media(max-width:760px) {.po-kpis{grid-template-columns:repeat(2,1fr);}}
.po-kpi { position:relative; }
.po-kpi-icon { width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:17px;margin-bottom:10px; }
.po-kpi-pill { position:absolute;top:16px;right:16px;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px; }
.po-kpi-label { font-size:12px;color:var(--po-txt2);margin-bottom:4px; }
.po-kpi-value { font-size:22px;font-weight:700;color:#0f172a;line-height:1.1; }
.po-kpi-bar   { height:6px;background:rgba(255,255,255,.07);border-radius:3px;margin-top:10px;overflow:hidden; }
.po-kpi-bar-fill { height:100%;border-radius:3px;transition:width .9s ease; }
.po-kpi-note  { font-size:11px;color:var(--po-txt2);margin-top:5px; }
/* Alertas */
.po-alertas { display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px; }
@media(max-width:1280px){.po-alertas{grid-template-columns:repeat(2,1fr);}}
@media(max-width:760px) {.po-alertas{grid-template-columns:1fr;}}
.po-alerta-card { border-left:3px solid var(--po-red); }
.po-alerta-card.warn { border-left-color:var(--po-yellow); }
/* Tabela */
.po-table-wrap { overflow-x:auto; }
.po-table { width:100%;border-collapse:collapse; }
.po-table th { font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--po-txt2);padding:10px 14px;border-bottom:1px solid var(--po-border);white-space:nowrap; }
.po-table th.r,.po-table td.r { text-align:right; }
.po-table td { padding:10px 14px;border-bottom:1px solid var(--po-border);font-size:13px;color:var(--po-txt);white-space:nowrap; }
.po-table tr:last-child td { border-bottom:none; }
.po-table tbody tr:hover { background:rgba(59,130,246,.05); }
.po-code { font-size:11px;font-weight:600;color:var(--po-blue-t);display:block; }
.po-name { font-weight:600;color:#0f172a; }
.po-desvio-pos { color:var(--po-red-t); }
.po-desvio-neg { color:var(--po-green-t); }
.po-bar-track { height:6px;background:rgba(255,255,255,.07);border-radius:3px;overflow:hidden; }
.po-bar-fill  { height:100%;border-radius:3px; }
/* Filtros */
.po-filters { display:flex;gap:10px;flex-wrap:wrap;padding:14px 20px;border-bottom:1px solid var(--po-border); }
/* Categorias */
.po-cat-row { display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--po-border); }
.po-cat-row:last-child { border-bottom:none; }
.po-cat-label { width:170px;font-size:13px;color:var(--po-txt);flex-shrink:0; }
.po-cat-bars  { flex:1; }
.po-cat-bar   { height:10px;border-radius:5px;margin-bottom:4px; }
/* Fluxo */
.po-fluxo-chart { display:flex;align-items:flex-end;gap:4px;height:220px;padding-bottom:20px;position:relative; }
.po-fluxo-col   { flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;gap:2px; }
.po-fluxo-bar   { width:100%;max-width:38px;border-radius:4px 4px 0 0;min-height:2px; }
.po-fluxo-mes   { font-size:10px;color:var(--po-txt2);margin-top:4px; }
.po-fluxo-boxes { display:flex;gap:12px;margin-top:12px;flex-wrap:wrap; }
.po-fluxo-box   { flex:1;min-width:120px;background:var(--po-input);border:1px solid var(--po-border);border-radius:10px;padding:12px 16px; }
/* Conciliação */
.po-conc-cards { display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap; }
.po-conc-card  { flex:1;min-width:110px;background:var(--po-input);border:1px solid var(--po-border);border-radius:10px;padding:14px 16px;text-align:center; }
/* Ações */
.po-acoes { display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:20px; }
@media(max-width:760px){.po-acoes{grid-template-columns:1fr;}}
/* Seção */
.po-stitle { font-size:16px;font-weight:600;color:#0f172a;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:4px; }
.po-ssub   { font-size:13px;color:var(--po-txt2);margin-bottom:14px; }
/* Warn bar */
.po-warn-bar { background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:10px;padding:10px 16px;font-size:13px;color:var(--po-yellow-t);margin-bottom:16px;display:flex;align-items:center;gap:8px; }
/* Toast */
#po-toast { position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px; }
.po-toast-item { background:var(--po-card);border:1px solid var(--po-border);border-radius:10px;padding:12px 20px;font-size:13px;color:var(--po-txt);box-shadow:0 8px 24px rgba(0,0,0,.4); }
.po-toast-item.ok { border-color:rgba(16,185,129,.4); }
.po-toast-item.error { border-color:rgba(239,68,68,.4); }
/* Skeletons */
.po-skel { background:linear-gradient(90deg,rgba(255,255,255,.04) 0%,rgba(255,255,255,.08) 50%,rgba(255,255,255,.04) 100%);background-size:200%;animation:shimmer 1.5s infinite;border-radius:6px; }
@keyframes shimmer{0%{background-position:200%}100%{background-position:-200%}}
@media(prefers-reduced-motion:reduce){*{transition:none!important;animation:none!important;}}
/* Periodo select */
.po-period-sel { background:var(--po-input);border:1px solid var(--po-border);color:var(--po-txt);border-radius:8px;padding:6px 10px;font-size:13px;outline:none;cursor:pointer; }
.po-sync-badge { display:flex;align-items:center;gap:6px;font-size:12px;color:var(--po-green-t);background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);border-radius:20px;padding:4px 12px; }
.po-sync-badge .dot { width:7px;height:7px;border-radius:50%;background:var(--po-green);animation:pulse 2s infinite; }
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
/* Modal */
.po-modal{position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:2000;display:flex;align-items:center;justify-content:center;padding:20px}
.po-modal-box{background:var(--po-card-bg);border:1px solid rgba(255,255,255,.08);border-radius:16px;width:100%;max-height:90vh;overflow-y:auto;padding:28px;box-shadow:0 20px 60px rgba(0,0,0,.5)}
.po-modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px}
.po-modal-title{margin:0;color:var(--po-txt);font-size:18px;font-weight:600}
.po-btn-close{background:none;border:none;color:var(--po-txt2);font-size:28px;line-height:1;cursor:pointer;padding:0;width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:6px;transition:all .15s}
.po-btn-close:hover{background:rgba(255,255,255,.05);color:var(--po-txt)}
.po-modal-footer{border-top:1px solid var(--po-border);padding-top:20px;margin-top:20px}
.po-btn-secondary{background:rgba(100,116,139,.15);border:1px solid rgba(100,116,139,.3);color:var(--po-txt2);padding:8px 18px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;transition:all .15s}
.po-btn-secondary:hover{background:rgba(100,116,139,.25);border-color:rgba(100,116,139,.4)}
</style>

<div id="po">

<!-- Aviso de pendências -->
<div id="po-pending-bar" class="po-warn-bar" style="display:none">
  <i class="bi bi-exclamation-triangle-fill"></i>
  <span id="po-pending-msg"></span>
  <a href="/admin/painel-obras/depara" style="color:inherit;margin-left:4px;text-decoration:underline">Mapear categorias</a>
</div>

<!-- Cabeçalho -->
<div style="display:flex;justify-content:space-between;align-items:flex-start;padding:4px 0 20px;gap:12px;flex-wrap:wrap">
  <div>
    <div style="display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:600;color:var(--po-blue-t);background:rgba(59,130,246,.12);border:1px solid rgba(59,130,246,.25);border-radius:20px;padding:3px 10px;margin-bottom:8px">
      <i class="bi bi-building"></i> Controladoria • Obras
    </div>
    <h4 style="font-size:26px;font-weight:700;color:#0f172a;margin:0 0 4px">Painel Financeiro de Obras</h4>
    <p style="font-size:14px;color:var(--po-txt2);margin:0">Visão consolidada — <span id="po-ativas">…</span> obras ativas</p>
  </div>
  <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <div class="po-sync-badge"><span class="dot"></span><span id="po-sync-time">Carregando…</span></div>
    <div style="display:flex;align-items:center;gap:6px">
      <label style="font-size:12px;color:var(--po-txt2)">Período:</label>
      <select id="po-periodo" class="po-period-sel" onchange="poLoad()">
        <?php
        $meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
        $ano = (int)date('Y');
        for($m=1;$m<=12;$m++){
          $v=sprintf('%04d-%02d',$ano,$m);
          $sel=($v===$competencia)?' selected':'';
          echo "<option value=\"{$v}\"{$sel}>{$meses[$m-1]}/{$ano}</option>";
        }
        for($q=1;$q<=4;$q++){
          $v="{$ano}-Q{$q}";
          $sel=($v===$competencia)?' selected':'';
          echo "<option value=\"{$v}\"{$sel}>Trimestre {$q}/{$ano}</option>";
        }
        ?>
      </select>
      <select id="po-company" class="po-period-sel" onchange="poLoad()">
        <?php foreach($companies as $k=>$l): ?>
        <option value="<?=$k?>" <?=$k===$company?'selected':''?>><?=htmlspecialchars($l)?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="po-btn po-btn-ghost" onclick="poCopiar()" style="font-size:12px;padding:6px 12px"><i class="bi bi-clipboard"></i> Copiar resumo</button>
    <button class="po-btn po-btn-primary" onclick="window.print()" style="font-size:12px;padding:6px 12px"><i class="bi bi-download"></i> Exportar</button>
  </div>
</div>

<!-- Sub-nav -->
<div class="po-subnav">
  <a href="/admin/painel-obras" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
  <a href="/admin/painel-obras/obras"><i class="bi bi-buildings"></i> Obras</a>
  <a href="/admin/painel-obras/lancamentos"><i class="bi bi-cash-stack"></i> Lançamentos</a>
  <a href="/admin/painel-obras/planilha"><i class="bi bi-table"></i> Planilha de Controle</a>
  <a href="/admin/painel-obras/depara"><i class="bi bi-arrow-left-right"></i> De-para Categorias</a>
  <a href="/admin/painel-obras/pauta"><i class="bi bi-calendar-check"></i> Pauta de Revisão</a>
  <button class="po-btn-config" onclick="poAbrirConfig()" style="background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.3);color:#2563eb;padding:6px 14px;border-radius:8px;font-size:13px;cursor:pointer;margin-left:auto">
    <i class="bi bi-funnel"></i> Filtros
  </button>
</div>

<!-- KPIs -->
<div class="po-kpis">
  <?php for($i=0;$i<6;$i++): ?>
  <div class="po-card po-kpi" id="kpi-<?=$i?>">
    <div class="po-skel" style="height:30px;margin-bottom:8px"></div>
    <div class="po-skel" style="height:16px;width:60%"></div>
  </div>
  <?php endfor; ?>
</div>
<p style="font-size:11px;color:var(--po-txt2);margin:-8px 0 20px">Valores consolidados de todas as obras ativas no período selecionado.</p>

<!-- Obras em Alerta -->
<div style="margin-bottom:20px">
  <div class="po-stitle">Obras em Alerta <span class="po-pill danger" id="po-alerta-pill"><span class="dot" style="background:var(--po-red)"></span>…</span></div>
  <p class="po-ssub">As três obras que mais exigem decisão da diretoria neste mês</p>
  <div class="po-alertas" id="po-alertas">
    <?php for($i=0;$i<3;$i++): ?>
    <div class="po-card"><div class="po-skel" style="height:80px"></div></div>
    <?php endfor; ?>
  </div>
</div>

<!-- Tabela Desempenho -->
<div class="po-card" style="padding:0;margin-bottom:20px">
  <div style="padding:16px 20px 0">
    <div class="po-stitle">Desempenho por Obra <span class="po-pill info" id="po-obras-pill"><span class="dot" style="background:var(--po-blue)"></span>…</span></div>
    <p class="po-ssub" style="margin-bottom:12px">Reformas e construções ordenadas pelo código</p>
  </div>
  <div class="po-filters">
    <input type="text" id="po-busca" class="po-input-dark" style="flex:1;min-width:200px" placeholder="Buscar por nome ou código (ex.: REF-07)" oninput="poFiltrar()">
    <select id="po-ftipo" class="po-input-dark" onchange="poFiltrar()">
      <option value="">Todos os tipos</option><option>Reforma</option><option>Construção</option>
    </select>
    <select id="po-fsit" class="po-input-dark" onchange="poFiltrar()">
      <option value="">Todas as situações</option><option value="ok">OK</option><option value="warn">Atenção</option><option value="danger">Estouro</option>
    </select>
    <select id="po-fcat" class="po-input-dark" onchange="poFiltrar()">
      <option value="">Todas as categorias</option>
      <?php foreach($categoriasObra as $c): ?><option><?=htmlspecialchars($c)?></option><?php endforeach; ?>
    </select>
    <button class="po-btn po-btn-ghost" onclick="poResetFiltros()" style="padding:7px 12px;font-size:12px">Limpar</button>
  </div>
  <div class="po-table-wrap">
    <table class="po-table">
      <thead><tr>
        <th>Obra</th><th>Tipo</th><th class="r">Contratado</th><th class="r">Orçado</th>
        <th class="r">Gasto</th><th class="r">Desvio</th><th>Consumo</th><th>Situação</th><th>Conciliação</th>
      </tr></thead>
      <tbody id="po-tbody"><tr><td colspan="9" style="text-align:center;padding:32px;color:var(--po-txt2)">Carregando…</td></tr></tbody>
    </table>
  </div>
  <div style="padding:10px 20px;font-size:12px;color:var(--po-txt2);border-top:1px solid var(--po-border)">
    Mostrando <span id="po-mostrando">0</span> obras •
    Desvio = Gasto − Orçado. Atenção até 5% acima; Estouro acima de 5%.
  </div>
</div>

<!-- Orçado x Gasto por Categoria -->
<div class="po-card" style="margin-bottom:20px">
  <div class="po-stitle">Orçado × Gasto por Categoria
    <div style="display:flex;gap:12px;font-size:12px"><span style="color:var(--po-gray)">■ Orçado</span><span style="color:var(--po-blue-t)">■ Gasto</span></div>
  </div>
  <p class="po-ssub">Consolidado das obras ativas no período — valores em reais</p>
  <div id="po-cat-rows"><div class="po-skel" style="height:200px"></div></div>
</div>

<!-- Conciliação Nibo -->
<div class="po-card" style="margin-bottom:20px">
  <div class="po-stitle">Conciliação Nibo <span class="po-pill ok" id="po-conc-pill"><span class="dot" style="background:var(--po-green)"></span>…</span></div>
  <p class="po-ssub">Comparativo entre lançamentos do Nibo e a planilha de controle de obra</p>
  <div class="po-conc-cards" id="po-conc-cards">
    <div class="po-conc-card"><div class="po-skel" style="height:40px"></div></div>
    <div class="po-conc-card"><div class="po-skel" style="height:40px"></div></div>
    <div class="po-conc-card"><div class="po-skel" style="height:40px"></div></div>
  </div>
  <div class="po-table-wrap">
    <table class="po-table">
      <thead><tr><th>Obra</th><th class="r">Total Nibo</th><th class="r">Total Planilha</th><th class="r">Diferença</th></tr></thead>
      <tbody id="po-conc-tbody"></tbody>
    </table>
  </div>
</div>

<!-- Fluxo Anual -->
<div class="po-card" style="margin-bottom:20px">
  <div class="po-stitle">Fluxo de Caixa <?=date('Y')?>
    <div style="display:flex;gap:12px;font-size:12px">
      <span style="color:#3b82f6">■ Realizado</span>
      <span style="color:#22d3ee">■ A pagar</span>
      <span style="color:var(--po-gray2)">■ Previsto</span>
    </div>
  </div>
  <p class="po-ssub">Realizado, a pagar e previsto por mês — em R$ mil</p>
  <div class="po-fluxo-chart" id="po-fluxo-chart"><div class="po-skel" style="width:100%;height:200px"></div></div>
  <div class="po-fluxo-boxes" id="po-fluxo-boxes"></div>
</div>

<!-- Ações Rápidas -->
<div class="po-acoes">
  <div class="po-card">
    <div style="font-size:14px;font-weight:600;color:#0f172a;margin-bottom:6px">Exportar relatório</div>
    <div style="font-size:13px;color:var(--po-txt2);margin-bottom:12px">Resumo textual do painel para a diretoria.</div>
    <button class="po-btn po-btn-primary" onclick="poCopiar()"><i class="bi bi-clipboard-check"></i> Gerar relatório</button>
  </div>
  <div class="po-card">
    <div style="font-size:14px;font-weight:600;color:#0f172a;margin-bottom:6px">Registrar revisão</div>
    <div style="font-size:13px;color:var(--po-txt2);margin-bottom:12px">Adiciona obras em alerta à pauta de sexta.</div>
    <button class="po-btn po-btn-ghost" onclick="poAdicionarPauta()"><i class="bi bi-calendar-plus"></i> Adicionar à pauta</button>
  </div>
  <div class="po-card">
    <div style="font-size:14px;font-weight:600;color:#0f172a;margin-bottom:6px">Limpar filtros</div>
    <div style="font-size:13px;color:var(--po-txt2);margin-bottom:12px">Restaura a tabela sem restrições.</div>
    <button class="po-btn po-btn-ghost" onclick="poResetFiltros()"><i class="bi bi-arrow-counterclockwise"></i> Redefinir filtros</button>
  </div>
</div>

<!-- Lançamentos sem obra vinculada -->
<div id="po-sem-obra-wrap" style="display:none;margin-bottom:20px">
  <div class="po-card">
    <div class="po-stitle" style="color:var(--po-yellow-t)"><i class="bi bi-exclamation-triangle-fill"></i> Lançamentos sem obra vinculada <span id="po-sem-obra-count" class="po-pill warn"></span></div>
    <p class="po-ssub">Centro de custo não está vinculado a nenhuma obra. <a href="/admin/painel-obras/obras" style="color:var(--po-blue-t)">Vincular agora</a></p>
    <div class="po-table-wrap">
      <table class="po-table" id="po-sem-obra-table">
        <thead><tr><th>Data</th><th>Contato</th><th>Centro de custo</th><th>Descrição</th><th class="r">Valor</th><th>Status</th></tr></thead>
        <tbody id="po-sem-obra-tbody"></tbody>
      </table>
    </div>
  </div>
</div>

<!-- Rodapé -->
<div style="border-top:1px solid var(--po-border);padding-top:14px;display:flex;justify-content:space-between;font-size:12px;color:var(--po-txt2);flex-wrap:wrap;gap:8px">
  <span>Brooks Construtora — Painel Financeiro</span>
  <span id="po-footer-sync">—</span>
</div>

</div><!-- /po -->
<div id="po-toast"></div>

<!-- Modal de Lançamentos -->
<div id="po-modal-lancamentos" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.7);z-index:10000;overflow-y:auto;padding:20px">
  <div style="max-width:1200px;margin:40px auto;background:var(--po-card);border-radius:16px;border:1px solid var(--po-border)">
    <div style="padding:20px;border-bottom:1px solid var(--po-border);display:flex;justify-content:space-between;align-items:center">
      <div>
        <div style="font-size:18px;font-weight:700;color:#0f172a" id="po-modal-title">Lançamentos da Obra</div>
        <div style="font-size:13px;color:var(--po-txt2);margin-top:4px" id="po-modal-subtitle"></div>
      </div>
      <button onclick="poFecharModal()" style="background:none;border:none;color:var(--po-txt);font-size:24px;cursor:pointer;padding:0;width:32px;height:32px">&times;</button>
    </div>
    
    <!-- Tabs -->
    <div style="display:flex;gap:4px;padding:16px 20px 0;border-bottom:1px solid var(--po-border)">
      <button class="po-tab active" data-tab="pagos" onclick="poMudarTab('pagos')">Pagos (<span id="po-tab-count-pagos">0</span>)</button>
      <button class="po-tab" data-tab="a_pagar" onclick="poMudarTab('a_pagar')">A Pagar (<span id="po-tab-count-a_pagar">0</span>)</button>
      <button class="po-tab" data-tab="a_receber" onclick="poMudarTab('a_receber')">A Receber (<span id="po-tab-count-a_receber">0</span>)</button>
    </div>
    
    <div style="padding:20px;max-height:60vh;overflow-y:auto" id="po-modal-body"></div>
  </div>
</div>

<style>
.po-tab { background:transparent;border:none;padding:10px 16px;font-size:13px;font-weight:600;color:var(--po-txt2);cursor:pointer;border-bottom:2px solid transparent;transition:all .15s; }
.po-tab.active { color:var(--po-blue-t);border-bottom-color:var(--po-blue); }
.po-tab:hover { color:var(--po-txt); }
.po-lanc-item { padding:12px;background:var(--po-input);border:1px solid var(--po-border);border-radius:8px;margin-bottom:8px; }
.po-lanc-header { display:flex;justify-content:space-between;align-items:start;margin-bottom:8px; }
.po-lanc-valor { font-size:16px;font-weight:700;color:var(--po-blue-t); }
.po-lanc-info { font-size:12px;color:var(--po-txt2);margin-bottom:2px; }
.po-lanc-cat { display:inline-block;background:rgba(59,130,246,.15);color:var(--po-blue-t);padding:2px 8px;border-radius:6px;font-size:11px;font-weight:600;margin-top:4px; }
</style>

<script>
(function(){
'use strict';
let _d = null;
let _obras = [];
const BRL = v => (v==null || v===undefined)?'—':new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL'}).format(v);
const PCT = v => (v>0?'+':'')+v.toFixed(1).replace('.',',')+' %';
const mesAtual = new Date().getMonth()+1;
const catObra = <?=json_encode($categoriasObra)?>;

console.log('[PAINEL OBRAS] Categorias carregadas:', catObra);
console.log('[PAINEL OBRAS] Total de categorias:', catObra ? catObra.length : 0);

window.poLoad = function(){
  console.log('%c[PAINEL OBRAS] ========== CARREGANDO DADOS ==========', 'color: #3b82f6; font-weight: bold; font-size: 14px');
  
  const poEl = document.getElementById('po');
  if (!poEl) {
    console.error('Elemento #po não encontrado');
    return;
  }
  
  const per = document.getElementById('po-periodo')?.value || '<?=date('Y-m')?>';
  const co  = document.getElementById('po-company')?.value || 'brooks';
  
  console.log('[PAINEL OBRAS] Parâmetros:', { periodo: per, company: co });
  
  // Mostra overlay de loading (não destroi o DOM)
  let loadingOverlay = document.getElementById('po-loading-overlay');
  if (!loadingOverlay) {
    loadingOverlay = document.createElement('div');
    loadingOverlay.id = 'po-loading-overlay';
    loadingOverlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(11,18,32,0.95);z-index:9999;display:flex;align-items:center;justify-content:center;flex-direction:column;color:#64748b';
    document.body.appendChild(loadingOverlay);
  }
  loadingOverlay.innerHTML = '<div class="spinner-border" role="status"></div><div style="margin-top:16px">Carregando dados do Nibo...</div>';
  loadingOverlay.style.display = 'flex';
  
  const url = '/admin/painel-obras/dashboard-data?periodo='+per+'&company='+co;
  console.log('[PAINEL OBRAS] Fazendo requisição para:', url);
  
  fetch(url)
    .then(r=>{
      console.log('[PAINEL OBRAS] Resposta recebida, status:', r.status);
      return r.json();
    })
    .then(res=>{
      console.log('[PAINEL OBRAS] Dados JSON parseados:', res);
      
      loadingOverlay.style.display = 'none';
      
      if(!res.ok){
        const msg = res.error || 'Erro desconhecido';
        console.error('[PAINEL OBRAS] ❌ Erro no backend:', msg);
        loadingOverlay.innerHTML = '<div style="text-align:center;padding:40px;max-width:500px"><div style="color:#dc2626;font-size:48px;margin-bottom:16px"><i class="bi bi-exclamation-triangle"></i></div><div style="color:#0f172a;font-size:18px;font-weight:600;margin-bottom:8px">Erro ao carregar dados</div><div style="color:#64748b;font-size:14px;margin-bottom:24px">'+msg+'</div><button class="po-btn-primary" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i> Tentar novamente</button></div>';
        loadingOverlay.style.display = 'flex';
        poToast('Erro: '+msg,'error');
        return;
      }
      
      console.log('%c[PAINEL OBRAS] ✅ Dados carregados com sucesso!', 'color: #10b981; font-weight: bold');
      console.log('[PAINEL OBRAS] 📊 KPIs:', res.data.kpis);
      console.log('[PAINEL OBRAS] 🏗️  Total de obras:', res.data.obras?.length || 0);
      console.log('[PAINEL OBRAS] 📋 Lançamentos por obra:', Object.keys(res.data.lancamentos_por_obra || {}).length + ' obras com lançamentos');
      
      // ⚠️ ALERTA: Se houver mais obras com lançamentos do que obras cadastradas
      const obrasComLancs = Object.keys(res.data.lancamentos_por_obra || {}).length;
      const obrasCadastradas = res.data.obras?.length || 0;
      if (obrasComLancs > obrasCadastradas) {
        console.warn('%c[PAINEL OBRAS] ⚠️  ATENÇÃO: Há ' + obrasComLancs + ' obras com lançamentos, mas apenas ' + obrasCadastradas + ' cadastradas!', 'color: #f59e0b; font-weight: bold; font-size: 13px');
        console.warn('[PAINEL OBRAS] Isso significa que existem centros de custo no Nibo que não foram convertidos em obras.');
        console.warn('[PAINEL OBRAS] IDs das obras com lançamentos:', Object.keys(res.data.lancamentos_por_obra || {}));
        console.warn('[PAINEL OBRAS] IDs das obras cadastradas:', (res.data.obras || []).map(o => o.id));
      }
      
      console.log('[PAINEL OBRAS] 🏷️  Categorias Nibo:', Object.keys(res.data.categorias_nibo || {}).length + ' categorias');
      if (Object.keys(res.data.categorias_nibo || {}).length > 0) {
        console.log('[PAINEL OBRAS] 📋 Lista de categorias Nibo:', Object.keys(res.data.categorias_nibo || {}).slice(0, 10).join(', ') + '...');
      }
      console.log('[PAINEL OBRAS] 📦 Gasto por categoria obra:', Object.keys(res.data.gasto_por_cat || {}).length + ' categorias');
      if (Object.keys(res.data.gasto_por_cat || {}).length > 0) {
        console.log('[PAINEL OBRAS] 💰 Categorias com gasto:', Object.keys(res.data.gasto_por_cat || {}).join(', '));
      }
      console.log('[PAINEL OBRAS] ⚠️  Sem obra vinculada:', res.data.sem_obra_count || 0, 'lançamentos (total: R$', (res.data.sem_obra_total || 0).toFixed(2), ')');
      console.log('[PAINEL OBRAS] 🔄 Sincronização:', res.synced_at || 'N/A');
      
      // Mostra mensagem se obras foram criadas
      if(res.sync_info && res.sync_info.criadas > 0) {
        console.log('[PAINEL OBRAS] 🆕 Obras criadas automaticamente:', res.sync_info.criadas);
        poToast(res.sync_info.criadas + ' obra(s) criada(s) automaticamente a partir dos centros de custo do Nibo', 'success');
      }
      
      // Verifica se há obras cadastradas
      if(res.data.kpis.obras_total === 0) {
        console.warn('[PAINEL OBRAS] ⚠️  NENHUMA OBRA ENCONTRADA!');
        console.warn('[PAINEL OBRAS] Possíveis causas:');
        console.warn('  1. Não há dados sincronizados do Nibo');
        console.warn('  2. Lançamentos não têm centros de custo associados');
        console.warn('  3. Período selecionado não contém lançamentos');
        loadingOverlay.innerHTML = '<div style="text-align:center;padding:40px;max-width:600px"><div style="color:#2563eb;font-size:48px;margin-bottom:16px"><i class="bi bi-building-exclamation"></i></div><div style="color:#0f172a;font-size:18px;font-weight:600;margin-bottom:8px">Nenhuma obra/centro de custo encontrado</div><div style="color:#64748b;font-size:14px;margin-bottom:16px">O painel de obras funciona a partir dos centros de custo do Nibo.</div><div style="color:#64748b;font-size:13px;margin-bottom:24px">Certifique-se de que:<br>1. Há dados sincronizados do Nibo (aba Financeiro)<br>2. Os lançamentos têm centros de custo associados</div><a href="/admin/finance" class="po-btn-primary"><i class="bi bi-arrow-clockwise"></i> Ir para Financeiro</a></div>';
        loadingOverlay.style.display = 'flex';
        return;
      }
      
      // Log detalhado de cada obra
      console.group('[PAINEL OBRAS] 📋 Detalhamento das Obras');
      (res.data.obras || []).forEach((obra, idx) => {
        const lancs = res.data.lancamentos_por_obra?.[obra.id];
        console.log(`Obra ${idx + 1}: ${obra.codigo} - ${obra.nome}`);
        console.log('  ├─ ID:', obra.id);
        console.log('  ├─ Tipo:', obra.tipo);
        console.log('  ├─ Contratado:', 'R$', obra.contratado.toFixed(2));
        console.log('  ├─ Orçado:', 'R$', obra.orcado.toFixed(2));
        console.log('  ├─ Gasto:', 'R$', obra.gasto.toFixed(2));
        console.log('  ├─ A Pagar:', 'R$', obra.a_pagar.toFixed(2));
        console.log('  ├─ Desvio:', 'R$', obra.desvio.toFixed(2), '(' + obra.desvio_pct.toFixed(1) + '%)');
        console.log('  ├─ Situação:', obra.situacao);
        console.log('  ├─ Lançamentos pagos:', lancs?.pagos?.length || 0);
        console.log('  ├─ Lançamentos a pagar:', lancs?.a_pagar?.length || 0);
        console.log('  └─ Top categorias:', obra.top_cats || []);
      });
      console.groupEnd();
      
      console.log('%c[PAINEL OBRAS] ========== FIM DO CARREGAMENTO ==========', 'color: #3b82f6; font-weight: bold; font-size: 14px');
      
      _d=res.data; 
      renderAll(res.data,res.synced_at);
    }).catch(e=>{
      console.error('%c[PAINEL OBRAS] ❌ ERRO FATAL:', 'color: #dc2626; font-weight: bold', e);
      console.error('[PAINEL OBRAS] Stack:', e.stack);
      loadingOverlay.innerHTML = '<div style="text-align:center;padding:40px;max-width:500px"><div style="color:#dc2626;font-size:48px;margin-bottom:16px"><i class="bi bi-wifi-off"></i></div><div style="color:#0f172a;font-size:18px;font-weight:600;margin-bottom:8px">Falha de conexão</div><div style="color:#64748b;font-size:14px;margin-bottom:24px">'+e.message+'</div><button class="po-btn-primary" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i> Recarregar</button></div>';
      loadingOverlay.style.display = 'flex';
      poToast('Falha: '+e.message,'error');
    });
};

// Aguarda o DOM carregar
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() {
    setTimeout(poLoad, 100);
  });
} else {
  setTimeout(poLoad, 100);
}

function renderAll(d,at){
  renderKpis(d.kpis);
  renderAlertas(d.obras_alerta||[]);
  renderTabela(d.obras||[]);
  renderCat(d.gasto_por_cat||{},d.orcado_por_cat||{});
  renderConc(d.conciliacao||{});
  renderFluxo(d.fluxo_mensal||[]);
  renderMeta(d,at);
  renderSemObra(d);
}

function renderMeta(d,at){
  document.getElementById('po-ativas').textContent=d.kpis.obras_total;
  if(at){
    const dt=at.substring(0,10).split('-').reverse().join('/');
    document.getElementById('po-sync-time').textContent='Última atualização: '+dt;
    document.getElementById('po-footer-sync').textContent='Sinc. Nibo: '+at.substring(0,16).replace('T',' ');
  }
  if(d.nao_mapeadas>0){
    document.getElementById('po-pending-msg').textContent=d.nao_mapeadas+' categoria(s) do Nibo sem de-para.';
    document.getElementById('po-pending-bar').style.display='flex';
  }
}

function renderKpis(k){
  console.log('[PAINEL OBRAS] KPIs recebidos:', k);
  console.log('[PAINEL OBRAS] Saldo:', k.saldo);
  
  const defs=[
    {icon:'bi-file-earmark-text',ib:'rgba(59,130,246,.15)',ic:'#2563eb',pill:'Período',pc:'info',
     val:BRL(k.contratado),note:'Soma dos contratos das '+k.obras_total+' obras',
     pct:100,bc:'var(--po-blue)'},
    {icon:'bi-calculator',ib:'rgba(100,116,139,.15)',ic:'#64748b',
     pill:k.contratado>0?PCT(((k.orcado/k.contratado)-1)*100)+' do contratado':'—',pc:'pending',
     val:BRL(k.orcado),note:'Base de referência para controle',
     pct:k.contratado>0?Math.min((k.orcado/k.contratado)*100,100):0,bc:'var(--po-gray)'},
    {icon:'bi-cash-stack',ib:'rgba(59,130,246,.15)',ic:'#2563eb',
     pill:k.orcado>0?PCT(((k.gasto/k.orcado)-1)*100)+' do orçado':'—',
     pc:k.gasto>k.orcado?'danger':'ok',
     val:BRL(k.gasto),note:'Lançamentos pagos no período',
     pct:k.orcado>0?Math.min((k.gasto/k.orcado)*100,100):0,
     bc:'linear-gradient(90deg,var(--po-blue),var(--po-cyan))'},
    {icon:'bi-wallet2',ib:k.saldo>=0?'rgba(16,185,129,.15)':'rgba(239,68,68,.15)',
     ic:k.saldo>=0?'var(--po-green-t)':'var(--po-red-t)',
     pill:k.contratado>0?PCT((k.saldo/k.contratado)*100)+' do contratado':'—',
     pc:k.saldo>=0?'ok':'danger',
     val:BRL(k.saldo),vc:k.saldo>=0?'var(--po-green-t)':'var(--po-red-t)',
     note:'Contratado - Gasto Real',
     pct:k.contratado>0?Math.min(Math.abs((k.saldo/k.contratado)*100),100):0,
     bc:k.saldo>=0?'var(--po-green)':'var(--po-red)'},
    {icon:'bi-graph-up-arrow',ib:k.desvio>0?'rgba(239,68,68,.15)':'rgba(16,185,129,.15)',
     ic:k.desvio>0?'var(--po-red-t)':'var(--po-green-t)',
     pill:k.orcado>0?PCT((k.desvio/k.orcado)*100):'—',pc:k.desvio>0?'danger':'ok',
     val:(k.desvio>0?'+':'')+BRL(k.desvio),vc:k.desvio>0?'var(--po-red-t)':'var(--po-green-t)',
     note:'Gasto '+(k.desvio>0?'acima':'abaixo')+' do orçamento',
     pct:k.orcado>0?Math.min(Math.abs((k.desvio/k.orcado)*100),100):0,
     bc:k.desvio>0?'var(--po-red)':'var(--po-green)'},
    {icon:'bi-exclamation-triangle',ib:'rgba(245,158,11,.15)',ic:'var(--po-yellow-t)',
     pill:k.obras_atencao+' em atenção',pc:'warn',
     val:k.obras_estouro+' de '+k.obras_total,note:'Desvio acima de 5% do orçado',
     pct:k.obras_total>0?(k.obras_estouro/k.obras_total)*100:0,bc:'var(--po-yellow)'},
  ];
  const labels=['Valor Contratado Total','Orçamento Estimado','Gasto Real Acumulado','Saldo Disponível','Desvio Global','Obras em Estouro'];
  defs.forEach((d,i)=>{
    const el=document.getElementById('kpi-'+i);
    el.innerHTML=`<div class="po-kpi-icon" style="background:${d.ib};color:${d.ic}"><i class="bi ${d.icon}"></i></div>
    <span class="po-kpi-pill po-pill ${d.pc}">${d.pill||''}</span>
    <div class="po-kpi-label">${labels[i]}</div>
    <div class="po-kpi-value" style="${d.vc?'color:'+d.vc:''}">${d.val}</div>
    <div class="po-kpi-bar"><div class="po-kpi-bar-fill" style="width:0%;background:${d.bc}" data-pct="${d.pct}"></div></div>
    <div class="po-kpi-note">${d.note}</div>`;
  });
  requestAnimationFrame(()=>{
    document.querySelectorAll('.po-kpi-bar-fill').forEach(b=>{b.style.width=Math.min(parseFloat(b.dataset.pct||0),100)+'%';});
  });
}

function renderAlertas(list){
  const el=document.getElementById('po-alertas');
  document.getElementById('po-alerta-pill').innerHTML=`<span class="dot" style="background:var(--po-red)"></span>${list.length} obra(s)`;
  if(!list.length){
    el.innerHTML='<div class="po-card" style="grid-column:1/-1;text-align:center;padding:32px;color:var(--po-txt2)"><i class="bi bi-check-circle" style="font-size:24px;color:var(--po-green-t)"></i><br>Nenhuma obra em alerta neste período.</div>';
    return;
  }
  el.innerHTML=list.map(a=>{
    const critico=a.desvio_pct>10;
    const pc=critico?'danger':a.situacao==='danger'?'danger':'warn';
    const lbl=critico?'ESTOURO CRÍTICO':a.situacao==='danger'?'ESTOURO':'ATENÇÃO';
    const pct=a.orcado>0?Math.min((a.gasto/a.orcado)*100,120):0;
    const bc=pc==='danger'?'var(--po-red)':'var(--po-yellow)';
    const cats=(a.top_cats||[]).filter(Boolean).join(' e ')||'diversas categorias';
    return `<div class="po-card po-alerta-card ${a.situacao==='warn'?'warn':''}">
      <div class="po-code">${a.codigo}</div>
      <div style="font-size:14px;font-weight:700;color:#0f172a;margin-bottom:8px">${a.nome}</div>
      <span class="po-pill ${pc}" style="margin-bottom:10px"><span class="dot" style="background:${bc}"></span>${lbl}</span>
      <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--po-txt2);margin-bottom:4px"><span>Orçado</span><span style="color:var(--po-txt)">${BRL(a.orcado)}</span></div>
      <div style="display:flex;justify-content:space-between;font-size:13px;color:var(--po-txt2);margin-bottom:6px"><span>Gasto</span><span style="color:var(--po-txt)">${BRL(a.gasto)}</span></div>
      <div class="po-kpi-bar"><div class="po-bar-fill" style="height:6px;width:${pct}%;background:${bc}"></div></div>
      <div style="font-size:12px;color:${bc};margin-top:4px">${a.desvio>0?'+':''}${BRL(a.desvio)} (${PCT(a.desvio_pct)})</div>
      <div style="font-size:12px;color:var(--po-txt2);margin-top:8px;border-top:1px solid var(--po-border);padding-top:6px">Concentrado em <strong style="color:var(--po-txt)">${cats}</strong></div>
    </div>`;
  }).join('');
}

window.poFiltrar = function(){
  if(!_obras.length) return;
  const q=document.getElementById('po-busca').value.toLowerCase();
  const tipo=document.getElementById('po-ftipo').value;
  const sit=document.getElementById('po-fsit').value;
  const cat=document.getElementById('po-fcat').value;
  const f=_obras.filter(o=>{
    if(q&&!o.nome.toLowerCase().includes(q)&&!o.codigo.toLowerCase().includes(q)) return false;
    if(tipo&&o.tipo!==tipo) return false;
    if(sit&&o.situacao!==sit) return false;
    if(cat&&!(o.gasto_cat&&o.gasto_cat[cat]>0)) return false;
    return true;
  });
  document.getElementById('po-mostrando').textContent=f.length;
  document.getElementById('po-obras-pill').innerHTML=`<span class="dot" style="background:var(--po-blue)"></span>${f.length} obras`;
  const tb=document.getElementById('po-tbody');
  if(!f.length){tb.innerHTML='<tr><td colspan="9" style="text-align:center;padding:32px;color:var(--po-txt2)">Nenhuma obra corresponde aos filtros.</td></tr>';return;}
  tb.innerHTML=f.map(o=>{
    const pct=o.orcado>0?Math.min((o.gasto/o.orcado)*100,120):0;
    const bc=o.situacao==='danger'?'var(--po-red)':o.situacao==='warn'?'var(--po-yellow)':'var(--po-blue)';
    const dCls=o.desvio>0?'po-desvio-pos':'po-desvio-neg';
    const dPct=o.orcado>0?((o.desvio/o.orcado)*100).toFixed(1).replace('.',',')+' %':'—';
    const badge=o.tipo==='Reforma'?'<span class="po-badge-ref">Reforma</span>':'<span class="po-badge-con">Construção</span>';
    const conc=o.planilha_total==null
      ?'<span class="po-pill pending">Pendente</span>'
      :Math.abs(o.planilha_dif||0)===0?'<span class="po-pill ok">Conciliado</span>'
      :Math.abs(o.planilha_dif||0)<=2000?'<span class="po-pill warn">Div. pequena</span>'
      :'<span class="po-pill danger">Divergente</span>';
    return `<tr>
      <td><span class="po-code">${o.codigo}</span><span class="po-name">${o.nome}</span></td>
      <td>${badge}</td>
      <td class="r">${BRL(o.contratado)}</td>
      <td class="r">${o.orcado>0?BRL(o.orcado):'<a href="/admin/painel-obras/obras" style="color:var(--po-blue-t);font-size:11px">— Preencher</a>'}</td>
      <td class="r"><a href="#" onclick="poVerLancamentos(${o.id},'${o.codigo}');return false" style="color:var(--po-blue-t)">${BRL(o.gasto)}</a></td>
      <td class="r ${dCls}">${o.orcado>0?(o.desvio>0?'+':'')+BRL(o.desvio):'—'}<br><small>${o.orcado>0?(o.desvio>0?'+':'')+dPct:''}</small></td>
      <td><div class="po-bar-track" style="width:120px"><div class="po-bar-fill" style="width:${pct}%;background:${bc}"></div></div><div style="font-size:10px;color:var(--po-txt2);margin-top:2px">${pct.toFixed(0)}% do orçado</div></td>
      <td><span class="po-pill ${o.situacao}"><span class="dot" style="background:${bc}"></span>${o.situacao==='ok'?'OK':o.situacao==='warn'?'Atenção':'Estouro'}</span></td>
      <td>${conc}</td>
    </tr>`;
  }).join('');
};

function renderTabela(obras){_obras=obras;poFiltrar();}

window.poResetFiltros=function(){
  ['po-busca','po-ftipo','po-fsit','po-fcat'].forEach(id=>{document.getElementById(id).value='';});
  poFiltrar();poToast('Filtros redefinidos.');
};

function renderCat(gasto,orcado){
  // Filtra apenas categorias com valores > 0
  const categoriasComValor = catObra.filter(cat => {
    const g = gasto[cat] || 0;
    const o = orcado[cat] || 0;
    return (g > 0 || o > 0);
  });
  
  console.log('[PAINEL OBRAS] renderCat - Total categorias:', catObra.length);
  console.log('[PAINEL OBRAS] renderCat - Categorias com valor > 0:', categoriasComValor.length);
  console.log('[PAINEL OBRAS] renderCat - Categorias filtradas:', categoriasComValor);
  
  const all=[...Object.values(gasto),...Object.values(orcado)];
  const mx=Math.max(...all,1);
  const el=document.getElementById('po-cat-rows');
  el.innerHTML=categoriasComValor.map(cat=>{
    const g=gasto[cat]||0,o=orcado[cat]||0;
    const gP=(g/mx)*100,oP=(o/mx)*100;
    const dv=g-o;
    const dc=dv>0?'var(--po-red-t)':dv<0?'var(--po-green-t)':'var(--po-txt2)';
    return `<div class="po-cat-row">
      <div class="po-cat-label">${cat}</div>
      <div class="po-cat-bars">
        <div class="po-cat-bar" style="width:${oP}%;background:var(--po-gray2)" title="Orçado: ${BRL(o)}"></div>
        <div class="po-cat-bar" style="width:0%;background:linear-gradient(90deg,var(--po-blue),var(--po-cyan))" data-pct="${gP}" title="Gasto: ${BRL(g)}"></div>
        <div style="font-size:11px;color:var(--po-txt2)">Orçado ${BRL(o)} · Gasto ${BRL(g)} · Desvio <span style="color:${dc}">${(dv>0?'+':'')+BRL(dv)}</span></div>
      </div>
    </div>`;
  }).join('');
  if(gasto['Sem categoria / Não mapeado']){
    const g=gasto['Sem categoria / Não mapeado'];
    el.innerHTML+=`<div class="po-cat-row"><div class="po-cat-label" style="color:var(--po-txt2)">Sem categoria</div>
    <div class="po-cat-bars"><div class="po-cat-bar" style="width:${(g/mx)*100}%;background:var(--po-gray)"></div>
    <div style="font-size:11px;color:var(--po-txt2)">Gasto ${BRL(g)} · <a href="/admin/painel-obras/depara" style="color:var(--po-blue-t)">Mapear</a></div></div></div>`;
  }
  requestAnimationFrame(()=>{document.querySelectorAll('.po-cat-bar[data-pct]').forEach(b=>{b.style.width=Math.min(parseFloat(b.dataset.pct||0),100)+'%';});});
}

function renderConc(c){
  const p=document.getElementById('po-conc-pill');
  const tot=(c.lista||[]).length;
  p.innerHTML=`<span class="dot" style="background:var(--po-green)"></span>${c.conciliadas||0} de ${tot} conciliadas`;
  document.getElementById('po-conc-cards').innerHTML=`
    <div class="po-conc-card"><div style="font-size:24px;font-weight:700;color:var(--po-green-t)">${c.conciliadas||0}</div><div style="font-size:12px;color:var(--po-txt2)">Conciliadas</div></div>
    <div class="po-conc-card"><div style="font-size:24px;font-weight:700;color:var(--po-yellow-t)">${c.divergencia_pequena||0}</div><div style="font-size:12px;color:var(--po-txt2)">Div. pequena (≤ R$ 2.000)</div></div>
    <div class="po-conc-card"><div style="font-size:24px;font-weight:700;color:var(--po-red-t)">${c.divergentes||0}</div><div style="font-size:12px;color:var(--po-txt2)">Divergentes</div></div>`;
  document.getElementById('po-conc-tbody').innerHTML=(c.lista||[]).map(o=>{
    const dc=o.dif==null?'var(--po-txt2)':Math.abs(o.dif)===0?'var(--po-txt2)':Math.abs(o.dif)<=2000?'var(--po-yellow-t)':'var(--po-red-t)';
    return `<tr>
      <td><span class="po-code">${o.codigo}</span><span class="po-name">${o.nome}</span></td>
      <td class="r">${BRL(o.nibo)}</td>
      <td class="r">${o.planilha!=null?BRL(o.planilha):'<a href="/admin/painel-obras/planilha" style="color:var(--po-blue-t);font-size:11px">— Preencher</a>'}</td>
      <td class="r" style="color:${dc}">${o.dif!=null?(o.dif>0?'+':'')+BRL(o.dif):'—'}</td>
    </tr>`;
  }).join('');
}

function renderFluxo(meses){
  const mx=Math.max(...meses.map(m=>m.pago+m.a_pagar+m.previsto),1);
  const H=180;
  document.getElementById('po-fluxo-chart').innerHTML=meses.map(m=>{
    const pH=Math.round((m.pago/mx)*H);
    const aH=Math.round((m.a_pagar/mx)*H);
    const pvH=Math.round((m.previsto/mx)*H);
    const isAt=m.mes===mesAtual;
    return `<div class="po-fluxo-col" title="${m.label}\nPago: ${BRL(m.pago)}\nA pagar: ${BRL(m.a_pagar)}\nPrevisto: ${BRL(m.previsto)}">
      ${isAt?'<span style="font-size:9px;color:var(--po-cyan);font-weight:700">Atual</span>':''}
      <div class="po-fluxo-bar" style="height:${pvH}px;background:var(--po-gray2)"></div>
      <div class="po-fluxo-bar" style="height:${aH}px;background:var(--po-cyan)"></div>
      <div class="po-fluxo-bar" style="height:${pH}px;background:var(--po-blue)"></div>
      <span class="po-fluxo-mes">${m.label}</span>
    </div>`;
  }).join('');
  const tPago=meses.slice(0,mesAtual).reduce((s,m)=>s+m.pago,0);
  const tApagar=meses.reduce((s,m)=>s+m.a_pagar,0);
  const tPrev=meses.slice(mesAtual).reduce((s,m)=>s+m.previsto,0);
  const nm=meses[mesAtual-1]?.label||'';
  document.getElementById('po-fluxo-boxes').innerHTML=`
    <div class="po-fluxo-box"><div style="font-size:11px;color:var(--po-txt2);margin-bottom:4px">Realizado até ${nm}</div><div style="font-size:18px;font-weight:700;color:var(--po-blue-t)">${BRL(tPago)}</div></div>
    <div class="po-fluxo-box"><div style="font-size:11px;color:var(--po-txt2);margin-bottom:4px">A pagar (comprometido)</div><div style="font-size:18px;font-weight:700;color:var(--po-cyan)">${BRL(tApagar)}</div></div>
    <div class="po-fluxo-box"><div style="font-size:11px;color:var(--po-txt2);margin-bottom:4px">Previsto para ${nm}</div><div style="font-size:18px;font-weight:700;color:var(--po-txt2)">${BRL(tPrev)}</div></div>`;
}

function renderSemObra(d){
  if(!d.sem_obra_count) return;
  document.getElementById('po-sem-obra-wrap').style.display='block';
  document.getElementById('po-sem-obra-count').textContent=d.sem_obra_count+' lançamentos';
}

window.poCopiar=function(){
  if(!_d){poToast('Aguarde os dados carregarem.','error');return;}
  const k=_d.kpis;
  const per=document.getElementById('po-periodo').value;
  const al=(_d.obras_alerta||[]).map(a=>'  - '+a.codigo+' '+a.nome+' ('+PCT(a.desvio_pct)+')').join('\n');
  const txt=['PAINEL FINANCEIRO DE OBRAS','Período: '+per,'',
    'Contratado: '+BRL(k.contratado),'Orçado: '+BRL(k.orcado),'Gasto: '+BRL(k.gasto),
    'Desvio: '+(k.desvio>0?'+':'')+BRL(k.desvio),'Obras em Estouro: '+k.obras_estouro+' de '+k.obras_total,
    '','Obras em Alerta:',al||'  Nenhuma'].join('\n');
  navigator.clipboard.writeText(txt).then(()=>poToast('Copiado!')).catch(()=>poToast('Erro ao copiar.','error'));
};

window.poAdicionarPauta=function(){
  if(!_d||!(_d.obras_alerta||[]).length){poToast('Nenhuma obra em alerta.','error');return;}
  const p=new URLSearchParams();
  _d.obras_alerta.forEach(a=>p.append('obra_ids[]',a.id));
  fetch('/admin/painel-obras/pauta/store',{method:'POST',body:p})
    .then(r=>r.json()).then(res=>{
      if(res.ok) poToast(_d.obras_alerta.length+' obra(s) adicionadas à pauta.');
      else poToast('Erro: '+(res.error||''),'error');
    });
};

window.poToast=function(msg,type='ok'){
  const c=document.getElementById('po-toast');
  const e=document.createElement('div');
  e.className='po-toast-item '+type; e.textContent=msg; c.appendChild(e);
  setTimeout(()=>e.remove(),3200);
};

// ─── Configurações e Filtros ──────────────────────────────────────────

let _poConfig = {
  centros_custo_visiveis: [],
  obras_visiveis: []
};

window.poAbrirConfig=function(){
  console.log('[DEBUG] Abrindo configurações...');
  console.log('[DEBUG] Dados disponíveis:', _d);
  
  // Busca configuração atual do usuário
  fetch('/admin/painel-obras/config/get')
    .then(r=>r.json())
    .then(cfg=>{
      console.log('[DEBUG] Config carregada:', cfg);
      _poConfig=cfg||{centros_custo_visiveis:[],obras_visiveis:[]};
      
      console.log('[DEBUG] Renderizando CCs...');
      poRenderConfigCc();
      
      console.log('[DEBUG] Renderizando Obras...');
      poRenderConfigObras();
      
      console.log('[DEBUG] Abrindo modal...');
      document.getElementById('po-modal-config').style.display='flex';
    })
    .catch(err=>{
      console.error('[DEBUG] Erro ao carregar config:', err);
      poToast('Erro ao carregar configurações','error');
    });
};

window.poFecharConfig=function(){
  document.getElementById('po-modal-config').style.display='none';
};

window.poRenderConfigCc=function(){
  if(!_d||!_d.centros_custo) return;
  const selected=_poConfig.centros_custo_visiveis||[];
  
  const html=_d.centros_custo.map(cc=>{
    // Se lista vazia, marca todos. Senão, marca apenas os que estão na lista
    const isChecked=selected.length===0||selected.includes(cc.id);
    return `<label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:#1e293b">
      <input type="checkbox" value="${cc.id}" ${isChecked?'checked':''} 
             onchange="poToggleCc('${cc.id}',this.checked)"
             style="width:16px;height:16px">
      <span>${cc.name}</span>
    </label>`;
  }).join('');
  
  document.getElementById('po-cfg-cc-list').innerHTML=html;
  
  // Atualiza checkbox "Selecionar Todos" após renderizar
  const allChecked = selected.length === 0 || selected.length === _d.centros_custo.length;
  document.getElementById('po-cfg-select-all').checked = allChecked;
  
  // Limpa campo de busca
  const searchField = document.getElementById('po-cfg-search-cc');
  if(searchField) searchField.value = '';
};

window.poRenderConfigObras=function(){
  if(!_d||!_d.obras) return;
  const selected=_poConfig.obras_visiveis||[];
  
  const html=_d.obras.map(o=>{
    // Se lista vazia, marca todos. Senão, marca apenas os que estão na lista
    const isChecked=selected.length===0||selected.includes(o.id);
    return `<label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:#1e293b">
      <input type="checkbox" value="${o.id}" ${isChecked?'checked':''} 
             onchange="poToggleObra(${o.id},this.checked)"
             style="width:16px;height:16px">
      <span>${o.codigo} - ${o.nome}</span>
    </label>`;
  }).join('');
  
  document.getElementById('po-cfg-obras-list').innerHTML=html;
  
  // Atualiza checkbox "Selecionar Todos" após renderizar
  const allChecked = selected.length === 0 || selected.length === _d.obras.length;
  document.getElementById('po-cfg-select-all-obras').checked = allChecked;
  
  // Limpa campo de busca
  const searchField = document.getElementById('po-cfg-search-obras');
  if(searchField) searchField.value = '';
};

window.poToggleCc=function(ccId,checked){
  console.log('[DEBUG] Toggle CC:', ccId, checked);
  
  // Garante que a lista existe
  if(!_poConfig.centros_custo_visiveis) {
    _poConfig.centros_custo_visiveis = [];
  }
  
  // Se lista estava vazia (= todos selecionados), inicializa com todos os IDs
  if(_poConfig.centros_custo_visiveis.length === 0 && !checked) {
    // Só inicializa se estiver DESMARCANDO (pois vazio = todos marcados)
    _poConfig.centros_custo_visiveis = _d.centros_custo.map(cc=>cc.id);
    console.log('[DEBUG] Lista inicializada com todos os CCs:', _poConfig.centros_custo_visiveis);
  }
  
  if(checked){
    // Adiciona se não estiver na lista
    if(!_poConfig.centros_custo_visiveis.includes(ccId)){
      _poConfig.centros_custo_visiveis.push(ccId);
      console.log('[DEBUG] CC adicionado:', ccId);
    }
  }else{
    // Remove da lista
    const lengthBefore = _poConfig.centros_custo_visiveis.length;
    _poConfig.centros_custo_visiveis = _poConfig.centros_custo_visiveis.filter(id=>id!==ccId);
    console.log('[DEBUG] CC removido:', ccId, '- Antes:', lengthBefore, 'Depois:', _poConfig.centros_custo_visiveis.length);
  }
  
  console.log('[DEBUG] CCs visíveis ATUAIS:', _poConfig.centros_custo_visiveis);
  console.log('[DEBUG] Total:', _poConfig.centros_custo_visiveis.length, 'de', _d.centros_custo.length);
  
  // Atualiza checkbox "Selecionar Todos"
  const allSelected = _poConfig.centros_custo_visiveis.length === _d.centros_custo.length;
  document.getElementById('po-cfg-select-all').checked = allSelected;
};

window.poToggleObra=function(obraId,checked){
  console.log('[DEBUG] Toggle Obra:', obraId, checked);
  
  // Garante que a lista existe
  if(!_poConfig.obras_visiveis) {
    _poConfig.obras_visiveis = [];
  }
  
  // Se lista estava vazia (= todos selecionados), inicializa com todos os IDs
  if(_poConfig.obras_visiveis.length === 0 && !checked) {
    // Só inicializa se estiver DESMARCANDO (pois vazio = todos marcados)
    _poConfig.obras_visiveis = _d.obras.map(o=>o.id);
    console.log('[DEBUG] Lista inicializada com todas as obras:', _poConfig.obras_visiveis);
  }
  
  if(checked){
    // Adiciona se não estiver na lista
    if(!_poConfig.obras_visiveis.includes(obraId)){
      _poConfig.obras_visiveis.push(obraId);
      console.log('[DEBUG] Obra adicionada:', obraId);
    }
  }else{
    // Remove da lista
    const lengthBefore = _poConfig.obras_visiveis.length;
    _poConfig.obras_visiveis = _poConfig.obras_visiveis.filter(id=>id!==obraId);
    console.log('[DEBUG] Obra removida:', obraId, '- Antes:', lengthBefore, 'Depois:', _poConfig.obras_visiveis.length);
  }
  
  console.log('[DEBUG] Obras visíveis ATUAIS:', _poConfig.obras_visiveis);
  console.log('[DEBUG] Total:', _poConfig.obras_visiveis.length, 'de', _d.obras.length);
  
  // Atualiza checkbox "Selecionar Todos"
  const allSelected = _poConfig.obras_visiveis.length === _d.obras.length;
  document.getElementById('po-cfg-select-all-obras').checked = allSelected;
};

window.poToggleAllCc=function(checked){
  console.log('[DEBUG] Toggle All CCs:', checked);
  if(!_d||!_d.centros_custo) return;
  
  if(checked){
    // Marca todos
    _poConfig.centros_custo_visiveis = _d.centros_custo.map(cc=>cc.id);
    console.log('[DEBUG] Todos CCs marcados:', _poConfig.centros_custo_visiveis.length);
  }else{
    // Desmarca todos
    _poConfig.centros_custo_visiveis = [];
    console.log('[DEBUG] Todos CCs desmarcados');
  }
  
  // Atualiza apenas os checkboxes individuais sem re-renderizar
  document.querySelectorAll('#po-cfg-cc-list input[type="checkbox"]').forEach(cb=>{
    cb.checked = checked;
  });
};

window.poToggleAllObras=function(checked){
  console.log('[DEBUG] Toggle All Obras:', checked);
  if(!_d||!_d.obras) return;
  
  if(checked){
    // Marca todos
    _poConfig.obras_visiveis = _d.obras.map(o=>o.id);
    console.log('[DEBUG] Todas obras marcadas:', _poConfig.obras_visiveis.length);
  }else{
    // Desmarca todos
    _poConfig.obras_visiveis = [];
    console.log('[DEBUG] Todas obras desmarcadas');
  }
  
  // Atualiza apenas os checkboxes individuais sem re-renderizar
  document.querySelectorAll('#po-cfg-obras-list input[type="checkbox"]').forEach(cb=>{
    cb.checked = checked;
  });
};

// ─── Funções de Busca/Filtro ─────────────────────────────────────────

window.poFilterCcList=function(searchTerm){
  if(searchTerm===undefined){
    searchTerm = document.getElementById('po-cfg-search-cc').value;
  }
  searchTerm = (searchTerm || '').toLowerCase();
  
  const labels = document.querySelectorAll('#po-cfg-cc-list label');
  
  let visibleCount = 0;
  labels.forEach(label=>{
    const text = label.textContent.toLowerCase();
    if(text.includes(searchTerm)){
      label.style.display = 'flex';
      visibleCount++;
    }else{
      label.style.display = 'none';
    }
  });
  
  console.log('[DEBUG] Filtro CC:', searchTerm, '- Visíveis:', visibleCount, 'de', labels.length);
};

window.poFilterObrasList=function(searchTerm){
  if(searchTerm===undefined){
    searchTerm = document.getElementById('po-cfg-search-obras').value;
  }
  searchTerm = (searchTerm || '').toLowerCase();
  
  const labels = document.querySelectorAll('#po-cfg-obras-list label');
  
  let visibleCount = 0;
  labels.forEach(label=>{
    const text = label.textContent.toLowerCase();
    if(text.includes(searchTerm)){
      label.style.display = 'flex';
      visibleCount++;
    }else{
      label.style.display = 'none';
    }
  });
  
  console.log('[DEBUG] Filtro Obras:', searchTerm, '- Visíveis:', visibleCount, 'de', labels.length);
};

// ─────────────────────────────────────────────────────────────────────

window.poSalvarConfig=function(){
  console.log('[DEBUG] ========== SALVANDO CONFIGURAÇÕES ==========');
  console.log('[DEBUG] Estado atual do _poConfig:', JSON.parse(JSON.stringify(_poConfig)));
  console.log('[DEBUG] CCs visíveis (length):', _poConfig.centros_custo_visiveis?.length);
  console.log('[DEBUG] CCs visíveis (array):', _poConfig.centros_custo_visiveis);
  console.log('[DEBUG] Obras visíveis (length):', _poConfig.obras_visiveis?.length);
  console.log('[DEBUG] Obras visíveis (array):', _poConfig.obras_visiveis);
  console.log('[DEBUG] Total de CCs disponíveis:', _d.centros_custo?.length);
  console.log('[DEBUG] Total de obras disponíveis:', _d.obras?.length);
  
  // Se todos estão selecionados, salva array vazio (= todos visíveis)
  const ccTodos = _d.centros_custo && _poConfig.centros_custo_visiveis.length === _d.centros_custo.length;
  const obrasTodas = _d.obras && _poConfig.obras_visiveis.length === _d.obras.length;
  
  console.log('[DEBUG] Todos CCs selecionados?', ccTodos, '(', _poConfig.centros_custo_visiveis.length, '===', _d.centros_custo?.length, ')');
  console.log('[DEBUG] Todas obras selecionadas?', obrasTodas, '(', _poConfig.obras_visiveis.length, '===', _d.obras?.length, ')');
  
  const configToSave = {
    centros_custo_visiveis: ccTodos ? [] : _poConfig.centros_custo_visiveis,
    obras_visiveis: obrasTodas ? [] : _poConfig.obras_visiveis,
    filtros_ativos: _poConfig.filtros_ativos || {}
  };
  
  console.log('[DEBUG] ========== CONFIG A SER ENVIADA ==========');
  console.log('[DEBUG]', JSON.stringify(configToSave, null, 2));
  console.log('[DEBUG] ============================================');
  
  fetch('/admin/painel-obras/config/save', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify(configToSave)
  })
  .then(r=>r.json())
  .then(res=>{
    console.log('[DEBUG] ========== RESPOSTA DO SERVIDOR ==========');
    console.log('[DEBUG]', res);
    console.log('[DEBUG] ============================================');
    if(res.ok){
      poToast('Configurações salvas!');
      poFecharConfig();
      console.log('[DEBUG] Recarregando dashboard...');
      poLoad(); // Recarrega dashboard com novos filtros
    }else{
      poToast('Erro ao salvar: '+(res.error||''),'error');
    }
  })
  .catch(err=>{
    console.error('[DEBUG] ========== ERRO AO SALVAR ==========');
    console.error('[DEBUG]', err);
    console.error('[DEBUG] ======================================');
    poToast('Erro de conexão','error');
  });
};

window.poLimparFiltros=function(){
  if(!_d) return;
  // Seleciona todos (= array vazio no backend)
  _poConfig={
    centros_custo_visiveis:[],
    obras_visiveis:[],
    filtros_ativos:{}
  };
  poRenderConfigCc();
  poRenderConfigObras();
  poToast('Filtros limpos. Clique em Salvar para aplicar.');
};

window.poToast=function(msg,type='ok'){
  const c=document.getElementById('po-toast');
  const e=document.createElement('div');
  e.className='po-toast-item '+type; e.textContent=msg; c.appendChild(e);
  setTimeout(()=>e.remove(),3200);
};

window.poVerLancamentos=function(obraId,codigo){
  if(!_d||!_d.lancamentos_por_obra||!_d.lancamentos_por_obra[obraId]){
    poToast('Lançamentos não disponíveis.','error');
    return;
  }
  const lancs=_d.lancamentos_por_obra[obraId];
  const obra=_d.obras.find(o=>o.id===obraId);
  
  window._currentObraId = obraId; // Salva para usar no cálculo de parcelas
  
  document.getElementById('po-modal-title').textContent='Lançamentos da Obra '+codigo;
  
  // Calcula totais de receitas e despesas em "Pagos"
  let totalReceitas = 0;
  let totalDespesas = 0;
  lancs.pagos.forEach(l => {
    if (l.type === 'receivable') {
      totalReceitas += (l.valor_rateado || 0);
    } else {
      totalDespesas += (l.valor_rateado || 0);
    }
  });
  
  // Adiciona resumo visual no subtítulo com cores
  const saldo = totalReceitas - totalDespesas;
  const resumoHtml = `
    <span>${obra?obra.nome:''}</span> • 
    <span style="color:#dc2626">Despesas: ${BRL(totalDespesas)}</span> • 
    <span style="color:#059669">Receitas: ${BRL(totalReceitas)}</span> • 
    <span style="color:${saldo >= 0 ? '#059669' : '#dc2626'};font-weight:600">Saldo: ${BRL(saldo)}</span>
  `;
  document.getElementById('po-modal-subtitle').innerHTML = resumoHtml;
  
  document.getElementById('po-tab-count-pagos').textContent=lancs.pagos.length;
  document.getElementById('po-tab-count-a_pagar').textContent=lancs.a_pagar.length;
  document.getElementById('po-tab-count-a_receber').textContent=lancs.a_receber.length;
  
  window._currentLancs=lancs;
  poMudarTab('pagos');
  document.getElementById('po-modal-lancamentos').style.display='block';
};

window.poFecharModal=function(){
  document.getElementById('po-modal-lancamentos').style.display='none';
};

window.poMudarTab=function(tab){
  document.querySelectorAll('.po-tab').forEach(t=>{
    t.classList.toggle('active',t.dataset.tab===tab);
  });
  if(!window._currentLancs)return;
  
  const body=document.getElementById('po-modal-body');
  
  // Se for "a_receber", mostra parcelas previstas + receitas reais
  if(tab === 'a_receber'){
    const obraId = window._currentObraId;
    const obra = _d.obras.find(o => o.id === obraId);
    
    if(!obra){
      body.innerHTML='<div style="text-align:center;padding:40px;color:var(--po-txt2)">Obra não encontrada.</div>';
      return;
    }
    
    // Calcula parcelas previstas do planejamento financeiro
    const contratado = parseFloat(obra.valor_contratado || 0);
    const entrada = parseFloat(obra.valor_entrada || 0);
    const numParcelas = parseInt(obra.numero_parcelas || 0);
    const freq = obra.frequencia_parcelas || 'Mensal';
    const carencia = parseInt(obra.carencia_meses || 0);
    const dataContrato = obra.data_contrato;
    
    let parcelasPrevistas = [];
    
    if(numParcelas > 0 && contratado > 0){
      const valorFinanciado = contratado - entrada;
      const valorParcela = valorFinanciado / numParcelas;
      
      // Calcula datas das parcelas
      const mesesEntreParcelas = freq === 'Trimestral' ? 3 : freq === 'Semestral' ? 6 : freq === 'Anual' ? 12 : 1;
      
      for(let i = 1; i <= numParcelas; i++){
        const mesesAteVenc = carencia + (i * mesesEntreParcelas);
        const dataVenc = new Date(dataContrato);
        dataVenc.setMonth(dataVenc.getMonth() + mesesAteVenc);
        
        parcelasPrevistas.push({
          numero: i,
          valor: valorParcela,
          vencimento: dataVenc.toISOString().substring(0,10),
          tipo: 'prevista'
        });
      }
    }
    
    // Busca receitas reais do Nibo
    const receitasReais = window._currentLancs.a_receber || [];
    
    // Combina previstas + reais
    let html = '';
    
    // Mostra entrada se houver
    if(entrada > 0){
      html += `<div class="po-lanc-item" style="background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.3);border-left:4px solid #059669">
        <div class="po-lanc-header">
          <div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
              <i class="bi-arrow-down-circle-fill" style="color:#059669;font-size:18px"></i>
              <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;background:rgba(16,185,129,.2);color:#059669">ENTRADA</span>
              <span style="font-weight:600;color:var(--po-txt)">Valor de Entrada</span>
            </div>
            <div class="po-lanc-info"><strong>Vencimento:</strong> ${dataContrato?dataContrato.split('-').reverse().join('/'):'Na assinatura'}</div>
          </div>
          <div style="text-align:right">
            <div class="po-lanc-valor" style="color:#059669;font-size:20px;font-weight:700">+ ${BRL(entrada)}</div>
            <span class="po-pill pending" style="margin-top:6px"><span class="dot" style="background:var(--po-gray)"></span>Previsto</span>
          </div>
        </div>
      </div>`;
    }
    
    // Mostra parcelas previstas
    parcelasPrevistas.forEach(p => {
      html += `<div class="po-lanc-item" style="background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.3);border-left:4px solid #059669">
        <div class="po-lanc-header">
          <div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
              <i class="bi-arrow-down-circle-fill" style="color:#059669;font-size:18px"></i>
              <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;background:rgba(16,185,129,.2);color:#059669">PARCELA ${p.numero}/${numParcelas}</span>
              <span style="font-weight:600;color:var(--po-txt)">Parcela ${p.numero} - ${freq}</span>
            </div>
            <div class="po-lanc-info"><strong>Vencimento previsto:</strong> ${p.vencimento.split('-').reverse().join('/')}</div>
          </div>
          <div style="text-align:right">
            <div class="po-lanc-valor" style="color:#059669;font-size:20px;font-weight:700">+ ${BRL(p.valor)}</div>
            <span class="po-pill pending" style="margin-top:6px"><span class="dot" style="background:var(--po-gray)"></span>Previsto</span>
          </div>
        </div>
      </div>`;
    });
    
    // Mostra receitas reais do Nibo
    if(receitasReais.length > 0){
      html += '<div style="margin:20px 0;border-top:2px solid var(--po-border);padding-top:16px"><strong style="color:var(--po-txt2);font-size:11px;text-transform:uppercase;letter-spacing:0.5px">Receitas Registradas no Nibo</strong></div>';
      
      receitasReais.forEach(l => {
        const statusClass=l.status==='paid'?'ok':l.status==='overdue'?'danger':'warn';
        const statusLabel=l.status==='paid'?'Recebido':l.status==='overdue'?'Vencido':'A receber';
        
        html += `<div class="po-lanc-item" style="background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.3);border-left:4px solid #059669">
          <div class="po-lanc-header">
            <div>
              <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                <i class="bi-arrow-down-circle-fill" style="color:#059669;font-size:18px"></i>
                <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;background:rgba(16,185,129,.2);color:#059669">RECEITA NIBO</span>
                <span style="font-weight:600;color:var(--po-txt)">${l.description||'—'}</span>
              </div>
              <div class="po-lanc-info"><strong style="color:#059669">Cliente:</strong> ${l.contact_name||'—'}</div>
              <div class="po-lanc-info"><strong>Vencimento:</strong> ${l.due_date?l.due_date.substring(0,10).split('-').reverse().join('/'):'—'}</div>
            </div>
            <div style="text-align:right">
              <div class="po-lanc-valor" style="color:#059669;font-size:20px;font-weight:700">+ ${BRL(l.valor_rateado||0)}</div>
              <span class="po-pill ${statusClass}" style="margin-top:6px"><span class="dot" style="background:var(--po-${statusClass==='ok'?'green':statusClass==='danger'?'red':'yellow'})"></span>${statusLabel}</span>
            </div>
          </div>
        </div>`;
      });
    }
    
    if(!html){
      html = '<div style="text-align:center;padding:40px;color:var(--po-txt2)">Configure o Planejamento Financeiro para ver as parcelas previstas.</div>';
    }
    
    body.innerHTML = html;
    return;
  }
  
  // Outras abas (pagos, a_pagar) - código original
  const list=window._currentLancs[tab]||[];
  if(!list.length){
    body.innerHTML='<div style="text-align:center;padding:40px;color:var(--po-txt2)">Nenhum lançamento nesta categoria.</div>';
    return;
  }
  body.innerHTML=list.map(l=>{
    const statusClass=l.status==='paid'?'ok':l.status==='overdue'?'danger':'warn';
    const statusLabel=l.status==='paid'?'Pago':l.status==='overdue'?'Vencido':'Em aberto';
    const catNome=l.categoria_nibo_nome||'Sem categoria';
    const catObra=l.categoria_obra||'Não mapeado';
    
    // Identifica se é RECEITA ou DESPESA
    const isReceita = l.type === 'receivable';
    const tipoLabel = isReceita ? 'RECEITA' : 'DESPESA';
    const tipoBg = isReceita ? 'rgba(16,185,129,.08)' : 'rgba(239,68,68,.08)';
    const tipoBorder = isReceita ? 'rgba(16,185,129,.3)' : 'rgba(239,68,68,.3)';
    const tipoColor = isReceita ? '#059669' : '#dc2626';
    const tipoIcon = isReceita ? 'bi-arrow-down-circle-fill' : 'bi-arrow-up-circle-fill';
    
    return `<div class="po-lanc-item" style="background:${tipoBg};border:1px solid ${tipoBorder};border-left:4px solid ${tipoColor}">
      <div class="po-lanc-header">
        <div>
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
            <i class="${tipoIcon}" style="color:${tipoColor};font-size:18px" title="${tipoLabel}"></i>
            <span style="font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px;background:${isReceita?'rgba(16,185,129,.2)':'rgba(239,68,68,.2)'};color:${tipoColor};letter-spacing:0.5px">${tipoLabel}</span>
            <span style="font-weight:600;color:var(--po-txt)">${l.description||'—'}</span>
          </div>
          <div class="po-lanc-info"><strong style="color:${tipoColor}">${isReceita?'Cliente':'Fornecedor'}:</strong> ${l.contact_name||'—'}</div>
          <div class="po-lanc-info"><strong>Vencimento:</strong> ${l.due_date?l.due_date.substring(0,10).split('-').reverse().join('/'):'—'}</div>
          <div class="po-lanc-info"><strong>Centro de custo:</strong> ${l.cost_center||'—'}</div>
          <div style="margin-top:6px;display:flex;gap:6px;flex-wrap:wrap">
            <span class="po-lanc-cat" title="Categoria Nibo original"><i class="bi bi-tag"></i> ${catNome}</span>
            <span class="po-lanc-cat" style="background:rgba(34,211,238,.15);color:var(--po-cyan)" title="Categoria de obra mapeada"><i class="bi bi-folder"></i> ${catObra}</span>
          </div>
        </div>
        <div style="text-align:right">
          <div class="po-lanc-valor" style="color:${tipoColor};font-size:20px;font-weight:700">
            ${isReceita?'+ ':' '} ${BRL(l.valor_rateado||0)}
          </div>
          <span class="po-pill ${statusClass}" style="margin-top:6px"><span class="dot" style="background:var(--po-${statusClass==='ok'?'green':statusClass==='danger'?'red':'yellow'})"></span>${statusLabel}</span>
        </div>
      </div>
    </div>`;
  }).join('');
};

// poLoad(); // Removido - já é chamado no DOMContentLoaded acima
})();
</script>

<!-- Modal de Configurações/Filtros -->
<div id="po-modal-config" class="po-modal" style="display:none">
  <div class="po-modal-box" style="max-width:600px">
    <div class="po-modal-header">
      <h5 class="po-modal-title">Configurações de Visualização</h5>
      <button class="po-btn-close" onclick="poFecharConfig()">×</button>
    </div>
    
    <div style="margin:20px 0">
      <h6 style="color:#0f172a;font-size:13px;font-weight:600;margin-bottom:12px">
        <i class="bi bi-building"></i> Centros de Custo Visíveis
      </h6>
      <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;max-height:300px;overflow-y:auto">
        <div style="margin-bottom:10px">
          <input type="text" id="po-cfg-search-cc" placeholder="Buscar centro de custo..." 
                 oninput="poFilterCcList(this.value)"
                 style="width:100%;padding:6px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;margin-bottom:8px">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;color:#2563eb;font-size:13px">
            <input type="checkbox" id="po-cfg-select-all" onchange="poToggleAllCc(this.checked)" style="width:16px;height:16px">
            <span style="font-weight:600">Selecionar / Desmarcar Todos</span>
          </label>
        </div>
        <div style="border-top:1px solid #e2e8f0;padding-top:10px;display:grid;gap:8px" id="po-cfg-cc-list">
          <!-- Preenchido via JavaScript -->
        </div>
      </div>
    </div>
    
    <div style="margin:20px 0">
      <h6 style="color:#0f172a;font-size:13px;font-weight:600;margin-bottom:12px">
        <i class="bi bi-buildings"></i> Obras Visíveis
      </h6>
      <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;max-height:300px;overflow-y:auto">
        <div style="margin-bottom:10px">
          <input type="text" id="po-cfg-search-obras" placeholder="Buscar obra por código ou nome..." 
                 oninput="poFilterObrasList(this.value)"
                 style="width:100%;padding:6px 10px;border:1px solid #e2e8f0;border-radius:6px;font-size:13px;margin-bottom:8px">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;color:#2563eb;font-size:13px">
            <input type="checkbox" id="po-cfg-select-all-obras" onchange="poToggleAllObras(this.checked)" style="width:16px;height:16px">
            <span style="font-weight:600">Selecionar / Desmarcar Todas</span>
          </label>
        </div>
        <div style="border-top:1px solid #e2e8f0;padding-top:10px;display:grid;gap:8px" id="po-cfg-obras-list">
          <!-- Preenchido via JavaScript -->
        </div>
      </div>
    </div>
    
    <div class="po-modal-footer" style="display:flex;gap:10px;margin-top:20px">
      <button class="po-btn po-btn-primary" onclick="poSalvarConfig()">
        <i class="bi bi-check2"></i> Salvar Filtros
      </button>
      <button class="po-btn-secondary" onclick="poLimparFiltros()">
        <i class="bi bi-arrow-counterclockwise"></i> Limpar Filtros
      </button>
      <button class="po-btn-secondary" onclick="poFecharConfig()">Cancelar</button>
    </div>
  </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>

