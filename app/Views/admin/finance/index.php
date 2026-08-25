<?php $pageTitle = 'Financeiro'; $currentPage = 'finance'; ?>
<?php ob_start(); ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h5 class="mb-0"><i class="bi bi-cash-coin"></i> Financeiro</h5>
        <small class="text-muted">Painel de fluxo de caixa — apenas leitura dos dados do Nibo.</small>
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
            <i class="bi bi-arrow-repeat"></i> Sincronizar dados
        </button>
    </div>
</div>

<?php if (!$hasToken): ?>
<div class="alert alert-warning d-flex align-items-center gap-2">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <div>O token do Nibo ainda não foi configurado. Configure em <a href="/admin/dev/nibo">Desenvolvimento → API Nibo</a> para atualizar os dados.</div>
</div>
<?php endif; ?>

<!-- Barra de status da sincronização -->
<div id="syncStatus" class="alert alert-info d-none align-items-center gap-2" role="alert">
    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
    <span id="syncStatusText">Sincronizando com o Nibo…</span>
</div>
<div id="syncError" class="alert alert-warning d-none" role="alert"></div>

<!-- Estado vazio -->
<div id="emptyState" class="text-center py-5 d-none">
    <i class="bi bi-cloud-arrow-down display-4 text-muted"></i>
    <p class="mt-3 mb-1">Nenhum dado carregado ainda.</p>
    <p class="text-muted small">Clique em <strong>Sincronizar dados</strong> para buscar as informações do Nibo.</p>
</div>

<!-- Conteúdo do dashboard -->
<div id="dashboard" class="d-none">

    <!-- KPIs -->
    <div class="row g-3 mb-3" id="kpiRow">
        <div class="col-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small text-uppercase" style="letter-spacing:.5px;">Saldo em contas</div>
                    <div class="fs-4 fw-bold" id="kpiBalance">—</div>
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
            <div class="card h-100 border-0 shadow-sm border-start border-4 border-success">
                <div class="card-body">
                    <div class="text-muted small text-uppercase" style="letter-spacing:.5px;">A receber (em aberto)</div>
                    <div class="fs-4 fw-bold text-success" id="kpiReceivable">—</div>
                    <div class="small text-muted"><span id="kpiReceivableCount">0</span> contas</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100 border-0 shadow-sm border-start border-4 border-warning">
                <div class="card-body">
                    <div class="text-muted small text-uppercase" style="letter-spacing:.5px;">Vencidas (a pagar)</div>
                    <div class="fs-4 fw-bold text-warning" id="kpiOverdue">—</div>
                    <div class="small text-muted"><span id="kpiOverdueCount">0</span> contas</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Abas -->
    <ul class="nav nav-tabs mb-3" id="financeTabs" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button">Visão Geral</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-weekly" type="button">Semanal</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-monthly" type="button">Mensal</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-yearly" type="button">Anual</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-payables" type="button">Contas a Pagar</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-receivables" type="button">Contas a Receber</button></li>
    </ul>

    <div class="tab-content">
        <!-- Visão Geral -->
        <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
            <div class="row g-3">
                <div class="col-12 col-lg-7">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white"><i class="bi bi-graph-up-arrow"></i> Entradas e saídas — próximos meses</div>
                        <div class="card-body"><canvas id="chartOverview" height="130"></canvas></div>
                    </div>
                </div>
                <div class="col-12 col-lg-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white"><i class="bi bi-pie-chart"></i> Situação das contas a pagar</div>
                        <div class="card-body d-flex align-items-center justify-content-center"><canvas id="chartStatus" height="200"></canvas></div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white"><i class="bi bi-calendar-event"></i> Próximos vencimentos (15 dias)</div>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 align-middle">
                                <thead class="table-light"><tr><th>Data</th><th>Tipo</th><th>Descrição</th><th>Contato</th><th class="text-end">Valor</th><th>Situação</th></tr></thead>
                                <tbody id="tblUpcoming"><tr><td colspan="6" class="text-center text-muted py-3">—</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Semanal -->
        <div class="tab-pane fade" id="tab-weekly" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><i class="bi bi-bar-chart"></i> Fluxo por semana (próximas 8 semanas)</div>
                <div class="card-body"><canvas id="chartWeekly" height="110"></canvas></div>
            </div>
            <div class="card border-0 shadow-sm mt-3">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light"><tr><th>Semana</th><th class="text-end text-success">Entradas</th><th class="text-end text-danger">Saídas</th><th class="text-end">Saldo</th></tr></thead>
                        <tbody id="tblWeekly"><tr><td colspan="4" class="text-center text-muted py-3">—</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Mensal -->
        <div class="tab-pane fade" id="tab-monthly" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><i class="bi bi-bar-chart"></i> Fluxo por mês</div>
                <div class="card-body"><canvas id="chartMonthly" height="110"></canvas></div>
            </div>
            <div class="card border-0 shadow-sm mt-3">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light"><tr><th>Mês</th><th class="text-end text-success">Entradas</th><th class="text-end text-danger">Saídas</th><th class="text-end">Saldo</th></tr></thead>
                        <tbody id="tblMonthly"><tr><td colspan="4" class="text-center text-muted py-3">—</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Anual -->
        <div class="tab-pane fade" id="tab-yearly" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><i class="bi bi-bar-chart"></i> Fluxo por ano</div>
                <div class="card-body"><canvas id="chartYearly" height="110"></canvas></div>
            </div>
            <div class="card border-0 shadow-sm mt-3">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light"><tr><th>Ano</th><th class="text-end text-success">Entradas</th><th class="text-end text-danger">Saídas</th><th class="text-end">Saldo</th></tr></thead>
                        <tbody id="tblYearly"><tr><td colspan="4" class="text-center text-muted py-3">—</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Contas a Pagar -->
        <div class="tab-pane fade" id="tab-payables" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span><i class="bi bi-arrow-down-circle text-danger"></i> Contas a Pagar</span>
                    <div class="d-flex gap-2">
                        <input type="search" id="filterPayables" class="form-control form-control-sm" placeholder="Buscar fornecedor / descrição" style="max-width:220px;">
                        <select id="statusPayables" class="form-select form-select-sm" style="max-width:150px;">
                            <option value="">Todas</option>
                            <option value="open">Em aberto</option>
                            <option value="overdue">Vencidas</option>
                            <option value="paid">Pagas</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light"><tr><th>Vencimento</th><th>Fornecedor</th><th>Descrição</th><th>Centro de custo</th><th class="text-end">Valor</th><th>Situação</th></tr></thead>
                        <tbody id="tblPayables"><tr><td colspan="6" class="text-center text-muted py-3">—</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Contas a Receber -->
        <div class="tab-pane fade" id="tab-receivables" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span><i class="bi bi-arrow-up-circle text-success"></i> Contas a Receber</span>
                    <div class="d-flex gap-2">
                        <input type="search" id="filterReceivables" class="form-control form-control-sm" placeholder="Buscar cliente / descrição" style="max-width:220px;">
                        <select id="statusReceivables" class="form-select form-select-sm" style="max-width:150px;">
                            <option value="">Todas</option>
                            <option value="open">Em aberto</option>
                            <option value="overdue">Vencidas</option>
                            <option value="paid">Recebidas</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light"><tr><th>Vencimento</th><th>Cliente</th><th>Descrição</th><th>Centro de custo</th><th class="text-end">Valor</th><th>Situação</th></tr></thead>
                        <tbody id="tblReceivables"><tr><td colspan="6" class="text-center text-muted py-3">—</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    const el = (id) => document.getElementById(id);
    const charts = {};
    let state = { payables: [], receivables: [], accounts: [], totals: {} };

    const WEEKDAYS = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'];

    function fmtMoney(v) {
        v = Number(v) || 0;
        return v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }
    function parseDate(s) {
        if (!s) return null;
        const d = new Date(s.length <= 10 ? s + 'T00:00:00' : s);
        return isNaN(d.getTime()) ? null : d;
    }
    function fmtDate(s) {
        const d = parseDate(s);
        if (!d) return '—';
        return WEEKDAYS[d.getDay()].slice(0,3) + ', ' + d.toLocaleDateString('pt-BR');
    }
    function statusBadge(st) {
        if (st === 'paid') return '<span class="badge bg-success">Pago</span>';
        if (st === 'overdue') return '<span class="badge bg-danger">Vencida</span>';
        return '<span class="badge bg-primary">Em aberto</span>';
    }

    // ── Renderização dos KPIs ────────────────────────────────────────
    function renderKpis() {
        const openPay = state.payables.filter(p => p.status !== 'paid');
        const openRec = state.receivables.filter(r => r.status !== 'paid');
        const overdue = state.payables.filter(p => p.status === 'overdue');

        const sum = (arr) => arr.reduce((a, x) => a + (Number(x.value) || 0), 0);

        el('kpiBalance').textContent = fmtMoney(state.totals.balance || 0);
        el('kpiPayable').textContent = fmtMoney(sum(openPay));
        el('kpiPayableCount').textContent = openPay.length;
        el('kpiReceivable').textContent = fmtMoney(sum(openRec));
        el('kpiReceivableCount').textContent = openRec.length;
        el('kpiOverdue').textContent = fmtMoney(sum(overdue));
        el('kpiOverdueCount').textContent = overdue.length;
    }

    // ── Agrupamento por período ──────────────────────────────────────
    function weekKey(d) {
        const t = new Date(d); t.setHours(0,0,0,0);
        const day = (t.getDay() + 6) % 7; // segunda = 0
        t.setDate(t.getDate() - day);
        return t;
    }
    function groupFlow(mode) {
        // retorna [{label, in, out, key}] ordenado
        const map = {};
        const add = (arr, field) => {
            arr.forEach(x => {
                const d = parseDate(x.due_date);
                if (!d) return;
                let key, label;
                if (mode === 'week') {
                    const wk = weekKey(d);
                    key = wk.toISOString().slice(0,10);
                    label = 'Sem. ' + wk.toLocaleDateString('pt-BR', { day:'2-digit', month:'2-digit' });
                } else if (mode === 'month') {
                    key = d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0');
                    label = d.toLocaleDateString('pt-BR', { month:'short', year:'numeric' });
                } else {
                    key = String(d.getFullYear());
                    label = key;
                }
                if (!map[key]) map[key] = { key, label, in:0, out:0 };
                map[key][field] += Number(x.value) || 0;
            });
        };
        add(state.receivables, 'in');
        add(state.payables, 'out');
        return Object.values(map).sort((a,b) => a.key < b.key ? -1 : 1);
    }

    function makeBarChart(canvasId, rows) {
        const ctx = el(canvasId);
        if (!ctx) return;
        if (charts[canvasId]) charts[canvasId].destroy();
        charts[canvasId] = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: rows.map(r => r.label),
                datasets: [
                    { label: 'Entradas', data: rows.map(r => r.in), backgroundColor: 'rgba(25,135,84,.75)' },
                    { label: 'Saídas', data: rows.map(r => r.out), backgroundColor: 'rgba(220,53,69,.75)' },
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: true,
                plugins: { tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + fmtMoney(c.raw) } } },
                scales: { y: { ticks: { callback: (v) => 'R$ ' + (v/1000).toFixed(0) + 'k' } } }
            }
        });
    }

    function renderFlowTable(tbodyId, rows) {
        const tb = el(tbodyId);
        if (!rows.length) { tb.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Sem dados no período.</td></tr>'; return; }
        tb.innerHTML = rows.map(r => {
            const bal = r.in - r.out;
            const cls = bal >= 0 ? 'text-success' : 'text-danger';
            return `<tr><td>${r.label}</td><td class="text-end text-success">${fmtMoney(r.in)}</td><td class="text-end text-danger">${fmtMoney(r.out)}</td><td class="text-end ${cls} fw-semibold">${fmtMoney(bal)}</td></tr>`;
        }).join('');
    }

    // ── Visão geral ──────────────────────────────────────────────────
    function renderOverview() {
        const months = groupFlow('month').slice(0, 6);
        const ctx = el('chartOverview');
        if (ctx) {
            if (charts.chartOverview) charts.chartOverview.destroy();
            charts.chartOverview = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months.map(m => m.label),
                    datasets: [
                        { label: 'Entradas', data: months.map(m => m.in), borderColor: 'rgba(25,135,84,1)', backgroundColor: 'rgba(25,135,84,.1)', fill: true, tension: .3 },
                        { label: 'Saídas', data: months.map(m => m.out), borderColor: 'rgba(220,53,69,1)', backgroundColor: 'rgba(220,53,69,.1)', fill: true, tension: .3 },
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: true,
                    plugins: { tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + fmtMoney(c.raw) } } },
                    scales: { y: { ticks: { callback: (v) => 'R$ ' + (v/1000).toFixed(0) + 'k' } } }
                }
            });
        }

        // Pizza de situação (a pagar)
        const open = state.payables.filter(p => p.status === 'open').length;
        const overdue = state.payables.filter(p => p.status === 'overdue').length;
        const paid = state.payables.filter(p => p.status === 'paid').length;
        const ctx2 = el('chartStatus');
        if (ctx2) {
            if (charts.chartStatus) charts.chartStatus.destroy();
            charts.chartStatus = new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['Em aberto', 'Vencidas', 'Pagas'],
                    datasets: [{ data: [open, overdue, paid], backgroundColor: ['#0d6efd', '#dc3545', '#198754'] }]
                },
                options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom' } } }
            });
        }

        // Próximos vencimentos (15 dias)
        const now = new Date(); now.setHours(0,0,0,0);
        const limit = new Date(now); limit.setDate(limit.getDate() + 15);
        const upcoming = [...state.payables, ...state.receivables]
            .filter(x => { const d = parseDate(x.due_date); return d && d >= now && d <= limit && x.status !== 'paid'; })
            .sort((a,b) => parseDate(a.due_date) - parseDate(b.due_date))
            .slice(0, 20);
        const tb = el('tblUpcoming');
        if (!upcoming.length) {
            tb.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Nenhum vencimento nos próximos 15 dias.</td></tr>';
        } else {
            tb.innerHTML = upcoming.map(x => {
                const tipo = x.type === 'payable'
                    ? '<span class="badge bg-danger-subtle text-danger">Saída</span>'
                    : '<span class="badge bg-success-subtle text-success">Entrada</span>';
                return `<tr><td>${fmtDate(x.due_date)}</td><td>${tipo}</td><td>${esc(x.description)}</td><td>${esc(x.contact_name)}</td><td class="text-end">${fmtMoney(x.value)}</td><td>${statusBadge(x.status)}</td></tr>`;
            }).join('');
        }
    }

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
    }

    // ── Tabelas de contas ────────────────────────────────────────────
    function renderList(tbodyId, items, filterText, statusFilter) {
        const tb = el(tbodyId);
        let rows = items;
        if (statusFilter) rows = rows.filter(r => r.status === statusFilter);
        if (filterText) {
            const q = filterText.toLowerCase();
            rows = rows.filter(r => (r.contact_name || '').toLowerCase().includes(q) || (r.description || '').toLowerCase().includes(q));
        }
        rows = rows.slice().sort((a,b) => parseDate(a.due_date) - parseDate(b.due_date));
        if (!rows.length) { tb.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Nenhum registro encontrado.</td></tr>'; return; }
        tb.innerHTML = rows.map(r =>
            `<tr><td>${fmtDate(r.due_date)}</td><td>${esc(r.contact_name)}</td><td>${esc(r.description)}</td><td>${esc(r.cost_center)}</td><td class="text-end fw-semibold">${fmtMoney(r.value)}</td><td>${statusBadge(r.status)}</td></tr>`
        ).join('');
    }

    function renderAll() {
        renderKpis();
        renderOverview();
        renderFlowTable('tblWeekly', groupFlow('week').slice(0, 8));
        makeBarChart('chartWeekly', groupFlow('week').slice(0, 8));
        renderFlowTable('tblMonthly', groupFlow('month'));
        makeBarChart('chartMonthly', groupFlow('month'));
        renderFlowTable('tblYearly', groupFlow('year'));
        makeBarChart('chartYearly', groupFlow('year'));
        renderList('tblPayables', state.payables, el('filterPayables').value, el('statusPayables').value);
        renderList('tblReceivables', state.receivables, el('filterReceivables').value, el('statusReceivables').value);
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

    // ── Carregamento / sincronização ─────────────────────────────────
    function showError(msg) {
        const box = el('syncError');
        box.textContent = msg;
        box.classList.remove('d-none');
        setTimeout(() => box.classList.add('d-none'), 8000);
    }

    async function loadData() {
        try {
            const res = await fetch('/admin/finance/data');
            const json = await res.json();
            if (json.ok && json.has_data) {
                applyData(json.data);
            } else {
                el('emptyState').classList.remove('d-none');
            }
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
                if (json.synced_at) {
                    el('lastSyncLabel').textContent = 'Última atualização: ' + new Date(json.synced_at.replace(' ', 'T')).toLocaleString('pt-BR');
                }
                if (json.partial_errors && json.partial_errors.length) {
                    showError('Sincronizado com avisos: ' + json.partial_errors.join(' · '));
                }
            } else {
                showError(json.error || 'Não foi possível sincronizar. Mantendo a última versão.');
            }
        } catch (e) {
            showError('Falha de conexão ao sincronizar. Mantendo a última versão.');
        } finally {
            el('syncStatus').classList.add('d-none');
            el('syncStatus').classList.remove('d-flex');
            btn.disabled = false;
        }
    }

    // ── Eventos ──────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        el('btnSync').addEventListener('click', doSync);
        ['filterPayables','statusPayables'].forEach(id => el(id).addEventListener('input', () => renderList('tblPayables', state.payables, el('filterPayables').value, el('statusPayables').value)));
        ['filterReceivables','statusReceivables'].forEach(id => el(id).addEventListener('input', () => renderList('tblReceivables', state.receivables, el('filterReceivables').value, el('statusReceivables').value)));
        loadData();
    });
})();
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
