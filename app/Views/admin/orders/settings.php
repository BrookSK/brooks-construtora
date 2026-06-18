<?php $pageTitle = 'Configurações de Pedidos'; $currentPage = 'orders_settings'; ?>
<?php ob_start(); ?>

<form method="POST" action="/admin/orders/settings/update">
    <div class="row">
        <!-- Fase 1: Cotação -->
        <div class="col-12 col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-header bg-warning bg-opacity-10">
                    <i class="bi bi-1-circle text-warning"></i> <strong>Cotação</strong>
                    <p class="small text-muted mb-0 mt-1">Quem recebe a notificação para informar os preços</p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">E-mails para Cotação</label>
                        <textarea class="form-control" name="orders_quote_emails" rows="3" placeholder="email1@empresa.com, email2@empresa.com"><?= htmlspecialchars($settings['orders_quote_emails'] ?? '') ?></textarea>
                        <small class="text-muted">Separe múltiplos e-mails por vírgula</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Webhook de Cotação</label>
                        <div class="input-group">
                            <input type="url" class="form-control" name="orders_quote_webhook" id="webhook_quote" placeholder="https://..." value="<?= htmlspecialchars($settings['orders_quote_webhook'] ?? '') ?>">
                            <button type="button" class="btn btn-outline-warning" onclick="testWebhook('quote')" title="Testar">
                                <i class="bi bi-lightning"></i>
                            </button>
                        </div>
                        <small class="text-muted">URL para envio de webhook (JSON POST)</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fase 2: Aprovação -->
        <div class="col-12 col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-header bg-info bg-opacity-10">
                    <i class="bi bi-2-circle text-info"></i> <strong>Aprovação</strong>
                    <p class="small text-muted mb-0 mt-1">Quem recebe a notificação para aprovar ou negar</p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">E-mails para Aprovação</label>
                        <textarea class="form-control" name="orders_approval_emails" rows="3" placeholder="gestor1@empresa.com, gestor2@empresa.com"><?= htmlspecialchars($settings['orders_approval_emails'] ?? '') ?></textarea>
                        <small class="text-muted">Separe múltiplos e-mails por vírgula</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Webhook de Aprovação</label>
                        <div class="input-group">
                            <input type="url" class="form-control" name="orders_approval_webhook" id="webhook_approval" placeholder="https://..." value="<?= htmlspecialchars($settings['orders_approval_webhook'] ?? '') ?>">
                            <button type="button" class="btn btn-outline-info" onclick="testWebhook('approval')" title="Testar">
                                <i class="bi bi-lightning"></i>
                            </button>
                        </div>
                        <small class="text-muted">URL para envio de webhook (JSON POST)</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fase 3: Conclusão -->
        <div class="col-12 col-lg-4 mb-3">
            <div class="card h-100">
                <div class="card-header bg-success bg-opacity-10">
                    <i class="bi bi-3-circle text-success"></i> <strong>Conclusão (PDF)</strong>
                    <p class="small text-muted mb-0 mt-1">Quem recebe a formalização final em PDF</p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">E-mails para Formalização</label>
                        <textarea class="form-control" name="orders_completed_emails" rows="3" placeholder="admin@empresa.com"><?= htmlspecialchars($settings['orders_completed_emails'] ?? '') ?></textarea>
                        <small class="text-muted">Separe múltiplos e-mails por vírgula</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Webhook de Conclusão</label>
                        <div class="input-group">
                            <input type="url" class="form-control" name="orders_completed_webhook" id="webhook_completed" placeholder="https://..." value="<?= htmlspecialchars($settings['orders_completed_webhook'] ?? '') ?>">
                            <button type="button" class="btn btn-outline-success" onclick="testWebhook('completed')" title="Testar">
                                <i class="bi bi-lightning"></i>
                            </button>
                        </div>
                        <small class="text-muted">URL para envio de webhook (JSON POST)</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-end">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-check-lg"></i> Salvar Configurações
        </button>
    </div>
</form>

<!-- Explicação do fluxo -->
<div class="card mt-4">
    <div class="card-header">Como funciona o fluxo</div>
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-center gap-2 justify-content-center">
            <span class="badge bg-secondary p-2">1. Pedido Criado</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-warning p-2">2. E-mail + Webhook → Cotação</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-info p-2">3. E-mail + Webhook → Aprovação</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-success p-2">4. E-mail + Webhook + PDF</span>
        </div>
        <p class="text-muted small text-center mt-3 mb-0">
            Cada fase envia notificações para os e-mails configurados acima + dispara o webhook correspondente.<br>
            Os webhooks enviam um JSON POST com os dados do pedido e o link de acesso.
        </p>
    </div>
</div>

<!-- Modal de resultado do teste -->
<div class="modal fade" id="testResultModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="testResultTitle">Resultado do Teste</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="testResultLoading" class="text-center py-3">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2 mb-0">Enviando webhook de teste...</p>
                </div>
                <div id="testResultContent" style="display:none;">
                    <div id="testResultStatus" class="alert mb-3"></div>
                    <div class="mb-2">
                        <strong class="small">Payload enviado:</strong>
                        <pre class="bg-dark text-light p-2 rounded mt-1 mb-0" style="font-size:0.7rem; max-height:200px; overflow-y:auto;"><code id="testPayload"></code></pre>
                    </div>
                    <div>
                        <strong class="small">Resposta:</strong>
                        <pre class="bg-light p-2 rounded mt-1 mb-0" style="font-size:0.7rem; max-height:150px; overflow-y:auto;"><code id="testResponse"></code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function testWebhook(type) {
    const inputId = 'webhook_' + type;
    const url = document.getElementById(inputId).value.trim();
    
    if (!url) {
        alert('Preencha a URL do webhook antes de testar.');
        return;
    }
    
    if (!url.startsWith('http')) {
        alert('URL inválida. Use uma URL começando com http:// ou https://');
        return;
    }

    // Payloads de teste para cada fase
    const payloads = {
        quote: {
            event: 'quote_requested',
            test: true,
            order_code: 'PED-TESTE-001',
            supplier: 'Fornecedor de Teste LTDA',
            items_count: 5,
            quote_url: window.location.origin + '/pedido/cotacao/token-de-teste-abc123',
            created_by: 'Usuário Teste',
            created_at: new Date().toISOString(),
            description: 'Este é um pedido de TESTE para validar o webhook de cotação.'
        },
        approval: {
            event: 'approval_requested',
            test: true,
            order_code: 'PED-TESTE-001',
            supplier: 'Fornecedor de Teste LTDA',
            total: 4750.00,
            items_count: 5,
            approval_url: window.location.origin + '/pedido/aprovacao/token-de-teste-xyz789',
            quoted_by: 'Cotador Teste',
            quoted_at: new Date().toISOString()
        },
        completed: {
            event: 'order_approved',
            test: true,
            order_code: 'PED-TESTE-001',
            supplier: 'Fornecedor de Teste LTDA',
            total: 4750.00,
            approved_by: 'Aprovador Teste',
            approved_at: new Date().toISOString(),
            pdf_url: window.location.origin + '/pedido/pdf/999'
        }
    };

    const payload = payloads[type];
    const labels = { quote: 'Cotação', approval: 'Aprovação', completed: 'Conclusão' };

    // Mostrar modal
    document.getElementById('testResultTitle').textContent = 'Teste Webhook - ' + labels[type];
    document.getElementById('testResultLoading').style.display = '';
    document.getElementById('testResultContent').style.display = 'none';
    document.getElementById('testPayload').textContent = JSON.stringify(payload, null, 2);
    
    const modal = new bootstrap.Modal(document.getElementById('testResultModal'));
    modal.show();

    try {
        const resp = await fetch('/admin/orders/test-webhook', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                url: url,
                payload: JSON.stringify(payload)
            })
        });

        const data = await resp.json();
        
        document.getElementById('testResultLoading').style.display = 'none';
        document.getElementById('testResultContent').style.display = '';
        
        if (data.success) {
            document.getElementById('testResultStatus').className = 'alert alert-success';
            document.getElementById('testResultStatus').innerHTML = '<i class="bi bi-check-circle"></i> <strong>Sucesso!</strong> Webhook respondeu com HTTP ' + (data.http_code || '200');
        } else {
            document.getElementById('testResultStatus').className = 'alert alert-danger';
            document.getElementById('testResultStatus').innerHTML = '<i class="bi bi-x-circle"></i> <strong>Erro!</strong> ' + (data.error || 'Falha na conexão');
        }
        
        document.getElementById('testResponse').textContent = data.response || '(sem corpo na resposta)';
    } catch (e) {
        document.getElementById('testResultLoading').style.display = 'none';
        document.getElementById('testResultContent').style.display = '';
        document.getElementById('testResultStatus').className = 'alert alert-danger';
        document.getElementById('testResultStatus').innerHTML = '<i class="bi bi-x-circle"></i> <strong>Erro de conexão:</strong> ' + e.message;
        document.getElementById('testResponse').textContent = '';
    }
}
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
