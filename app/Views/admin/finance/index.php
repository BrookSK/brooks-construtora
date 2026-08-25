<?php $pageTitle = 'Financeiro'; $currentPage = 'finance'; ?>
<?php ob_start(); ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h5 class="mb-0"><i class="bi bi-cash-coin"></i> Financeiro</h5>
        <small class="text-muted">Previsão de fluxo de caixa — apenas leitura dos dados do Nibo.</small>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span id="lastSyncLabel" class="small text-muted">
            <?php if (!empty($lastSyncAt)): ?>
                Última atualização: <?= date('d/m/Y H:i', strtotime($lastSyncAt)) ?>
            <?php else: ?>
                Nunca sincronizado
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
    <span id="syncStatusText">Sincronizando com o Nibo…</span>
</div>
<div id="syncError" class="alert alert-warning d-none" role="alert"></div>

<div id="emptyState" class="text-center py-5 d-none">
    <i class="bi bi-cloud-arrow-down display-4 text-muted"></i>
    <p class="mt-3 mb-1">Nenhum dado carregado ainda.</p>
    <p class="text-muted small">Clique em <strong>Atualizar</strong> para buscar as informações do Nibo.</p>
</div>

<div id="dashboard" class="d-none">

    <!-- Filtros globais -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Agrupar por</label>
                    <select id="flowGrouping" class="form-select form-select-sm">
                        <option value="day" selected>Diário</option>
                        <option value="week">Semanal</option>
                        <option value="month">Mensal</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">De</label>
                    <input type="date" id="flowStart" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Até</label>
                    <input type="date" id="flowEnd" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Centro de custo</label>
                    <select id="filterCostCenter" class="form-select form-select-sm"><option value="">Todos</option></select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Fornecedor / Cliente</label>
                    <select id="filterContact" class="form-select form-select-sm"><option value="">Todos</option></select>
                </div>
                <div class="col-6 col-md-2 d-flex gap-1">
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">Todas situações</option>
                        <option value="open">Em aberto</option>
                        <option value="overdue">Vencidas</option>
                        <option value="paid">Liquidadas</option>
                    </select>
                    <button id="btnClearFilters" class="btn btn-sm btn-outline-secondary" title="Limpar filtros"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div class="mt-2 d-flex flex-wrap gap-2">
                <button class="btn btn-sm btn-outline-primary preset" data-days="7">Próximos 7 dias</button>
                <button class="btn btn-sm btn-outline-primary preset" data-days="30">30 dias</button>
                <button class="btn btn-sm btn-outline-primary preset" data-days="90">90 dias</button>
                <button class="btn btn-sm btn-outline-primary preset" data-mode="this-month">Este mês</button>
                <button class="btn btn-sm btn-outline-primary preset" data-mode="year">Este ano</button>
            </div>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small text-uppercase" style="letter-spacing:.5px;">Saldo atual em contas</div>
                    <div class="fs-4 fw-bold text-primary" id="kpiBalance">—</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm border-start border-4 border-success">
                <div class="card-body">
                    <div class="text-muted small text-uppercase" style="letter-spacing:.5px;">A receber (em aberto)</div>
                    <div class="fs-4 fw-bold text-success" id="kpiReceivable">—</div>
                    <div class="small text-muted"><span id="kpiReceivableCount">0</span> contas</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm border-start border-4 border-danger">
                <div class="card-body">
                    <div class="text-muted small text-uppercase" style="letter-spacing:.5px;">A pagar (em aberto)</div>
                    <div class="fs-4 fw-bold text-danger" id="kpiPayable">—</div>
                    <div class="small text-muted"><span id="kpiPayableCount">0</span> contas</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm border-start border-4 border-warning">
                <div class="card-body">
                    <div class="text-muted small text-uppercase" style="letter-spacing:.5px;">Saldo projetado (fim do período)</div>
                    <div class="fs-4 fw-bold" id="kpiProjected">—</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Abas -->
    <ul class="nav nav-tabs mb-3" id="financeTabs" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-flow" type="button">Fluxo de Caixa</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-charts" type="button">Gráficos</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-payables" type="button">Contas a Pagar</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-receivables" type="button">Contas a Receber</button></li>
    </ul>

    <div class="tab-content">
        <!-- Fluxo de Caixa (matriz por data) -->
        <div class="tab-pane fade show active" id="tab-flow" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span><i class="bi bi-table"></i> Previsão de fluxo de caixa</span>
                    <span class="small text-muted" id="flowSummary"></span>
                </div>
                <div class="table-responsive" style="max-height:70vh;">
                    <table class="table table-sm table-bordered mb-0 finance-flow" style="font-size:.8rem; white-space:nowrap;">
                        <thead id="flowHead"></thead>
                        <tbody id="flowBody"></tbody>
                    </table>
                </div>
                <div class="card-footer bg-white small text-muted">
                    <span class="badge bg-primary-subtle text-primary">Saldo inicial</span>
                    entradas somam ao saldo,
                    <span class="text-danger">saídas</span> reduzem.
                    O <strong>saldo final</strong> de um dia vira o saldo inicial do próximo.
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="tab-pane fade" id="tab-charts" role="tabpanel">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white"><i class="bi bi-bar-chart"></i> Entradas x Saídas por período</div>
                <div class="card-body"><canvas id="chartInOut" height="110"></canvas></div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><i class="bi bi-graph-up"></i> Saldo projetado acumulado</div>
                <div class="card-body"><canvas id="chartBalance" height="110"></canvas></div>
            </div>
        </div>

        <!-- Contas a Pagar -->
        <div class="tab-pane fade" id="tab-payables" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span><i class="bi bi-arrow-down-circle text-danger"></i> Contas a Pagar</span>
                    <input type="search" id="filterPayables" class="form-control form-control-sm" placeholder="Buscar nesta lista…" style="max-width:220px;">
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light"><tr><th>Vencimento</th><th>Fornecedor</th><th>Descrição</th><th>Centro de custo</th><th class="text-end">Valor</th><th>Situação</th></tr></thead>
                        <tbody id="tblPayables"><tr><td colspan="6" class="text-center text-muted py-3">—</td></tr></tbody>
                    </table>
                </div>
                <div class="card-footer bg-white text-end small">Total a pagar em aberto: <strong class="text-danger" id="totalPayables">—</strong></div>
            </div>
        </div>

        <!-- Contas a Receber -->
        <div class="tab-pane fade" id="tab-receivables" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span><i class="bi bi-arrow-up-circle text-success"></i> Contas a Receber</span>
                    <input type="search" id="filterReceivables" class="form-control form-control-sm" placeholder="Buscar nesta lista…" style="max-width:220px;">
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light"><tr><th>Vencimento</th><th>Cliente</th><th>Descrição</th><th>Centro de custo</th><th class="text-end">Valor</th><th>Situação</th></tr></thead>
                        <tbody id="tblReceivables"><tr><td colspan="6" class="text-center text-muted py-3">—</td></tr></tbody>
                    </table>
                </div>
                <div class="card-footer bg-white text-end small">Total a receber em aberto: <strong class="text-success" id="totalReceivables">—</strong></div>
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
.finance-flow .sec-in { background:#0d6efd; color:#fff; }
.finance-flow .sec-in td, .finance-flow .sec-in th { background:#0d6efd; color:#fff; }
.finance-flow .sec-out { background:#dc3545; color:#fff; }
.finance-flow .sec-out td, .finance-flow .sec-out th { background:#dc3545; color:#fff; }
.finance-flow .val-in { color:#198754; }
.finance-flow .val-out { color:#dc3545; }
.finance-flow .row-detail td:first-child { padding-left:1.25rem; font-weight:400; color:#444; }
.finance-flow .balance-row { background:#fff3cd; font-weight:700; }
.finance-flow .balance-row td:first-child { background:#fff3cd; }
.finance-flow .zero { color:#bbb; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    const el = (id) => document.getElementById(id);
    const charts = {};
    let state = { payables: [], receivables: [], accounts: [], totals: {} };

    const WEEKDAYS = ['domingo','segunda-feira','terça-feira','quarta-feira','quinta-feira','sexta-feira','sábado'];
    const MONTHS = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'];

    function fmtMoney(v) {
        v = Number(v) || 0;
        return v.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function fmtMoneyFull(v) {
        v = Number(v) || 0;
        return v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }
    function parseDate(s) {
        if (!s) return null;
        const d = new Date(String(s).length <= 10 ? s + 'T00:00:00' : s);
        return isNaN(d.getTime()) ? null : d;
    }
    function toKey(d) { return d.toISOString().slice(0,10); }
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
    }
    function statusBadge(st) {
        if (st === 'paid') return '<span class="badge bg-success">Pago</span>';
        if (st === 'overdue') return '<span class="badge bg-danger">Vencida</span>';
        return '<span class="badge bg-primary">Em aberto</span>';
    }
    function moneyCell(v) {
        if (!v) return '<span class="zero">0,00</span>';
        return fmtMoney(v);
    }

    // ── Filtros globais ───────────────────────────────────────────────
    function getFilters() {
        return {
            start: parseDate(el('flowStart').value),
            end: parseDate(el('flowEnd').value),
            costCenter: el('filterCostCenter').value,
            contact: el('filterContact').value,
            status: el('filterStatus').value,
        };
    }

    // Aplica os filtros globais (data, centro de custo, contato, situação)
    // a uma lista de lançamentos. A busca textual das tabelas é aplicada à parte.
    function applyGlobalFilters(list) {
        const f = getFilters();
        return list.filter(x => {
            if (f.status && x.status !== f.status) return false;
            if (f.costCenter && (x.cost_center || '—') !== f.costCenter) return false;
            if (f.contact && (x.contact_name || '—') !== f.contact) return false;
            if (f.start || f.end) {
                const d = parseDate(x.due_date);
                if (!d) return false;
                if (f.start && d < f.start) return false;
                if (f.end && d > f.end) return false;
            }
            return true;
        });
    }

    function filteredPayables() { return applyGlobalFilters(state.payables); }
    function filteredReceivables() { return applyGlobalFilters(state.receivables); }

    // Popula os seletores de centro de custo e fornecedor/cliente
    function populateFilterOptions() {
        const centers = new Set(), contacts = new Set();
        [...state.payables, ...state.receivables].forEach(x => {
            if (x.cost_center && x.cost_center !== '—') centers.add(x.cost_center);
            if (x.contact_name && x.contact_name !== '—') contacts.add(x.contact_name);
        });
        fillSelect('filterCostCenter', [...centers].sort((a,b)=>a.localeCompare(b)), 'Todos');
        fillSelect('filterContact', [...contacts].sort((a,b)=>a.localeCompare(b)), 'Todos');
    }
    function fillSelect(id, values, allLabel) {
        const sel = el(id);
        const current = sel.value;
        sel.innerHTML = '<option value="">' + allLabel + '</option>' + values.map(v => '<option value="' + esc(v) + '">' + esc(v) + '</option>').join('');
        if (values.includes(current)) sel.value = current;
    }

    // ── KPIs ──────────────────────────────────────────────────────────
    function renderKpis(fpay, frec) {
        const openPay = fpay.filter(p => p.status !== 'paid');
        const openRec = frec.filter(r => r.status !== 'paid');
        const sum = (arr) => arr.reduce((a, x) => a + (Number(x.value) || 0), 0);
        el('kpiBalance').textContent = fmtMoneyFull(state.totals.balance || 0);
        el('kpiPayable').textContent = fmtMoneyFull(sum(openPay));
        el('kpiPayableCount').textContent = openPay.length;
        el('kpiReceivable').textContent = fmtMoneyFull(sum(openRec));
        el('kpiReceivableCount').textContent = openRec.length;
    }

    // ── Geração de períodos (colunas) ─────────────────────────────────
    function startOfWeek(d) { const t = new Date(d); t.setHours(0,0,0,0); const day = (t.getDay()+6)%7; t.setDate(t.getDate()-day); return t; }

    function buildPeriods(start, end, grouping) {
        const periods = [];
        let cur = new Date(start); cur.setHours(0,0,0,0);
        if (grouping === 'week') cur = startOfWeek(cur);
        if (grouping === 'month') cur = new Date(cur.getFullYear(), cur.getMonth(), 1);
        let guard = 0;
        while (cur <= end && guard < 800) {
            guard++;
            let next, key, label;
            if (grouping === 'day') {
                key = toKey(cur);
                label = cur.getDate() + '/' + (cur.getMonth()+1) + '<br><span class="small opacity-75">' + WEEKDAYS[cur.getDay()].slice(0,3) + '</span>';
                next = new Date(cur); next.setDate(next.getDate()+1);
            } else if (grouping === 'week') {
                const wkEnd = new Date(cur); wkEnd.setDate(wkEnd.getDate()+6);
                key = toKey(cur);
                label = cur.getDate() + '/' + (cur.getMonth()+1) + '<br><span class="small opacity-75">a ' + wkEnd.getDate() + '/' + (wkEnd.getMonth()+1) + '</span>';
                next = new Date(cur); next.setDate(next.getDate()+7);
            } else {
                key = cur.getFullYear() + '-' + String(cur.getMonth()+1).padStart(2,'0');
                label = MONTHS[cur.getMonth()] + '<br><span class="small opacity-75">' + cur.getFullYear() + '</span>';
                next = new Date(cur.getFullYear(), cur.getMonth()+1, 1);
            }
            periods.push({ key, label, start: new Date(cur), end: new Date(next.getTime()-1) });
            cur = next;
        }
        return periods;
    }

    function periodKeyForDate(d, grouping) {
        if (grouping === 'day') return toKey(d);
        if (grouping === 'week') return toKey(startOfWeek(d));
        return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0');
    }

    // ── Construção da matriz de fluxo ─────────────────────────────────
    function buildFlowMatrix(periods, grouping, fpay, frec) {
        // acumula por período: entradas/saídas totais e por contato
        const map = {};
        periods.forEach(p => { map[p.key] = { in:0, out:0, inBy:{}, outBy:{} }; });

        function add(list, field, byField) {
            list.forEach(x => {
                const d = parseDate(x.due_date);
                if (!d) return;
                const k = periodKeyForDate(d, grouping);
                if (!map[k]) return; // fora da janela
                const v = Number(x.value) || 0;
                map[k][field] += v;
                const name = x.contact_name && x.contact_name !== '—' ? x.contact_name : (x.description || 'Outros');
                map[k][byField][name] = (map[k][byField][name] || 0) + v;
            });
        }
        add(frec, 'in', 'inBy');
        add(fpay, 'out', 'outBy');

        // conjunto de contatos (linhas de detalhe)
        const incomeNames = new Set(), expenseNames = new Set();
        periods.forEach(p => {
            Object.keys(map[p.key].inBy).forEach(n => incomeNames.add(n));
            Object.keys(map[p.key].outBy).forEach(n => expenseNames.add(n));
        });

        // saldo inicial = saldo atual em contas
        let running = Number(state.totals.balance) || 0;
        periods.forEach(p => {
            const m = map[p.key];
            m.open = running;
            m.close = running + m.in - m.out;
            running = m.close;
        });

        return {
            map,
            incomeNames: [...incomeNames].sort((a,b)=>a.localeCompare(b)),
            expenseNames: [...expenseNames].sort((a,b)=>a.localeCompare(b)),
        };
    }

    function renderFlow() {
        const grouping = el('flowGrouping').value;
        const startV = el('flowStart').value, endV = el('flowEnd').value;
        if (!startV || !endV) return;
        const start = parseDate(startV), end = parseDate(endV);
        if (!start || !end || end < start) return;

        const fpay = filteredPayables(), frec = filteredReceivables();
        const periods = buildPeriods(start, end, grouping);
        const { map, incomeNames, expenseNames } = buildFlowMatrix(periods, grouping, fpay, frec);

        // Resumo no cabeçalho
        const totIn = frec.reduce((a,x)=>a+(Number(x.value)||0),0);
        const totOut = fpay.reduce((a,x)=>a+(Number(x.value)||0),0);
        el('flowSummary').innerHTML = 'Entradas: <span class="text-success">' + fmtMoneyFull(totIn) + '</span> · Saídas: <span class="text-danger">' + fmtMoneyFull(totOut) + '</span>';

        // Cabeçalho
        let head = '<tr><th>DATA</th>';
        periods.forEach(p => { head += '<th>' + p.label + '</th>'; });
        head += '</tr>';
        el('flowHead').innerHTML = head;

        const cols = periods.map(p => p.key);
        let body = '';

        // Saldo inicial
        body += '<tr class="row-label"><td>SALDO INICIAL</td>';
        cols.forEach(k => { body += '<td>' + moneyCell(map[k].open) + '</td>'; });
        body += '</tr>';

        // Total de entradas
        body += '<tr class="sec-in row-label"><td>TOTAL DE ENTRADAS</td>';
        cols.forEach(k => { body += '<td>' + moneyCell(map[k].in) + '</td>'; });
        body += '</tr>';
        // Detalhe entradas por contato
        incomeNames.forEach(name => {
            body += '<tr class="row-detail"><td>' + esc(name) + '</td>';
            cols.forEach(k => { body += '<td class="val-in">' + moneyCell(map[k].inBy[name] || 0) + '</td>'; });
            body += '</tr>';
        });

        // Total de saídas
        body += '<tr class="sec-out row-label"><td>TOTAL DE SAÍDAS</td>';
        cols.forEach(k => { body += '<td>' + moneyCell(map[k].out) + '</td>'; });
        body += '</tr>';
        // Detalhe saídas por fornecedor
        expenseNames.forEach(name => {
            body += '<tr class="row-detail"><td>' + esc(name) + '</td>';
            cols.forEach(k => { body += '<td class="val-out">' + moneyCell(map[k].outBy[name] || 0) + '</td>'; });
            body += '</tr>';
        });

        // Saldo final
        body += '<tr class="balance-row"><td>SALDO FINAL</td>';
        cols.forEach(k => {
            const v = map[k].close;
            const cls = v < 0 ? ' text-danger' : '';
            body += '<td class="' + cls + '">' + moneyCell(v) + '</td>';
        });
        body += '</tr>';

        el('flowBody').innerHTML = body;

        // Atualiza KPI de saldo projetado (último período)
        if (cols.length) {
            const last = map[cols[cols.length-1]].close;
            el('kpiProjected').textContent = fmtMoneyFull(last);
            el('kpiProjected').className = 'fs-4 fw-bold ' + (last < 0 ? 'text-danger' : 'text-success');
        }

        renderCharts(periods, map);
    }

    // ── Gráficos (barras simples) ─────────────────────────────────────
    function renderCharts(periods, map) {
        const labels = periods.map(p => p.label.replace(/<br>/g, ' ').replace(/<[^>]+>/g, ''));
        const ins = periods.map(p => map[p.key].in);
        const outs = periods.map(p => map[p.key].out);
        const closes = periods.map(p => map[p.key].close);

        if (el('chartInOut')) {
            if (charts.inOut) charts.inOut.destroy();
            charts.inOut = new Chart(el('chartInOut'), {
                type: 'bar',
                data: { labels, datasets: [
                    { label: 'Entradas', data: ins, backgroundColor: 'rgba(25,135,84,.8)' },
                    { label: 'Saídas', data: outs, backgroundColor: 'rgba(220,53,69,.8)' },
                ]},
                options: {
                    responsive: true, maintainAspectRatio: true,
                    plugins: { tooltip: { callbacks: { label: c => c.dataset.label + ': ' + fmtMoneyFull(c.raw) } } },
                    scales: { y: { ticks: { callback: v => 'R$ ' + (v/1000).toFixed(0) + 'k' } } }
                }
            });
        }
        if (el('chartBalance')) {
            if (charts.balance) charts.balance.destroy();
            charts.balance = new Chart(el('chartBalance'), {
                type: 'bar',
                data: { labels, datasets: [
                    { label: 'Saldo projetado', data: closes,
                      backgroundColor: closes.map(v => v < 0 ? 'rgba(220,53,69,.8)' : 'rgba(13,110,253,.8)') },
                ]},
                options: {
                    responsive: true, maintainAspectRatio: true,
                    plugins: { tooltip: { callbacks: { label: c => fmtMoneyFull(c.raw) } } },
                    scales: { y: { ticks: { callback: v => 'R$ ' + (v/1000).toFixed(0) + 'k' } } }
                }
            });
        }
    }

    // ── Tabelas de contas ─────────────────────────────────────────────
    function renderList(tbodyId, totalId, items, filterText) {
        const tb = el(tbodyId);
        let rows = items;
        if (filterText) {
            const q = filterText.toLowerCase();
            rows = rows.filter(r => (r.contact_name||'').toLowerCase().includes(q) || (r.description||'').toLowerCase().includes(q) || (r.cost_center||'').toLowerCase().includes(q));
        }
        rows = rows.slice().sort((a,b) => (parseDate(a.due_date)||0) - (parseDate(b.due_date)||0));
        const openTotal = rows.filter(r => r.status !== 'paid').reduce((a,x) => a + (Number(x.value)||0), 0);
        if (totalId) el(totalId).textContent = fmtMoneyFull(openTotal);
        if (!rows.length) { tb.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Nenhum registro encontrado.</td></tr>'; return; }
        tb.innerHTML = rows.map(r => {
            const d = parseDate(r.due_date);
            const dateStr = d ? d.toLocaleDateString('pt-BR') + ' <span class="small text-muted">' + WEEKDAYS[d.getDay()].slice(0,3) + '</span>' : '—';
            return '<tr><td>' + dateStr + '</td><td>' + esc(r.contact_name) + '</td><td>' + esc(r.description) + '</td><td>' + esc(r.cost_center) + '</td><td class="text-end fw-semibold">' + fmtMoneyFull(r.value) + '</td><td>' + statusBadge(r.status) + '</td></tr>';
        }).join('');
    }

    // Recalcula TUDO conforme os filtros globais (cards, matriz, gráficos, tabelas)
    function refresh() {
        const fpay = filteredPayables(), frec = filteredReceivables();
        renderKpis(fpay, frec);
        renderFlow();
        renderList('tblPayables', 'totalPayables', fpay, el('filterPayables').value);
        renderList('tblReceivables', 'totalReceivables', frec, el('filterReceivables').value);
    }

    function renderAll() {
        setDefaultDates();
        populateFilterOptions();
        refresh();
    }

    function setDefaultDates(force) {
        if (!force && el('flowStart').value && el('flowEnd').value) return;
        const today = new Date(); today.setHours(0,0,0,0);
        const end = new Date(today); end.setDate(end.getDate() + 30);
        el('flowStart').value = toKey(today);
        el('flowEnd').value = toKey(end);
    }

    function applyData(data) {
        state.payables = data.payables || [];
        state.receivables = data.receivables || [];
        state.accounts = data.accounts || [];
        state.totals = data.totals || {};
        el('emptyState').classList.add('d-none');
        el('dashboard').classList.remove('d-none');
        renderAll();
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
        } catch (e) {
            el('emptyState').classList.remove('d-none');
        }
    }

    async function doSync() {
        const btn = el('btnSync');
        btn.disabled = true;
        el('syncStatus').classList.remove('d-none');
        el('syncStatus').classList.add('d-flex');
        el('syncError').classList.add('d-none');
        try {
            const res = await fetch('/admin/finance/sync', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const json = await res.json();
            if (json.ok) {
                applyData(json.data);
                if (json.synced_at) el('lastSyncLabel').textContent = 'Última atualização: ' + new Date(json.synced_at.replace(' ','T')).toLocaleString('pt-BR');
                if (json.partial_errors && json.partial_errors.length) showError('Atualizado com avisos: ' + json.partial_errors.join(' · '));
            } else {
                showError(json.error || 'Não foi possível atualizar. Mantendo a última versão.');
            }
        } catch (e) {
            showError('Falha de conexão ao atualizar. Mantendo a última versão.');
        } finally {
            el('syncStatus').classList.add('d-none');
            el('syncStatus').classList.remove('d-flex');
            btn.disabled = false;
        }
    }

    function applyPreset(btn) {
        const today = new Date(); today.setHours(0,0,0,0);
        let start = new Date(today), end = new Date(today);
        if (btn.dataset.days) {
            end.setDate(end.getDate() + parseInt(btn.dataset.days, 10));
        } else if (btn.dataset.mode === 'this-month') {
            start = new Date(today.getFullYear(), today.getMonth(), 1);
            end = new Date(today.getFullYear(), today.getMonth()+1, 0);
        } else if (btn.dataset.mode === 'year') {
            start = new Date(today.getFullYear(), 0, 1);
            end = new Date(today.getFullYear(), 11, 31);
        }
        el('flowStart').value = toKey(start);
        el('flowEnd').value = toKey(end);
        refresh();
    }

    document.addEventListener('DOMContentLoaded', function () {
        el('btnSync').addEventListener('click', doSync);

        // Qualquer filtro global recalcula tudo (cards, matriz, gráficos, tabelas)
        ['flowGrouping','flowStart','flowEnd','filterCostCenter','filterContact','filterStatus']
            .forEach(id => el(id).addEventListener('change', refresh));

        // Busca textual dentro de cada tabela
        el('filterPayables').addEventListener('input', () => renderList('tblPayables','totalPayables', filteredPayables(), el('filterPayables').value));
        el('filterReceivables').addEventListener('input', () => renderList('tblReceivables','totalReceivables', filteredReceivables(), el('filterReceivables').value));

        // Presets de período
        document.querySelectorAll('.preset').forEach(b => b.addEventListener('click', () => applyPreset(b)));

        // Limpar filtros
        el('btnClearFilters').addEventListener('click', function () {
            el('filterCostCenter').value = '';
            el('filterContact').value = '';
            el('filterStatus').value = '';
            el('filterPayables').value = '';
            el('filterReceivables').value = '';
            el('flowGrouping').value = 'day';
            setDefaultDates(true);
            refresh();
        });

        loadData();
    });
})();
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
