<?php
$pageTitle   = 'Painel de Obras';
$currentPage = 'painel_obras';
ob_start();
?>
<style>
/* ── Tokens ── */
:root {
  --po-bg:        #0b1220;
  --po-sidebar:   #0a101c;
  --po-card:      #151f32;
  --po-input:     #0f1829;
  --po-border:    rgba(255,255,255,0.06);
  --po-txt:       #e2e8f0;
  --po-txt2:      #94a3b8;
  --po-blue:      #3b82f6;
  --po-blue-t:    #60a5fa;
  --po-cyan:      #22d3ee;
  --po-green:     #10b981;
  --po-green-t:   #6ee7b7;
  --po-yellow:    #f59e0b;
  --po-yellow-t:  #fcd34d;
  --po-red:       #ef4444;
  --po-red-t:     #fca5a5;
  --po-gray:      #64748b;
  --po-gray2:     #334155;
}

/* ── Área escura (wrapper independente, não polui o layout Bootstrap) ── */
#po-wrap {
  background: var(--po-bg);
  color: var(--po-txt);
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  font-variant-numeric: tabular-nums;
  min-height: 100vh;
  padding: 0 32px 48px;
  margin: -1.5rem -1.5rem 0;
}

/* ── Cabeçalho ── */
.po-header {
  display: flex; justify-content: space-between; align-items: flex-start;
  padding: 28px 0 24px; gap: 16px; flex-wrap: wrap;
}
.po-header-left .po-pill-label {
  display: inline-flex; align-items: center; gap: 6px;
  font-size: 11px; font-weight: 600; letter-spacing: .5px;
  color: var(--po-blue-t); background: rgba(59,130,246,.12);
  border: 1px solid rgba(59,130,246,.25); border-radius: 20px;
  padding: 3px 10px; margin-bottom: 8px;
}
.po-header h1 { font-size: 28px; font-weight: 700; color: #f8fafc; margin: 0 0 4px; }
.po-header .po-sub { font-size: 14px; color: var(--po-txt2); margin: 0; }
.po-header-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.po-sync-badge {
  display: flex; align-items: center; gap: 6px;
  font-size: 12px; color: var(--po-green-t);
  background: rgba(16,185,129,.1); border: 1px solid rgba(16,185,129,.25);
  border-radius: 20px; padding: 4px 12px;
}
.po-sync-badge .dot {
  width: 7px; height: 7px; border-radius: 50%; background: var(--po-green);
  animation: pulse 2s infinite;
}
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

/* ── Alerta de pendências ── */
.po-warn-bar {
  background: rgba(245,158,11,.08); border: 1px solid rgba(245,158,11,.2);
  border-radius: 10px; padding: 10px 16px; font-size: 13px;
  color: var(--po-yellow-t); margin-bottom: 20px; display: flex; align-items: center; gap: 8px;
}

/* ── Cards ── */
.po-card {
  background: var(--po-card);
  border: 1px solid var(--po-border);
  border-radius: 16px; padding: 20px;
  box-shadow: 0 8px 24px rgba(0,0,0,.25);
  transition: transform .2s, box-shadow .2s;
}
.po-card:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(0,0,0,.35); }
@media (prefers-reduced-motion: reduce) { .po-card, .po-card:hover { transition: none; transform: none; } }

/* ── KPIs ── */
.po-kpis { display: grid; grid-template-columns: repeat(5,1fr); gap: 16px; margin-bottom: 24px; }
@media (max-width: 1280px) { .po-kpis { grid-template-columns: repeat(3,1fr); } }
@media (max-width: 760px)  { .po-kpis { grid-template-columns: repeat(2,1fr); } }

.po-kpi { position: relative; overflow: hidden; }
.po-kpi-icon {
  width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
  font-size: 17px; margin-bottom: 12px;
}
.po-kpi-pill {
  position: absolute; top: 16px; right: 16px;
  font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 20px;
}
.po-kpi-label { font-size: 12px; color: var(--po-txt2); margin-bottom: 4px; }
.po-kpi-value { font-size: 24px; font-weight: 700; color: #f8fafc; line-height: 1.1; }
.po-kpi-var   { font-size: 12px; margin-top: 4px; }
.po-kpi-bar   { height: 6px; background: rgba(255,255,255,.07); border-radius: 3px; margin-top: 12px; overflow: hidden; }
.po-kpi-bar-fill { height: 100%; border-radius: 3px; transition: width .9s ease; }
.po-kpi-note  { font-size: 11px; color: var(--po-txt2); margin-top: 6px; }

/* ── Status pills ── */
.po-pill { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 20px; }
.po-pill .dot { width: 6px; height: 6px; border-radius: 50%; }
.po-pill.ok     { color: var(--po-green-t);  background: rgba(16,185,129,.14); }
.po-pill.warn   { color: var(--po-yellow-t); background: rgba(245,158,11,.14); }
.po-pill.danger { color: var(--po-red-t);    background: rgba(239,68,68,.14);  }
.po-pill.pending{ color: #cbd5e1;            background: rgba(100,116,139,.14);}
.po-pill.info   { color: #93c5fd;            background: rgba(59,130,246,.14); }

/* ── Badges de tipo ── */
.po-badge-ref  { font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 8px; color: #67e8f9; background: rgba(103,232,249,.12); }
.po-badge-con  { font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 8px; color: #93c5fd; background: rgba(147,197,253,.14); }

/* ── Obras em alerta ── */
.po-alertas { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; margin-bottom: 24px; }
@media (max-width: 1280px) { .po-alertas { grid-template-columns: repeat(2,1fr); } }
@media (max-width: 760px)  { .po-alertas { grid-template-columns: 1fr; } }

.po-alerta-card { border-left: 3px solid var(--po-red); }
.po-alerta-card.warn { border-left-color: var(--po-yellow); }
.po-alerta-code { font-size: 12px; font-weight: 600; color: var(--po-blue-t); margin-bottom: 2px; }
.po-alerta-name { font-size: 15px; font-weight: 700; color: #f8fafc; margin-bottom: 10px; }
.po-alerta-row  { display: flex; justify-content: space-between; font-size: 13px; color: var(--po-txt2); margin-bottom: 4px; }
.po-alerta-row span:last-child { color: var(--po-txt); font-weight: 600; }
.po-alerta-note { font-size: 12px; color: var(--po-txt2); margin-top: 10px; border-top: 1px solid var(--po-border); padding-top: 8px; }

/* ── Tabela de obras ── */
.po-table-wrap { overflow-x: auto; }
.po-table { width: 100%; border-collapse: collapse; }
.po-table th {
  font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px;
  color: var(--po-txt2); padding: 10px 14px; border-bottom: 1px solid var(--po-border);
  white-space: nowrap; text-align: left;
}
.po-table th.r, .po-table td.r { text-align: right; }
.po-table td {
  padding: 11px 14px; border-bottom: 1px solid var(--po-border);
  font-size: 13px; color: var(--po-txt); white-space: nowrap;
}
.po-table tr:last-child td { border-bottom: none; }
.po-table tbody tr:hover { background: rgba(59,130,246,.05); }
.po-table td .po-code { font-size: 11px; font-weight: 600; color: var(--po-blue-t); display: block; }
.po-table td .po-name { font-weight: 600; color: #f8fafc; }
.po-desvio-pos { color: var(--po-red-t); }
.po-desvio-neg { color: var(--po-green-t); }
.po-bar-wrap { width: 130px; display: inline-block; }
.po-bar-track { height: 6px; background: rgba(255,255,255,.07); border-radius: 3px; overflow: hidden; }
.po-bar-fill  { height: 100%; border-radius: 3px; }
.po-bar-lbl   { font-size: 10px; color: var(--po-txt2); margin-top: 3px; }

/* Filtros */
.po-filters { display: flex; gap: 10px; flex-wrap: wrap; padding: 16px 20px; border-bottom: 1px solid var(--po-border); }
.po-filters input, .po-filters select {
  background: var(--po-input); border: 1px solid var(--po-border); color: var(--po-txt);
  border-radius: 8px; padding: 7px 12px; font-size: 13px; outline: none;
  transition: border-color .2s;
}
.po-filters input:focus, .po-filters select:focus { border-color: var(--po-blue); }
.po-filters input { flex: 1; min-width: 200px; }

/* ── Categorias ── */
.po-cat-row { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--po-border); }
.po-cat-row:last-child { border-bottom: none; }
.po-cat-label { width: 170px; font-size: 13px; color: var(--po-txt); flex-shrink: 0; }
.po-cat-bars  { flex: 1; display: flex; flex-direction: column; gap: 4px; }
.po-cat-bar   { height: 10px; border-radius: 5px; }
.po-cat-info  { font-size: 11px; color: var(--po-txt2); margin-top: 2px; }
@media (max-width: 760px) {
  .po-cat-row  { flex-direction: column; align-items: flex-start; }
  .po-cat-label{ width: 100%; }
}

/* ── Fluxo ── */
.po-fluxo-chart { display: flex; align-items: flex-end; gap: 6px; height: 260px; padding: 0 0 20px; }
.po-fluxo-col   { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; gap: 2px; }
.po-fluxo-bar   { width: 100%; max-width: 38px; border-radius: 4px 4px 0 0; min-height: 2px; transition: height .6s ease; }
.po-fluxo-mes   { font-size: 10px; color: var(--po-txt2); margin-top: 4px; }
.po-fluxo-atual { font-size: 9px; color: var(--po-cyan); font-weight: 700; }
.po-fluxo-boxes { display: flex; gap: 12px; margin-top: 12px; flex-wrap: wrap; }
.po-fluxo-box   {
  flex: 1; min-width: 120px; background: var(--po-input); border: 1px solid var(--po-border);
  border-radius: 10px; padding: 12px 16px;
}
.po-fluxo-box-label { font-size: 11px; color: var(--po-txt2); margin-bottom: 4px; }
.po-fluxo-box-val   { font-size: 18px; font-weight: 700; }

/* ── Conciliação ── */
.po-conc-cards { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
.po-conc-card  {
  flex: 1; min-width: 120px; background: var(--po-input); border: 1px solid var(--po-border);
  border-radius: 10px; padding: 14px 16px; text-align: center;
}
.po-conc-card .num { font-size: 26px; font-weight: 700; }
.po-conc-card .lbl { font-size: 12px; color: var(--po-txt2); margin-top: 4px; }

/* ── Ações rápidas ── */
.po-acoes { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; margin-bottom: 24px; }
@media (max-width: 760px) { .po-acoes { grid-template-columns: 1fr; } }

/* ── Seções ── */
.po-section-title {
  font-size: 18px; font-weight: 600; color: #f8fafc; margin: 0 0 2px;
  display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;
}
.po-section-sub { font-size: 13px; color: var(--po-txt2); margin-bottom: 16px; }

/* ── Botões ── */
.po-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 8px 16px; border-radius: 12px; font-size: 13px; font-weight: 600;
  cursor: pointer; border: none; transition: opacity .2s;
}
.po-btn:hover { opacity: .85; }
.po-btn-primary { background: linear-gradient(135deg, var(--po-blue), var(--po-cyan)); color: #fff; box-shadow: 0 4px 12px rgba(59,130,246,.3); }
.po-btn-ghost   { background: var(--po-card); border: 1px solid var(--po-border); color: var(--po-txt); }

/* ── Rodapé ── */
.po-footer { border-top: 1px solid var(--po-border); padding-top: 16px; margin-top: 24px; display: flex; justify-content: space-between; font-size: 12px; color: var(--po-txt2); flex-wrap: wrap; gap: 8px; }

/* ── Período selector ── */
.po-periodo { display: flex; align-items: center; gap: 8px; }
.po-periodo label { font-size: 12px; color: var(--po-txt2); }
.po-periodo select {
  background: var(--po-input); border: 1px solid var(--po-border); color: var(--po-txt);
  border-radius: 8px; padding: 6px 10px; font-size: 13px; outline: none; cursor: pointer;
}

/* ── Skeleton ── */
.po-skeleton { background: linear-gradient(90deg, rgba(255,255,255,.04) 0%, rgba(255,255,255,.08) 50%, rgba(255,255,255,.04) 100%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 6px; }
@keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
.po-skeleton.h-32 { height: 32px; margin-bottom: 8px; }
.po-skeleton.h-16 { height: 16px; }

/* ── Toast ── */
#po-toast { position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 8px; }
.po-toast-item {
  background: var(--po-card); border: 1px solid var(--po-border); border-radius: 10px;
  padding: 12px 20px; font-size: 13px; color: var(--po-txt);
  animation: slideUp .3s ease; box-shadow: 0 8px 24px rgba(0,0,0,.4);
}
.po-toast-item.ok     { border-color: rgba(16,185,129,.4); }
.po-toast-item.error  { border-color: rgba(239,68,68,.4); }
@keyframes slideUp { from{transform:translateY(20px);opacity:0} to{transform:translateY(0);opacity:1} }

/* ── Submenu (cadastros) ── */
.po-subnav { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 20px; }
.po-subnav a {
  font-size: 13px; padding: 6px 14px; border-radius: 8px; color: var(--po-txt2);
  text-decoration: none; border: 1px solid var(--po-border); transition: all .2s;
}
.po-subnav a:hover, .po-subnav a.active {
  background: rgba(59,130,246,.12); color: var(--po-blue-t); border-color: rgba(59,130,246,.3);
}

/* ── Responsive ── */
@media (max-width: 1024px) { #po-wrap { padding: 0 16px 32px; } }
@media (max-width: 760px)  { #po-wrap { padding: 0 10px 24px; } }
</style>

<div id="po-wrap">

  <!-- Alerta de pendências (preenchido via JS) -->
  <div id="po-pending-bar" class="po-warn-bar" style="display:none">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <span id="po-pending-msg"></span>
    <a href="/admin/painel-obras/obras" style="color:inherit;margin-left:4px;text-decoration:underline;">Ver obras</a>
  </div>

  <!-- Cabeçalho -->
  <div class="po-header">
    <div class="po-header-left">
      <div class="po-pill-label"><i class="bi bi-building"></i> Controladoria • Obras</div>
      <h1>Painel Financeiro de Obras</h1>
      <p class="po-sub">Visão consolidada — <span id="po-obras-ativas">…</span> obras ativas</p>
    </div>
    <div class="po-header-right">
      <div class="po-sync-badge"><span class="dot"></span> <span id="po-sync-time">Carregando…</span></div>
      <div class="po-periodo">
        <label>Período:</label>
        <select id="po-periodo-sel" onchange="poCarregar()">
          <?php
          $meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
          $ano = (int) date('Y');
          for ($m = 1; $m <= 12; $m++) {
              $val = sprintf('%04d-%02d', $ano, $m);
              $sel = ($val === $competencia) ? ' selected' : '';
              echo "<option value=\"{$val}\"{$sel}>{$meses[$m-1]}/{$ano}</option>";
          }
          // Trimestres
          for ($q = 1; $q <= 4; $q++) {
              $val = "{$ano}-Q{$q}";
              $sel = ($val === $competencia) ? ' selected' : '';
              echo "<option value=\"{$val}\"{$sel}>Trimestre {$q}/{$ano}</option>";
          }
          ?>
        </select>
        <select id="po-company-sel" onchange="poCarregar()">
          <?php foreach ($companies as $k => $l): ?>
          <option value="<?= $k ?>" <?= $k === $company ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="po-btn po-btn-ghost" onclick="poCopiarResumo()"><i class="bi bi-clipboard"></i> Copiar resumo</button>
      <button class="po-btn po-btn-primary" onclick="window.print()"><i class="bi bi-download"></i> Exportar</button>
    </div>
  </div>

  <!-- Sub-navegação -->
  <div class="po-subnav">
    <a href="/admin/painel-obras" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="/admin/painel-obras/obras"><i class="bi bi-buildings"></i> Obras</a>
    <a href="/admin/painel-obras/orcamento"><i class="bi bi-calculator"></i> Orçamento</a>
    <a href="/admin/painel-obras/planilha"><i class="bi bi-table"></i> Planilha de Controle</a>
    <a href="/admin/painel-obras/depara"><i class="bi bi-arrow-left-right"></i> De-para Categorias</a>
    <a href="/admin/painel-obras/pauta"><i class="bi bi-calendar-check"></i> Pauta de Revisão</a>
  </div>

  <!-- KPIs -->
  <div class="po-kpis" id="po-kpis">
    <?php foreach (['Valor Contratado Total','Orçamento Estimado','Gasto Real Acumulado','Desvio Global','Obras em Estouro'] as $i => $kpi): ?>
    <div class="po-card po-kpi" id="po-kpi-<?= $i ?>">
      <div class="po-skeleton h-32"></div><div class="po-skeleton h-16"></div>
    </div>
    <?php endforeach; ?>
  </div>
  <p style="font-size:11px;color:var(--po-txt2);margin:-12px 0 20px;">Valores consolidados de todas as obras ativas no período selecionado.</p>

  <!-- Obras em alerta -->
  <div style="margin-bottom:24px">
    <div class="po-section-title">
      Obras em Alerta
      <span class="po-pill danger" id="po-alerta-count"><span class="dot" style="background:var(--po-red)"></span> …</span>
    </div>
    <p class="po-section-sub">As três obras que mais exigem decisão da diretoria neste mês</p>
    <div class="po-alertas" id="po-alertas">
      <?php for ($i=0;$i<3;$i++): ?>
      <div class="po-card"><div class="po-skeleton h-32"></div><div class="po-skeleton h-16" style="margin-top:8px"></div></div>
      <?php endfor; ?>
    </div>
  </div>

  <!-- Tabela de desempenho -->
  <div class="po-card" style="padding:0;margin-bottom:24px">
    <div style="padding:20px 20px 0">
      <div class="po-section-title">
        Desempenho por Obra
        <span class="po-pill info" id="po-obras-count"><span class="dot" style="background:var(--po-blue)"></span> … obras</span>
      </div>
      <p class="po-section-sub">Ordenadas pelo código</p>
    </div>
    <div class="po-filters">
      <input type="text" id="po-busca" placeholder="Buscar por nome ou código (ex.: Alphaville, REF-07)" oninput="poFiltrar()">
      <select id="po-ftipo" onchange="poFiltrar()">
        <option value="">Todos os tipos</option>
        <option value="Reforma">Reforma</option>
        <option value="Construção">Construção</option>
      </select>
      <select id="po-fsit" onchange="poFiltrar()">
        <option value="">Todas as situações</option>
        <option value="ok">OK</option>
        <option value="warn">Atenção</option>
        <option value="danger">Estouro</option>
      </select>
      <select id="po-fcat" onchange="poFiltrar()">
        <option value="">Todas as categorias</option>
        <?php foreach ($categoriasObra as $c): ?>
        <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="po-btn po-btn-ghost" onclick="poResetFiltros()" style="padding:7px 12px;">Limpar</button>
    </div>
    <div class="po-table-wrap">
      <table class="po-table">
        <thead>
          <tr>
            <th>Obra</th><th>Tipo</th><th class="r">Contratado</th><th class="r">Orçado</th>
            <th class="r">Gasto</th><th class="r">Desvio</th><th>Consumo</th><th>Situação</th><th>Conciliação</th>
          </tr>
        </thead>
        <tbody id="po-tbody">
          <tr><td colspan="9" style="text-align:center;padding:32px;color:var(--po-txt2)">Carregando…</td></tr>
        </tbody>
      </table>
    </div>
    <div style="padding:12px 20px;font-size:12px;color:var(--po-txt2);border-top:1px solid var(--po-border)">
      Mostrando <span id="po-mostrando">0</span> obras •
      Desvio = Gasto − Orçado. Atenção até 5% acima; Estouro acima de 5%.
    </div>
  </div>

  <!-- Orçado x Gasto por Categoria -->
  <div class="po-card" style="margin-bottom:24px">
    <div class="po-section-title">
      Orçado × Gasto por Categoria
      <div style="display:flex;gap:12px;font-size:12px">
        <span style="color:var(--po-txt2)">■ <span style="color:var(--po-gray)">Orçado</span></span>
        <span style="color:var(--po-blue-t)">■ Gasto</span>
      </div>
    </div>
    <p class="po-section-sub">Consolidado das obras ativas no período — valores em reais</p>
    <div id="po-cat-rows"></div>
  </div>

  <!-- Conciliação Nibo -->
  <div class="po-card" style="margin-bottom:24px">
    <div class="po-section-title">
      Conciliação Nibo
      <span class="po-pill ok" id="po-conc-badge"><span class="dot" style="background:var(--po-green)"></span> …</span>
    </div>
    <p class="po-section-sub">Comparativo entre lançamentos do Nibo e a planilha de controle de obra</p>
    <div class="po-conc-cards" id="po-conc-cards">
      <?php for ($i=0;$i<3;$i++): ?>
      <div class="po-conc-card"><div class="po-skeleton h-32"></div></div>
      <?php endfor; ?>
    </div>
    <div class="po-table-wrap">
      <table class="po-table">
        <thead><tr><th>Obra</th><th class="r">Total Nibo</th><th class="r">Total Planilha</th><th class="r">Diferença</th></tr></thead>
        <tbody id="po-conc-tbody"></tbody>
      </table>
    </div>
  </div>

  <!-- Fluxo de Caixa Anual -->
  <div class="po-card" style="margin-bottom:24px">
    <div class="po-section-title">
      Fluxo de Caixa <?= date('Y') ?>
      <div style="display:flex;gap:12px;font-size:12px">
        <span style="color:#3b82f6">■ Realizado</span>
        <span style="color:#22d3ee">■ A pagar</span>
        <span style="color:var(--po-gray2)">■ Previsto</span>
      </div>
    </div>
    <p class="po-section-sub">Realizado, a pagar e previsto por mês — em R$ mil</p>
    <div class="po-fluxo-chart" id="po-fluxo-chart" style="padding:10px 0 0"></div>
    <div class="po-fluxo-boxes" id="po-fluxo-boxes">
      <?php for ($i=0;$i<3;$i++): ?>
      <div class="po-fluxo-box"><div class="po-skeleton h-16"></div></div>
      <?php endfor; ?>
    </div>
  </div>

  <!-- Ações rápidas -->
  <div class="po-acoes">
    <div class="po-card">
      <div style="font-size:15px;font-weight:600;color:#f8fafc;margin-bottom:6px">Exportar relatório</div>
      <div style="font-size:13px;color:var(--po-txt2);margin-bottom:14px">Gera um resumo textual do painel para envio à diretoria.</div>
      <button class="po-btn po-btn-primary" onclick="poCopiarResumo()"><i class="bi bi-clipboard-check"></i> Gerar relatório</button>
    </div>
    <div class="po-card">
      <div style="font-size:15px;font-weight:600;color:#f8fafc;margin-bottom:6px">Registrar revisão</div>
      <div style="font-size:13px;color:var(--po-txt2);margin-bottom:14px">Adicione obras em alerta à pauta da reunião de sexta.</div>
      <button class="po-btn po-btn-ghost" onclick="poAdicionarPauta()"><i class="bi bi-calendar-plus"></i> Adicionar à pauta</button>
    </div>
    <div class="po-card">
      <div style="font-size:15px;font-weight:600;color:#f8fafc;margin-bottom:6px">Limpar filtros</div>
      <div style="font-size:13px;color:var(--po-txt2);margin-bottom:14px">Restaura a tabela para exibir todas as obras sem restrições.</div>
      <button class="po-btn po-btn-ghost" onclick="poResetFiltros()"><i class="bi bi-arrow-counterclockwise"></i> Redefinir filtros</button>
    </div>
  </div>

  <!-- Rodapé -->
  <div class="po-footer">
    <span>Brooks Construtora — Painel Financeiro</span>
    <span id="po-footer-sync">—</span>
  </div>
</div>

<!-- Toast container -->
<div id="po-toast"></div>

<script>
(function(){
'use strict';

// ── Estado global ──────────────────────────────────────────────────
let _data = null;

const BRL = v => v == null ? '—' : new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL'}).format(v);
const PCT = v => (v > 0 ? '+' : '') + v.toFixed(1).replace('.',',') + '%';
const mesAtual = new Date().getMonth() + 1;

// ── Carregar dados ─────────────────────────────────────────────────
window.poCarregar = function() {
  const periodo = document.getElementById('po-periodo-sel').value;
  const company = document.getElementById('po-company-sel').value;
  mostrarSkeletons();

  fetch(`/admin/painel-obras/dashboard-data?periodo=${periodo}&company=${company}`)
    .then(r => r.json())
    .then(res => {
      if (!res.ok) { poToast('Erro ao carregar dados: ' + (res.error || 'desconhecido'), 'error'); return; }
      _data = res.data;
      renderTudo(res.data, res.synced_at);
    })
    .catch(e => poToast('Falha de conexão: ' + e.message, 'error'));
};

function renderTudo(d, syncedAt) {
  renderKpis(d.kpis);
  renderAlertas(d.obras_alerta);
  renderTabela(d.obras);
  renderCategorias(d.gasto_por_cat, d.orcado_por_cat);
  renderConciliacao(d.conciliacao);
  renderFluxo(d.fluxo_mensal);
  renderMetaInfo(d, syncedAt);
}

function renderMetaInfo(d, syncedAt) {
  const kpis = d.kpis;
  document.getElementById('po-obras-ativas').textContent = kpis.obras_total;
  if (syncedAt) {
    const dt = syncedAt.substring(0,10).split('-').reverse().join('/');
    document.getElementById('po-sync-time').textContent = 'Última atualização: ' + dt;
    document.getElementById('po-footer-sync').textContent = 'Última sinc. Nibo: ' + syncedAt.substring(0,16).replace('T',' ');
  }
  if (d.nao_mapeadas > 0) {
    const bar = document.getElementById('po-pending-bar');
    const msg = document.getElementById('po-pending-msg');
    msg.textContent = d.nao_mapeadas + ' categori' + (d.nao_mapeadas === 1 ? 'a' : 'as') + ' do Nibo sem de-para.';
    bar.style.display = 'flex';
  }
}

// ── KPIs ──────────────────────────────────────────────────────────
function renderKpis(k) {
  const kpis = [
    {
      id:0, label:'Valor Contratado Total', icon:'bi-file-earmark-text', iconBg:'rgba(59,130,246,.15)', iconColor:'#60a5fa',
      pill:'Período', pillClass:'info',
      value: BRL(k.contratado), var: null,
      barPct: 100, barColor: 'var(--po-blue)',
      note: `Soma dos contratos das ${k.obras_total} obras`
    },
    {
      id:1, label:'Orçamento Estimado', icon:'bi-calculator', iconBg:'rgba(100,116,139,.15)', iconColor:'#94a3b8',
      pill: k.contratado > 0 ? PCT(((k.orcado/k.contratado)-1)*100) + ' do contratado' : '—', pillClass:'pending',
      value: BRL(k.orcado), var: null,
      barPct: k.contratado > 0 ? Math.min((k.orcado/k.contratado)*100, 100) : 0, barColor:'var(--po-gray)',
      note: 'Base de referência para controle'
    },
    {
      id:2, label:'Gasto Real Acumulado', icon:'bi-cash-stack', iconBg:'rgba(59,130,246,.15)', iconColor:'#60a5fa',
      pill: k.orcado > 0 ? PCT(((k.gasto/k.orcado)-1)*100) + ' do orçado' : '—', pillClass: k.gasto > k.orcado ? 'danger' : 'ok',
      value: BRL(k.gasto), var: null,
      barPct: k.orcado > 0 ? Math.min((k.gasto/k.orcado)*100, 100) : 0,
      barColor: 'linear-gradient(90deg,var(--po-blue),var(--po-cyan))',
      note: 'Lançamentos pagos no período'
    },
    {
      id:3, label:'Desvio Global', icon:'bi-graph-up-arrow', iconBg: k.desvio > 0 ? 'rgba(239,68,68,.15)' : 'rgba(16,185,129,.15)',
      iconColor: k.desvio > 0 ? 'var(--po-red-t)' : 'var(--po-green-t)',
      pill: k.orcado > 0 ? PCT((k.desvio/k.orcado)*100) : '—',
      pillClass: k.desvio > 0 ? 'danger' : 'ok',
      value: (k.desvio > 0 ? '+' : '') + BRL(k.desvio),
      valueColor: k.desvio > 0 ? 'var(--po-red-t)' : 'var(--po-green-t)',
      barPct: k.orcado > 0 ? Math.min(Math.abs(k.desvio/k.orcado)*100, 100) : 0,
      barColor: k.desvio > 0 ? 'var(--po-red)' : 'var(--po-green)',
      note: 'Gasto ' + (k.desvio > 0 ? 'acima' : 'abaixo') + ' do orçamento consolidado'
    },
    {
      id:4, label:'Obras em Estouro', icon:'bi-exclamation-triangle', iconBg:'rgba(245,158,11,.15)', iconColor:'var(--po-yellow-t)',
      pill: k.obras_atencao + ' em atenção', pillClass:'warn',
      value: k.obras_estouro + ' de ' + k.obras_total,
      barPct: k.obras_total > 0 ? (k.obras_estouro/k.obras_total)*100 : 0,
      barColor:'var(--po-yellow)',
      note: 'Desvio acima de 5% do orçado'
    },
  ];

  kpis.forEach(kpi => {
    const el = document.getElementById('po-kpi-' + kpi.id);
    el.innerHTML = `
      <div class="po-kpi-icon" style="background:${kpi.iconBg};color:${kpi.iconColor}"><i class="bi ${kpi.icon}"></i></div>
      <span class="po-kpi-pill po-pill ${kpi.pillClass}">${kpi.pill||''}</span>
      <div class="po-kpi-label">${kpi.label}</div>
      <div class="po-kpi-value" style="${kpi.valueColor?'color:'+kpi.valueColor:''}">${kpi.value}</div>
      <div class="po-kpi-bar"><div class="po-kpi-bar-fill" style="width:0%;background:${kpi.barColor}" data-pct="${kpi.barPct}"></div></div>
      <div class="po-kpi-note">${kpi.note}</div>`;
  });

  // Anima barras
  requestAnimationFrame(() => {
    document.querySelectorAll('.po-kpi-bar-fill').forEach(b => {
      b.style.width = Math.min(parseFloat(b.dataset.pct||0), 100) + '%';
    });
  });
}

// ── Alertas ───────────────────────────────────────────────────────
function renderAlertas(alertas) {
  const el = document.getElementById('po-alertas');
  const cnt = document.getElementById('po-alerta-count');
  const criticas = alertas.filter(a => a.desvio_pct > 5).length;
  cnt.innerHTML = `<span class="dot" style="background:var(--po-red)"></span> ${alertas.length} obra${alertas.length!==1?'s':''} crítica${alertas.length!==1?'s':''}`;

  if (!alertas.length) {
    el.innerHTML = '<div class="po-card" style="grid-column:1/-1;text-align:center;padding:32px;color:var(--po-txt2)"><i class="bi bi-check-circle" style="font-size:24px;color:var(--po-green-t)"></i><br>Nenhuma obra em alerta neste período.</div>';
    return;
  }

  el.innerHTML = alertas.map(a => {
    const isCritico = a.desvio_pct > 10;
    const pClass    = isCritico ? 'danger' : (a.situacao === 'danger' ? 'danger' : 'warn');
    const pLabel    = isCritico ? 'ESTOURO CRÍTICO' : (a.situacao === 'danger' ? 'ESTOURO' : 'ATENÇÃO');
    const pct       = a.orcado > 0 ? Math.min((a.gasto/a.orcado)*100, 120) : 0;
    const barColor  = pClass === 'danger' ? 'var(--po-red)' : 'var(--po-yellow)';
    const topCats   = (a.top_cats||[]).filter(Boolean).join(' e ') || 'diversas categorias';
    return `
    <div class="po-card po-alerta-card ${a.situacao==='warn'?'warn':''}">
      <div class="po-alerta-code">${a.codigo}</div>
      <div class="po-alerta-name">${a.nome}</div>
      <span class="po-pill ${pClass}" style="margin-bottom:12px"><span class="dot" style="background:${pClass==='danger'?'var(--po-red)':'var(--po-yellow)'}"></span> ${pLabel}</span>
      <div class="po-alerta-row"><span>Orçado</span><span>${BRL(a.orcado)}</span></div>
      <div class="po-alerta-row"><span>Gasto</span><span>${BRL(a.gasto)}</span></div>
      <div class="po-kpi-bar" style="margin:8px 0"><div class="po-kpi-bar-fill" style="width:${pct}%;background:${barColor}"></div></div>
      <div class="po-alerta-row"><span>Desvio</span><span style="color:${barColor}">${(a.desvio>0?'+':'') + BRL(a.desvio)} (${PCT(a.desvio_pct)})</span></div>
      <div class="po-alerta-note">Desvio concentrado em <strong>${topCats}</strong>.</div>
    </div>`;
  }).join('');
}

// ── Tabela de obras ───────────────────────────────────────────────
let _obras = [];
function renderTabela(obras) {
  _obras = obras;
  poFiltrar();
}

window.poFiltrar = function() {
  if (!_obras) return;
  const q    = document.getElementById('po-busca').value.toLowerCase();
  const tipo = document.getElementById('po-ftipo').value;
  const sit  = document.getElementById('po-fsit').value;
  const cat  = document.getElementById('po-fcat').value;

  let filtradas = _obras.filter(o => {
    if (q && !o.nome.toLowerCase().includes(q) && !o.codigo.toLowerCase().includes(q)) return false;
    if (tipo && o.tipo !== tipo) return false;
    if (sit  && o.situacao !== sit) return false;
    if (cat  && !(o.gasto_cat && o.gasto_cat[cat] > 0)) return false;
    return true;
  });

  document.getElementById('po-mostrando').textContent = filtradas.length;
  document.getElementById('po-obras-count').innerHTML =
    `<span class="dot" style="background:var(--po-blue)"></span> ${filtradas.length} obras`;

  const tbody = document.getElementById('po-tbody');
  if (!filtradas.length) {
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;padding:32px;color:var(--po-txt2)">Nenhuma obra corresponde aos filtros. Ajuste a busca ou redefina os filtros.</td></tr>';
    return;
  }

  tbody.innerHTML = filtradas.map(o => {
    const pct    = o.orcado > 0 ? Math.min((o.gasto/o.orcado)*100, 120) : 0;
    const bColor = o.situacao === 'danger' ? 'var(--po-red)' : (o.situacao === 'warn' ? 'var(--po-yellow)' : 'var(--po-blue)');
    const dClass = o.desvio > 0 ? 'po-desvio-pos' : 'po-desvio-neg';
    const dSign  = o.desvio > 0 ? '+' : '';
    const devPct = o.orcado > 0 ? (o.desvio/o.orcado*100).toFixed(1).replace('.',',')+'%' : '—';
    const conc   = o.planilha_total == null ? '<span class="po-pill pending">Pendente</span>'
                 : (Math.abs(o.planilha_dif||0) === 0 ? '<span class="po-pill ok">Conciliado</span>'
                 : (Math.abs(o.planilha_dif||0) <= 2000 ? '<span class="po-pill warn">Div. pequena</span>'
                 : '<span class="po-pill danger">Divergente</span>'));
    const tipoBadge = o.tipo === 'Reforma' ? `<span class="po-badge-ref">${o.tipo}</span>` : `<span class="po-badge-con">${o.tipo}</span>`;
    return `<tr>
      <td><span class="po-code">${o.codigo}</span><span class="po-name">${o.nome}</span></td>
      <td>${tipoBadge}</td>
      <td class="r">${BRL(o.contratado)}</td>
      <td class="r">${o.orcado > 0 ? BRL(o.orcado) : '<a href="/admin/painel-obras/orcamento" style="color:var(--po-blue-t);font-size:11px">— Preencher</a>'}</td>
      <td class="r">${BRL(o.gasto)}</td>
      <td class="r ${dClass}">${o.orcado > 0 ? dSign+BRL(o.desvio) : '—'}<br><small>${o.orcado > 0 ? (o.desvio>0?'+':'')+devPct : ''}</small></td>
      <td>
        <div class="po-bar-wrap">
          <div class="po-bar-track"><div class="po-bar-fill" style="width:${pct}%;background:${bColor}"></div></div>
          <div class="po-bar-lbl">${pct.toFixed(0)}% do orçado</div>
        </div>
      </td>
      <td><span class="po-pill ${o.situacao}"><span class="dot" style="background:${bColor}"></span> ${o.situacao==='ok'?'OK':o.situacao==='warn'?'Atenção':'Estouro'}</span></td>
      <td>${conc}</td>
    </tr>`;
  }).join('');
};

window.poResetFiltros = function() {
  document.getElementById('po-busca').value = '';
  document.getElementById('po-ftipo').value = '';
  document.getElementById('po-fsit').value  = '';
  document.getElementById('po-fcat').value  = '';
  poFiltrar();
  poToast('Filtros redefinidos.');
};

// ── Categorias ────────────────────────────────────────────────────
function renderCategorias(gasto, orcado) {
  const cats = <?= json_encode($categoriasObra) ?>;
  const allVals = [...Object.values(gasto), ...Object.values(orcado)];
  const maxVal  = Math.max(...allVals, 1);

  const container = document.getElementById('po-cat-rows');
  container.innerHTML = cats.map(cat => {
    const g = gasto[cat]  || 0;
    const o = orcado[cat] || 0;
    const gPct = (g/maxVal)*100;
    const oPct = (o/maxVal)*100;
    const desvio = g - o;
    const dColor = desvio > 0 ? 'var(--po-red-t)' : (desvio < 0 ? 'var(--po-green-t)' : 'var(--po-txt2)');
    return `
    <div class="po-cat-row">
      <div class="po-cat-label">${cat}</div>
      <div class="po-cat-bars">
        <div class="po-cat-bar" style="width:${oPct}%;max-width:100%;background:var(--po-gray2)" title="Orçado: ${BRL(o)}"></div>
        <div class="po-cat-bar" style="width:0%;max-width:100%;background:linear-gradient(90deg,var(--po-blue),var(--po-cyan))" data-pct="${gPct}" title="Gasto: ${BRL(g)}"></div>
        <div class="po-cat-info">Orçado ${BRL(o)} · Gasto ${BRL(g)} · Desvio <span style="color:${dColor}">${(desvio>0?'+':'') + BRL(desvio)}</span></div>
      </div>
    </div>`;
  }).join('');

  // Sem categoria
  if (gasto['Sem categoria / Não mapeado']) {
    container.innerHTML += `
    <div class="po-cat-row" style="border-top:1px solid var(--po-border);margin-top:8px;padding-top:8px">
      <div class="po-cat-label" style="color:var(--po-txt2)">Sem categoria / Não mapeado</div>
      <div class="po-cat-bars">
        <div class="po-cat-bar" style="width:${(gasto['Sem categoria / Não mapeado']/maxVal)*100}%;background:var(--po-gray)"></div>
        <div class="po-cat-info" style="color:var(--po-txt2)">Gasto ${BRL(gasto['Sem categoria / Não mapeado'])} · <a href="/admin/painel-obras/depara" style="color:var(--po-blue-t)">Mapear categorias</a></div>
      </div>
    </div>`;
  }

  requestAnimationFrame(() => {
    document.querySelectorAll('.po-cat-bar[data-pct]').forEach(b => {
      b.style.width = Math.min(parseFloat(b.dataset.pct||0), 100) + '%';
    });
  });
}

// ── Conciliação ───────────────────────────────────────────────────
function renderConciliacao(c) {
  const cards = document.getElementById('po-conc-cards');
  const badge = document.getElementById('po-conc-badge');
  badge.innerHTML = `<span class="dot" style="background:var(--po-green)"></span> ${c.conciliadas} de ${c.lista.length} conciliadas`;

  cards.innerHTML = `
    <div class="po-conc-card"><div class="num" style="color:var(--po-green-t)">${c.conciliadas}</div><div class="lbl">Conciliadas</div></div>
    <div class="po-conc-card"><div class="num" style="color:var(--po-yellow-t)">${c.divergencia_pequena}</div><div class="lbl">Divergência pequena (≤ R$ 2.000)</div></div>
    <div class="po-conc-card"><div class="num" style="color:var(--po-red-t)">${c.divergentes}</div><div class="lbl">Divergentes — exigem revisão</div></div>`;

  const tbody = document.getElementById('po-conc-tbody');
  tbody.innerHTML = c.lista.map(o => {
    const dif = o.dif;
    const dColor = dif == null ? 'var(--po-txt2)' : (Math.abs(dif) === 0 ? 'var(--po-txt2)' : (Math.abs(dif) <= 2000 ? 'var(--po-yellow-t)' : 'var(--po-red-t)'));
    return `<tr>
      <td><span class="po-code">${o.codigo}</span><span class="po-name">${o.nome}</span></td>
      <td class="r">${BRL(o.nibo)}</td>
      <td class="r">${o.planilha != null ? BRL(o.planilha) : '<a href="/admin/painel-obras/planilha" style="color:var(--po-blue-t);font-size:11px">— Preencher</a>'}</td>
      <td class="r" style="color:${dColor}">${dif != null ? (dif > 0 ? '+' : '') + BRL(dif) : '—'}</td>
    </tr>`;
  }).join('');
}

// ── Fluxo Mensal ──────────────────────────────────────────────────
function renderFluxo(meses) {
  const maxVal = Math.max(...meses.map(m => m.pago + m.a_pagar + m.previsto), 1);
  const H = 220;

  const chart = document.getElementById('po-fluxo-chart');
  chart.innerHTML = meses.map(m => {
    const paidH   = Math.round((m.pago    / maxVal) * H);
    const apagarH = Math.round((m.a_pagar / maxVal) * H);
    const prevH   = Math.round((m.previsto/ maxVal) * H);
    const isAtual = m.mes === mesAtual;
    return `<div class="po-fluxo-col" title="${m.label}\nPago: ${BRL(m.pago)}\nA pagar: ${BRL(m.a_pagar)}\nPrevisto: ${BRL(m.previsto)}">
      ${isAtual ? '<span class="po-fluxo-atual">Mês atual</span>' : ''}
      <div class="po-fluxo-bar" style="height:${prevH}px;background:var(--po-gray2)"></div>
      <div class="po-fluxo-bar" style="height:${apagarH}px;background:var(--po-cyan)"></div>
      <div class="po-fluxo-bar" style="height:${paidH}px;background:var(--po-blue)"></div>
      <span class="po-fluxo-mes">${m.label}</span>
    </div>`;
  }).join('');

  const totalPago    = meses.slice(0, mesAtual).reduce((s,m) => s + m.pago, 0);
  const totalAPagar  = meses.reduce((s,m) => s + m.a_pagar, 0);
  const totalPrev    = meses.slice(mesAtual).reduce((s,m) => s + m.previsto, 0);
  const nomeMes      = meses[mesAtual-1]?.label || '';

  document.getElementById('po-fluxo-boxes').innerHTML = `
    <div class="po-fluxo-box"><div class="po-fluxo-box-label">Realizado até ${nomeMes}/${new Date().getFullYear()}</div><div class="po-fluxo-box-val" style="color:var(--po-blue-t)">${BRL(totalPago)}</div></div>
    <div class="po-fluxo-box"><div class="po-fluxo-box-label">A pagar (comprometido)</div><div class="po-fluxo-box-val" style="color:var(--po-cyan)">${BRL(totalAPagar)}</div></div>
    <div class="po-fluxo-box"><div class="po-fluxo-box-label">Previsto para ${nomeMes}/${new Date().getFullYear()}</div><div class="po-fluxo-box-val" style="color:var(--po-txt2)">${BRL(totalPrev)}</div></div>`;
}

// ── Skeletons ─────────────────────────────────────────────────────
function mostrarSkeletons() {
  for (let i=0;i<5;i++) {
    const el = document.getElementById('po-kpi-'+i);
    if (el) el.innerHTML = '<div class="po-skeleton h-32"></div><div class="po-skeleton h-16" style="margin-top:8px"></div>';
  }
}

// ── Copiar resumo ─────────────────────────────────────────────────
window.poCopiarResumo = function() {
  if (!_data) { poToast('Aguarde os dados carregarem.', 'error'); return; }
  const k = _data.kpis;
  const p = document.getElementById('po-periodo-sel').value;
  const alertas = (_data.obras_alerta||[]).map(a => `  - ${a.codigo} ${a.nome} (${PCT(a.desvio_pct)})`).join('\n');
  const txt = [
    'PAINEL FINANCEIRO DE OBRAS',
    'Período: ' + p,
    '',
    'Valor Contratado: '  + BRL(k.contratado),
    'Orçamento Estimado: ' + BRL(k.orcado),
    'Gasto Real: '         + BRL(k.gasto),
    'Desvio Global: '      + (k.desvio>0?'+':'') + BRL(k.desvio),
    'Obras em Estouro: '   + k.obras_estouro + ' de ' + k.obras_total,
    '',
    'Obras em Alerta:',
    alertas || '  Nenhuma',
  ].join('\n');
  navigator.clipboard.writeText(txt).then(() => poToast('Resumo copiado!')).catch(() => poToast('Erro ao copiar.','error'));
};

// ── Adicionar à pauta ─────────────────────────────────────────────
window.poAdicionarPauta = function() {
  if (!_data || !_data.obras_alerta?.length) { poToast('Nenhuma obra em alerta.', 'error'); return; }
  const ids = _data.obras_alerta.map(a => a.id);
  fetch('/admin/painel-obras/pauta/store', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: ids.map(id => 'obra_ids[]=' + id).join('&'),
  }).then(r => r.json()).then(res => {
    if (res.ok) poToast(ids.length + ' obra(s) adicionada(s) à pauta.');
    else poToast('Erro: ' + res.error, 'error');
  }).catch(() => poToast('Falha de conexão.', 'error'));
};

// ── Toast ─────────────────────────────────────────────────────────
window.poToast = function(msg, type = 'ok') {
  const c = document.getElementById('po-toast');
  const el = document.createElement('div');
  el.className = 'po-toast-item ' + type;
  el.textContent = msg;
  c.appendChild(el);
  setTimeout(() => el.remove(), 3500);
};

// ── Init ──────────────────────────────────────────────────────────
poCarregar();
})();
</script>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/Views/admin/layouts/app.php';
