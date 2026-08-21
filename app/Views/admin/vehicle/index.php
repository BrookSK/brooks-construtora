<?php $pageTitle = 'Controle do Saveiro'; $currentPage = 'vehicle'; ?>
<?php ob_start(); ?>

<?php
$isAvailable = $stats['is_available'];
$currentUsage = $stats['current_usage'];
?>

<!-- Status Atual -->
<div class="card mb-3 <?= $isAvailable ? 'border-success' : 'border-warning' ?>">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center <?= $isAvailable ? 'bg-success' : 'bg-warning' ?> bg-opacity-10" style="width:50px; height:50px;">
                    <i class="bi bi-truck-front <?= $isAvailable ? 'text-success' : 'text-warning' ?>" style="font-size:1.5rem;"></i>
                </div>
                <div>
                    <?php if ($isAvailable): ?>
                    <strong class="text-success">Veículo Disponível</strong>
                    <br><small class="text-muted">Pronto para uso</small>
                    <?php else: ?>
                    <strong class="text-warning">Em uso por <?= htmlspecialchars($currentUsage['driver_name']) ?></strong>
                    <br><small class="text-muted">
                        Desde <?= date('d/m/Y', strtotime($currentUsage['pickup_date'])) ?> às <?= substr($currentUsage['pickup_time'], 0, 5) ?>
                        &middot; Destino: <?= htmlspecialchars($currentUsage['destination']) ?>
                    </small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex gap-2">
                <?php if ($isAvailable): ?>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#pickupModal">
                    <i class="bi bi-key-fill"></i> Registrar Retirada
                </button>
                <?php else: ?>
                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#returnModal">
                    <i class="bi bi-arrow-return-left"></i> Registrar Devolução
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Resumo -->
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Total de Viagens</small>
                <div class="stat-number" style="font-size:1.5rem;"><?= $stats['total_trips'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body py-2 px-3">
                <small class="text-muted">KM Total Percorrido</small>
                <div class="stat-number" style="font-size:1.5rem;"><?= number_format($stats['total_km'], 0, ',', '.') ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Último KM</small>
                <div class="stat-number" style="font-size:1.5rem;"><?= $stats['last_km'] ? number_format($stats['last_km'], 0, ',', '.') : '-' ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card stat-card">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Status</small>
                <div style="font-size:1.2rem;">
                    <?= $isAvailable ? '<span class="badge bg-success">Disponível</span>' : '<span class="badge bg-warning text-dark">Em uso</span>' ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Histórico -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history"></i> Histórico de Uso</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($records)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-truck-front" style="font-size:2.5rem;"></i>
            <p class="mt-2 mb-0">Nenhum registro de uso.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Motorista</th>
                        <th>Saída</th>
                        <th>Destino</th>
                        <th class="text-center">KM Saída</th>
                        <th>Devolução</th>
                        <th class="text-center">KM Devol.</th>
                        <th class="text-center">KM Rodados</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $rec): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($rec['driver_name']) ?></strong>
                            <br><small class="text-muted">por <?= htmlspecialchars($rec['registered_by']) ?></small>
                        </td>
                        <td>
                            <?= date('d/m/Y', strtotime($rec['pickup_date'])) ?>
                            <br><small class="text-muted"><?= substr($rec['pickup_time'], 0, 5) ?> — <?= htmlspecialchars($rec['pickup_location']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($rec['destination']) ?>
                            <?php if (!empty($rec['pickup_notes'])): ?>
                            <br><small class="text-muted fst-italic"><?= htmlspecialchars($rec['pickup_notes']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= number_format($rec['pickup_km'], 0, ',', '.') ?></td>
                        <td>
                            <?php if (!empty($rec['return_date'])): ?>
                            <?= date('d/m/Y', strtotime($rec['return_date'])) ?>
                            <br><small class="text-muted"><?= substr($rec['return_time'], 0, 5) ?> — <?= htmlspecialchars($rec['returned_by'] ?? '') ?></small>
                            <?php if (!empty($rec['return_notes'])): ?>
                            <br><small class="text-muted fst-italic"><?= htmlspecialchars($rec['return_notes']) ?></small>
                            <?php endif; ?>
                            <?php else: ?>
                            <span class="text-warning">Pendente</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= !empty($rec['return_km']) ? number_format($rec['return_km'], 0, ',', '.') : '-' ?></td>
                        <td class="text-center">
                            <?php if (!empty($rec['return_km'])): ?>
                            <strong><?= number_format($rec['return_km'] - $rec['pickup_km'], 0, ',', '.') ?> km</strong>
                            <?php else: ?>
                            -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($rec['return_date'])): ?>
                            <span class="badge bg-success">Devolvido</span>
                            <?php else: ?>
                            <span class="badge bg-warning text-dark">Em uso</span>
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

<!-- Modal Retirada -->
<div class="modal fade" id="pickupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="/admin/vehicle/pickup">
                <div class="modal-header bg-success bg-opacity-10">
                    <h5 class="modal-title"><i class="bi bi-key-fill text-success"></i> Registrar Retirada</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Motorista *</label>
                        <input type="text" class="form-control" name="driver_name" required placeholder="Nome de quem vai usar o veículo">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Data de Saída *</label>
                            <input type="date" class="form-control" name="pickup_date" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Horário de Saída *</label>
                            <input type="time" class="form-control" name="pickup_time" required value="<?= date('H:i') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Local de Saída *</label>
                        <input type="text" class="form-control" name="pickup_location" required placeholder="Ex: Escritório, Obra X...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quilometragem de Saída *</label>
                        <input type="number" class="form-control" name="pickup_km" required min="0" placeholder="Ex: 45320" <?= $stats['last_km'] ? 'min="' . $stats['last_km'] . '"' : '' ?>>
                        <?php if ($stats['last_km']): ?>
                        <small class="text-muted">Último KM registrado: <?= number_format($stats['last_km'], 0, ',', '.') ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Destino *</label>
                        <input type="text" class="form-control" name="destination" required placeholder="Para onde vai">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="pickup_notes" rows="2" placeholder="Observações (opcional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Registrar Retirada</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Devolução -->
<?php if (!$isAvailable && $currentUsage): ?>
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="/admin/vehicle/return">
                <input type="hidden" name="id" value="<?= $currentUsage['id'] ?>">
                <div class="modal-header bg-warning bg-opacity-10">
                    <h5 class="modal-title"><i class="bi bi-arrow-return-left text-warning"></i> Registrar Devolução</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light small">
                        <strong>Motorista:</strong> <?= htmlspecialchars($currentUsage['driver_name']) ?><br>
                        <strong>Saída:</strong> <?= date('d/m/Y', strtotime($currentUsage['pickup_date'])) ?> às <?= substr($currentUsage['pickup_time'], 0, 5) ?><br>
                        <strong>KM Saída:</strong> <?= number_format($currentUsage['pickup_km'], 0, ',', '.') ?><br>
                        <strong>Destino:</strong> <?= htmlspecialchars($currentUsage['destination']) ?>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Data de Chegada *</label>
                            <input type="date" class="form-control" name="return_date" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Horário de Chegada *</label>
                            <input type="time" class="form-control" name="return_time" required value="<?= date('H:i') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quilometragem de Chegada *</label>
                        <input type="number" class="form-control" name="return_km" required min="<?= $currentUsage['pickup_km'] ?>" placeholder="Ex: 45380">
                        <small class="text-muted">KM na saída: <?= number_format($currentUsage['pickup_km'], 0, ',', '.') ?> (mínimo aceito)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="return_notes" rows="2" placeholder="Observações da devolução (opcional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-check-lg"></i> Registrar Devolução</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
