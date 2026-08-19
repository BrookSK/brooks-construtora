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
</div>

<!-- Stepper visual -->
<div class="stepper-nav mb-4">
    <button class="step-btn active" id="step-btn-1" onclick="goToStep(1)">
        <span class="step-num">1</span>
        <span class="step-label">Cadastro &amp; Briefing</span>
    </button>
    <div class="step-divider"></div>
    <button class="step-btn" id="step-btn-2" onclick="goToStep(2)">
        <span class="step-num">2</span>
        <span class="step-label">Modelo do Objeto</span>
    </button>
    <div class="step-divider"></div>
    <button class="step-btn" id="step-btn-3" onclick="goToStep(3)">
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
                <div class="col-md-4">
                    <label class="form-label">Valor Total (R$)</label>
                    <input type="number" class="form-control bf-field" id="contract_value" name="contract_value"
                           step="0.01" min="0" value="<?= bval($briefing['contract_value'] ?? '') ?>" placeholder="0,00">
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
                    'briefing','valor_contrato','parcelamento',
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
            <textarea id="prompt-template-field" class="form-control font-monospace"
                      rows="14" style="font-size:.82rem;line-height:1.6;"
                      placeholder="Cole ou edite o template aqui. Use {{variável}} para inserir dados do briefing..."><?= bval($defaultTemplate['prompt_template'] ?? '') ?></textarea>
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
        <button type="button" class="btn btn-outline-secondary" onclick="goToStep(2)">
            <i class="bi bi-pencil me-1"></i> Editar Modelo
        </button>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" id="btn-regenerate" onclick="triggerGenerate()">
                <i class="bi bi-arrow-repeat me-1"></i> Gerar Novamente
            </button>
            <button type="button" class="btn btn-success" id="btn-approve-footer" onclick="approveObject()">
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
    cursor: pointer;
    padding: .5rem .75rem;
    border-radius: 6px;
    color: #6c757d;
    transition: all .2s;
    white-space: nowrap;
}
.step-btn:hover { background: rgba(0,0,0,.04); color: var(--color-primary); }
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
}
</style>

<!-- ================================================================
     JAVASCRIPT
================================================================ -->
<script>
// ─── Estado global ───────────────────────────────────────────────
var _currentStep  = 1;
var _objectId     = <?= (int)($contractObject['id'] ?? 0) ?>;

// ─── Stepper ─────────────────────────────────────────────────────
function goToStep(n) {
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
    // Ao chegar na etapa 3, mostra estado atual
    if (n === 3 && _objectId) markApproved(false);
}

// ─── Coleta todos os campos do briefing ─────────────────────────
function collectFormData() {
    var fd = new FormData();
    fd.append('project_id',  _projectId);
    fd.append('briefing_id', _briefingId);
    document.querySelectorAll('.bf-field').forEach(function(el) {
        fd.append(el.name, el.value);
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
        document.getElementById('prompt-template-field').value = opt.dataset.prompt || '';
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
        + '<button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyObjectText()" title="Copiar texto">'
        + '<i class="bi bi-clipboard"></i></button></div>'
        + '<div class="card-body"><div id="object-text-display">' + escHtml(text) + '</div></div>'
        + '</div>';

    wrapper.dataset.objectText = text;
    wrapper.dataset.objectId   = objectId;
}

function copyObjectText() {
    var text = document.getElementById('object-result-wrapper').dataset.objectText || '';
    navigator.clipboard.writeText(text).then(function() { showToast('Texto copiado!', 'success'); });
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
    var badge = document.getElementById('obj-status-badge');
    if (badge) { badge.textContent = 'Aprovado'; badge.className = 'badge bg-success ms-2'; }
    var btn = document.getElementById('btn-approve-footer');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Aprovado'; }
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
        if (final) { baseValue = (baseValue + (baseValue ? ' ' : '') + final).trim(); }
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
        if (targetEl) { targetEl.value = baseValue; }
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
    if (!confirm('Excluir o briefing de "' + name + '"?\nTodos os dados vinculados serão removidos permanentemente.')) return;
    document.getElementById('delete-id').value = id;
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
    // Se já há um objeto, habilita stepper para ir direto à etapa 3
    if (_objectId) {
        var b3 = document.getElementById('step-btn-3');
        if (b3) b3.classList.remove('disabled');
    }
})();
</script>

<?php
// Encerra buffer e inclui layout
$content = ob_get_clean();
include ROOT_PATH . '/app/Views/admin/layouts/app.php';
?>
