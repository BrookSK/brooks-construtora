<?php $pageTitle = 'Cadastro de EPI'; $currentPage = 'epi_catalog'; $user = $user ?? \App\Core\Auth::user(); ?>
<?php ob_start(); ?>

<div style="max-width: 900px;">
    <!-- Novo EPI -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-bold"><i class="bi bi-plus-circle text-primary"></i> Novo EPI</div>
        <div class="card-body">
            <form method="POST" action="/cadastro-de-epi/salvar" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-bold">Nome do EPI *</label>
                    <input type="text" class="form-control" name="name" required placeholder="Ex: Capacete, Luva, Botina...">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">CA</label>
                    <input type="text" class="form-control" name="ca" placeholder="Ex: 12345">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Dias mín. p/ troca *</label>
                    <input type="number" class="form-control" name="min_replacement_days" min="0" value="0" required>
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
                </div>
                <div class="col-12"><small class="text-muted">Dias mínimos que o colaborador precisa esperar antes de solicitar a substituição deste EPI.</small></div>
            </form>
        </div>
    </div>

    <!-- Lista -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold"><i class="bi bi-shield-check text-success"></i> EPIs Cadastrados</div>
        <div class="card-body p-0">
            <?php $active = array_filter($epis, fn($e) => (int)$e['active'] === 1); ?>
            <?php if (empty($active)): ?>
            <p class="text-muted text-center py-4 mb-0"><i class="bi bi-inbox"></i> Nenhum EPI cadastrado ainda.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>Nome</th><th>CA</th><th>Dias mín. troca</th><th style="width:120px;"></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($active as $e): ?>
                        <tr>
                            <td><?= htmlspecialchars($e['name']) ?></td>
                            <td><?= htmlspecialchars($e['ca'] ?? '—') ?></td>
                            <td><?= (int) $e['min_replacement_days'] ?> dias</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick='editEpi(<?= json_encode($e) ?>)'><i class="bi bi-pencil"></i></button>
                                <form method="POST" action="/cadastro-de-epi/excluir" class="d-inline" onsubmit="return confirm('Remover este EPI?');">
                                    <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
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

<!-- Modal Editar -->
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <form method="POST" action="/cadastro-de-epi/atualizar">
        <div class="modal-header py-2"><h6 class="modal-title"><i class="bi bi-pencil"></i> Editar EPI</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" name="id" id="editId">
            <div class="mb-2"><label class="form-label small fw-bold">Nome *</label><input type="text" class="form-control" name="name" id="editName" required></div>
            <div class="mb-2"><label class="form-label small fw-bold">CA</label><input type="text" class="form-control" name="ca" id="editCa"></div>
            <div class="mb-2"><label class="form-label small fw-bold">Dias mín. p/ troca *</label><input type="number" class="form-control" name="min_replacement_days" id="editDays" min="0" required></div>
        </div>
        <div class="modal-footer py-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg"></i> Salvar alterações</button></div>
    </form>
</div></div></div>

<script>
function editEpi(e) {
    document.getElementById('editId').value = e.id;
    document.getElementById('editName').value = e.name;
    document.getElementById('editCa').value = e.ca || '';
    document.getElementById('editDays').value = e.min_replacement_days;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
