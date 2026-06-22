<?php $pageTitle = 'Itens Sobressalentes'; $currentPage = 'orders'; ?>
<?php ob_start(); ?>

<?php
$remaining = $weeklyBudget - $thisWeekTotal;
$pct = $weeklyBudget > 0 ? min(100, ($thisWeekTotal / $weeklyBudget) * 100) : 0;
$barColor = $remaining < 0 ? 'danger' : ($remaining < ($weeklyBudget * 0.2) ? 'warning' : 'success');
$paymentLabels = ['pix'=>'PIX','boleto'=>'Boleto','cartao'=>'Cartão','transferencia'=>'Transf.','dinheiro'=>'Dinheiro','outro'=>'Outro'];
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0">Itens Sobressalentes</h5>
        <small class="text-muted">Itens comprados na hora, avulsos, vinculados a pedidos</small>
    </div>
    <a href="/admin/orders" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar</a>
</div>

<!-- Saldo semanal -->
<div class="card mb-3 border-<?= $barColor ?>">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>Saldo Semanal</strong>
            <div>
                <span class="text-<?= $barColor ?> fw-bold fs-5">R$ <?= number_format(max(0, $remaining), 2, ',', '.') ?></span>
                <span class="text-muted small"> restante</span>
            </div>
        </div>
        <div class="progress" style="height:8px;">
            <div class="progress-bar bg-<?= $barColor ?>" style="width:<?= $pct ?>%;"></div>
        </div>
        <div class="d-flex justify-content-between mt-1 small text-muted">
            <span>Gasto: R$ <?= number_format($thisWeekTotal, 2, ',', '.') ?></span>
            <span>Orçamento: R$ <?= number_format($weeklyBudget, 2, ',', '.') ?></span>
        </div>
        <?php if ($remaining < 0): ?>
        <div class="alert alert-danger py-1 px-2 mt-2 mb-0 small"><i class="bi bi-exclamation-triangle-fill"></i> Orçamento semanal excedido em R$ <?= number_format(abs($remaining), 2, ',', '.') ?>!</div>
        <?php endif; ?>
        <!-- Config do orçamento -->
        <form method="POST" action="/admin/orders/settings/update" class="mt-2">
            <div class="input-group input-group-sm" style="max-width:280px;">
                <span class="input-group-text">Orçamento R$</span>
                <input type="text" class="form-control" name="spare_items_weekly_budget" value="<?= number_format($weeklyBudget, 2, ',', '.') ?>" inputmode="decimal">
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-check"></i></button>
            </div>
        </form>
    </div>
</div>
<!-- Formulário de adição -->
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-plus-circle"></i> Adicionar Item Sobressalente</div>
    <div class="card-body">
        <form method="POST" action="/admin/orders/spare-items/add">
            <div class="row g-2">
                <div class="col-12 col-md-3">
                    <label class="form-label small">Pedido vinculado *</label>
                    <select class="form-select form-select-sm" name="order_id" required>
                        <option value="">-- Selecione --</option>
                        <?php foreach ($orders as $o): ?>
                        <option value="<?= $o['id'] ?>"><?= $o['code'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label small">Descrição *</label>
                    <input type="text" class="form-control form-control-sm" name="description" required placeholder="Ex: Fita isolante, parafusos, etc.">
                </div>
                <div class="col-4 col-md-1">
                    <label class="form-label small">Qtd</label>
                    <input type="text" class="form-control form-control-sm" name="quantity" value="1" inputmode="decimal">
                </div>
                <div class="col-4 col-md-1">
                    <label class="form-label small">Unid.</label>
                    <input type="text" class="form-control form-control-sm" name="unit" placeholder="un">
                </div>
                <div class="col-4 col-md-2">
                    <label class="form-label small">Preço Unit. *</label>
                    <input type="text" class="form-control form-control-sm" name="unit_price" required placeholder="0,00" inputmode="decimal">
                </div>
            </div>
            <div class="row g-2 mt-1">
                <div class="col-6 col-md-3">
                    <label class="form-label small">Onde comprou</label>
                    <input type="text" class="form-control form-control-sm" name="supplier_name" placeholder="Nome da loja">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small">Pagamento</label>
                    <select class="form-select form-select-sm" name="payment_method">
                        <option value="">--</option>
                        <option value="pix">PIX</option>
                        <option value="boleto">Boleto</option>
                        <option value="cartao">Cartão</option>
                        <option value="transferencia">Transferência</option>
                        <option value="dinheiro">Dinheiro</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small">Comprado por</label>
                    <input type="text" class="form-control form-control-sm" name="purchased_by" value="<?= htmlspecialchars($user['name'] ?? '') ?>">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small">Data</label>
                    <input type="date" class="form-control form-control-sm" name="purchased_at" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-12 col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-plus-lg"></i> Adicionar</button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- Itens desta semana -->
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-calendar-week"></i> Itens da Semana Atual</div>
    <?php if (empty($thisWeekItems)): ?>
    <div class="card-body text-center text-muted py-3">
        <p class="mb-0 small">Nenhum item sobressalente esta semana.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size:0.8rem;">
            <thead class="table-light">
                <tr>
                    <th>Data</th>
                    <th>Pedido</th>
                    <th>Item</th>
                    <th class="text-center">Qtd</th>
                    <th class="text-end">Valor</th>
                    <th>Onde</th>
                    <th>Pgto</th>
                    <th>Por</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($thisWeekItems as $si): ?>
                <tr>
                    <td><?= date('d/m', strtotime($si['purchased_at'])) ?></td>
                    <td><a href="/admin/orders/show/<?= $si['order_id'] ?>"><?= $si['order_code'] ?></a></td>
                    <td><strong><?= htmlspecialchars($si['description']) ?></strong></td>
                    <td class="text-center"><?= number_format($si['quantity'], $si['quantity'] == (int)$si['quantity'] ? 0 : 2) ?> <?= htmlspecialchars($si['unit'] ?? '') ?></td>
                    <td class="text-end fw-bold">R$ <?= number_format($si['total_price'], 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars($si['supplier_name'] ?? '-') ?></td>
                    <td><?= $si['payment_method'] ? ($paymentLabels[$si['payment_method']] ?? $si['payment_method']) : '-' ?></td>
                    <td><?= htmlspecialchars($si['purchased_by'] ?? '-') ?></td>
                    <td>
                        <form method="POST" action="/admin/orders/spare-items/delete" class="d-inline" onsubmit="return confirm('Excluir?')">
                            <input type="hidden" name="id" value="<?= $si['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger p-0 px-1"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr class="table-warning">
                    <td colspan="4" class="text-end fw-bold">Total da Semana:</td>
                    <td class="text-end fw-bold">R$ <?= number_format($thisWeekTotal, 2, ',', '.') ?></td>
                    <td colspan="4"></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Histórico completo -->
<?php if (!empty($allItems)): ?>
<div class="card">
    <div class="card-header"><i class="bi bi-clock-history"></i> Histórico</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0" style="font-size:0.75rem;">
            <thead class="table-light">
                <tr>
                    <th>Data</th>
                    <th>Pedido</th>
                    <th>Item</th>
                    <th class="text-center">Qtd</th>
                    <th class="text-end">Valor</th>
                    <th>Onde</th>
                    <th>Por</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allItems as $si): ?>
                <tr>
                    <td><?= $si['purchased_at'] ? date('d/m/Y', strtotime($si['purchased_at'])) : '-' ?></td>
                    <td><a href="/admin/orders/show/<?= $si['order_id'] ?>"><?= $si['order_code'] ?></a></td>
                    <td><?= htmlspecialchars($si['description']) ?></td>
                    <td class="text-center"><?= number_format($si['quantity'], $si['quantity'] == (int)$si['quantity'] ? 0 : 2) ?></td>
                    <td class="text-end">R$ <?= number_format($si['total_price'], 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars($si['supplier_name'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($si['purchased_by'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
