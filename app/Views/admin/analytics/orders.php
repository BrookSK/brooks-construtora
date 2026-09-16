<?php
/**
 * Painel analítico de pedidos de compra.
 * Recebe $data (saída de PurchaseOrderReportService::collectData()) e $error.
 */
$pageTitle = 'Relatório de Pedidos';
$currentPage = 'analytics_orders';

/** Helpers de leitura do array estruturado ($data[secao]['rows']). */
$rowsOf = function (array $data, string $section): array {
    return $data[$section]['rows'] ?? [];
};
/** Procura o valor (col 1) a partir do rótulo (col 0) numa seção "Indicador/Valor". */
$valueByLabel = function (array $rows, string $label): string {
    foreach ($rows as $r) {
        if (isset($r[0]) && trim((string)$r[0]) === $label) {
            return (string)($r[1] ?? '');
        }
    }
    return '—';
};
$esc = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$resumo      = $rowsOf($data, '1. Resumo Geral');
$mensal      = $rowsOf($data, '2. Media Mensal');
$cotacao     = $rowsOf($data, '3. Tempo Medio Cotacao');
$aprovacao   = $rowsOf($data, '4. Tempo Medio Aprovacao');
$profis      = $rowsOf($data, '5. Profissionais por Pedidos');
$materiais   = $rowsOf($data, '6. Materiais Mais Consumidos');
$obras       = $rowsOf($data, '7. Resumo por Obra');
$diarios     = $rowsOf($data, '8. Pedidos Diarios');
$matObra     = $rowsOf($data, '9. Material x Custo Obra');
$criticos    = $rowsOf($data, '10. Pedidos Criticos');
$profCrit    = $rowsOf($data, '11. Profissional x Criticos');
$aprovCat    = $rowsOf($data, '12. Aprovados por Categoria');

// Total geral de materiais aprovados com menor cotação (linha "TOTAL GERAL" da seção 12).
// Nesta seção o valor fica na última coluna, então lemos direto da linha.
$kpiAprovCat = '—';
foreach ($aprovCat as $r) {
    if (isset($r[0]) && strpos((string)$r[0], 'TOTAL GERAL') === 0) {
        $kpiAprovCat = (string)($r[count($r) - 1] ?? '—');
        break;
    }
}

// KPIs principais
$kpiTotal      = $valueByLabel($resumo, 'Total de pedidos (todos)');
$kpiValidos    = $valueByLabel($resumo, 'Total de pedidos (excluindo cancelados)');
$kpiValor      = $valueByLabel($resumo, 'Valor total dos itens (R$)');
$kpiMensal     = $valueByLabel($mensal, 'MÉDIA MENSAL');
$kpiCotacao    = $valueByLabel($cotacao, 'Tempo MÉDIO de cotação');
$kpiAprovacao  = $valueByLabel($aprovacao, 'Tempo MÉDIO de aprovação');
$kpiDiaria     = $valueByLabel($diarios, 'MÉDIA DIÁRIA (dias com pedidos)');

// Líder de críticos (primeira linha destacada da seção 11)
$liderCritico = '—';
foreach ($profCrit as $r) {
    if (isset($r[0]) && strpos((string)$r[0], 'MAIS pedidos') !== false) {
        $liderCritico = (string)($r[1] ?? '—');
        break;
    }
}
// Totais de críticos (linhas destacadas da seção 10)
$totalCritico = $valueByLabel($criticos, 'TOTAL Crítico');
$totalUrgente = $valueByLabel($criticos, 'TOTAL Alta/Urgente');

ob_start();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h5 class="mb-0"><i class="bi bi-bar-chart-line"></i> Relatório de Pedidos de Compra</h5>
        <small class="text-muted">Indicadores gerais · atualizados agora (<?= date('d/m/Y H:i') ?>)</small>
    </div>
    <a href="/admin/relatorio-pedidos/download" class="btn btn-success">
        <i class="bi bi-file-earmark-spreadsheet"></i> Baixar Excel
    </a>
</div>

<?php if (!empty($error)): ?>
<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= $esc($error) ?></div>
<?php endif; ?>

<!-- KPIs -->
<div class="row g-3 mb-1">
    <?php
    $cards = [
        ['Total de pedidos', $kpiTotal, 'bi-cart3', 'primary', 'Excluindo cancelados: ' . $kpiValidos],
        ['Média mensal', $kpiMensal, 'bi-calendar3', 'info', 'pedidos/mês'],
        ['Média diária', $kpiDiaria, 'bi-calendar-day', 'info', 'dias com pedidos'],
        ['Tempo médio de cotação', $kpiCotacao, 'bi-hourglass-split', 'warning', ''],
        ['Tempo médio de aprovação', $kpiAprovacao, 'bi-check2-circle', 'success', ''],
        ['Valor total (itens)', 'R$ ' . $kpiValor, 'bi-cash-stack', 'dark', ''],
    ];
    foreach ($cards as $c): ?>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body p-3">
                <div class="d-flex align-items-center gap-2 mb-1 text-<?= $c[3] ?>">
                    <i class="bi <?= $c[2] ?>"></i>
                    <span class="small text-muted"><?= $esc($c[0]) ?></span>
                </div>
                <div class="fs-5 fw-bold"><?= $esc($c[1]) ?></div>
                <?php if ($c[4] !== ''): ?><div class="small text-muted"><?= $esc($c[4]) ?></div><?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Alertas de urgência -->
<div class="row g-3 my-1">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-danger bg-opacity-10 h-100">
            <div class="card-body">
                <div class="text-danger small"><i class="bi bi-exclamation-octagon-fill"></i> Pedidos críticos</div>
                <div class="fs-4 fw-bold text-danger"><?= $esc($totalCritico) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-warning bg-opacity-10 h-100">
            <div class="card-body">
                <div class="text-warning-emphasis small"><i class="bi bi-exclamation-triangle-fill"></i> Pedidos urgentes (alta)</div>
                <div class="fs-4 fw-bold text-warning-emphasis"><?= $esc($totalUrgente) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small"><i class="bi bi-person-fill-exclamation"></i> Quem mais abre crítico/urgente</div>
                <div class="fs-6 fw-bold"><?= $esc($liderCritico) ?></div>
            </div>
        </div>
    </div>
</div>

<?php
/** Renderiza uma tabela genérica a partir de headers + rows. */
$renderTable = function (array $headers, array $rows, string $emptyMsg = 'Sem dados.') use ($esc) {
    if (empty($rows)) {
        echo '<div class="text-muted small py-3 text-center">' . $esc($emptyMsg) . '</div>';
        return;
    }
    echo '<div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">';
    echo '<thead class="table-light"><tr>';
    foreach ($headers as $i => $h) {
        $align = $i === 0 ? '' : ' class="text-end"';
        echo '<th' . $align . '>' . $esc($h) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($rows as $r) {
        // pula linhas totalmente vazias (separadores)
        $joined = trim(implode('', array_map('strval', $r)));
        if ($joined === '') continue;
        echo '<tr>';
        foreach ($r as $i => $cell) {
            $align = $i === 0 ? '' : ' class="text-end"';
            echo '<td' . $align . '>' . $esc($cell) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
};
?>

<!-- Materiais aprovados por categoria (menor preço cotado) -->
<div class="card shadow-sm mb-3">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-tags"></i> Materiais aprovados por categoria · menor preço cotado</span>
        <span class="badge bg-dark fs-6">Total: R$ <?= $esc($kpiAprovCat) ?></span>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-2">
            Considera apenas pedidos <strong>aprovados</strong>. Cada material aparece
            <strong>uma única vez</strong>, com o <strong>menor preço unitário</strong> já
            aprovado entre todos os pedidos. A coluna "Pedido" indica em qual pedido esse
            menor preço foi encontrado. Materiais agrupados pela sua categoria.
        </p>
        <div style="max-height:460px; overflow:auto;">
            <?php $renderTable($data['12. Aprovados por Categoria']['headers'] ?? [], $aprovCat); ?>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Materiais mais consumidos -->
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm h-100">
            <div class="card-header"><i class="bi bi-box-seam"></i> Materiais mais consumidos</div>
            <div class="card-body p-0" style="max-height:420px; overflow:auto;">
                <?php $renderTable($data['6. Materiais Mais Consumidos']['headers'] ?? [], $materiais); ?>
            </div>
        </div>
    </div>

    <!-- Profissionais por pedidos -->
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm h-100">
            <div class="card-header"><i class="bi bi-people"></i> Profissionais com mais pedidos</div>
            <div class="card-body p-0" style="max-height:420px; overflow:auto;">
                <?php $renderTable($data['5. Profissionais por Pedidos']['headers'] ?? [], $profis); ?>
            </div>
        </div>
    </div>

    <!-- Resumo por obra -->
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm h-100">
            <div class="card-header"><i class="bi bi-buildings"></i> Resumo por obra</div>
            <div class="card-body p-0" style="max-height:420px; overflow:auto;">
                <?php $renderTable($data['7. Resumo por Obra']['headers'] ?? [], $obras); ?>
            </div>
        </div>
    </div>

    <!-- Pedidos críticos / urgentes -->
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm h-100">
            <div class="card-header"><i class="bi bi-exclamation-triangle"></i> Pedidos críticos / urgentes</div>
            <div class="card-body p-0" style="max-height:420px; overflow:auto;">
                <?php $renderTable($data['10. Pedidos Criticos']['headers'] ?? [], $criticos); ?>
            </div>
        </div>
    </div>

    <!-- Profissional x críticos -->
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm h-100">
            <div class="card-header"><i class="bi bi-person-fill-exclamation"></i> Profissionais x pedidos críticos/urgentes</div>
            <div class="card-body p-0" style="max-height:420px; overflow:auto;">
                <?php $renderTable($data['11. Profissional x Criticos']['headers'] ?? [], $profCrit); ?>
            </div>
        </div>
    </div>

    <!-- Material x custo da obra -->
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm h-100">
            <div class="card-header"><i class="bi bi-pie-chart"></i> Participação de cada material no custo da obra</div>
            <div class="card-body p-0" style="max-height:420px; overflow:auto;">
                <?php $renderTable($data['9. Material x Custo Obra']['headers'] ?? [], $matObra); ?>
            </div>
        </div>
    </div>

    <!-- Pedidos por mês -->
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm h-100">
            <div class="card-header"><i class="bi bi-calendar3"></i> Pedidos por mês</div>
            <div class="card-body p-0" style="max-height:420px; overflow:auto;">
                <?php $renderTable($data['2. Media Mensal']['headers'] ?? [], $mensal); ?>
            </div>
        </div>
    </div>

    <!-- Pedidos por dia -->
    <div class="col-12 col-xl-6">
        <div class="card shadow-sm h-100">
            <div class="card-header"><i class="bi bi-calendar-day"></i> Pedidos por dia</div>
            <div class="card-body p-0" style="max-height:420px; overflow:auto;">
                <?php $renderTable($data['8. Pedidos Diarios']['headers'] ?? [], $diarios); ?>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end mt-3">
    <a href="/admin/relatorio-pedidos/download" class="btn btn-success">
        <i class="bi bi-file-earmark-spreadsheet"></i> Baixar Excel completo
    </a>
</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/Views/admin/layouts/app.php';
