<?php $pageTitle = 'Newsletter - Inscritos'; $currentPage = 'newsletter'; ob_start(); ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h6 class="mb-0">Lista de Inscritos</h6>
        <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-sm btn-outline-success" onclick="resendMagazineAll()" title="Reenviar última revista via WhatsApp para todos com telefone">
                <i class="bi bi-whatsapp"></i> Reenviar Revista (WhatsApp)
            </button>
            <a href="/admin/newsletter/export" class="btn btn-sm btn-success">
                <i class="bi bi-download"></i> Exportar CSV
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($subscribers)): ?>
            <p class="text-muted text-center py-3">Nenhum inscrito ainda.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>WhatsApp</th>
                            <th>Data de Inscrição</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscribers as $index => $sub): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= htmlspecialchars($sub['name'] ?: '-') ?></td>
                            <td><?= htmlspecialchars($sub['email']) ?></td>
                            <td><?= htmlspecialchars($sub['phone'] ?? '-') ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($sub['subscribed_at'])) ?></td>
                            <td>
                                <span class="badge bg-<?= $sub['active'] ? 'success' : 'secondary' ?>">
                                    <?= $sub['active'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($sub['phone'])): ?>
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="resendMagazineTo(<?= $sub['id'] ?>, '<?= htmlspecialchars($sub['name'] ?: $sub['phone']) ?>')" title="Reenviar revista via WhatsApp">
                                    <i class="bi bi-whatsapp"></i>
                                </button>
                                <?php endif; ?>
                                <form method="POST" action="/admin/newsletter/delete" class="d-inline" onsubmit="return confirm('Remover este inscrito?')">
                                    <input type="hidden" name="id" value="<?= $sub['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-muted small mt-2">
                Total: <?= count($subscribers) ?> inscrito(s)
            </div>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>

<script>
async function resendMagazineTo(subscriberId, name) {
    if (!confirm('Reenviar a última revista via WhatsApp para ' + name + '?')) return;

    try {
        const resp = await fetch('/admin/newsletter/resend-whatsapp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ subscriber_id: subscriberId })
        });
        const data = await resp.json();
        if (data.success) {
            alert('Enviado com sucesso para ' + name + '!');
        } else {
            alert(data.error || 'Erro ao enviar.');
        }
    } catch (e) {
        alert('Erro de conexão.');
    }
}

async function resendMagazineAll() {
    if (!confirm('Reenviar a última revista via WhatsApp para TODOS os inscritos com telefone?')) return;

    try {
        const resp = await fetch('/admin/newsletter/resend-whatsapp-all', {
            method: 'POST'
        });
        const data = await resp.json();
        if (data.success) {
            alert('Enviado para ' + data.count + ' inscrito(s)!');
        } else {
            alert(data.error || 'Erro ao enviar.');
        }
    } catch (e) {
        alert('Erro de conexão.');
    }
}
</script>
