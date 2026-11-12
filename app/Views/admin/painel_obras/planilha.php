<?php $pageTitle = 'Planilha de Controle'; $currentPage = 'painel_obras'; ?>
<?php ob_start(); ?>
<link rel="stylesheet" href="/assets/css/painel-obras-light.css">
<style>
/* Override do layout para tema claro nesta página */
.main-content{background:#f8f9fa!important} body{background:#f8f9fa!important}
.top-bar{background:#ffffff!important;color:#1e293b!important;box-shadow:none!important;border-bottom:1px solid #e2e8f0!important}
.top-bar h5{color:#0f172a!important} .top-bar .text-muted{color:#64748b!important}
.top-bar .badge{background:rgba(59,130,246,.2)!important;color:#2563eb!important}
#pl-pg{font-family:'Inter',system-ui,sans-serif;color:#1e293b;padding-bottom:48px}
.pl-card{background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden}
.pl-inp{background:#f8f9fa;border:1px solid #e2e8f0;color:#1e293b;border-radius:8px;padding:8px 12px;width:100%;outline:none;font-size:14px}
.pl-inp:focus{border-color:#3b82f6} .pl-inp option{background:#ffffff}
.pl-label{font-size:12px;color:#64748b;margin-bottom:4px;display:block}
.pl-btn-p{background:linear-gradient(135deg,#3b82f6,#22d3ee);color:#fff;border:none;border-radius:10px;padding:8px 18px;font-size:13px;font-weight:600;cursor:pointer}
.pl-btn-g{background:#ffffff;border:1px solid #e2e8f0;color:#1e293b;border-radius:10px;padding:8px 14px;font-size:13px;cursor:pointer}
.pl-btn-sm{background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.2);color:#2563eb;border-radius:8px;padding:5px 12px;font-size:12px;cursor:pointer}
.pl-table{width:100%;border-collapse:collapse}
.pl-table th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#64748b;padding:10px 16px;border-bottom:1px solid #e2e8f0}
.pl-table td{padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#1e293b;vertical-align:middle}
.pl-table tr:last-child td{border-bottom:none}
.pl-table tbody tr:hover{background:rgba(59,130,246,.05)}
.pl-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1050;align-items:center;justify-content:center;padding:16px}
.pl-modal-box{background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;width:100%;max-width:460px;padding:28px}
.pl-subnav{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px}
.pl-subnav a{font-size:13px;padding:6px 14px;border-radius:8px;color:#64748b;text-decoration:none;border:1px solid #e2e8f0;transition:all .15s}
.pl-subnav a:hover,.pl-subnav a.active{background:rgba(59,130,246,.12);color:#2563eb;border-color:rgba(59,130,246,.3)}
</style>

<div id="pl-pg">

<div class="pl-subnav">
  <a href="/admin/painel-obras"><i class="bi bi-speedometer2"></i> Dashboard</a>
  <a href="/admin/painel-obras/obras"><i class="bi bi-buildings"></i> Obras</a>
  <a href="/admin/painel-obras/orcamento"><i class="bi bi-calculator"></i> Orçamento</a>
  <a href="/admin/painel-obras/planilha" class="active"><i class="bi bi-table"></i> Planilha de Controle</a>
  <a href="/admin/painel-obras/depara"><i class="bi bi-arrow-left-right"></i> De-para Categorias</a>
  <a href="/admin/painel-obras/pauta"><i class="bi bi-calendar-check"></i> Pauta de Revisão</a>
</div>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
  <h5 style="margin:0;color:#f8fafc">Planilha de Controle Mensal</h5>
  <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
    <label class="pl-label" style="margin:0;white-space:nowrap">Competência:</label>
    <input type="month" id="pl-comp" class="pl-inp" style="max-width:160px" value="<?=htmlspecialchars($competencia)?>"
           onchange="window.location='/admin/painel-obras/planilha?competencia='+this.value">
    <button class="pl-btn-p" onclick="document.getElementById('pl-modal').style.display='flex'">
      <i class="bi bi-plus-circle"></i> Registrar
    </button>
  </div>
</div>

<div class="pl-card" style="overflow-x:auto">
  <table class="pl-table">
    <thead>
      <tr><th>Código</th><th>Nome</th><th style="text-align:right">Total Planilha</th><th>Informado por</th><th>Data</th><th>Obs.</th><th></th></tr>
    </thead>
    <tbody>
      <?php
      $rm=[];
      foreach($registros as $r) $rm[$r['obra_id']]=$r;
      foreach($obras as $o):
        $r=$rm[$o['id']]??null;
      ?>
      <tr>
        <td><span style="color:#60a5fa;font-weight:600"><?=htmlspecialchars($o['codigo'])?></span></td>
        <td style="font-weight:600"><?=htmlspecialchars($o['nome'])?></td>
        <td style="text-align:right">
          <?=$r?'R$ '.number_format($r['total_planilha'],2,',','.'):'<span style="color:#64748b">—</span>'?>
        </td>
        <td style="color:#94a3b8"><?=htmlspecialchars($r['informado_por']??'—')?></td>
        <td style="color:#94a3b8;font-size:12px"><?=$r?substr($r['data_informacao'],0,16):'—'?></td>
        <td style="color:#94a3b8;font-size:12px"><?=htmlspecialchars($r['observacao']??'')?></td>
        <td>
          <button class="pl-btn-sm" onclick='plPrePreencher(<?=$o["id"]?>,"<?=htmlspecialchars($o["codigo"]." — ".$o["nome"],ENT_QUOTES)?>","<?=htmlspecialchars($r["total_planilha"]??"",ENT_QUOTES)?>")'><?=$r?'Atualizar':'Informar'?></button>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($obras)): ?>
      <tr><td colspan="7" style="text-align:center;padding:32px;color:#94a3b8">Nenhuma obra ativa cadastrada.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<p style="font-size:12px;color:#64748b;margin-top:8px">O gasto real do Nibo é calculado no dashboard a partir dos lançamentos pagos do período.</p>

<!-- Modal -->
<div id="pl-modal" class="pl-modal">
  <div class="pl-modal-box">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
      <h5 style="margin:0;color:#f8fafc">Registrar Total da Planilha</h5>
      <button style="background:none;border:none;color:#94a3b8;font-size:20px;cursor:pointer" onclick="document.getElementById('pl-modal').style.display='none'">×</button>
    </div>
    <div style="margin-bottom:12px">
      <label class="pl-label">Obra *</label>
      <select id="pl-obra" class="pl-inp">
        <option value="">— Selecionar —</option>
        <?php foreach($obras as $o): ?><option value="<?=$o['id']?>"><?=htmlspecialchars($o['codigo'].' — '.$o['nome'])?></option><?php endforeach; ?>
      </select>
    </div>
    <div style="margin-bottom:12px">
      <label class="pl-label">Competência *</label>
      <input id="pl-comp-mo" type="month" class="pl-inp" value="<?=htmlspecialchars($competencia)?>">
    </div>
    <div style="margin-bottom:12px">
      <label class="pl-label">Total da Planilha *</label>
      <input id="pl-total" class="pl-inp" placeholder="R$ 0,00" oninput="fmtMoeda(this)">
    </div>
    <div style="margin-bottom:12px">
      <label class="pl-label">Observação</label>
      <textarea id="pl-obs" class="pl-inp" rows="2" placeholder="Opcional"></textarea>
    </div>
    <div id="pl-err" style="color:#fca5a5;font-size:13px;display:none;margin-bottom:10px"></div>
    <div style="display:flex;gap:10px">
      <button class="pl-btn-p" onclick="plSalvar()"><i class="bi bi-check2"></i> Salvar</button>
      <button class="pl-btn-g" onclick="document.getElementById('pl-modal').style.display='none'">Cancelar</button>
    </div>
  </div>
</div>

</div>
<script>
function plPrePreencher(id,nome,total){
  document.getElementById('pl-obra').value=id;
  if(total){const f=parseFloat(total).toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.');document.getElementById('pl-total').value='R$ '+f;}
  document.getElementById('pl-modal').style.display='flex';
}
function plSalvar(){
  const p=new URLSearchParams({obra_id:document.getElementById('pl-obra').value,competencia:document.getElementById('pl-comp-mo').value,total_planilha:document.getElementById('pl-total').value,observacao:document.getElementById('pl-obs').value.trim()});
  if(!p.get('obra_id')||!p.get('competencia')){const e=document.getElementById('pl-err');e.textContent='Obra e competência obrigatórios.';e.style.display='block';return;}
  fetch('/admin/painel-obras/planilha/salvar',{method:'POST',body:p}).then(r=>r.json()).then(res=>{
    if(!res.ok){const e=document.getElementById('pl-err');e.textContent=res.error||'Erro';e.style.display='block';return;}
    location.reload();
  });
}
function fmtMoeda(el){let v=el.value.replace(/\D/g,'');v=(parseInt(v||'0')/100).toFixed(2);el.value='R$ '+v.replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.');}
</script>
<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
