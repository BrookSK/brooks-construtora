<?php $pageTitle = 'Detalhe do Log de Cotação'; $currentPage = 'orders'; ob_start(); ?>

<?php
$backend = $log['backend']['data'] ?? [];
$frontend = $log['frontend']['data'] ?? [];
$orderCode = $backend['order_code'] ?? "#{$log['order_id']}";
?>

<div class="top-bar">
    <div>
        <h5 class="mb-0"><i class="bi bi-bug"></i> Log: Pedido <?= htmlspecialchars($orderCode) ?></h5>
        <small class="text-muted"><?= htmlspecialchars($log['created_at'] ?? '') ?> | Arquivo: <?= htmlspecialchars($filename) ?></small>
    </div>
    <a href="/admin/orders/quote-logs" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<!-- Resumo -->
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card p-3">
            <small class="text-muted">Cotado por</small>
            <strong><?= htmlspecialchars($backend['quoted_by'] ?? '-') ?></strong>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <small class="text-muted">Total Salvo no Pedido</small>
            <strong class="text-success fs-5">R$ <?= number_format($backend['total_saved_to_order'] ?? 0, 2, ',', '.') ?></strong>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <small class="text-muted">Modo de Preço (Frontend)</small>
            <strong><?= htmlspecialchars($frontend['price_mode'] ?? 'N/A') ?></strong>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card p-3">
            <small class="text-muted">Visualização (Frontend)</small>
            <strong><?= htmlspecialchars($frontend['current_view'] ?? 'N/A') ?></strong>
        </div>
    </div>
</div>

<!-- Deduções de Estoque -->
<?php $stockDeductions = $backend['stock_deductions'] ?? []; ?>
<?php if (!empty($stockDeductions)): ?>
<div class="card mb-3 border-warning">
    <div class="card-header bg-warning bg-opacity-10 py-2">
        <strong><i class="bi bi-boxes"></i> Deduções de Estoque</strong>
    </div>
    <div class="card-body py-2">
        <table class="table table-sm mb-0">
            <thead><tr><th>Item ID</th><th>Quantidade Deduzida</th></tr></thead>
            <tbody>
                <?php foreach ($stockDeductions as $itemId => $qty): ?>
                <tr><td>#<?= $itemId ?></td><td><?= $qty ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Fornecedores Processados (Backend) -->
<?php $suppliers = $backend['suppliers_processing'] ?? []; ?>
<?php foreach ($suppliers as $sIdx => $supplier): ?>
<div class="card mb-3">
    <div class="card-header bg-primary bg-opacity-10 py-2 d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-building"></i> Fornecedor #<?= $supplier['supplier_id'] ?></strong>
        <span class="badge bg-success fs-6">Total: R$ <?= number_format($supplier['final_total'] ?? 0, 2, ',', '.') ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-striped mb-0" style="font-size:0.8rem;">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th>Material</th>
                        <th>Valor POST (raw)</th>
                        <th>Unit. Parseado</th>
                        <th>Qty Original (BD)</th>
                        <th>Estoque Ded.</th>
                        <th>Comprado Ded.</th>
                        <th>Qty Final</th>
                        <th>Total Calc.</th>
                        <th>Fórmula</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($supplier['items'] ?? [] as $item): ?>
                    <tr>
                        <td>#<?= $item['item_id'] ?></td>
                        <td><?= htmlspecialchars($item['material_name'] ?? '') ?></td>
                        <td><code><?= htmlspecialchars($item['price_str_raw'] ?? '') ?></code></td>
                        <td><strong><?= number_format($item['unit_price_parsed'] ?? 0, 6, ',', '.') ?></strong></td>
                        <td><?= $item['qty_original_db'] ?? '-' ?></td>
                        <td><?= $item['stock_deducted'] ?? 0 ?></td>
                        <td><?= $item['purchased_deducted'] ?? 0 ?></td>
                        <td><strong><?= $item['qty_final'] ?? '-' ?></strong></td>
                        <td class="fw-bold text-success">R$ <?= number_format($item['total_price_calculated'] ?? 0, 2, ',', '.') ?></td>
                        <td><small class="text-muted"><?= htmlspecialchars($item['formula'] ?? '') ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="8" class="text-end fw-bold">Subtotal Itens:</td>
                        <td class="fw-bold">R$ <?= number_format($supplier['subtotal_items'] ?? 0, 2, ',', '.') ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Financeiros -->
        <?php $fin = $supplier['financials'] ?? []; ?>
        <div class="p-2 border-top bg-light" style="font-size:0.8rem;">
            <strong>Financeiros:</strong>
            Desconto: <?= ($fin['discount_type'] ?? 'percent') === 'percent' ? ($fin['discount_value'] ?? 0) . '%' : 'R$ ' . number_format($fin['discount_value'] ?? 0, 2, ',', '.') ?> |
            Acréscimo: <?= ($fin['surcharge_type'] ?? 'percent') === 'percent' ? ($fin['surcharge_value'] ?? 0) . '%' : 'R$ ' . number_format($fin['surcharge_value'] ?? 0, 2, ',', '.') ?> |
            IPI: <?= $fin['ipi_percent'] ?? 0 ?>% |
            ICMS: <?= $fin['icms_percent'] ?? 0 ?>% |
            Frete: R$ <?= number_format($fin['freight'] ?? 0, 2, ',', '.') ?> (raw: <code><?= htmlspecialchars($fin['freight_raw'] ?? '') ?></code>)
            <br><small class="text-muted"><?= htmlspecialchars($supplier['formula_final'] ?? '') ?></small>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Frontend: Valores ANTES da conversão -->
<?php $beforeConversion = $frontend['values_before_conversion'] ?? []; ?>
<?php if (!empty($beforeConversion)): ?>
<div class="card mb-3 border-info">
    <div class="card-header bg-info bg-opacity-10 py-2">
        <strong><i class="bi bi-arrow-down-up"></i> Frontend: Valores ANTES da Conversão (total → unitário)</strong>
        <small class="text-muted ms-2">O que o usuário digitou/viu na tela</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0" style="font-size:0.8rem;">
                <thead class="table-light">
                    <tr>
                        <th>Item ID</th>
                        <th>Campo (name)</th>
                        <th>Valor no Input</th>
                        <th>parseBRL()</th>
                        <th>data-qty</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($beforeConversion as $item): ?>
                    <tr>
                        <td>#<?= htmlspecialchars($item['item_id'] ?? '') ?></td>
                        <td><code class="small"><?= htmlspecialchars($item['name'] ?? '') ?></code></td>
                        <td><strong><?= htmlspecialchars($item['value_before'] ?? '') ?></strong></td>
                        <td><?= $item['parsed_value'] !== null ? number_format($item['parsed_value'], 2, ',', '.') : '<em>null</em>' ?></td>
                        <td><?= htmlspecialchars($item['data_qty'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Frontend: Valores APÓS conversão (o que foi enviado no POST) -->
<?php $frontendSuppliers = $frontend['suppliers'] ?? []; ?>
<?php if (!empty($frontendSuppliers)): ?>
<div class="card mb-3 border-success">
    <div class="card-header bg-success bg-opacity-10 py-2">
        <strong><i class="bi bi-send"></i> Frontend: Valores APÓS Conversão (enviados no POST)</strong>
        <small class="text-muted ms-2">O que realmente foi submetido no formulário</small>
    </div>
    <div class="card-body p-0">
        <?php foreach ($frontendSuppliers as $fSup): ?>
        <div class="p-2 border-bottom">
            <strong>Fornecedor: <?= htmlspecialchars($fSup['supplier_name'] ?? $fSup['supplier_id'] ?? '') ?></strong>
            <table class="table table-sm mb-0 mt-1" style="font-size:0.8rem;">
                <thead><tr><th>Item ID</th><th>Campo (name)</th><th>Valor Enviado</th><th>data-qty</th></tr></thead>
                <tbody>
                    <?php foreach ($fSup['items'] ?? [] as $fItem): ?>
                    <tr>
                        <td>#<?= htmlspecialchars($fItem['item_id'] ?? '') ?></td>
                        <td><code class="small"><?= htmlspecialchars($fItem['name'] ?? '') ?></code></td>
                        <td><strong><?= htmlspecialchars($fItem['value_in_input'] ?? '') ?></strong></td>
                        <td><?= htmlspecialchars($fItem['data_qty'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (!empty($fSup['financials'])): ?>
            <small class="text-muted">Financeiros: <?= htmlspecialchars(json_encode($fSup['financials'])) ?></small>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Dados brutos do POST (backend) -->
<div class="card mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-code-slash"></i> Dados brutos do POST recebidos pelo backend</strong>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#rawPostData">
            <i class="bi bi-chevron-down"></i> Expandir
        </button>
    </div>
    <div class="collapse" id="rawPostData">
        <div class="card-body">
            <pre style="font-size:0.7rem; max-height:400px; overflow:auto; background:#f8f9fa; padding:1rem; border-radius:4px;"><?= htmlspecialchars(json_encode($backend['raw_post'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
        </div>
    </div>
</div>

<!-- Itens do BD no momento do processamento -->
<div class="card mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-database"></i> Itens do BD (no momento do processamento)</strong>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#dbItemsData">
            <i class="bi bi-chevron-down"></i> Expandir
        </button>
    </div>
    <div class="collapse" id="dbItemsData">
        <div class="card-body p-0">
            <table class="table table-sm mb-0" style="font-size:0.8rem;">
                <thead class="table-light"><tr><th>ID</th><th>Material</th><th>Qty (BD)</th><th>Já Comprado</th><th>Qty Comprada</th></tr></thead>
                <tbody>
                    <?php foreach ($backend['items_from_db'] ?? [] as $dbItem): ?>
                    <tr>
                        <td>#<?= $dbItem['id'] ?></td>
                        <td><?= htmlspecialchars($dbItem['material_name'] ?? '') ?></td>
                        <td><?= $dbItem['quantity'] ?></td>
                        <td><?= $dbItem['already_purchased'] ? '<span class="badge bg-info">Sim</span>' : '-' ?></td>
                        <td><?= $dbItem['already_purchased_qty'] ?? '-' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- JSON completo do frontend -->
<?php if (!empty($log['frontend'])): ?>
<div class="card mb-3">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-laptop"></i> Log completo do Frontend (JSON)</strong>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#fullFrontendLog">
            <i class="bi bi-chevron-down"></i> Expandir
        </button>
    </div>
    <div class="collapse" id="fullFrontendLog">
        <div class="card-body">
            <pre style="font-size:0.7rem; max-height:400px; overflow:auto; background:#f8f9fa; padding:1rem; border-radius:4px;"><?= htmlspecialchars(json_encode($log['frontend'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
