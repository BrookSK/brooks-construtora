<!-- Seção: Enviar Cotação para Fornecedor via WhatsApp -->
<div class="card mb-3 border-success" id="sendQuoteSection">
    <div class="card-header bg-success bg-opacity-10">
        <i class="bi bi-whatsapp text-success"></i> <strong>Enviar Cotação para Fornecedor</strong>
        <small class="text-muted d-block mt-1">Envie a solicitação de cotação diretamente para o WhatsApp do vendedor.</small>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label small fw-bold">Fornecedor</label>
                <select class="form-select form-select-sm" id="sendQuoteSupplier" onchange="loadVendorContacts()">
                    <option value="">Selecione o fornecedor...</option>
                    <?php foreach ($suppliers as $sup): ?>
                        <option value="<?= $sup['id'] ?>"><?= htmlspecialchars($sup['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label small fw-bold">Vendedor</label>
                <select class="form-select form-select-sm" id="sendQuoteVendor" disabled>
                    <option value="">Selecione o vendedor...</option>
                </select>
                <small class="text-muted" id="vendorPhonePreview"></small>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" class="btn btn-success btn-sm w-100" id="sendQuoteBtn" onclick="sendQuoteToSupplier()" disabled>
                    <i class="bi bi-send"></i> Enviar
                </button>
            </div>
        </div>

        <!-- Preview da mensagem -->
        <div class="mt-3" id="quoteMessagePreview" style="display:none;">
            <label class="form-label small fw-bold">Preview da mensagem:</label>
            <div class="bg-light rounded p-2 small" id="quoteMessageText" style="white-space: pre-wrap; max-height: 150px; overflow-y: auto;"></div>
        </div>

        <!-- Log de envios -->
        <div class="mt-3" id="quoteSendLog" style="display:none;">
            <div class="alert alert-success small py-2 mb-0" id="quoteSendLogContent"></div>
        </div>
    </div>
</div>

<script>
const quoteOrderId = <?= $order['id'] ?>;
const quoteOrderCode = '<?= $order['code'] ?>';
const quoteSiteName = '<?= htmlspecialchars($order['construction_site_name'] ?? 'N/A') ?>';
const quoteItems = <?= json_encode(array_map(function($item) {
    return [
        'name' => $item['material_name'],
        'quantity' => $item['quantity'],
        'unit' => $item['unit'] ?? '',
        'specification' => $item['specification'] ?? '',
    ];
}, $items)) ?>;

async function loadVendorContacts() {
    const supplierId = document.getElementById('sendQuoteSupplier').value;
    const vendorSelect = document.getElementById('sendQuoteVendor');
    const sendBtn = document.getElementById('sendQuoteBtn');
    const preview = document.getElementById('quoteMessagePreview');

    vendorSelect.innerHTML = '<option value="">Carregando...</option>';
    vendorSelect.disabled = true;
    sendBtn.disabled = true;
    preview.style.display = 'none';

    if (!supplierId) {
        vendorSelect.innerHTML = '<option value="">Selecione o vendedor...</option>';
        return;
    }

    try {
        const resp = await fetch(`/admin/suppliers/get-contacts?supplier_id=${supplierId}`);
        const data = await resp.json();

        vendorSelect.innerHTML = '<option value="">Selecione o vendedor...</option>';
        if (data.contacts && data.contacts.length > 0) {
            data.contacts.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = c.name + (c.phone ? ` (${c.phone})` : '');
                opt.dataset.phone = c.phone || '';
                opt.dataset.name = c.name || '';
                vendorSelect.appendChild(opt);
            });
            vendorSelect.disabled = false;
        } else {
            vendorSelect.innerHTML = '<option value="">Nenhum vendedor cadastrado</option>';
        }
    } catch (e) {
        vendorSelect.innerHTML = '<option value="">Erro ao carregar</option>';
    }

    vendorSelect.onchange = function() {
        if (this.value) {
            sendBtn.disabled = false;
            const opt = this.selectedOptions[0];
            document.getElementById('vendorPhonePreview').textContent = opt.dataset.phone ? `Tel: ${opt.dataset.phone}` : '';
            showMessagePreview(opt.dataset.name);
        } else {
            sendBtn.disabled = true;
            document.getElementById('vendorPhonePreview').textContent = '';
            preview.style.display = 'none';
        }
    };
}

function showMessagePreview(vendorName) {
    const supplierName = document.getElementById('sendQuoteSupplier').selectedOptions[0]?.text || '';
    
    // Montar lista de itens
    let itemsList = '';
    quoteItems.forEach((item, i) => {
        itemsList += `${i+1}. ${item.name}`;
        if (item.specification) itemsList += ` - ${item.specification}`;
        if (item.quantity) itemsList += ` - Qtd: ${item.quantity}`;
        itemsList += '\n';
    });

    // Mensagem padrão (do settings ou default)
    let message = document.getElementById('defaultQuoteMessage')?.value || 
        `Olá! Bom dia, tudo bem?\n\nPrecisamos de cotação para os seguintes itens:\n\n{items_list}\n\nObra: {construction_site}\nPedido: {order_code}\n\nPoderia nos enviar o orçamento?\n\nObrigado!`;

    message = message.replace('{items_list}', itemsList.trim())
        .replace('{construction_site}', quoteSiteName)
        .replace('{order_code}', quoteOrderCode)
        .replace('{supplier_name}', supplierName)
        .replace('{vendor_name}', vendorName);

    document.getElementById('quoteMessageText').textContent = message;
    document.getElementById('quoteMessagePreview').style.display = 'block';
}

async function sendQuoteToSupplier() {
    const supplierId = document.getElementById('sendQuoteSupplier').value;
    const vendorSelect = document.getElementById('sendQuoteVendor');
    const vendorId = vendorSelect.value;
    const vendorOpt = vendorSelect.selectedOptions[0];
    const vendorPhone = vendorOpt?.dataset?.phone || '';
    const vendorName = vendorOpt?.dataset?.name || '';
    const supplierName = document.getElementById('sendQuoteSupplier').selectedOptions[0]?.text || '';
    const message = document.getElementById('quoteMessageText').textContent;

    if (!supplierId || !vendorId) {
        alert('Selecione o fornecedor e o vendedor.');
        return;
    }

    if (!vendorPhone) {
        alert('Este vendedor não tem telefone cadastrado. Cadastre o telefone primeiro.');
        return;
    }

    const sendBtn = document.getElementById('sendQuoteBtn');
    sendBtn.disabled = true;
    sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    try {
        const resp = await fetch('/pedido/cotacao/send-to-supplier', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                order_id: quoteOrderId,
                supplier_id: supplierId,
                contact_id: vendorId,
                phone: vendorPhone,
                vendor_name: vendorName,
                supplier_name: supplierName,
                message: message,
            })
        });
        const data = await resp.json();

        if (data.success) {
            const log = document.getElementById('quoteSendLog');
            const logContent = document.getElementById('quoteSendLogContent');
            log.style.display = 'block';
            logContent.innerHTML = `<i class="bi bi-check-circle"></i> Cotação enviada para <strong>${vendorName}</strong> (${vendorPhone}) via webhook!`;
        } else {
            alert(data.error || 'Erro ao enviar cotação.');
        }
    } catch (e) {
        alert('Erro de conexão ao enviar cotação.');
    }

    sendBtn.disabled = false;
    sendBtn.innerHTML = '<i class="bi bi-send"></i> Enviar';
}
</script>
<input type="hidden" id="defaultQuoteMessage" value="<?= htmlspecialchars(\App\Models\Setting::get('orders_quote_default_message', "Olá! Bom dia, tudo bem?\n\nPrecisamos de cotação para os seguintes itens:\n\n{items_list}\n\nObra: {construction_site}\nPedido: {order_code}\n\nPoderia nos enviar o orçamento?\n\nObrigado!")) ?>">
