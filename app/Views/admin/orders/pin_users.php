<?php $pageTitle = 'Usuários e Convites (PIN)'; $currentPage = 'orders_settings'; ?>
<?php ob_start(); ?>

<?php
$roleLabels = ['buyer'=>'Comprador/Entrega','quoter'=>'Cotador','approver'=>'Aprovador','payment'=>'Financeiro','delivery'=>'Comprador/Entrega','all'=>'Completo'];
$roleColors = ['buyer'=>'primary','quoter'=>'warning','approver'=>'info','payment'=>'success','delivery'=>'primary','all'=>'secondary'];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Usuários e Convites (PIN)</h5>
    <a href="/admin/orders/settings" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Config. Pedidos</a>
</div>

<div class="row g-4">
    <!-- Gerar convite -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-link-45deg"></i> <strong>Gerar Link de Convite</strong></div>
            <div class="card-body">
                <form method="POST" action="/admin/orders/generate-invite">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Permissão *</label>
                        <select class="form-select" name="role" required>
                            <option value="buyer">Comprador / Entrega (criar pedidos + checklist)</option>
                            <option value="quoter">Cotador (fazer orçamentos)</option>
                            <option value="approver">Aprovador (aprovar pedidos)</option>
                            <option value="payment">Financeiro (NF/Boleto)</option>
                            <option value="all">Acesso Completo</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Descrição (opcional)</label>
                        <input type="text" class="form-control form-control-sm" name="description" placeholder="Ex: Para o Bruno da obra">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Limite de usos</label>
                        <input type="number" class="form-control form-control-sm" name="max_uses" placeholder="Vazio = ilimitado" min="1">
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-circle"></i> Gerar Convite</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Convites ativos -->
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-envelope-open"></i> Convites Gerados</div>
            <?php if (empty($invites)): ?>
            <div class="card-body text-center text-muted py-3">Nenhum convite gerado.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0" style="font-size:0.8rem;">
                    <thead class="table-light">
                        <tr><th>Permissão</th><th>Link</th><th>Usos</th><th>Descrição</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($invites as $inv): ?>
                    <tr>
                        <td><span class="badge bg-<?= $roleColors[$inv['role']] ?? 'secondary' ?>"><?= $roleLabels[$inv['role']] ?? $inv['role'] ?></span></td>
                        <td>
                            <div class="input-group input-group-sm" style="max-width:280px;">
                                <input type="text" class="form-control" value="<?= $baseUrl ?>/pin/cadastro/<?= $inv['token'] ?>" readonly style="font-size:0.65rem;">
                                <button class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(this.previousElementSibling.value);this.innerHTML='<i class=\'bi bi-check\'></i>'"><i class="bi bi-clipboard"></i></button>
                            </div>
                        </td>
                        <td><?= $inv['uses'] ?><?= $inv['max_uses'] ? '/' . $inv['max_uses'] : '' ?></td>
                        <td><small><?= htmlspecialchars($inv['description'] ?? '-') ?></small></td>
                        <td class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-success p-0 px-1" onclick="sendInviteWebhook('<?= $inv['token'] ?>', '<?= $inv['role'] ?>')" title="Enviar via Webhook"><i class="bi bi-send"></i></button>
                            <form method="POST" action="/admin/orders/delete-invite" class="d-inline" onsubmit="return confirm('Excluir?')">
                                <input type="hidden" name="id" value="<?= $inv['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger p-0 px-1"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Usuários cadastrados -->
        <div class="card">
            <div class="card-header"><i class="bi bi-people"></i> Usuários Cadastrados</div>
            <?php if (empty($users)): ?>
            <div class="card-body text-center text-muted py-3">Nenhum usuário cadastrado via PIN.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0" style="font-size:0.8rem;">
                    <thead class="table-light">
                        <tr><th>Nome</th><th>PIN</th><th>Permissão</th><th>E-mail</th><th>Último login</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr class="<?= !$u['active'] ? 'text-decoration-line-through text-muted' : '' ?>">
                        <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
                        <td><code><?= $u['pin'] ?></code></td>
                        <td>
                            <form method="POST" action="/admin/orders/update-pin-user" class="d-inline">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <select class="form-select form-select-sm d-inline-block" name="role" style="width:auto; font-size:0.7rem;" onchange="this.form.submit()">
                                    <option value="buyer" <?= in_array($u['role'], ['buyer','delivery']) ? 'selected' : '' ?>>Comprador/Entrega</option>
                                    <option value="quoter" <?= $u['role'] === 'quoter' ? 'selected' : '' ?>>Cotador</option>
                                    <option value="approver" <?= $u['role'] === 'approver' ? 'selected' : '' ?>>Aprovador</option>
                                    <option value="payment" <?= $u['role'] === 'payment' ? 'selected' : '' ?>>Financeiro</option>
                                    <option value="all" <?= $u['role'] === 'all' ? 'selected' : '' ?>>Completo</option>
                                </select>
                            </form>
                        </td>
                        <td><small><?= htmlspecialchars($u['email'] ?? '-') ?></small></td>
                        <td><small><?= $u['last_login_at'] ? date('d/m/Y H:i', strtotime($u['last_login_at'])) : 'Nunca' ?></small></td>
                        <td>
                            <?php if ($u['active']): ?>
                            <form method="POST" action="/admin/orders/delete-pin-user" class="d-inline" onsubmit="return confirm('Desativar este usuário?')">
                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger p-0 px-1"><i class="bi bi-person-x"></i></button>
                            </form>
                            <?php else: ?>
                            <span class="badge bg-secondary">Inativo</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>

<script>
function sendInviteWebhook(token, role) {
    if (!confirm('Enviar link de cadastro via webhook para os números configurados?')) return;
    fetch('/admin/orders/send-invite-webhook', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'token=' + token + '&role=' + role
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) alert(d.message || 'Enviado!');
        else alert(d.error || 'Erro ao enviar.');
    })
    .catch(() => alert('Erro de conexão.'));
}
</script>

<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
