<?php $pageTitle = 'Nova Obra'; $currentPage = 'obras'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-buildings"></i> Nova Obra</h5>
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

<form method="POST" action="/admin/obras/store">
    <div class="row">
        <div class="col-lg-8">
            <!-- Dados principais -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-info-circle"></i> Dados da Obra</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nome da Obra *</label>
                        <input type="text" class="form-control" name="name" required placeholder="Ex: Residência Família Silva - Alphaville">
                    </div>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Endereço</label>
                            <input type="text" class="form-control" name="address" placeholder="Rua, número, bairro">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Cidade</label>
                            <input type="text" class="form-control" name="city" placeholder="São Paulo">
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
                                <option value="<?= $uf ?>"><?= $uf ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Cliente / Proprietário</label>
                            <input type="text" class="form-control" name="client_name" placeholder="Nome do cliente ou proprietário">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição / Observações</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Informações adicionais sobre a obra..."></textarea>
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
                            <input type="text" class="form-control" name="responsible_name" placeholder="Nome completo">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telefone do Responsável</label>
                            <input type="text" class="form-control" name="responsible_phone" placeholder="(11) 99999-9999">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aprovadores -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-person-check"></i> Aprovadores desta Obra</div>
                <div class="card-body">
                    <p class="text-muted small mb-2">Selecione quem recebe as notificações de aprovação dos pedidos desta obra. Se nenhum for selecionado, será usado o configurado nas <a href="/admin/orders/settings">configurações globais</a>.</p>
                    <?php
                    $availableApprovers = \App\Models\ConstructionSite::getAvailableApprovers();
                    ?>
                    <?php if (empty($availableApprovers)): ?>
                    <div class="alert alert-light small mb-0">Nenhum usuário com permissão de aprovação cadastrado. <a href="/admin/orders/pin-users">Gerenciar usuários</a></div>
                    <?php else: ?>
                    <div class="row">
                        <?php foreach ($availableApprovers as $ap): ?>
                        <div class="col-md-6 mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="approvers[]" value="<?= $ap['id'] ?>" id="approver-<?= $ap['id'] ?>">
                                <label class="form-check-label" for="approver-<?= $ap['id'] ?>">
                                    <strong><?= htmlspecialchars($ap['name']) ?></strong>
                                    <?php if (!empty($ap['phone'])): ?><br><small class="text-muted"><i class="bi bi-whatsapp"></i> <?= htmlspecialchars($ap['phone']) ?></small><?php endif; ?>
                                    <?php if (!empty($ap['email'])): ?><br><small class="text-muted"><i class="bi bi-envelope"></i> <?= htmlspecialchars($ap['email']) ?></small><?php endif; ?>
                                </label>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Datas e Status -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-calendar"></i> Datas e Status</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active">Ativa</option>
                            <option value="inactive">Inativa</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data de Início</label>
                        <input type="date" class="form-control" name="started_at">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Previsão de Término</label>
                        <input type="date" class="form-control" name="expected_end_at">
                    </div>
                </div>
            </div>

            <!-- Botão salvar -->
            <div class="card">
                <div class="card-body d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Cadastrar Obra
                    </button>
                    <a href="/admin/obras" class="btn btn-outline-secondary btn-sm">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</form>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
