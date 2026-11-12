<?php $pageTitle = 'Obras — Painel Financeiro'; $currentPage = 'painel_obras'; ?>
<?php ob_start(); ?>
<style>
.main-content{background:#0b1220!important} body{background:#0b1220!important}
.top-bar{background:#151f32!important;color:#e2e8f0!important;box-shadow:none!important;border-bottom:1px solid rgba(255,255,255,.06)!important}
.top-bar h5{color:#f8fafc!important} .top-bar .text-muted{color:#94a3b8!important}
.top-bar .badge{background:rgba(59,130,246,.2)!important;color:#93c5fd!important}
#ob{font-family:'Inter',system-ui,sans-serif;font-variant-numeric:tabular-nums;color:#e2e8f0;padding-bottom:48px}
.ob-card{background:#151f32;border:1px solid rgba(255,255,255,.06);border-radius:12px;overflow:hidden}
.ob-inp{background:#0f1829;border:1px solid rgba(255,255,255,.1);color:#e2e8f0;border-radius:8px;padding:8px 12px;width:100%;outline:none;font-size:14px}
.ob-inp:focus{border-color:#3b82f6} .ob-inp option{background:#0f1829}
.ob-label{font-size:12px;color:#94a3b8;margin-bottom:4px;display:block}
.ob-btn-p{background:linear-gradient(135deg,#3b82f6,#22d3ee);color:#fff;border:none;border-radius:10px;padding:8px 18px;font-size:13px;font-weight:600;cursor:pointer}
.ob-btn-g{background:#151f32;border:1px solid rgba(255,255,255,.12);color:#e2e8f0;border-radius:10px;padding:8px 14px;font-size:13px;cursor:pointer}
.ob-btn-d{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#fca5a5;border-radius:8px;padding:5px 10px;font-size:12px;cursor:pointer}
.ob-table{width:100%;border-collapse:collapse}
.ob-table th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;padding:10px 16px;border-bottom:1px solid rgba(255,255,255,.06);text-align:left;white-space:nowrap}
.ob-table td{padding:11px 16px;border-bottom:1px solid rgba(255,255,255,.04);font-size:13px;color:#e2e8f0;vertical-align:middle}
.ob-table tr:last-child td{border-bottom:none}
.ob-table tbody tr:hover{background:rgba(59,130,246,.05)}
.ob-pill-ok{font-size:11px;font-weight:600;padding:2px 8px;border-radius:12px;background:rgba(16,185,129,.14);color:#6ee7b7}
.ob-pill-warn{font-size:11px;font-weight:600;padding:2px 8px;border-radius:12px;background:rgba(245,158,11,.14);color:#fcd34d}
.ob-pill-blue{font-size:11px;font-weight:600;padding:2px 8px;border-radius:12px;background:rgba(59,130,246,.14);color:#93c5fd}
.ob-pill-gray{font-size:11px;font-weight:600;padding:2px 8px;border-radius:12px;background:rgba(100,116,139,.14);color:#94a3b8}
.ob-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:1050;align-items:center;justify-content:center;padding:16px}
.ob-modal-box{background:#151f32;border:1px solid rgba(255,255,255,.08);border-radius:16px;width:100%;max-width:700px;max-height:90vh;overflow-y:auto;padding:28px}
.ob-modal-box .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.ob-modal-box .col-full{grid-column:1/-1}
.ob-modal-box .col-third{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;grid-column:1/-1}
.ob-cc-tag{background:rgba(59,130,246,.12);color:#60a5fa;font-size:12px;padding:3px 10px;border-radius:20px;display:inline-flex;align-items:center;gap:6px;margin:2px}
.ob-subnav{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px}
.ob-subnav a{font-size:13px;padding:6px 14px;border-radius:8px;color:#94a3b8;text-decoration:none;border:1px solid rgba(255,255,255,.06);transition:all .15s}
.ob-subnav a:hover,.ob-subnav a.active{background:rgba(59,130,246,.12);color:#60a5fa;border-color:rgba(59,130,246,.3)}
.ob-warn{background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:10px;padding:10px 16px;font-size:13px;color:#fcd34d;margin-bottom:14px;display:flex;align-items:center;gap:8px}
</style>

<div id="ob">

<div class="ob-subnav">
  <a href="/admin/painel-obras"><i class="bi bi-speedometer2"></i> Dashboard</a>
  <a href="/admin/painel-obras/obras" class="active"><i class="bi bi-buildings"></i> Obras</a>
  <a href="/admin/painel-obras/orcamento"><i class="bi bi-calculator"></i> Orçamento</a>
  <a href="/admin/painel-obras/planilha"><i class="bi bi-table"></i> Planilha de Controle</a>
  <a href="/admin/painel-obras/depara"><i class="bi bi-arrow-left-right"></i> De-para Categorias</a>
  <a href="/admin/painel-obras/pauta"><i class="bi bi-calendar-check"></i> Pauta de Revisão</a>
</div>

<?php if($pendentes>0): ?>
<div class="ob-warn">
  <i class="bi bi-exclamation-triangle-fill"></i>
  <?=$pendentes?> obra<?=$pendentes!==1?'s':''?> com informações pendentes (contratado, orçamento, centro de custo ou planilha do mês).
</div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
  <h5 style="margin:0;color:#f8fafc">Obras Cadastradas</h5>
  <button class="ob-btn-p" onclick="document.getElementById('ob-modal').style.display='flex'">
    <i class="bi bi-plus-circle"></i> Nova Obra
  </button>
</div>

<div style="display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap">
  <input type="text" id="ob-busca" class="ob-inp" style="max-width:280px" placeholder="Buscar por nome, código ou cliente" oninput="obFiltrar()">
  <select id="ob-sit" class="ob-inp" style="max-width:160px" onchange="obFiltrar()">
    <option value="">Todas as situações</option>
    <option>Ativa</option><option>Concluída</option><option>Pausada</option><option>Cancelada</option>
  </select>
</div>

<div class="ob-card" style="overflow-x:auto">
  <table class="ob-table">
    <thead>
      <tr><th>Código</th><th>Nome</th><th>Tipo</th><th>Cliente</th><th style="text-align:right">Contratado</th><th>Situação</th><th>Completude</th><th></th></tr>
    </thead>
    <tbody id="ob-tbody">
    <?php foreach($obras as $o): $falta=$o['completude']['falta']; $ok=$o['completude']['completa']; ?>
      <tr data-nome="<?=strtolower(htmlspecialchars($o['nome']))?>"
          data-codigo="<?=strtolower(htmlspecialchars($o['codigo']))?>"
          data-cliente="<?=strtolower(htmlspecialchars($o['cliente']??''))?>"
          data-sit="<?=htmlspecialchars($o['situacao'])?>">
        <td><span style="color:#60a5fa;font-weight:600"><?=htmlspecialchars($o['codigo'])?></span></td>
        <td style="font-weight:600"><?=htmlspecialchars($o['nome'])?></td>
        <td><?=$o['tipo']==='Reforma'?'<span style="color:#67e8f9;background:rgba(103,232,249,.12);font-size:11px;font-weight:600;padding:2px 8px;border-radius:8px">Reforma</span>':'<span style="color:#93c5fd;background:rgba(147,197,253,.14);font-size:11px;font-weight:600;padding:2px 8px;border-radius:8px">Construção</span>'?></td>
        <td style="color:#94a3b8"><?=htmlspecialchars($o['cliente']??'—')?></td>
        <td style="text-align:right"><?=$o['valor_contratado']>0?'R$ '.number_format($o['valor_contratado'],2,',','.'):'<a href="/admin/painel-obras/obras#" style="color:#60a5fa;font-size:11px" onclick="obEditar('.json_encode($o).')">— Preencher</a>'?></td>
        <td><?php
          $sit=$o['situacao'];
          $sc=$sit==='Ativa'?'ob-pill-ok':($sit==='Concluída'?'ob-pill-blue':'ob-pill-gray');
          echo "<span class=\"{$sc}\">{$sit}</span>";
        ?></td>
        <td><?php if($ok):?>
          <span class="ob-pill-ok">Completa</span>
          <?php else:?>
          <span class="ob-pill-warn" title="Falta: <?=implode(', ',$falta)?>">Pendente: <?=implode(', ',$falta)?></span>
          <?php endif;?></td>
        <td style="white-space:nowrap">
          <button class="ob-btn-g" style="padding:4px 10px;font-size:12px" onclick='obEditar(<?=json_encode($o)?>)'><i class="bi bi-pencil"></i></button>
          <button class="ob-btn-d ms-1" onclick="obDel(<?=$o['id']?>,'<?=htmlspecialchars($o['nome'],ENT_QUOTES)?>')"><i class="bi bi-trash3"></i></button>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if(empty($obras)): ?>
      <tr><td colspan="8" style="text-align:center;padding:40px;color:#94a3b8">
        Nenhuma obra cadastrada.
        <button class="ob-btn-p" style="margin-left:12px" onclick="document.getElementById('ob-modal').style.display='flex'">Cadastrar primeira obra</button>
      </td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal -->
<div id="ob-modal" class="ob-modal">
  <div class="ob-modal-box">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <h5 style="margin:0;color:#f8fafc" id="ob-modal-title">Nova Obra</h5>
      <button style="background:none;border:none;color:#94a3b8;font-size:20px;cursor:pointer" onclick="obFechar()">×</button>
    </div>
    <input type="hidden" id="ob-id">
    <div class="row">
      <div>
        <label class="ob-label">Código *</label>
        <input id="ob-codigo" class="ob-inp" placeholder="REF-07, CON-03, P002" style="text-transform:uppercase">
      </div>
      <div>
        <label class="ob-label">Situação *</label>
        <select id="ob-situacao" class="ob-inp"><option>Ativa</option><option>Concluída</option><option>Pausada</option><option>Cancelada</option></select>
      </div>
      <div class="col-full">
        <label class="ob-label">Nome *</label>
        <input id="ob-nome" class="ob-inp" placeholder="Nome completo da obra">
      </div>
      <div class="col-third">
        <div>
          <label class="ob-label">Tipo *</label>
          <select id="ob-tipo" class="ob-inp"><option>Reforma</option><option>Construção</option></select>
        </div>
        <div>
          <label class="ob-label">Cliente</label>
          <input id="ob-cliente" class="ob-inp" placeholder="Nome do cliente">
        </div>
        <div>
          <label class="ob-label">Responsável</label>
          <input id="ob-resp" class="ob-inp" placeholder="Nome">
        </div>
      </div>
      <div>
        <label class="ob-label">Valor Contratado *</label>
        <input id="ob-contratado" class="ob-inp" placeholder="R$ 0,00" oninput="fmtMoeda(this)">
      </div>
      <div>
        <label class="ob-label">Data do Contrato *</label>
        <input id="ob-dcontrato" type="date" class="ob-inp">
      </div>
      <div>
        <label class="ob-label">Data de Início</label>
        <input id="ob-dinicio" type="date" class="ob-inp">
      </div>
      <div>
        <label class="ob-label">Data Prev. Fim</label>
        <input id="ob-dfim" type="date" class="ob-inp">
      </div>
      <div class="col-full">
        <label class="ob-label">Centros de Custo do Nibo <small style="color:#64748b">(vincula lançamentos à obra)</small></label>
        <div id="ob-cc-tags" style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:6px;min-height:28px"></div>
        <select id="ob-cc-sel" class="ob-inp" onchange="obAddCc()">
          <option value="">— Selecionar centro de custo —</option>
          <?php foreach($costCenters as $cc): ?>
          <option value="<?=htmlspecialchars($cc['id'])?>"><?=htmlspecialchars($cc['name'])?></option>
          <?php endforeach; ?>
        </select>
        <input type="hidden" id="ob-cc-json" value="[]">
      </div>
      <div class="col-full">
        <label class="ob-label">Códigos alternativos <small style="color:#64748b">(separados por vírgula, ex.: ADM-105, P002)</small></label>
        <input id="ob-codalt" class="ob-inp" placeholder="ADM-105, P002">
      </div>
      <div class="col-full">
        <label class="ob-label">Observações</label>
        <textarea id="ob-obs" class="ob-inp" rows="2" placeholder="Observações gerais"></textarea>
      </div>
    </div>
    <div id="ob-err" style="color:#fca5a5;font-size:13px;margin-top:10px;display:none"></div>
    <div style="display:flex;gap:10px;margin-top:16px">
      <button class="ob-btn-p" onclick="obSalvar()"><i class="bi bi-check2"></i> Salvar</button>
      <button class="ob-btn-g" onclick="obFechar()">Cancelar</button>
    </div>
  </div>
</div>

</div><!-- /ob -->
<script>
function obFiltrar(){
  const q=document.getElementById('ob-busca').value.toLowerCase();
  const sit=document.getElementById('ob-sit').value;
  document.querySelectorAll('#ob-tbody tr[data-nome]').forEach(tr=>{
    const ok=(!q||tr.dataset.nome.includes(q)||tr.dataset.codigo.includes(q)||tr.dataset.cliente.includes(q))&&(!sit||tr.dataset.sit===sit);
    tr.style.display=ok?'':'none';
  });
}
function obFechar(){document.getElementById('ob-modal').style.display='none';document.getElementById('ob-id').value='';document.getElementById('ob-cc-json').value='[]';document.getElementById('ob-cc-tags').innerHTML='';document.getElementById('ob-err').style.display='none';}
function obEditar(o){
  document.getElementById('ob-modal-title').textContent='Editar Obra';
  document.getElementById('ob-id').value=o.id;
  document.getElementById('ob-codigo').value=o.codigo;
  document.getElementById('ob-nome').value=o.nome;
  document.getElementById('ob-tipo').value=o.tipo;
  document.getElementById('ob-situacao').value=o.situacao;
  document.getElementById('ob-cliente').value=o.cliente||'';
  document.getElementById('ob-resp').value=o.responsavel||'';
  document.getElementById('ob-contratado').value=o.valor_contratado>0?fmtBRL(o.valor_contratado):'';
  document.getElementById('ob-dcontrato').value=o.data_contrato||'';
  document.getElementById('ob-dinicio').value=o.data_inicio||'';
  document.getElementById('ob-dfim').value=o.data_prev_fim||'';
  document.getElementById('ob-obs').value=o.observacoes||'';
  document.getElementById('ob-codalt').value=JSON.parse(o.codigos_alt||'[]').join(', ');
  const ccs=JSON.parse(o.centros_custo||'[]');
  document.getElementById('ob-cc-json').value=JSON.stringify(ccs);
  renderCcTags(ccs);
  document.getElementById('ob-modal').style.display='flex';
}
function renderCcTags(ccs){
  const sel=document.getElementById('ob-cc-sel');
  document.getElementById('ob-cc-tags').innerHTML=ccs.map((id,i)=>{
    const opt=[...sel.options].find(o=>o.value===id);
    return `<span class="ob-cc-tag">${opt?opt.text:id} <span style="cursor:pointer" onclick="obRmCc(${i})">×</span></span>`;
  }).join('');
}
function obAddCc(){
  const sel=document.getElementById('ob-cc-sel');
  const val=sel.value; if(!val) return;
  const ccs=JSON.parse(document.getElementById('ob-cc-json').value||'[]');
  if(!ccs.includes(val)){ccs.push(val);document.getElementById('ob-cc-json').value=JSON.stringify(ccs);renderCcTags(ccs);}
  sel.value='';
}
function obRmCc(i){const ccs=JSON.parse(document.getElementById('ob-cc-json').value||'[]');ccs.splice(i,1);document.getElementById('ob-cc-json').value=JSON.stringify(ccs);renderCcTags(ccs);}
function obSalvar(){
  const id=document.getElementById('ob-id').value;
  const url=id?'/admin/painel-obras/obras/update':'/admin/painel-obras/obras/store';
  const p=new URLSearchParams({id,
    codigo:document.getElementById('ob-codigo').value.trim().toUpperCase(),
    nome:document.getElementById('ob-nome').value.trim(),
    tipo:document.getElementById('ob-tipo').value,
    situacao:document.getElementById('ob-situacao').value,
    cliente:document.getElementById('ob-cliente').value.trim(),
    responsavel:document.getElementById('ob-resp').value.trim(),
    valor_contratado:document.getElementById('ob-contratado').value,
    data_contrato:document.getElementById('ob-dcontrato').value,
    data_inicio:document.getElementById('ob-dinicio').value,
    data_prev_fim:document.getElementById('ob-dfim').value,
    observacoes:document.getElementById('ob-obs').value.trim(),
    centros_custo:document.getElementById('ob-cc-json').value,
    codigos_alt:document.getElementById('ob-codalt').value,
  });
  fetch(url,{method:'POST',body:p}).then(r=>r.json()).then(res=>{
    if(!res.ok){const e=document.getElementById('ob-err');e.textContent=res.error||'Erro';e.style.display='block';return;}
    location.reload();
  }).catch(()=>{const e=document.getElementById('ob-err');e.textContent='Falha de conexão';e.style.display='block';});
}
function obDel(id,nome){
  if(!confirm('Excluir a obra "'+nome+'"?')) return;
  fetch('/admin/painel-obras/obras/delete',{method:'POST',body:new URLSearchParams({id})}).then(r=>r.json()).then(res=>{if(res.ok)location.reload();});
}
function fmtMoeda(el){let v=el.value.replace(/\D/g,'');v=(parseInt(v||'0')/100).toFixed(2);el.value='R$ '+v.replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.');}
function fmtBRL(v){return 'R$ '+parseFloat(v).toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.');}
</script>
<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
