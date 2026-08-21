<?php $pageTitle = 'Lista Semanal de Materiais'; $currentPage = 'weekly_materials'; ?>
<?php ob_start(); ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <span class="badge bg-secondary"><?= count($managers) ?> gerente(s) cadastrado(s)</span>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#managerModal">
            <i class="bi bi-person-plus"></i> Novo Gerente
        </button>
        <form method="POST" action="/admin/weekly-materials/generate" class="d-inline">
            <input type="hidden" name="week_start" value="<?= \App\Models\WeeklyMaterialRequest::nextWeekStart() ?>">
            <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Gerar registros para a semana de <?= date('d/m/Y', strtotime(\App\Models\WeeklyMaterialRequest::nextWeekStart())) ?>?')">
                <i class="bi bi-plus-lg"></i> Gerar Semana
            </button>
        </form>
    </div>
</div>

<!-- Gerentes Cadastrados -->
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-people"></i> Gerentes</div>
    <div class="card-body p-0">
        <?php if (empty($managers)): ?>
        <div class="text-center py-4 text-muted">
            <p class="mb-0">Nenhum gerente cadastrado. Adicione o primeiro.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th>Email</th>
                        <th>Obra</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($managers as $m): ?>
                    <tr class="<?= !$m['active'] ? 'text-muted' : '' ?>">
                        <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
                        <td><?= htmlspecialchars($m['phone'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($m['email'] ?? '-') ?></td>
                        <td><?= !empty($m['construction_site_name']) ? htmlspecialchars($m['construction_site_code'] . ' - ' . $m['construction_site_name']) : '-' ?></td>
                        <td>
                            <?= $m['active'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>' ?>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="/admin/weekly-materials/toggle-manager" class="d-inline">
                                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary" title="<?= $m['active'] ? 'Desativar' : 'Ativar' ?>">
                                    <i class="bi bi-<?= $m['active'] ? 'pause' : 'play' ?>-fill"></i>
                                </button>
                            </form>
                            <form method="POST" action="/admin/weekly-materials/delete-manager" class="d-inline" onsubmit="return confirm('Remover este gerente?')">
                                <input type="hidden" name="id" value="<?= $m['id'] ?>">
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

<!-- Modal Novo Gerente -->
<div class="modal fade" id="managerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="/admin/weekly-materials/store-manager">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus"></i> Cadastrar Gerente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome *</label>
                        <input type="text" class="form-control" name="name" required placeholder="Nome completo">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" class="form-control" name="phone" placeholder="5511999999999">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" placeholder="email@empresa.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Obra (opcional)</label>
                        <select class="form-select" name="construction_site_id">
                            <option value="">-- Nenhuma --</option>
                            <?php
                            try {
                                $sites = \App\Models\ConstructionSite::allActive();
                                foreach ($sites as $site):
                            ?>
                            <option value="<?= $site['id'] ?>"><?= htmlspecialchars($site['code'] . ' - ' . $site['name']) ?></option>
                            <?php endforeach; } catch (\Exception $e) {} ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
