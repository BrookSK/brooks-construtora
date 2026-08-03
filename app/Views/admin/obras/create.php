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

            <!-- Responsáveis por Fase -->
            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-person-check"></i> Responsáveis por Notificação</div>
                <div class="card-body">
                    <p class="text-muted small mb-3">Selecione quem recebe as notificações de cada fase dos pedidos desta obra, além dos configurados nas <a href="/admin/orders/settings">configurações globais</a>.</p>
                    <?php
                    $availableNotifiers = \App\Models\ConstructionSite::getAvailableNotifiers();
                    $phases = [
                        'quote' => ['label' => 'Cotação', 'icon' => 'bi-1-circle text-warning', 'desc' => 'Quem recebe para informar preços'],
                        'approval' => ['label' => 'Aprovação', 'icon' => 'bi-2-circle text-info', 'desc' => 'Quem recebe para aprovar/rejeitar'],
                        'completed' => ['label' => 'Conclusão', 'icon' => 'bi-3-circle text-success', 'desc' => 'Quem recebe quando aprovado'],
                        'payment' => ['label' => 'Pagamento', 'icon' => 'bi-4-circle text-primary', 'desc' => 'Quem recebe para NF/Boleto'],
                        'delivery' => ['label' => 'Entrega', 'icon' => 'bi-5-circle text-dark', 'desc' => 'Quem recebe o checklist'],
                        'spare' => ['label' => 'Sobressalentes', 'icon' => 'bi-6-circle text-warning', 'desc' => 'Quem recebe itens avulsos'],
                        'transport' => ['label' => 'Transporte', 'icon' => 'bi-truck text-primary', 'desc' => 'Quem recebe transferências'],
                    ];
                    ?>
                    <?php if (empty($availableNotifiers)): ?>
                    <div class="alert alert-light small mb-0">Nenhum usuário cadastrado. <a href="/admin/orders/pin-users">Gerenciar usuários</a></div>
                    <?php else: ?>
                    <div class="accordion" id="phaseAccordion">
                        <?php foreach ($phases as $phaseKey => $phaseInfo): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#phase-<?= $phaseKey ?>" style="font-size:0.85rem;">
                                    <i class="bi <?= $phaseInfo['icon'] ?> me-2"></i> <strong><?= $phaseInfo['label'] ?></strong>
                                    <small class="text-muted ms-2">— <?= $phaseInfo['desc'] ?></small>
                                </button>
                            </h2>
                            <div id="phase-<?= $phaseKey ?>" class="accordion-collapse collapse">
                                <div class="accordion-body py-2">
                                    <div class="row">
                                        <?php foreach ($availableNotifiers as $ap): ?>
                                        <div class="col-md-6 mb-1">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="notifiers[<?= $phaseKey ?>][]" value="<?= $ap['id'] ?>" id="nf-<?= $phaseKey ?>-<?= $ap['id'] ?>">
                                                <label class="form-check-label small" for="nf-<?= $phaseKey ?>-<?= $ap['id'] ?>">
                                                    <strong><?= htmlspecialchars($ap['name']) ?></strong>
                                                    <?php if (!empty($ap['phone'])): ?><span class="text-muted ms-1"><i class="bi bi-whatsapp"></i> <?= htmlspecialchars($ap['phone']) ?></span><?php endif; ?>
                                                </label>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
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
