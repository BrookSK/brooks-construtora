<?php $pageTitle = 'Configurações de Pedidos'; $currentPage = 'orders_settings'; ?>
<?php ob_start(); ?>

<form method="POST" action="/admin/orders/settings/update">
    <!-- PIN de acesso rápido -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-key"></i> <strong>Acesso Rápido (PIN)</strong></span>
            <a href="/admin/orders/pin-users" class="btn btn-sm btn-outline-primary"><i class="bi bi-people"></i> Gerenciar Usuários e Convites</a>
        </div>
        <div class="card-body">
            <div class="row align-items-end mb-3">
                <div class="col-md-4">
                    <label class="form-label">Senha global de 4 dígitos</label>
                    <input type="text" class="form-control" name="orders_pin_code" value="<?= htmlspecialchars($settings['orders_pin_code'] ?? '') ?>" maxlength="4" pattern="\d{4}" placeholder="0000" style="font-size:1.5rem; text-align:center; letter-spacing:8px; max-width:180px;">
                </div>
                <div class="col-md-8">
                    <p class="text-muted small mb-0">
                        Esta senha permite acesso ao painel de pedidos sem login/email.<br>
                        Acesso via: <strong><a href="/pedidos" target="_blank">/pedidos</a></strong> — sessão mantida por 30 dias.
                    </p>
                </div>
            </div>
            <div class="form-check form-switch mb-2">
                <input class="form-check-input" type="checkbox" name="orders_pin_global_active" value="1" id="pinGlobalActive" <?= ($settings['orders_pin_global_active'] ?? '1') === '1' ? 'checked' : '' ?>>
                <label class="form-check-label fw-bold" for="pinGlobalActive">PIN global ativo</label>
            </div>
            <p class="text-muted small mt-1 mb-0">
                <strong>Ativado:</strong> Qualquer pessoa pode entrar usando a senha global acima.<br>
                <strong>Desativado:</strong> A senha global é ignorada — cada pessoa precisa usar sua conta individual (PIN pessoal). A senha não é apagada, apenas desativada.
            </p>
            <hr>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="orders_require_pin_login" value="1" id="requirePinLogin" <?= ($settings['orders_require_pin_login'] ?? '0') === '1' ? 'checked' : '' ?>>
                <label class="form-check-label fw-bold" for="requirePinLogin">Exigir login com PIN para acessar links públicos</label>
            </div>
            <p class="text-muted small mt-1 mb-0">
                Quando ativado, os links de cotação, aprovação, checklist e etc. exigirão que a pessoa esteja logada com PIN antes de acessar.<br>
                Quando desativado, qualquer pessoa com o link pode acessar (comportamento atual).
            </p>
        </div>
    </div>

    <!-- Cron de Notificações -->
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-arrow-repeat"></i> <strong>Fila de Notificações (Cron)</strong></div>
        <div class="card-body">
            <?php
            $pendingCount = \App\Models\NotificationQueue::countPending();
            $baseUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
            $cronToken = \App\Models\Setting::get('cron_token', '');
            ?>
            <div class="row">
                <div class="col-md-6">
                    <p class="small mb-2"><strong>Status:</strong> 
                        <?php if ($pendingCount > 0): ?>
                        <span class="badge bg-warning"><?= $pendingCount ?> pendente(s)</span>
                        <?php else: ?>
                        <span class="badge bg-success">Fila vazia</span>
                        <?php endif; ?>
                    </p>
                    <p class="small mb-2"><strong>URL do Cron:</strong></p>
                    <div class="input-group input-group-sm mb-2">
                        <input type="text" class="form-control" value="<?= $baseUrl ?>/cron-notifications.php?token=<?= htmlspecialchars($cronToken) ?>" readonly id="cronUrl">
                        <button class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('cronUrl').value); this.innerHTML='<i class=\'bi bi-check\'></i>'"><i class="bi bi-clipboard"></i></button>
                    </div>
                </div>
                <div class="col-md-6">
                    <p class="small mb-2"><strong>Intervalo:</strong> A cada 1 minuto</p>
                    <p class="small mb-2"><strong>Token:</strong> Configurado em <a href="/admin/settings">Configurações Gerais</a> (campo "Cron Token")</p>
                    <p class="small text-muted mb-0">Configure esta URL em um serviço de cron externo (ex: cron-job.org) ou no crontab do servidor.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Configuração de Aprovação de Transferência -->
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-shield-check"></i> <strong>Aprovação de Transferência</strong></div>
        <div class="card-body">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="orders_require_transfer_approval" value="1" id="requireTransferApproval" <?= ($settings['orders_require_transfer_approval'] ?? '1') === '1' ? 'checked' : '' ?>>
                <label class="form-check-label fw-bold" for="requireTransferApproval">Exigir aprovação para itens de transferência/estoque</label>
            </div>
            <p class="text-muted small mt-2 mb-0">
                <strong>Ativado:</strong> Itens de transferência/estoque passam pela aprovação junto com os itens de compra.<br>
                <strong>Desativado:</strong> Itens de transferência/estoque são aprovados automaticamente. Se o pedido tiver APENAS itens de estoque, pula cotação e aprovação (vai direto pro checklist e notifica o transporte).
            </p>
        </div>
    </div>

    <div class="row">
        <!-- Fase 1: Cotação -->
        <div class="col-12 col-lg-3 mb-3">
            <div class="card h-100">
                <div class="card-header bg-warning bg-opacity-10">
                    <i class="bi bi-1-circle text-warning"></i> <strong>Cotação</strong>
                    <p class="small text-muted mb-0 mt-1">Quem recebe a notificação para informar os preços</p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small">E-mails</label>
                        <textarea class="form-control form-control-sm" name="orders_quote_emails" rows="2" placeholder="email1@empresa.com"><?= htmlspecialchars($settings['orders_quote_emails'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Nome (Webhook)</label>
                        <input type="text" class="form-control form-control-sm" name="orders_quote_phone_name" placeholder="Ex: João" value="<?= htmlspecialchars($settings['orders_quote_phone_name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Telefone (Webhook)</label>
                        <input type="text" class="form-control form-control-sm" name="orders_quote_phone" placeholder="5511999999999" value="<?= htmlspecialchars($settings['orders_quote_phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">URL Webhook</label>
                        <div class="input-group input-group-sm">
                            <input type="url" class="form-control" name="orders_quote_webhook" id="webhook_quote" placeholder="https://..." value="<?= htmlspecialchars($settings['orders_quote_webhook'] ?? '') ?>">
                            <button type="button" class="btn btn-outline-warning" onclick="testWebhook('quote')" title="Testar"><i class="bi bi-lightning"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fase 2: Aprovação -->
        <div class="col-12 col-lg-3 mb-3">
            <div class="card h-100">
                <div class="card-header bg-info bg-opacity-10">
                    <i class="bi bi-2-circle text-info"></i> <strong>Aprovação</strong>
                    <p class="small text-muted mb-0 mt-1">Quem recebe para aprovar ou rejeitar</p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small">E-mails</label>
                        <textarea class="form-control form-control-sm" name="orders_approval_emails" rows="2" placeholder="gestor@empresa.com"><?= htmlspecialchars($settings['orders_approval_emails'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Nome (Webhook)</label>
                        <input type="text" class="form-control form-control-sm" name="orders_approval_phone_name" placeholder="Ex: Carlos" value="<?= htmlspecialchars($settings['orders_approval_phone_name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Telefone (Webhook)</label>
                        <input type="text" class="form-control form-control-sm" name="orders_approval_phone" placeholder="5511999999999" value="<?= htmlspecialchars($settings['orders_approval_phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">URL Webhook</label>
                        <div class="input-group input-group-sm">
                            <input type="url" class="form-control" name="orders_approval_webhook" id="webhook_approval" placeholder="https://..." value="<?= htmlspecialchars($settings['orders_approval_webhook'] ?? '') ?>">
                            <button type="button" class="btn btn-outline-info" onclick="testWebhook('approval')" title="Testar"><i class="bi bi-lightning"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fase 3: Conclusão -->
        <div class="col-12 col-lg-3 mb-3">
            <div class="card h-100">
                <div class="card-header bg-success bg-opacity-10">
                    <i class="bi bi-3-circle text-success"></i> <strong>Conclusão</strong>
                    <p class="small text-muted mb-0 mt-1">Quem recebe quando aprovado ou rejeitado</p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small">E-mails</label>
                        <textarea class="form-control form-control-sm" name="orders_completed_emails" rows="2" placeholder="admin@empresa.com"><?= htmlspecialchars($settings['orders_completed_emails'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Nome (Webhook)</label>
                        <input type="text" class="form-control form-control-sm" name="orders_completed_phone_name" placeholder="Ex: Paulo" value="<?= htmlspecialchars($settings['orders_completed_phone_name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Telefone (Webhook)</label>
                        <input type="text" class="form-control form-control-sm" name="orders_completed_phone" placeholder="5511999999999" value="<?= htmlspecialchars($settings['orders_completed_phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">URL Webhook</label>
                        <div class="input-group input-group-sm">
                            <input type="url" class="form-control" name="orders_completed_webhook" id="webhook_completed" placeholder="https://..." value="<?= htmlspecialchars($settings['orders_completed_webhook'] ?? '') ?>">
                            <button type="button" class="btn btn-outline-success" onclick="testWebhook('completed')" title="Testar"><i class="bi bi-lightning"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fase 4: Pagamento / NF -->
        <div class="col-12 col-lg-3 mb-3">
            <div class="card h-100">
                <div class="card-header bg-primary bg-opacity-10">
                    <i class="bi bi-4-circle text-primary"></i> <strong>Pagamento / NF</strong>
                    <p class="small text-muted mb-0 mt-1">Quem recebe quando NF ou boleto é enviado</p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small">E-mails</label>
                        <textarea class="form-control form-control-sm" name="orders_payment_emails" rows="2" placeholder="financeiro@empresa.com"><?= htmlspecialchars($settings['orders_payment_emails'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Nome (Webhook)</label>
                        <input type="text" class="form-control form-control-sm" name="orders_payment_phone_name" placeholder="Ex: Financeiro" value="<?= htmlspecialchars($settings['orders_payment_phone_name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Telefone (Webhook)</label>
                        <input type="text" class="form-control form-control-sm" name="orders_payment_phone" placeholder="5511999999999" value="<?= htmlspecialchars($settings['orders_payment_phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">URL Webhook</label>
                        <div class="input-group input-group-sm">
                            <input type="url" class="form-control" name="orders_payment_webhook" id="webhook_payment" placeholder="https://..." value="<?= htmlspecialchars($settings['orders_payment_webhook'] ?? '') ?>">
                            <button type="button" class="btn btn-outline-primary" onclick="testWebhook('payment')" title="Testar"><i class="bi bi-lightning"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fase 5: Entrega / Checklist -->
        <div class="col-12 col-lg-3 mb-3">
            <div class="card h-100">
                <div class="card-header bg-dark bg-opacity-10">
                    <i class="bi bi-5-circle text-dark"></i> <strong>Entrega</strong>
                    <p class="small text-muted mb-0 mt-1">Quem recebe o link do checklist de entrega</p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small">E-mails</label>
                        <textarea class="form-control form-control-sm" name="orders_delivery_emails" rows="2" placeholder="obra@empresa.com"><?= htmlspecialchars($settings['orders_delivery_emails'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Nome (Webhook)</label>
                        <input type="text" class="form-control form-control-sm" name="orders_delivery_phone_name" placeholder="Ex: Mestre de Obras" value="<?= htmlspecialchars($settings['orders_delivery_phone_name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Telefone (Webhook)</label>
                        <input type="text" class="form-control form-control-sm" name="orders_delivery_phone" placeholder="5511999999999" value="<?= htmlspecialchars($settings['orders_delivery_phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">URL Webhook</label>
                        <div class="input-group input-group-sm">
                            <input type="url" class="form-control" name="orders_delivery_webhook" id="webhook_delivery" placeholder="https://..." value="<?= htmlspecialchars($settings['orders_delivery_webhook'] ?? '') ?>">
                            <button type="button" class="btn btn-outline-dark" onclick="testWebhook('delivery')" title="Testar"><i class="bi bi-lightning"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fase 6: Itens Sobressalentes -->
        <div class="col-12 col-lg-3 mb-3">
            <div class="card h-100">
                <div class="card-header bg-warning bg-opacity-25">
                    <i class="bi bi-6-circle text-dark"></i> <strong>Sobressalentes</strong>
                    <p class="small text-muted mb-0 mt-1">Quem recebe quando item avulso é adicionado</p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small">E-mails</label>
                        <textarea class="form-control form-control-sm" name="orders_spare_emails" rows="2" placeholder="gestor@empresa.com"><?= htmlspecialchars($settings['orders_spare_emails'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Nome (Webhook)</label>
                        <input type="text" class="form-control form-control-sm" name="orders_spare_phone_name" placeholder="Ex: Gestor" value="<?= htmlspecialchars($settings['orders_spare_phone_name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Telefone (Webhook)</label>
                        <input type="text" class="form-control form-control-sm" name="orders_spare_phone" placeholder="5511999999999" value="<?= htmlspecialchars($settings['orders_spare_phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">URL Webhook</label>
                        <div class="input-group input-group-sm">
                            <input type="url" class="form-control" name="orders_spare_webhook" id="webhook_spare" placeholder="https://..." value="<?= htmlspecialchars($settings['orders_spare_webhook'] ?? '') ?>">
                            <button type="button" class="btn btn-outline-warning" onclick="testWebhook('spare')" title="Testar"><i class="bi bi-lightning"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transporte (Wilton) -->
        <div class="col-12 col-lg-3 mb-3">
            <div class="card h-100 border-primary">
                <div class="card-header bg-primary bg-opacity-10">
                    <i class="bi bi-truck text-primary"></i> <strong>Transporte (Estoque)</strong>
                    <p class="small text-muted mb-0 mt-1">Responsável pelo transporte de materiais entre obras</p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small">E-mails</label>
                        <textarea class="form-control form-control-sm" name="orders_transport_emails" rows="2" placeholder="wilton@empresa.com"><?= htmlspecialchars($settings['orders_transport_emails'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Nome (Webhook)</label>
                        <input type="text" class="form-control form-control-sm" name="orders_transport_phone_name" placeholder="Ex: Wilton" value="<?= htmlspecialchars($settings['orders_transport_phone_name'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Telefone (Webhook)</label>
                        <input type="text" class="form-control form-control-sm" name="orders_transport_phone" placeholder="5511999999999" value="<?= htmlspecialchars($settings['orders_transport_phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">URL Webhook</label>
                        <div class="input-group input-group-sm">
                            <input type="url" class="form-control" name="orders_transport_webhook" id="webhook_transport" placeholder="https://..." value="<?= htmlspecialchars($settings['orders_transport_webhook'] ?? '') ?>">
                            <button type="button" class="btn btn-outline-primary" onclick="testWebhook('transport')" title="Testar"><i class="bi bi-lightning"></i></button>
                        </div>
                    </div>
                    <div class="alert alert-light small py-2 mb-0">
                        <i class="bi bi-info-circle"></i> Notificado quando há transferência ou saída de estoque.
                    </div>
                </div>
            </div>
        </div>

        <!-- Envio de Cotação para Fornecedores -->
        <div class="col-12 col-lg-3 mb-3">
            <div class="card h-100 border-success">
                <div class="card-header bg-success bg-opacity-10">
                    <i class="bi bi-whatsapp text-success"></i> <strong>Envio de Cotação</strong>
                    <p class="small text-muted mb-0 mt-1">Webhook para enviar cotação aos fornecedores via WhatsApp</p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small">Nome (padrão)</label>
                        <input type="text" class="form-control form-control-sm" name="orders_quote_send_phone_name" placeholder="Ex: Comprador" value="<?= htmlspecialchars($settings['orders_quote_send_phone_name'] ?? '') ?>">
                        <small class="text-muted">Usado quando não há vendedor selecionado</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Telefone (padrão)</label>
                        <input type="text" class="form-control form-control-sm" name="orders_quote_send_phone" placeholder="5511999999999" value="<?= htmlspecialchars($settings['orders_quote_send_phone'] ?? '') ?>">
                        <small class="text-muted">Usado quando não há vendedor selecionado</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">URL Webhook (envio)</label>
                        <div class="input-group input-group-sm">
                            <input type="url" class="form-control" name="orders_quote_send_webhook" id="webhook_quote_send" placeholder="https://..." value="<?= htmlspecialchars($settings['orders_quote_send_webhook'] ?? '') ?>">
                            <button type="button" class="btn btn-outline-success" onclick="testWebhook('quote_send')" title="Testar"><i class="bi bi-lightning"></i></button>
                        </div>
                        <small class="text-muted">Este webhook envia a cotação direto pro WhatsApp do fornecedor.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Mensagem Padrão</label>
                        <textarea class="form-control form-control-sm" name="orders_quote_default_message" rows="6" placeholder="Mensagem que será enviada ao fornecedor..."><?= htmlspecialchars($settings['orders_quote_default_message'] ?? "Olá! Bom dia, tudo bem?\n\nPrecisamos de cotação para os seguintes itens:\n\n{items_list}\n\nObra: {construction_site}\nPedido: {order_code}\n\nPoderia nos enviar o orçamento?\n\nObrigado!") ?></textarea>
                        <small class="text-muted">Variáveis: {items_list}, {construction_site}, {order_code}, {supplier_name}, {vendor_name}</small>
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
            <span class="badge bg-success p-2" style="background-color:#198754!important;">1.5 Verificação Estoque</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-warning p-2">2. Cotação</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-info p-2">3. Aprovação</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-success p-2">4. Aprovado/Rejeitado</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-primary p-2">5. NF/Boleto</span>
            <i class="bi bi-arrow-right text-muted"></i>
            <span class="badge bg-dark p-2">6. Checklist Entrega</span>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2 justify-content-center mt-2">
            <span class="badge bg-warning text-dark p-2">⚡ Sobressalentes</span>
            <span class="text-muted small">— Pode ocorrer a qualquer momento após aprovação</span>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2 justify-content-center mt-2">
            <span class="badge p-2" style="background-color:#0dcaf0;">🚚 Transporte (Estoque)</span>
            <span class="text-muted small">— Notifica o responsável quando sai material do estoque</span>
        </div>
        <p class="text-muted small text-center mt-3 mb-0">
            Cada fase envia notificações (e-mail + webhook) para os responsáveis configurados.<br>
            Na verificação de estoque, o sistema checa disponibilidade em todas as obras antes de enviar para cotação.<br>
            Itens sobressalentes disparam notificação imediata com controle de saldo semanal.
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
    const quotePhone = document.querySelector('[name="orders_quote_phone"]').value;
    const quotePhoneName = document.querySelector('[name="orders_quote_phone_name"]').value;
    const approvalPhone = document.querySelector('[name="orders_approval_phone"]').value;
    const approvalPhoneName = document.querySelector('[name="orders_approval_phone_name"]').value;
    const completedPhone = document.querySelector('[name="orders_completed_phone"]').value;
    const completedPhoneName = document.querySelector('[name="orders_completed_phone_name"]').value;

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
            description: 'Pedido de teste para validar webhook.',
            phone: quotePhone,
            phone_name: quotePhoneName,
            message: '*NOVO PEDIDO - COTAÇÃO PENDENTE*\n\n*Pedido:* PED-TESTE-001\n*Fornecedor:* Fornecedor de Teste LTDA\n*Solicitado por:* Usuário Teste\n*Data:* ' + new Date().toLocaleDateString('pt-BR') + '\n*Itens:* 5\n\n*Lista de materiais:*\n1. Cano - Esgoto (100mm) - Qtd: 10 unid\n2. Joelho - Esgoto (40mm) - Qtd: 20 unid\n3. Caixa D\'Água (500L) - Qtd: 1 unid\n4. Brita 01 - Qtd: 6 mts\n5. Prancha Cedrinho (15x5) - Qtd: 32 mts\n\n*Obs:* Pedido de teste para validar webhook.\n\n*Link para informar cotação:*\n' + window.location.origin + '/pedido/cotacao/token-de-teste-abc123'
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
            quoted_at: new Date().toISOString(),
            phone: approvalPhone,
            phone_name: approvalPhoneName,
            message: '*PEDIDO AGUARDANDO APROVAÇÃO*\n\n*Pedido:* PED-TESTE-001\n*Fornecedor:* Fornecedor de Teste LTDA\n*Valor Total:* R$ 4.750,00\n*Itens:* 5\n*Cotado por:* Cotador Teste\n*Data cotação:* ' + new Date().toLocaleDateString('pt-BR') + '\n\n*Link para aprovar/rejeitar:*\n' + window.location.origin + '/pedido/aprovacao/token-de-teste-xyz789'
        },
        completed: {
            event: 'order_approved',
            test: true,
            order_code: 'PED-TESTE-001',
            supplier: 'Fornecedor de Teste LTDA',
            total: 4750.00,
            approved_by: 'Aprovador Teste',
            approved_at: new Date().toISOString(),
            pdf_url: window.location.origin + '/pedido/pdf/999',
            phone: completedPhone,
            phone_name: completedPhoneName,
            message: '*PEDIDO APROVADO*\n\n*Pedido:* PED-TESTE-001\n*Fornecedor:* Fornecedor de Teste LTDA\n*Valor Total:* R$ 4.750,00\n*Aprovado por:* Aprovador Teste\n*Data:* ' + new Date().toLocaleDateString('pt-BR') + '\n\n*PDF do pedido:*\n' + window.location.origin + '/pedido/pdf/999'
        },
        payment: {
            event: 'payment_uploaded',
            test: true,
            order_code: 'PED-TESTE-001',
            supplier: 'Fornecedor de Teste LTDA',
            total: 4750.00,
            document_type: 'NF',
            document_number: '12345',
            amount: 4750.00,
            due_date: '2026-07-15',
            uploaded_by: 'Comprador Teste',
            panel_url: window.location.origin + '/pedidos',
            phone: document.querySelector('[name="orders_payment_phone"]')?.value || '',
            phone_name: document.querySelector('[name="orders_payment_phone_name"]')?.value || '',
            message: '*NF/BOLETO ENVIADO*\n\n*Pedido:* PED-TESTE-001\n*Fornecedor:* Fornecedor de Teste LTDA\n*Tipo:* NF\n*Numero:* 12345\n*Valor:* R$ 4.750,00\n*Vencimento:* 15/07/2026\n\n*Acesse o painel para conferir:*\n' + window.location.origin + '/pedidos'
        },
        delivery: {
            event: 'delivery_checklist_ready',
            test: true,
            order_code: 'PED-TESTE-001',
            suppliers: ['Fornecedor A', 'Fornecedor B'],
            items_count: 5,
            checklist_url: window.location.origin + '/pedido/entrega/token-de-teste-delivery',
            phone: document.querySelector('[name="orders_delivery_phone"]')?.value || '',
            phone_name: document.querySelector('[name="orders_delivery_phone_name"]')?.value || '',
            message: '*CHECKLIST DE ENTREGA DISPONÍVEL*\n\n*Pedido:* PED-TESTE-001\n*Fornecedores:* Fornecedor A, Fornecedor B\n*Itens:* 5\n\n*Acesse o checklist para conferir as entregas:*\n' + window.location.origin + '/pedido/entrega/token-de-teste-delivery'
        },
        spare: {
            event: 'spare_item_added',
            test: true,
            order_code: 'PED-TESTE-001',
            item: 'Fita Isolante 20m',
            total: 12.90,
            purchased_by: 'Mestre de Obras',
            week_total: 350.00,
            weekly_budget: 1000.00,
            remaining: 650.00,
            exceeded: false,
            phone: document.querySelector('[name="orders_spare_phone"]')?.value || '',
            phone_name: document.querySelector('[name="orders_spare_phone_name"]')?.value || '',
            message: '*ITEM SOBRESSALENTE ADICIONADO*\n\n*Pedido:* PED-TESTE-001\n*Item:* Fita Isolante 20m\n*Valor:* R$ 12,90\n*Comprado por:* Mestre de Obras\n\n*Saldo semanal:*\nGasto: R$ 350,00 / R$ 1.000,00\nRestante: R$ 650,00'
        },
        transport: {
            event: 'stock_movement',
            test: true,
            type: 'transfer',
            material: 'Cano PVC 100mm',
            quantity: 10,
            from_site: 'Obra Alpha',
            to_site: 'Obra Beta',
            requested_by: 'Comprador Teste',
            phone: document.querySelector('[name="orders_transport_phone"]')?.value || '',
            phone_name: document.querySelector('[name="orders_transport_phone_name"]')?.value || '',
            message: '*TRANSFERÊNCIA*\n\n*Material:* Cano PVC 100mm\n*Quantidade:* 10\n*Origem:* Obra Alpha\n*Destino:* Obra Beta\n*Solicitado por:* Comprador Teste\n*Data:* ' + new Date().toLocaleDateString('pt-BR') + ' ' + new Date().toLocaleTimeString('pt-BR', {hour:'2-digit', minute:'2-digit'})
        },
        quote_send: {
            event: 'quote_send_to_supplier',
            test: true,
            order_id: 999,
            supplier_id: 1,
            contact_id: 1,
            supplier_name: 'Fornecedor de Teste LTDA',
            vendor_name: 'Vendedor Teste',
            phone: document.querySelector('[name="orders_quote_send_phone"]')?.value || '',
            phone_name: document.querySelector('[name="orders_quote_send_phone_name"]')?.value || '',
            message: 'Olá! Bom dia, tudo bem?\n\nPrecisamos de cotação para os seguintes itens:\n\n1. Cano PVC 100mm - Qtd: 10 un\n2. Joelho 40mm - Qtd: 20 un\n3. Cimento 50kg - Qtd: 30 sc\n\nObra: Obra Teste\nPedido: PED-TESTE-001\n\nPoderia nos enviar o orçamento?\n\nObrigado!'
        }
    };

    const payload = payloads[type];
    const labels = { quote: 'Cotação', approval: 'Aprovação', completed: 'Conclusão', payment: 'Pagamento/NF', delivery: 'Entrega', spare: 'Sobressalentes', transport: 'Transporte', quote_send: 'Envio de Cotação' };

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
