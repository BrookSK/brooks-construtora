<?php $pageTitle = 'Lista Semanal de Materiais'; $currentPage = 'weekly_materials'; ?>
<?php ob_start(); ?>

<?php
$weeklyManagers = \App\Core\Database::fetchAll("SELECT id, name, phone, email FROM pin_users WHERE active = 1 AND is_weekly_manager = 1 ORDER BY name ASC");
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <span class="badge bg-secondary"><?= count($weeklyManagers) ?> gerente(s) ativo(s)</span>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="/admin/orders/pin-users" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-people"></i> Gerenciar Gerentes
        </a>
        <form method="POST" action="/admin/weekly-materials/generate" class="d-inline">
            <input type="hidden" name="week_start" value="<?= \App\Models\WeeklyMaterialRequest::nextWeekStart() ?>">
            <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Gerar registros para a semana de <?= date('d/m/Y', strtotime(\App\Models\WeeklyMaterialRequest::nextWeekStart())) ?>?')">
                <i class="bi bi-plus-lg"></i> Gerar Semana
            </button>
        </form>
        <form method="POST" action="/admin/weekly-materials/send-now" class="d-inline">
            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Enviar notificação AGORA para todos os gerentes pendentes?')">
                <i class="bi bi-send"></i> Enviar Agora
            </button>
        </form>
        <form method="POST" action="/admin/weekly-materials/send-reminder" class="d-inline">
            <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Enviar COBRANÇA para quem não preencheu?')">
                <i class="bi bi-bell"></i> Cobrar Pendentes
            </button>
        </form>
    </div>
</div>

<!-- Histórico de Semanas -->
<div class="card">
    <div class="card-header"><i class="bi bi-calendar-week"></i> Histórico por Semana</div>
    <div class="card-body p-0">
        <?php if (empty($weeks)): ?>
        <div class="text-center py-4 text-muted">
            <p class="mb-0">Nenhuma semana gerada ainda. Clique em "Gerar Semana" acima.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Semana</th>
                        <th class="text-center">Total</th>
                        <th class="text-center">Preenchidos</th>
                        <th class="text-center">Pendentes</th>
                        <th class="text-center">Atrasados</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($weeks as $w): ?>
                    <tr>
                        <td>
                            <strong><?= date('d/m/Y', strtotime($w['week_start'])) ?></strong>
                            <small class="text-muted d-block">a <?= date('d/m/Y', strtotime($w['week_start'] . ' +6 days')) ?></small>
                        </td>
                        <td class="text-center"><?= $w['total_managers'] ?></td>
                        <td class="text-center"><span class="badge bg-success"><?= $w['filled_count'] ?></span></td>
                        <td class="text-center"><span class="badge bg-warning text-dark"><?= $w['pending_count'] ?></span></td>
                        <td class="text-center"><span class="badge bg-danger"><?= $w['overdue_count'] ?></span></td>
                        <td class="text-end">
                            <a href="/admin/weekly-materials/week/<?= $w['week_start'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> Ver
                            </a>
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
