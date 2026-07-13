<?php $pageTitle = 'Editar Obra - ' . $site['name']; $currentPage = 'obras'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h5 class="mb-0"><i class="bi bi-buildings"></i> <?= htmlspecialchars($site['name']) ?> <small class="text-muted">(<?= $site['code'] ?>)</small></h5>
    <a href="/admin/obras" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
</div>

<?php if (!empty($flash)): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
    <?= $flash['message'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Resumo -->
<div class="row mb-3">
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="stat-number"><?= $site['orders_count'] ?? 0 ?></div>
                <small class="text-muted">Pedidos Vinculados</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body text-center">
                <div class="stat-number" style="font-size:1.4rem;">R$ <?= number_format($site['total_approved'] ?? 0, 2, ',', '.') ?></div>
                <small class="text-muted">Total Aprovado</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card">
            <div class="card-body text-center">
                <?php
                $statusLabels = ['active' => ['Ativa', 'success'], 'inactive' => ['Inativa', 'secondary'], 'completed' => ['Concluída', 'info']];
                $st = $statusLabels[$site['status']] ?? ['?', 'dark'];
                ?>
                <div class="stat-number" style="font-size:1.4rem;"><span class="badge bg-<?= $st[1] ?> fs-6"><?= $st[0] ?></span></div>
                <small class="text-muted">Status Atual</small>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="/admin/obras/update">
    <input type="hidden" name="id" value="<?= $site['id'] ?>">
    <div class="row">
        <div class="col-lg-8">
            <!-- Dados principais -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-info-circle"></i> Dados da Obra</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nome da Obra *</label>
                        <input type="text" class="form-control" name="name" required value="<?= htmlspecialchars($site['name']) ?>">
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Endereço</label>
                            <input type="text" class="form-control" name="address" value="<?= htmlspecialchars($site['address'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Cidade</label>
                            <input type="text" class="form-control" name="city" value="<?= htmlspecialchars($site['city'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">UF</label>
                            <select class="form-select" name="state">
                                <option value="">--</option>
                                <?php
                                $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                                foreach ($ufs as $uf): ?>
                                <option value="<?= $uf ?>" <?= ($site['state'] ?? '') === $uf ? 'selected' : '' ?>><?= $uf ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Cliente / Proprietário</label>
                            <input type="text" class="form-control" name="client_name" value="<?= htmlspecialchars($site['client_name'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição / Observações</label>
                        <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($site['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Responsável -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-person"></i> Responsável pela Obra</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nome do Responsável</label>
                            <input type="text" class="form-control" name="responsible_name" value="<?= htmlspecialchars($site['responsible_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telefone do Responsável</label>
                            <input type="text" class="form-control" name="responsible_phone" value="<?= htmlspecialchars($site['responsible_phone'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pedidos vinculados -->
            <?php if (!empty($orders)): ?>
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-cart3"></i> Pedidos Vinculados (<?= count($orders) ?>)</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Fornecedor</th>
                                <th>Status</th>
                                <th class="text-end">Valor</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $orderStatusLabels = [
                                'pending_quote' => ['Ag. Cotação', 'warning'],
                                'quoted' => ['Cotado', 'info'],
                                'pending_approval' => ['Ag. Aprovação', 'info'],
                                'approved' => ['Aprovado', 'success'],
                                'rejected' => ['Rejeitado', 'danger'],
                                'cancelled' => ['Cancelado', 'dark'],
                            ];
                            foreach ($orders as $order):
                                $oStatus = $orderStatusLabels[$order['status']] ?? ['?', 'secondary'];
                            ?>
                            <tr>
                                <td><a href="/admin/orders/show/<?= $order['id'] ?>" class="text-decoration-none"><?= $order['code'] ?></a></td>
                                <td><small><?= htmlspecialchars($order['supplier_name'] ?? 'Pendente') ?></small></td>
                                <td><span class="badge bg-<?= $oStatus[1] ?>"><?= $oStatus[0] ?></span></td>
                                <td class="text-end">
                                    <?= $order['display_total'] > 0 ? 'R$ ' . number_format($order['display_total'], 2, ',', '.') : '-' ?>
                                </td>
                                <td><small><?= date('d/m/Y', strtotime($order['created_at'])) ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <!-- Datas e Status -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-calendar"></i> Datas e Status</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Código</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($site['code'] ?? '') ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active" <?= $site['status'] === 'active' ? 'selected' : '' ?>>Ativa</option>
                            <option value="inactive" <?= $site['status'] === 'inactive' ? 'selected' : '' ?>>Inativa</option>
                            <option value="completed" <?= $site['status'] === 'completed' ? 'selected' : '' ?>>Concluída</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data de Início</label>
                        <input type="date" class="form-control" name="started_at" value="<?= $site['started_at'] ?? '' ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Previsão de Término</label>
                        <input type="date" class="form-control" name="expected_end_at" value="<?= $site['expected_end_at'] ?? '' ?>">
                    </div>
                    <?php if (!empty($site['completed_at'])): ?>
                    <div class="mb-3">
                        <label class="form-label">Concluída em</label>
                        <input type="text" class="form-control" value="<?= date('d/m/Y', strtotime($site['completed_at'])) ?>" readonly>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Cadastrada em</label>
                        <input type="text" class="form-control" value="<?= date('d/m/Y H:i', strtotime($site['created_at'])) ?>" readonly>
                    </div>
                </div>
            </div>

            <!-- Ações -->
            <div class="card">
                <div class="card-body d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Salvar Alterações
                    </button>
                    <?php if (($site['orders_count'] ?? 0) == 0): ?>
                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal">
                        <i class="bi bi-trash"></i> Excluir Obra
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Modal Excluir -->
<?php if (($site['orders_count'] ?? 0) == 0): ?>
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir a obra <strong>"<?= htmlspecialchars($site['name']) ?>"</strong>?</p>
                <p class="text-danger small">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" action="/admin/obras/delete" class="d-inline">
                    <input type="hidden" name="id" value="<?= $site['id'] ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
