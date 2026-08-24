<?php $pageTitle = 'Briefing & Contratos'; $currentPage = 'briefing'; ob_start(); ?>
<?php
$mode=$mode??'list'; $project=$project??null; $briefing=$briefing??null;
$contractObject=$contractObject??null; $templates=$templates??[]; $defaultTemplate=$defaultTemplate??null;
$projects=$projects??[]; $contractors=$contractors??[]; $selectedContractor=$selectedContractor??null;
$isEdit=$mode==='edit'; $isCreate=$mode==='create'; $isList=$mode==='list'; $isView=$mode==='view';

function bval(?string $v):string{return htmlspecialchars($v??'',ENT_QUOTES);}
function fmtDoc(?string $v):string{$d=preg_replace('/\D/','',$v??'');if(strlen($d)===11)return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/','$1.$2.$3-$4',$d);if(strlen($d)===14)return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/','$1.$2.$3/$4-$5',$d);return bval($v);}
function fmtPhone(?string $v):string{$d=preg_replace('/\D/','',$v??'');if(strlen($d)===11)return preg_replace('/(\d{2})(\d{5})(\d{4})/','($1) $2-$3',$d);if(strlen($d)===10)return preg_replace('/(\d{2})(\d{4})(\d{4})/','($1) $2-$3',$d);return bval($v);}
function fmtCep(?string $v):string{$d=preg_replace('/\D/','',$v??'');if(strlen($d)===8)return preg_replace('/(\d{5})(\d{3})/','$1-$2',$d);return bval($v);}
function micBtn(string $b,string $t):string{return '<button type="button" class="mic-btn" id="'.$b.'" onclick="toggleSpeech(\''.$b.'\',\''.$t.'\')" title="Ditado por voz"><i class="bi bi-mic"></i></button>';}

$projectId=(int)($project['id']??0); $briefingId=(int)($briefing['id']??0);
$templateId=(int)($defaultTemplate['id']??($templates[0]['id']??0));
$contractorId=(int)($briefing['contractor_company_id']??0);
?>

<?php if(!empty($flash)):?><div class="alert alert-<?=$flash['type']==='error'?'danger':htmlspecialchars($flash['type'])?> alert-dismissible fade show"><?=htmlspecialchars($flash['message']??'')?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif;?>

<?php if($isList):?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div><h5 class="mb-0 fw-semibold">Projetos e Briefings</h5><p class="text-muted small mb-0">Gerencie clientes, briefings e gere contratos com IA.</p></div>
    <a href="/admin/briefing/create" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Novo Briefing</a>
</div>
<?php if(empty($projects)):?><div class="card p-5 text-center text-muted"><i class="bi bi-file-earmark-text" style="font-size:3rem;opacity:.3;"></i><p class="mt-3 mb-0">Nenhum briefing cadastrado.</p><a href="/admin/briefing/create" class="btn btn-primary mt-3">Criar primeiro</a></div>
<?php else:?><div class="card"><div class="table-responsive"><table class="table table-hover mb-0 align-middle"><thead class="table-light"><tr><th>Cliente</th><th>Tipo</th><th class="d-none d-md-table-cell">Cidade</th><th class="d-none d-md-table-cell">Valor</th><th class="d-none d-sm-table-cell">Obj</th><th class="text-end">Ações</th></tr></thead><tbody>
<?php foreach($projects as $p):?><tr><td><div class="fw-medium"><?=bval($p['client_name'])?></div><div class="text-muted small"><?=bval($p['client_email'])?></div></td><td><?=bval($p['project_type'])?></td><td class="d-none d-md-table-cell"><?=bval($p['project_city'])?></td><td class="d-none d-md-table-cell"><?=!empty($p['contract_value'])?'R$ '.number_format((float)$p['contract_value'],2,',','.'):'<span class="text-muted">—</span>'?></td><td class="d-none d-sm-table-cell"><span class="badge bg-<?=(int)($p['objects_count']??0)>0?'success':'secondary'?>"><?=(int)($p['objects_count']??0)?></span></td><td class="text-end text-nowrap"><a href="/admin/briefing/show/<?=$p['id']?>" class="btn btn-sm btn-outline-secondary" title="Visualizar"><i class="bi bi-eye"></i></a> <button type="button" class="btn btn-sm btn-outline-success" title="Compartilhar via WhatsApp" onclick="shareWhatsapp(<?=$p['id']?>)"><i class="bi bi-whatsapp"></i></button> <a href="/admin/briefing/edit/<?=$p['id']?>" class="btn btn-sm btn-outline-primary" title="Editar"><i class="bi bi-pencil"></i></a> <button type="button" class="btn btn-sm btn-outline-danger" title="Excluir" onclick="confirmDelete(<?=$p['id']?>,'<?=bval($p['client_name'])?>')"><i class="bi bi-trash"></i></button></td></tr><?php endforeach;?>
</tbody></table></div></div>
<form id="delete-form" method="POST" action="/admin/briefing/delete" style="display:none"><input type="hidden" name="id" id="delete-id"></form>
<script>
function confirmDelete(id,n){if(!confirm('Excluir "'+n+'"?'))return;document.getElementById('delete-id').value=id;document.getElementById('delete-form').submit();}
function shareWhatsapp(id){
    fetch('/admin/briefing/whatsapp-text/'+id).then(r=>r.json()).then(d=>{
        if(d.success&&d.text){
            var url='https://api.whatsapp.com/send?text='+encodeURIComponent(d.text);
            window.open(url,'_blank');
        }else{
            alert(d.error||'Não foi possível montar o conteúdo do briefing.');
        }
    }).catch(()=>alert('Falha ao gerar o compartilhamento.'));
}
</script>
<?php endif;?>

<?php elseif($isView):?>
<?php
// Helper de exibição somente-leitura — mostra "—" quando vazio, nunca altera o valor
function vrow(string $label, $value): string {
    $v = trim((string)($value ?? ''));
    $disp = $v === '' ? '<span class="text-muted">—</span>' : nl2br(htmlspecialchars($v, ENT_QUOTES));
    return '<div class="col-md-6 mb-2"><div class="small text-muted">'.htmlspecialchars($label).'</div><div class="fw-medium">'.$disp.'</div></div>';
}
?>
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <a href="/admin/briefing" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
    <h5 class="mb-0 fw-semibold">Visualizar — <?=bval($project['client_name']??'')?></h5>
    <div class="ms-auto d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-success" onclick="shareWhatsapp(<?=$projectId?>)"><i class="bi bi-whatsapp me-1"></i> WhatsApp</button>
        <a href="/admin/briefing/edit/<?=$projectId?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil me-1"></i> Editar</a>
    </div>
</div>

<div class="alert alert-info d-flex align-items-center gap-2 py-2"><i class="bi bi-eye"></i> Modo de visualização — somente consulta.</div>

<?php if($selectedContractor):?>
<div class="card mb-3"><div class="card-header"><h6 class="mb-0"><i class="bi bi-building-check me-2"></i>Empresa Contratada</h6></div><div class="card-body"><div class="row g-2">
<?=vrow('Razão Social',$selectedContractor['company_name']??'')?>
<?=vrow('Nome Fantasia',$selectedContractor['trade_name']??'')?>
<?=vrow('CNPJ',fmtDoc($selectedContractor['cnpj']??''))?>
<?=vrow('Endereço',trim(($selectedContractor['address']??'').' '.($selectedContractor['address_number']??'').' '.($selectedContractor['complement']??'')))?>
<?=vrow('Bairro',$selectedContractor['neighborhood']??'')?>
<?=vrow('Cidade/UF',trim(($selectedContractor['city']??'').(!empty($selectedContractor['state'])?'/'.$selectedContractor['state']:'')))?>
<?=vrow('CEP',fmtCep($selectedContractor['cep']??''))?>
<?=vrow('Telefone',fmtPhone($selectedContractor['phone']??''))?>
<?=vrow('E-mail',$selectedContractor['email']??'')?>
<?=vrow('Representante',$selectedContractor['representative_name']??'')?>
<?=vrow('Cargo',$selectedContractor['representative_role']??'')?>
</div></div></div>
<?php endif;?>

<div class="card mb-3"><div class="card-header"><h6 class="mb-0"><i class="bi bi-person me-2"></i>Dados do Contratante</h6></div><div class="card-body"><div class="row g-2">
<?=vrow('Nome/Razão Social',$project['client_name']??'')?>
<?=vrow('CPF/CNPJ',fmtDoc($project['client_document']??''))?>
<?=vrow('Telefone',fmtPhone($project['client_phone']??''))?>
<?=vrow('E-mail',$project['client_email']??'')?>
<?=vrow('Nacionalidade',$project['client_nationality']??'')?>
<?=vrow('Estado Civil',$project['client_marital_status']??'')?>
</div></div></div>

<div class="card mb-3"><div class="card-header"><h6 class="mb-0"><i class="bi bi-buildings me-2"></i>Informações da Obra</h6></div><div class="card-body"><div class="row g-2">
<?=vrow('Tipo de Obra',$project['project_type']??'')?>
<?=vrow('Área (m²)',$project['project_area']??'')?>
<?=vrow('Nº Projeto',$briefing['project_number']??'')?>
<?=vrow('Endereço',$project['project_address']??'')?>
<?=vrow('Número',$project['project_address_number']??'')?>
<?=vrow('Complemento',$project['project_complement']??'')?>
<?=vrow('Bairro',$project['project_neighborhood']??'')?>
<?=vrow('Cidade',$project['project_city']??'')?>
<?=vrow('UF',$project['project_state']??'')?>
<?=vrow('CEP',fmtCep($project['project_cep']??''))?>
<?=vrow('Objetivo',$project['project_goal']??'')?>
</div></div></div>

<?php if(!empty($briefing)):?>
<div class="card mb-3"><div class="card-header"><h6 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Briefing da Negociação</h6></div><div class="card-body"><div class="row g-2">
<?=vrow('Preferências',$briefing['preferences']??'')?>
<?=vrow('Prioridades',$briefing['priorities']??'')?>
<?=vrow('Necessidades',$briefing['needs']??'')?>
<?=vrow('Restrições',$briefing['restrictions']??'')?>
<?=vrow('Resumo',$briefing['briefing_summary']??'')?>
<?=vrow('Detalhes Negociação',$briefing['negotiation_details']??'')?>
</div></div></div>

<div class="card mb-3"><div class="card-header"><h6 class="mb-0"><i class="bi bi-currency-dollar me-2"></i>Condições Comerciais</h6></div><div class="card-body"><div class="row g-2">
<?=vrow('Valor Total (R$)',!empty($briefing['contract_value'])?number_format((float)$briefing['contract_value'],2,',','.'):'')?>
<?=vrow('Desconto (R$)',!empty($briefing['discount_value'])?number_format((float)$briefing['discount_value'],2,',','.'):'')?>
<?=vrow('Desconto (%)',$briefing['discount_percent']??'')?>
<?=vrow('Forma de Pagamento',$briefing['payment_method']??'')?>
<?=vrow('Parcelas',$briefing['payment_installments']??'')?>
<?=vrow('Detalhes Parcelamento',$briefing['payment_details']??'')?>
<?=vrow('Início',$briefing['start_date']??'')?>
<?=vrow('Conclusão',$briefing['end_date']??'')?>
<?=vrow('Prazo (dias)',$briefing['deadline_days']??'')?>
<?=vrow('Responsável',$briefing['responsible_name']??'')?>
<?=vrow('Cargo',$briefing['responsible_role']??'')?>
<?=vrow('Cláusulas',$briefing['clauses']??'')?>
</div></div></div>
<?php endif;?>

<?php if($contractObject):?>
<div class="card mb-3"><div class="card-header"><h6 class="mb-0"><i class="bi bi-file-earmark-check me-2"></i>Objeto Gerado</h6></div><div class="card-body"><div style="white-space:pre-wrap;line-height:1.8;font-size:.95rem;"><?=htmlspecialchars($contractObject['generated_text']??'',ENT_QUOTES)?></div></div></div>
<?php endif;?>

<script>
function shareWhatsapp(id){
    fetch('/admin/briefing/whatsapp-text/'+id).then(r=>r.json()).then(d=>{
        if(d.success&&d.text){window.open('https://api.whatsapp.com/send?text='+encodeURIComponent(d.text),'_blank');}
        else{alert(d.error||'Não foi possível montar o conteúdo.');}
    }).catch(()=>alert('Falha ao gerar o compartilhamento.'));
}
</script>

<?php else:?>
<script>var _projectId=<?=$projectId?>,_briefingId=<?=$briefingId?>,_templateId=<?=$templateId?>;</script>
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <a href="/admin/briefing" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
    <h5 class="mb-0 fw-semibold"><?=$isCreate?'Novo Briefing':'Editar — '.bval($project['client_name']??'')?></h5>
    <span id="save-indicator" class="ms-auto badge bg-secondary d-none">Salvando…</span>
</div>

<!-- Stepper -->
<div class="stepper-nav mb-4">
    <button class="step-btn active" id="step-btn-1" onclick="goToStep(1)"><span class="step-num">1</span><span class="step-label">Cadastro &amp; Briefing</span></button><div class="step-divider"></div>
    <button class="step-btn" id="step-btn-2" onclick="goToStep(2)"><span class="step-num">2</span><span class="step-label">Modelo do Objeto</span></button><div class="step-divider"></div>
    <button class="step-btn" id="step-btn-3" onclick="goToStep(3)"><span class="step-num">3</span><span class="step-label">Objeto Gerado</span></button>
</div>

<!-- ═══ ETAPA 1 ═══ -->
<div class="step-panel" id="step-1">

<!-- Importar PDF -->
<div class="card mb-4 border-primary border-opacity-25"><div class="card-body py-3"><div class="d-flex align-items-center gap-3 flex-wrap"><div class="flex-grow-1"><h6 class="mb-1"><i class="bi bi-file-earmark-pdf me-2 text-danger"></i>Importar Briefing por PDF</h6><p class="text-muted small mb-0">A IA preencherá os campos. Você poderá revisar antes de salvar.</p></div><div class="d-flex gap-2"><input type="file" id="pdf-input" accept=".pdf" class="form-control form-control-sm" style="max-width:220px;"><button type="button" id="btn-import-pdf" class="btn btn-sm btn-outline-primary" onclick="importPdf()"><i class="bi bi-magic me-1"></i> Processar</button></div></div><div id="pdf-status" class="mt-2 small" style="display:none;"></div></div></div>

<!-- Empresa Contratada -->
<div class="card mb-4"><div class="card-header"><h6 class="mb-0"><i class="bi bi-building-check me-2"></i>Empresa Contratada</h6></div><div class="card-body"><div class="row g-3">
<div class="col-md-8"><label class="form-label">Selecionar empresa</label><select class="form-select bf-field" id="contractor_company_id" name="contractor_company_id" onchange="loadContractor(this.value)"><option value="">— Selecione ou cadastre —</option><?php foreach($contractors as $c):?><option value="<?=(int)$c['id']?>" <?=$contractorId===(int)$c['id']?'selected':''?> data-json="<?=bval(json_encode($c))?>"><?=bval($c['company_name'])?><?=!empty($c['cnpj'])?' — '.fmtDoc($c['cnpj']):''?></option><?php endforeach;?></select></div>
<div class="col-md-4 d-flex align-items-end"><button type="button" class="btn btn-outline-secondary w-100" data-bs-toggle="modal" data-bs-target="#modalContractor"><i class="bi bi-plus-lg me-1"></i> Nova empresa</button></div>
<div class="col-12" id="contractor-preview"><?php if($selectedContractor):?><div class="bg-light rounded p-3 small"><strong><?=bval($selectedContractor['company_name'])?></strong><?=!empty($selectedContractor['cnpj'])?' — CNPJ: '.fmtDoc($selectedContractor['cnpj']):''?><br><?=bval($selectedContractor['address']??'')?><?=!empty($selectedContractor['address_number'])?', '.bval($selectedContractor['address_number']):''?> <?=bval($selectedContractor['neighborhood']??'')?>, <?=bval($selectedContractor['city']??'')?>/<?=bval($selectedContractor['state']??'')?></div><?php endif;?></div>
</div></div></div>

<!-- Dados do Contratante -->
<div class="card mb-4"><div class="card-header"><h6 class="mb-0"><i class="bi bi-person me-2"></i>Dados do Contratante</h6></div><div class="card-body"><div class="row g-3">
<div class="col-md-6"><label class="form-label">Nome/Razão Social <span class="text-danger">*</span></label><input type="text" class="form-control bf-field" id="client_name" name="client_name" value="<?=bval($project['client_name']??'')?>" required maxlength="255"></div>
<div class="col-md-3"><label class="form-label">CPF/CNPJ</label><input type="text" class="form-control bf-field" id="client_document" name="client_document" value="<?=fmtDoc($project['client_document']??'')?>" maxlength="18" inputmode="numeric"></div>
<div class="col-md-3"><label class="form-label">Telefone</label><input type="text" class="form-control bf-field" id="client_phone" name="client_phone" value="<?=fmtPhone($project['client_phone']??'')?>" maxlength="15" inputmode="numeric"></div>
<div class="col-md-6"><label class="form-label">E-mail</label><input type="email" class="form-control bf-field" id="client_email" name="client_email" value="<?=bval($project['client_email']??'')?>"><div class="invalid-feedback">E-mail inválido.</div></div>
<div class="col-md-3"><label class="form-label">Nacionalidade</label><input type="text" class="form-control bf-field" id="client_nationality" name="client_nationality" value="<?=bval($project['client_nationality']??'')?>" placeholder="Brasileira"></div>
<div class="col-md-3"><label class="form-label">Estado Civil</label><select class="form-select bf-field" id="client_marital_status" name="client_marital_status"><option value="">— Selecione —</option><?php foreach(['Solteiro(a)','Casado(a)','Divorciado(a)','Viúvo(a)','União Estável','Separado(a)'] as $es):?><option value="<?=$es?>" <?=($project['client_marital_status']??'')===$es?'selected':''?>><?=$es?></option><?php endforeach;?></select></div>
</div></div></div>

<!-- Obra -->
<div class="card mb-4"><div class="card-header"><h6 class="mb-0"><i class="bi bi-buildings me-2"></i>Informações da Obra</h6></div><div class="card-body"><div class="row g-3">
<div class="col-md-3"><label class="form-label">Tipo</label><select class="form-select bf-field" id="project_type" name="project_type"><option value="">— Selecione —</option><?php foreach(['Residencial','Comercial','Industrial','Reforma','Ampliação','Retrofit','Paisagismo','Outro'] as $t):?><option value="<?=$t?>" <?=($project['project_type']??'')===$t?'selected':''?>><?=$t?></option><?php endforeach;?></select></div>
<div class="col-md-2"><label class="form-label">Área m²</label><input type="number" class="form-control bf-field" id="project_area" name="project_area" step="0.01" value="<?=bval($project['project_area']??'')?>"></div>
<div class="col-md-3"><label class="form-label">Nº Projeto</label><input type="text" class="form-control bf-field" id="project_number" name="project_number" value="<?=bval($briefing['project_number']??'')?>"></div>
<div class="col-md-2"><label class="form-label">CEP</label><input type="text" class="form-control bf-field" id="project_cep" name="project_cep" maxlength="9" value="<?=fmtCep($project['project_cep']??'')?>" inputmode="numeric"><div class="invalid-feedback" id="cep-feedback"></div></div>
<div class="col-md-2"><label class="form-label">UF</label><input type="text" class="form-control bf-field" id="project_state" name="project_state" maxlength="2" value="<?=bval($project['project_state']??'')?>" style="text-transform:uppercase;"></div>
<div class="col-md-5"><label class="form-label">Endereço <?=micBtn('mic_addr','project_address')?></label><input type="text" class="form-control bf-field" id="project_address" name="project_address" value="<?=bval($project['project_address']??'')?>"></div>
<div class="col-md-2"><label class="form-label">Número</label><input type="text" class="form-control bf-field" id="project_address_number" name="project_address_number" value="<?=bval($project['project_address_number']??'')?>"></div>
<div class="col-md-2"><label class="form-label">Complemento</label><input type="text" class="form-control bf-field" id="project_complement" name="project_complement" value="<?=bval($project['project_complement']??'')?>"></div>
<div class="col-md-3"><label class="form-label">Bairro</label><input type="text" class="form-control bf-field" id="project_neighborhood" name="project_neighborhood" value="<?=bval($project['project_neighborhood']??'')?>"></div>
<div class="col-md-4"><label class="form-label">Cidade <?=micBtn('mic_city','project_city')?></label><input type="text" class="form-control bf-field" id="project_city" name="project_city" value="<?=bval($project['project_city']??'')?>"></div>
<div class="col-12"><label class="form-label">Objetivo <?=micBtn('mic_goal','project_goal')?></label><textarea class="form-control bf-field" id="project_goal" name="project_goal" rows="3"><?=bval($project['project_goal']??'')?></textarea></div>
</div></div></div>

<!-- Briefing -->
<div class="card mb-4"><div class="card-header"><h6 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Briefing da Negociação</h6></div><div class="card-body"><div class="row g-3">
<div class="col-md-6"><label class="form-label">Preferências <?=micBtn('mic_pref','preferences')?></label><textarea class="form-control bf-field" id="preferences" name="preferences" rows="3"><?=bval($briefing['preferences']??'')?></textarea></div>
<div class="col-md-6"><label class="form-label">Prioridades <?=micBtn('mic_prio','priorities')?></label><textarea class="form-control bf-field" id="priorities" name="priorities" rows="3"><?=bval($briefing['priorities']??'')?></textarea></div>
<div class="col-md-6"><label class="form-label">Necessidades <?=micBtn('mic_needs','needs')?></label><textarea class="form-control bf-field" id="needs" name="needs" rows="3"><?=bval($briefing['needs']??'')?></textarea></div>
<div class="col-md-6"><label class="form-label">Restrições <?=micBtn('mic_rest','restrictions')?></label><textarea class="form-control bf-field" id="restrictions" name="restrictions" rows="3"><?=bval($briefing['restrictions']??'')?></textarea></div>
<div class="col-md-6"><label class="form-label">Resumo <?=micBtn('mic_summ','briefing_summary')?></label><textarea class="form-control bf-field" id="briefing_summary" name="briefing_summary" rows="4"><?=bval($briefing['briefing_summary']??'')?></textarea></div>
<div class="col-md-6"><label class="form-label">Detalhes Negociação <?=micBtn('mic_neg','negotiation_details')?></label><textarea class="form-control bf-field" id="negotiation_details" name="negotiation_details" rows="4"><?=bval($briefing['negotiation_details']??'')?></textarea></div>
</div></div></div>

<!-- Condições Comerciais -->
<div class="card mb-4"><div class="card-header"><h6 class="mb-0"><i class="bi bi-currency-dollar me-2"></i>Condições Comerciais</h6></div><div class="card-body"><div class="row g-3">
<div class="col-md-3"><label class="form-label">Valor Total (R$)</label><input type="number" class="form-control bf-field" id="contract_value" name="contract_value" step="0.01" min="0" value="<?=bval($briefing['contract_value']??'')?>"></div>
<div class="col-md-3"><label class="form-label">Desconto (R$)</label><input type="number" class="form-control bf-field" id="discount_value" name="discount_value" step="0.01" min="0" value="<?=bval($briefing['discount_value']??'')?>"></div>
<div class="col-md-2"><label class="form-label">Desconto (%)</label><input type="number" class="form-control bf-field" id="discount_percent" name="discount_percent" step="0.01" min="0" max="100" value="<?=bval($briefing['discount_percent']??'')?>"></div>
<div class="col-md-4"><label class="form-label">Forma de Pagamento</label><input type="text" class="form-control bf-field" id="payment_method" name="payment_method" value="<?=bval($briefing['payment_method']??'')?>"></div>
<div class="col-md-2"><label class="form-label">Parcelas</label><input type="number" class="form-control bf-field" id="payment_installments" name="payment_installments" min="1" value="<?=bval($briefing['payment_installments']??'')?>"></div>
<div class="col-md-7"><label class="form-label">Detalhes Parcelamento</label><input type="text" class="form-control bf-field" id="payment_details" name="payment_details" value="<?=bval($briefing['payment_details']??'')?>"></div>
<div class="col-md-3"><label class="form-label">Início</label><input type="date" class="form-control bf-field" id="start_date" name="start_date" value="<?=bval($briefing['start_date']??'')?>"></div>
<div class="col-md-3"><label class="form-label">Conclusão</label><input type="date" class="form-control bf-field" id="end_date" name="end_date" value="<?=bval($briefing['end_date']??'')?>"></div>
<div class="col-md-2"><label class="form-label">Prazo (dias)</label><input type="number" class="form-control bf-field" id="deadline_days" name="deadline_days" min="1" value="<?=bval($briefing['deadline_days']??'')?>"></div>
<div class="col-md-4"><label class="form-label">Responsável</label><input type="text" class="form-control bf-field" id="responsible_name" name="responsible_name" value="<?=bval($briefing['responsible_name']??'')?>"></div>
<div class="col-md-3"><label class="form-label">Cargo</label><input type="text" class="form-control bf-field" id="responsible_role" name="responsible_role" value="<?=bval($briefing['responsible_role']??'')?>"></div>
<div class="col-12"><label class="form-label">Cláusulas <?=micBtn('mic_cl','clauses')?></label><textarea class="form-control bf-field" id="clauses" name="clauses" rows="4"><?=bval($briefing['clauses']??'')?></textarea></div>
</div></div></div>

<div class="step-footer"><a href="/admin/briefing" class="btn btn-outline-secondary">Cancelar</a><div class="d-flex gap-2"><button type="button" class="btn btn-outline-primary" onclick="saveDraft()"><i class="bi bi-floppy me-1"></i> Salvar Rascunho</button><button type="button" class="btn btn-primary" onclick="saveAndContinue()">Salvar e Continuar <i class="bi bi-arrow-right ms-1"></i></button></div></div>
</div><!-- /step-1 -->

<!-- ═══ ETAPA 2 ═══ -->
<div class="step-panel d-none" id="step-2">
<div class="card mb-4"><div class="card-header"><h6 class="mb-0"><i class="bi bi-braces me-2"></i>Variáveis Disponíveis</h6></div><div class="card-body">
<p class="small text-muted mb-2"><strong>Contratante / Obra:</strong></p><div class="d-flex flex-wrap gap-1 mb-3"><?php foreach(['cliente_nome','cliente_documento','cliente_telefone','cliente_email','cliente_nacionalidade','cliente_estado_civil','tipo_obra','endereco_obra','numero_obra','complemento_obra','bairro_obra','cidade_obra','estado_obra','cep_obra','objetivo','area_m2','numero_projeto'] as $var):?><button type="button" class="btn btn-sm btn-outline-secondary font-monospace var-chip" data-var="{{<?=$var?>}}" onclick="copyVar(this)" style="font-size:.72rem">{{<?=$var?>}}</button><?php endforeach;?></div>
<p class="small text-muted mb-2"><strong>Briefing:</strong></p><div class="d-flex flex-wrap gap-1 mb-3"><?php foreach(['preferencias','prioridades','necessidades','restricoes','resumo_briefing','detalhes_negociacao','briefing'] as $var):?><button type="button" class="btn btn-sm btn-outline-secondary font-monospace var-chip" data-var="{{<?=$var?>}}" onclick="copyVar(this)" style="font-size:.72rem">{{<?=$var?>}}</button><?php endforeach;?></div>
<p class="small text-muted mb-2"><strong>Condições Comerciais:</strong></p><div class="d-flex flex-wrap gap-1 mb-3"><?php foreach(['valor_contrato','desconto_valor','desconto_percentual','forma_pagamento','parcelas','detalhes_parcelamento','data_inicio','data_conclusao','prazo_dias','clausulas','responsavel_nome','responsavel_cargo'] as $var):?><button type="button" class="btn btn-sm btn-outline-secondary font-monospace var-chip" data-var="{{<?=$var?>}}" onclick="copyVar(this)" style="font-size:.72rem">{{<?=$var?>}}</button><?php endforeach;?></div>
<p class="small text-muted mb-2"><strong>Empresa Contratada:</strong></p><div class="d-flex flex-wrap gap-1"><?php foreach(['contratada_razao_social','contratada_nome_fantasia','contratada_cnpj','contratada_endereco','contratada_numero','contratada_complemento','contratada_bairro','contratada_cidade','contratada_estado','contratada_cep','contratada_telefone','contratada_email','contratada_representante','contratada_representante_cargo'] as $var):?><button type="button" class="btn btn-sm btn-outline-secondary font-monospace var-chip" data-var="{{<?=$var?>}}" onclick="copyVar(this)" style="font-size:.72rem">{{<?=$var?>}}</button><?php endforeach;?></div>
</div></div>

<?php if(!empty($templates)):?><div class="card mb-4"><div class="card-header"><h6 class="mb-0"><i class="bi bi-layout-text-window me-2"></i>Modelo</h6></div><div class="card-body"><div class="row g-3 align-items-end"><div class="col-md-8"><select class="form-select" id="template-select" onchange="loadTemplate(this.value)"><?php foreach($templates as $tpl):?><option value="<?=(int)$tpl['id']?>" data-prompt="<?=bval($tpl['prompt_template'])?>" <?=($tpl['is_default']??0)?'selected':''?>><?=bval($tpl['name'])?><?=$tpl['is_default']?' (padrão)':''?></option><?php endforeach;?></select></div><div class="col-md-4"><button type="button" class="btn btn-outline-secondary w-100" data-bs-toggle="modal" data-bs-target="#modalTemplate"><i class="bi bi-plus-lg me-1"></i> Novo modelo</button></div></div></div></div><?php endif;?>

<div class="card mb-4"><div class="card-header d-flex justify-content-between"><h6 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Template <?=micBtn('mic_tpl','prompt-template-field')?></h6><button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetTemplate()"><i class="bi bi-arrow-counterclockwise"></i></button></div><div class="card-body"><textarea id="prompt-template-field" class="form-control font-monospace" rows="14" style="font-size:.82rem;line-height:1.6;"><?=bval($defaultTemplate['prompt_template']??'')?></textarea></div></div>

<div class="step-footer"><button type="button" class="btn btn-outline-secondary" onclick="goToStep(1)"><i class="bi bi-arrow-left me-1"></i> Voltar</button><div class="d-flex gap-2"><button type="button" class="btn btn-outline-primary" onclick="saveModelOnly()"><i class="bi bi-floppy me-1"></i> Salvar Modelo</button><button type="button" class="btn btn-primary btn-lg" id="btn-generate" onclick="triggerGenerate()"><i class="bi bi-stars me-2"></i> Gerar Contrato</button></div></div>
</div><!-- /step-2 -->

<!-- ═══ ETAPA 3 ═══ -->
<div class="step-panel d-none" id="step-3">
<div id="gen-loading" class="card p-5 text-center d-none"><div class="spinner-border text-primary mx-auto mb-3" style="width:3rem;height:3rem;"></div><p class="fw-semibold mb-1">Gerando com IA...</p></div>
<div id="object-result-wrapper"><?php if($contractObject):?><?php include __DIR__.'/_object_result.php';?><?php else:?><div class="card p-5 text-center text-muted" id="gen-empty-state"><i class="bi bi-file-earmark-plus" style="font-size:3rem;opacity:.3;"></i><p class="mt-3 mb-0">Nenhum objeto gerado.</p></div><?php endif;?></div>
<div class="step-footer mt-4"><button type="button" class="btn btn-outline-secondary" onclick="goToStep(2)"><i class="bi bi-pencil me-1"></i> Editar Modelo</button><div class="d-flex gap-2"><button type="button" class="btn btn-outline-primary" id="btn-regenerate" onclick="triggerGenerate()"><i class="bi bi-arrow-repeat me-1"></i> Gerar Novamente</button><button type="button" class="btn btn-success" id="btn-approve-footer" onclick="approveObject()"><i class="bi bi-check2-circle me-1"></i> Aprovar</button></div></div>
</div><!-- /step-3 -->
<?php endif;?>

<!-- Modal Contratada --><div class="modal fade" id="modalContractor" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="bi bi-building-add me-2"></i>Nova Empresa Contratada</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-md-6"><label class="form-label">Razão Social *</label><input type="text" class="form-control" id="ct-company_name"></div><div class="col-md-6"><label class="form-label">Nome Fantasia</label><input type="text" class="form-control" id="ct-trade_name"></div><div class="col-md-4"><label class="form-label">CNPJ</label><input type="text" class="form-control" id="ct-cnpj" maxlength="18" inputmode="numeric"></div><div class="col-md-5"><label class="form-label">Endereço</label><input type="text" class="form-control" id="ct-address"></div><div class="col-md-3"><label class="form-label">Número</label><input type="text" class="form-control" id="ct-address_number"></div><div class="col-md-3"><label class="form-label">Complemento</label><input type="text" class="form-control" id="ct-complement"></div><div class="col-md-3"><label class="form-label">Bairro</label><input type="text" class="form-control" id="ct-neighborhood"></div><div class="col-md-3"><label class="form-label">Cidade</label><input type="text" class="form-control" id="ct-city"></div><div class="col-md-1"><label class="form-label">UF</label><input type="text" class="form-control" id="ct-state" maxlength="2"></div><div class="col-md-2"><label class="form-label">CEP</label><input type="text" class="form-control" id="ct-cep" maxlength="9" inputmode="numeric"></div><div class="col-md-4"><label class="form-label">Telefone</label><input type="text" class="form-control" id="ct-phone" maxlength="15" inputmode="numeric"></div><div class="col-md-4"><label class="form-label">E-mail</label><input type="email" class="form-control" id="ct-email"></div><div class="col-md-4"><label class="form-label">Representante</label><input type="text" class="form-control" id="ct-representative_name"></div><div class="col-md-4"><label class="form-label">Cargo</label><input type="text" class="form-control" id="ct-representative_role"></div></div><div id="ct-feedback" class="small mt-2"></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" onclick="saveContractor()"><i class="bi bi-check-lg me-1"></i> Salvar</button></div></div></div></div>
<!-- Modal Template --><div class="modal fade" id="modalTemplate" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Novo Modelo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Nome *</label><input type="text" class="form-control" id="tpl-name"></div><div class="mb-3"><label class="form-label">Descrição</label><input type="text" class="form-control" id="tpl-desc"></div><div class="mb-3"><label class="form-label">Template *</label><textarea class="form-control font-monospace" id="tpl-prompt" rows="10" style="font-size:.82rem;"></textarea></div><div id="tpl-feedback" class="small mt-1"></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="button" class="btn btn-primary" onclick="saveTemplate()"><i class="bi bi-check-lg me-1"></i> Salvar</button></div></div></div></div>

<style>.stepper-nav{display:flex;align-items:center;background:#fff;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.06);padding:.5rem 1rem;overflow-x:auto}.step-btn{display:flex;align-items:center;gap:.5rem;background:none;border:none;cursor:pointer;padding:.5rem .75rem;border-radius:6px;color:#6c757d;transition:all .2s;white-space:nowrap}.step-btn:hover{background:rgba(0,0,0,.04);color:var(--color-primary)}.step-btn.active{color:var(--color-primary);font-weight:600}.step-btn.done{color:#28a745}.step-num{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;background:#e9ecef;font-size:.8rem;font-weight:700}.step-btn.active .step-num{background:var(--color-primary);color:#fff}.step-btn.done .step-num{background:#28a745;color:#fff}.step-divider{flex:1;height:2px;background:#dee2e6;min-width:20px;max-width:60px}.step-label{font-size:.875rem}.step-footer{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.75rem;padding:1rem 0 2rem;border-top:1px solid #e9ecef;margin-top:.5rem}.mic-btn{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:50%;border:1px solid #dee2e6;background:#fff;color:#6c757d;cursor:pointer;vertical-align:middle;margin-left:4px;transition:all .15s;font-size:.75rem}.mic-btn:hover{background:var(--color-primary);color:#fff;border-color:var(--color-primary)}.mic-btn.active{background:var(--color-accent);color:#fff;border-color:var(--color-accent);animation:pulse-mic .7s infinite}@keyframes pulse-mic{0%,100%{transform:scale(1)}50%{transform:scale(1.18)}}.var-chip.copied{background:var(--color-primary)!important;color:#fff!important;border-color:var(--color-primary)!important}#object-text-display{white-space:pre-wrap;line-height:1.8;font-size:.95rem;background:#fafafa;border:1px solid #e9ecef;border-radius:6px;padding:1.25rem 1.5rem}</style>

<script>
var _currentStep=1,_objectId=<?=(int)($contractObject['id']??0)?>;
function goToStep(n){document.querySelectorAll('.step-panel').forEach(p=>p.classList.add('d-none'));var p=document.getElementById('step-'+n);if(p)p.classList.remove('d-none');document.querySelectorAll('.step-btn').forEach((b,i)=>{b.classList.remove('active');if(i+1<n)b.classList.add('done');if(i+1>=n)b.classList.remove('done');});var a=document.getElementById('step-btn-'+n);if(a)a.classList.add('active');_currentStep=n;}
function collectFormData(){var fd=new FormData();fd.append('project_id',_projectId);fd.append('briefing_id',_briefingId);document.querySelectorAll('.bf-field').forEach(el=>fd.append(el.name,el.value));return fd;}
function saveAjax(cb){var ind=document.getElementById('save-indicator');if(ind){ind.textContent='Salvando…';ind.classList.remove('d-none','bg-success','bg-danger');ind.classList.add('bg-secondary');}fetch('/admin/briefing/save-ajax',{method:'POST',body:collectFormData()}).then(r=>r.json()).then(d=>{if(d.success){_projectId=d.project_id;_briefingId=d.briefing_id;if(ind){ind.textContent='Salvo';ind.classList.remove('bg-secondary','bg-danger');ind.classList.add('bg-success');}if(cb)cb(d);}else{if(ind){ind.textContent='Erro';ind.classList.remove('bg-secondary','bg-success');ind.classList.add('bg-danger');}alert(d.error||'Erro.');}}).catch(()=>{if(ind){ind.textContent='Erro';ind.classList.remove('bg-secondary','bg-success');ind.classList.add('bg-danger');}alert('Falha.');});}
function saveDraft(){saveAjax(()=>showToast('Rascunho salvo!','success'));}
function saveAndContinue(){saveAjax(d=>{if(history.pushState&&d.edit_url)history.replaceState({},'',d.edit_url);goToStep(2);});}
function saveModelOnly(){showToast('Modelo mantido.','info');}
function loadTemplate(id){var s=document.getElementById('template-select');if(!s)return;var o=s.querySelector('option[value="'+id+'"]');if(o)document.getElementById('prompt-template-field').value=o.dataset.prompt||'';}
function resetTemplate(){var s=document.getElementById('template-select');if(s)loadTemplate(s.value);}
(function(){var s=document.getElementById('template-select');if(s)loadTemplate(s.value);})();
function saveTemplate(){var n=document.getElementById('tpl-name').value.trim(),d=document.getElementById('tpl-desc').value.trim(),p=document.getElementById('tpl-prompt').value.trim(),fb=document.getElementById('tpl-feedback');if(!n||!p){fb.textContent='Obrigatórios.';fb.className='small text-danger';return;}var fd=new FormData();fd.append('template_name',n);fd.append('template_description',d);fd.append('prompt_template',p);fetch('/admin/briefing/store-template',{method:'POST',body:fd}).then(r=>r.json()).then(data=>{if(data.success){fb.textContent='Salvo!';fb.className='small text-success';var s=document.getElementById('template-select');if(s){var o=document.createElement('option');o.value=data.id;o.textContent=data.name;o.dataset.prompt=p;s.appendChild(o);s.value=data.id;loadTemplate(data.id);}setTimeout(()=>{bootstrap.Modal.getInstance(document.getElementById('modalTemplate')).hide();fb.textContent='';},700);}else{fb.textContent=data.error||'Erro.';fb.className='small text-danger';}}).catch(()=>{fb.textContent='Erro.';fb.className='small text-danger';});}
function copyVar(b){navigator.clipboard.writeText(b.dataset.var).then(()=>{b.classList.add('copied');setTimeout(()=>b.classList.remove('copied'),1200);});}
function triggerGenerate(){saveAjax(()=>{if(!_briefingId){alert('Salve primeiro.');return;}doGenerate();});}
function doGenerate(){goToStep(3);var ld=document.getElementById('gen-loading'),wr=document.getElementById('object-result-wrapper');if(ld)ld.classList.remove('d-none');if(wr)wr.innerHTML='';['btn-generate','btn-regenerate'].forEach(id=>{var b=document.getElementById(id);if(b){b.disabled=true;b.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Gerando…';}});var sel=document.getElementById('template-select'),tplId=sel?sel.value:_templateId,cp=document.getElementById('prompt-template-field').value.trim();var fd=new FormData();fd.append('briefing_id',_briefingId);fd.append('template_id',tplId);fd.append('custom_prompt',cp);fetch('/admin/briefing/generate-object',{method:'POST',body:fd}).then(r=>r.json()).then(data=>{if(ld)ld.classList.add('d-none');resetGenBtns();if(data.success){_objectId=data.object_id;renderObj(data.text,data.object_id);}else{if(wr)wr.innerHTML='<div class="alert alert-danger">'+escH(data.error||'Erro.')+'</div>';}}).catch(()=>{if(ld)ld.classList.add('d-none');resetGenBtns();if(wr)wr.innerHTML='<div class="alert alert-danger">Falha na requisição.</div>';});}
function resetGenBtns(){var g=document.getElementById('btn-generate'),r=document.getElementById('btn-regenerate');if(g){g.disabled=false;g.innerHTML='<i class="bi bi-stars me-2"></i> Gerar Contrato';}if(r){r.disabled=false;r.innerHTML='<i class="bi bi-arrow-repeat me-1"></i> Gerar Novamente';}}
function renderObj(text,id){var w=document.getElementById('object-result-wrapper');w.innerHTML='<div class="card mb-3"><div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2"><h6 class="mb-0"><i class="bi bi-file-earmark-check me-2"></i>Contrato Gerado<span id="obj-status-badge" class="badge bg-warning text-dark ms-2">Gerado</span></h6><button class="btn btn-sm btn-outline-secondary" onclick="copyObjText()"><i class="bi bi-clipboard"></i></button></div><div class="card-body"><div id="object-text-display">'+escH(text)+'</div></div></div>';w.dataset.objectText=text;w.dataset.objectId=id;}
function copyObjText(){navigator.clipboard.writeText(document.getElementById('object-result-wrapper').dataset.objectText||'').then(()=>showToast('Copiado!','success'));}
function approveObject(){if(!_objectId){alert('Nada para aprovar.');return;}if(!confirm('Aprovar?'))return;var fd=new FormData();fd.append('object_id',_objectId);fetch('/admin/briefing/approve-object',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{if(d.success){var b=document.getElementById('obj-status-badge');if(b){b.textContent='Aprovado';b.className='badge bg-success ms-2';}var bf=document.getElementById('btn-approve-footer');if(bf){bf.disabled=true;bf.innerHTML='<i class="bi bi-check2-circle me-1"></i> Aprovado';}showToast('Aprovado!','success');}else alert(d.error||'Erro.');});}
function importPdf(){var inp=document.getElementById('pdf-input'),btn=document.getElementById('btn-import-pdf'),st=document.getElementById('pdf-status');if(!inp||!inp.files.length){alert('Selecione um PDF.');return;}btn.disabled=true;btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Processando…';st.style.display='block';st.textContent='Enviando PDF para a IA...';st.className='mt-2 small text-muted';var fd=new FormData();fd.append('pdf',inp.files[0]);fetch('/admin/briefing/import-pdf',{method:'POST',body:fd}).then(r=>r.json()).then(data=>{btn.disabled=false;btn.innerHTML='<i class="bi bi-magic me-1"></i> Processar';if(data.success&&data.fields){var c=fillFields(data.fields);st.textContent='✔ '+c+' campo(s) preenchido(s). Revise antes de salvar.';st.className='mt-2 small text-success';}else{st.textContent='✘ '+(data.error||'Erro.');st.className='mt-2 small text-danger';}}).catch(()=>{btn.disabled=false;btn.innerHTML='<i class="bi bi-magic me-1"></i> Processar';st.textContent='✘ Falha.';st.className='mt-2 small text-danger';});}
function fillFields(f){var c=0;Object.keys(f).forEach(k=>{var v=f[k];if(!v)return;var el=document.getElementById(k);if(el){el.value=v;c++;el.classList.add('border-success');setTimeout(()=>el.classList.remove('border-success'),3000);}});return c;}
function loadContractor(id){var sel=document.getElementById('contractor_company_id'),opt=sel.querySelector('option[value="'+id+'"]'),prev=document.getElementById('contractor-preview');if(!opt||!id){if(prev)prev.innerHTML='';return;}try{var c=JSON.parse(opt.dataset.json||'{}');if(prev&&c.company_name)prev.innerHTML='<div class="bg-light rounded p-3 small"><strong>'+escH(c.company_name)+'</strong>'+(c.cnpj?' — CNPJ: '+escH(c.cnpj):'')+'<br>'+escH(c.address||'')+', '+escH(c.address_number||'')+' '+escH(c.neighborhood||'')+', '+escH(c.city||'')+'/'+escH(c.state||'')+'</div>';}catch(e){}}
function saveContractor(){var fb=document.getElementById('ct-feedback'),name=document.getElementById('ct-company_name').value.trim();if(!name){fb.textContent='Razão social obrigatória.';fb.className='small text-danger';return;}var fd=new FormData();['company_name','trade_name'].forEach(f=>fd.append(f,document.getElementById('ct-'+f).value.trim()));fd.append('contractor_cnpj',document.getElementById('ct-cnpj').value.trim());['address','address_number','complement','neighborhood','city','state','cep','phone','email','representative_name','representative_role'].forEach(f=>fd.append('contractor_'+f,document.getElementById('ct-'+f).value.trim()));fetch('/admin/briefing/store-contractor',{method:'POST',body:fd}).then(r=>r.json()).then(data=>{if(data.success){fb.textContent='Salvo!';fb.className='small text-success';var sel=document.getElementById('contractor_company_id'),opt=document.createElement('option');opt.value=data.id;opt.textContent=data.company.company_name;opt.dataset.json=JSON.stringify(data.company);sel.appendChild(opt);sel.value=data.id;loadContractor(data.id);setTimeout(()=>{bootstrap.Modal.getInstance(document.getElementById('modalContractor')).hide();fb.textContent='';},800);}else{fb.textContent=data.error||'Erro.';fb.className='small text-danger';}}).catch(()=>{fb.textContent='Erro.';fb.className='small text-danger';});}
function confirmDelete(id,n){if(!confirm('Excluir "'+n+'"?'))return;document.getElementById('delete-id').value=id;document.getElementById('delete-form').submit();}
var _sr=null,_srBtn=null,_srOk=('SpeechRecognition' in window||'webkitSpeechRecognition' in window);
function toggleSpeech(bId,tId){if(!_srOk){alert('Voz não suportada.');return;}if(_sr&&_srBtn&&_srBtn.id===bId){_sr.stop();return;}if(_sr)try{_sr.stop();}catch(e){}var SR=window.SpeechRecognition||window.webkitSpeechRecognition,sr=new SR();sr.lang='pt-BR';sr.continuous=true;sr.interimResults=true;_sr=sr;_srBtn=document.getElementById(bId);var tgt=document.getElementById(tId),base=tgt?tgt.value:'',startLen=base.length;sr.onstart=()=>{if(_srBtn){_srBtn.classList.add('active');_srBtn.innerHTML='<i class="bi bi-stop-fill"></i>';}};sr.onresult=e=>{var f='',i='';for(var x=e.resultIndex;x<e.results.length;x++){if(e.results[x].isFinal)f+=e.results[x][0].transcript;else i+=e.results[x][0].transcript;}if(f)base=(base+(base?' ':'')+f).trim();if(tgt)tgt.value=base+(i?' '+i:'');};sr.onerror=e=>{if(e.error!=='no-speech'&&e.error!=='aborted')showToast('Mic: '+e.error,'danger');rstSR();};sr.onend=()=>{if(tgt)tgt.value=base;var dictated=base.substring(startLen).trim();if(dictated)polishDictated(tgt,base,startLen,dictated);rstSR();};sr.start();}
function polishDictated(tgt,base,startLen,dictated){var prev=tgt.value;if(_srBtn){}tgt.disabled=true;var fd=new FormData();fd.append('text',dictated);fetch('/admin/briefing/polish-text',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{tgt.disabled=false;if(d.success&&d.text){tgt.value=base.substring(0,startLen)+(startLen>0&&base.substring(0,startLen).trim()!==''?' ':'')+d.text;}}).catch(()=>{tgt.disabled=false;});}
function rstSR(){if(_srBtn){_srBtn.classList.remove('active');_srBtn.innerHTML='<i class="bi bi-mic"></i>';}_sr=null;_srBtn=null;}
(function(){var d=document.getElementById('client_document');if(d)d.addEventListener('input',()=>{var v=d.value.replace(/\D/g,'').substring(0,14);if(v.length<=11){v=v.replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d{1,2})$/,'$1-$2');d.maxLength=14;}else{v=v.replace(/(\d{2})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1/$2').replace(/(\d{4})(\d{1,2})$/,'$1-$2');d.maxLength=18;}d.value=v;});var t=document.getElementById('client_phone');if(t)t.addEventListener('input',()=>{var v=t.value.replace(/\D/g,'').substring(0,11);v=v.length<=10?v.replace(/(\d{2})(\d)/,'($1) $2').replace(/(\d{4})(\d)/,'$1-$2'):v.replace(/(\d{2})(\d)/,'($1) $2').replace(/(\d{5})(\d)/,'$1-$2');t.value=v;});var c=document.getElementById('project_cep');if(c)c.addEventListener('input',()=>{var v=c.value.replace(/\D/g,'').substring(0,8);if(v.length>5)v=v.replace(/(\d{5})(\d)/,'$1-$2');c.value=v;});})();
(function(){var e=document.getElementById('client_email');if(!e)return;function ck(){var v=e.value.trim(),ok=/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v);e.classList.toggle('is-invalid',v!==''&&!ok);e.classList.toggle('is-valid',v!==''&&ok);}e.addEventListener('blur',ck);e.addEventListener('input',ck);})();
(function(){var c=document.getElementById('project_cep'),a=document.getElementById('project_address'),ci=document.getElementById('project_city'),nb=document.getElementById('project_neighborhood'),st=document.getElementById('project_state'),fb=document.getElementById('cep-feedback');if(!c)return;c.addEventListener('input',()=>{var d=c.value.replace(/\D/g,'');if(d.length===8){fb.textContent='Consultando…';fb.style.display='block';fetch('https://viacep.com.br/ws/'+d+'/json/').then(r=>r.json()).then(data=>{if(data.erro){c.classList.add('is-invalid');fb.textContent='CEP não encontrado.';}else{c.classList.remove('is-invalid');c.classList.add('is-valid');fb.style.display='none';if(a&&data.logradouro)a.value=data.logradouro;if(nb&&data.bairro)nb.value=data.bairro;if(ci&&data.localidade)ci.value=data.localidade;if(st&&data.uf)st.value=data.uf;}}).catch(()=>{fb.textContent='Erro.';});}else{c.classList.remove('is-invalid','is-valid');fb.style.display='none';}});})();
function escH(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function showToast(m,t){var c=document.createElement('div');c.className='toast align-items-center text-bg-'+(t||'primary')+' border-0 show position-fixed';c.style.cssText='bottom:1.5rem;right:1.5rem;z-index:9999;min-width:220px;';c.innerHTML='<div class="d-flex"><div class="toast-body">'+escH(m)+'</div><button type="button" class="btn-close btn-close-white me-2 m-auto" onclick="this.closest(\'.toast\').remove()"></button></div>';document.body.appendChild(c);setTimeout(()=>{if(c.parentNode)c.remove();},3000);}
<?php if($contractObject&&($contractObject['status']??'')==='approved'):?>setTimeout(()=>{var b=document.getElementById('obj-status-badge');if(b){b.textContent='Aprovado';b.className='badge bg-success ms-2';}var bf=document.getElementById('btn-approve-footer');if(bf){bf.disabled=true;bf.innerHTML='<i class="bi bi-check2-circle me-1"></i> Aprovado';}},50);<?php endif;?>
</script>
<?php $content=ob_get_clean(); include ROOT_PATH.'/app/Views/admin/layouts/app.php'; ?>
