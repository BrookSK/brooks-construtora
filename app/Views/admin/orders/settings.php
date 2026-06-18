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
                        <input type="url" class="form-control" name="orders_quote_webhook" placeholder="https://..." value="<?= htmlspecialchars($settings['orders_quote_webhook'] ?? '') ?>">
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
                        <input type="url" class="form-control" name="orders_approval_webhook" placeholder="https://..." value="<?= htmlspecialchars($settings['orders_approval_webhook'] ?? '') ?>">
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
                        <input type="url" class="form-control" name="orders_completed_webhook" placeholder="https://..." value="<?= htmlspecialchars($settings['orders_completed_webhook'] ?? '') ?>">
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

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
