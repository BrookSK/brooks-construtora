<?php $pageTitle = 'Pauta de Revisão'; $currentPage = 'painel_obras'; ?>
<?php ob_start(); ?>
<style>
.main-content{background:#0b1220!important} body{background:#0b1220!important}
.top-bar{background:#151f32!important;color:#e2e8f0!important;box-shadow:none!important;border-bottom:1px solid rgba(255,255,255,.06)!important}
.top-bar h5{color:#f8fafc!important} .top-bar .text-muted{color:#94a3b8!important}
.top-bar .badge{background:rgba(59,130,246,.2)!important;color:#93c5fd!important}
#pt-pg{font-family:'Inter',system-ui,sans-serif;color:#e2e8f0;padding-bottom:48px}
.pt-card{background:#151f32;border:1px solid rgba(255,255,255,.06);border-radius:12px;overflow:hidden}
.pt-inp{background:#0f1829;border:1px solid rgba(255,255,255,.1);color:#e2e8f0;border-radius:8px;padding:8px 12px;width:100%;outline:none;font-size:14px}
.pt-inp:focus{border-color:#3b82f6} .pt-inp option{background:#0f1829}
.pt-label{font-size:12px;color:#94a3b8;margin-bottom:4px;display:block}
.pt-btn-p{background:linear-gradient(135deg,#3b82f6,#22d3ee);color:#fff;border:none;border-radius:10px;padding:8px 18px;font-size:13px;font-weight:600;cursor:pointer}
.pt-btn-g{background:#151f32;border:1px solid rgba(255,255,255,.12);color:#e2e8f0;border-radius:10px;padding:8px 14px;font-size:13px;cursor:pointer}
.pt-btn-d{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#fca5a5;border-radius:8px;padding:5px 10px;font-size:12px;cursor:pointer}
.pt-table{width:100%;border-collapse:collapse}
.pt-table th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;padding:10px 16px;border-bottom:1px solid rgba(255,255,255,.06)}
.pt-table td{padding:10px 16px;border-bottom:1px solid rgba(255,255,255,.04);font-size:13px;color:#e2e8f0;vertical-align:middle}
.pt-table tr:last-child td{border-bottom:none}
.pt-table tbody tr:hover{background:rgba(59,130,246,.05)}
.pt-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:1050;align-items:center;justify-content:center;padding:16px}
.pt-modal-box{background:#151f32;border:1px solid rgba(255,255,255,.08);border-radius:16px;width:100%;max-width:480px;padding:28px}
.pt-subnav{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px}
.pt-subnav a{font-size:13px;padding:6px 14px;border-radius:8px;color:#94a3b8;text-decoration:none;border:1px solid rgba(255,255,255,.06);transition:all .15s}
.pt-subnav a:hover,.pt-subnav a.active{background:rgba(59,130,246,.12);color:#60a5fa;border-color:rgba(59,130,246,.3)}
</style>

<div id="pt-pg">

<div class="pt-subnav">
  <a href="/admin/painel-obras"><i class="bi bi-speedometer2"></i> Dashboard</a>
  <a href="/admin/painel-obras/obras"><i class="bi bi-buildings"></i> Obras</a>
  <a href="/admin/painel-obras/orcamento"><i class="bi bi-calculator"></i> Orçamento</a>
  <a href="/admin/painel-obras/planilha"><i class="bi bi-table"></i> Planilha de Controle</a>
  <a href="/admin/painel-obras/depara"><i class="bi bi-arrow-left-right"></i> De-para Categorias</a>
  <a href="/admin/painel-obras/pauta" class="active"><i class="bi bi-calendar-check"></i> Pauta de Revisão</a>
</div>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
  <h5 style="margin:0;color:#f8fafc">Pauta de Revisão</h5>
  <button class="pt-btn-p" onclick="document.getElementById('pt-modal').style.display='flex'">
    <i class="bi bi-calendar-plus"></i> Adicionar item
  </button>
</div>

<div class="pt-card" style="overflow-x:auto">
  <table class="pt-table">
    <thead>
      <tr><th>Obra</th><th>Data da reunião</th><th>Motivo</th><th>Adicionado por</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach($pauta as $p): ?>
      <tr>
        <td><span style="color:#60a5fa;font-weight:600"><?=htmlspecialchars($p['codigo'])?></span> <span style="color:#e2e8f0"><?=htmlspecialchars($p['nome'])?></span></td>
        <td><?=date('d/m/Y',strtotime($p['data_reuniao']))?></td>
        <td style="color:#94a3b8"><?=htmlspecialchars($p['motivo']??'—')?></td>
        <td style="color:#94a3b8"><?=htmlspecialchars($p['adicionado_por'])?></td>
        <td><?php
          $st=$p['status'];
          $sc=$st==='Pendente'?'background:rgba(245,158,11,.12);color:#fcd34d':'background:rgba(16,185,129,.12);color:#6ee7b7';
          echo "<span style=\"font-size:11px;font-weight:600;padding:2px 8px;border-radius:12px;{$sc}\">{$st}</span>";
        ?></td>
        <td style="white-space:nowrap">
          <?php if($p['status']==='Pendente'): ?>
          <button class="pt-btn-g" style="padding:4px 10px;font-size:12px" onclick="ptRevisar(<?=$p['id']?>)">Marcar revisada</button>
          <?php endif; ?>
          <button class="pt-btn-d" style="margin-left:4px" onclick="ptDel(<?=$p['id']?>)"><i class="bi bi-trash3"></i></button>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($pauta)): ?>
      <tr><td colspan="6" style="text-align:center;padding:32px;color:#94a3b8">Nenhum item na pauta.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal -->
<div id="pt-modal" class="pt-modal">
  <div class="pt-modal-box">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
      <h5 style="margin:0;color:#f8fafc">Adicionar à Pauta</h5>
      <button style="background:none;border:none;color:#94a3b8;font-size:20px;cursor:pointer" onclick="document.getElementById('pt-modal').style.display='none'">×</button>
    </div>
    <div style="margin-bottom:12px">
      <label class="pt-label">Obra(s) * <small style="color:#64748b">Ctrl/Cmd para múltiplas</small></label>
      <select id="pt-obras" class="pt-inp" multiple size="5">
        <?php foreach($obras as $o): ?><option value="<?=$o['id']?>"><?=htmlspecialchars($o['codigo'].' — '.$o['nome'])?></option><?php endforeach; ?>
      </select>
    </div>
    <div style="margin-bottom:12px">
      <label class="pt-label">Data da reunião *</label>
      <input id="pt-data" type="date" class="pt-inp" value="<?=date('Y-m-d',strtotime('next friday'))?>">
    </div>
    <div style="margin-bottom:12px">
      <label class="pt-label">Motivo (opcional)</label>
      <textarea id="pt-motivo" class="pt-inp" rows="2" placeholder="Descreva o motivo"></textarea>
    </div>
    <div id="pt-err" style="color:#fca5a5;font-size:13px;display:none;margin-bottom:10px"></div>
    <div style="display:flex;gap:10px">
      <button class="pt-btn-p" onclick="ptSalvar()"><i class="bi bi-check2"></i> Adicionar</button>
      <button class="pt-btn-g" onclick="document.getElementById('pt-modal').style.display='none'">Cancelar</button>
    </div>
  </div>
</div>

</div>
<script>
function ptSalvar(){
  const sel=document.getElementById('pt-obras');
  const ids=[...sel.selectedOptions].map(o=>o.value);
  if(!ids.length){const e=document.getElementById('pt-err');e.textContent='Selecione ao menos uma obra.';e.style.display='block';return;}
  const p=new URLSearchParams({data_reuniao:document.getElementById('pt-data').value,motivo:document.getElementById('pt-motivo').value.trim()});
  ids.forEach(id=>p.append('obra_ids[]',id));
  fetch('/admin/painel-obras/pauta/store',{method:'POST',body:p}).then(r=>r.json()).then(res=>{
    if(res.ok) location.reload();
    else{const e=document.getElementById('pt-err');e.textContent=res.error||'Erro';e.style.display='block';}
  });
}
function ptRevisar(id){fetch('/admin/painel-obras/pauta/update',{method:'POST',body:new URLSearchParams({id,status:'Revisada'})}).then(r=>r.json()).then(res=>{if(res.ok)location.reload();});}
function ptDel(id){if(!confirm('Remover item da pauta?'))return;fetch('/admin/painel-obras/pauta/delete',{method:'POST',body:new URLSearchParams({id})}).then(r=>r.json()).then(res=>{if(res.ok)location.reload();});}
</script>
<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
