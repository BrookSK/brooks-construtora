<?php $pageTitle = 'De-para de Categorias'; $currentPage = 'painel_obras'; ?>
<?php ob_start(); $cats=$categoriasObra; ?>
<style>
.main-content{background:#0b1220!important} body{background:#0b1220!important}
.top-bar{background:#151f32!important;color:#e2e8f0!important;box-shadow:none!important;border-bottom:1px solid rgba(255,255,255,.06)!important}
.top-bar h5{color:#f8fafc!important} .top-bar .text-muted{color:#94a3b8!important}
.top-bar .badge{background:rgba(59,130,246,.2)!important;color:#93c5fd!important}
#dp-pg{font-family:'Inter',system-ui,sans-serif;color:#e2e8f0;padding-bottom:48px}
.dp-card{background:#151f32;border:1px solid rgba(255,255,255,.06);border-radius:12px;overflow:hidden;margin-bottom:16px}
.dp-inp{background:#0f1829;border:1px solid rgba(255,255,255,.1);color:#e2e8f0;border-radius:8px;padding:7px 12px;width:100%;outline:none;font-size:13px}
.dp-inp:focus{border-color:#3b82f6} .dp-inp option{background:#0f1829}
.dp-btn-p{background:linear-gradient(135deg,#3b82f6,#22d3ee);color:#fff;border:none;border-radius:10px;padding:7px 16px;font-size:13px;font-weight:600;cursor:pointer}
.dp-btn-g{background:#151f32;border:1px solid rgba(255,255,255,.12);color:#e2e8f0;border-radius:10px;padding:7px 12px;font-size:13px;cursor:pointer}
.dp-btn-d{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#fca5a5;border-radius:8px;padding:5px 10px;font-size:12px;cursor:pointer}
.dp-row{display:grid;grid-template-columns:1fr 220px 100px auto;gap:8px;align-items:center;padding:10px 20px;border-bottom:1px solid rgba(255,255,255,.04)}
.dp-row:last-child{border-bottom:none}
@media(max-width:760px){.dp-row{grid-template-columns:1fr;}}
.dp-table{width:100%;border-collapse:collapse}
.dp-table th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;padding:10px 16px;border-bottom:1px solid rgba(255,255,255,.06)}
.dp-table td{padding:9px 16px;border-bottom:1px solid rgba(255,255,255,.04);font-size:13px;color:#e2e8f0;vertical-align:middle}
.dp-table tr:last-child td{border-bottom:none}
.dp-table tbody tr:hover{background:rgba(59,130,246,.05)}
.dp-subnav{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px}
.dp-subnav a{font-size:13px;padding:6px 14px;border-radius:8px;color:#94a3b8;text-decoration:none;border:1px solid rgba(255,255,255,.06);transition:all .15s}
.dp-subnav a:hover,.dp-subnav a.active{background:rgba(59,130,246,.12);color:#60a5fa;border-color:rgba(59,130,246,.3)}
.dp-warn{background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:10px;padding:12px 20px;font-size:13px;color:#fcd34d;margin-bottom:0}
</style>

<div id="dp-pg">

<div class="dp-subnav">
  <a href="/admin/painel-obras"><i class="bi bi-speedometer2"></i> Dashboard</a>
  <a href="/admin/painel-obras/obras"><i class="bi bi-buildings"></i> Obras</a>
  <a href="/admin/painel-obras/orcamento"><i class="bi bi-calculator"></i> Orçamento</a>
  <a href="/admin/painel-obras/planilha"><i class="bi bi-table"></i> Planilha de Controle</a>
  <a href="/admin/painel-obras/depara" class="active"><i class="bi bi-arrow-left-right"></i> De-para Categorias</a>
  <a href="/admin/painel-obras/pauta"><i class="bi bi-calendar-check"></i> Pauta de Revisão</a>
</div>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
  <h5 style="margin:0;color:#f8fafc">De-para: Categorias Nibo → Categoria de Obra</h5>
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
    <select id="dp-company" class="dp-inp" style="max-width:200px" onchange="location.href='/admin/painel-obras/depara?company='+this.value">
      <?php foreach($companies as $k=>$l): ?>
      <option value="<?=$k?>" <?=$k===$company?'selected':''?>><?=htmlspecialchars($l)?></option>
      <?php endforeach; ?>
    </select>
    <button class="dp-btn-g" onclick="dpSync()"><i class="bi bi-arrow-repeat"></i> Atualizar do Nibo</button>
  </div>
</div>

<!-- Não mapeadas -->
<?php $totalNao=count($naoMapeadas)+count($semEntrada); ?>
<?php if($totalNao>0): ?>
<div class="dp-card">
  <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid rgba(255,255,255,.06);flex-wrap:wrap;gap:8px">
    <span style="color:#fcd34d;font-size:14px;font-weight:600">
      <i class="bi bi-exclamation-triangle-fill"></i> <?=$totalNao?> categori<?=$totalNao===1?'a':'as'?> não mapeada<?=$totalNao===1?'':'s'?>
    </span>
    <button class="dp-btn-p" onclick="dpSalvarLote()"><i class="bi bi-check2-all"></i> Salvar todos</button>
  </div>
  <?php foreach($semEntrada as $cat): ?>
  <div class="dp-row" id="nm-<?=htmlspecialchars($cat['id'],ENT_ATTR)?>">
    <div>
      <div style="font-size:13px;font-weight:600;color:#e2e8f0"><?=htmlspecialchars($cat['nome'])?></div>
      <div style="font-size:11px;color:#64748b"><?=htmlspecialchars($cat['caminho']??'')?></div>
    </div>
    <select class="dp-inp dp-catobra" data-id="<?=htmlspecialchars($cat['id'],ENT_ATTR)?>" data-nome="<?=htmlspecialchars($cat['nome'],ENT_ATTR)?>">
      <option value="">— Selecionar —</option>
      <?php foreach($cats as $c): ?><option><?=htmlspecialchars($c)?></option><?php endforeach; ?>
    </select>
    <div style="display:flex;align-items:center;gap:6px;white-space:nowrap">
      <input type="checkbox" class="dp-ignorar" id="ig-<?=htmlspecialchars($cat['id'],ENT_ATTR)?>">
      <label for="ig-<?=htmlspecialchars($cat['id'],ENT_ATTR)?>" style="font-size:11px;color:#94a3b8;cursor:pointer">Ignorar</label>
    </div>
    <button class="dp-btn-g" style="padding:5px 10px;font-size:12px" onclick="dpSalvarUm('<?=htmlspecialchars($cat['id'],ENT_JS)?>','<?=htmlspecialchars($cat['nome'],ENT_JS)?>')">Salvar</button>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Mapeamentos existentes -->
<div class="dp-card">
  <div style="padding:14px 20px;border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;gap:12px">
    <span style="font-size:14px;font-weight:600;color:#f8fafc">Mapeamentos existentes</span>
    <span style="font-size:12px;color:#64748b"><?=count($deParaMap)?> categorias mapeadas</span>
  </div>
  <div style="overflow-x:auto">
    <table class="dp-table">
      <thead><tr><th>Categoria Nibo</th><th>Caminho</th><th>Categoria de Obra</th><th>Ignorar</th><th></th></tr></thead>
      <tbody>
        <?php foreach($deParaMap as $niboId=>$row): $ci=$catMap[$niboId]??null; ?>
        <tr id="dp-row-<?=$row['id']?>">
          <td style="font-weight:600"><?=htmlspecialchars($row['categoria_nibo_nome'])?></td>
          <td style="color:#64748b;font-size:12px"><?=htmlspecialchars($ci['caminho']??'—')?></td>
          <td>
            <select class="dp-inp dp-edit-cat" style="max-width:220px" data-id="<?=htmlspecialchars($niboId,ENT_ATTR)?>" data-nome="<?=htmlspecialchars($row['categoria_nibo_nome'],ENT_ATTR)?>">
              <option value="">— Sem mapeamento —</option>
              <?php foreach($cats as $c): ?><option <?=$row['categoria_obra']===$c?'selected':''?>><?=htmlspecialchars($c)?></option><?php endforeach; ?>
            </select>
          </td>
          <td><input type="checkbox" class="dp-edit-ignorar" data-id="<?=htmlspecialchars($niboId,ENT_ATTR)?>" <?=$row['ignorar_no_painel']?'checked':''?>></td>
          <td style="white-space:nowrap">
            <button class="dp-btn-g" style="padding:4px 10px;font-size:12px" onclick="dpAtualizar('<?=htmlspecialchars($niboId,ENT_JS)?>','<?=htmlspecialchars($row['categoria_nibo_nome'],ENT_JS)?>', this)"><i class="bi bi-check2"></i></button>
            <button class="dp-btn-d" style="margin-left:4px" onclick="dpDel(<?=$row['id']?>)"><i class="bi bi-trash3"></i></button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($deParaMap)): ?>
        <tr><td colspan="5" style="text-align:center;padding:32px;color:#94a3b8">Nenhum mapeamento ainda. Use a seção acima para mapear as categorias do Nibo.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</div>
<script>
function dpSync(){
  const co=document.getElementById('dp-company').value;
  fetch('/admin/painel-obras/depara/sync-categorias',{method:'POST',body:new URLSearchParams({company:co})})
    .then(r=>r.json()).then(res=>{if(res.ok){alert('Categorias sincronizadas!');location.reload();}else alert('Erro: '+(res.error||''));});
}
function dpSalvarUm(id,nome){
  const sel=document.querySelector('.dp-catobra[data-id="'+id+'"]');
  const ign=document.getElementById('ig-'+id);
  fetch('/admin/painel-obras/depara/salvar',{method:'POST',body:new URLSearchParams({categoria_nibo_id:id,categoria_nibo_nome:nome,categoria_obra:sel?.value||'',ignorar_no_painel:ign?.checked?'1':'0'})})
    .then(r=>r.json()).then(res=>{
      if(res.ok){const row=document.getElementById('nm-'+id);if(row){row.style.opacity='.4';row.style.pointerEvents='none';}}
      else alert('Erro: '+(res.error||''));
    });
}
function dpSalvarLote(){
  const rows=document.querySelectorAll('.dp-catobra');
  if(!rows.length) return;
  const p=new URLSearchParams();
  rows.forEach((sel,i)=>{
    const id=sel.dataset.id,nome=sel.dataset.nome;
    const ign=document.getElementById('ig-'+id);
    p.append('mapeamentos['+i+'][categoria_nibo_id]',id);
    p.append('mapeamentos['+i+'][categoria_nibo_nome]',nome);
    p.append('mapeamentos['+i+'][categoria_obra]',sel.value);
    p.append('mapeamentos['+i+'][ignorar_no_painel]',ign?.checked?'1':'0');
  });
  fetch('/admin/painel-obras/depara/salvar-lote',{method:'POST',body:p})
    .then(r=>r.json()).then(res=>{if(res.ok){alert(res.salvos+' salvo(s).');location.reload();}else alert('Erro: '+(res.error||''));});
}
function dpAtualizar(id,nome,btn){
  const tr=btn.closest('tr');
  const sel=tr.querySelector('.dp-edit-cat'),ign=tr.querySelector('.dp-edit-ignorar');
  fetch('/admin/painel-obras/depara/salvar',{method:'POST',body:new URLSearchParams({categoria_nibo_id:id,categoria_nibo_nome:nome,categoria_obra:sel?.value||'',ignorar_no_painel:ign?.checked?'1':'0'})})
    .then(r=>r.json()).then(res=>{if(res.ok){btn.innerHTML='<i class="bi bi-check2-all"></i>';setTimeout(()=>{btn.innerHTML='<i class="bi bi-check2"></i>';},1500);}else alert('Erro: '+(res.error||''));});
}
function dpDel(id){
  if(!confirm('Remover mapeamento?')) return;
  fetch('/admin/painel-obras/depara/delete',{method:'POST',body:new URLSearchParams({id})}).then(r=>r.json()).then(res=>{if(res.ok)location.reload();});
}
</script>
<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
