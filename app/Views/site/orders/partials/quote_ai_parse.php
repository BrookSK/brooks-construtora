<!-- Seção: Auto-preenchimento de Orçamento via IA -->
<div class="card mb-3 border-info" id="aiParseSection">
    <div class="card-header bg-info bg-opacity-10">
        <i class="bi bi-robot text-info"></i> <strong>Preencher Orçamento com IA</strong>
        <small class="text-muted d-block mt-1">Cole as mensagens do fornecedor (WhatsApp) e a IA preenche os preços automaticamente.</small>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label small fw-bold">Fornecedor</label>
                <select class="form-select form-select-sm" id="aiParseSupplier">
                    <option value="">Selecione o fornecedor...</option>
                    <?php foreach ($suppliers as $sup): ?>
                        <option value="<?= $sup['id'] ?>"><?= htmlspecialchars($sup['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-7">
                <label class="form-label small fw-bold">Mensagens do Fornecedor (cole aqui)</label>
                <textarea class="form-control form-control-sm" id="aiParseMessages" rows="6" placeholder="Cole aqui as mensagens do WhatsApp com o orçamento do fornecedor...&#10;&#10;Exemplo:&#10;Bom dia! Segue os valores:&#10;Cano 100mm - R$ 45,00 cada&#10;Joelho 40mm - R$ 3,50&#10;Frete: R$ 150,00&#10;Prazo: 5 dias úteis&#10;Pagamento: 28 dias"></textarea>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-3 gap-2">
            <button type="button" class="btn btn-info btn-sm" id="aiParseBtn" onclick="parseWithAI()" disabled>
                <i class="bi bi-magic"></i> Processar com IA
            </button>
        </div>

        <!-- Resultado da IA -->
        <div id="aiParseResult" style="display:none;" class="mt-3">
            <div class="alert alert-success small py-2" id="aiParseSuccess" style="display:none;">
                <i class="bi bi-check-circle"></i> <span id="aiParseSuccessMsg"></span>
            </div>
            <div class="alert alert-danger small py-2" id="aiParseError" style="display:none;">
                <i class="bi bi-x-circle"></i> <span id="aiParseErrorMsg"></span>
            </div>

            <!-- Preview dos dados extraídos -->
            <div id="aiParsedData" style="display:none;">
                <h6 class="small fw-bold mb-2">Dados extraídos pela IA:</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-2" style="font-size:0.8rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Material</th>
                                <th>Preço Unitário</th>
                                <th class="text-center">Usar?</th>
                            </tr>
                        </thead>
                        <tbody id="aiParsedItemsBody"></tbody>
                    </table>
                </div>

                <!-- Condições extraídas -->
                <div class="row g-2 mb-3" id="aiParsedConditions">
                    <div class="col-6 col-md-3">
                        <label class="form-label small">Prazo Entrega</label>
                        <input type="text" class="form-control form-control-sm" id="aiParsedDeliveryDays" readonly>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small">Forma Pgto</label>
                        <input type="text" class="form-control form-control-sm" id="aiParsedPaymentMethod" readonly>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small">Cond. Pgto</label>
                        <input type="text" class="form-control form-control-sm" id="aiParsedPaymentCondition" readonly>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small">Frete</label>
                        <input type="text" class="form-control form-control-sm" id="aiParsedFreight" readonly>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearAIParse()">
                        <i class="bi bi-x"></i> Descartar
                    </button>
                    <button type="button" class="btn btn-success btn-sm" onclick="applyAIParse()">
                        <i class="bi bi-check-lg"></i> Aplicar no Formulário
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let aiParsedResult = null;

// Habilitar botão quando tiver fornecedor e mensagem
document.getElementById('aiParseMessages').addEventListener('input', toggleAIBtn);
document.getElementById('aiParseSupplier').addEventListener('change', toggleAIBtn);

function toggleAIBtn() {
    const hasSupplier = document.getElementById('aiParseSupplier').value;
    const hasMessage = document.getElementById('aiParseMessages').value.trim().length > 10;
    document.getElementById('aiParseBtn').disabled = !(hasSupplier && hasMessage);
}

async function parseWithAI() {
    const supplierId = document.getElementById('aiParseSupplier').value;
    const messages = document.getElementById('aiParseMessages').value.trim();
    const btn = document.getElementById('aiParseBtn');

    if (!supplierId || !messages) {
        alert('Selecione o fornecedor e cole as mensagens.');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processando...';

    document.getElementById('aiParseResult').style.display = 'block';
    document.getElementById('aiParseSuccess').style.display = 'none';
    document.getElementById('aiParseError').style.display = 'none';
    document.getElementById('aiParsedData').style.display = 'none';

    try {
        const resp = await fetch('/pedido/cotacao/parse-ai-quote', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                order_id: <?= $order['id'] ?>,
                supplier_id: supplierId,
                messages: messages,
            })
        });
        const data = await resp.json();

        if (data.success && data.parsed) {
            aiParsedResult = data.parsed;
            renderParsedData(data.parsed, supplierId);
            document.getElementById('aiParseSuccess').style.display = 'block';
            document.getElementById('aiParseSuccessMsg').textContent = 'Dados extraídos com sucesso! Revise antes de aplicar.';
            document.getElementById('aiParsedData').style.display = 'block';
        } else {
            document.getElementById('aiParseError').style.display = 'block';
            document.getElementById('aiParseErrorMsg').textContent = data.error || 'Não foi possível extrair dados das mensagens.';
        }
    } catch (e) {
        document.getElementById('aiParseError').style.display = 'block';
        document.getElementById('aiParseErrorMsg').textContent = 'Erro de conexão ao processar com IA.';
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-magic"></i> Processar com IA';
}

function renderParsedData(parsed, supplierId) {
    const tbody = document.getElementById('aiParsedItemsBody');
    tbody.innerHTML = '';

    const items = parsed.items || [];
    items.forEach((item, i) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${item.name || 'Item ' + (i+1)}</td>
            <td><strong>R$ ${formatPrice(item.unit_price || 0)}</strong></td>
            <td class="text-center">
                <input type="checkbox" class="form-check-input ai-item-check" data-index="${i}" checked>
            </td>
        `;
        tbody.appendChild(tr);
    });

    // Condições
    document.getElementById('aiParsedDeliveryDays').value = parsed.delivery_days || '';
    document.getElementById('aiParsedPaymentMethod').value = parsed.payment_method || '';
    document.getElementById('aiParsedPaymentCondition').value = parsed.payment_condition || '';
    document.getElementById('aiParsedFreight').value = parsed.freight ? `R$ ${formatPrice(parsed.freight)}` : '';
}

function formatPrice(value) {
    return parseFloat(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function clearAIParse() {
    document.getElementById('aiParseResult').style.display = 'none';
    document.getElementById('aiParsedData').style.display = 'none';
    aiParsedResult = null;
}

function applyAIParse() {
    if (!aiParsedResult) return;

    const supplierId = document.getElementById('aiParseSupplier').value;
    if (!supplierId) { alert('Selecione o fornecedor.'); return; }

    // Verificar se o fornecedor já foi adicionado no formulário
    const supplierBlock = document.getElementById('supplier-block-' + supplierId);
    if (!supplierBlock) {
        alert('Adicione este fornecedor na seção de cotação primeiro, depois aplique os dados da IA.');
        return;
    }

    // Aplicar preços nos itens
    const checkedItems = document.querySelectorAll('.ai-item-check:checked');
    const parsedItems = aiParsedResult.items || [];
    const orderItems = <?= json_encode(array_values($items)) ?>;

    checkedItems.forEach(chk => {
        const idx = parseInt(chk.dataset.index);
        const parsedItem = parsedItems[idx];
        if (!parsedItem || !parsedItem.unit_price) return;

        // Tentar encontrar o item do pedido correspondente pelo nome
        let matchedItemId = null;
        const parsedNameNorm = (parsedItem.name || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

        orderItems.forEach(oi => {
            const oiNameNorm = (oi.material_name || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            if (oiNameNorm.includes(parsedNameNorm) || parsedNameNorm.includes(oiNameNorm)) {
                matchedItemId = oi.id;
            }
        });

        // Se encontrou, preencher o campo de preço
        if (matchedItemId) {
            const priceInput = document.querySelector(`input[name="supplier_prices[${supplierId}][${matchedItemId}]"]`);
            if (priceInput) {
                // Se priceMode é 'total', converter unitário para total (unitário × quantidade)
                let valueToFill = parsedItem.unit_price;
                if (typeof priceMode !== 'undefined' && priceMode === 'total') {
                    const qty = parseFloat(priceInput.dataset.qty) || 1;
                    valueToFill = parseFloat(parsedItem.unit_price) * qty;
                }
                priceInput.value = formatPrice(valueToFill);
                priceInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        } else if (idx < orderItems.length) {
            // Fallback: preencher por posição
            const itemId = orderItems[idx]?.id;
            if (itemId) {
                const priceInput = document.querySelector(`input[name="supplier_prices[${supplierId}][${itemId}]"]`);
                if (priceInput) {
                    // Se priceMode é 'total', converter unitário para total (unitário × quantidade)
                    let valueToFill = parsedItem.unit_price;
                    if (typeof priceMode !== 'undefined' && priceMode === 'total') {
                        const qty = parseFloat(priceInput.dataset.qty) || 1;
                        valueToFill = parseFloat(parsedItem.unit_price) * qty;
                    }
                    priceInput.value = formatPrice(valueToFill);
                    priceInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }
        }
    });

    // Aplicar condições financeiras
    if (aiParsedResult.freight) {
        const freightInput = supplierBlock.querySelector('input[name*="[freight]"]');
        if (freightInput) freightInput.value = formatPrice(aiParsedResult.freight);
    }

    if (aiParsedResult.delivery_days) {
        const deliveryInput = supplierBlock.querySelector('input[name*="[delivery_days]"]');
        if (deliveryInput) deliveryInput.value = aiParsedResult.delivery_days;
    }

    if (aiParsedResult.payment_method) {
        const pmInput = supplierBlock.querySelector('select[name*="[payment_method]"]');
        if (pmInput) {
            // Tentar encontrar a opção correspondente
            for (let opt of pmInput.options) {
                if (opt.text.toLowerCase().includes(aiParsedResult.payment_method.toLowerCase())) {
                    pmInput.value = opt.value;
                    break;
                }
            }
        }
    }

    if (aiParsedResult.payment_condition) {
        const pcInput = supplierBlock.querySelector('input[name*="[payment_condition]"]');
        if (pcInput) pcInput.value = aiParsedResult.payment_condition;
    }

    // Feedback visual
    document.getElementById('aiParseSuccess').style.display = 'block';
    document.getElementById('aiParseSuccessMsg').textContent = 'Dados aplicados no formulário! Revise os valores antes de enviar.';
    document.getElementById('aiParsedData').style.display = 'none';

    // Scroll para o bloco do fornecedor
    supplierBlock.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>
