<?php $pageTitle = 'Lançamentos Financeiros'; $currentPage = 'painel_obras'; ?>
<?php ob_start(); ?>
<link rel="stylesheet" href="/assets/css/painel-obras-light.css">
<style>
.main-content{background:#f8f9fa!important} body{background:#f8f9fa!important}
.top-bar{background:#ffffff!important;color:#1e293b!important;box-shadow:none!important;border-bottom:1px solid #e2e8f0!important}
.top-bar h5{color:#0f172a!important} .top-bar .text-muted{color:#64748b!important}
.top-bar .badge{background:rgba(59,130,246,.2)!important;color:#2563eb!important}

#lanc-pg{font-family:'Inter',system-ui,sans-serif;color:#1e293b;padding-bottom:48px}
.lanc-card{background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:16px}
.lanc-inp{background:#f8f9fa;border:1px solid #e2e8f0;color:#1e293b;border-radius:8px;padding:8px 12px;width:100%;outline:none;font-size:14px}
.lanc-inp:focus{border-color:#3b82f6}
.lanc-label{font-size:12px;color:#64748b;margin-bottom:4px;display:block;font-weight:600}
.lanc-btn-p{background:linear-gradient(135deg,#3b82f6,#22d3ee);color:#fff;border:none;border-radius:10px;padding:10px 20px;font-size:13px;font-weight:600;cursor:pointer;transition:all .2s}
.lanc-btn-p:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(59,130,246,.3)}
.lanc-btn-g{background:#ffffff;border:1px solid #e2e8f0;color:#1e293b;border-radius:10px;padding:10px 18px;font-size:13px;cursor:pointer;transition:all .2s}
.lanc-btn-g:hover{border-color:#3b82f6;color:#3b82f6}
.lanc-btn-del{background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:8px;padding:6px 12px;font-size:12px;cursor:pointer}
.lanc-btn-del:hover{background:#fee2e2}
.lanc-table{width:100%;border-collapse:collapse}
.lanc-table th{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:#64748b;padding:12px 16px;border-bottom:1px solid #e2e8f0;text-align:left}
.lanc-table td{padding:12px 16px;border-bottom:1px solid #f1f5f9;font-size:13px;color:#1e293b}
.lanc-table tr:last-child td{border-bottom:none}
.lanc-table tr:hover{background:#f8f9fa}
.lanc-badge{display:inline-block;padding:4px 10px;border-radius:6px;font-size:11px;font-weight:600}
.lanc-badge-realizado{background:#dcfce7;color:#166534}
.lanc-badge-previsto{background:#fef3c7;color:#92400e}
.lanc-badge-cancelado{background:#fee2e2;color:#991b1b}
.lanc-badge-receita{background:#dbeafe;color:#1e40af}
.lanc-badge-despesa{background:#fee2e2;color:#991b1b}
.lanc-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1050;align-items:center;justify-content:center;padding:16px}
.lanc-modal-box{background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;width:100%;max-width:640px;max-height:90vh;overflow-y:auto;padding:28px}
.lanc-subnav{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px}
.lanc-subnav a{font-size:13px;padding:6px 14px;border-radius:8px;color:#64748b;text-decoration:none;border:1px solid #e2e8f0;transition:all .15s}
.lanc-subnav a:hover,.lanc-subnav a.active{background:rgba(59,130,246,.12);color:#2563eb;border-color:rgba(59,130,246,.3)}
.lanc-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.lanc-summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-bottom:20px}
.lanc-summary-card{background:linear-gradient(135deg,#f8f9fa,#ffffff);border:1px solid #e2e8f0;border-radius:12px;padding:16px}
.lanc-summary-value{font-size:28px;font-weight:700;margin-bottom:4px}
.lanc-summary-label{font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.5px}
.lanc-filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px}
</style>

<div id="lanc-pg">

<div class="lanc-subnav">
  <a href="/admin/painel-obras"><i class="bi bi-speedometer2"></i> Dashboard</a>
  <a href="/admin/painel-obras/obras"><i class="bi bi-buildings"></i> Obras</a>
  <a href="/admin/painel-obras/lancamentos" class="active"><i class="bi bi-cash-stack"></i> Lançamentos</a>
  <a href="/admin/painel-obras/orcamento"><i class="bi bi-calculator"></i> Orçamento</a>
  <a href="/admin/painel-obras/planilha"><i class="bi bi-table"></i> Planilha de Controle</a>
  <a href="/admin/painel-obras/depara"><i class="bi bi-arrow-left-right"></i> De-para Categorias</a>
  <a href="/admin/painel-obras/pauta"><i class="bi bi-calendar-check"></i> Pauta de Revisão</a>
</div>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px">
  <h5 style="margin:0;color:#0f172a">Gestão de Lançamentos Financeiros</h5>
  <div style="display:flex;gap:10px">
    <button class="lanc-btn-g" onclick="lancAbrirModalCategoria()"><i class="bi bi-tags"></i> Gerenciar Categorias</button>
    <button class="lanc-btn-g" onclick="lancMostrarNibo()"><i class="bi bi-cloud-arrow-down"></i> Ver Lançamentos Nibo</button>
    <button class="lanc-btn-p" onclick="lancAbrirModalNovo()"><i class="bi bi-plus-circle"></i> Novo Lançamento</button>
  </div>
</div>

<!-- Summary Cards -->
<div class="lanc-summary" id="lanc-summary">
  <div class="lanc-summary-card">
    <div class="lanc-summary-value" style="color:#3b82f6" id="sum-contratado">R$ 0,00</div>
    <div class="lanc-summary-label">Valor Contratado Total</div>
  </div>
  <div class="lanc-summary-card">
    <div class="lanc-summary-value" style="color:#dc2626" id="sum-realizado">R$ 0,00</div>
    <div class="lanc-summary-label">Total Realizado (Manual + Nibo)</div>
    <div style="font-size:11px;color:#64748b;margin-top:4px">
      <span id="sum-realizado-manual">R$ 0,00</span> (manual) + 
      <span id="sum-realizado-nibo">R$ 0,00</span> (Nibo)
    </div>
  </div>
  <div class="lanc-summary-card">
    <div class="lanc-summary-value" style="color:#16a34a" id="sum-saldo">R$ 0,00</div>
    <div class="lanc-summary-label">Saldo Disponível</div>
  </div>
  <div class="lanc-summary-card">
    <div class="lanc-summary-value" style="color:#ca8a04" id="sum-previsto">R$ 0,00</div>
    <div class="lanc-summary-label">Previsto (Não Realizado)</div>
  </div>
</div>

<!-- Filtros -->
<div class="lanc-card">
  <div class="lanc-filters">
    <div style="flex:1;min-width:200px">
      <label class="lanc-label">Obra</label>
      <select id="lanc-obra-sel" class="lanc-inp" onchange="lancCarregar()">
        <option value="">— Todas as obras —</option>
        <?php foreach($obras as $o): ?>
        <option value="<?=$o['id']?>"><?=htmlspecialchars($o['codigo'].' — '.$o['nome'])?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="flex:1;min-width:180px">
      <label class="lanc-label">Categoria</label>
      <select id="lanc-cat-sel" class="lanc-inp" onchange="lancCarregar()">
        <option value="">— Todas —</option>
        <!-- Populado dinamicamente -->
      </select>
    </div>
    <div style="flex:1;min-width:160px">
      <label class="lanc-label">Status</label>
      <select id="lanc-status-sel" class="lanc-inp" onchange="lancCarregar()">
        <option value="">— Todos —</option>
        <option value="Previsto">Previsto</option>
        <option value="Realizado">Realizado</option>
        <option value="Cancelado">Cancelado</option>
      </select>
    </div>
    <div style="flex:1;min-width:160px">
      <label class="lanc-label">Tipo</label>
      <select id="lanc-tipo-sel" class="lanc-inp" onchange="lancCarregar()">
        <option value="">— Todos —</option>
        <option value="Despesa">Despesa</option>
        <option value="Receita">Receita</option>
      </select>
    </div>
  </div>
</div>

<!-- Tabela de Lançamentos -->
<div class="lanc-card">
  <table class="lanc-table">
    <thead>
      <tr>
        <th>Data</th>
        <th>Obra</th>
        <th>Categoria</th>
        <th>Descrição</th>
        <th style="text-align:right">Valor</th>
        <th>Tipo</th>
        <th>Status</th>
        <th style="text-align:center">Ações</th>
      </tr>
    </thead>
    <tbody id="lanc-tbody">
      <tr><td colspan="8" style="text-align:center;padding:48px;color:#94a3b8">Carregando...</td></tr>
    </tbody>
  </table>
</div>

</div>

<!-- Modal Novo Lançamento -->
<div id="lanc-modal-novo" class="lanc-modal">
  <div class="lanc-modal-box">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <h5 style="margin:0;color:#0f172a" id="lanc-modal-title">Novo Lançamento</h5>
      <button style="background:none;border:none;color:#64748b;font-size:24px;cursor:pointer" onclick="lancFecharModalNovo()">×</button>
    </div>
    
    <input type="hidden" id="lanc-edit-id">
    
    <div style="margin-bottom:14px">
      <label class="lanc-label">Obra *</label>
      <select id="lanc-modal-obra" class="lanc-inp">
        <option value="">— Selecione —</option>
        <?php foreach($obras as $o): ?>
        <option value="<?=$o['id']?>" data-contratado="<?=$o['valor_contratado']?>"><?=htmlspecialchars($o['codigo'].' — '.$o['nome'])?></option>
        <?php endforeach; ?>
      </select>
    </div>
    
    <div style="margin-bottom:14px">
      <label class="lanc-label">Categoria *</label>
      <select id="lanc-modal-cat" class="lanc-inp">
        <option value="">— Selecione —</option>
        <!-- Populado dinamicamente -->
      </select>
    </div>
    
    <div class="lanc-grid-2" style="margin-bottom:14px">
      <div>
        <label class="lanc-label">Tipo *</label>
        <select id="lanc-modal-tipo" class="lanc-inp">
          <option value="Despesa">Despesa</option>
          <option value="Receita">Receita</option>
        </select>
      </div>
      <div>
        <label class="lanc-label">Status *</label>
        <select id="lanc-modal-status" class="lanc-inp">
          <option value="Previsto">Previsto</option>
          <option value="Realizado">Realizado</option>
          <option value="Cancelado">Cancelado</option>
        </select>
      </div>
    </div>
    
    <div style="margin-bottom:14px">
      <label class="lanc-label">Descrição *</label>
      <input type="text" id="lanc-modal-desc" class="lanc-inp" placeholder="Ex: Pagamento de fundação">
    </div>
    
    <div class="lanc-grid-2" style="margin-bottom:14px">
      <div>
        <label class="lanc-label">Valor *</label>
        <input type="text" id="lanc-modal-valor" class="lanc-inp" placeholder="R$ 0,00" oninput="fmtMoeda(this)">
      </div>
      <div>
        <label class="lanc-label">Data do Lançamento *</label>
        <input type="date" id="lanc-modal-data" class="lanc-inp">
      </div>
    </div>
    
    <div style="margin-bottom:14px">
      <label class="lanc-label">Data de Vencimento</label>
      <input type="date" id="lanc-modal-venc" class="lanc-inp">
    </div>
    
    <div style="margin-bottom:14px">
      <label class="lanc-label">Observações</label>
      <textarea id="lanc-modal-obs" class="lanc-inp" rows="3" placeholder="Informações adicionais..."></textarea>
    </div>
    
    <div id="lanc-err" style="color:#dc2626;font-size:13px;display:none;margin-bottom:12px"></div>
    
    <div style="display:flex;gap:10px">
      <button class="lanc-btn-p" onclick="lancSalvar()"><i class="bi bi-check2"></i> Salvar</button>
      <button class="lanc-btn-g" onclick="lancFecharModalNovo()">Cancelar</button>
    </div>
  </div>
</div>

<!-- Modal Gerenciar Categorias -->
<div id="lanc-modal-cat" class="lanc-modal">
  <div class="lanc-modal-box">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <h5 style="margin:0;color:#0f172a">Gerenciar Categorias</h5>
      <button style="background:none;border:none;color:#64748b;font-size:24px;cursor:pointer" onclick="lancFecharModalCategoria()">×</button>
    </div>
    
    <div style="margin-bottom:16px">
      <button class="lanc-btn-p" onclick="lancNovaCat()"><i class="bi bi-plus"></i> Nova Categoria</button>
    </div>
    
    <div id="lanc-cat-form" style="display:none;background:#f8f9fa;border:1px solid #e2e8f0;border-radius:8px;padding:16px;margin-bottom:16px">
      <input type="hidden" id="cat-edit-id">
      <div style="margin-bottom:12px">
        <label class="lanc-label">Nome da Categoria *</label>
        <input type="text" id="cat-nome" class="lanc-inp" placeholder="Ex: Material de Construção">
      </div>
      <div style="margin-bottom:12px">
        <label class="lanc-label">Código (único) *</label>
        <input type="text" id="cat-codigo" class="lanc-inp" placeholder="Ex: MAT_CONSTRUCAO">
      </div>
      <div style="margin-bottom:12px">
        <label class="lanc-label">Tipo de Obra</label>
        <select id="cat-tipo" class="lanc-inp">
          <option value="Ambos">Ambos</option>
          <option value="Construcao">Construção</option>
          <option value="Reforma">Reforma</option>
        </select>
      </div>
      <div style="display:flex;gap:10px">
        <button class="lanc-btn-p" onclick="catSalvar()"><i class="bi bi-check2"></i> Salvar</button>
        <button class="lanc-btn-g" onclick="catCancelar()">Cancelar</button>
      </div>
    </div>
    
    <table class="lanc-table">
      <thead>
        <tr>
          <th>Nome</th>
          <th>Código</th>
          <th>Tipo</th>
          <th style="text-align:center">Ações</th>
        </tr>
      </thead>
      <tbody id="cat-tbody">
        <tr><td colspan="4" style="text-align:center;padding:24px;color:#94a3b8">Carregando...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Lançamentos Nibo -->
<div id="lanc-modal-nibo" class="lanc-modal">
  <div class="lanc-modal-box" style="max-width:900px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <h5 style="margin:0;color:#0f172a">Lançamentos do Nibo (Pagos)</h5>
      <button style="background:none;border:none;color:#64748b;font-size:24px;cursor:pointer" onclick="lancFecharModalNibo()">×</button>
    </div>
    
    <div style="background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;padding:12px;margin-bottom:16px">
      <div style="font-size:12px;color:#64748b;margin-bottom:4px">Estes lançamentos vêm direto do Nibo e <strong>já estão sendo debitados</strong> do valor contratado.</div>
      <div style="font-size:12px;color:#64748b">Total pago (Nibo): <strong style="color:#dc2626" id="nibo-total-pago">R$ 0,00</strong></div>
    </div>
    
    <div style="margin-bottom:12px">
      <input type="text" id="nibo-search" class="lanc-inp" placeholder="🔍 Buscar por descrição, fornecedor..." oninput="niboFiltrar()">
    </div>
    
    <div style="max-height:500px;overflow-y:auto">
      <table class="lanc-table">
        <thead>
          <tr>
            <th>Data</th>
            <th>Obra</th>
            <th>Descrição</th>
            <th>Fornecedor</th>
            <th style="text-align:right">Valor</th>
            <th>Categoria</th>
          </tr>
        </thead>
        <tbody id="nibo-tbody">
          <tr><td colspan="6" style="text-align:center;padding:24px;color:#94a3b8">Carregando...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
const BRL=v=>v==null?'—':new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL'}).format(v);
function fmtMoeda(el){let v=el.value.replace(/\D/g,'');v=(parseInt(v||'0')/100).toFixed(2);el.value='R$ '+v.replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.');}
function parseMoeda(v){return parseFloat(String(v).replace(/[R$\s.]/g,'').replace(',','.'))||0;}
function fmtData(d){if(!d)return'—';const p=d.split('-');return`${p[2]}/${p[1]}/${p[0]}`;}

let _lancamentos=[];
let _categorias=[];

// Carregar categorias
async function carregarCategorias(){
  try{
    const r=await fetch('/admin/painel-obras/categorias');
    const res=await r.json();
    _categorias=res.categorias||[];
    const s1=document.getElementById('lanc-cat-sel');
    const s2=document.getElementById('lanc-modal-cat');
    const opts=_categorias.map(c=>`<option value="${c.id}">${c.nome}</option>`).join('');
    s1.innerHTML='<option value="">— Todas —</option>'+opts;
    s2.innerHTML='<option value="">— Selecione —</option>'+opts;
  }catch(e){console.error('Erro ao carregar categorias:',e);}
}

// Carregar lançamentos
async function lancCarregar(){
  const obra=document.getElementById('lanc-obra-sel').value;
  const cat=document.getElementById('lanc-cat-sel').value;
  const status=document.getElementById('lanc-status-sel').value;
  const tipo=document.getElementById('lanc-tipo-sel').value;
  const q=new URLSearchParams({obra_id:obra,categoria_id:cat,status:status,tipo:tipo});
  try{
    const r=await fetch('/admin/painel-obras/lancamentos/listar?'+q);
    const res=await r.json();
    _lancamentos=res.lancamentos||[];
    renderLancamentos();
    renderSummary(res.summary||{});
  }catch(e){
    console.error('Erro ao carregar lançamentos:',e);
    document.getElementById('lanc-tbody').innerHTML='<tr><td colspan="8" style="text-align:center;padding:24px;color:#ef4444">Erro ao carregar dados</td></tr>';
  }
}

function renderLancamentos(){
  const tb=document.getElementById('lanc-tbody');
  if(!_lancamentos.length){
    tb.innerHTML='<tr><td colspan="8" style="text-align:center;padding:32px;color:#94a3b8">Nenhum lançamento encontrado</td></tr>';
    return;
  }
  tb.innerHTML=_lancamentos.map(l=>{
    const catNome=(_categorias.find(c=>c.id==l.categoria_id)||{}).nome||'—';
    const badgeStatus=l.status=='Realizado'?'lanc-badge-realizado':l.status=='Cancelado'?'lanc-badge-cancelado':'lanc-badge-previsto';
    const badgeTipo=l.tipo=='Receita'?'lanc-badge-receita':'lanc-badge-despesa';
    const valorColor=l.tipo=='Receita'?'#16a34a':'#dc2626';
    const valorPrefix=l.tipo=='Receita'?'+':'-';
    return`<tr>
      <td>${fmtData(l.data_lancamento)}</td>
      <td><small style="color:#64748b">${l.obra_codigo||'—'}</small><br>${l.obra_nome||'—'}</td>
      <td>${catNome}</td>
      <td>${l.descricao}</td>
      <td style="text-align:right;font-weight:600;color:${valorColor}">${valorPrefix} ${BRL(l.valor)}</td>
      <td><span class="lanc-badge ${badgeTipo}">${l.tipo}</span></td>
      <td><span class="lanc-badge ${badgeStatus}">${l.status}</span></td>
      <td style="text-align:center">
        <button class="lanc-btn-g" style="padding:6px 10px;font-size:11px" onclick="lancEditar(${l.id})" title="Editar"><i class="bi bi-pencil"></i></button>
        <button class="lanc-btn-del" style="padding:6px 10px;font-size:11px" onclick="lancDeletar(${l.id})" title="Deletar"><i class="bi bi-trash"></i></button>
      </td>
    </tr>`;
  }).join('');
}

function renderSummary(s){
  document.getElementById('sum-contratado').textContent=BRL(s.contratado||0);
  document.getElementById('sum-realizado').textContent=BRL(s.realizado||0);
  document.getElementById('sum-realizado-manual').textContent=BRL(s.realizado_manual||0);
  document.getElementById('sum-realizado-nibo').textContent=BRL(s.realizado_nibo||0);
  document.getElementById('sum-saldo').textContent=BRL(s.saldo||0);
  document.getElementById('sum-previsto').textContent=BRL(s.previsto||0);
}

// Modal Novo/Editar
function lancAbrirModalNovo(){
  document.getElementById('lanc-modal-title').textContent='Novo Lançamento';
  document.getElementById('lanc-edit-id').value='';
  ['lanc-modal-obra','lanc-modal-cat','lanc-modal-tipo','lanc-modal-status','lanc-modal-desc','lanc-modal-valor','lanc-modal-data','lanc-modal-venc','lanc-modal-obs'].forEach(id=>document.getElementById(id).value='');
  document.getElementById('lanc-modal-tipo').value='Despesa';
  document.getElementById('lanc-modal-status').value='Previsto';
  document.getElementById('lanc-modal-data').value=new Date().toISOString().substring(0,10);
  document.getElementById('lanc-err').style.display='none';
  document.getElementById('lanc-modal-novo').style.display='flex';
}

function lancFecharModalNovo(){
  document.getElementById('lanc-modal-novo').style.display='none';
}

function lancEditar(id){
  const l=_lancamentos.find(x=>x.id==id);
  if(!l)return;
  document.getElementById('lanc-modal-title').textContent='Editar Lançamento';
  document.getElementById('lanc-edit-id').value=l.id;
  document.getElementById('lanc-modal-obra').value=l.obra_id;
  document.getElementById('lanc-modal-cat').value=l.categoria_id;
  document.getElementById('lanc-modal-tipo').value=l.tipo;
  document.getElementById('lanc-modal-status').value=l.status;
  document.getElementById('lanc-modal-desc').value=l.descricao;
  document.getElementById('lanc-modal-valor').value=BRL(l.valor);
  document.getElementById('lanc-modal-data').value=l.data_lancamento;
  document.getElementById('lanc-modal-venc').value=l.data_vencimento||'';
  document.getElementById('lanc-modal-obs').value=l.observacoes||'';
  document.getElementById('lanc-err').style.display='none';
  document.getElementById('lanc-modal-novo').style.display='flex';
}

async function lancSalvar(){
  const id=document.getElementById('lanc-edit-id').value;
  const obra=document.getElementById('lanc-modal-obra').value;
  const cat=document.getElementById('lanc-modal-cat').value;
  const tipo=document.getElementById('lanc-modal-tipo').value;
  const status=document.getElementById('lanc-modal-status').value;
  const desc=document.getElementById('lanc-modal-desc').value.trim();
  const valor=parseMoeda(document.getElementById('lanc-modal-valor').value);
  const data=document.getElementById('lanc-modal-data').value;
  const venc=document.getElementById('lanc-modal-venc').value;
  const obs=document.getElementById('lanc-modal-obs').value.trim();
  
  if(!obra||!cat||!desc||valor<=0||!data){
    const e=document.getElementById('lanc-err');
    e.textContent='Preencha todos os campos obrigatórios.';
    e.style.display='block';
    return;
  }
  
  const p=new URLSearchParams({id:id,obra_id:obra,categoria_id:cat,tipo:tipo,status:status,descricao:desc,valor:valor,data_lancamento:data,data_vencimento:venc,observacoes:obs});
  try{
    const r=await fetch('/admin/painel-obras/lancamentos/salvar',{method:'POST',body:p});
    const res=await r.json();
    if(!res.ok){
      const e=document.getElementById('lanc-err');
      e.textContent=res.error||'Erro ao salvar';
      e.style.display='block';
      return;
    }
    lancFecharModalNovo();
    lancCarregar();
  }catch(e){
    console.error('Erro ao salvar:',e);
    const er=document.getElementById('lanc-err');
    er.textContent='Erro de conexão';
    er.style.display='block';
  }
}

async function lancDeletar(id){
  if(!confirm('Confirma exclusão deste lançamento?'))return;
  try{
    const r=await fetch('/admin/painel-obras/lancamentos/deletar',{method:'POST',body:new URLSearchParams({id:id})});
    const res=await r.json();
    if(!res.ok){alert(res.error||'Erro ao deletar');return;}
    lancCarregar();
  }catch(e){console.error('Erro ao deletar:',e);alert('Erro de conexão');}
}

// Modal Categorias
function lancAbrirModalCategoria(){
  carregarCatList();
  document.getElementById('lanc-modal-cat').style.display='flex';
}

function lancFecharModalCategoria(){
  document.getElementById('lanc-modal-cat').style.display='none';
}

function lancNovaCat(){
  document.getElementById('cat-edit-id').value='';
  document.getElementById('cat-nome').value='';
  document.getElementById('cat-codigo').value='';
  document.getElementById('cat-tipo').value='Ambos';
  document.getElementById('lanc-cat-form').style.display='block';
}

function catCancelar(){
  document.getElementById('lanc-cat-form').style.display='none';
}

async function carregarCatList(){
  try{
    const r=await fetch('/admin/painel-obras/categorias');
    const res=await r.json();
    const tb=document.getElementById('cat-tbody');
    if(!res.categorias||!res.categorias.length){
      tb.innerHTML='<tr><td colspan="4" style="text-align:center;padding:24px;color:#94a3b8">Nenhuma categoria</td></tr>';
      return;
    }
    tb.innerHTML=res.categorias.map(c=>`<tr>
      <td>${c.nome}</td>
      <td><code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;font-size:11px">${c.codigo}</code></td>
      <td>${c.tipo_obra}</td>
      <td style="text-align:center">
        <button class="lanc-btn-g" style="padding:6px 10px;font-size:11px" onclick="catEditar(${c.id},'${c.nome}','${c.codigo}','${c.tipo_obra}')"><i class="bi bi-pencil"></i></button>
        <button class="lanc-btn-del" style="padding:6px 10px;font-size:11px" onclick="catDeletar(${c.id})"><i class="bi bi-trash"></i></button>
      </td>
    </tr>`).join('');
  }catch(e){console.error('Erro ao carregar categorias:',e);}
}

function catEditar(id,nome,codigo,tipo){
  document.getElementById('cat-edit-id').value=id;
  document.getElementById('cat-nome').value=nome;
  document.getElementById('cat-codigo').value=codigo;
  document.getElementById('cat-tipo').value=tipo;
  document.getElementById('lanc-cat-form').style.display='block';
}

async function catSalvar(){
  const id=document.getElementById('cat-edit-id').value;
  const nome=document.getElementById('cat-nome').value.trim();
  const codigo=document.getElementById('cat-codigo').value.trim();
  const tipo=document.getElementById('cat-tipo').value;
  if(!nome||!codigo){alert('Preencha nome e código');return;}
  const p=new URLSearchParams({id:id,nome:nome,codigo:codigo,tipo_obra:tipo});
  try{
    const r=await fetch('/admin/painel-obras/categorias/salvar',{method:'POST',body:p});
    const res=await r.json();
    if(!res.ok){alert(res.error||'Erro ao salvar');return;}
    catCancelar();
    carregarCatList();
    carregarCategorias();
  }catch(e){console.error('Erro ao salvar categoria:',e);alert('Erro de conexão');}
}

async function catDeletar(id){
  if(!confirm('Confirma exclusão desta categoria?'))return;
  try{
    const r=await fetch('/admin/painel-obras/categorias/deletar',{method:'POST',body:new URLSearchParams({id:id})});
    const res=await r.json();
    if(!res.ok){alert(res.error||'Erro ao deletar');return;}
    carregarCatList();
    carregarCategorias();
  }catch(e){console.error('Erro ao deletar:',e);alert('Erro de conexão');}
}

// Modal Lançamentos Nibo
let _niboLancamentos=[];
let _niboLancamentosFiltrados=[];

async function lancMostrarNibo(){
  const obraId=document.getElementById('lanc-obra-sel').value;
  try{
    const q=new URLSearchParams({periodo:new Date().toISOString().substring(0,7),company:'brooks'});
    const r=await fetch('/admin/painel-obras/dashboard/data?'+q);
    const res=await r.json();
    
    // Extrai lançamentos pagos
    _niboLancamentos=[];
    let totalPago=0;
    
    if(res.lancamentos_por_obra){
      for(const [id,lancs] of Object.entries(res.lancamentos_por_obra)){
        if(!obraId || parseInt(obraId)===parseInt(id)){
          (lancs.pagos||[]).forEach(l=>{
            _niboLancamentos.push(l);
            totalPago+=(parseFloat(l.valor_rateado)||0);
          });
        }
      }
    }
    
    _niboLancamentosFiltrados=[..._niboLancamentos];
    document.getElementById('nibo-total-pago').textContent=BRL(totalPago);
    document.getElementById('nibo-search').value='';
    niboRenderizar();
    document.getElementById('lanc-modal-nibo').style.display='flex';
  }catch(e){
    console.error('Erro ao carregar lançamentos Nibo:',e);
    alert('Erro ao carregar dados do Nibo');
  }
}

function niboFiltrar(){
  const termo=document.getElementById('nibo-search').value.toLowerCase();
  if(!termo){
    _niboLancamentosFiltrados=[..._niboLancamentos];
  }else{
    _niboLancamentosFiltrados=_niboLancamentos.filter(l=>{
      const desc=(l.description||'').toLowerCase();
      const forn=(l.contact_name||'').toLowerCase();
      const cat=(l.categoria_obra||l.categoria_nibo_nome||'').toLowerCase();
      return desc.includes(termo)||forn.includes(termo)||cat.includes(termo);
    });
  }
  niboRenderizar();
}

function niboRenderizar(){
  const tb=document.getElementById('nibo-tbody');
  if(!_niboLancamentosFiltrados.length){
    tb.innerHTML='<tr><td colspan="6" style="text-align:center;padding:32px;color:#94a3b8">Nenhum lançamento pago encontrado</td></tr>';
    return;
  }
  tb.innerHTML=_niboLancamentosFiltrados.map(l=>{
    const obra=`<small style="color:#64748b">${l.obra_codigo||'—'}</small><br>${l.obra_nome||'—'}`;
    const cat=l.categoria_obra||l.categoria_nibo_nome||'Sem categoria';
    return`<tr>
      <td>${fmtData(l.due_date||l.data_lancamento)}</td>
      <td>${obra}</td>
      <td style="max-width:250px">${l.description||'—'}</td>
      <td>${l.contact_name||'—'}</td>
      <td style="text-align:right;font-weight:600;color:#dc2626">- ${BRL(l.valor_rateado)}</td>
      <td><small style="background:#f1f5f9;padding:2px 6px;border-radius:4px;font-size:11px">${cat}</small></td>
    </tr>`;
  }).join('');
}

function lancFecharModalNibo(){
  document.getElementById('lanc-modal-nibo').style.display='none';
}

// Init
document.addEventListener('DOMContentLoaded',()=>{
  carregarCategorias();
  lancCarregar();
});
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
