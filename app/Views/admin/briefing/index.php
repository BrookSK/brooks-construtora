<?php $pageTitle = 'Briefing & Contratos'; $currentPage = 'briefing'; ob_start(); ?>

<?php
// -----------------------------------------------------------------
// Helpers de conveniência
// -----------------------------------------------------------------
$mode            = $mode            ?? 'list';
$project         = $project         ?? null;
$briefing        = $briefing        ?? null;
$contractObject  = $contractObject  ?? null;
$templates       = $templates       ?? [];
$defaultTemplate = $defaultTemplate ?? null;
$projects        = $projects        ?? [];

$isEdit   = $mode === 'edit';
$isCreate = $mode === 'create';
$isList   = $mode === 'list';

// Campo seguro
function bval(?string $v): string {
    return htmlspecialchars($v ?? '', ENT_QUOTES);
}

// Formata dígitos como CPF (11 dígitos) ou CNPJ (14 dígitos) para exibição
function fmtDoc(?string $v): string {
    $d = preg_replace('/\D/', '', $v ?? '');
    if (strlen($d) === 11) {
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $d);
    }
    if (strlen($d) === 14) {
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $d);
    }
    return htmlspecialchars($v ?? '', ENT_QUOTES);
}

// Formata dígitos como telefone para exibição
function fmtPhone(?string $v): string {
    $d = preg_replace('/\D/', '', $v ?? '');
    if (strlen($d) === 11) {
        return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $d);
    }
    if (strlen($d) === 10) {
        return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $d);
    }
    return htmlspecialchars($v ?? '', ENT_QUOTES);
}

// Formata dígitos como CEP para exibição
function fmtCep(?string $v): string {
    $d = preg_replace('/\D/', '', $v ?? '');
    if (strlen($d) === 8) {
        return preg_replace('/(\d{5})(\d{3})/', '$1-$2', $d);
    }
    return htmlspecialchars($v ?? '', ENT_QUOTES);
}
?>

<?php if (!empty($flash)): ?>
<div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ================================================================
     MODO LISTA
     ================================================================ -->
<?php if ($isList): ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="mb-0 fw-semibold">Projetos e Briefings</h5>
        <p class="text-muted small mb-0">Gerencie clientes, briefings e gere objetos de contrato com IA.</p>
    </div>
    <a href="/admin/briefing/create" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Novo Briefing
    </a>
</div>

<?php if (empty($projects)): ?>
<div class="card p-5 text-center text-muted">
    <i class="bi bi-file-earmark-text" style="font-size:3rem;opacity:.3;"></i>
    <p class="mt-3 mb-0">Nenhum briefing cadastrado ainda.</p>
    <a href="/admin/briefing/create" class="btn btn-primary mt-3">Criar primeiro briefing</a>
</div>
<?php else: ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Cliente</th>
                    <th>Tipo de Obra</th>
                    <th class="d-none d-md-table-cell">Cidade</th>
                    <th class="d-none d-md-table-cell">Valor</th>
                    <th class="d-none d-sm-table-cell">Objetos</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $p): ?>
                <tr>
                    <td>
                        <div class="fw-medium"><?= bval($p['client_name']) ?></div>
                        <div class="text-muted small"><?= bval($p['client_email']) ?></div>
                    </td>
                    <td><?= bval($p['project_type']) ?></td>
                    <td class="d-none d-md-table-cell"><?= bval($p['project_city']) ?></td>
                    <td class="d-none d-md-table-cell">
                        <?php if (!empty($p['contract_value'])): ?>
                            R$ <?= number_format((float)$p['contract_value'], 2, ',', '.') ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="d-none d-sm-table-cell">
                        <span class="badge bg-<?= (int)($p['objects_count'] ?? 0) > 0 ? 'success' : 'secondary' ?>">
                            <?= (int)($p['objects_count'] ?? 0) ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="/admin/briefing/edit/<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                onclick="confirmDelete(<?= $p['id'] ?>, '<?= bval($p['client_name']) ?>')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Form oculto para exclusão -->
<form id="delete-form" method="POST" action="/admin/briefing/delete" style="display:none;">
    <input type="hidden" name="id" id="delete-id">
</form>
<?php endif; ?>

<!-- ================================================================
     MODOS CREATE / EDIT  (interface de 3 abas)
     ================================================================ -->
<?php else: ?>

<div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
    <a href="/admin/briefing" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
    <h5 class="mb-0 fw-semibold">
        <?= $isCreate ? 'Novo Briefing' : 'Editar Briefing — ' . bval($project['client_name'] ?? '') ?>
    </h5>
</div>

<!-- Navegação das abas -->
<ul class="nav nav-tabs mb-4" id="briefingTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-cadastro-btn" data-bs-toggle="tab"
                data-bs-target="#tab-cadastro" type="button" role="tab">
            <i class="bi bi-person-lines-fill me-1"></i>
            <span class="d-none d-sm-inline">1. </span>Cadastro / Briefing
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $isCreate ? 'disabled' : '' ?>"
                id="tab-modelo-btn" data-bs-toggle="tab"
                data-bs-target="#tab-modelo" type="button" role="tab"
                <?= $isCreate ? 'title="Salve o briefing primeiro"' : '' ?>>
            <i class="bi bi-braces me-1"></i>
            <span class="d-none d-sm-inline">2. </span>Modelo do Objeto
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $isCreate ? 'disabled' : '' ?>"
                id="tab-gerado-btn" data-bs-toggle="tab"
                data-bs-target="#tab-gerado" type="button" role="tab"
                <?= $isCreate ? 'title="Gere o objeto primeiro"' : '' ?>>
            <i class="bi bi-file-check me-1"></i>
            <span class="d-none d-sm-inline">3. </span>Objeto Gerado
        </button>
    </li>
</ul>

<div class="tab-content" id="briefingTabsContent">

    <!-- ==============================================================
         ABA 1 — CADASTRO / BRIEFING
         ============================================================== -->
    <div class="tab-pane fade show active" id="tab-cadastro" role="tabpanel">

        <form method="POST"
              action="<?= $isCreate ? '/admin/briefing/store' : '/admin/briefing/update' ?>">

            <?php if ($isEdit): ?>
            <input type="hidden" name="project_id"  value="<?= (int)($project['id'] ?? 0) ?>">
            <input type="hidden" name="briefing_id" value="<?= (int)($briefing['id'] ?? 0) ?>">
            <?php endif; ?>

            <!-- Card 1: Dados do Cliente -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-person me-2"></i>Dados do Cliente</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nome / Razão Social <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="client_name"
                                   value="<?= bval($project['client_name'] ?? '') ?>"
                                   required maxlength="255" placeholder="Nome completo ou razão social">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">CPF / CNPJ</label>
                            <input type="text" class="form-control" name="client_document" id="client_document"
                                   value="<?= fmtDoc($project['client_document'] ?? '') ?>"
                                   maxlength="18" placeholder="000.000.000-00"
                                   inputmode="numeric" autocomplete="off">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Telefone</label>
                            <input type="text" class="form-control" name="client_phone" id="client_phone"
                                   value="<?= fmtPhone($project['client_phone'] ?? '') ?>"
                                   maxlength="15" placeholder="(11) 99999-9999"
                                   inputmode="numeric" autocomplete="tel">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-mail</label>
                            <input type="email" class="form-control" name="client_email" id="client_email"
                                   value="<?= bval($project['client_email'] ?? '') ?>"
                                   placeholder="cliente@email.com"
                                   autocomplete="email">
                            <div class="invalid-feedback">Informe um e-mail válido (ex: nome@dominio.com.br).</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Informações da Obra -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-buildings me-2"></i>Informações da Obra</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Tipo de Obra / Imóvel</label>
                            <select class="form-select" name="project_type">
                                <option value="">— Selecione —</option>
                                <?php foreach (['Residencial', 'Comercial', 'Industrial', 'Reforma', 'Ampliação', 'Retrofit', 'Paisagismo', 'Outro'] as $tipo): ?>
                                <option value="<?= $tipo ?>" <?= ($project['project_type'] ?? '') === $tipo ? 'selected' : '' ?>><?= $tipo ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Área (m²)</label>
                            <input type="number" class="form-control" name="project_area" step="0.01" min="0"
                                   value="<?= bval($project['project_area'] ?? '') ?>"
                                   placeholder="150.00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">CEP</label>
                            <input type="text" class="form-control" name="project_cep" id="project_cep"
                                   maxlength="9" placeholder="00000-000"
                                   value="<?= fmtCep($project['project_cep'] ?? '') ?>"
                                   inputmode="numeric" autocomplete="postal-code">
                            <div class="invalid-feedback" id="cep-feedback"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">
                                Cidade
                                <?= micBtn('city_mic', 'project_city') ?>
                            </label>
                            <input type="text" class="form-control" name="project_city" id="project_city"
                                   value="<?= bval($project['project_city'] ?? '') ?>"
                                   placeholder="São Paulo">
                        </div>
                        <div class="col-12">
                            <label class="form-label">
                                Endereço
                                <?= micBtn('address_mic', 'project_address') ?>
                            </label>
                            <input type="text" class="form-control" name="project_address" id="project_address"
                                   value="<?= bval($project['project_address'] ?? '') ?>"
                                   placeholder="Rua, número, bairro">
                        </div>
                        <div class="col-12">
                            <label class="form-label">
                                Objetivo / Finalidade da Obra
                                <?= micBtn('goal_mic', 'project_goal') ?>
                            </label>
                            <textarea class="form-control" name="project_goal" id="project_goal"
                                      rows="3" placeholder="Descreva o objetivo principal da obra..."><?= bval($project['project_goal'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Briefing da Negociação -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Briefing da Negociação</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">
                                Preferências do Cliente
                                <?= micBtn('pref_mic', 'preferences') ?>
                            </label>
                            <textarea class="form-control" name="preferences" id="preferences"
                                      rows="3" placeholder="Materiais, estilos, marcas preferidas..."><?= bval($briefing['preferences'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                Prioridades
                                <?= micBtn('prio_mic', 'priorities') ?>
                            </label>
                            <textarea class="form-control" name="priorities" id="priorities"
                                      rows="3" placeholder="O que é mais importante para o cliente..."><?= bval($briefing['priorities'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                Necessidades Específicas
                                <?= micBtn('needs_mic', 'needs') ?>
                            </label>
                            <textarea class="form-control" name="needs" id="needs"
                                      rows="3" placeholder="Requisitos técnicos, acessibilidade, etc."><?= bval($briefing['needs'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                Restrições
                                <?= micBtn('rest_mic', 'restrictions') ?>
                            </label>
                            <textarea class="form-control" name="restrictions" id="restrictions"
                                      rows="3" placeholder="Limitações de orçamento, prazo, estrutura..."><?= bval($briefing['restrictions'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                Resumo do Briefing
                                <?= micBtn('summary_mic', 'briefing_summary') ?>
                            </label>
                            <textarea class="form-control" name="briefing_summary" id="briefing_summary"
                                      rows="4" placeholder="Resumo geral da reunião de briefing..."><?= bval($briefing['briefing_summary'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                Detalhes da Negociação
                                <?= micBtn('neg_mic', 'negotiation_details') ?>
                            </label>
                            <textarea class="form-control" name="negotiation_details" id="negotiation_details"
                                      rows="4" placeholder="Pontos acordados, pendências, observações..."><?= bval($briefing['negotiation_details'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 4: Condições Comerciais -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-currency-dollar me-2"></i>Condições Comerciais</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Valor Total (R$)</label>
                            <input type="number" class="form-control" name="contract_value"
                                   step="0.01" min="0"
                                   value="<?= bval($briefing['contract_value'] ?? '') ?>"
                                   placeholder="0,00">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Parcelas</label>
                            <input type="number" class="form-control" name="payment_installments"
                                   min="1"
                                   value="<?= bval($briefing['payment_installments'] ?? '') ?>"
                                   placeholder="12">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Data de Início</label>
                            <input type="date" class="form-control" name="start_date"
                                   value="<?= bval($briefing['start_date'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Data de Conclusão</label>
                            <input type="date" class="form-control" name="end_date"
                                   value="<?= bval($briefing['end_date'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Prazo (dias corridos)</label>
                            <input type="number" class="form-control" name="deadline_days"
                                   min="1"
                                   value="<?= bval($briefing['deadline_days'] ?? '') ?>"
                                   placeholder="180">
                        </div>
                        <div class="col-md-9">
                            <label class="form-label">Detalhes do Parcelamento</label>
                            <input type="text" class="form-control" name="payment_details"
                                   value="<?= bval($briefing['payment_details'] ?? '') ?>"
                                   placeholder="Ex: 30% entrada + 70% em 6x mensais">
                        </div>
                        <div class="col-12">
                            <label class="form-label">
                                Cláusulas Especiais
                                <?= micBtn('clauses_mic', 'clauses') ?>
                            </label>
                            <textarea class="form-control" name="clauses" id="clauses"
                                      rows="4" placeholder="Cláusulas específicas a serem incluídas no contrato..."><?= bval($briefing['clauses'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end mb-5">
                <a href="/admin/briefing" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-lg me-1"></i>
                    <?= $isCreate ? 'Salvar Briefing' : 'Atualizar Briefing' ?>
                </button>
            </div>
        </form>

    </div><!-- /tab-cadastro -->


    <!-- ==============================================================
         ABA 2 — MODELO DO OBJETO
         ============================================================== -->
    <div class="tab-pane fade" id="tab-modelo" role="tabpanel">

        <?php
        $briefingId = (int)($briefing['id'] ?? 0);
        $selTemplate = $defaultTemplate;
        ?>

        <!-- Variáveis disponíveis -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-braces me-2"></i>Variáveis Disponíveis</h6>
                <small class="text-muted">Clique para copiar e colar no template</small>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ([
                        'cliente_nome', 'cliente_documento', 'cliente_telefone', 'cliente_email',
                        'tipo_obra', 'endereco', 'cidade', 'objetivo', 'area_m2',
                        'briefing', 'valor_contrato', 'parcelamento',
                        'data_inicio', 'data_conclusao', 'prazo_dias', 'clausulas'
                    ] as $var): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary font-monospace var-chip"
                            data-var="{{<?= $var ?>}}"
                            onclick="copyVar(this)"
                            title="Clique para copiar">
                        {{<?= $var ?>}}
                    </button>
                    <?php endforeach; ?>
                </div>
                <p class="text-muted small mt-2 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Os valores são preenchidos automaticamente com os dados do briefing ao gerar o objeto.
                </p>
            </div>
        </div>

        <!-- Seleção de modelo -->
        <?php if (!empty($templates)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-layout-text-window me-2"></i>Modelo de Prompt</h6>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label class="form-label">Selecionar modelo</label>
                        <select class="form-select" id="template-select" onchange="loadTemplate(this.value)">
                            <?php foreach ($templates as $tpl): ?>
                            <option value="<?= (int)$tpl['id'] ?>"
                                    data-prompt="<?= bval($tpl['prompt_template']) ?>"
                                    <?= ($tpl['is_default'] ?? 0) ? 'selected' : '' ?>>
                                <?= bval($tpl['name']) ?><?= $tpl['is_default'] ? ' (padrão)' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-outline-secondary w-100" data-bs-toggle="modal" data-bs-target="#modalTemplate">
                            <i class="bi bi-plus-lg me-1"></i> Novo modelo
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Template editável -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Template do Objeto
                    <?= micBtn('template_mic', 'prompt-template-field') ?>
                </h6>
                <button type="button" class="btn btn-sm btn-outline-secondary"
                        onclick="resetTemplate()" title="Recarregar modelo selecionado">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            </div>
            <div class="card-body">
                <textarea id="prompt-template-field" class="form-control font-monospace"
                          rows="14" placeholder="Cole ou edite o template aqui. Use {{variável}} para inserir dados do briefing..."
                          style="font-size:0.82rem;line-height:1.6;"><?= bval($selTemplate['prompt_template'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Dados preenchidos (prévia das variáveis) -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-eye me-2"></i>Prévia dos Dados do Briefing</h6>
            </div>
            <div class="card-body">
                <div class="row g-2 small">
                    <?php $previewVars = [
                        'cliente_nome'      => $project['client_name']       ?? '',
                        'cliente_documento' => $project['client_document']   ?? '',
                        'cliente_telefone'  => $project['client_phone']      ?? '',
                        'cliente_email'     => $project['client_email']      ?? '',
                        'tipo_obra'         => $project['project_type']      ?? '',
                        'endereco'          => $project['project_address']   ?? '',
                        'cidade'            => $project['project_city']      ?? '',
                        'objetivo'          => $project['project_goal']      ?? '',
                        'area_m2'           => $project['project_area']      ?? '',
                        'valor_contrato'    => !empty($briefing['contract_value'])
                            ? 'R$ ' . number_format((float)$briefing['contract_value'], 2, ',', '.')
                            : '',
                        'parcelamento'      => ($briefing['payment_installments'] ?? '')
                            ? ($briefing['payment_installments'] . 'x — ' . ($briefing['payment_details'] ?? ''))
                            : ($briefing['payment_details'] ?? ''),
                        'data_inicio'       => $briefing['start_date']    ?? '',
                        'data_conclusao'    => $briefing['end_date']       ?? '',
                        'prazo_dias'        => $briefing['deadline_days']  ?? '',
                    ]; ?>
                    <?php foreach ($previewVars as $k => $v): ?>
                    <div class="col-sm-6 col-md-4">
                        <span class="text-muted font-monospace">{{<?= $k ?>}}</span><br>
                        <span class="fw-medium"><?= $v !== '' ? bval((string)$v) : '<span class="text-danger">não preenchido</span>' ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Botão gerar -->
        <div class="d-grid mb-5">
            <button type="button" id="btn-generate" class="btn btn-primary btn-lg"
                    onclick="generateObject(<?= $briefingId ?>)">
                <i class="bi bi-stars me-2"></i> Gerar Objeto do Contrato com IA
            </button>
            <div id="generate-status" class="text-center text-muted small mt-2" style="display:none;"></div>
        </div>

    </div><!-- /tab-modelo -->


    <!-- ==============================================================
         ABA 3 — OBJETO GERADO
         ============================================================== -->
    <div class="tab-pane fade" id="tab-gerado" role="tabpanel">

        <!-- Área de resultado (preenchida via JS após geração ou carregada do banco) -->
        <div id="object-result-wrapper">
            <?php if ($contractObject): ?>
            <?php include __DIR__ . '/_object_result.php'; ?>
            <?php else: ?>
            <div id="object-empty-state" class="card p-5 text-center text-muted">
                <i class="bi bi-file-earmark-plus" style="font-size:3rem;opacity:.3;"></i>
                <p class="mt-3 mb-0">Nenhum objeto gerado ainda.</p>
                <p class="small">Vá até a aba <strong>Modelo do Objeto</strong> e clique em <strong>Gerar</strong>.</p>
                <button type="button" class="btn btn-outline-primary mt-2"
                        onclick="showTab('tab-modelo-btn')">
                    <i class="bi bi-braces me-1"></i> Ir para Modelo
                </button>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /tab-gerado -->

</div><!-- /tab-content -->

<?php endif; // fim create/edit ?>

<!-- ==============================================================
     MODAL — Novo Modelo de Template
     ============================================================== -->
<div class="modal fade" id="modalTemplate" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-layout-text-window me-2"></i>Novo Modelo de Prompt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nome do modelo <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="tpl-name" maxlength="255" placeholder="Ex: Objeto Padrão Residencial">
                </div>
                <div class="mb-3">
                    <label class="form-label">Descrição</label>
                    <input type="text" class="form-control" id="tpl-desc" placeholder="Breve descrição do modelo">
                </div>
                <div class="mb-3">
                    <label class="form-label">Template <span class="text-danger">*</span></label>
                    <textarea class="form-control font-monospace" id="tpl-prompt" rows="10"
                              style="font-size:0.82rem;line-height:1.6;"
                              placeholder="Use {{variável}} para inserir dados do briefing..."></textarea>
                </div>
                <div id="tpl-feedback" class="small mt-1"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveTemplate()">
                    <i class="bi bi-check-lg me-1"></i> Salvar Modelo
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ==============================================================
     ESTILOS COMPLEMENTARES
     ============================================================== -->
<style>
.mic-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px; height: 26px;
    padding: 0;
    border-radius: 50%;
    border: 1px solid #dee2e6;
    background: #fff;
    color: #6c757d;
    cursor: pointer;
    vertical-align: middle;
    margin-left: 4px;
    transition: all .15s;
    font-size: .75rem;
}
.mic-btn:hover { background: var(--color-primary); color: #fff; border-color: var(--color-primary); }
.mic-btn.recording { background: var(--color-accent); color: #fff; border-color: var(--color-accent); animation: pulse-mic .8s infinite; }
@keyframes pulse-mic { 0%,100%{transform:scale(1);} 50%{transform:scale(1.15);} }

.var-chip { font-size: .78rem; }
.var-chip.copied { background: var(--color-primary) !important; color: #fff !important; border-color: var(--color-primary) !important; }

#object-text-display {
    white-space: pre-wrap;
    line-height: 1.75;
    font-size: .95rem;
    background: #fafafa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 1.25rem 1.5rem;
}
</style>

<!-- ==============================================================
     JAVASCRIPT
     ============================================================== -->
<script>
// -----------------------------------------------------------------
// Helper: troca de aba
// -----------------------------------------------------------------
function showTab(btnId) {
    var btn = document.getElementById(btnId);
    if (btn && !btn.classList.contains('disabled')) {
        btn.click();
    }
}

// -----------------------------------------------------------------
// Exclusão com confirmação
// -----------------------------------------------------------------
function confirmDelete(id, name) {
    if (!confirm('Excluir o briefing de "' + name + '"?\n\nTodos os dados vinculados (briefing e objetos gerados) serão removidos permanentemente.')) return;
    document.getElementById('delete-id').value = id;
    document.getElementById('delete-form').submit();
}

// -----------------------------------------------------------------
// Variáveis — copiar ao clicar
// -----------------------------------------------------------------
function copyVar(btn) {
    var text = btn.dataset.var;
    navigator.clipboard.writeText(text).then(function() {
        btn.classList.add('copied');
        setTimeout(function() { btn.classList.remove('copied'); }, 1200);
    }).catch(function() {
        // Fallback
        var ta = document.createElement('textarea');
        ta.value = text;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        btn.classList.add('copied');
        setTimeout(function() { btn.classList.remove('copied'); }, 1200);
    });
}

// -----------------------------------------------------------------
// Carregar template no textarea ao mudar o select
// -----------------------------------------------------------------
function loadTemplate(id) {
    var sel = document.getElementById('template-select');
    if (!sel) return;
    var opt = sel.querySelector('option[value="' + id + '"]');
    if (opt) {
        document.getElementById('prompt-template-field').value = opt.dataset.prompt || '';
    }
}

function resetTemplate() {
    var sel = document.getElementById('template-select');
    if (sel) loadTemplate(sel.value);
}

// Inicializa o textarea com o template selecionado
(function() {
    var sel = document.getElementById('template-select');
    if (sel) loadTemplate(sel.value);
})();

// -----------------------------------------------------------------
// Salvar novo template via API
// -----------------------------------------------------------------
function saveTemplate() {
    var name   = document.getElementById('tpl-name').value.trim();
    var desc   = document.getElementById('tpl-desc').value.trim();
    var prompt = document.getElementById('tpl-prompt').value.trim();
    var fb     = document.getElementById('tpl-feedback');

    if (!name || !prompt) {
        fb.textContent = 'Nome e template são obrigatórios.';
        fb.className = 'small text-danger mt-1';
        return;
    }

    var fd = new FormData();
    fd.append('template_name', name);
    fd.append('template_description', desc);
    fd.append('prompt_template', prompt);

    fetch('/admin/briefing/store-template', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            fb.textContent = 'Modelo salvo!';
            fb.className = 'small text-success mt-1';
            // Adiciona ao select sem recarregar
            var sel = document.getElementById('template-select');
            if (sel) {
                var opt = document.createElement('option');
                opt.value = data.id;
                opt.textContent = data.name;
                opt.dataset.prompt = prompt;
                sel.appendChild(opt);
                sel.value = data.id;
            }
            setTimeout(function() {
                bootstrap.Modal.getInstance(document.getElementById('modalTemplate')).hide();
                fb.textContent = '';
            }, 800);
        } else {
            fb.textContent = data.error || 'Erro ao salvar.';
            fb.className = 'small text-danger mt-1';
        }
    })
    .catch(function() {
        fb.textContent = 'Erro na requisição.';
        fb.className = 'small text-danger mt-1';
    });
}

// -----------------------------------------------------------------
// Geração do Objeto do Contrato
// -----------------------------------------------------------------
function generateObject(briefingId) {
    var btn    = document.getElementById('btn-generate');
    var status = document.getElementById('generate-status');
    var sel    = document.getElementById('template-select');
    var tplId  = sel ? sel.value : 0;
    var customPrompt = document.getElementById('prompt-template-field').value.trim();

    if (!briefingId) {
        alert('Salve o briefing antes de gerar o objeto.');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Gerando com IA...';
    status.textContent = 'Enviando dados para a IA. Isso pode levar alguns instantes...';
    status.style.display = 'block';

    var fd = new FormData();
    fd.append('briefing_id', briefingId);
    fd.append('template_id', tplId);
    fd.append('custom_prompt', customPrompt);

    fetch('/admin/briefing/generate-object', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-stars me-2"></i> Gerar Objeto do Contrato com IA';
        status.style.display = 'none';

        if (data.success) {
            renderObjectResult(data.text, data.object_id);
            // Ativa aba 3
            showTab('tab-gerado-btn');
        } else {
            alert('Erro ao gerar: ' + (data.error || 'Erro desconhecido.'));
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-stars me-2"></i> Gerar Objeto do Contrato com IA';
        status.style.display = 'none';
        alert('Erro na requisição. Verifique a chave OpenAI nas Configurações.');
    });
}

// Renderiza o objeto gerado no DOM da aba 3
function renderObjectResult(text, objectId) {
    var wrapper = document.getElementById('object-result-wrapper');
    var statusBadge = '<span id="obj-status-badge" class="badge bg-warning text-dark ms-2">Gerado</span>';

    wrapper.innerHTML = '<div class="card mb-4">'
        + '<div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">'
        + '<h6 class="mb-0"><i class="bi bi-file-earmark-check me-2"></i>Objeto do Contrato' + statusBadge + '</h6>'
        + '<div class="d-flex gap-2">'
        + '<button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyObjectText()" title="Copiar texto">'
        + '<i class="bi bi-clipboard"></i></button>'
        + '<button type="button" class="btn btn-sm btn-outline-primary" onclick="showTab(\'tab-modelo-btn\')">'
        + '<i class="bi bi-arrow-repeat me-1"></i> Re-gerar</button>'
        + '<button type="button" id="btn-approve" class="btn btn-sm btn-success" onclick="approveObject(' + objectId + ')">'
        + '<i class="bi bi-check2-circle me-1"></i> Aprovar</button>'
        + '</div></div>'
        + '<div class="card-body"><div id="object-text-display">' + escapeHtml(text) + '</div></div>'
        + '</div>';

    // Armazena o texto para cópia
    wrapper.dataset.objectText = text;
    wrapper.dataset.objectId   = objectId;
}

function escapeHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function copyObjectText() {
    var text = document.getElementById('object-result-wrapper').dataset.objectText || '';
    navigator.clipboard.writeText(text).then(function() {
        alert('Texto copiado para a área de transferência!');
    });
}

function approveObject(objectId) {
    if (!confirm('Aprovar este objeto do contrato?')) return;
    var fd = new FormData();
    fd.append('object_id', objectId);

    fetch('/admin/briefing/approve-object', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            var badge = document.getElementById('obj-status-badge');
            if (badge) {
                badge.textContent = 'Aprovado';
                badge.className = 'badge bg-success ms-2';
            }
            var btn = document.getElementById('btn-approve');
            if (btn) { btn.disabled = true; btn.textContent = '✓ Aprovado'; }
        } else {
            alert(data.error || 'Erro ao aprovar.');
        }
    });
}

// -----------------------------------------------------------------
// Máscaras de entrada
// -----------------------------------------------------------------
(function () {
    // --- CPF / CNPJ dinâmico ---
    var doc = document.getElementById('client_document');
    if (doc) {
        doc.addEventListener('input', function () {
            var v = doc.value.replace(/\D/g, '').substring(0, 14);
            if (v.length <= 11) {
                // CPF: 000.000.000-00
                v = v.replace(/(\d{3})(\d)/, '$1.$2');
                v = v.replace(/(\d{3})(\d)/, '$1.$2');
                v = v.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                doc.maxLength = 14;
            } else {
                // CNPJ: 00.000.000/0000-00
                v = v.replace(/(\d{2})(\d)/, '$1.$2');
                v = v.replace(/(\d{3})(\d)/, '$1.$2');
                v = v.replace(/(\d{3})(\d)/, '$1/$2');
                v = v.replace(/(\d{4})(\d{1,2})$/, '$1-$2');
                doc.maxLength = 18;
            }
            doc.value = v;
        });
    }

    // --- Telefone: (00) 00000-0000 ---
    var tel = document.getElementById('client_phone');
    if (tel) {
        tel.addEventListener('input', function () {
            var v = tel.value.replace(/\D/g, '').substring(0, 11);
            if (v.length <= 10) {
                v = v.replace(/(\d{2})(\d)/, '($1) $2');
                v = v.replace(/(\d{4})(\d)/, '$1-$2');
            } else {
                v = v.replace(/(\d{2})(\d)/, '($1) $2');
                v = v.replace(/(\d{5})(\d)/, '$1-$2');
            }
            tel.value = v;
        });
    }

    // --- CEP: 00000-000 ---
    var cepEl = document.getElementById('project_cep');
    if (cepEl) {
        cepEl.addEventListener('input', function () {
            var v = cepEl.value.replace(/\D/g, '').substring(0, 8);
            if (v.length > 5) {
                v = v.replace(/(\d{5})(\d)/, '$1-$2');
            }
            cepEl.value = v;
        });
    }
})();

// -----------------------------------------------------------------
// Validação de e-mail front-end (em tempo real)
// -----------------------------------------------------------------
(function () {
    var emailEl = document.getElementById('client_email');
    if (!emailEl) return;

    function validateEmail() {
        var val = emailEl.value.trim();
        // Regex com verificação de TLD mínimo de 2 chars
        var ok = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(val);
        if (val === '') {
            emailEl.classList.remove('is-invalid', 'is-valid');
        } else if (ok) {
            emailEl.classList.remove('is-invalid');
            emailEl.classList.add('is-valid');
        } else {
            emailEl.classList.remove('is-valid');
            emailEl.classList.add('is-invalid');
        }
        return ok || val === '';
    }

    emailEl.addEventListener('blur',  validateEmail);
    emailEl.addEventListener('input', validateEmail);

    // Bloqueia o submit se o e-mail estiver inválido
    var form = emailEl.closest('form');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (!validateEmail() && emailEl.value.trim() !== '') {
                e.preventDefault();
                emailEl.classList.add('is-invalid');
                emailEl.focus();
            }
        });
    }
})();

// -----------------------------------------------------------------
// Consulta automática de CEP (ViaCEP)
// -----------------------------------------------------------------
(function () {
    var cepEl    = document.getElementById('project_cep');
    var addrEl   = document.getElementById('project_address');
    var cityEl   = document.getElementById('project_city');
    var feedback = document.getElementById('cep-feedback');

    if (!cepEl) return;

    cepEl.addEventListener('input', function () {
        var digits = cepEl.value.replace(/\D/g, '');
        if (digits.length === 8) {
            fetchCep(digits);
        } else {
            setCepFeedback('', false);
        }
    });

    function fetchCep(cep) {
        setCepFeedback('Consultando...', false);
        cepEl.classList.remove('is-invalid', 'is-valid');

        fetch('https://viacep.com.br/ws/' + cep + '/json/')
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.erro) {
                cepEl.classList.add('is-invalid');
                setCepFeedback('CEP não encontrado. Preencha o endereço manualmente.', true);
                return;
            }
            cepEl.classList.remove('is-invalid');
            cepEl.classList.add('is-valid');
            setCepFeedback('', false);

            // Preenche endereço (logradouro + bairro) se estiver vazio ou vier do ViaCEP
            if (addrEl) {
                var logr = [data.logradouro, data.bairro].filter(Boolean).join(', ');
                if (logr) addrEl.value = logr;
            }
            if (cityEl && data.localidade) {
                cityEl.value = data.localidade;
            }
        })
        .catch(function () {
            setCepFeedback('Erro ao consultar CEP. Verifique sua conexão.', true);
        });
    }

    function setCepFeedback(msg, isError) {
        if (!feedback) return;
        feedback.textContent = msg;
        if (isError) {
            cepEl.classList.add('is-invalid');
            feedback.style.display = msg ? 'block' : 'none';
        } else {
            cepEl.classList.remove('is-invalid');
            feedback.style.display = msg ? 'block' : 'none';
            feedback.style.color = '#6c757d';
        }
    }
})();

// -----------------------------------------------------------------
// Microfone — gravação com MediaRecorder + transcrição Whisper
// -----------------------------------------------------------------
var _micRecorder  = null;
var _micChunks    = [];
var _micTargetId  = null;
var _micBtnEl     = null;

function micBtn(btnId, targetId) {
    return '<button type="button" class="mic-btn" id="' + btnId + '" '
        + 'onclick="toggleMic(\'' + btnId + '\', \'' + targetId + '\')" '
        + 'title="Gravar por voz"><i class="bi bi-mic"></i></button>';
}

function toggleMic(btnId, targetId) {
    if (_micRecorder && _micRecorder.state === 'recording') {
        stopMic();
        return;
    }
    startMic(btnId, targetId);
}

function startMic(btnId, targetId) {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Gravação de áudio não suportada neste navegador.');
        return;
    }

    navigator.mediaDevices.getUserMedia({ audio: true })
    .then(function(stream) {
        _micTargetId = targetId;
        _micBtnEl    = document.getElementById(btnId);
        _micChunks   = [];

        _micRecorder = new MediaRecorder(stream, { mimeType: getSupportedMimeType() });
        _micRecorder.ondataavailable = function(e) { if (e.data.size > 0) _micChunks.push(e.data); };
        _micRecorder.onstop = function() {
            stream.getTracks().forEach(function(t) { t.stop(); });
            sendAudioForTranscription();
        };

        _micRecorder.start();
        if (_micBtnEl) {
            _micBtnEl.classList.add('recording');
            _micBtnEl.innerHTML = '<i class="bi bi-stop-fill"></i>';
            _micBtnEl.title = 'Parar gravação';
        }
    })
    .catch(function(err) {
        alert('Não foi possível acessar o microfone: ' + err.message);
    });
}

function stopMic() {
    if (_micRecorder && _micRecorder.state === 'recording') {
        _micRecorder.stop();
        if (_micBtnEl) {
            _micBtnEl.classList.remove('recording');
            _micBtnEl.innerHTML = '<span class="spinner-border spinner-border-sm" style="width:.7rem;height:.7rem;"></span>';
            _micBtnEl.title = 'Transcrevendo...';
        }
    }
}

function getSupportedMimeType() {
    var types = ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/mp4'];
    for (var i = 0; i < types.length; i++) {
        if (MediaRecorder.isTypeSupported(types[i])) return types[i];
    }
    return '';
}

function sendAudioForTranscription() {
    var mimeType = getSupportedMimeType() || 'audio/webm';
    var blob = new Blob(_micChunks, { type: mimeType });
    var ext  = mimeType.includes('ogg') ? 'ogg' : mimeType.includes('mp4') ? 'mp4' : 'webm';

    var fd = new FormData();
    fd.append('audio', blob, 'recording.' + ext);

    fetch('/admin/briefing/transcribe-audio', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        resetMicBtn();
        if (data.success && data.text) {
            var el = document.getElementById(_micTargetId);
            if (el) {
                var sep = el.value && el.value.trim() ? ' ' : '';
                el.value = el.value + sep + data.text;
                el.dispatchEvent(new Event('input'));
            }
        } else {
            alert('Erro na transcrição: ' + (data.error || 'Resposta vazia.'));
        }
    })
    .catch(function() {
        resetMicBtn();
        alert('Erro ao enviar áudio para transcrição.');
    });
}

function resetMicBtn() {
    if (_micBtnEl) {
        _micBtnEl.classList.remove('recording');
        _micBtnEl.innerHTML = '<i class="bi bi-mic"></i>';
        _micBtnEl.title = 'Gravar por voz';
    }
    _micRecorder = null;
    _micBtnEl    = null;
    _micTargetId = null;
}
</script>

<?php
// Helper PHP inline — evita include extra para o caso de objeto existir no carregamento
function micBtn(string $btnId, string $targetId): string {
    return '<button type="button" class="mic-btn" id="' . $btnId . '" '
        . 'onclick="toggleMic(\'' . $btnId . '\', \'' . $targetId . '\')" '
        . 'title="Gravar por voz"><i class="bi bi-mic"></i></button>';
}
?>

<?php $content = ob_get_clean(); include ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
