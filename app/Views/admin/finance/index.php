<?php $pageTitle = 'Financeiro'; $currentPage = 'finance'; ?>
<?php ob_start(); ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h5 class="mb-0"><i class="bi bi-cash-coin"></i> Financeiro</h5>
        <small class="text-muted">Um resumo simples do seu caixa: o que entra, o que sai e quanto sobra.</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span id="lastSyncLabel" class="small text-muted">
            <?php if (!empty($lastSyncAt)): ?>
                Atualizado em <?= date('d/m/Y H:i', strtotime($lastSyncAt)) ?>
            <?php else: ?>
                Nunca atualizado
            <?php endif; ?>
        </span>
        <button id="btnSync" class="btn btn-sm btn-dark" <?= $hasToken ? '' : 'disabled' ?>>
            <i class="bi bi-arrow-repeat"></i> Atualizar
        </button>
    </div>
</div>

<?php if (!$hasToken): ?>
<div class="alert alert-warning d-flex align-items-center gap-2">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <div>O token do Nibo ainda não foi configurado. Configure em <a href="/admin/dev/nibo">Desenvolvimento → API Nibo</a> para atualizar os dados.</div>
</div>
<?php endif; ?>

<div id="syncStatus" class="alert alert-info d-none align-items-center gap-2" role="alert">
    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
    <span id="syncStatusText">Buscando seus dados no Nibo…</span>
</div>
<div id="syncError" class="alert alert-warning d-none" role="alert"></div>

<div id="emptyState" class="text-center py-5 d-none">
    <i class="bi bi-cloud-arrow-down display-4 text-muted"></i>
    <p class="mt-3 mb-1">Nenhum dado carregado ainda.</p>
    <p class="text-muted small">Clique em <strong>Atualizar</strong> para buscar as informações do Nibo.</p>
</div>

<div id="dashboard" class="d-none">

    <!-- Seletor de período (grande e simples) -->
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <span class="fw-semibold me-1">Ver os próximos:</span>
        <div class="btn-group" role="group" id="periodButtons">
            <button type="button" class="btn btn-outline-primary period-btn active" data-days="7">7 dias</button>
            <button type="button" class="btn btn-outline-primary period-btn" data-days="15">15 dias</button>
            <button type="button" class="btn btn-outline-primary period-btn" data-days="20">20 dias</button>
            <button type="button" class="btn btn-outline-primary period-btn" data-days="30">30 dias</button>
        </div>
        <span class="text-muted small ms-auto" id="periodRangeLabel"></span>
    </div>

    <!-- Cards grandes: a pergunta que todo mundo faz -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-1 text-primary">
                        <i class="bi bi-wallet2 fs-5"></i>
                        <span class="small text-uppercase fw-semibold" style="letter-spacing:.5px;">Tenho hoje</span>
                    </div>
                    <div class="fs-3 fw-bold text-primary" id="cardBalance">—</div>
                    <div class="small text-muted">Saldo somado das contas</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm bg-success-subtle">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-1 text-success">
                        <i class="bi bi-arrow-down-circle fs-5"></i>
                        <span class="small text-uppercase fw-semibold" style="letter-spacing:.5px;">Vou receber</span>
                    </div>
                    <div class="fs-3 fw-bold text-success" id="cardReceive">—</div>
                    <div class="small text-muted"><span id="cardReceiveCount">0</span> contas no período</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm bg-danger-subtle">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-1 text-danger">
                        <i class="bi bi-arrow-up-circle fs-5"></i>
                        <span class="small text-uppercase fw-semibold" style="letter-spacing:.5px;">Tenho que pagar</span>
                    </div>
                    <div class="fs-3 fw-bold text-danger" id="cardPay">—</div>
                    <div class="small text-muted"><span id="cardPayCount">0</span> contas no período</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card h-100 border-0 shadow-sm" id="cardLeftWrap">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-1" id="cardLeftHead">
                        <i class="bi bi-piggy-bank fs-5"></i>
                        <span class="small text-uppercase fw-semibold" style="letter-spacing:.5px;">Vai sobrar</span>
                    </div>
                    <div class="fs-3 fw-bold" id="cardLeft">—</div>
                    <div class="small text-muted">Tenho + recebo − pago</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerta de saldo negativo -->
    <div id="negativeAlert" class="alert alert-danger d-none align-items-center gap-2">
        <i class="bi bi-exclamation-octagon-fill fs-5"></i>
        <div>Atenção: no período escolhido as saídas superam o que você tem mais o que vai receber. <strong id="negativeGap"></strong></div>
    </div>

    <!-- Filtros opcionais (recolhidos, para não assustar leigos) -->
    <div class="mb-3">
        <button class="btn btn-sm btn-link text-decoration-none px-0" type="button" data-bs-toggle="collapse" data-bs-target="#advancedFilters">
            <i class="bi bi-funnel"></i> Filtrar por fornecedor, centro de custo ou situação
        </button>
        <div class="collapse" id="advancedFilters">
            <div class="card card-body border-0 shadow-sm">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Fornecedor / Cliente</label>
                        <select id="filterContact" class="form-select form-select-sm"><option value="">Todos</option></select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Centro de custo</label>
                        <select id="filterCostCenter" class="form-select form-select-sm"><option value="">Todos</option></select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Situação</label>
                        <select id="filterStatus" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            <option value="open">Em aberto</option>
                            <option value="overdue">Vencidas</option>
                            <option value="paid">Já pagas/recebidas</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <button id="btnClearFilters" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-x-lg"></i> Limpar filtros</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Duas listas claras: quem pagar e quem recebe -->
    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold text-danger"><i class="bi bi-arrow-up-circle"></i> Contas a pagar no período</span>
                    <span class="badge bg-danger" id="payListTotal">R$ 0,00</span>
                </div>
                <div class="table-responsive" style="max-height:420px;">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light" style="position:sticky;top:0;">
                            <tr><th>Quando</th><th>Quem</th><th class="text-end">Quanto</th><th>Situação</th></tr>
                        </thead>
                        <tbody id="payList"><tr><td colspan="4" class="text-center text-muted py-3">—</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <span class="fw-semibold text-success"><i class="bi bi-arrow-down-circle"></i> Contas a receber no período</span>
                    <span class="badge bg-success" id="recListTotal">R$ 0,00</span>
                </div>
                <div class="table-responsive" style="max-height:420px;">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light" style="position:sticky;top:0;">
                            <tr><th>Quando</th><th>Quem</th><th class="text-end">Quanto</th><th>Situação</th></tr>
                        </thead>
                        <tbody id="recList"><tr><td colspan="4" class="text-center text-muted py-3">—</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico simples: entradas x saídas -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white"><i class="bi bi-bar-chart"></i> Entradas e saídas ao longo do período</div>
        <div class="card-body"><canvas id="chartInOut" height="90"></canvas></div>
    </div>

    <!-- Maiores contas a pagar (ranking simples) -->
    <div class="row g-3 mb-3">
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-trophy"></i> Para quem você mais paga</div>
                <ul class="list-group list-group-flush" id="topSuppliers"></ul>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold"><i class="bi bi-trophy"></i> De quem você mais recebe</div>
                <ul class="list-group list-group-flush" id="topCustomers"></ul>
            </div>
        </div>
    </div>

    <!-- Visão detalhada (avançada) recolhida -->
    <div class="accordion mb-3" id="advancedAcc">
        <div class="accordion-item border-0 shadow-sm">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#detailFlow">
                    <i class="bi bi-table me-2"></i> Visão detalhada por dia (avançado)
                </button>
            </h2>
            <div id="detailFlow" class="accordion-collapse collapse" data-bs-parent="#advancedAcc">
                <div class="accordion-body p-0">
                    <div class="d-flex justify-content-end p-2">
                        <select id="flowGrouping" class="form-select form-select-sm" style="width:auto;">
                            <option value="day" selected>Por dia</option>
                            <option value="week">Por semana</option>
                            <option value="month">Por mês</option>
                        </select>
                    </div>
                    <div class="table-responsive" style="max-height:60vh;">
                        <table class="table table-sm table-bordered mb-0 finance-flow" style="font-size:.8rem; white-space:nowrap;">
                            <thead id="flowHead"></thead>
                            <tbody id="flowBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<style>
.finance-flow th, .finance-flow td { text-align:right; }
.finance-flow th:first-child, .finance-flow td:first-child { text-align:left; position:sticky; left:0; background:#fff; z-index:2; min-width:230px; }
.finance-flow thead th { position:sticky; top:0; background:#f8f9fa; z-index:3; }
.finance-flow thead th:first-child { z-index:4; }
.finance-flow .row-label { font-weight:600; }
.finance-flow .sec-in td, .finance-flow .sec-in th { background:#0d6efd; color:#fff; }
.finance-flow .sec-out td, .finance-flow .sec-out th { background:#dc3545; color:#fff; }
.finance-flow .val-in { color:#198754; }
.finance-flow .val-out { color:#dc3545; }
.finance-flow .row-detail td:first-child { padding-left:1.25rem; font-weight:400; color:#444; }
.finance-flow .balance-row td { background:#fff3cd; font-weight:700; }
.finance-flow .zero { color:#bbb; }
.period-btn.active { color:#fff; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    const el = (id) => document.getElementById(id);
    const charts = {};
    let state = { payables: [], receivables: [], accounts: [], totals: {}, filters: {} };
    let periodDays = 7;

    const WEEKDAYS = ['dom','seg','ter','qua','qui','sex','sáb'];
    const MONTHS = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'];

    function fmtMoney(v) { v = Number(v)||0; return v.toLocaleString('pt-BR', { minimumFractionDigits:2, maximumFractionDigits:2 }); }
    function fmtMoneyFull(v) { v = Number(v)||0; return v.toLocaleString('pt-BR', { style:'currency', currency:'BRL' }); }
    function parseDate(s) { if (!s) return null; const d = new Date(String(s).length<=10 ? s+'T00:00:00' : s); return isNaN(d.getTime())?null:d; }
    function toKey(d) { return d.toISOString().slice(0,10); }
    function esc(s) { return String(s==null?'':s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }
    function moneyCell(v) { return !v ? '<span class="zero">0,00</span>' : fmtMoney(v); }
    function statusBadge(st) {
        if (st === 'paid') return '<span class="badge bg-success">Liquidada</span>';
        if (st === 'overdue') return '<span class="badge bg-danger">Vencida</span>';
        return '<span class="badge bg-primary">Em aberto</span>';
    }
    function dateLabel(s) {
        const d = parseDate(s);
        if (!d) return '—';
        return d.toLocaleDateString('pt-BR', { day:'2-digit', month:'2-digit' }) + ' <span class="text-muted small">' + WEEKDAYS[d.getDay()] + '</span>';
    }

    // ── Janela de datas do período selecionado ────────────────────────
    function periodRange() {
        const start = new Date(); start.setHours(0,0,0,0);
        const end = new Date(start); end.setDate(end.getDate() + periodDays);
        return { start, end };
    }

    // ── Filtros (avançados, opcionais) ────────────────────────────────
    function getFilters() {
        return {
            costCenter: el('filterCostCenter').value,
            contact: el('filterContact').value,
            status: el('filterStatus').value,
        };
    }
    // Mapas id→nome dos cadastros, para casar filtro por ID ou por nome
    let ccNameById = {}, contactNameById = {};
    function buildLookups() {
        ccNameById = {}; contactNameById = {};
        (state.filters.costcenters || []).forEach(c => { if (c.id) ccNameById[String(c.id)] = c.name; });
        (state.filters.contacts || []).forEach(c => { if (c.id) contactNameById[String(c.id)] = c.name; });
    }

    function inPeriodAndFilters(x) {
        const f = getFilters();
        if (f.status && x.status !== f.status) return false;
        // Casa por ID; se o lançamento não tiver o ID, tenta pelo nome do cadastro.
        if (f.costCenter) {
            const byId = String(x.cost_center_id||'') === f.costCenter;
            const byName = ccNameById[f.costCenter] && (x.cost_center||'') === ccNameById[f.costCenter];
            if (!byId && !byName) return false;
        }
        if (f.contact) {
            const byId = String(x.contact_id||'') === f.contact;
            const byName = contactNameById[f.contact] && (x.contact_name||'') === contactNameById[f.contact];
            if (!byId && !byName) return false;
        }
        const { start, end } = periodRange();
        const d = parseDate(x.due_date);
        if (!d) return false;
        return d >= start && d <= end;
    }
    function filteredPayables() { return state.payables.filter(inPeriodAndFilters); }
    function filteredReceivables() { return state.receivables.filter(inPeriodAndFilters); }
    const sum = (arr) => arr.reduce((a,x) => a + (Number(x.value)||0), 0);

    // Popula os seletores a partir dos CADASTROS completos vindos da API
    // (todos os centros de custo e contatos), não apenas os que aparecem nos
    // lançamentos. Fallback: se a API não trouxe as listas, monta a partir dos
    // próprios lançamentos.
    function populateFilterOptions() {
        const fdata = state.filters || {};
        let centers = fdata.costcenters || [];
        let contacts = fdata.contacts || [];

        if (!centers.length || !contacts.length) {
            const cMap = {}, kMap = {};
            [...state.payables, ...state.receivables].forEach(x => {
                if (x.cost_center_id && x.cost_center && x.cost_center !== '—') cMap[x.cost_center_id] = x.cost_center;
                if (x.contact_id && x.contact_name && x.contact_name !== '—') kMap[x.contact_id] = x.contact_name;
            });
            if (!centers.length) centers = Object.entries(cMap).map(([id,name])=>({id,name})).sort((a,b)=>a.name.localeCompare(b.name));
            if (!contacts.length) contacts = Object.entries(kMap).map(([id,name])=>({id,name})).sort((a,b)=>a.name.localeCompare(b.name));
        }

        fillSelect('filterCostCenter', centers);
        fillSelect('filterContact', contacts);
    }
    // values = [{id, name}]
    function fillSelect(id, values) {
        const sel = el(id); const current = sel.value;
        sel.innerHTML = '<option value="">Todos</option>' + values
            .filter(v => v && v.id)
            .map(v => '<option value="'+esc(v.id)+'">'+esc(v.name)+'</option>').join('');
        if (current && sel.querySelector('option[value="'+CSS.escape(current)+'"]')) sel.value = current;
    }

    // ── Cards de resumo ───────────────────────────────────────────────
    function renderCards(fpay, frec) {
        const balance = Number(state.totals.balance) || 0;
        const toPay = sum(fpay);
        const toReceive = sum(frec);
        const left = balance + toReceive - toPay;

        el('cardBalance').textContent = fmtMoneyFull(balance);
        el('cardReceive').textContent = fmtMoneyFull(toReceive);
        el('cardReceiveCount').textContent = frec.length;
        el('cardPay').textContent = fmtMoneyFull(toPay);
        el('cardPayCount').textContent = fpay.length;
        el('cardLeft').textContent = fmtMoneyFull(left);

        // Cor do "vai sobrar"
        const wrap = el('cardLeftWrap'), head = el('cardLeftHead'), val = el('cardLeft');
        if (left < 0) {
            wrap.className = 'card h-100 border-0 shadow-sm bg-danger-subtle';
            head.className = 'd-flex align-items-center gap-2 mb-1 text-danger';
            val.className = 'fs-3 fw-bold text-danger';
        } else {
            wrap.className = 'card h-100 border-0 shadow-sm bg-primary-subtle';
            head.className = 'd-flex align-items-center gap-2 mb-1 text-primary';
            val.className = 'fs-3 fw-bold text-primary';
        }

        // Alerta de saldo negativo
        if (left < 0) {
            el('negativeAlert').classList.remove('d-none');
            el('negativeAlert').classList.add('d-flex');
            el('negativeGap').textContent = 'Faltam ' + fmtMoneyFull(Math.abs(left)) + ' para cobrir os pagamentos.';
        } else {
            el('negativeAlert').classList.add('d-none');
            el('negativeAlert').classList.remove('d-flex');
        }

        // Rótulo do período
        const { start, end } = periodRange();
        el('periodRangeLabel').textContent = 'De ' + start.toLocaleDateString('pt-BR') + ' até ' + end.toLocaleDateString('pt-BR');
    }

    // ── Listas quem pagar / quem recebe ───────────────────────────────
    function renderSimpleList(tbodyId, totalId, items, valClass) {
        const rows = items.slice().sort((a,b) => (parseDate(a.due_date)||0) - (parseDate(b.due_date)||0));
        el(totalId).textContent = fmtMoneyFull(sum(rows));
        const tb = el(tbodyId);
        if (!rows.length) { tb.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Nada no período.</td></tr>'; return; }
        tb.innerHTML = rows.map(r =>
            '<tr><td>' + dateLabel(r.due_date) + '</td>'
            + '<td>' + esc(r.contact_name && r.contact_name !== '—' ? r.contact_name : (r.description||'—')) + '</td>'
            + '<td class="text-end fw-semibold ' + valClass + '">' + fmtMoneyFull(r.value) + '</td>'
            + '<td>' + statusBadge(r.status) + '</td></tr>'
        ).join('');
    }

    // ── Ranking (top contatos) ────────────────────────────────────────
    function renderRanking(ulId, items, valClass) {
        const by = {};
        items.forEach(x => {
            const name = x.contact_name && x.contact_name !== '—' ? x.contact_name : (x.description||'Outros');
            by[name] = (by[name]||0) + (Number(x.value)||0);
        });
        const top = Object.entries(by).sort((a,b)=>b[1]-a[1]).slice(0,5);
        const ul = el(ulId);
        if (!top.length) { ul.innerHTML = '<li class="list-group-item text-muted small">Nada no período.</li>'; return; }
        ul.innerHTML = top.map(([name,val]) =>
            '<li class="list-group-item d-flex justify-content-between align-items-center">'
            + '<span class="text-truncate me-2">' + esc(name) + '</span>'
            + '<span class="fw-semibold ' + valClass + '">' + fmtMoneyFull(val) + '</span></li>'
        ).join('');
    }

    // ── Gráfico entradas x saídas ─────────────────────────────────────
    function renderChart(fpay, frec) {
        const grouping = periodDays <= 15 ? 'day' : 'week';
        const { start, end } = periodRange();
        const periods = buildPeriods(start, end, grouping);
        const map = {};
        periods.forEach(p => map[p.key] = { in:0, out:0 });
        frec.forEach(x => { const k = periodKeyForDate(parseDate(x.due_date), grouping); if (map[k]) map[k].in += Number(x.value)||0; });
        fpay.forEach(x => { const k = periodKeyForDate(parseDate(x.due_date), grouping); if (map[k]) map[k].out += Number(x.value)||0; });

        const labels = periods.map(p => p.plain);
        if (charts.inOut) charts.inOut.destroy();
        charts.inOut = new Chart(el('chartInOut'), {
            type: 'bar',
            data: { labels, datasets: [
                { label: 'Entra', data: periods.map(p=>map[p.key].in), backgroundColor: 'rgba(25,135,84,.8)' },
                { label: 'Sai', data: periods.map(p=>map[p.key].out), backgroundColor: 'rgba(220,53,69,.8)' },
            ]},
            options: {
                responsive: true, maintainAspectRatio: true,
                plugins: { tooltip: { callbacks: { label: c => c.dataset.label + ': ' + fmtMoneyFull(c.raw) } } },
                scales: { y: { ticks: { callback: v => 'R$ ' + (v/1000).toFixed(0) + 'k' } } }
            }
        });
    }

    // ── Períodos (colunas) para gráfico e matriz ──────────────────────
    function startOfWeek(d) { const t = new Date(d); t.setHours(0,0,0,0); const day=(t.getDay()+6)%7; t.setDate(t.getDate()-day); return t; }
    function buildPeriods(start, end, grouping) {
        const periods = [];
        let cur = new Date(start); cur.setHours(0,0,0,0);
        if (grouping==='week') cur = startOfWeek(cur);
        if (grouping==='month') cur = new Date(cur.getFullYear(), cur.getMonth(), 1);
        let guard = 0;
        while (cur <= end && guard < 800) {
            guard++;
            let next, key, label, plain;
            if (grouping==='day') {
                key = toKey(cur);
                plain = cur.getDate()+'/'+(cur.getMonth()+1);
                label = plain + '<br><span class="small opacity-75">'+WEEKDAYS[cur.getDay()]+'</span>';
                next = new Date(cur); next.setDate(next.getDate()+1);
            } else if (grouping==='week') {
                const we = new Date(cur); we.setDate(we.getDate()+6);
                key = toKey(cur);
                plain = cur.getDate()+'/'+(cur.getMonth()+1)+' a '+we.getDate()+'/'+(we.getMonth()+1);
                label = cur.getDate()+'/'+(cur.getMonth()+1)+'<br><span class="small opacity-75">a '+we.getDate()+'/'+(we.getMonth()+1)+'</span>';
                next = new Date(cur); next.setDate(next.getDate()+7);
            } else {
                key = cur.getFullYear()+'-'+String(cur.getMonth()+1).padStart(2,'0');
                plain = MONTHS[cur.getMonth()]+'/'+cur.getFullYear();
                label = MONTHS[cur.getMonth()]+'<br><span class="small opacity-75">'+cur.getFullYear()+'</span>';
                next = new Date(cur.getFullYear(), cur.getMonth()+1, 1);
            }
            periods.push({ key, label, plain });
            cur = next;
        }
        return periods;
    }
    function periodKeyForDate(d, grouping) {
        if (!d) return null;
        if (grouping==='day') return toKey(d);
        if (grouping==='week') return toKey(startOfWeek(d));
        return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0');
    }

    // ── Matriz detalhada (avançado) ───────────────────────────────────
    function renderFlow(fpay, frec) {
        const detail = el('detailFlow');
        if (!detail || !detail.classList.contains('show')) return; // só renderiza se aberto
        const grouping = el('flowGrouping').value;
        const { start, end } = periodRange();
        const periods = buildPeriods(start, end, grouping);
        const map = {};
        periods.forEach(p => map[p.key] = { in:0, out:0, inBy:{}, outBy:{} });
        function add(list, field, byField) {
            list.forEach(x => {
                const k = periodKeyForDate(parseDate(x.due_date), grouping);
                if (!map[k]) return;
                const v = Number(x.value)||0;
                map[k][field] += v;
                const name = x.contact_name && x.contact_name!=='—' ? x.contact_name : (x.description||'Outros');
                map[k][byField][name] = (map[k][byField][name]||0) + v;
            });
        }
        add(frec, 'in', 'inBy'); add(fpay, 'out', 'outBy');
        const incomeNames = new Set(), expenseNames = new Set();
        periods.forEach(p => { Object.keys(map[p.key].inBy).forEach(n=>incomeNames.add(n)); Object.keys(map[p.key].outBy).forEach(n=>expenseNames.add(n)); });
        let running = Number(state.totals.balance)||0;
        periods.forEach(p => { const m=map[p.key]; m.open=running; m.close=running+m.in-m.out; running=m.close; });

        let head = '<tr><th>DATA</th>' + periods.map(p=>'<th>'+p.label+'</th>').join('') + '</tr>';
        el('flowHead').innerHTML = head;
        const cols = periods.map(p=>p.key);
        let body = '';
        body += '<tr class="row-label"><td>SALDO INICIAL</td>' + cols.map(k=>'<td>'+moneyCell(map[k].open)+'</td>').join('') + '</tr>';
        body += '<tr class="sec-in row-label"><td>TOTAL DE ENTRADAS</td>' + cols.map(k=>'<td>'+moneyCell(map[k].in)+'</td>').join('') + '</tr>';
        [...incomeNames].sort((a,b)=>a.localeCompare(b)).forEach(name => {
            body += '<tr class="row-detail"><td>'+esc(name)+'</td>' + cols.map(k=>'<td class="val-in">'+moneyCell(map[k].inBy[name]||0)+'</td>').join('') + '</tr>';
        });
        body += '<tr class="sec-out row-label"><td>TOTAL DE SAÍDAS</td>' + cols.map(k=>'<td>'+moneyCell(map[k].out)+'</td>').join('') + '</tr>';
        [...expenseNames].sort((a,b)=>a.localeCompare(b)).forEach(name => {
            body += '<tr class="row-detail"><td>'+esc(name)+'</td>' + cols.map(k=>'<td class="val-out">'+moneyCell(map[k].outBy[name]||0)+'</td>').join('') + '</tr>';
        });
        body += '<tr class="balance-row"><td>SALDO FINAL</td>' + cols.map(k=>{const v=map[k].close; return '<td class="'+(v<0?'text-danger':'')+'">'+moneyCell(v)+'</td>';}).join('') + '</tr>';
        el('flowBody').innerHTML = body;
    }

    // ── Recalcula tudo conforme período + filtros ─────────────────────
    function refresh() {
        const fpay = filteredPayables(), frec = filteredReceivables();
        renderCards(fpay, frec);
        renderSimpleList('payList', 'payListTotal', fpay, 'text-danger');
        renderSimpleList('recList', 'recListTotal', frec, 'text-success');
        renderRanking('topSuppliers', fpay, 'text-danger');
        renderRanking('topCustomers', frec, 'text-success');
        renderChart(fpay, frec);
        renderFlow(fpay, frec);
    }

    function applyData(data) {
        state.payables = data.payables || [];
        state.receivables = data.receivables || [];
        state.accounts = data.accounts || [];
        state.totals = data.totals || {};
        state.filters = data.filters || {};
        buildLookups();
        el('emptyState').classList.add('d-none');
        el('dashboard').classList.remove('d-none');
        populateFilterOptions();
        refresh();
    }

    function showError(msg) {
        const box = el('syncError');
        box.textContent = msg;
        box.classList.remove('d-none');
        setTimeout(() => box.classList.add('d-none'), 9000);
    }

    async function loadData() {
        try {
            const res = await fetch('/admin/finance/data');
            const json = await res.json();
            if (json.ok && json.has_data) applyData(json.data);
            else el('emptyState').classList.remove('d-none');
        } catch (e) { el('emptyState').classList.remove('d-none'); }
    }

    async function doSync() {
        const btn = el('btnSync');
        btn.disabled = true;
        el('syncStatus').classList.remove('d-none'); el('syncStatus').classList.add('d-flex');
        el('syncError').classList.add('d-none');
        try {
            const res = await fetch('/admin/finance/sync', { method:'POST', headers:{ 'X-Requested-With':'XMLHttpRequest' } });
            const json = await res.json();
            if (json.ok) {
                applyData(json.data);
                if (json.synced_at) el('lastSyncLabel').textContent = 'Atualizado em ' + new Date(json.synced_at.replace(' ','T')).toLocaleString('pt-BR');
                if (json.partial_errors && json.partial_errors.length) showError('Atualizado com avisos: ' + json.partial_errors.join(' · '));
            } else {
                showError(json.error || 'Não foi possível atualizar. Mantendo a última versão.');
            }
        } catch (e) {
            showError('Falha de conexão ao atualizar. Mantendo a última versão.');
        } finally {
            el('syncStatus').classList.add('d-none'); el('syncStatus').classList.remove('d-flex');
            btn.disabled = false;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        el('btnSync').addEventListener('click', doSync);

        // Botões de período (7/15/20/30)
        document.querySelectorAll('.period-btn').forEach(b => b.addEventListener('click', function () {
            document.querySelectorAll('.period-btn').forEach(x => x.classList.remove('active'));
            this.classList.add('active');
            periodDays = parseInt(this.dataset.days, 10);
            refresh();
        }));

        // Filtros avançados recalculam tudo
        ['filterContact','filterCostCenter','filterStatus','flowGrouping'].forEach(id => el(id).addEventListener('change', refresh));
        el('btnClearFilters').addEventListener('click', function () {
            el('filterContact').value = ''; el('filterCostCenter').value = ''; el('filterStatus').value = '';
            refresh();
        });

        // Renderiza a matriz quando o usuário abrir a seção avançada
        el('detailFlow').addEventListener('shown.bs.collapse', refresh);

        loadData();
    });
})();
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
