<?php $pageTitle = 'Briefing & Contratos'; $currentPage = 'briefing'; ob_start(); ?>
<?php
// -----------------------------------------------------------------
// Helpers PHP
// -----------------------------------------------------------------
$mode            = $mode            ?? 'list';
$project         = $project         ?? null;
$briefing        = $briefing        ?? null;
$contractObject  = $contractObject  ?? null;
$templates       = $templates       ?? [];
$defaultTemplate = $defaultTemplate ?? null;
$projects        = $projects        ?? [];
$isEdit          = $mode === 'edit';
$isCreate        = $mode === 'create';
$isList          = $mode === 'list';

function bval(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES); }

function fmtDoc(?string $v): string {
    $d = preg_replace('/\D/', '', $v ?? '');
    if (strlen($d) === 11) return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $d);
    if (strlen($d) === 14) return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $d);
    return htmlspecialchars($v ?? '', ENT_QUOTES);
}

function fmtPhone(?string $v): string {
    $d = preg_replace('/\D/', '', $v ?? '');
    if (strlen($d) === 11) return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $d);
    if (strlen($d) === 10) return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $d);
    return htmlspecialchars($v ?? '', ENT_QUOTES);
}

function fmtCep(?string $v): string {
    $d = preg_replace('/\D/', '', $v ?? '');
    if (strlen($d) === 8) return preg_replace('/(\d{5})(\d{3})/', '$1-$2', $d);
    return htmlspecialchars($v ?? '', ENT_QUOTES);
}

function micBtn(string $btnId, string $targetId): string {
    return '<button type="button" class="mic-btn" id="' . $btnId . '" '
        . 'onclick="toggleSpeech(\'' . $btnId . '\',\'' . $targetId . '\')" '
        . 'title="Ditado por voz (clique para iniciar/parar)"><i class="bi bi-mic"></i></button>';
}

$projectId  = (int)($project['id']  ?? 0);
$briefingId = (int)($briefing['id'] ?? 0);
$templateId = (int)($defaultTemplate['id'] ?? ($templates[0]['id'] ?? 0));
?>

<?php if (!empty($flash)): ?>
<div class="alert alert-<?= in_array($flash['type'], ['success','warning','info','error']) ? ($flash['type'] === 'error' ? 'danger' : htmlspecialchars($flash['type'])) : 'info' ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($flash['message'] ?? '') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ================================================================
     MODO LISTA
================================================================ -->
<?php if ($isList): ?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h5 class="mb-0 fw-semibold">Clientes, Briefings & Contratos</h5>
        <p class="text-muted small mb-0">Gerencie clientes, acompanhe briefings e status dos contratos.</p>
    </div>
    <a href="/admin/briefing/create" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Novo Briefing
    </a>
</div>

<!-- Filtros de status -->
<?php $sf = $statusFilter ?? ''; ?>
<div class="d-flex gap-2 mb-3 flex-wrap">
    <a href="/admin/briefing" class="btn btn-sm <?= $sf === '' ? 'btn-dark' : 'btn-outline-secondary' ?>">
        <i class="bi bi-grid me-1"></i> Todos
    </a>
    <a href="/admin/briefing?status=approved" class="btn btn-sm <?= $sf === 'approved' ? 'btn-success' : 'btn-outline-success' ?>">
        <i class="bi bi-check-circle me-1"></i> Aprovados
    </a>
    <a href="/admin/briefing?status=pending" class="btn btn-sm <?= $sf === 'pending' ? 'btn-warning' : 'btn-outline-warning' ?>">
        <i class="bi bi-clock me-1"></i> Pendentes
    </a>
    <a href="/admin/briefing?status=no_object" class="btn btn-sm <?= $sf === 'no_object' ? 'btn-secondary' : 'btn-outline-secondary' ?>">
        <i class="bi bi-file-earmark me-1"></i> Sem Contrato
    </a>
</div>

<?php if (empty($projects)): ?>
<div class="card p-5 text-center text-muted">
    <i class="bi bi-file-earmark-text" style="font-size:3rem;opacity:.3;"></i>
    <?php if ($sf): ?>
        <p class="mt-3 mb-0">Nenhum projeto encontrado com esse filtro.</p>
        <a href="/admin/briefing" class="btn btn-outline-secondary mt-3">Ver todos</a>
    <?php else: ?>
        <p class="mt-3 mb-0">Nenhum briefing cadastrado ainda.</p>
        <a href="/admin/briefing/create" class="btn btn-primary mt-3">Criar primeiro briefing</a>
    <?php endif; ?>
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
                    <th>Status</th>
                    <th class="d-none d-lg-table-cell">Última Atualização</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $p):
                    $cStatus = $p['contract_status'] ?? null;
                    if ($cStatus === 'approved') {
                        $statusBadge = '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Aprovado</span>';
                    } elseif ($cStatus === 'generated') {
                        $statusBadge = '<span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Pendente</span>';
                    } else {
                        $statusBadge = '<span class="badge bg-secondary"><i class="bi bi-file-earmark me-1"></i>Sem contrato</span>';
                    }
                ?>
                <tr>
                    <td>
                        <div class="fw-medium"><?= bval($p['client_name']) ?></div>
                        <div class="text-muted small"><?= bval($p['client_email']) ?></div>
                        <?php if (!empty($p['client_phone'])): ?>
                            <div class="text-muted small"><i class="bi bi-phone me-1"></i><?= bval($p['client_phone']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= bval($p['project_type']) ?></td>
                    <td class="d-none d-md-table-cell"><?= bval($p['project_city']) ?></td>
                    <td class="d-none d-md-table-cell">
                        <?php if (!empty($p['contract_value'])): ?>
                            <span class="fw-medium">R$ <?= number_format((float)$p['contract_value'], 2, ',', '.') ?></span>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $statusBadge ?></td>
                    <td class="d-none d-lg-table-cell">
                        <?php if (!empty($p['last_object_date'])): ?>
                            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($p['last_object_date'])) ?></small>
                        <?php elseif (!empty($p['created_at'])): ?>
                            <small class="text-muted"><?= date('d/m/Y', strtotime($p['created_at'])) ?></small>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <a href="/admin/briefing/edit/<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger" title="Excluir"
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
<form id="delete-form" method="POST" action="/admin/briefing/delete" style="display:none;">
    <input type="hidden" name="id" id="delete-id">
</form>
<?php endif; // lista ?>

<?php else: ?>
<!-- ================================================================
     MODOS CREATE / EDIT — Stepper 3 etapas
================================================================ -->

<!-- IDs PHP disponíveis para o JS -->
<script>
var _projectId  = <?= $projectId ?>;
var _briefingId = <?= $briefingId ?>;
var _templateId = <?= $templateId ?>;
</script>

<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <a href="/admin/briefing" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Voltar
    </a>
    <h5 class="mb-0 fw-semibold">
        <?= $isCreate ? 'Novo Briefing' : 'Editar Briefing — ' . bval($project['client_name'] ?? '') ?>
    </h5>
    <span id="save-indicator" class="ms-auto badge bg-secondary d-none">Salvando…</span>
    <?php if (!$isCreate): ?>
    <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="downloadFullPdf()" title="Baixar PDF completo do briefing">
        <i class="bi bi-file-earmark-pdf me-1"></i> PDF Completo
    </button>
    <?php endif; ?>
</div>

<!-- Stepper visual -->
<div class="stepper-nav mb-4">
    <button class="step-btn active" id="step-btn-1" onclick="goToStepFromStepper(1)">
        <span class="step-num">1</span>
        <span class="step-label">Cadastro &amp; Briefing</span>
    </button>
    <div class="step-divider"></div>
    <button class="step-btn" id="step-btn-2" onclick="goToStepFromStepper(2)">
        <span class="step-num">2</span>
        <span class="step-label">Modelo do Objeto</span>
    </button>
    <div class="step-divider"></div>
    <button class="step-btn" id="step-btn-3" onclick="goToStepFromStepper(3)">
        <span class="step-num">3</span>
        <span class="step-label">Objeto Gerado</span>
    </button>
</div>

<!-- ── ETAPA 1 ───────────────────────────────────────────────── -->
<div class="step-panel" id="step-1">

    <!-- Card 1: Dados do Cliente -->
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0"><i class="bi bi-person me-2"></i>Dados do Cliente</h6></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nome / Razão Social <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bf-field" id="client_name" name="client_name"
                           value="<?= bval($project['client_name'] ?? '') ?>"
                           required maxlength="255" placeholder="Nome completo ou razão social">
                </div>
                <div class="col-md-3">
                    <label class="form-label">CPF / CNPJ</label>
                    <input type="text" class="form-control bf-field" id="client_document" name="client_document"
                           value="<?= fmtDoc($project['client_document'] ?? '') ?>"
                           maxlength="18" placeholder="000.000.000-00" inputmode="numeric" autocomplete="off">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Telefone</label>
                    <input type="text" class="form-control bf-field" id="client_phone" name="client_phone"
                           value="<?= fmtPhone($project['client_phone'] ?? '') ?>"
                           maxlength="15" placeholder="(11) 99999-9999" inputmode="numeric" autocomplete="tel">
                </div>
                <div class="col-md-6">
                    <label class="form-label">E-mail</label>
                    <input type="email" class="form-control bf-field" id="client_email" name="client_email"
                           value="<?= bval($project['client_email'] ?? '') ?>"
                           placeholder="cliente@email.com" autocomplete="email">
                    <div class="invalid-feedback">Informe um e-mail válido (ex: nome@dominio.com.br).</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Informações da Obra -->
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0"><i class="bi bi-buildings me-2"></i>Informações da Obra</h6></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Tipo de Obra / Imóvel</label>
                    <select class="form-select bf-field" id="project_type" name="project_type">
                        <option value="">— Selecione —</option>
                        <?php foreach (['Residencial','Comercial','Industrial','Reforma','Ampliação','Retrofit','Paisagismo','Outro'] as $tipo): ?>
                        <option value="<?= $tipo ?>" <?= ($project['project_type'] ?? '') === $tipo ? 'selected' : '' ?>><?= $tipo ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Área (m²)</label>
                    <input type="number" class="form-control bf-field" id="project_area" name="project_area"
                           step="0.01" min="0" value="<?= bval($project['project_area'] ?? '') ?>" placeholder="150.00">
                </div>
                <div class="col-md-3">
                    <label class="form-label">CEP</label>
                    <input type="text" class="form-control bf-field" id="project_cep" name="project_cep"
                           maxlength="9" placeholder="00000-000"
                           value="<?= fmtCep($project['project_cep'] ?? '') ?>"
                           inputmode="numeric" autocomplete="postal-code">
                    <div class="invalid-feedback" id="cep-feedback"></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cidade <?= micBtn('mic_city','project_city') ?></label>
                    <input type="text" class="form-control bf-field" id="project_city" name="project_city"
                           value="<?= bval($project['project_city'] ?? '') ?>" placeholder="São Paulo">
                </div>
                <div class="col-12">
                    <label class="form-label">Endereço <?= micBtn('mic_addr','project_address') ?></label>
                    <input type="text" class="form-control bf-field" id="project_address" name="project_address"
                           value="<?= bval($project['project_address'] ?? '') ?>" placeholder="Rua, número, bairro">
                </div>
                <div class="col-12">
                    <label class="form-label">Objetivo / Finalidade da Obra <?= micBtn('mic_goal','project_goal') ?></label>
                    <textarea class="form-control bf-field" id="project_goal" name="project_goal"
                              rows="3" placeholder="Descreva o objetivo principal da obra..."><?= bval($project['project_goal'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Briefing da Negociação -->
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Briefing da Negociação</h6></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Preferências do Cliente <?= micBtn('mic_pref','preferences') ?></label>
                    <textarea class="form-control bf-field" id="preferences" name="preferences"
                              rows="3" placeholder="Materiais, estilos, marcas preferidas..."><?= bval($briefing['preferences'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Prioridades <?= micBtn('mic_prio','priorities') ?></label>
                    <textarea class="form-control bf-field" id="priorities" name="priorities"
                              rows="3" placeholder="O que é mais importante para o cliente..."><?= bval($briefing['priorities'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Necessidades Específicas <?= micBtn('mic_needs','needs') ?></label>
                    <textarea class="form-control bf-field" id="needs" name="needs"
                              rows="3" placeholder="Requisitos técnicos, acessibilidade, etc."><?= bval($briefing['needs'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Restrições <?= micBtn('mic_rest','restrictions') ?></label>
                    <textarea class="form-control bf-field" id="restrictions" name="restrictions"
                              rows="3" placeholder="Limitações de orçamento, prazo, estrutura..."><?= bval($briefing['restrictions'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Resumo do Briefing <?= micBtn('mic_summ','briefing_summary') ?></label>
                    <textarea class="form-control bf-field" id="briefing_summary" name="briefing_summary"
                              rows="4" placeholder="Resumo geral da reunião de briefing..."><?= bval($briefing['briefing_summary'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Detalhes da Negociação <?= micBtn('mic_neg','negotiation_details') ?></label>
                    <textarea class="form-control bf-field" id="negotiation_details" name="negotiation_details"
                              rows="4" placeholder="Pontos acordados, pendências, observações..."><?= bval($briefing['negotiation_details'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Condições Comerciais -->
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0"><i class="bi bi-currency-dollar me-2"></i>Condições Comerciais</h6></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Valor Total (R$)</label>
                    <input type="text" class="form-control bf-field money-mask" id="contract_value" name="contract_value"
                           value="<?= !empty($briefing['contract_value']) ? number_format((float)$briefing['contract_value'], 2, ',', '.') : '' ?>" placeholder="0,00"
                           inputmode="numeric">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Entrada (R$)</label>
                    <input type="text" class="form-control bf-field money-mask" id="down_payment" name="down_payment"
                           value="<?= !empty($briefing['down_payment']) ? number_format((float)$briefing['down_payment'], 2, ',', '.') : '' ?>" placeholder="0,00"
                           inputmode="numeric">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Desconto (R$)</label>
                    <input type="text" class="form-control bf-field money-mask" id="discount_value" name="discount_value"
                           value="<?= !empty($briefing['discount_value']) ? number_format((float)$briefing['discount_value'], 2, ',', '.') : '' ?>" placeholder="0,00"
                           inputmode="numeric">
                </div>
                <div class="col-md-1">
                    <label class="form-label">Desc. %</label>
                    <input type="number" class="form-control bf-field" id="discount_percent" name="discount_percent"
                           step="0.01" min="0" max="100" value="<?= bval($briefing['discount_percent'] ?? '') ?>" placeholder="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Parcelas</label>
                    <input type="number" class="form-control bf-field" id="payment_installments" name="payment_installments"
                           min="1" value="<?= bval($briefing['payment_installments'] ?? '') ?>" placeholder="12">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data de Início</label>
                    <input type="date" class="form-control bf-field" id="start_date" name="start_date"
                           value="<?= bval($briefing['start_date'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data de Conclusão</label>
                    <input type="date" class="form-control bf-field" id="end_date" name="end_date"
                           value="<?= bval($briefing['end_date'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Prazo (dias corridos)</label>
                    <input type="number" class="form-control bf-field" id="deadline_days" name="deadline_days"
                           min="1" value="<?= bval($briefing['deadline_days'] ?? '') ?>" placeholder="180">
                </div>
                <!-- Aviso de divergência de prazo -->
                <div class="col-md-3 d-flex align-items-end">
                    <div id="deadline-warning" class="alert alert-warning py-1 px-2 mb-0 small d-none w-100" role="alert">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <span id="deadline-warning-text"></span>
                    </div>
                </div>
                <div class="col-md-9">
                    <label class="form-label">Detalhes do Parcelamento</label>
                    <input type="text" class="form-control bf-field" id="payment_details" name="payment_details"
                           value="<?= bval($briefing['payment_details'] ?? '') ?>"
                           placeholder="Ex: 30% entrada + 70% em 6x mensais">
                </div>
                <div class="col-12">
                    <label class="form-label">Cláusulas Especiais <?= micBtn('mic_clauses','clauses') ?></label>
                    <textarea class="form-control bf-field" id="clauses" name="clauses"
                              rows="4" placeholder="Cláusulas específicas a serem incluídas no contrato..."><?= bval($briefing['clauses'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Rodapé Etapa 1 -->
    <div class="step-footer">
        <a href="/admin/briefing" class="btn btn-outline-secondary">Cancelar</a>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" onclick="saveDraft()">
                <i class="bi bi-floppy me-1"></i> Salvar Rascunho
            </button>
            <button type="button" class="btn btn-primary" onclick="saveAndContinue()">
                Salvar e Continuar <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </div>
    </div>

</div><!-- /step-1 -->

<!-- ── ETAPA 2 ───────────────────────────────────────────────── -->
<div class="step-panel d-none" id="step-2">

    <!-- Variáveis disponíveis -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-braces me-2"></i>Variáveis Disponíveis</h6>
            <small class="text-muted">Clique para copiar</small>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ([
                    'cliente_nome','cliente_documento','cliente_telefone','cliente_email',
                    'tipo_obra','endereco','cidade','objetivo','area_m2',
                    'briefing','valor_contrato','entrada','desconto','parcelas','parcelamento',
                    'data_inicio','data_conclusao','prazo_dias','clausulas'
                ] as $var): ?>
                <button type="button" class="btn btn-sm btn-outline-secondary font-monospace var-chip"
                        data-var="{{<?= $var ?>}}" onclick="copyVar(this)" title="Clique para copiar">
                    {{<?= $var ?>}}
                </button>
                <?php endforeach; ?>
            </div>
            <p class="text-muted small mt-2 mb-0">
                <i class="bi bi-info-circle me-1"></i>Substituídos automaticamente pelos dados do briefing ao gerar.
            </p>
        </div>
    </div>

    <!-- Seleção de modelo -->
    <?php if (!empty($templates)): ?>
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0"><i class="bi bi-layout-text-window me-2"></i>Modelo de Prompt</h6></div>
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
                    <button type="button" class="btn btn-outline-secondary w-100"
                            data-bs-toggle="modal" data-bs-target="#modalTemplate">
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
            <h6 class="mb-0">
                <i class="bi bi-pencil-square me-2"></i>Template do Objeto
                <?= micBtn('mic_tpl','prompt-template-field') ?>
            </h6>
            <button type="button" class="btn btn-sm btn-outline-secondary"
                    onclick="resetTemplate()" title="Recarregar modelo selecionado">
                <i class="bi bi-arrow-counterclockwise"></i>
            </button>
        </div>
        <div class="card-body">
            <!-- Editor Rich Text -->
            <div id="prompt-toolbar">
                <span class="ql-formats">
                    <button class="ql-bold" title="Negrito"></button>
                    <button class="ql-italic" title="Itálico"></button>
                    <button class="ql-underline" title="Sublinhado"></button>
                </span>
                <span class="ql-formats">
                    <button class="ql-list" value="ordered" title="Lista numerada"></button>
                    <button class="ql-list" value="bullet" title="Lista com marcadores"></button>
                </span>
                <span class="ql-formats">
                    <button class="ql-clean" title="Limpar formatação"></button>
                </span>
            </div>
            <div id="prompt-editor" style="min-height:250px;font-size:.85rem;line-height:1.7;"><?= nl2br(htmlspecialchars($defaultTemplate['prompt_template'] ?? '', ENT_QUOTES)) ?></div>
            <!-- Campo hidden que sincroniza o conteúdo para envio -->
            <textarea id="prompt-template-field" class="d-none" name="prompt_template"><?= bval($defaultTemplate['prompt_template'] ?? '') ?></textarea>
        </div>
    </div>

    <!-- Prévia dos dados -->
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0"><i class="bi bi-eye me-2"></i>Prévia dos Dados do Briefing</h6></div>
        <div class="card-body">
            <div class="row g-2 small" id="preview-vars">
                <!-- Preenchido via JS com os dados em memória -->
            </div>
        </div>
    </div>

    <!-- Rodapé Etapa 2 -->
    <div class="step-footer">
        <button type="button" class="btn btn-outline-secondary" onclick="goToStep(1)">
            <i class="bi bi-arrow-left me-1"></i> Voltar
        </button>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" onclick="saveModelOnly()">
                <i class="bi bi-floppy me-1"></i> Salvar Modelo
            </button>
            <button type="button" class="btn btn-primary btn-lg" id="btn-generate" onclick="triggerGenerate()">
                <i class="bi bi-stars me-2"></i> Gerar Objeto do Contrato
            </button>
        </div>
    </div>

</div><!-- /step-2 -->

<!-- ── ETAPA 3 ───────────────────────────────────────────────── -->
<div class="step-panel d-none" id="step-3">

    <!-- Estado de loading -->
    <div id="gen-loading" class="card p-5 text-center d-none">
        <div class="spinner-border text-primary mx-auto mb-3" style="width:3rem;height:3rem;"></div>
        <p class="fw-semibold mb-1">Gerando com IA...</p>
        <p class="text-muted small mb-0">Aguarde enquanto a IA sintetiza o contexto completo do briefing.</p>
    </div>

    <!-- Resultado -->
    <div id="object-result-wrapper">
        <?php if ($contractObject): ?>
        <?php include __DIR__ . '/_object_result.php'; ?>
        <?php else: ?>
        <div class="card p-5 text-center text-muted" id="gen-empty-state">
            <i class="bi bi-file-earmark-plus" style="font-size:3rem;opacity:.3;"></i>
            <p class="mt-3 mb-0">Nenhum objeto gerado ainda.</p>
            <p class="small">Volte para a <strong>Etapa 2</strong> e clique em <strong>Gerar</strong>.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Rodapé Etapa 3 -->
    <div class="step-footer mt-4">
        <button type="button" class="btn btn-outline-secondary <?= ($contractObject && ($contractObject['status'] ?? '') === 'approved') ? 'd-none' : '' ?>" id="btn-edit-model-footer" onclick="goToStep(2)">
            <i class="bi bi-pencil me-1"></i> Editar Modelo
        </button>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary <?= ($contractObject && ($contractObject['status'] ?? '') === 'approved') ? 'd-none' : '' ?>" id="btn-regenerate" onclick="triggerGenerate()">
                <i class="bi bi-arrow-repeat me-1"></i> Gerar Novamente
            </button>
            <button type="button" class="btn btn-success <?= ($contractObject && ($contractObject['status'] ?? '') === 'approved') ? 'd-none' : '' ?>" id="btn-approve-footer" onclick="approveObject()">
                <i class="bi bi-check2-circle me-1"></i> Aprovar Projeto
            </button>
        </div>
    </div>

</div><!-- /step-3 -->

<?php endif; // fim create/edit ?>

<!-- ================================================================
     MODAL — Novo Template
================================================================ -->
<div class="modal fade" id="modalTemplate" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-layout-text-window me-2"></i>Novo Modelo de Prompt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nome <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="tpl-name" maxlength="255" placeholder="Ex: Objeto Padrão Residencial">
                </div>
                <div class="mb-3">
                    <label class="form-label">Descrição</label>
                    <input type="text" class="form-control" id="tpl-desc" placeholder="Breve descrição">
                </div>
                <div class="mb-3">
                    <label class="form-label">Template <span class="text-danger">*</span></label>
                    <textarea class="form-control font-monospace" id="tpl-prompt" rows="10"
                              style="font-size:.82rem;line-height:1.6;"
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

<!-- ================================================================
     ESTILOS
================================================================ -->
<!-- Quill Editor CSS -->
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<style>
/* Stepper */
.stepper-nav {
    display: flex;
    align-items: center;
    gap: 0;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
    padding: .5rem 1rem;
    overflow-x: auto;
}
.step-btn {
    display: flex;
    align-items: center;
    gap: .5rem;
    background: none;
    border: none;
    cursor: default;
    padding: .5rem .75rem;
    border-radius: 6px;
    color: #6c757d;
    transition: all .2s;
    white-space: nowrap;
    pointer-events: none;
}
.step-btn:hover { background: none; }
.step-btn.active { color: var(--color-primary); font-weight: 600; }
.step-btn.done   { color: #28a745; }
.step-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px; height: 26px;
    border-radius: 50%;
    background: #e9ecef;
    font-size: .8rem;
    font-weight: 700;
    transition: all .2s;
}
.step-btn.active .step-num { background: var(--color-primary); color: #fff; }
.step-btn.done .step-num   { background: #28a745; color: #fff; }
.step-divider { flex: 1; height: 2px; background: #dee2e6; min-width: 20px; max-width: 60px; }
.step-label { font-size: .875rem; }

/* Rodapé fixo das etapas */
.step-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: .75rem;
    padding: 1rem 0 2rem;
    border-top: 1px solid #e9ecef;
    margin-top: .5rem;
}

/* Microfone */
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
.mic-btn:hover   { background: var(--color-primary); color: #fff; border-color: var(--color-primary); }
.mic-btn.active  { background: var(--color-accent);  color: #fff; border-color: var(--color-accent); animation: pulse-mic .7s infinite; }
@keyframes pulse-mic { 0%,100%{transform:scale(1);} 50%{transform:scale(1.18);} }

/* Indicador de polimento de texto por IA */
.polishing {
    opacity: .7;
    border-color: var(--color-primary) !important;
    background: linear-gradient(90deg, #f8f9fa 25%, #e9ecef 50%, #f8f9fa 75%);
    background-size: 200% 100%;
    animation: shimmer 1.2s infinite;
}
@keyframes shimmer { 0%{background-position:200% 0;} 100%{background-position:-200% 0;} }

/* Variáveis */
.var-chip { font-size: .78rem; }
.var-chip.copied { background: var(--color-primary) !important; color: #fff !important; border-color: var(--color-primary) !important; }

/* Objeto gerado */
#object-text-display {
    white-space: pre-wrap;
    line-height: 1.8;
    font-size: .95rem;
    background: #fafafa;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 1.25rem 1.5rem;
    max-height: 500px;
    overflow-y: auto;
    transition: max-height .3s ease;
}
#object-text-display.expanded {
    max-height: none;
    overflow: visible;
}
.expand-toggle {
    display: block;
    text-align: center;
    padding: 0.5rem;
    font-size: .82rem;
    color: var(--color-primary, #4a6cf7);
    cursor: pointer;
    border-top: 1px solid #e9ecef;
    background: linear-gradient(to bottom, rgba(250,250,250,0.8), #fff);
}
.expand-toggle:hover { text-decoration: underline; }

/* Quill Editor customizações */
#prompt-editor { background: #fff; }
#prompt-editor .ql-editor { font-family: 'Segoe UI', sans-serif; font-size: .85rem; line-height: 1.7; }
#prompt-editor .ql-editor p { margin-bottom: 0.4rem; }
.ql-toolbar.ql-snow { border-radius: 6px 6px 0 0; border-color: #dee2e6; }
.ql-container.ql-snow { border-radius: 0 0 6px 6px; border-color: #dee2e6; }
</style>

<!-- ================================================================
     JAVASCRIPT
================================================================ -->
<script>
// ─── Estado global ───────────────────────────────────────────────
var _currentStep  = 1;
var _objectId     = <?= (int)($contractObject['id'] ?? 0) ?>;
var _isApproved   = <?= ($contractObject && ($contractObject['status'] ?? '') === 'approved') ? 'true' : 'false' ?>;

// ─── Máscara de moeda (R$ 100.000,00) ───────────────────────────
function formatMoney(value) {
    // Remove tudo que não é dígito
    var digits = value.replace(/\D/g, '');
    if (digits === '') return '';
    // Converte para centavos
    var num = parseInt(digits, 10);
    var str = (num / 100).toFixed(2);
    // Formata: separa parte inteira e decimal
    var parts = str.split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return parts[0] + ',' + parts[1];
}

function unformatMoney(value) {
    // Remove pontos de milhar, troca vírgula por ponto
    return value.replace(/\./g, '').replace(',', '.');
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.money-mask').forEach(function(el) {
        el.addEventListener('input', function() {
            var pos = el.selectionStart;
            var oldLen = el.value.length;
            el.value = formatMoney(el.value);
            var newLen = el.value.length;
            // Ajusta posição do cursor
            var newPos = pos + (newLen - oldLen);
            el.setSelectionRange(newPos, newPos);
        });
    });

    // ─── Cálculo automático de prazo / data de conclusão ─────────
    var startEl    = document.getElementById('start_date');
    var endEl      = document.getElementById('end_date');
    var daysEl     = document.getElementById('deadline_days');
    var warnEl     = document.getElementById('deadline-warning');
    var warnTextEl = document.getElementById('deadline-warning-text');

    function calcDaysBetween(d1, d2) {
        var ms = d2.getTime() - d1.getTime();
        return Math.round(ms / (1000 * 60 * 60 * 24));
    }

    function checkDeadlineDivergence() {
        if (!startEl || !endEl || !daysEl || !warnEl) return;
        var start = startEl.value;
        var end   = endEl.value;
        var days  = parseInt(daysEl.value, 10);

        if (!start || !end || !days) {
            warnEl.classList.add('d-none');
            return;
        }

        var d1 = new Date(start + 'T00:00:00');
        var d2 = new Date(end + 'T00:00:00');
        var realDays = calcDaysBetween(d1, d2);

        if (realDays !== days) {
            warnTextEl.textContent = 'Prazo divergente! Entre as datas são ' + realDays + ' dias, mas o prazo informado é ' + days + ' dias.';
            warnEl.classList.remove('d-none');
        } else {
            warnEl.classList.add('d-none');
        }
    }

    // Ao mudar o prazo em dias + data início, calcula data de conclusão automaticamente
    function autoCalcEndDate() {
        if (!startEl || !daysEl || !endEl) return;
        var start = startEl.value;
        var days  = parseInt(daysEl.value, 10);
        if (!start || !days) return;

        // Só calcula automaticamente se a data de conclusão estiver vazia
        // ou se o usuário acabou de alterar o prazo/início
        var d1 = new Date(start + 'T00:00:00');
        d1.setDate(d1.getDate() + days);
        var yyyy = d1.getFullYear();
        var mm = String(d1.getMonth() + 1).padStart(2, '0');
        var dd = String(d1.getDate()).padStart(2, '0');
        endEl.value = yyyy + '-' + mm + '-' + dd;
        warnEl.classList.add('d-none');
    }

    if (startEl && daysEl && endEl) {
        // Quando muda prazo ou data início → calcula data de conclusão
        daysEl.addEventListener('input', function() { autoCalcEndDate(); });
        startEl.addEventListener('change', function() {
            if (daysEl.value) autoCalcEndDate();
            else checkDeadlineDivergence();
        });

        // Quando muda data de conclusão manualmente → verifica divergência
        endEl.addEventListener('change', function() { checkDeadlineDivergence(); });

        // Verifica no carregamento
        checkDeadlineDivergence();
    }
});

// ─── Stepper ─────────────────────────────────────────────────────
// Navegação interna (usada pelos botões de ação)
function goToStep(n, bypassApproval) {
    // Se aprovado e tentando voltar para etapas 1 ou 2, exigir motivo
    if (_isApproved && n < 3 && !bypassApproval) {
        var motivo = prompt('Este contrato já foi aprovado.\nPara voltar à edição, informe o motivo:');
        if (!motivo || !motivo.trim()) {
            showToast('Operação cancelada. Informe um motivo para editar após aprovação.', 'warning');
            return;
        }
        // Registra o motivo (podemos enviar ao servidor futuramente)
        _isApproved = false;
        showToast('Aprovação revogada. Motivo: ' + motivo.trim(), 'info');
        updateApprovalUI();
    }

    document.querySelectorAll('.step-panel').forEach(function(p) { p.classList.add('d-none'); });
    var panel = document.getElementById('step-' + n);
    if (panel) panel.classList.remove('d-none');

    document.querySelectorAll('.step-btn').forEach(function(b, i) {
        b.classList.remove('active');
        if (i + 1 < n)  b.classList.add('done');
        if (i + 1 >= n) b.classList.remove('done');
    });
    var activeBtn = document.getElementById('step-btn-' + n);
    if (activeBtn) activeBtn.classList.add('active');

    _currentStep = n;

    // Ao chegar na etapa 2, atualiza prévia das variáveis
    if (n === 2) renderPreviewVars();
    // Ao chegar na etapa 3, atualiza UI de aprovação
    if (n === 3) updateApprovalUI();
}

// Navegação pelo stepper (bloqueada — só indicativo visual)
function goToStepFromStepper(n) {
    // Não faz nada — navegação só pelos botões de ação
    return;
}

// Atualiza a UI conforme status de aprovação
function updateApprovalUI() {
    var badge = document.getElementById('obj-status-badge');
    var btnFooter = document.getElementById('btn-approve-footer');
    var btnRegen = document.getElementById('btn-regenerate');
    var btnEditModel = document.getElementById('btn-edit-model-footer');

    if (_isApproved) {
        if (badge) { badge.textContent = 'Aprovado'; badge.className = 'badge bg-success ms-2'; }
        if (btnFooter) btnFooter.classList.add('d-none');
        if (btnRegen) btnRegen.classList.add('d-none');
        if (btnEditModel) btnEditModel.classList.add('d-none');
    } else {
        if (badge && _objectId) { badge.textContent = 'Gerado'; badge.className = 'badge bg-warning text-dark ms-2'; }
        if (btnFooter) { btnFooter.classList.remove('d-none'); btnFooter.disabled = false; btnFooter.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Aprovar Projeto'; }
        if (btnRegen) btnRegen.classList.remove('d-none');
        if (btnEditModel) btnEditModel.classList.remove('d-none');
    }
}

// ─── Coleta todos os campos do briefing ─────────────────────────
function collectFormData() {
    var fd = new FormData();
    fd.append('project_id',  _projectId);
    fd.append('briefing_id', _briefingId);
    document.querySelectorAll('.bf-field').forEach(function(el) {
        var val = el.value;
        // Converte campo de moeda formatado para valor numérico
        if (el.classList.contains('money-mask') && val) {
            val = unformatMoney(val);
        }
        fd.append(el.name, val);
    });
    return fd;
}

// ─── Persistência via AJAX ───────────────────────────────────────
function saveAjax(callback) {
    var ind = document.getElementById('save-indicator');
    if (ind) { ind.textContent = 'Salvando…'; ind.classList.remove('d-none','bg-success','bg-danger'); ind.classList.add('bg-secondary'); }

    fetch('/admin/briefing/save-ajax', { method: 'POST', body: collectFormData() })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            _projectId  = data.project_id;
            _briefingId = data.briefing_id;
            if (ind) { ind.textContent = 'Salvo'; ind.classList.remove('bg-secondary','bg-danger'); ind.classList.add('bg-success'); }
            if (callback) callback(data);
        } else {
            if (ind) { ind.textContent = 'Erro'; ind.classList.remove('bg-secondary','bg-success'); ind.classList.add('bg-danger'); }
            alert(data.error || 'Erro ao salvar.');
        }
    })
    .catch(function() {
        if (ind) { ind.textContent = 'Erro'; ind.classList.remove('bg-secondary','bg-success'); ind.classList.add('bg-danger'); }
        alert('Falha na requisição de salvamento.');
    });
}

function saveDraft() { saveAjax(function(d) { showToast('Rascunho salvo!', 'success'); }); }

function saveAndContinue() {
    saveAjax(function(d) {
        // Atualiza URL sem recarregar (para que F5 reabra o briefing correto)
        if (history.pushState && d.edit_url) {
            history.replaceState({}, '', d.edit_url);
        }
        goToStep(2);
    });
}

function saveModelOnly() { showToast('Modelo mantido em memória. Clique em Gerar para processar.', 'info'); }

// ─── Prévia de variáveis na etapa 2 ─────────────────────────────
var _varKeys = [
    ['cliente_nome',      'client_name'],
    ['cliente_documento', 'client_document'],
    ['cliente_telefone',  'client_phone'],
    ['cliente_email',     'client_email'],
    ['tipo_obra',         'project_type'],
    ['endereco',          'project_address'],
    ['cidade',            'project_city'],
    ['objetivo',          'project_goal'],
    ['area_m2',           'project_area'],
    ['valor_contrato',    'contract_value'],
    ['entrada',           'down_payment'],
    ['desconto',          'discount_value'],
    ['parcelas',          'payment_installments'],
    ['parcelamento',      'payment_details'],
    ['data_inicio',       'start_date'],
    ['data_conclusao',    'end_date'],
    ['prazo_dias',        'deadline_days'],
];

function renderPreviewVars() {
    var wrap = document.getElementById('preview-vars');
    if (!wrap) return;
    var html = '';
    _varKeys.forEach(function(pair) {
        var el  = document.getElementById(pair[1]);
        var val = el ? el.value.trim() : '';
        var disp = val ? escHtml(val.substring(0, 80)) + (val.length > 80 ? '…' : '')
                       : '<span class="text-danger">não preenchido</span>';
        html += '<div class="col-sm-6 col-md-4 mb-1">'
              + '<span class="text-muted font-monospace" style="font-size:.75rem;">{{' + pair[0] + '}}</span><br>'
              + '<span class="fw-medium">' + disp + '</span></div>';
    });
    wrap.innerHTML = html;
}

// ─── Template select ─────────────────────────────────────────────
function loadTemplate(id) {
    var sel = document.getElementById('template-select');
    if (!sel) return;
    var opt = sel.querySelector('option[value="' + id + '"]');
    if (opt) {
        var promptText = opt.dataset.prompt || '';
        document.getElementById('prompt-template-field').value = promptText;
        // Atualiza o Quill se disponível
        if (typeof _quillEditor !== 'undefined' && _quillEditor) {
            _quillEditor.root.innerHTML = escHtml(promptText).replace(/\n/g, '<br>');
        }
        _templateId = parseInt(id, 10) || 0;
    }
}
function resetTemplate() {
    var sel = document.getElementById('template-select');
    if (sel) loadTemplate(sel.value);
}
(function() {
    var sel = document.getElementById('template-select');
    if (sel) loadTemplate(sel.value);
})();

// ─── Salvar novo template ────────────────────────────────────────
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
            var sel = document.getElementById('template-select');
            if (sel) {
                var opt = document.createElement('option');
                opt.value = data.id;
                opt.textContent = data.name;
                opt.dataset.prompt = prompt;
                sel.appendChild(opt);
                sel.value = data.id;
                loadTemplate(data.id);
            }
            setTimeout(function() {
                bootstrap.Modal.getInstance(document.getElementById('modalTemplate')).hide();
                fb.textContent = '';
            }, 700);
        } else {
            fb.textContent = data.error || 'Erro ao salvar.';
            fb.className = 'small text-danger mt-1';
        }
    })
    .catch(function() { fb.textContent = 'Erro na requisição.'; fb.className = 'small text-danger mt-1'; });
}

// ─── Copiar variável ─────────────────────────────────────────────
function copyVar(btn) {
    var text = btn.dataset.var;
    navigator.clipboard.writeText(text).then(function() {
        btn.classList.add('copied');
        setTimeout(function() { btn.classList.remove('copied'); }, 1200);
    }).catch(function() {
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

// ─── Geração do objeto via IA ────────────────────────────────────
function triggerGenerate() {
    // Primeiro garante que os dados estão salvos
    saveAjax(function() {
        if (!_briefingId) { alert('Salve o briefing antes de gerar.'); return; }
        doGenerate();
    });
}

function doGenerate() {
    // Avança para etapa 3 e mostra loading
    goToStep(3);
    var loading = document.getElementById('gen-loading');
    var wrapper = document.getElementById('object-result-wrapper');
    var empty   = document.getElementById('gen-empty-state');
    if (loading) loading.classList.remove('d-none');
    if (wrapper) wrapper.innerHTML = '';
    if (empty)   empty.classList.add('d-none');

    // Desabilita botões de gerar
    ['btn-generate','btn-regenerate'].forEach(function(id) {
        var b = document.getElementById(id);
        if (b) { b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Gerando…'; }
    });

    var sel          = document.getElementById('template-select');
    var tplId        = sel ? sel.value : _templateId;
    var customPrompt = document.getElementById('prompt-template-field').value.trim();

    // Monta contexto completo: lê os campos da etapa 1 diretamente do DOM
    var fd = new FormData();
    fd.append('briefing_id',   _briefingId);
    fd.append('template_id',   tplId);
    fd.append('custom_prompt', customPrompt);

    // Passa também todos os campos da etapa 1 para o servidor poder enriquecer o prompt
    document.querySelectorAll('.bf-field').forEach(function(el) {
        fd.append('ctx_' + el.name, el.value);
    });

    fetch('/admin/briefing/generate-object', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (loading) loading.classList.add('d-none');
        resetGenerateButtons();

        if (data.success) {
            _objectId = data.object_id;
            renderObjectResult(data.text, data.object_id);
        } else {
            if (wrapper) wrapper.innerHTML = '<div class="alert alert-danger">'
                + escHtml(data.error || 'Erro ao gerar objeto.') + '</div>';
        }
    })
    .catch(function() {
        if (loading) loading.classList.add('d-none');
        resetGenerateButtons();
        if (wrapper) wrapper.innerHTML = '<div class="alert alert-danger">Falha na requisição. Verifique a chave OpenAI nas Configurações.</div>';
    });
}

function resetGenerateButtons() {
    var btnG = document.getElementById('btn-generate');
    var btnR = document.getElementById('btn-regenerate');
    if (btnG) { btnG.disabled = false; btnG.innerHTML = '<i class="bi bi-stars me-2"></i> Gerar Objeto do Contrato'; }
    if (btnR) { btnR.disabled = false; btnR.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Gerar Novamente'; }
}

function renderObjectResult(text, objectId) {
    var wrapper = document.getElementById('object-result-wrapper');
    wrapper.innerHTML = '<div class="card mb-3">'
        + '<div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">'
        + '<h6 class="mb-0"><i class="bi bi-file-earmark-check me-2"></i>Objeto do Contrato'
        + '<span id="obj-status-badge" class="badge bg-warning text-dark ms-2">Gerado</span></h6>'
        + '<div class="d-flex gap-2">'
        + '<button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyObjectText()" title="Copiar texto">'
        + '<i class="bi bi-clipboard"></i></button>'
        + '<button type="button" class="btn btn-sm btn-outline-danger" onclick="downloadObjectPdf()" title="Baixar PDF">'
        + '<i class="bi bi-file-earmark-pdf"></i></button>'
        + '</div></div>'
        + '<div class="card-body"><div id="object-text-display">' + escHtml(text) + '</div>'
        + '<span class="expand-toggle" id="expand-object-btn" onclick="toggleExpandObject()">'
        + '<i class="bi bi-chevron-down me-1"></i> Ver contrato completo</span></div>'
        + '</div>';

    wrapper.dataset.objectText = text;
    wrapper.dataset.objectId   = objectId;
}

function copyObjectText() {
    var text = document.getElementById('object-result-wrapper').dataset.objectText || '';
    navigator.clipboard.writeText(text).then(function() { showToast('Texto copiado!', 'success'); });
}

function toggleExpandObject() {
    var display = document.getElementById('object-text-display');
    var btn = document.getElementById('expand-object-btn');
    if (!display || !btn) return;
    if (display.classList.contains('expanded')) {
        display.classList.remove('expanded');
        btn.innerHTML = '<i class="bi bi-chevron-down me-1"></i> Ver contrato completo';
    } else {
        display.classList.add('expanded');
        btn.innerHTML = '<i class="bi bi-chevron-up me-1"></i> Recolher';
    }
}

function approveObject() {
    if (!_objectId) { alert('Nenhum objeto gerado para aprovar.'); return; }
    if (!confirm('Aprovar este objeto do contrato?')) return;

    var fd = new FormData();
    fd.append('object_id', _objectId);

    fetch('/admin/briefing/approve-object', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            markApproved(true);
            showToast('Projeto aprovado!', 'success');
        } else {
            alert(data.error || 'Erro ao aprovar.');
        }
    });
}

function markApproved(animate) {
    _isApproved = true;
    updateApprovalUI();
    // Esconde botão aprovar no header do card também
    var btnApproveHeader = document.getElementById('btn-approve');
    if (btnApproveHeader) btnApproveHeader.classList.add('d-none');
}

// ─── Formatação de texto ditado por voz ──────────────────────────
function formatSpeechText(text) {
    // Remove espaços duplos (causados por pausas na fala)
    text = text.replace(/\s{2,}/g, ' ').trim();
    // Primeira letra maiúscula
    if (text.length > 0) {
        text = text.charAt(0).toUpperCase() + text.slice(1);
    }
    return text;
}

function polishSpeechText(targetEl) {
    if (!targetEl || !targetEl.value.trim()) return;
    var originalText = targetEl.value.trim();
    // Mostra indicação de que está polindo
    targetEl.classList.add('polishing');

    var fd = new FormData();
    fd.append('text', originalText);

    fetch('/admin/briefing/polish-text', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        targetEl.classList.remove('polishing');
        if (data.success && data.text) {
            targetEl.value = data.text;
            // Sincroniza com o Quill se necessário
            if (targetEl.id === 'prompt-template-field' && typeof _quillEditor !== 'undefined' && _quillEditor) {
                _quillEditor.root.innerHTML = escHtml(data.text).replace(/\n/g, '<br>');
            }
        }
    })
    .catch(function() {
        targetEl.classList.remove('polishing');
    });
}

// ─── SpeechRecognition — ditado por voz inline ───────────────────
var _srInstance  = null;
var _srTargetId  = null;
var _srBtnEl     = null;
var _srSupported = ('SpeechRecognition' in window || 'webkitSpeechRecognition' in window);

function toggleSpeech(btnId, targetId) {
    if (!_srSupported) {
        alert('Reconhecimento de voz não suportado neste navegador. Use Chrome ou Edge.');
        return;
    }

    // Se já está gravando no mesmo botão, para
    if (_srInstance && _srBtnEl && _srBtnEl.id === btnId) {
        _srInstance.stop();
        return;
    }

    // Para qualquer instância anterior
    if (_srInstance) { try { _srInstance.stop(); } catch(e) {} }

    var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    var sr = new SpeechRecognition();
    sr.lang              = 'pt-BR';
    sr.continuous        = true;      // grava até clicar de novo
    sr.interimResults    = true;      // mostra resultado parcial em tempo real
    sr.maxAlternatives   = 1;

    _srInstance = sr;
    _srTargetId = targetId;
    _srBtnEl    = document.getElementById(btnId);

    var targetEl    = document.getElementById(targetId);
    var baseValue   = targetEl ? targetEl.value : '';
    var interim     = '';

    sr.onstart = function() {
        if (_srBtnEl) { _srBtnEl.classList.add('active'); _srBtnEl.innerHTML = '<i class="bi bi-stop-fill"></i>'; _srBtnEl.title = 'Parar ditado'; }
    };

    sr.onresult = function(event) {
        interim = '';
        var final = '';
        for (var i = event.resultIndex; i < event.results.length; i++) {
            if (event.results[i].isFinal) {
                final += event.results[i][0].transcript;
            } else {
                interim += event.results[i][0].transcript;
            }
        }
        if (final) {
            final = formatSpeechText(final);
            baseValue = (baseValue + (baseValue ? ' ' : '') + final).trim();
            // Garante primeira letra maiúscula do texto completo
            baseValue = baseValue.charAt(0).toUpperCase() + baseValue.slice(1);
        }
        if (targetEl) { targetEl.value = baseValue + (interim ? ' ' + interim : ''); }
    };

    sr.onerror = function(event) {
        if (event.error !== 'no-speech' && event.error !== 'aborted') {
            showToast('Erro no microfone: ' + event.error, 'danger');
        }
        resetSpeechBtn();
    };

    sr.onend = function() {
        // Garante que o texto final (sem interim) fica no campo
        if (targetEl) {
            baseValue = formatSpeechText(baseValue);
            targetEl.value = baseValue;
            // Sincroniza com o Quill se o target é o template
            if (targetId === 'prompt-template-field' && typeof _quillEditor !== 'undefined' && _quillEditor) {
                _quillEditor.root.innerHTML = escHtml(baseValue).replace(/\n/g, '<br>');
            }
            // Envia para IA polir gramática (vírgulas, pontos, etc.)
            polishSpeechText(targetEl);
        }
        resetSpeechBtn();
    };

    sr.start();
}

function resetSpeechBtn() {
    if (_srBtnEl) {
        _srBtnEl.classList.remove('active');
        _srBtnEl.innerHTML = '<i class="bi bi-mic"></i>';
        _srBtnEl.title = 'Ditado por voz (clique para iniciar/parar)';
    }
    _srInstance = null;
    _srBtnEl    = null;
    _srTargetId = null;
}

// ─── Máscaras ────────────────────────────────────────────────────
(function() {
    var doc = document.getElementById('client_document');
    if (doc) doc.addEventListener('input', function() {
        var v = doc.value.replace(/\D/g,'').substring(0,14);
        if (v.length <= 11) {
            v = v.replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d{1,2})$/,'$1-$2');
            doc.maxLength = 14;
        } else {
            v = v.replace(/(\d{2})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1/$2').replace(/(\d{4})(\d{1,2})$/,'$1-$2');
            doc.maxLength = 18;
        }
        doc.value = v;
    });

    var tel = document.getElementById('client_phone');
    if (tel) tel.addEventListener('input', function() {
        var v = tel.value.replace(/\D/g,'').substring(0,11);
        v = v.length <= 10 ? v.replace(/(\d{2})(\d)/,'($1) $2').replace(/(\d{4})(\d)/,'$1-$2')
                           : v.replace(/(\d{2})(\d)/,'($1) $2').replace(/(\d{5})(\d)/,'$1-$2');
        tel.value = v;
    });

    var cepEl = document.getElementById('project_cep');
    if (cepEl) cepEl.addEventListener('input', function() {
        var v = cepEl.value.replace(/\D/g,'').substring(0,8);
        if (v.length > 5) v = v.replace(/(\d{5})(\d)/,'$1-$2');
        cepEl.value = v;
    });
})();

// ─── Validação de e-mail ─────────────────────────────────────────
(function() {
    var emailEl = document.getElementById('client_email');
    if (!emailEl) return;
    function check() {
        var v = emailEl.value.trim();
        var ok = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v);
        emailEl.classList.toggle('is-invalid', v !== '' && !ok);
        emailEl.classList.toggle('is-valid',   v !== '' &&  ok);
        return ok || v === '';
    }
    emailEl.addEventListener('blur', check);
    emailEl.addEventListener('input', check);
})();

// ─── ViaCEP ──────────────────────────────────────────────────────
(function() {
    var cepEl  = document.getElementById('project_cep');
    var addrEl = document.getElementById('project_address');
    var cityEl = document.getElementById('project_city');
    var fb     = document.getElementById('cep-feedback');
    if (!cepEl) return;

    cepEl.addEventListener('input', function() {
        var d = cepEl.value.replace(/\D/g,'');
        if (d.length === 8) {
            fb.textContent = 'Consultando…';
            fb.style.display = 'block';
            fetch('https://viacep.com.br/ws/' + d + '/json/')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.erro) {
                    cepEl.classList.add('is-invalid');
                    fb.textContent = 'CEP não encontrado.';
                } else {
                    cepEl.classList.remove('is-invalid'); cepEl.classList.add('is-valid');
                    fb.style.display = 'none';
                    if (addrEl) { var l = [data.logradouro, data.bairro].filter(Boolean).join(', '); if (l) addrEl.value = l; }
                    if (cityEl && data.localidade) cityEl.value = data.localidade;
                }
            })
            .catch(function() { fb.textContent = 'Erro ao consultar CEP.'; });
        } else {
            cepEl.classList.remove('is-invalid','is-valid');
            fb.style.display = 'none';
        }
    });
})();

// ─── Exclusão ────────────────────────────────────────────────────
function confirmDelete(id, name) {
    var motivo = prompt('Excluir o briefing de "' + name + '"?\n\nTodos os dados vinculados serão removidos permanentemente.\n\nInforme o motivo da exclusão:');
    if (!motivo || !motivo.trim()) {
        if (motivo !== null) showToast('Exclusão cancelada. Informe um motivo.', 'warning');
        return;
    }
    document.getElementById('delete-id').value = id;
    // Adiciona o motivo no formulário
    var motivoInput = document.getElementById('delete-reason');
    if (!motivoInput) {
        motivoInput = document.createElement('input');
        motivoInput.type = 'hidden';
        motivoInput.name = 'delete_reason';
        motivoInput.id = 'delete-reason';
        document.getElementById('delete-form').appendChild(motivoInput);
    }
    motivoInput.value = motivo.trim();
    document.getElementById('delete-form').submit();
}

// ─── Utilitários ─────────────────────────────────────────────────
function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function showToast(msg, type) {
    var c = document.createElement('div');
    c.className = 'toast align-items-center text-bg-' + (type || 'primary') + ' border-0 show position-fixed';
    c.style.cssText = 'bottom:1.5rem;right:1.5rem;z-index:9999;min-width:220px;';
    c.setAttribute('role','alert');
    c.innerHTML = '<div class="d-flex"><div class="toast-body">' + escHtml(msg) + '</div>'
        + '<button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.closest(\'.toast\').remove()"></button></div>';
    document.body.appendChild(c);
    setTimeout(function() { if (c.parentNode) c.remove(); }, 3000);
}

// ─── Se já existe objeto aprovado, reflete no estado inicial ─────
(function() {
    <?php if ($contractObject && ($contractObject['status'] ?? '') === 'approved'): ?>
    setTimeout(function() { markApproved(false); }, 50);
    <?php endif; ?>
    // Atualiza UI de aprovação no carregamento
    if (_objectId) {
        setTimeout(function() { updateApprovalUI(); }, 100);
    }
})();
</script>

<!-- jsPDF para download do objeto do contrato -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<!-- Quill Editor JS -->
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
// ─── Inicialização do Quill Editor ──────────────────────────────
var _quillEditor = null;

document.addEventListener('DOMContentLoaded', function() {
    var editorEl = document.getElementById('prompt-editor');
    if (!editorEl) return;

    _quillEditor = new Quill('#prompt-editor', {
        theme: 'snow',
        modules: {
            toolbar: '#prompt-toolbar'
        },
        placeholder: 'Cole ou edite o template aqui. Use {{variável}} para inserir dados do briefing...'
    });

    // Sincroniza o conteúdo do editor com o textarea hidden
    _quillEditor.on('text-change', function() {
        // Envia texto puro para o servidor (a IA não precisa de HTML)
        document.getElementById('prompt-template-field').value = _quillEditor.getText();
    });

    // Inicializa o textarea hidden com o conteúdo inicial (texto puro)
    document.getElementById('prompt-template-field').value = _quillEditor.getText();
});
</script>
<script>
function downloadObjectPdf() {
    var text = (document.getElementById('object-result-wrapper').dataset.objectText || '').trim();
    if (!text) { alert('Nenhum objeto gerado para baixar.'); return; }

    var { jsPDF } = window.jspdf;
    var doc = new jsPDF('p', 'mm', 'a4');

    // Configurações
    var marginLeft = 20;
    var marginTop = 25;
    var pageWidth = 210;
    var maxWidth = pageWidth - marginLeft * 2; // 170mm
    var lineHeight = 7;
    var pageHeight = 297;
    var maxY = pageHeight - 25; // margem inferior

    // Cabeçalho
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(14);
    doc.text('OBJETO DO CONTRATO', pageWidth / 2, 15, { align: 'center' });

    // Linha decorativa
    doc.setDrawColor(58, 59, 78);
    doc.setLineWidth(0.5);
    doc.line(marginLeft, 19, pageWidth - marginLeft, 19);

    // Corpo do texto
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(11);

    var lines = doc.splitTextToSize(text, maxWidth);
    var y = marginTop;

    for (var i = 0; i < lines.length; i++) {
        if (y + lineHeight > maxY) {
            doc.addPage();
            y = 20;
        }
        doc.text(lines[i], marginLeft, y);
        y += lineHeight;
    }

    // Rodapé na última página
    doc.setFontSize(8);
    doc.setTextColor(150);
    doc.text('Brooks Construtora - Documento gerado em ' + new Date().toLocaleDateString('pt-BR'), pageWidth / 2, pageHeight - 10, { align: 'center' });

    doc.save('Objeto_Contrato.pdf');
}

function downloadFullPdf() {
    var { jsPDF } = window.jspdf;
    var doc = new jsPDF('p', 'mm', 'a4');

    var pageWidth = 210;
    var marginLeft = 18;
    var marginRight = 18;
    var maxWidth = pageWidth - marginLeft - marginRight;
    var lineHeight = 6;
    var pageHeight = 297;
    var maxY = pageHeight - 20;
    var y = 20;

    function checkPage() {
        if (y > maxY) { doc.addPage(); y = 20; }
    }

    function addTitle(title) {
        checkPage();
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(13);
        doc.setTextColor(58, 59, 78);
        doc.text(title, pageWidth / 2, y, { align: 'center' });
        y += 4;
        doc.setDrawColor(58, 59, 78);
        doc.setLineWidth(0.5);
        doc.line(marginLeft, y, pageWidth - marginRight, y);
        y += 8;
    }

    function addSection(title) {
        y += 4;
        checkPage();
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(10);
        doc.setTextColor(58, 59, 78);
        doc.text(title, marginLeft, y);
        y += 2;
        doc.setDrawColor(200, 200, 200);
        doc.setLineWidth(0.2);
        doc.line(marginLeft, y, pageWidth - marginRight, y);
        y += 6;
    }

    function addField(label, value) {
        checkPage();
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(9);
        doc.setTextColor(100, 100, 100);
        doc.text(label + ':', marginLeft, y);

        doc.setFont('helvetica', 'normal');
        doc.setFontSize(9);
        doc.setTextColor(30, 30, 30);

        var labelWidth = doc.getTextWidth(label + ': ');
        var valX = marginLeft + labelWidth + 2;
        var valMaxWidth = maxWidth - labelWidth - 4;

        if (!value || value.trim() === '') {
            doc.setTextColor(180, 180, 180);
            doc.text('Não informado', valX, y);
            doc.setTextColor(30, 30, 30);
            y += lineHeight;
        } else if (value.length > 60) {
            y += lineHeight;
            var lines = doc.splitTextToSize(value, maxWidth);
            for (var i = 0; i < lines.length; i++) {
                checkPage();
                doc.text(lines[i], marginLeft + 2, y);
                y += lineHeight - 1;
            }
            y += 2;
        } else {
            doc.text(value, valX, y);
            y += lineHeight;
        }
    }

    function getVal(id) {
        var el = document.getElementById(id);
        return el ? el.value.trim() : '';
    }

    // ─── Título principal ────────────────────────────────────────
    addTitle('BRIEFING & CONTRATO');
    doc.setFontSize(8);
    doc.setTextColor(150);
    doc.text('Gerado em ' + new Date().toLocaleDateString('pt-BR') + ' às ' + new Date().toLocaleTimeString('pt-BR', {hour:'2-digit',minute:'2-digit'}), pageWidth / 2, y - 2, { align: 'center' });
    y += 6;

    // ─── 1. Dados do Cliente ─────────────────────────────────────
    addSection('1. DADOS DO CLIENTE');
    addField('Nome / Razão Social', getVal('client_name'));
    addField('CPF / CNPJ', getVal('client_document'));
    addField('Telefone', getVal('client_phone'));
    addField('E-mail', getVal('client_email'));

    // ─── 2. Informações da Obra ──────────────────────────────────
    addSection('2. INFORMAÇÕES DA OBRA');
    addField('Tipo de Obra', getVal('project_type'));
    addField('Endereço', getVal('project_address'));
    addField('CEP', getVal('project_cep'));
    addField('Cidade', getVal('project_city'));
    addField('Objetivo / Finalidade', getVal('project_goal'));
    addField('Área (m²)', getVal('project_area'));

    // ─── 3. Briefing da Negociação ───────────────────────────────
    addSection('3. BRIEFING DA NEGOCIAÇÃO');
    addField('Preferências do Cliente', getVal('preferences'));
    addField('Prioridades', getVal('priorities'));
    addField('Necessidades Específicas', getVal('needs'));
    addField('Restrições', getVal('restrictions'));
    addField('Resumo do Briefing', getVal('briefing_summary'));
    addField('Detalhes da Negociação', getVal('negotiation_details'));

    // ─── 4. Condições Comerciais ─────────────────────────────────
    addSection('4. CONDIÇÕES COMERCIAIS');
    addField('Valor Total (R$)', getVal('contract_value'));
    addField('Entrada (R$)', getVal('down_payment'));
    addField('Desconto (R$)', getVal('discount_value'));
    addField('Desconto (%)', getVal('discount_percent') ? getVal('discount_percent') + '%' : '');
    addField('Parcelas', getVal('payment_installments'));
    addField('Detalhes do Parcelamento', getVal('payment_details'));
    addField('Data de Início', getVal('start_date'));
    addField('Data de Conclusão', getVal('end_date'));
    addField('Prazo (dias corridos)', getVal('deadline_days'));
    addField('Cláusulas Especiais', getVal('clauses'));

    // ─── 5. Objeto do Contrato ───────────────────────────────────
    var objectText = (document.getElementById('object-result-wrapper')
        ? document.getElementById('object-result-wrapper').dataset.objectText : '') || '';

    if (objectText.trim()) {
        addSection('5. OBJETO DO CONTRATO (GERADO)');
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(9.5);
        doc.setTextColor(30, 30, 30);
        var objLines = doc.splitTextToSize(objectText, maxWidth);
        for (var j = 0; j < objLines.length; j++) {
            checkPage();
            doc.text(objLines[j], marginLeft, y);
            y += lineHeight - 0.5;
        }
    }

    // ─── Rodapé ──────────────────────────────────────────────────
    var totalPages = doc.internal.getNumberOfPages();
    for (var p = 1; p <= totalPages; p++) {
        doc.setPage(p);
        doc.setFontSize(7);
        doc.setTextColor(170);
        doc.text('Brooks Construtora — Briefing & Contrato', marginLeft, pageHeight - 8);
        doc.text('Página ' + p + ' de ' + totalPages, pageWidth - marginRight, pageHeight - 8, { align: 'right' });
    }

    var clientName = getVal('client_name').replace(/[^a-zA-Z0-9]/g, '_') || 'Briefing';
    doc.save('Briefing_' + clientName + '.pdf');
}
</script>

<?php
// Encerra buffer e inclui layout
$content = ob_get_clean();
include ROOT_PATH . '/app/Views/admin/layouts/app.php';
?>
