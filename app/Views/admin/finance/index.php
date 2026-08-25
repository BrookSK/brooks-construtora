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

    <!-- BARRA ÚNICA DE FILTROS (vale para todas as abas) -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label small mb-1">Período</label>
                    <select id="filterPeriod" class="form-select form-select-sm">
                        <option value="7">Próximos 7 dias</option>
                        <option value="15">Próximos 15 dias</option>
                        <option value="20">Próximos 20 dias</option>
                        <option value="30">Próximos 30 dias</option>
                        <option value="60">Próximos 60 dias</option>
                        <option value="90">Próximos 90 dias</option>
                        <option value="this-month">Este mês</option>
                        <option value="this-year" selected>Este ano</option>
                        <option value="all">Tudo (2024 até hoje)</option>
                        <option value="custom">Datas personalizadas</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-2 filter-custom-date d-none">
                    <label class="form-label small mb-1">De</label>
                    <input type="date" id="filterStart" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3 col-xl-2 filter-custom-date d-none">
                    <label class="form-label small mb-1">Até</label>
                    <input type="date" id="filterEnd" class="form-control form-control-sm">
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label small mb-1">Fornecedor / Cliente</label>
                    <select id="filterContact" class="form-select form-select-sm"><option value="">Todos</option></select>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label small mb-1">Centro de custo</label>
                    <select id="filterCostCenter" class="form-select form-select-sm"><option value="">Todos</option></select>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label small mb-1">Situação</label>
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        <option value="open">Em aberto</option>
                        <option value="overdue">Vencidas</option>
                        <option value="paid">Já pagas/recebidas</option>
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <button id="btnClearFilters" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-x-lg"></i> Limpar filtros</button>
                </div>
            </div>
            <div class="small text-muted mt-2" id="periodRangeLabel"></div>
        </div>
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


    <!-- Abas -->
    <ul class="nav nav-tabs mb-3" id="financeTabs" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-resumo" type="button"><i class="bi bi-grid-1x2"></i> Resumo</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-semanal" type="button"><i class="bi bi-calendar-week"></i> Semanal</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-mensal" type="button"><i class="bi bi-calendar-month"></i> Mensal</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-anual" type="button"><i class="bi bi-calendar3"></i> Anual</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-contas" type="button"><i class="bi bi-list-columns-reverse"></i> Contas a Pagar e Receber</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-detalhe" type="button"><i class="bi bi-table"></i> Detalhado</button></li>
    </ul>

    <div class="tab-content">

        <!-- ═══ RESUMO ═══ -->
        <div class="tab-pane fade show active" id="tab-resumo" role="tabpanel">
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

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white"><i class="bi bi-bar-chart"></i> Entradas e saídas ao longo do período</div>
                <div class="card-body"><canvas id="chartInOut" height="90"></canvas></div>
            </div>

            <div class="row g-3">
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
        </div>

        <!-- ═══ SEMANAL ═══ -->
        <div class="tab-pane fade" id="tab-semanal" role="tabpanel">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white"><i class="bi bi-bar-chart"></i> Entradas x Saídas por semana (próximas 12 semanas)</div>
                <div class="card-body"><canvas id="chartWeekly" height="90"></canvas></div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light"><tr><th>Semana</th><th class="text-end text-success">Entradas</th><th class="text-end text-danger">Saídas</th><th class="text-end">Saldo do período</th></tr></thead>
                        <tbody id="tblWeekly"><tr><td colspan="4" class="text-center text-muted py-3">—</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ═══ MENSAL ═══ -->
        <div class="tab-pane fade" id="tab-mensal" role="tabpanel">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white"><i class="bi bi-bar-chart"></i> Entradas x Saídas por mês (próximos 12 meses)</div>
                <div class="card-body"><canvas id="chartMonthly" height="90"></canvas></div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light"><tr><th>Mês</th><th class="text-end text-success">Entradas</th><th class="text-end text-danger">Saídas</th><th class="text-end">Saldo do período</th></tr></thead>
                        <tbody id="tblMonthly"><tr><td colspan="4" class="text-center text-muted py-3">—</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ═══ ANUAL ═══ -->
        <div class="tab-pane fade" id="tab-anual" role="tabpanel">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white"><i class="bi bi-bar-chart"></i> Entradas x Saídas por ano</div>
                <div class="card-body"><canvas id="chartYearly" height="90"></canvas></div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light"><tr><th>Ano</th><th class="text-end text-success">Entradas</th><th class="text-end text-danger">Saídas</th><th class="text-end">Saldo do período</th></tr></thead>
                        <tbody id="tblYearly"><tr><td colspan="4" class="text-center text-muted py-3">—</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ═══ CONTAS A PAGAR E RECEBER ═══ -->
        <div class="tab-pane fade" id="tab-contas" role="tabpanel">

            <!-- Cards de totais -->
            <div class="row g-3 mb-3">
                <div class="col-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100 border-start border-4 border-danger">
                        <div class="card-body">
                            <div class="small text-uppercase text-danger fw-semibold" style="letter-spacing:.5px;">A pagar</div>
                            <div class="fs-4 fw-bold text-danger" id="ctaPayTotal">—</div>
                            <div class="small text-muted"><span id="ctaPayCount">0</span> contas</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
                        <div class="card-body">
                            <div class="small text-uppercase text-success fw-semibold" style="letter-spacing:.5px;">A receber</div>
                            <div class="fs-4 fw-bold text-success" id="ctaRecTotal">—</div>
                            <div class="small text-muted"><span id="ctaRecCount">0</span> contas</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
                        <div class="card-body">
                            <div class="small text-uppercase text-warning fw-semibold" style="letter-spacing:.5px;">Vencidas (a pagar)</div>
                            <div class="fs-4 fw-bold text-warning" id="ctaOverdueTotal">—</div>
                            <div class="small text-muted"><span id="ctaOverdueCount">0</span> contas</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
                        <div class="card-body">
                            <div class="small text-uppercase text-primary fw-semibold" style="letter-spacing:.5px;">Diferença (recebe − paga)</div>
                            <div class="fs-4 fw-bold" id="ctaDiff">—</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumos por centro de custo e por fornecedor/cliente -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white fw-semibold"><i class="bi bi-diagram-3"></i> Total por centro de custo</div>
                        <div class="table-responsive" style="max-height:320px;">
                            <table class="table table-sm mb-0 align-middle">
                                <thead class="table-light" style="position:sticky;top:0;"><tr><th>Centro de custo</th><th class="text-end text-success">Recebe</th><th class="text-end text-danger">Paga</th></tr></thead>
                                <tbody id="byCostCenter"><tr><td colspan="3" class="text-center text-muted py-3">—</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white fw-semibold"><i class="bi bi-people"></i> Total por fornecedor / cliente</div>
                        <div class="table-responsive" style="max-height:320px;">
                            <table class="table table-sm mb-0 align-middle">
                                <thead class="table-light" style="position:sticky;top:0;"><tr><th>Fornecedor / Cliente</th><th class="text-end text-success">Recebe</th><th class="text-end text-danger">Paga</th></tr></thead>
                                <tbody id="byContact"><tr><td colspan="3" class="text-center text-muted py-3">—</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Listagem completa: A PAGAR -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="fw-semibold text-danger"><i class="bi bi-arrow-up-circle"></i> Contas a Pagar (detalhado)</span>
                    <div class="d-flex align-items-center gap-2">
                        <input type="search" id="searchPay" class="form-control form-control-sm" placeholder="Buscar nesta lista…" style="max-width:220px;">
                        <span class="badge bg-danger" id="ctaPayListTotal">R$ 0,00</span>
                    </div>
                </div>
                <div class="table-responsive" style="max-height:500px;">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light" style="position:sticky;top:0;">
                            <tr><th>Vencimento</th><th>Fornecedor</th><th>Descrição</th><th>Centro de custo</th><th>Categoria</th><th class="text-end">Valor</th><th>Situação</th></tr>
                        </thead>
                        <tbody id="fullPayList"><tr><td colspan="7" class="text-center text-muted py-3">—</td></tr></tbody>
                    </table>
                </div>
            </div>

            <!-- Listagem completa: A RECEBER -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="fw-semibold text-success"><i class="bi bi-arrow-down-circle"></i> Contas a Receber (detalhado)</span>
                    <div class="d-flex align-items-center gap-2">
                        <input type="search" id="searchRec" class="form-control form-control-sm" placeholder="Buscar nesta lista…" style="max-width:220px;">
                        <span class="badge bg-success" id="ctaRecListTotal">R$ 0,00</span>
                    </div>
                </div>
                <div class="table-responsive" style="max-height:500px;">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light" style="position:sticky;top:0;">
                            <tr><th>Vencimento</th><th>Cliente</th><th>Descrição</th><th>Centro de custo</th><th>Categoria</th><th class="text-end">Valor</th><th>Situação</th></tr>
                        </thead>
                        <tbody id="fullRecList"><tr><td colspan="7" class="text-center text-muted py-3">—</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ═══ DETALHADO (matriz) ═══ -->
        <div class="tab-pane fade" id="tab-detalhe" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span><i class="bi bi-table"></i> Previsão de fluxo de caixa por período</span>
                    <select id="flowGrouping" class="form-select form-select-sm" style="width:auto;">
                        <option value="day" selected>Por dia</option>
                        <option value="week">Por semana</option>
                        <option value="month">Por mês</option>
                    </select>
                </div>
                <div class="table-responsive" style="max-height:65vh;">
                    <table class="table table-sm table-bordered mb-0 finance-flow" style="font-size:.8rem; white-space:nowrap;">
                        <thead id="flowHead"></thead>
                        <tbody id="flowBody"></tbody>
                    </table>
                </div>
                <div class="card-footer bg-white small text-muted">
                    O <strong>saldo final</strong> de cada período vira o saldo inicial do próximo. Entradas somam, saídas reduzem.
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
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    const el = (id) => document.getElementById(id);
    const charts = {};
    let state = { payables: [], receivables: [], accounts: [], totals: {}, filters: {} };

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

    // ── Janela de datas do período selecionado (barra única) ──────────
    function periodRange() {
        const today = new Date(); today.setHours(0,0,0,0);
        const val = el('filterPeriod').value;
        let start = new Date(today), end = new Date(today);
        if (val === 'all') {
            // Toda a base sincronizada
            return { start: new Date(2000,0,1), end: new Date(2100,0,1) };
        } else if (val === 'this-month') {
            start = new Date(today.getFullYear(), today.getMonth(), 1);
            end = new Date(today.getFullYear(), today.getMonth()+1, 0);
        } else if (val === 'this-year') {
            start = new Date(today.getFullYear(), 0, 1);
            end = new Date(today.getFullYear(), 11, 31);
        } else if (val === 'custom') {
            const s = parseDate(el('filterStart').value), e = parseDate(el('filterEnd').value);
            start = s || new Date(2000,0,1);
            end = e || new Date(2100,0,1);
        } else {
            end.setDate(end.getDate() + parseInt(val, 10));
        }
        return { start, end };
    }

    // ── Filtros (barra única) ─────────────────────────────────────────
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

    // Filtro ÚNICO: fornecedor/centro/situação + janela de datas do período.
    // Vale igualmente para TODAS as abas, evitando inconsistências.
    function matchesAll(x) {
        const f = getFilters();
        if (f.status && x.status !== f.status) return false;
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
    function filteredPayables() { return state.payables.filter(matchesAll); }
    function filteredReceivables() { return state.receivables.filter(matchesAll); }
    // Aliases usados pelas abas (mesma fonte, filtro único)
    const allPayables = filteredPayables;
    const allReceivables = filteredReceivables;
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
        const periodVal = el('filterPeriod').value;
        if (periodVal === 'all') {
            el('periodRangeLabel').innerHTML = '<i class="bi bi-calendar-range"></i> Mostrando toda a base sincronizada';
        } else {
            const range = periodRange();
            el('periodRangeLabel').innerHTML = '<i class="bi bi-calendar-range"></i> Recorte atual: ' + range.start.toLocaleDateString('pt-BR') + ' até ' + range.end.toLocaleDateString('pt-BR');
        }
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
        let { start, end } = periodRange();
        // Se for "Tudo", ajusta a janela aos dados existentes
        if (start.getFullYear() <= 2000 || end.getFullYear() >= 2100) {
            const dates = [...fpay, ...frec].map(x => parseDate(x.due_date)).filter(Boolean).sort((a,b)=>a-b);
            if (dates.length) { start = dates[0]; end = dates[dates.length-1]; }
        }
        // Escolhe o agrupamento conforme o tamanho da janela
        const spanDays = Math.round((end - start) / 86400000);
        const grouping = spanDays <= 20 ? 'day' : (spanDays <= 120 ? 'week' : 'month');
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

    // ── Visões Semanal / Mensal / Anual ───────────────────────────────
    // Agrupa TODOS os lançamentos filtrados (sem a janela de dias) por período.
    function groupInOut(fpay, frec, grouping) {
        const map = {};
        const ensure = k => { if (!map[k]) map[k] = { in:0, out:0 }; return map[k]; };
        frec.forEach(x => { const d = parseDate(x.due_date); if (!d) return; ensure(periodKeyForDate(d, grouping)).in += Number(x.value)||0; });
        fpay.forEach(x => { const d = parseDate(x.due_date); if (!d) return; ensure(periodKeyForDate(d, grouping)).out += Number(x.value)||0; });
        return Object.keys(map).sort().map(k => ({ key:k, in:map[k].in, out:map[k].out }));
    }
    function keyToLabel(key, grouping) {
        if (grouping === 'week') { const d = parseDate(key); const e = new Date(d); e.setDate(e.getDate()+6); return d.getDate()+'/'+(d.getMonth()+1)+' a '+e.getDate()+'/'+(e.getMonth()+1); }
        if (grouping === 'month') { const [y,m] = key.split('-'); return MONTHS[parseInt(m,10)-1]+'/'+y; }
        return key; // ano
    }
    function renderPeriodView(grouping, canvasId, tblId) {
        // Usa o MESMO recorte (filtros + janela) das demais abas
        const fpay = filteredPayables(), frec = filteredReceivables();
        let rows = groupInOut(fpay, frec, grouping);

        const labels = rows.map(r => keyToLabel(r.key, grouping));
        if (charts[canvasId]) charts[canvasId].destroy();
        if (el(canvasId)) {
            charts[canvasId] = new Chart(el(canvasId), {
                type: 'bar',
                data: { labels, datasets: [
                    { label: 'Entradas', data: rows.map(r=>r.in), backgroundColor: 'rgba(25,135,84,.8)' },
                    { label: 'Saídas', data: rows.map(r=>r.out), backgroundColor: 'rgba(220,53,69,.8)' },
                ]},
                options: {
                    responsive: true, maintainAspectRatio: true,
                    plugins: { tooltip: { callbacks: { label: c => c.dataset.label + ': ' + fmtMoneyFull(c.raw) } } },
                    scales: { y: { ticks: { callback: v => 'R$ ' + (v/1000).toFixed(0) + 'k' } } }
                }
            });
        }
        const tb = el(tblId);
        if (!rows.length) { tb.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Sem dados.</td></tr>'; return; }
        tb.innerHTML = rows.map(r => {
            const bal = r.in - r.out;
            const cls = bal >= 0 ? 'text-success' : 'text-danger';
            return '<tr><td>'+esc(keyToLabel(r.key, grouping))+'</td>'
                + '<td class="text-end text-success">'+fmtMoneyFull(r.in)+'</td>'
                + '<td class="text-end text-danger">'+fmtMoneyFull(r.out)+'</td>'
                + '<td class="text-end fw-semibold '+cls+'">'+fmtMoneyFull(bal)+'</td></tr>';
        }).join('');
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

    // ── Aba Contas a Pagar e Receber ──────────────────────────────────
    function renderContas() {
        const fpay = allPayables(), frec = allReceivables();
        const openPay = fpay.filter(x => x.status !== 'paid');
        const openRec = frec.filter(x => x.status !== 'paid');
        const overdue = fpay.filter(x => x.status === 'overdue');
        const totPay = sum(openPay), totRec = sum(openRec);

        el('ctaPayTotal').textContent = fmtMoneyFull(totPay);
        el('ctaPayCount').textContent = openPay.length;
        el('ctaRecTotal').textContent = fmtMoneyFull(totRec);
        el('ctaRecCount').textContent = openRec.length;
        el('ctaOverdueTotal').textContent = fmtMoneyFull(sum(overdue));
        el('ctaOverdueCount').textContent = overdue.length;
        const diff = totRec - totPay;
        const diffEl = el('ctaDiff');
        diffEl.textContent = fmtMoneyFull(diff);
        diffEl.className = 'fs-4 fw-bold ' + (diff < 0 ? 'text-danger' : 'text-success');

        // Resumo por centro de custo
        renderGroupTable('byCostCenter', fpay, frec, x => (x.cost_center && x.cost_center !== '—') ? x.cost_center : 'Sem centro de custo');
        // Resumo por fornecedor/cliente
        renderGroupTable('byContact', fpay, frec, x => (x.contact_name && x.contact_name !== '—') ? x.contact_name : (x.description || 'Outros'));

        // Listagens completas (com busca textual)
        renderFullList('fullPayList', 'ctaPayListTotal', fpay, el('searchPay').value);
        renderFullList('fullRecList', 'ctaRecListTotal', frec, el('searchRec').value);
    }

    // Agrupa recebe/paga por uma chave (centro de custo ou contato)
    function renderGroupTable(tbodyId, fpay, frec, keyFn) {
        const map = {};
        const ensure = k => { if (!map[k]) map[k] = { in:0, out:0 }; return map[k]; };
        frec.forEach(x => ensure(keyFn(x)).in += Number(x.value)||0);
        fpay.forEach(x => ensure(keyFn(x)).out += Number(x.value)||0);
        const rows = Object.entries(map)
            .map(([name,v]) => ({ name, in:v.in, out:v.out }))
            .sort((a,b) => (b.in+b.out) - (a.in+a.out));
        const tb = el(tbodyId);
        if (!rows.length) { tb.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-3">Sem dados.</td></tr>'; return; }
        tb.innerHTML = rows.map(r =>
            '<tr><td class="text-truncate" style="max-width:280px;">' + esc(r.name) + '</td>'
            + '<td class="text-end text-success">' + (r.in ? fmtMoneyFull(r.in) : '—') + '</td>'
            + '<td class="text-end text-danger">' + (r.out ? fmtMoneyFull(r.out) : '—') + '</td></tr>'
        ).join('');
    }

    // Listagem detalhada com todas as colunas
    function renderFullList(tbodyId, totalId, items, searchText) {
        let rows = items.slice();
        if (searchText) {
            const q = searchText.toLowerCase();
            rows = rows.filter(r =>
                (r.contact_name||'').toLowerCase().includes(q) ||
                (r.description||'').toLowerCase().includes(q) ||
                (r.cost_center||'').toLowerCase().includes(q) ||
                (r.category||'').toLowerCase().includes(q));
        }
        rows.sort((a,b) => (parseDate(a.due_date)||0) - (parseDate(b.due_date)||0));
        const openTotal = rows.filter(r => r.status !== 'paid').reduce((a,x)=>a+(Number(x.value)||0),0);
        el(totalId).textContent = fmtMoneyFull(openTotal);
        const tb = el(tbodyId);
        if (!rows.length) { tb.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">Nenhuma conta encontrada.</td></tr>'; return; }
        tb.innerHTML = rows.map(r =>
            '<tr><td>' + dateLabel(r.due_date) + '</td>'
            + '<td>' + esc(r.contact_name) + '</td>'
            + '<td class="text-truncate" style="max-width:240px;">' + esc(r.description) + '</td>'
            + '<td>' + esc(r.cost_center) + '</td>'
            + '<td>' + esc(r.category) + '</td>'
            + '<td class="text-end fw-semibold">' + fmtMoneyFull(r.value) + '</td>'
            + '<td>' + statusBadge(r.status) + '</td></tr>'
        ).join('');
    }

    // ── Matriz detalhada ──────────────────────────────────────────────
    function renderFlow() {
        const fpay = filteredPayables(), frec = filteredReceivables();
        const grouping = el('flowGrouping').value;
        // Usa a MESMA janela do período selecionado na barra de filtros
        let { start, end } = periodRange();
        // Se for "Tudo" (janela gigante), limita a matriz aos dados existentes
        if (start.getFullYear() <= 2000 || end.getFullYear() >= 2100) {
            const dates = [...fpay, ...frec].map(x => parseDate(x.due_date)).filter(Boolean).sort((a,b)=>a-b);
            if (dates.length) { start = dates[0]; end = dates[dates.length-1]; }
            else { start = new Date(); end = new Date(); }
        }
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
        // Resumo (respeita a janela do botão de período)
        const fpay = filteredPayables(), frec = filteredReceivables();
        renderCards(fpay, frec);
        renderSimpleList('payList', 'payListTotal', fpay, 'text-danger');
        renderSimpleList('recList', 'recListTotal', frec, 'text-success');
        renderRanking('topSuppliers', fpay, 'text-danger');
        renderRanking('topCustomers', frec, 'text-success');
        renderChart(fpay, frec);

        // Semanal / Mensal / Anual (mesmo recorte da barra de filtros)
        renderPeriodView('week', 'chartWeekly', 'tblWeekly');
        renderPeriodView('month', 'chartMonthly', 'tblMonthly');
        renderPeriodView('year', 'chartYearly', 'tblYearly');

        // Contas a pagar e receber (respeita filtros, sem janela de dias)
        renderContas();

        // Detalhado (matriz)
        renderFlow();
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
            if (json.debug) console.log('[Finance] debug do sync:', json.debug);
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

        // Mostra/esconde os campos de data personalizada
        function toggleCustomDates() {
            const custom = el('filterPeriod').value === 'custom';
            document.querySelectorAll('.filter-custom-date').forEach(d => d.classList.toggle('d-none', !custom));
        }

        // BARRA ÚNICA: qualquer filtro recalcula TODAS as abas
        ['filterPeriod','filterStart','filterEnd','filterContact','filterCostCenter','filterStatus']
            .forEach(id => el(id).addEventListener('change', function () {
                toggleCustomDates();
                refresh();
            }));

        // Agrupamento da matriz detalhada (só re-renderiza a matriz)
        el('flowGrouping').addEventListener('change', renderFlow);

        // Buscas das listagens de contas (dentro do recorte atual)
        el('searchPay').addEventListener('input', () => renderFullList('fullPayList', 'ctaPayListTotal', filteredPayables(), el('searchPay').value));
        el('searchRec').addEventListener('input', () => renderFullList('fullRecList', 'ctaRecListTotal', filteredReceivables(), el('searchRec').value));

        el('btnClearFilters').addEventListener('click', function () {
            el('filterPeriod').value = 'this-year';
            el('filterStart').value = ''; el('filterEnd').value = '';
            el('filterContact').value = ''; el('filterCostCenter').value = ''; el('filterStatus').value = '';
            toggleCustomDates();
            refresh();
        });

        toggleCustomDates();
        loadData();
    });
})();
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
