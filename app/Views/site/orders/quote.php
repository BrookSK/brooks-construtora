<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotação - Pedido <?= htmlspecialchars($order['code']) ?> | Brooks Construtora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/searchable-select.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
        .page-header { background: #3a3b4e; color: #fff; padding: 1rem 0; }
        .main-card { border: none; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .supplier-block { border: 2px solid #dee2e6; border-radius: 10px; padding: 1.25rem; margin-bottom: 1.25rem; background: #fff; }
        .supplier-block h6 { color: #3a3b4e; }
        .history-hint { font-size: 0.7rem; color: #888; margin-top: 2px; }
        .history-hint strong { color: #28a745; }
        @media (max-width: 768px) {
            .main-card .card-body, .main-card .card-header { padding: 1rem; }
            .supplier-block { padding: 1rem; }
            input, select, textarea { font-size: 16px !important; }
        }
    </style>
</head>
<body>
    <div class="page-header text-center">
        <div class="container">
            <h4 class="mb-1">BROOKS CONSTRUTORA</h4>
            <p class="mb-0 opacity-75 small">Mapa de Cotação</p>
        </div>
    </div>

    <div class="container py-4">
        <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card main-card">
            <div class="card-header bg-warning bg-opacity-10 border-0 p-3 p-md-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">Pedido <strong><?= htmlspecialchars($order['code']) ?></strong></h5>
                        <p class="mb-0 text-muted small">Solicitado por: <?= htmlspecialchars($order['created_by_name']) ?> em <?= date('d/m/Y', strtotime($order['created_at'])) ?></p>
                    </div>
                    <span class="badge bg-warning text-dark p-2">Aguardando Cotação</span>
                </div>
                <?php if (!empty($order['description'])): ?>
                <div class="mt-2 p-2 bg-white rounded small">
                    <strong>Obs:</strong> <?= nl2br(htmlspecialchars($order['description'])) ?>
                </div>
                <?php endif; ?>
            </div>

            <form method="POST" action="/pedido/cotacao/enviar/<?= $token ?>" id="quoteForm">
                <div class="card-body p-3 p-md-4">
                    <!-- Identificação -->
                    <h6 class="mb-3"><i class="bi bi-person"></i> Identificação</h6>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Seu Nome *</label>
                            <input type="text" class="form-control" name="quoted_by_name" required placeholder="Informe seu nome completo">
                        </div>
                    </div>

                    <!-- Itens do pedido (referência) -->
                    <h6 class="mb-2"><i class="bi bi-list-check"></i> Itens do Pedido</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr><th>#</th><th>Material</th><th>Espec.</th><th>Class.</th><th>Unid.</th><th class="text-center">Qtd</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $i => $item): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><strong><?= htmlspecialchars($item['material_name']) ?></strong></td>
                                    <td class="text-muted small"><?= htmlspecialchars($item['specification'] ?? '-') ?></td>
                                    <td class="text-muted small"><?= htmlspecialchars($item['classification'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($item['unit'] ?? '-') ?></td>
                                    <td class="text-center fw-bold"><?= number_format($item['quantity'], $item['quantity'] == (int)$item['quantity'] ? 0 : 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Fornecedores para cotação -->
                    <h6 class="mb-3"><i class="bi bi-building"></i> Fornecedores</h6>
                    <p class="text-muted small mb-2">Adicione os fornecedores e informe os valores de cada um.</p>
                    
                    <div class="mb-3">
                        <select id="addSupplierSelect" style="display:none;">
                            <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s['id'] ?>" data-name="<?= htmlspecialchars($s['name']) ?>"><?= htmlspecialchars($s['name']) ?> <?= $s['cnpj'] ? '(' . $s['cnpj'] . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="d-flex gap-2">
                            <div class="flex-grow-1" id="supplierSearchWrap"></div>
                            <button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="addSelectedSupplier()">
                                <i class="bi bi-plus"></i> Adicionar
                            </button>
                        </div>
                        <div class="mt-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="document.getElementById('newSupplierSection').style.display = document.getElementById('newSupplierSection').style.display === 'none' ? 'block' : 'none'">
                                <i class="bi bi-building"></i> Cadastrar Novo Fornecedor
                            </button>
                        </div>
                        <!-- Cadastro rápido de fornecedor inline -->
                        <div id="newSupplierSection" style="display:none;" class="mt-2 p-3 border rounded bg-light">
                            <h6 class="mb-2 small">Novo Fornecedor</h6>
                            <div class="row g-2">
                                <div class="col-12">
                                    <input type="text" class="form-control form-control-sm" id="newSupName" placeholder="Nome do fornecedor *">
                                </div>
                                <div class="col-6">
                                    <input type="text" class="form-control form-control-sm" id="newSupCnpj" placeholder="CNPJ">
                                </div>
                                <div class="col-6">
                                    <input type="text" class="form-control form-control-sm" id="newSupPhone" placeholder="Telefone">
                                </div>
                                <div class="col-12">
                                    <input type="email" class="form-control form-control-sm" id="newSupEmail" placeholder="E-mail">
                                </div>
                                <div class="col-12">
                                    <button type="button" class="btn btn-primary btn-sm w-100" onclick="saveNewSupplier()">
                                        <i class="bi bi-check"></i> Salvar Fornecedor e Adicionar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="suppliersContainer"></div>

                    <!-- Observações -->
                    <div class="mt-3">
                        <label class="form-label">Observações da Cotação</label>
                        <textarea class="form-control" name="quote_notes" rows="2" placeholder="Observações sobre preços, prazos, condições de pagamento, etc."></textarea>
                    </div>
                </div>

                <div class="card-footer p-3 p-md-4 text-center">
                    <button type="submit" class="btn btn-success btn-lg px-5" id="submitBtn" disabled>
                        <i class="bi bi-check-lg"></i> Enviar Cotação
                    </button>
                    <p class="text-muted small mt-2 mb-0">Adicione pelo menos um fornecedor para enviar.</p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/searchable-select.js"></script>
    <script>
    const items = <?= json_encode($items) ?>;
    const priceHistory = <?= json_encode($priceHistory ?? []) ?>;
    let supplierCount = 0;
    let addedSuppliers = [];

    // SearchableSelect para adicionar fornecedor
    const supplierSS = new SearchableSelect(document.getElementById('addSupplierSelect'), {
        placeholder: 'Buscar fornecedor...',
        onSelect: function(value, text, dataset) {
            // Guardamos temporariamente; o botão "Adicionar" confirma
            document.getElementById('addSupplierSelect').dataset.selectedId = value;
            document.getElementById('addSupplierSelect').dataset.selectedName = dataset.name || text;
        }
    });

    function addSelectedSupplier() {
        const sel = document.getElementById('addSupplierSelect');
        const sid = sel.dataset.selectedId;
        const sname = sel.dataset.selectedName;
        if (!sid) { alert('Selecione um fornecedor primeiro.'); return; }
        if (addedSuppliers.includes(sid)) { alert('Fornecedor já adicionado.'); return; }
        
        addedSuppliers.push(sid);
        addSupplierBlock(sid, sname);
        supplierSS.clear();
        sel.dataset.selectedId = '';
        sel.dataset.selectedName = '';
        document.getElementById('submitBtn').disabled = false;
    }

    async function saveNewSupplier() {
        const name = document.getElementById('newSupName').value.trim();
        if (!name) { alert('Nome é obrigatório'); return; }

        const resp = await fetch('/pedido/cotacao/novo-fornecedor', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                name: name,
                cnpj: document.getElementById('newSupCnpj').value,
                email: document.getElementById('newSupEmail').value,
                phone: document.getElementById('newSupPhone').value,
            })
        });
        const data = await resp.json();
        if (data.success) {
            // Adicionar no SearchableSelect
            supplierSS.addOption(data.supplier.id, data.supplier.name, { name: data.supplier.name });
            
            // Adicionar direto como bloco
            addedSuppliers.push(String(data.supplier.id));
            addSupplierBlock(data.supplier.id, data.supplier.name);
            document.getElementById('submitBtn').disabled = false;

            // Limpar e fechar o formulário
            document.getElementById('newSupName').value = '';
            document.getElementById('newSupCnpj').value = '';
            document.getElementById('newSupPhone').value = '';
            document.getElementById('newSupEmail').value = '';
            document.getElementById('newSupplierSection').style.display = 'none';
        } else {
            alert(data.error || 'Erro ao salvar fornecedor');
        }
    }

    function addSupplierBlock(sid, name) {
        supplierCount++;
        const block = document.createElement('div');
        block.className = 'supplier-block';
        block.id = 'supplier-block-' + sid;
        
        let itemsHtml = '';
        items.forEach(item => {
            // Buscar histórico de preço deste material com este fornecedor
            const hist = priceHistory.filter(h => h.material_id == item.material_id && h.supplier_id == sid);
            const lastPrice = hist.length > 0 ? hist[0] : null;
            const bestPrice = hist.length > 0 ? hist.reduce((a, b) => a.unit_price < b.unit_price ? a : b) : null;
            
            let histHint = '';
            if (lastPrice) {
                histHint = `<span class="history-hint">Último: <strong>R$ ${parseFloat(lastPrice.unit_price).toFixed(2).replace('.', ',')}</strong>`;
                if (bestPrice && bestPrice.unit_price != lastPrice.unit_price) {
                    histHint += ` | Melhor: <strong>R$ ${parseFloat(bestPrice.unit_price).toFixed(2).replace('.', ',')}</strong>`;
                }
                histHint += '</span>';
            }
            
            itemsHtml += `
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div class="flex-grow-1">
                        <span class="small">${item.material_name}</span>
                        <span class="text-muted small">(x${item.quantity} ${item.unit || ''})</span>
                        ${histHint}
                    </div>
                    <div style="min-width:120px;">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">R$</span>
                            <input type="text" inputmode="decimal" class="form-control price-input" 
                                name="supplier_prices[${sid}][${item.id}]" placeholder="0,00" required
                                data-qty="${item.quantity}" data-sid="${sid}">
                        </div>
                    </div>
                </div>`;
        });

        block.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0"><i class="bi bi-building"></i> ${name}</h6>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeSupplierBlock('${sid}')"><i class="bi bi-x"></i></button>
            </div>
            <input type="hidden" name="supplier_ids[]" value="${sid}">
            
            <!-- Preços por item -->
            ${itemsHtml}
            
            <!-- Ajustes financeiros -->
            <div class="mt-3 pt-3 border-top">
                <div class="row g-2">
                    <div class="col-6 col-md-4">
                        <label class="form-label small text-muted mb-0">Desconto</label>
                        <div class="input-group input-group-sm">
                            <input type="text" inputmode="decimal" class="form-control" name="supplier_financials[${sid}][discount_value]" placeholder="0" data-sid="${sid}">
                            <select class="form-select" name="supplier_financials[${sid}][discount_type]" style="max-width:55px;">
                                <option value="percent">%</option>
                                <option value="fixed">R$</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label small text-muted mb-0">Acréscimo</label>
                        <div class="input-group input-group-sm">
                            <input type="text" inputmode="decimal" class="form-control" name="supplier_financials[${sid}][surcharge_value]" placeholder="0" data-sid="${sid}">
                            <select class="form-select" name="supplier_financials[${sid}][surcharge_type]" style="max-width:55px;">
                                <option value="percent">%</option>
                                <option value="fixed">R$</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-4 col-md-4 d-none d-md-block"></div>
                    <div class="col-4 col-md-2">
                        <label class="form-label small text-muted mb-0">IPI %</label>
                        <input type="text" inputmode="decimal" class="form-control form-control-sm" name="supplier_financials[${sid}][ipi_percent]" placeholder="0">
                    </div>
                    <div class="col-4 col-md-2">
                        <label class="form-label small text-muted mb-0">ICMS %</label>
                        <input type="text" inputmode="decimal" class="form-control form-control-sm" name="supplier_financials[${sid}][icms_percent]" placeholder="0">
                    </div>
                    <div class="col-4 col-md-3">
                        <label class="form-label small text-muted mb-0">Frete (R$)</label>
                        <input type="text" inputmode="decimal" class="form-control form-control-sm" name="supplier_financials[${sid}][freight]" placeholder="0,00">
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-4 mt-3 pt-2 border-top">
                    <div class="text-end">
                        <small class="text-muted d-block">Subtotal insumos</small>
                        <strong id="subtotal-items-${sid}">R$ 0,00</strong>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block">Total final</small>
                        <strong class="text-success fs-6" id="subtotal-final-${sid}">R$ 0,00</strong>
                    </div>
                </div>
            </div>
        `;

        document.getElementById('suppliersContainer').appendChild(block);

        // Bind events para calcular
        block.querySelectorAll('.price-input, input[name*="financials"]').forEach(input => {
            input.addEventListener('input', () => calculateSupplierTotal(sid));
            input.addEventListener('blur', function() {
                let val = this.value.replace(/[^\d,\.]/g, '').replace(',', '.');
                if (val && !isNaN(parseFloat(val))) this.value = parseFloat(val).toFixed(2).replace('.', ',');
                calculateSupplierTotal(sid);
            });
        });
    }

    function removeSupplierBlock(sid) {
        document.getElementById('supplier-block-' + sid)?.remove();
        addedSuppliers = addedSuppliers.filter(s => s !== sid);
        if (addedSuppliers.length === 0) document.getElementById('submitBtn').disabled = true;
    }

    function calculateSupplierTotal(sid) {
        const block = document.getElementById('supplier-block-' + sid);
        if (!block) return;

        let subtotalItems = 0;
        block.querySelectorAll('.price-input').forEach(input => {
            const val = parseFloat(input.value.replace(/\./g, '').replace(',', '.')) || 0;
            const qty = parseFloat(input.dataset.qty) || 0;
            subtotalItems += val * qty;
        });

        // Financeiros
        const getVal = (name) => parseFloat((block.querySelector(`[name="supplier_financials[${sid}][${name}]"]`)?.value || '0').replace(/\./g, '').replace(',', '.')) || 0;
        const getType = (name) => block.querySelector(`[name="supplier_financials[${sid}][${name}]"]`)?.value || 'percent';
        
        const discountVal = getVal('discount_value');
        const discountType = getType('discount_type');
        const surchargeVal = getVal('surcharge_value');
        const surchargeType = getType('surcharge_type');
        const ipi = getVal('ipi_percent');
        const icms = getVal('icms_percent');
        const freight = getVal('freight');

        let total = subtotalItems;
        
        // Desconto
        if (discountType === 'percent') total -= subtotalItems * (discountVal / 100);
        else total -= discountVal;
        
        // Acréscimo
        if (surchargeType === 'percent') total += subtotalItems * (surchargeVal / 100);
        else total += surchargeVal;
        
        // IPI e ICMS
        total += subtotalItems * (ipi / 100);
        total += subtotalItems * (icms / 100);
        
        // Frete
        total += freight;

        document.getElementById('subtotal-items-' + sid).textContent = 'R$ ' + subtotalItems.toFixed(2).replace('.', ',');
        document.getElementById('subtotal-final-' + sid).textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
    }
    </script>
</body>
</html>
