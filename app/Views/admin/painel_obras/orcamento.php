<?php $pageTitle = 'Orçamento por Obra'; $currentPage = 'painel_obras'; ?>
<?php ob_start(); ?>
<link rel="stylesheet" href="/assets/css/painel-obras-light.css">
<style>
/* Override do layout para tema claro nesta página */
.main-content{background:#f8f9fa!important} body{background:#f8f9fa!important}
.top-bar{background:#ffffff!important;color:#1e293b!important;box-shadow:none!important;border-bottom:1px solid #e2e8f0!important}
.top-bar h5{color:#0f172a!important} .top-bar .text-muted{color:#64748b!important}
.top-bar .badge{background:rgba(59,130,246,.2)!important;color:#2563eb!important}
#orc-pg{font-family:'Inter',system-ui,sans-serif;color:#1e293b;padding-bottom:48px}
.orc-card{background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:16px}
.orc-inp{background:#f8f9fa;border:1px solid #e2e8f0;color:#1e293b;border-radius:8px;padding:8px 12px;width:100%;outline:none;font-size:14px}
.orc-inp:focus{border-color:#3b82f6} .orc-inp option{background:#ffffff}
.orc-label{font-size:12px;color:#64748b;margin-bottom:4px;display:block}
.orc-btn-p{background:linear-gradient(135deg,#3b82f6,#22d3ee);color:#fff;border:none;border-radius:10px;padding:8px 18px;font-size:13px;font-weight:600;cursor:pointer}
.orc-btn-g{background:#ffffff;border:1px solid #e2e8f0;color:#1e293b;border-radius:10px;padding:8px 14px;font-size:13px;cursor:pointer}
.orc-table{width:100%;border-collapse:collapse}
.orc-table th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#64748b;padding:10px 16px;border-bottom:1px solid #e2e8f0}
.orc-table td{padding:10px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#1e293b}
.orc-table tr:last-child td{border-bottom:none}
.orc-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1050;align-items:center;justify-content:center;padding:16px}
.orc-modal-box{background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;padding:28px}
.orc-subnav{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px}
.orc-subnav a{font-size:13px;padding:6px 14px;border-radius:8px;color:#64748b;text-decoration:none;border:1px solid #e2e8f0;transition:all .15s}
.orc-subnav a:hover,.orc-subnav a.active{background:rgba(59,130,246,.12);color:#2563eb;border-color:rgba(59,130,246,.3)}
</style>

<div id="orc-pg">

<div class="orc-subnav">
  <a href="/admin/painel-obras"><i class="bi bi-speedometer2"></i> Dashboard</a>
  <a href="/admin/painel-obras/obras"><i class="bi bi-buildings"></i> Obras</a>
  <a href="/admin/painel-obras/orcamento" class="active"><i class="bi bi-calculator"></i> Orçamento</a>
  <a href="/admin/painel-obras/planilha"><i class="bi bi-table"></i> Planilha de Controle</a>
  <a href="/admin/painel-obras/depara"><i class="bi bi-arrow-left-right"></i> De-para Categorias</a>
  <a href="/admin/painel-obras/pauta"><i class="bi bi-calendar-check"></i> Pauta de Revisão</a>
</div>

<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap">
  <h5 style="margin:0;color:#f8fafc">Orçamento por Obra e Categoria</h5>
  <select id="orc-obra-sel" class="orc-inp" style="max-width:340px" onchange="orcCarregar()">
    <option value="">— Selecionar obra —</option>
    <?php foreach($obras as $o): ?>
    <option value="<?=$o['id']?>"><?=htmlspecialchars($o['codigo'].' — '.$o['nome'])?></option>
    <?php endforeach; ?>
  </select>
</div>

<div id="orc-area" style="display:none">
  <div class="orc-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:10px">
      <div>
        <span style="font-size:14px;font-weight:600;color:#f8fafc">Versão vigente: <span id="orc-versao" style="color:#60a5fa">—</span></span>
        <span style="font-size:12px;color:#94a3b8;margin-left:12px">Vigência: <span id="orc-vigencia">—</span></span>
      </div>
      <button class="orc-btn-g" onclick="orcNovaVersao()"><i class="bi bi-plus-circle"></i> Nova revisão</button>
    </div>
    <table class="orc-table">
      <thead><tr><th style="width:65%">Categoria de Obra</th><th style="text-align:right">Valor Orçado</th></tr></thead>
      <tbody id="orc-tbody">
        <tr><td colspan="2" style="text-align:center;padding:24px;color:#94a3b8">Selecione uma obra acima.</td></tr>
      </tbody>
      <tfoot>
        <tr style="border-top:1px solid rgba(255,255,255,.1)">
          <td style="font-weight:700;color:#f8fafc;padding:10px 16px">Total</td>
          <td style="text-align:right;font-weight:700;color:#60a5fa;padding:10px 16px" id="orc-total">—</td>
        </tr>
      </tfoot>
    </table>
  </div>

  <div class="orc-card">
    <div style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;margin-bottom:12px">Histórico de Versões</div>
    <div id="orc-historico" style="color:#94a3b8;font-size:13px">—</div>
  </div>
</div>

<!-- Modal nova versão -->
<div id="orc-modal" class="orc-modal">
  <div class="orc-modal-box">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
      <h5 style="margin:0;color:#f8fafc">Novo Orçamento</h5>
      <button style="background:none;border:none;color:#94a3b8;font-size:20px;cursor:pointer" onclick="document.getElementById('orc-modal').style.display='none'">×</button>
    </div>
    <div style="margin-bottom:12px">
      <label class="orc-label">Data de vigência *</label>
      <input id="orc-vig" type="date" class="orc-inp">
    </div>
    <div id="orc-motivo-wrap" style="display:none;margin-bottom:12px">
      <label class="orc-label">Motivo da revisão * <small style="color:#64748b">(obrigatório a partir da v2)</small></label>
      <textarea id="orc-motivo" class="orc-inp" rows="2"></textarea>
    </div>
    <table class="orc-table" style="margin-bottom:12px">
      <thead><tr><th>Categoria</th><th style="text-align:right">Valor Orçado</th></tr></thead>
      <tbody>
        <?php foreach($categoriasObra as $cat): ?>
        <tr>
          <td><?=htmlspecialchars($cat)?></td>
          <td><input class="orc-inp orc-val" data-cat="<?=htmlspecialchars($cat)?>" placeholder="R$ 0,00" oninput="fmtMoeda(this);orcCalcTotal()" style="text-align:right"></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr><td style="font-weight:700;color:#f8fafc;padding:10px 16px">Total</td>
            <td style="text-align:right;font-weight:700;color:#60a5fa;padding:10px 16px" id="orc-mo-total">R$ 0,00</td></tr>
      </tfoot>
    </table>
    <div id="orc-err" style="color:#fca5a5;font-size:13px;display:none;margin-bottom:10px"></div>
    <div style="display:flex;gap:10px">
      <button class="orc-btn-p" onclick="orcSalvar()"><i class="bi bi-check2"></i> Salvar</button>
      <button class="orc-btn-g" onclick="document.getElementById('orc-modal').style.display='none'">Cancelar</button>
    </div>
  </div>
</div>

</div>
<script>
const BRL=v=>v==null?'—':new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL'}).format(v);
function orcCarregar(){
  const id=document.getElementById('orc-obra-sel').value;
  if(!id){document.getElementById('orc-area').style.display='none';return;}
  document.getElementById('orc-area').style.display='block';
  fetch('/admin/painel-obras/orcamento/data?obra_id='+id).then(r=>r.json()).then(res=>{
    if(!res.ok) return;
    document.getElementById('orc-versao').textContent=res.versao||'(nenhuma)';
    document.getElementById('orc-vigencia').textContent=(res.linhas[0]?.data_vigencia)||'—';
    const cats=<?=json_encode($categoriasObra)?>;
    const m={};res.linhas.forEach(l=>{m[l.categoria_obra]=l.valor_orcado;});
    let tot=0;
    document.getElementById('orc-tbody').innerHTML=cats.map(c=>{
      const v=m[c]||0; tot+=parseFloat(v);
      return `<tr><td style="padding:10px 16px">${c}</td><td style="text-align:right;padding:10px 16px">${v>0?BRL(v):'<span style="color:#64748b">—</span>'}</td></tr>`;
    }).join('');
    document.getElementById('orc-total').textContent=BRL(tot);
    if(res.historico?.length){
      document.getElementById('orc-historico').innerHTML=`<table class="orc-table"><thead><tr><th>Versão</th><th>Vigência</th><th style="text-align:right">Total</th><th>Motivo</th></tr></thead><tbody>${
        res.historico.map(h=>`<tr><td style="padding:8px 16px">v${h.versao}</td><td style="padding:8px 16px">${h.data_vigencia||'—'}</td><td style="text-align:right;padding:8px 16px">${BRL(h.total)}</td><td style="color:#94a3b8;padding:8px 16px">${h.motivo_revisao||'—'}</td></tr>`).join('')
      }</tbody></table>`;
    } else {document.getElementById('orc-historico').textContent='Nenhuma versão cadastrada ainda.';}
  });
}
function orcNovaVersao(){
  if(!document.getElementById('orc-obra-sel').value){alert('Selecione uma obra primeiro.');return;}
  const v=parseInt(document.getElementById('orc-versao').textContent)||0;
  document.getElementById('orc-motivo-wrap').style.display=v>=1?'block':'none';
  document.getElementById('orc-vig').value=new Date().toISOString().substring(0,10);
  document.querySelectorAll('.orc-val').forEach(i=>i.value='');
  document.getElementById('orc-mo-total').textContent='R$ 0,00';
  document.getElementById('orc-err').style.display='none';
  document.getElementById('orc-modal').style.display='flex';
}
function orcCalcTotal(){
  let t=0;document.querySelectorAll('.orc-val').forEach(i=>{t+=parseMoeda(i.value);});
  document.getElementById('orc-mo-total').textContent=BRL(t);
}
function orcSalvar(){
  const id=document.getElementById('orc-obra-sel').value;
  const v=parseInt(document.getElementById('orc-versao').textContent)||0;
  const motivo=document.getElementById('orc-motivo').value.trim();
  if(v>=1&&!motivo){const e=document.getElementById('orc-err');e.textContent='Motivo obrigatório a partir da v2.';e.style.display='block';return;}
  const p=new URLSearchParams({obra_id:id,data_vigencia:document.getElementById('orc-vig').value,motivo_revisao:motivo});
  document.querySelectorAll('.orc-val').forEach(i=>p.append('valores['+i.dataset.cat+']',i.value));
  fetch('/admin/painel-obras/orcamento/salvar',{method:'POST',body:p}).then(r=>r.json()).then(res=>{
    if(!res.ok){const e=document.getElementById('orc-err');e.textContent=res.error||'Erro';e.style.display='block';return;}
    document.getElementById('orc-modal').style.display='none';orcCarregar();
  });
}
function fmtMoeda(el){let v=el.value.replace(/\D/g,'');v=(parseInt(v||'0')/100).toFixed(2);el.value='R$ '+v.replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.');}
function parseMoeda(v){return parseFloat(String(v).replace(/[R$\s.]/g,'').replace(',','.'))||0;}
</script>
<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
