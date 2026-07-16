<?php
$pageTitle = 'Checklist de Limpeza';
$currentPage = 'cleaning_index';
$user = $user ?? \App\Core\Auth::user();
?>
<?php ob_start(); ?>

<div style="max-width: 1000px;">
    <?php if (!empty($flash)): ?>
    <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?> py-2 small">
        <i class="bi bi-<?= $flash['type'] === 'error' ? 'exclamation-triangle' : 'check-circle' ?>"></i>
        <?= htmlspecialchars($flash['message']) ?>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="bi bi-clipboard-check text-primary"></i> Histórico de Checklists</h5>
        <a href="/checklist-limpeza/novo" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Novo Checklist</a>
    </div>

    <?php if (empty($checklists)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-clipboard" style="font-size: 3rem; opacity: 0.3;"></i>
            <p class="mt-3 mb-0">Nenhum checklist registrado ainda.</p>
            <a href="/checklist-limpeza/novo" class="btn btn-outline-primary mt-3"><i class="bi bi-plus-lg"></i> Criar primeiro checklist</a>
        </div>
    </div>
    <?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Responsável</th>
                            <th>Setores</th>
                            <th>Inspetor</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($checklists as $cl): ?>
                        <?php
                            $sectors = json_decode($cl['sectors'], true) ?: [];
                            $sectorLabels = [
                                'vestiario' => 'Vestiário',
                                'refeicao' => 'Refeição',
                                'almoxarifado' => 'Almoxarifado',
                                'escritorio' => 'Escritório',
                            ];
                        ?>
                        <tr>
                            <td class="small"><?= date('d/m/Y', strtotime($cl['performed_at'])) ?></td>
                            <td class="small"><?= htmlspecialchars($cl['responsible_name']) ?></td>
                            <td class="small">
                                <?php foreach ($sectors as $s): ?>
                                <span class="badge bg-light text-dark border"><?= $sectorLabels[$s] ?? $s ?></span>
                                <?php endforeach; ?>
                            </td>
                            <td class="small"><?= htmlspecialchars($cl['inspector_name'] ?? '—') ?></td>
                            <td class="text-center">
                                <a href="/checklist-limpeza/ver/<?= $cl['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/app/Views/admin/layouts/app.php';
?>
