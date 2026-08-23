<?php $pageTitle = 'Gerenciar Ciclos'; $currentPage = 'weekly_materials'; ?>
<?php ob_start(); ?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <a href="/admin/weekly-materials" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar</a>
    <h5 class="mb-0"><i class="bi bi-arrow-repeat"></i> Gerenciar Ciclos</h5>
    <form method="POST" action="/admin/weekly-materials/generate" class="d-inline">
        <input type="hidden" name="week_start" value="<?= htmlspecialchars($nextCycle) ?>">
        <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Gerar ciclo de <?= (int) $cycleDays ?> dias iniciando em <?= date('d/m/Y', strtotime($nextCycle)) ?>?')">
            <i class="bi bi-plus-lg"></i> Gerar Ciclo (<?= date('d/m/Y', strtotime($nextCycle)) ?>)
        </button>
    </form>
</div>

<div class="alert alert-info small">
    <i class="bi bi-info-circle"></i>
    Ao apagar um ciclo, a próxima geração recomeça a partir da data dele. Ex.: se o último ciclo era 25/08 e você apaga, a próxima geração volta a oferecer 25/08. Útil para testes.
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-calendar-week"></i> Ciclos Gerados</div>
    <div class="card-body p-0">
        <?php if (empty($weeks)): ?>
        <div class="text-center py-4 text-muted">
            <p class="mb-0">Nenhum ciclo gerado ainda.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Ciclo (início)</th>
                        <th class="text-center">Solicitações</th>
                        <th class="text-center">Preenchidas</th>
                        <th class="text-center">Pendentes</th>
                        <th class="text-center">Atrasadas</th>
                        <th class="text-center">Pedidos</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($weeks as $w): ?>
                    <tr>
                        <td>
                            <strong><?= date('d/m/Y', strtotime($w['week_start'])) ?></strong>
                            <small class="text-muted d-block">a <?= date('d/m/Y', strtotime($w['week_start'] . ' +' . (max(1, (int)$cycleDays) - 1) . ' days')) ?></small>
                        </td>
                        <td class="text-center"><?= (int) $w['total_managers'] ?></td>
                        <td class="text-center"><span class="badge bg-success"><?= (int) $w['filled_count'] ?></span></td>
                        <td class="text-center"><span class="badge bg-warning text-dark"><?= (int) $w['pending_count'] ?></span></td>
                        <td class="text-center"><span class="badge bg-danger"><?= (int) $w['overdue_count'] ?></span></td>
                        <td class="text-center"><span class="badge bg-primary"><?= (int) $w['orders_count'] ?></span></td>
                        <td class="text-end">
                            <a href="/admin/weekly-materials/week/<?= $w['week_start'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i> Ver</a>
                            <form method="POST" action="/admin/weekly-materials/delete-cycle" class="d-inline">
                                <input type="hidden" name="week_start" value="<?= htmlspecialchars($w['week_start']) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Apagar o ciclo de <?= date('d/m/Y', strtotime($w['week_start'])) ?>? Isso remove <?= (int) $w['total_managers'] ?> solicitação(ões) e seus itens. Ação irreversível.')">
                                    <i class="bi bi-trash"></i> Apagar
                                </button>
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

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
