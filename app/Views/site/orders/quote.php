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
        .history-hint { font-size: 0.7rem; color: #888; margin-top: 2px; display: block; }
        .history-hint strong { color: #28a745; }
        .supplier-item-entry .item-info { flex: 1; min-width: 0; font-size: 0.8rem; }
        .supplier-item-entry .item-price-input { width: 130px; flex-shrink: 0; }
        #quotationMap { background: #fff; border-radius: 8px; border: 1px solid #dee2e6; padding: 0.75rem; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        #quotationMap table th { font-size: 0.75rem; white-space: nowrap; }
        #quotationMap table td { vertical-align: middle; font-size: 0.8rem; }
        #quotationMap table th:first-child, #quotationMap table td:first-child { position: sticky; left: 0; background: #fff; z-index: 1; }
        #quotationMap table thead th:first-child { background: #212529; z-index: 2; }
        #quotationMap table tfoot td:first-child { background: #f8f9fa; z-index: 1; }
        #mapFinancials .accordion-button { font-size: 0.85rem; padding: 0.5rem 1rem; }
        .map-price-input { font-size: 0.8rem !important; padding: 4px 6px; }
        @media (max-width: 768px) {
            .main-card .card-body, .main-card .card-header { padding: 0.75rem; }
            .page-header h4 { font-size: 1.1rem; }
            .supplier-block { padding: 0.75rem; margin-bottom: 1rem; }
            .supplier-block h6 { font-size: 0.9rem; }
            .supplier-block .d-flex.justify-content-between { flex-direction: column; gap: 0.25rem; }
            .supplier-block .d-flex.justify-content-between .flex-grow-1 { width: 100%; }
            .supplier-block .d-flex.justify-content-between div[style*="min-width"] { min-width: 100% !important; margin-top: 4px; }
            .supplier-item-entry .item-price-input { width: 100%; margin-top: 4px; }
            .supplier-item-entry .item-info { font-size: 0.72rem; }
            input, select, textarea { font-size: 16px !important; }
            #quotationMap { padding: 0.4rem; margin: 0 -0.5rem; border-radius: 4px; }
            #quotationMap table th { font-size: 0.6rem; padding: 0.25rem 0.3rem; }
            #quotationMap table td { font-size: 0.65rem; padding: 0.2rem 0.3rem; }
            .map-price-input { font-size: 14px !important; padding: 3px 4px; min-width: 65px; }
            #mapFinancials .accordion-button { font-size: 0.78rem; padding: 0.4rem 0.6rem; }
            #mapFinancials .accordion-body { padding: 0.5rem !important; }
            #mapFinancials .form-control-sm { font-size: 14px !important; padding: 0.25rem 0.4rem; }
            #mapFinancials .form-label { font-size: 0.65rem !important; }
            .view-toggle-wrap .btn-group { width: 100%; }
            .price-mode-wrap { flex-direction: column; align-items: stretch !important; }
            .price-mode-wrap .btn-group { width: 100%; }
            .price-mode-wrap span { margin-bottom: 4px; }
            .history-hint { font-size: 0.65rem; }
            /* Seção de serviço - PDF e materiais */
            .svc-pdf-section .form-control-sm { font-size: 14px !important; }
            .svc-pdf-section .btn-sm { font-size: 0.8rem; padding: 0.3rem 0.5rem; }
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
                    <div class="d-flex gap-2 align-items-center">
                        <?php if (($order['order_type'] ?? 'material') === 'service'): ?>
                        <span class="badge bg-success p-2"><i class="bi bi-wrench"></i> Serviço</span>
                        <?php endif; ?>
                        <span class="badge bg-warning text-dark p-2">Aguardando Cotação</span>
                    </div>
                </div>
                <?php if (!empty($order['description'])): ?>
                <div class="mt-2 p-2 bg-white rounded small">
                    <strong>Obs:</strong> <?= nl2br(htmlspecialchars($order['description'])) ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($order['construction_site_name'])): ?>
                <div class="mt-2 p-2 bg-white rounded small">
                    <i class="bi bi-buildings"></i> <strong>Obra:</strong> <?= htmlspecialchars($order['construction_site_code'] . ' - ' . $order['construction_site_name']) ?>
                    <?php if (!empty($order['construction_site_address'])): ?>
                    <span class="text-muted ms-2"><?= htmlspecialchars($order['construction_site_address']) ?><?= !empty($order['construction_site_city']) ? ' - ' . $order['construction_site_city'] . '/' . ($order['construction_site_state'] ?? '') : '' ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if (!empty($comments)): ?>
            <!-- Conversas (fora do quoteForm) -->
            <div class="card-body py-3 border-bottom bg-warning bg-opacity-10">
                <h6 class="small fw-bold mb-2"><i class="bi bi-chat-dots"></i> Perguntas/Observações sobre este pedido</h6>
                <?php foreach ($comments as $c): ?>
                <div class="p-2 mb-1 rounded <?= $c['author_role'] === 'approver' ? 'bg-light border-start border-warning border-3' : 'bg-white border-start border-info border-3' ?>" style="font-size:0.8rem;">
                    <strong><?= htmlspecialchars($c['author_name']) ?></strong>
                    <span class="text-muted small">(<?= $c['author_role'] === 'approver' ? 'Aprovação' : 'Cotação' ?>) · <?= date('d/m H:i', strtotime($c['created_at'])) ?></span>
                    <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($c['message'])) ?></p>
                </div>
                <?php endforeach; ?>
                <!-- Responder -->
                <div class="mt-2 pt-2 border-top">
                    <form method="POST" action="/pedido/cotacao/comentario/<?= $token ?>" class="d-flex gap-2 align-items-end flex-wrap" onsubmit="this.querySelector('[name=person_name]').value = document.querySelector('[name=quoted_by_name]').value || 'Cotador';">
                        <input type="hidden" name="person_name" value="">
                        <div class="flex-grow-1">
                            <textarea class="form-control form-control-sm" name="comment_message" rows="2" placeholder="Responder à pergunta..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-info"><i class="bi bi-send"></i> Responder</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" action="/pedido/cotacao/enviar/<?= $token ?>" id="quoteForm">
                <div class="card-body p-3 p-md-4">
                    <!-- Identificação -->
                    <h6 class="mb-3"><i class="bi bi-person"></i> Identificação</h6>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Seu Nome *</label>
                            <input type="text" class="form-control" name="quoted_by_name" required placeholder="Informe seu nome completo" value="<?= htmlspecialchars($pinUser['name'] ?? $order['quoted_by_name'] ?? '') ?>" <?= !empty($pinUser) ? 'readonly' : '' ?>>
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
                    <?php if (($order['order_type'] ?? 'material') === 'service'): ?>
                    <div class="alert alert-success small py-2 mb-3">
                        <i class="bi bi-wrench"></i> <strong>Pedido de Serviço:</strong> Para cada fornecedor, você pode fazer upload de um PDF com a lista de materiais necessários para o serviço. A IA irá identificar os itens automaticamente.
                    </div>
                    <?php endif; ?>
                    <p class="text-muted small mb-2">Adicione os fornecedores e informe os valores de cada um.</p>
                    
                    <!-- Toggle Unitário / Total -->
                    <div class="mb-3 d-flex align-items-center gap-2 flex-wrap price-mode-wrap">
                        <span class="small text-muted">Informar preço por:</span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" id="btnModeUnit" onclick="setPriceMode('unit')">Unitário</button>
                            <button type="button" class="btn btn-outline-secondary active" id="btnModeTotal" onclick="setPriceMode('total')">Total do item</button>
                        </div>
                    </div>
                    
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

                    <!-- Toggle de visualização -->
                    <div class="d-none mb-3 view-toggle-wrap" id="viewToggle">
                        <div class="btn-group btn-group-sm w-100">
                            <button type="button" class="btn btn-outline-secondary active" id="btnViewList" onclick="setView('list')"><i class="bi bi-list"></i> Lista</button>
                            <button type="button" class="btn btn-outline-secondary" id="btnViewMap" onclick="setView('map')"><i class="bi bi-table"></i> Mapa</button>
                        </div>
                    </div>

                    <!-- Modo Mapa de Cotações (desktop - colunas lado a lado) -->
                    <div id="quotationMap" class="d-none mb-3">
                        <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                        <table class="table table-sm table-bordered mb-0" id="mapTable" style="min-width:500px;">
                            <thead id="mapHead"></thead>
                            <tbody id="mapBody"></tbody>
                            <tfoot id="mapFoot"></tfoot>
                        </table>
                        </div>
                        <!-- Financeiros por fornecedor (colapsável) no mapa -->
                        <div id="mapFinancials" class="mt-2"></div>
                    </div>

                    <!-- Observações -->
                    <div class="mt-3">
                        <label class="form-label">Observações da Cotação</label>
                        <textarea class="form-control" name="quote_notes" rows="2" placeholder="Observações sobre preços, prazos, condições de pagamento, etc."></textarea>
                    </div>
                </div>

                <div class="card-footer p-3 p-md-4 text-center">
                    <button type="button" class="btn btn-success btn-lg px-5" id="submitBtn" disabled onclick="showReviewModal()">
                        <i class="bi bi-check-lg"></i> Revisar e Enviar
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
    const orderType = '<?= $order['order_type'] ?? 'material' ?>';
    const orderId = <?= (int)$order['id'] ?>;
    const allMaterials = <?= json_encode($allMaterials ?? []) ?>;
    let supplierCount = 0;

    // ─── Helpers de moeda BRL ────────────────────────────────────────────────
    // Aceita: "4997,85" | "4.997,85" | "4997.85" | "4,997.85" | "4997" | "5"
    function parseBRL(raw) {
        if (raw === null || raw === undefined) return null;
        let s = String(raw).trim();
        if (s === '' || s === '0,00' || s === '0.00') return 0;

        // Detecta o formato: se tem vírgula E ponto
        const hasDot   = s.includes('.');
        const hasComma = s.includes(',');

        if (hasDot && hasComma) {
            // Descobrir qual é separador decimal: o que vem DEPOIS
            const dotIdx   = s.lastIndexOf('.');
            const commaIdx = s.lastIndexOf(',');
            if (commaIdx > dotIdx) {
                // "4.997,85" → ponto=milhar, vírgula=decimal
                s = s.replace(/\./g, '').replace(',', '.');
            } else {
                // "4,997.85" → vírgula=milhar, ponto=decimal
                s = s.replace(/,/g, '');
            }
        } else if (hasComma) {
            // Só vírgula: pode ser decimal ("4997,85") ou milhar ("4,997")
            const parts = s.split(',');
            if (parts.length === 2 && parts[1].length <= 2) {
                // Decimal: "4997,85" → "4997.85"
                s = s.replace(',', '.');
            } else {
                // Milhar: "4,997" → "4997"
                s = s.replace(/,/g, '');
            }
        }
        // Só ponto: pode ser decimal ("4997.85") ou milhar ("4.997")
        else if (hasDot) {
            const parts = s.split('.');
            if (parts.length === 2 && parts[1].length <= 2) {
                // Decimal: "4997.85" → já ok
            } else {
                // Milhar: "4.997" → "4997"
                s = s.replace(/\./g, '');
            }
        }

        const num = parseFloat(s);
        return isNaN(num) ? null : num;
    }

    function formatBRL(num) {
        return num.toFixed(2).replace('.', ',');
    }
    // ────────────────────────────────────────────────────────────────────────

    // ─── Modo de entrada de preço (unitário vs total) ────────────────────────
    let priceMode = 'total'; // 'unit' ou 'total' — padrão: total

    function setPriceMode(mode) {
        const oldMode = priceMode;
        priceMode = mode;
        document.getElementById('btnModeUnit').classList.toggle('active', mode === 'unit');
        document.getElementById('btnModeTotal').classList.toggle('active', mode === 'total');

        // Converter valores exibidos nos inputs existentes
        document.querySelectorAll('.price-input').forEach(input => {
            const currentVal = parseBRL(input.value);
            if (currentVal === null || currentVal === 0) return;
            const qty = parseFloat(input.dataset.qty) || 1;

            if (oldMode === 'unit' && mode === 'total') {
                // Unitário → Total: multiplica pela quantidade
                input.value = formatBRL(currentVal * qty);
            } else if (oldMode === 'total' && mode === 'unit') {
                // Total → Unitário: divide pela quantidade
                input.value = formatBRL(currentVal / qty);
            }
        });

        // Converter valores no mapa também
        document.querySelectorAll('.map-price-input').forEach(input => {
            const currentVal = parseBRL(input.value);
            if (currentVal === null || currentVal === 0) return;
            const sid = input.dataset.sid;
            const itemId = input.dataset.item;
            const item = items.find(i => String(i.id) === String(itemId));
            const qty = item ? parseFloat(item.quantity) || 1 : 1;

            if (oldMode === 'unit' && mode === 'total') {
                input.value = formatBRL(currentVal * qty);
            } else if (oldMode === 'total' && mode === 'unit') {
                input.value = formatBRL(currentVal / qty);
            }

            // Sync com a lista
            const listInput = document.querySelector(`#supplier-block-${sid} [name="supplier_prices[${sid}][${itemId}]"]`);
            if (listInput) listInput.value = input.value;
        });

        // Atualizar labels
        updatePriceLabels();
        // Recalcular totais
        addedSuppliers.forEach(sid => calculateSupplierTotal(sid));
    }

    function updatePriceLabels() {
        document.querySelectorAll('.price-input-label').forEach(el => {
            el.textContent = priceMode === 'total' ? 'Total' : 'R$';
        });
    }

    // Retorna o unitário a partir do valor digitado (seja unitário ou total)
    function getUnitPrice(inputValue, qty) {
        const val = parseBRL(inputValue) || 0;
        if (priceMode === 'total') {
            return qty > 0 ? val / qty : val;
        }
        return val;
    }

    // Retorna o total a partir do valor digitado
    function getTotalPrice(inputValue, qty) {
        const val = parseBRL(inputValue) || 0;
        if (priceMode === 'total') {
            return val;
        }
        return val * qty;
    }

    // Valida limite de 100% nos campos de desconto/acréscimo
    function validatePercentLimit(input) {
        const name = input.name || '';
        if (!name.includes('discount_value') && !name.includes('surcharge_value')) return;
        
        // Encontrar o select de tipo correspondente
        const group = input.closest('.input-group');
        if (!group) return;
        const typeSelect = group.querySelector('select');
        if (!typeSelect || typeSelect.value !== 'percent') return;
        
        const val = parseBRL(input.value) || 0;
        if (val > 100) {
            input.value = '100';
            input.classList.add('is-invalid');
            setTimeout(() => input.classList.remove('is-invalid'), 2000);
        }
    }
    // ────────────────────────────────────────────────────────────────────────
    let addedSuppliers = [];
    let supplierNames = {};

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
        supplierNames[sid] = name;
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
                if (lastPrice.vendor_name) histHint += ` | Vend: ${lastPrice.vendor_name}`;
                if (lastPrice.delivery_days) histHint += ` | Prazo: ${lastPrice.delivery_days}d`;
                histHint += '</span>';
            }
            
            itemsHtml += `
                <div class="supplier-item-entry py-2 border-bottom">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-1">
                        <div class="item-info">
                            <span class="small">${item.material_name}</span>
                            <span class="text-muted small">(x${item.quantity} ${item.unit || ''})</span>
                            ${histHint}
                        </div>
                        <div class="item-price-input">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text price-input-label">${priceMode === 'total' ? 'Total' : 'R$'}</span>
                                <input type="text" inputmode="decimal" class="form-control price-input" 
                                    name="supplier_prices[${sid}][${item.id}]" placeholder="0,00" required
                                    data-qty="${item.quantity}" data-sid="${sid}" data-item-id="${item.id}">
                            </div>
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
            
            <!-- Vendedor e prazo -->
            <div class="row g-2 mb-3 p-2 bg-light rounded">
                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted mb-0">Vendedor</label>
                    <input type="text" class="form-control form-control-sm" name="supplier_vendor[${sid}][name]" placeholder="Nome do vendedor">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-0">Tel. vendedor</label>
                    <input type="text" inputmode="tel" class="form-control form-control-sm" name="supplier_vendor[${sid}][phone]" placeholder="(11) 99999-9999">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-0">E-mail vendedor</label>
                    <input type="email" class="form-control form-control-sm" name="supplier_vendor[${sid}][email]" placeholder="email@loja.com">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-0">Prazo (dias)</label>
                    <input type="number" inputmode="numeric" class="form-control form-control-sm" name="supplier_vendor[${sid}][delivery_days]" placeholder="0" min="0">
                </div>
            </div>

            ${orderType === 'service' ? `
            <!-- Upload PDF de Materiais (Serviço) -->
            <div class="mb-3 p-2 border rounded bg-warning bg-opacity-10 svc-pdf-section">
                <h6 class="small fw-bold mb-2"><i class="bi bi-file-earmark-pdf text-danger"></i> PDF de Materiais do Prestador</h6>
                <p class="text-muted small mb-2">Faça upload do PDF com a lista de materiais do prestador de serviço.</p>
                <div class="d-flex gap-2 align-items-center mb-2 flex-wrap">
                    <input type="file" class="form-control form-control-sm flex-grow-1" id="servicePdf-${sid}" accept=".pdf,.jpg,.jpeg,.png,.webp" style="min-width:0;">
                    <button type="button" class="btn btn-sm btn-outline-primary flex-shrink-0" onclick="parseServicePdf('${sid}')">
                        <i class="bi bi-magic"></i> Analisar
                    </button>
                </div>
                <div id="servicePdfStatus-${sid}" style="display:none;"></div>
                <div id="serviceMaterialsList-${sid}" style="display:none;"></div>
            </div>
            ` : ''}
            <!-- Preços por item -->
            ${itemsHtml}
            
            <!-- Ajustes financeiros -->
            <div class="mt-3 pt-3 border-top">
                <div class="row g-2">
                    <div class="col-6 col-md-4">
                        <label class="form-label small text-muted mb-0">Desconto</label>
                        <div class="input-group input-group-sm">
                            <input type="text" inputmode="decimal" class="form-control" name="supplier_financials[${sid}][discount_value]" placeholder="0" data-sid="${sid}">
                            <select class="form-select" name="supplier_financials[${sid}][discount_type]" style="max-width:70px;">
                                <option value="percent">%</option>
                                <option value="fixed">R$</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label small text-muted mb-0">Acréscimo</label>
                        <div class="input-group input-group-sm">
                            <input type="text" inputmode="decimal" class="form-control" name="supplier_financials[${sid}][surcharge_value]" placeholder="0" data-sid="${sid}">
                            <select class="form-select" name="supplier_financials[${sid}][surcharge_type]" style="max-width:70px;">
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
                <!-- Forma de pagamento -->
                <div class="row g-2 mt-2">
                    <div class="col-6 col-md-3">
                        <label class="form-label small text-muted mb-0">Forma de Pgto</label>
                        <select class="form-select form-select-sm" name="supplier_vendor[${sid}][payment_method]">
                            <option value="">-- Selecione --</option>
                            <option value="pix">PIX</option>
                            <option value="boleto">Boleto</option>
                            <option value="cartao">Cartão</option>
                            <option value="transferencia">Transferência</option>
                            <option value="dinheiro">Dinheiro</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small text-muted mb-0">Condição</label>
                        <input type="text" class="form-control form-control-sm" name="supplier_vendor[${sid}][payment_condition]" placeholder="à vista, 30/60/90...">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small text-muted mb-0">1ª parcela</label>
                        <input type="date" class="form-control form-control-sm" name="supplier_vendor[${sid}][payment_first_due]">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small text-muted mb-0">Obs pgto</label>
                        <input type="text" class="form-control form-control-sm" name="supplier_vendor[${sid}][payment_notes]" placeholder="Observações...">
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
                const parsed = parseBRL(this.value);
                if (parsed !== null) this.value = formatBRL(parsed);
                // Validar limite de % para desconto/acréscimo
                validatePercentLimit(this);
                calculateSupplierTotal(sid);
            });
        });
        // Validar ao trocar tipo (% ↔ R$)
        block.querySelectorAll('select[name*="discount_type"], select[name*="surcharge_type"]').forEach(sel => {
            sel.addEventListener('change', function() {
                const valueInput = this.closest('.input-group').querySelector('input');
                if (valueInput) validatePercentLimit(valueInput);
                calculateSupplierTotal(sid);
            });
        });
    }

    function removeSupplierBlock(sid) {
        document.getElementById('supplier-block-' + sid)?.remove();
        addedSuppliers = addedSuppliers.filter(s => s !== sid);
        delete supplierNames[sid];
        if (addedSuppliers.length === 0) document.getElementById('submitBtn').disabled = true;
    }

    function calculateSupplierTotal(sid) {
        const block = document.getElementById('supplier-block-' + sid);
        if (!block) return;

        let subtotalItems = 0;
        block.querySelectorAll('.price-input').forEach(input => {
            const qty = parseFloat(input.dataset.qty) || 0;
            subtotalItems += getTotalPrice(input.value, qty);
        });

        // Financeiros
        const getVal = (name) => parseBRL(block.querySelector(`[name="supplier_financials[${sid}][${name}]"]`)?.value) || 0;
        const getType = (name) => block.querySelector(`[name="supplier_financials[${sid}][${name}]"]`)?.value || 'percent';
        
        const discountVal = getVal('discount_value');
        const discountType = getType('discount_type');
        const surchargeVal = getVal('surcharge_value');
        const surchargeType = getType('surcharge_type');
        const ipi = getVal('ipi_percent');
        const icms = getVal('icms_percent');
        const freight = getVal('freight');

        let total = subtotalItems;
        
        if (discountType === 'percent') total -= subtotalItems * (discountVal / 100);
        else total -= discountVal;
        
        if (surchargeType === 'percent') total += subtotalItems * (surchargeVal / 100);
        else total += surchargeVal;
        
        total += subtotalItems * (ipi / 100);
        total += subtotalItems * (icms / 100);
        total += freight;

        document.getElementById('subtotal-items-' + sid).textContent = 'R$ ' + formatBRL(subtotalItems);
        document.getElementById('subtotal-final-' + sid).textContent = 'R$ ' + formatBRL(total);
    }

    // --- Modo de visualização (Lista vs Mapa) ---
    let currentView = 'list';

    function updateViewToggle() {
        const toggle = document.getElementById('viewToggle');

        if (addedSuppliers.length >= 1) {
            toggle.classList.remove('d-none');
            // Se tem 2+ fornecedores e está em lista, mudar para mapa automaticamente
            if (addedSuppliers.length >= 2 && currentView === 'list') {
                setView('map');
            }
        } else {
            toggle.classList.add('d-none');
            if (currentView === 'map') setView('list');
        }
    }

    function setView(mode) {
        currentView = mode;
        document.getElementById('btnViewList').classList.toggle('active', mode === 'list');
        document.getElementById('btnViewMap').classList.toggle('active', mode === 'map');
        
        if (mode === 'map') {
            document.getElementById('suppliersContainer').style.display = 'none';
            document.getElementById('quotationMap').classList.remove('d-none');
            renderMap();
        } else {
            document.getElementById('suppliersContainer').style.display = '';
            document.getElementById('quotationMap').classList.add('d-none');
        }
    }

    function renderMap() {
        if (addedSuppliers.length === 0) return;

        // Header
        let headHtml = '<tr class="table-dark"><th style="min-width:160px; position:sticky; left:0; background:#212529; z-index:2;">Material</th><th class="text-center" style="width:50px;">Qtd</th>';
        addedSuppliers.forEach(sid => {
            const name = supplierNames[sid] || 'Fornecedor';
            headHtml += `<th class="text-center" style="min-width:110px;">${name}</th>`;
        });
        headHtml += '</tr>';
        document.getElementById('mapHead').innerHTML = headHtml;

        // Body
        let bodyHtml = '';
        items.forEach(item => {
            bodyHtml += `<tr><td style="position:sticky; left:0; background:#fff; z-index:1;"><strong style="font-size:0.75rem;">${item.material_name}</strong></td>`;
            bodyHtml += `<td class="text-center">${item.quantity}</td>`;
            addedSuppliers.forEach(sid => {
                const input = document.querySelector(`#supplier-block-${sid} [name="supplier_prices[${sid}][${item.id}]"]`);
                const val = input ? input.value : '';
                bodyHtml += `<td class="text-center"><input type="text" inputmode="decimal" class="form-control form-control-sm text-center map-price-input" data-sid="${sid}" data-item="${item.id}" value="${val}" placeholder="0,00" style="font-size:0.85rem;"></td>`;
            });
            bodyHtml += '</tr>';
        });
        document.getElementById('mapBody').innerHTML = bodyHtml;

        // Footer com totais dos fornecedores
        renderMapFooter();

        // Render financials section
        renderMapFinancials();

        // Bind inputs do mapa para sincronizar com os inputs ocultos da lista
        document.querySelectorAll('.map-price-input').forEach(input => {
            input.addEventListener('input', function() {
                const sid = this.dataset.sid;
                const itemId = this.dataset.item;
                const listInput = document.querySelector(`#supplier-block-${sid} [name="supplier_prices[${sid}][${itemId}]"]`);
                if (listInput) {
                    listInput.value = this.value;
                    calculateSupplierTotal(sid);
                }
                setTimeout(renderMapFooter, 100);
            });
            input.addEventListener('blur', function() {
                const parsed = parseBRL(this.value);
                if (parsed !== null) {
                    this.value = formatBRL(parsed);
                    // Sync com lista também no blur
                    const sid = this.dataset.sid;
                    const itemId = this.dataset.item;
                    const listInput = document.querySelector(`#supplier-block-${sid} [name="supplier_prices[${sid}][${itemId}]"]`);
                    if (listInput) {
                        listInput.value = this.value;
                        calculateSupplierTotal(sid);
                    }
                }
                setTimeout(renderMapFooter, 150);
            });
        });
    }

    function renderMapFinancials() {
        const container = document.getElementById('mapFinancials');
        let html = '<div class="accordion accordion-flush" id="mapFinancialsAccordion">';
        
        addedSuppliers.forEach((sid, index) => {
            const name = supplierNames[sid] || 'Fornecedor';
            const block = document.getElementById('supplier-block-' + sid);
            
            // Lê valores atuais dos campos financeiros da lista (hidden)
            const getFieldVal = (field) => block ? (block.querySelector(`[name="supplier_financials[${sid}][${field}]"]`)?.value || '') : '';
            const getVendorVal = (field) => block ? (block.querySelector(`[name="supplier_vendor[${sid}][${field}]"]`)?.value || '') : '';
            
            html += `
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button py-2" type="button" data-bs-toggle="collapse" data-bs-target="#mapFin${sid}">
                        <small><i class="bi bi-building"></i> ${name} - Vendedor / Financeiro</small>
                    </button>
                </h2>
                <div id="mapFin${sid}" class="accordion-collapse collapse show">
                    <div class="accordion-body p-2">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-0">Vendedor</label>
                                <input type="text" class="form-control form-control-sm map-vendor-field" data-sid="${sid}" data-field="name" value="${getVendorVal('name')}" placeholder="Nome do vendedor">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted mb-0">Tel. vendedor</label>
                                <input type="text" class="form-control form-control-sm map-vendor-field" data-sid="${sid}" data-field="phone" value="${getVendorVal('phone')}" placeholder="(11) 99999-9999">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-0">E-mail vendedor</label>
                                <input type="email" class="form-control form-control-sm map-vendor-field" data-sid="${sid}" data-field="email" value="${getVendorVal('email')}" placeholder="email@loja.com">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted mb-0">Prazo (dias)</label>
                                <input type="number" class="form-control form-control-sm map-vendor-field" data-sid="${sid}" data-field="delivery_days" value="${getVendorVal('delivery_days')}" placeholder="0" min="0">
                            </div>
                        </div>
                        <div class="row g-2 mt-1">
                            <div class="col-4 col-md-2">
                                <label class="form-label small text-muted mb-0">Desconto</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control map-fin-field" data-sid="${sid}" data-field="discount_value" value="${getFieldVal('discount_value')}" placeholder="0">
                                    <select class="form-select map-fin-select" data-sid="${sid}" data-field="discount_type" style="max-width:70px;">
                                        <option value="percent" ${getFieldVal('discount_type') !== 'fixed' ? 'selected' : ''}>%</option>
                                        <option value="fixed" ${getFieldVal('discount_type') === 'fixed' ? 'selected' : ''}>R$</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small text-muted mb-0">Acréscimo</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control map-fin-field" data-sid="${sid}" data-field="surcharge_value" value="${getFieldVal('surcharge_value')}" placeholder="0">
                                    <select class="form-select map-fin-select" data-sid="${sid}" data-field="surcharge_type" style="max-width:70px;">
                                        <option value="percent" ${getFieldVal('surcharge_type') !== 'fixed' ? 'selected' : ''}>%</option>
                                        <option value="fixed" ${getFieldVal('surcharge_type') === 'fixed' ? 'selected' : ''}>R$</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small text-muted mb-0">IPI %</label>
                                <input type="text" class="form-control form-control-sm map-fin-field" data-sid="${sid}" data-field="ipi_percent" value="${getFieldVal('ipi_percent')}" placeholder="0">
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small text-muted mb-0">ICMS %</label>
                                <input type="text" class="form-control form-control-sm map-fin-field" data-sid="${sid}" data-field="icms_percent" value="${getFieldVal('icms_percent')}" placeholder="0">
                            </div>
                            <div class="col-4 col-md-2">
                                <label class="form-label small text-muted mb-0">Frete (R$)</label>
                                <input type="text" class="form-control form-control-sm map-fin-field" data-sid="${sid}" data-field="freight" value="${getFieldVal('freight')}" placeholder="0,00">
                            </div>
                        </div>
                        <div class="row g-2 mt-1">
                            <div class="col-6 col-md-3">
                                <label class="form-label small text-muted mb-0">Forma de Pgto</label>
                                <select class="form-select form-select-sm map-vendor-field" data-sid="${sid}" data-field="payment_method">
                                    <option value="">-- Selecione --</option>
                                    <option value="pix" ${getVendorVal('payment_method')==='pix'?'selected':''}>PIX</option>
                                    <option value="boleto" ${getVendorVal('payment_method')==='boleto'?'selected':''}>Boleto</option>
                                    <option value="cartao" ${getVendorVal('payment_method')==='cartao'?'selected':''}>Cartão</option>
                                    <option value="transferencia" ${getVendorVal('payment_method')==='transferencia'?'selected':''}>Transferência</option>
                                    <option value="dinheiro" ${getVendorVal('payment_method')==='dinheiro'?'selected':''}>Dinheiro</option>
                                    <option value="outro" ${getVendorVal('payment_method')==='outro'?'selected':''}>Outro</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small text-muted mb-0">Condição</label>
                                <input type="text" class="form-control form-control-sm map-vendor-field" data-sid="${sid}" data-field="payment_condition" value="${getVendorVal('payment_condition')}" placeholder="à vista, 30/60/90...">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small text-muted mb-0">1ª parcela</label>
                                <input type="date" class="form-control form-control-sm map-vendor-field" data-sid="${sid}" data-field="payment_first_due" value="${getVendorVal('payment_first_due')}">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small text-muted mb-0">Obs pgto</label>
                                <input type="text" class="form-control form-control-sm map-vendor-field" data-sid="${sid}" data-field="payment_notes" value="${getVendorVal('payment_notes')}" placeholder="Observações...">
                            </div>
                        </div>
                        ${orderType === 'service' ? `
                        <!-- Upload PDF de Materiais (Serviço) - Mapa -->
                        <div class="mt-2 pt-2 border-top svc-pdf-section">
                            <h6 class="small fw-bold mb-2"><i class="bi bi-file-earmark-pdf text-danger"></i> PDF de Materiais do Prestador</h6>
                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                <input type="file" class="form-control form-control-sm flex-grow-1" id="servicePdfMap-${sid}" accept=".pdf,.jpg,.jpeg,.png,.webp" style="min-width:0;">
                                <button type="button" class="btn btn-sm btn-outline-primary flex-shrink-0" onclick="parseServicePdfFromMap('${sid}')">
                                    <i class="bi bi-magic"></i> Analisar
                                </button>
                            </div>
                            <div id="servicePdfStatusMap-${sid}" class="mt-2" style="display:none;"></div>
                            <div id="serviceMaterialsListMap-${sid}" class="mt-2" style="display:none;"></div>
                        </div>
                        ` : ''}
                    </div>
                </div>
            </div>`;
        });
        
        html += '</div>';
        container.innerHTML = html;

        // Bind syncs: map financials → list financials
        container.querySelectorAll('.map-fin-field').forEach(input => {
            input.addEventListener('input', function() {
                const sid = this.dataset.sid;
                const field = this.dataset.field;
                const listInput = document.querySelector(`#supplier-block-${sid} [name="supplier_financials[${sid}][${field}]"]`);
                if (listInput) {
                    listInput.value = this.value;
                    calculateSupplierTotal(sid);
                    setTimeout(renderMapFooter, 100);
                }
            });
            input.addEventListener('blur', function() {
                // Validar % nos campos de desconto/acréscimo do mapa
                const field = this.dataset.field;
                if (field === 'discount_value' || field === 'surcharge_value') {
                    const sid = this.dataset.sid;
                    const typeField = field.replace('_value', '_type');
                    const typeSelect = container.querySelector(`.map-fin-select[data-sid="${sid}"][data-field="${typeField}"]`);
                    if (typeSelect && typeSelect.value === 'percent') {
                        const val = parseBRL(this.value) || 0;
                        if (val > 100) {
                            this.value = '100';
                            this.classList.add('is-invalid');
                            setTimeout(() => this.classList.remove('is-invalid'), 2000);
                            // Sync
                            const listInput = document.querySelector(`#supplier-block-${sid} [name="supplier_financials[${sid}][${field}]"]`);
                            if (listInput) listInput.value = '100';
                            calculateSupplierTotal(sid);
                            setTimeout(renderMapFooter, 100);
                        }
                    }
                }
            });
        });
        container.querySelectorAll('.map-fin-select').forEach(select => {
            select.addEventListener('change', function() {
                const sid = this.dataset.sid;
                const field = this.dataset.field;
                const listSelect = document.querySelector(`#supplier-block-${sid} [name="supplier_financials[${sid}][${field}]"]`);
                if (listSelect) {
                    listSelect.value = this.value;
                    calculateSupplierTotal(sid);
                    setTimeout(renderMapFooter, 100);
                }
            });
        });
        container.querySelectorAll('.map-vendor-field').forEach(input => {
            input.addEventListener('input', function() {
                const sid = this.dataset.sid;
                const field = this.dataset.field;
                const listInput = document.querySelector(`#supplier-block-${sid} [name="supplier_vendor[${sid}][${field}]"]`);
                if (listInput) listInput.value = this.value;
            });
        });
    }

    function renderMapFooter() {
        let footHtml = '<tr class="table-light fw-bold"><td style="position:sticky; left:0; background:#f8f9fa; z-index:1;">TOTAL</td><td></td>';
        addedSuppliers.forEach(sid => {
            const totalEl = document.getElementById('subtotal-final-' + sid);
            footHtml += `<td class="text-center text-success">${totalEl ? totalEl.textContent : 'R$ 0,00'}</td>`;
        });
        footHtml += '</tr>';
        document.getElementById('mapFoot').innerHTML = footHtml;
    }

    // Observar mudanças no container de fornecedores para atualizar toggle/mapa
    new MutationObserver(() => {
        updateViewToggle();
        if (currentView === 'map') setTimeout(renderMap, 200);
    }).observe(document.getElementById('suppliersContainer'), { childList: true });

    // Auto-carregar fornecedores existentes (quando pedido já foi cotado e está sendo editado)
    <?php if ($order['status'] === 'pending_approval' && !empty($orderSuppliers)): ?>
    (function() {
        const existingPrices = <?= json_encode($itemPrices ?? []) ?>;
        const pricesBySupplier = {};
        existingPrices.forEach(p => {
            if (!pricesBySupplier[p.supplier_id]) pricesBySupplier[p.supplier_id] = {};
            pricesBySupplier[p.supplier_id][p.item_id] = p.unit_price;
        });

        <?php foreach ($orderSuppliers as $os): ?>
        // Adicionar fornecedor <?= $os['supplier_name'] ?>
        addedSuppliers.push('<?= $os['supplier_id'] ?>');
        addSupplierBlock('<?= $os['supplier_id'] ?>', '<?= htmlspecialchars($os['supplier_name']) ?>');
        
        // Preencher preços
        setTimeout(function() {
            const block = document.getElementById('supplier-block-<?= $os['supplier_id'] ?>');
            if (!block) return;
            const prices = pricesBySupplier['<?= $os['supplier_id'] ?>'] || {};
            for (const itemId in prices) {
                const input = block.querySelector('[name="supplier_prices[<?= $os['supplier_id'] ?>][' + itemId + ']"]');
                if (input) {
                    // Converter formato do banco (100.00) para formato BR (100,00)
                    const numVal = parseFloat(prices[itemId]) || 0;
                    input.value = numVal.toFixed(2).replace('.', ',');
                    input.dispatchEvent(new Event('input'));
                }
            }
            // Preencher vendedor
            const vName = block.querySelector('[name*="[name]"]'); if (vName) vName.value = '<?= htmlspecialchars($os['vendor_name'] ?? '') ?>';
            const vPhone = block.querySelector('[name*="[phone]"]'); if (vPhone) vPhone.value = '<?= htmlspecialchars($os['vendor_phone'] ?? '') ?>';
            const vEmail = block.querySelector('[name*="[email]"]'); if (vEmail) vEmail.value = '<?= htmlspecialchars($os['vendor_email'] ?? '') ?>';
            const vDays = block.querySelector('[name*="[delivery_days]"]'); if (vDays) vDays.value = '<?= $os['delivery_days'] ?? '' ?>';
            const vPm = block.querySelector('[name*="[payment_method]"]'); if (vPm) vPm.value = '<?= $os['payment_method'] ?? '' ?>';
            const vPc = block.querySelector('[name*="[payment_condition]"]'); if (vPc) vPc.value = '<?= htmlspecialchars($os['payment_condition'] ?? '') ?>';
            const vPd = block.querySelector('[name*="[payment_first_due]"]'); if (vPd) vPd.value = '<?= $os['payment_first_due'] ?? '' ?>';
            const vPn = block.querySelector('[name*="[payment_notes]"]'); if (vPn) vPn.value = '<?= htmlspecialchars($os['payment_notes'] ?? '') ?>';
            // Financeiros
            <?php if ($os['discount_value'] > 0): ?>
            const dv = block.querySelector('[name*="[discount_value]"]'); if (dv) dv.value = '<?= str_replace('.', ',', $os['discount_value']) ?>';
            const dt = block.querySelector('[name*="[discount_type]"]'); if (dt) dt.value = '<?= $os['discount_type'] ?? 'percent' ?>';
            <?php endif; ?>
            <?php if ($os['ipi_percent'] > 0): ?>
            const ipi = block.querySelector('[name*="[ipi_percent]"]'); if (ipi) ipi.value = '<?= str_replace('.', ',', $os['ipi_percent']) ?>';
            <?php endif; ?>
            <?php if ($os['icms_percent'] > 0): ?>
            const icms = block.querySelector('[name*="[icms_percent]"]'); if (icms) icms.value = '<?= str_replace('.', ',', $os['icms_percent']) ?>';
            <?php endif; ?>
            <?php if ($os['freight'] > 0): ?>
            const fr = block.querySelector('[name*="[freight]"]'); if (fr) fr.value = '<?= str_replace('.', ',', $os['freight']) ?>';
            <?php endif; ?>
            // Recalcular total
            setTimeout(function() { calculateSupplierTotal('<?= $os['supplier_id'] ?>'); }, 500);
        }, 300);
        <?php endforeach; ?>

        document.getElementById('submitBtn').disabled = false;
    })();
    <?php endif; ?>
    </script>

<!-- Modal de Revisão antes de enviar -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white py-2">
                <h6 class="modal-title"><i class="bi bi-clipboard-check"></i> Revisar Cotação antes de Enviar</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="reviewContent" style="max-height:70vh; overflow-y:auto;">
                <!-- Preenchido via JS -->
            </div>
            <div class="modal-footer py-2 flex-column gap-2">
                <button type="button" class="btn btn-success btn-lg w-100" onclick="confirmSubmit()">
                    <i class="bi bi-send"></i> Confirmar e Enviar para Aprovação
                </button>
                <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">
                    <i class="bi bi-pencil"></i> Voltar e Editar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function showReviewModal() {
    const form = document.getElementById('quoteForm');
    const quotedBy = form.querySelector('[name="quoted_by_name"]')?.value || '-';
    const quoteNotes = form.querySelector('[name="quote_notes"]')?.value || '';

    let html = '<div class="mb-3 p-3 bg-light rounded">';
    html += '<p class="mb-1"><strong>Cotado por:</strong> ' + escHtml(quotedBy) + '</p>';
    if (quoteNotes) html += '<p class="mb-0"><strong>Observações:</strong> ' + escHtml(quoteNotes) + '</p>';
    html += '</div>';

    // Fornecedores e preços
    html += '<h6 class="fw-bold mb-2"><i class="bi bi-building"></i> Fornecedores Cotados</h6>';

    addedSuppliers.forEach(sid => {
        const name = supplierNames[sid] || 'Fornecedor';
        const block = document.getElementById('supplier-block-' + sid);
        if (!block) return;

        // Dados do vendedor
        const vendorName = block.querySelector('[name*="[name]"]')?.value || '';
        const vendorPhone = block.querySelector('[name*="[phone]"]')?.value || '';
        const deliveryDays = block.querySelector('[name*="[delivery_days]"]')?.value || '';
        const paymentMethod = block.querySelector('[name*="[payment_method]"]');
        const paymentMethodText = paymentMethod ? paymentMethod.options[paymentMethod.selectedIndex]?.text || '' : '';
        const paymentCondition = block.querySelector('[name*="[payment_condition]"]')?.value || '';

        // Total
        const totalEl = document.getElementById('subtotal-final-' + sid);
        const totalText = totalEl ? totalEl.textContent : '-';

        html += '<div class="card mb-2"><div class="card-body p-2">';
        html += '<div class="d-flex justify-content-between align-items-center mb-1">';
        html += '<strong>' + escHtml(name) + '</strong>';
        html += '<span class="badge bg-success">' + totalText + '</span>';
        html += '</div>';
        
        // Info do vendedor
        let infoItems = [];
        if (vendorName) infoItems.push('<i class="bi bi-person"></i> ' + escHtml(vendorName));
        if (vendorPhone) infoItems.push('<i class="bi bi-telephone"></i> ' + escHtml(vendorPhone));
        if (deliveryDays) infoItems.push('<i class="bi bi-truck"></i> ' + deliveryDays + ' dias');
        if (paymentMethodText && paymentMethodText !== '-- Selecione --') infoItems.push('<i class="bi bi-credit-card"></i> ' + escHtml(paymentMethodText));
        if (paymentCondition) infoItems.push(escHtml(paymentCondition));
        if (infoItems.length) html += '<div class="small text-muted mb-1">' + infoItems.join(' · ') + '</div>';

        // Preços dos itens
        html += '<table class="table table-sm mb-0" style="font-size:0.75rem;"><thead><tr><th>Material</th><th class="text-end">Unit.</th><th class="text-end">Total</th></tr></thead><tbody>';
        const priceInputs = block.querySelectorAll('.price-input');
        priceInputs.forEach(input => {
            const val = parseBRL(input.value) || 0;
            if (val === 0) return;
            const qty = parseFloat(input.dataset.qty) || 1;
            const itemId = input.dataset.itemId || '';
            const item = items.find(i => String(i.id) === String(itemId));
            const matName = item ? item.material_name : 'Material';
            
            let unitPrice, totalPrice;
            if (priceMode === 'total') {
                totalPrice = val;
                unitPrice = qty > 0 ? val / qty : val;
            } else {
                unitPrice = val;
                totalPrice = val * qty;
            }
            
            html += '<tr><td>' + escHtml(matName) + ' <small class="text-muted">(x' + qty + ')</small></td>';
            html += '<td class="text-end">R$ ' + formatBRL(unitPrice) + '</td>';
            html += '<td class="text-end fw-bold">R$ ' + formatBRL(totalPrice) + '</td></tr>';
        });
        html += '</tbody></table>';
        html += '</div></div>';
    });

    html += '<div class="alert alert-info small mt-3 mb-0"><i class="bi bi-info-circle"></i> Ao confirmar, a cotação será enviada e o pedido seguirá para aprovação. Revise os valores com atenção.</div>';

    document.getElementById('reviewContent').innerHTML = html;
    new bootstrap.Modal(document.getElementById('reviewModal')).show();
}

function confirmSubmit() {
    // Se está no modo "total", converter todos os preços para unitário antes de enviar
    if (priceMode === 'total') {
        document.querySelectorAll('.price-input').forEach(input => {
            const val = parseBRL(input.value) || 0;
            const qty = parseFloat(input.dataset.qty) || 1;
            const unitPrice = qty > 0 ? val / qty : val;
            input.value = formatBRL(unitPrice);
        });
    }
    
    // Se é pedido de serviço, incluir materiais de cada fornecedor no form
    if (orderType === 'service') {
        addedSuppliers.forEach(sid => {
            const rawMats = window['svcMats_' + sid] || [];
            const materialsToSave = [];
            const processedIdx = new Set(); // Evitar duplicatas desktop/mobile
            
            // Pegar materiais selecionados (checked)
            const container = document.getElementById('serviceMaterialsListMap-' + sid) || document.getElementById('serviceMaterialsList-' + sid);
            let checkboxes = [];
            if (container) {
                checkboxes = container.querySelectorAll('input[type="checkbox"][data-idx]:checked');
            }
            if (checkboxes.length === 0) {
                checkboxes = document.querySelectorAll(`input[type="checkbox"][data-sid="${sid}"][data-idx]:checked`);
            }
            
            for (const cb of checkboxes) {
                const idx = parseInt(cb.dataset.idx);
                if (processedIdx.has(idx)) continue; // Já processado (evita duplicata desktop/mobile)
                processedIdx.add(idx);
                
                const raw = rawMats[idx] || {};
                if (raw._removed) continue;
                
                const getName = () => document.getElementById(`svc-name-${sid}-${idx}`)?.value || document.getElementById(`svc-name-m-${sid}-${idx}`)?.value || raw.name;
                const getUnit = () => document.getElementById(`svc-unit-${sid}-${idx}`)?.value || document.getElementById(`svc-unit-m-${sid}-${idx}`)?.value || raw.unit;
                const getQty = () => document.getElementById(`svc-qty-${sid}-${idx}`)?.value || document.getElementById(`svc-qty-m-${sid}-${idx}`)?.value || raw.quantity;
                const getUprice = () => document.getElementById(`svc-uprice-${sid}-${idx}`)?.value || document.getElementById(`svc-uprice-m-${sid}-${idx}`)?.value || raw.unit_price;
                const getTprice = () => document.getElementById(`svc-tprice-${sid}-${idx}`)?.value || document.getElementById(`svc-tprice-m-${sid}-${idx}`)?.value || raw.total_price;
                
                materialsToSave.push({
                    name: getName(),
                    code: raw.code || null,
                    specification: raw.specification || null,
                    classification: raw.classification || null,
                    unit: getUnit(),
                    quantity: parseFloat(getQty()) || 1,
                    unit_price: parseBRL(getUprice()) || raw.unit_price || null,
                    total_price: parseBRL(getTprice()) || raw.total_price || null,
                    material_id: raw.material_id || null,
                });
            }
            
            if (materialsToSave.length > 0) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = `service_materials[${sid}]`;
                hidden.value = JSON.stringify(materialsToSave);
                document.getElementById('quoteForm').appendChild(hidden);
            }
        });
    }
    
    bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();
    document.getElementById('quoteForm').submit();
}

function escHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ─── Serviço: Upload e análise de PDF do fornecedor ─────────────────────────
async function parseServicePdf(sid) {
    const fileInput = document.getElementById('servicePdf-' + sid);
    const statusEl = document.getElementById('servicePdfStatus-' + sid);
    const materialsEl = document.getElementById('serviceMaterialsList-' + sid);
    
    if (!fileInput || !fileInput.files.length) {
        alert('Selecione um arquivo PDF primeiro.');
        return;
    }

    const file = fileInput.files[0];
    statusEl.style.display = 'block';
    materialsEl.style.display = 'none';

    const maxRetries = 3;
    let attempt = 0;
    let data = null;

    while (attempt < maxRetries) {
        attempt++;
        statusEl.innerHTML = `<div class="alert alert-info small py-2 mb-0"><span class="spinner-border spinner-border-sm me-1"></span> Analisando PDF com IA...${attempt > 1 ? ' (tentativa ' + attempt + '/' + maxRetries + ')' : ''}</div>`;

        const formData = new FormData();
        formData.append('pdf', file);
        formData.append('order_id', orderId);
        formData.append('supplier_id', sid);
        formData.append('uploaded_by', document.querySelector('[name="quoted_by_name"]')?.value || 'Cotador');

        try {
            const resp = await fetch('/pedido/cotacao/parse-service-pdf', { method: 'POST', body: formData });
            data = await resp.json();

            if (data.success && data.materials && data.materials.length > 0) {
                break; // Sucesso, sai do loop
            }
            // Se salvou PDF mas não achou materiais, tentar de novo (sem re-upload)
            if (data.success && (!data.materials || data.materials.length === 0) && attempt < maxRetries) {
                await new Promise(r => setTimeout(r, 1000)); // Esperar 1s antes de retry
                continue;
            }
            break; // Não tentar mais
        } catch (e) {
            if (attempt >= maxRetries) {
                statusEl.innerHTML = `<div class="alert alert-danger small py-2 mb-0"><i class="bi bi-x-circle"></i> Erro de conexão após ${maxRetries} tentativas.</div>`;
                fileInput.value = '';
                return;
            }
            await new Promise(r => setTimeout(r, 1000));
        }
    }

    if (data && data.success) {
        let html = '';
        if (data.file_path) {
            html += `<div class="alert alert-success small py-2 mb-2"><i class="bi bi-check-circle"></i> PDF salvo com sucesso. <a href="${data.file_path}" target="_blank" class="fw-bold">Download</a></div>`;
        }
        if (data.warning) {
            html += `<div class="alert alert-warning small py-2 mb-2"><i class="bi bi-exclamation-triangle"></i> ${data.warning}</div>`;
        }
        if (data.materials && data.materials.length > 0) {
            matchServiceMaterials(data.materials);
            html += renderServiceMaterials(sid, data.materials, data.totals, data.pdf_id);
        } else {
            html += `<div class="alert alert-warning small py-2 mb-2"><i class="bi bi-exclamation-triangle"></i> Não foi possível identificar materiais após ${maxRetries} tentativas.</div>`;
            html += `<div class="mt-2"><button type="button" class="btn btn-sm btn-outline-secondary" onclick="showManualServiceMaterial('${sid}', ${data.pdf_id || 0})"><i class="bi bi-plus"></i> Adicionar material manualmente</button></div>`;
        }
        statusEl.innerHTML = '';
        materialsEl.style.display = 'block';
        materialsEl.innerHTML = html;
        if (data.materials && data.materials.length > 0) {
            window['svcMats_' + sid] = data.materials;
            setTimeout(() => bindSvcRecalc(sid), 100);
        }
    } else {
        statusEl.innerHTML = `<div class="alert alert-danger small py-2 mb-0"><i class="bi bi-x-circle"></i> ${data?.error || 'Erro ao processar.'}</div>`;
    }

    fileInput.value = '';
}

// Versão do parse para o modo Mapa
async function parseServicePdfFromMap(sid) {
    const fileInput = document.getElementById('servicePdfMap-' + sid);
    const statusEl = document.getElementById('servicePdfStatusMap-' + sid);
    const materialsEl = document.getElementById('serviceMaterialsListMap-' + sid);
    
    if (!fileInput || !fileInput.files.length) {
        alert('Selecione um arquivo PDF primeiro.');
        return;
    }

    const file = fileInput.files[0];
    statusEl.style.display = 'block';
    materialsEl.style.display = 'none';

    const maxRetries = 3;
    let attempt = 0;
    let data = null;

    while (attempt < maxRetries) {
        attempt++;
        statusEl.innerHTML = `<div class="alert alert-info small py-2 mb-0"><span class="spinner-border spinner-border-sm me-1"></span> Analisando...${attempt > 1 ? ' (tentativa ' + attempt + '/' + maxRetries + ')' : ''}</div>`;

        const formData = new FormData();
        formData.append('pdf', file);
        formData.append('order_id', orderId);
        formData.append('supplier_id', sid);
        formData.append('uploaded_by', document.querySelector('[name="quoted_by_name"]')?.value || 'Cotador');

        try {
            const resp = await fetch('/pedido/cotacao/parse-service-pdf', { method: 'POST', body: formData });
            data = await resp.json();

            if (data.success && data.materials && data.materials.length > 0) break;
            if (data.success && (!data.materials || data.materials.length === 0) && attempt < maxRetries) {
                await new Promise(r => setTimeout(r, 1000));
                continue;
            }
            break;
        } catch (e) {
            if (attempt >= maxRetries) {
                statusEl.innerHTML = `<div class="alert alert-danger small py-2 mb-0"><i class="bi bi-x-circle"></i> Erro após ${maxRetries} tentativas.</div>`;
                fileInput.value = '';
                return;
            }
            await new Promise(r => setTimeout(r, 1000));
        }
    }

    if (data && data.success) {
        let html = '';
        if (data.file_path) html += `<div class="alert alert-success small py-2 mb-2"><i class="bi bi-check-circle"></i> PDF salvo. <a href="${data.file_path}" target="_blank" class="fw-bold">Download</a></div>`;
        if (data.warning) html += `<div class="alert alert-warning small py-2 mb-2"><i class="bi bi-exclamation-triangle"></i> ${data.warning}</div>`;
        if (data.materials && data.materials.length > 0) {
            matchServiceMaterials(data.materials);
            html += renderServiceMaterials(sid, data.materials, data.totals, data.pdf_id);
        } else {
            html += `<div class="alert alert-warning small py-2 mb-2"><i class="bi bi-exclamation-triangle"></i> Não foi possível identificar materiais após ${maxRetries} tentativas.</div>`;
            html += `<div class="mt-2"><button type="button" class="btn btn-sm btn-outline-secondary" onclick="showManualServiceMaterial('${sid}', ${data.pdf_id || 0})"><i class="bi bi-plus"></i> Adicionar manual</button></div>`;
        }
        statusEl.innerHTML = '';
        materialsEl.style.display = 'block';
        materialsEl.innerHTML = html;
        if (data.materials && data.materials.length > 0) {
            window['svcMats_' + sid] = data.materials;
            setTimeout(() => bindSvcRecalc(sid), 100);
        }
    } else {
        statusEl.innerHTML = `<div class="alert alert-danger small py-2 mb-0"><i class="bi bi-x-circle"></i> ${data?.error || 'Erro.'}</div>`;
    }

    fileInput.value = '';
}

// Matching de materiais da IA com cadastro existente
function matchServiceMaterials(materials) {
    const normalize = (str) => (str || '').toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s]/g, ' ')
        .replace(/\s+/g, ' ').trim();

    materials.forEach(m => {
        if (m.material_id) return; // Já vinculado
        
        const mNorm = normalize(m.name);
        const mWords = mNorm.split(' ').filter(w => w.length > 2);
        let bestMatch = null;
        let bestScore = 0;

        allMaterials.forEach(mat => {
            const matNorm = normalize(mat.name);
            // Match exato
            if (matNorm === mNorm) { bestMatch = mat; bestScore = 100; return; }
            // Match por inclusão
            if (matNorm.includes(mNorm) || mNorm.includes(matNorm)) { if (bestScore < 80) { bestMatch = mat; bestScore = 80; } return; }
            // Match por palavras em comum
            const matWords = matNorm.split(' ').filter(w => w.length > 2);
            let commonWords = 0;
            mWords.forEach(w => { if (matWords.includes(w)) commonWords++; });
            const score = mWords.length > 0 ? (commonWords / mWords.length) * 70 : 0;
            if (score > bestScore && score >= 50) { bestMatch = mat; bestScore = score; }
        });

        if (bestMatch) {
            m.material_id = bestMatch.id;
            m._matched_name = bestMatch.name;
        }
    });
    return materials;
}

function renderServiceMaterials(sid, materials, totals, pdfId) {
    let html = `<div class="card border-success mt-2"><div class="card-header bg-success bg-opacity-10 py-2 d-flex justify-content-between align-items-center">`;
    html += `<strong class="small"><i class="bi bi-list-check"></i> Materiais identificados (${materials.length})</strong>`;
    html += `<label class="small mb-0"><input type="checkbox" checked onchange="toggleAllServiceMats(this, '${sid}')" class="me-1">Todos</label>`;
    html += `</div><div class="card-body p-0">`;
    
    // Desktop: Tabela
    html += `<div class="d-none d-md-block"><div class="table-responsive"><table class="table table-sm mb-0" style="font-size:0.75rem;">`;
    html += `<thead><tr><th style="width:30px;"></th><th>Material</th><th>Unid.</th><th>Qtd</th><th>Unit.</th><th>Total</th><th style="width:60px;"></th></tr></thead><tbody>`;

    materials.forEach((m, idx) => {
        const unitPrice = m.unit_price ? parseFloat(m.unit_price).toFixed(2).replace('.', ',') : '-';
        const totalPrice = m.total_price ? parseFloat(m.total_price).toFixed(2).replace('.', ',') : '-';
        
        html += `<tr id="svc-mat-${sid}-${idx}">`;
        html += `<td><input type="checkbox" class="svc-mat-check" data-sid="${sid}" data-idx="${idx}" checked></td>`;
        html += `<td><input type="text" class="form-control form-control-sm p-1 bg-white" value="${escHtml(m.name || '')}" id="svc-name-${sid}-${idx}" style="font-size:0.75rem;"></td>`;
        html += `<td><input type="text" class="form-control form-control-sm p-1 bg-white" value="${escHtml(m.unit || '')}" id="svc-unit-${sid}-${idx}" style="width:50px;font-size:0.75rem;"></td>`;
        html += `<td><input type="number" class="form-control form-control-sm p-1 bg-white" value="${m.quantity || 1}" id="svc-qty-${sid}-${idx}" style="width:55px;font-size:0.75rem;" step="0.001"></td>`;
        html += `<td><input type="text" class="form-control form-control-sm p-1 bg-white" value="${unitPrice}" id="svc-uprice-${sid}-${idx}" style="width:75px;font-size:0.75rem;" inputmode="decimal"></td>`;
        html += `<td><input type="text" class="form-control form-control-sm p-1 bg-white" value="${totalPrice}" id="svc-tprice-${sid}-${idx}" style="width:80px;font-size:0.75rem;" inputmode="decimal"></td>`;
        html += `<td class="text-nowrap"><button type="button" class="btn btn-sm btn-outline-danger p-0 px-1" onclick="removeServiceMaterial('${sid}', ${idx})" title="Remover"><i class="bi bi-x"></i></button></td>`;
        html += `</tr>`;
    });
    html += `</tbody></table></div></div>`;

    // Mobile: Cards
    html += `<div class="d-md-none p-2">`;
    materials.forEach((m, idx) => {
        const unitPrice = m.unit_price ? parseFloat(m.unit_price).toFixed(2).replace('.', ',') : '-';
        const totalPrice = m.total_price ? parseFloat(m.total_price).toFixed(2).replace('.', ',') : '-';
        
        html += `<div class="border rounded p-2 mb-2 bg-white" id="svc-mat-m-${sid}-${idx}">`;
        html += `<div class="d-flex justify-content-between align-items-start mb-1">`;
        html += `<div class="d-flex align-items-center gap-2 flex-grow-1 min-width-0">`;
        html += `<input type="checkbox" class="svc-mat-check-m" data-sid="${sid}" data-idx="${idx}" checked>`;
        html += `<input type="text" class="form-control form-control-sm border-0 p-0 fw-bold" value="${escHtml(m.name || '')}" id="svc-name-m-${sid}-${idx}" style="font-size:0.8rem;" placeholder="Nome do material">`;
        html += `</div>`;
        html += `<div class="d-flex gap-1 flex-shrink-0 ms-1">`;
        html += `<button type="button" class="btn btn-sm btn-outline-danger p-0 px-1" onclick="removeServiceMaterial('${sid}', ${idx})" title="Remover"><i class="bi bi-x"></i></button>`;
        html += `</div>`;
        html += `</div>`;
        html += `<div class="d-flex flex-wrap gap-2 align-items-center" style="font-size:0.75rem;">`;
        html += `<div><span class="text-muted">Unid:</span> <input type="text" class="form-control form-control-sm d-inline-block p-0 bg-transparent" value="${escHtml(m.unit || '')}" id="svc-unit-m-${sid}-${idx}" style="width:40px;font-size:0.75rem;border-bottom:1px dashed #ccc !important;border-top:0;border-left:0;border-right:0;border-radius:0;"></div>`;
        html += `<div><span class="text-muted">Qtd:</span> <input type="number" class="form-control form-control-sm d-inline-block p-0 bg-transparent" value="${m.quantity || 1}" id="svc-qty-m-${sid}-${idx}" style="width:45px;font-size:0.75rem;border-bottom:1px dashed #ccc !important;border-top:0;border-left:0;border-right:0;border-radius:0;" step="0.001"></div>`;
        html += `<div><span class="text-muted">Unit:</span> <input type="text" class="form-control form-control-sm d-inline-block p-0 bg-transparent" value="${unitPrice}" id="svc-uprice-m-${sid}-${idx}" style="width:65px;font-size:0.75rem;border-bottom:1px dashed #ccc !important;border-top:0;border-left:0;border-right:0;border-radius:0;" inputmode="decimal"></div>`;
        html += `<div class="fw-bold"><span class="text-muted">Total:</span> <input type="text" class="form-control form-control-sm d-inline-block p-0 bg-transparent fw-bold" value="${totalPrice}" id="svc-tprice-m-${sid}-${idx}" style="width:70px;font-size:0.75rem;border-bottom:1px dashed #ccc !important;border-top:0;border-left:0;border-right:0;border-radius:0;" inputmode="decimal"></div>`;
        html += `</div></div>`;
    });
    html += `</div>`;

    // Totais removidos - não exibir subtotal/total calculado
    html += `<div class="p-2 border-top d-flex flex-wrap gap-2 justify-content-end">`;
    html += `<button type="button" class="btn btn-sm btn-outline-secondary" onclick="showManualServiceMaterial('${sid}', ${pdfId || 0})"><i class="bi bi-plus"></i> <span class="d-none d-sm-inline">Adicionar</span> manual</button>`;
    html += `</div></div>`;

    // Dados armazenados pelo caller (parseServicePdf/parseServicePdfFromMap)

    return html;
}

// Recalcular subtotal/total quando edita valores
function bindSvcRecalc(sid) {
    // Usar event delegation no container pai para pegar todos os inputs (inclusive futuros)
    const containers = [
        document.getElementById('serviceMaterialsList-' + sid),
        document.getElementById('serviceMaterialsListMap-' + sid)
    ];
    containers.forEach(container => {
        if (!container) return;
        container.addEventListener('input', (e) => {
            if (e.target.matches('input[id*="svc-tprice-"], input[id*="svc-uprice-"], input[id*="svc-qty-"]')) {
                recalcServiceTotals(sid);
            }
        });
    });
    // Calcular na inicialização
    recalcServiceTotals(sid);
}

function recalcServiceTotals(sid) {
    let subtotal = 0;
    
    const container = document.getElementById('serviceMaterialsList-' + sid) || document.getElementById('serviceMaterialsListMap-' + sid);
    if (!container) return;
    
    // Pegar inputs de total APENAS da tabela desktop (d-none d-md-block)
    // Se não achar na tabela desktop, tentar mobile
    let totalInputs = container.querySelectorAll('.d-none.d-md-block input[id*="svc-tprice-"]');
    if (totalInputs.length === 0) {
        totalInputs = container.querySelectorAll('.d-md-none input[id*="svc-tprice-m-"]');
    }
    // Fallback: pegar só os que NÃO tem -m- (desktop)
    if (totalInputs.length === 0) {
        totalInputs = container.querySelectorAll('input[id*="svc-tprice-"]');
    }
    
    totalInputs.forEach(el => {
        const val = parseBRL(el.value);
        if (val !== null && val > 0) {
            subtotal += val;
        } else {
            // Se total vazio, calcular de qty * unit no mesmo row
            const row = el.closest('tr') || el.closest('.border.rounded');
            if (row) {
                const qtyInput = row.querySelector('input[id*="svc-qty-"]');
                const upriceInput = row.querySelector('input[id*="svc-uprice-"]');
                if (qtyInput && upriceInput) {
                    subtotal += (parseFloat(qtyInput.value) || 0) * (parseBRL(upriceInput.value) || 0);
                }
            }
        }
    });
    
    const totalsEl = document.getElementById('svc-totals-' + sid);
    if (totalsEl) {
        totalsEl.innerHTML = `<span>Subtotal: <strong>R$ ${subtotal.toFixed(2).replace('.', ',')}</strong></span> <span class="fw-bold text-success">Total: R$ ${subtotal.toFixed(2).replace('.', ',')}</span>`;
    }
}

function toggleAllServiceMats(checkbox, sid) {
    document.querySelectorAll(`.svc-mat-check[data-sid="${sid}"], .svc-mat-check-m[data-sid="${sid}"]`).forEach(cb => cb.checked = checkbox.checked);
}

async function saveServiceMaterials(sid, pdfId) {
    // Buscar checkboxes no container VISÍVEL
    let container = document.getElementById('serviceMaterialsListMap-' + sid);
    if (!container || container.style.display === 'none' || !container.querySelector('input[data-idx]')) {
        container = document.getElementById('serviceMaterialsList-' + sid);
    }
    if (!container) { alert('Erro: container não encontrado.'); return; }
    
    let checkboxes = container.querySelectorAll('input[type="checkbox"][data-idx]:checked');
    
    // Fallback: buscar em todo o documento por esse sid
    if (checkboxes.length === 0) {
        checkboxes = document.querySelectorAll(`input[type="checkbox"][data-sid="${sid}"][data-idx]:checked`);
    }
    
    const rawMats = window['svcMats_' + sid] || [];
    const materials = [];

    for (const cb of checkboxes) {
        const idx = parseInt(cb.dataset.idx);
        const raw = rawMats[idx] || {};
        if (raw._removed) continue;
        
        const getName = () => document.getElementById(`svc-name-${sid}-${idx}`)?.value || document.getElementById(`svc-name-m-${sid}-${idx}`)?.value || raw.name;
        const getUnit = () => document.getElementById(`svc-unit-${sid}-${idx}`)?.value || document.getElementById(`svc-unit-m-${sid}-${idx}`)?.value || raw.unit;
        const getQty = () => document.getElementById(`svc-qty-${sid}-${idx}`)?.value || document.getElementById(`svc-qty-m-${sid}-${idx}`)?.value || raw.quantity;
        const getUprice = () => document.getElementById(`svc-uprice-${sid}-${idx}`)?.value || document.getElementById(`svc-uprice-m-${sid}-${idx}`)?.value || raw.unit_price;
        const getTprice = () => document.getElementById(`svc-tprice-${sid}-${idx}`)?.value || document.getElementById(`svc-tprice-m-${sid}-${idx}`)?.value || raw.total_price;

        const name = getName();
        const unit = getUnit();
        let matId = raw.material_id || null;

        // Auto-cadastrar material se não tem ID (primeiro tenta encontrar existente)
        if (!matId && name) {
            // Tentar encontrar material existente por nome similar
            const normalize = (str) => (str || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9\s]/g, ' ').replace(/\s+/g, ' ').trim();
            const nameNorm = normalize(name);
            const nameWords = nameNorm.split(' ').filter(w => w.length > 2);
            
            let bestMatch = null;
            let bestScore = 0;
            
            allMaterials.forEach(m => {
                const mNorm = normalize(m.name);
                if (mNorm === nameNorm) { bestMatch = m; bestScore = 100; return; }
                if (mNorm.includes(nameNorm) || nameNorm.includes(mNorm)) { if (bestScore < 80) { bestMatch = m; bestScore = 80; } return; }
                const mWords = mNorm.split(' ').filter(w => w.length > 2);
                let common = 0;
                nameWords.forEach(w => { if (mWords.includes(w)) common++; });
                const score = nameWords.length > 0 ? (common / nameWords.length) * 70 : 0;
                if (score > bestScore && score >= 50) { bestMatch = m; bestScore = score; }
            });

            if (bestMatch && bestScore >= 50) {
                matId = bestMatch.id;
                if (rawMats[idx]) rawMats[idx].material_id = matId;
            } else {
                // Não encontrou: cadastrar novo
                try {
                    const resp = await fetch('/admin/materials/quick-store', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ name, specification: raw.specification || '', classification: raw.classification || '', unit_id: '', category_id: '' })
                    });
                    const data = await resp.json();
                    if (data.success) {
                        matId = data.material.id;
                        if (rawMats[idx]) rawMats[idx].material_id = matId;
                        allMaterials.push({ id: data.material.id, name: data.material.name, specification: '', classification: '', unit_abbr: unit || '', unit_name: '' });
                    }
                } catch (e) {}
            }
        }

        materials.push({
            name: name,
            code: raw.code || null,
            description: raw.description || null,
            specification: raw.specification || null,
            classification: raw.classification || null,
            unit: unit,
            quantity: parseFloat(getQty()) || 1,
            weight: raw.weight || null,
            unit_price: parseBRL(getUprice()) || raw.unit_price || null,
            total_price: parseBRL(getTprice()) || raw.total_price || null,
            material_id: matId,
        });
    }

    if (materials.length === 0) {
        alert('Selecione pelo menos um material para salvar.');
        return;
    }

    try {
        const resp = await fetch('/pedido/cotacao/save-service-materials', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                order_id: orderId,
                supplier_id: sid,
                pdf_id: pdfId || 0,
                materials: JSON.stringify(materials),
            })
        });
        const data = await resp.json();
        if (data.success) {
            const savedBadge = document.createElement('div');
            savedBadge.className = 'alert alert-success small py-2 mt-2';
            savedBadge.innerHTML = `<i class="bi bi-check-circle"></i> ${data.saved} materiais salvos e cadastrados!`;
            container.appendChild(savedBadge);
            // Esconder botões de salvar e adicionar manual
            container.querySelectorAll('button[onclick*="saveServiceMaterials"], button[onclick*="showManualServiceMaterial"]').forEach(btn => btn.style.display = 'none');
            // Desabilitar edição
            container.querySelectorAll('input[id*="svc-"]').forEach(inp => { inp.readOnly = true; inp.style.opacity = '0.7'; });
            container.querySelectorAll('button[onclick*="removeServiceMaterial"]').forEach(btn => btn.style.display = 'none');
        } else {
            alert(data.error || 'Erro ao salvar materiais.');
        }
    } catch (e) {
        alert('Erro de conexão ao salvar materiais.');
    }
}

async function registerServiceMaterial(sid, idx) {
    const name = document.getElementById(`svc-name-${sid}-${idx}`)?.value || document.getElementById(`svc-name-m-${sid}-${idx}`)?.value;
    
    if (!name) { alert('Nome é obrigatório'); return; }

    try {
        const resp = await fetch('/admin/materials/quick-store', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ name: name, specification: '', classification: '', unit_id: '', category_id: '' })
        });
        const data = await resp.json();
        if (data.success) {
            // Atualizar dados em memória
            const rawMats = window['svcMats_' + sid] || [];
            if (rawMats[idx]) rawMats[idx].material_id = data.material.id;
            
            // Marcar visualmente (desktop)
            const row = document.getElementById(`svc-mat-${sid}-${idx}`);
            if (row) {
                const btn = row.querySelector('button');
                if (btn) {
                    btn.innerHTML = '<i class="bi bi-check text-white"></i>';
                    btn.disabled = true;
                    btn.classList.remove('btn-outline-success');
                    btn.classList.add('btn-success');
                }
            }
            // Marcar visualmente (mobile)
            const card = document.getElementById(`svc-mat-m-${sid}-${idx}`);
            if (card) {
                const btn = card.querySelector('button');
                if (btn) {
                    btn.innerHTML = '<i class="bi bi-check text-white"></i>';
                    btn.disabled = true;
                    btn.classList.remove('btn-outline-success');
                    btn.classList.add('btn-success');
                }
                card.classList.add('border-success');
            }
        } else {
            alert(data.error || 'Erro ao cadastrar material.');
        }
    } catch (e) {
        alert('Erro de conexão.');
    }
}


function showManualServiceMaterial(sid, pdfId) {
    // Buscar container — tenta lista, depois mapa, depois cria no accordion
    let container = null;
    const listC = document.getElementById('serviceMaterialsList-' + sid);
    const mapC = document.getElementById('serviceMaterialsListMap-' + sid);
    
    if (listC && listC.offsetParent !== null) container = listC;
    else if (mapC && mapC.offsetParent !== null) container = mapC;
    else if (listC) container = listC;
    else if (mapC) container = mapC;

    if (!container) {
        const mapFin = document.getElementById('mapFin' + sid);
        if (mapFin) {
            let mc = document.getElementById('svc-manual-wrap-' + sid);
            if (!mc) {
                mc = document.createElement('div');
                mc.id = 'svc-manual-wrap-' + sid;
                mc.className = 'mt-2';
                mapFin.querySelector('.accordion-body')?.appendChild(mc);
            }
            container = mc;
        }
    }
    if (!container) { alert('Erro: container não encontrado.'); return; }

    if (document.getElementById('manual-svc-form-' + sid)) {
        document.getElementById('manual-svc-form-' + sid).style.display = 'block';
        return;
    }

    // Montar options para SearchableSelect
    let matOpts = '<option value="">-- Selecione --</option>';
    allMaterials.forEach(m => {
        const label = m.name + (m.classification ? ' - ' + m.classification : '') + (m.specification ? ' (' + m.specification + ')' : '');
        matOpts += `<option value="${m.id}" data-name="${m.name}" data-spec="${m.specification || ''}" data-class="${m.classification || ''}" data-unit="${m.unit_abbr || m.unit_name || ''}">${label}</option>`;
    });

    const formHtml = `
        <div id="manual-svc-form-${sid}" class="p-2 border rounded bg-light mt-2">
            <h6 class="small fw-bold mb-2"><i class="bi bi-plus-circle"></i> Adicionar Material</h6>
            <div class="row g-2">
                <div class="col-12">
                    <select id="manual-mat-select-${sid}" style="display:none;">${matOpts}</select>
                    <div id="manual-mat-ss-${sid}"></div>
                    <input type="hidden" id="manual-mat-id-${sid}" value="">
                </div>
                <div class="col-12">
                    <input type="text" class="form-control form-control-sm" id="manual-name-${sid}" placeholder="Ou digite um nome novo aqui (se não achou acima)">
                </div>
                <div class="col-4"><input type="text" class="form-control form-control-sm" id="manual-unit-${sid}" placeholder="Unid."></div>
                <div class="col-4"><input type="number" class="form-control form-control-sm" id="manual-qty-${sid}" placeholder="Qtd" value="1" step="0.01"></div>
                <div class="col-4"><input type="text" class="form-control form-control-sm" id="manual-uprice-${sid}" placeholder="R$ unit." inputmode="decimal"></div>
                <div class="col-12">
                    <button type="button" class="btn btn-sm btn-success w-100" onclick="addManualServiceMaterial('${sid}', ${pdfId})"><i class="bi bi-plus"></i> Adicionar</button>
                </div>
            </div>
        </div>`;

    container.insertAdjacentHTML('beforeend', formHtml);
    container.style.display = 'block';

    // Inicializar SearchableSelect (igual na tela de pedido)
    new SearchableSelect(document.getElementById('manual-mat-select-' + sid), {
        placeholder: 'Buscar material cadastrado...',
        onSelect: function(value, text, dataset) {
            document.getElementById('manual-mat-id-' + sid).value = value;
            document.getElementById('manual-name-' + sid).value = dataset.name || '';
            document.getElementById('manual-unit-' + sid).value = dataset.unit || '';
        }
    });
}

async function addManualServiceMaterial(sid, pdfId) {
    const materialId = document.getElementById(`manual-mat-id-${sid}`)?.value;
    const nameInput = document.getElementById(`manual-name-${sid}`)?.value?.trim();
    const unit = document.getElementById(`manual-unit-${sid}`)?.value?.trim();
    const qty = document.getElementById(`manual-qty-${sid}`)?.value || 1;
    const uprice = document.getElementById(`manual-uprice-${sid}`)?.value;

    let name = nameInput;
    let matId = materialId || null;

    if (!name && !matId) { alert('Selecione um material ou digite um nome.'); return; }
    
    if (matId && !name) {
        const found = allMaterials.find(m => String(m.id) === String(matId));
        if (found) name = found.name;
    }
    if (!name) { alert('Nome é obrigatório.'); return; }

    const unitPriceNum = parseBRL(uprice) || 0;
    const totalPriceNum = unitPriceNum * (parseFloat(qty) || 1);

    // Se não selecionou existente, cadastrar novo
    if (!matId) {
        try {
            const resp = await fetch('/admin/materials/quick-store', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ name, specification: '', classification: '', unit_id: '', category_id: '' })
            });
            const data = await resp.json();
            if (data.success) {
                matId = data.material.id;
                allMaterials.push({ id: data.material.id, name: data.material.name, specification: '', classification: '', unit_abbr: unit || '', unit_name: '' });
            }
        } catch (e) {}
    }

    const materials = [{ name, unit: unit || 'UN', quantity: parseFloat(qty) || 1, unit_price: unitPriceNum || null, total_price: totalPriceNum || null, material_id: matId }];

    try {
        const resp = await fetch('/pedido/cotacao/save-service-materials', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ order_id: orderId, supplier_id: sid, pdf_id: pdfId || 0, materials: JSON.stringify(materials) })
        });
        const data = await resp.json();
        if (data.success) {
            document.getElementById(`manual-name-${sid}`).value = '';
            document.getElementById(`manual-unit-${sid}`).value = '';
            document.getElementById(`manual-qty-${sid}`).value = '1';
            document.getElementById(`manual-uprice-${sid}`).value = '';
            document.getElementById(`manual-mat-id-${sid}`).value = '';
            
            // Adicionar item visualmente na tabela/lista
            const qtyNum = parseFloat(qty) || 1;
            const upFmt = unitPriceNum ? unitPriceNum.toFixed(2).replace('.', ',') : '-';
            const tpFmt = totalPriceNum ? totalPriceNum.toFixed(2).replace('.', ',') : '-';
            
            // Tentar adicionar na tabela desktop
            const desktopTable = document.querySelector(`#serviceMaterialsList-${sid} table tbody, #serviceMaterialsListMap-${sid} table tbody`);
            if (desktopTable) {
                const newIdx = desktopTable.querySelectorAll('tr').length;
                const tr = document.createElement('tr');
                tr.id = `svc-mat-${sid}-manual-${newIdx}`;
                tr.innerHTML = `<td><input type="checkbox" checked disabled></td><td>${name}</td><td>${unit || 'UN'}</td><td>${qtyNum}</td><td>${upFmt}</td><td>${tpFmt}</td><td><span class="badge bg-success"><i class="bi bi-check"></i></span></td>`;
                desktopTable.appendChild(tr);
            }
            
            // Feedback
            const form = document.getElementById('manual-svc-form-' + sid);
            if (form) {
                const msg = document.createElement('div');
                msg.className = 'alert alert-success small py-1 mt-1';
                msg.innerHTML = `<i class="bi bi-check"></i> "${name}" salvo!`;
                form.appendChild(msg);
                setTimeout(() => msg.remove(), 3000);
            }
        } else {
            alert(data.error || 'Erro');
        }
    } catch (e) {
        alert('Erro de conexão.');
    }
}

function removeServiceMaterial(sid, idx) {
    document.getElementById(`svc-mat-${sid}-${idx}`)?.remove();
    document.getElementById(`svc-mat-m-${sid}-${idx}`)?.remove();
    const rawMats = window['svcMats_' + sid] || [];
    if (rawMats[idx]) rawMats[idx]._removed = true;
    recalcServiceTotals(sid);
}
</script>
</body>
</html>
