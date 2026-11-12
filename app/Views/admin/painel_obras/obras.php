<?php
$pageTitle   = 'Obras — Painel Financeiro';
$currentPage = 'painel_obras';
ob_start();
?>
<style>
#po-obras-wrap { font-family:'Inter',system-ui,sans-serif; font-variant-numeric:tabular-nums; }
.po-dark { background:#151f32; border:1px solid rgba(255,255,255,.06); border-radius:12px; color:#e2e8f0; }
.po-input { background:#0f1829; border:1px solid rgba(255,255,255,.1); color:#e2e8f0; border-radius:8px; padding:8px 12px; width:100%; outline:none; font-size:14px; }
.po-input:focus { border-color:#3b82f6; }
.po-label { font-size:12px; color:#94a3b8; margin-bottom:4px; display:block; }
.po-btn-p { background:linear-gradient(135deg,#3b82f6,#22d3ee); color:#fff; border:none; border-radius:10px; padding:8px 18px; font-size:13px; font-weight:600; cursor:pointer; }
.po-btn-g { background:#151f32; border:1px solid rgba(255,255,255,.1); color:#e2e8f0; border-radius:10px; padding:8px 14px; font-size:13px; cursor:pointer; }
.po-btn-d { background:rgba(239,68,68,.1); border:1px solid rgba(239,68,68,.2); color:#fca5a5; border-radius:8px; padding:5px 10px; font-size:12px; cursor:pointer; }
.po-pill { display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px; }
.po-pill.ok { color:#6ee7b7;background:rgba(16,185,129,.14); }
.po-pill.warn { color:#fcd34d;background:rgba(245,158,11,.14); }
.po-pill.danger { color:#fca5a5;background:rgba(239,68,68,.14); }
</style>

<div id="po-obras-wrap">

<nav aria-label="breadcrumb" style="margin-bottom:16px">
  <ol class="breadcrumb" style="background:none;padding:0;margin:0;font-size:13px;color:#94a3b8">
    <li class="breadcrumb-item"><a href="/admin/painel-obras" style="color:#60a5fa">Painel de Obras</a></li>
    <li class="breadcrumb-item active" style="color:#e2e8f0">Obras</li>
  </ol>
</nav>

<?php if ($pendentes > 0): ?>
<div class="alert" style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:10px;color:#fcd34d;font-size:13px;margin-bottom:16px;padding:10px 16px">
  <i class="bi bi-exclamation-triangle-fill"></i>
  <?= $pendentes ?> obra<?= $pendentes !== 1 ? 's' : '' ?> com informações pendentes (contratado, orçamento, centro de custo ou planilha do mês).
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h5 class="mb-0">Obras Cadastradas</h5>
  <button class="po-btn-p" onclick="document.getElementById('mo-nova').style.display='flex'">
    <i class="bi bi-plus-circle"></i> Nova Obra
  </button>
</div>

<div class="d-flex gap-2 mb-3 flex-wrap">
  <input type="text" id="ob-busca" class="po-input" style="max-width:280px" placeholder="Buscar por nome, código ou cliente" oninput="obFiltrar()">
  <select id="ob-sit" class="po-input" style="max-width:160px" onchange="obFiltrar()">
    <option value="">Todas as situações</option>
    <option value="Ativa">Ativa</option>
    <option value="Concluída">Concluída</option>
    <option value="Pausada">Pausada</option>
    <option value="Cancelada">Cancelada</option>
  </select>
</div>

<div class="po-dark" style="overflow-x:auto">
  <table class="table table-dark table-hover mb-0" style="color:#e2e8f0;font-size:13px">
    <thead style="font-size:11px;text-transform:uppercase;color:#94a3b8">
      <tr>
        <th>Código</th><th>Nome</th><th>Tipo</th><th>Cliente</th>
        <th class="text-end">Contratado</th><th>Situação</th><th>Completude</th><th></th>
      </tr>
    </thead>
    <tbody id="ob-tbody">
      <?php foreach ($obras as $o): ?>
      <?php $falta = $o['completude']['falta']; $ok = $o['completude']['completa']; ?>
      <tr data-nome="<?= strtolower(htmlspecialchars($o['nome'])) ?>"
          data-codigo="<?= strtolower(htmlspecialchars($o['codigo'])) ?>"
          data-cliente="<?= strtolower(htmlspecialchars($o['cliente'] ?? '')) ?>"
          data-sit="<?= htmlspecialchars($o['situacao']) ?>">
        <td><span style="color:#60a5fa;font-weight:600"><?= htmlspecialchars($o['codigo']) ?></span></td>
        <td style="font-weight:600"><?= htmlspecialchars($o['nome']) ?></td>
        <td><?= $o['tipo'] === 'Reforma' ? '<span style="color:#67e8f9;background:rgba(103,232,249,.12);font-size:11px;font-weight:600;padding:2px 8px;border-radius:8px">Reforma</span>' : '<span style="color:#93c5fd;background:rgba(147,197,253,.14);font-size:11px;font-weight:600;padding:2px 8px;border-radius:8px">Construção</span>' ?></td>
        <td><?= htmlspecialchars($o['cliente'] ?? '—') ?></td>
        <td class="text-end"><?= $o['valor_contratado'] > 0 ? 'R$ ' . number_format($o['valor_contratado'], 2, ',', '.') : '<a href="/admin/painel-obras/obras#" style="color:#60a5fa;font-size:11px">— Preencher</a>' ?></td>
        <td><span style="font-size:12px;padding:2px 8px;border-radius:12px;background:<?= $o['situacao']==='Ativa'?'rgba(16,185,129,.14)':($o['situacao']==='Concluída'?'rgba(59,130,246,.14)':'rgba(100,116,139,.14)') ?>;color:<?= $o['situacao']==='Ativa'?'#6ee7b7':($o['situacao']==='Concluída'?'#60a5fa':'#94a3b8') ?>"><?= $o['situacao'] ?></span></td>
        <td>
          <?php if ($ok): ?>
          <span class="po-pill ok"><span style="width:5px;height:5px;border-radius:50%;background:#10b981;display:inline-block"></span> Completa</span>
          <?php else: ?>
          <span class="po-pill warn" title="Falta: <?= implode(', ', $falta) ?>"><span style="width:5px;height:5px;border-radius:50%;background:#f59e0b;display:inline-block"></span> Pendente: <?= implode(', ', $falta) ?></span>
          <?php endif; ?>
        </td>
        <td>
          <button class="po-btn-g" style="padding:4px 10px;font-size:12px" onclick='obEditar(<?= json_encode($o) ?>)'>
            <i class="bi bi-pencil"></i>
          </button>
          <button class="po-btn-d ms-1" onclick="obDeletar(<?= $o['id'] ?>, '<?= htmlspecialchars($o['nome'], ENT_QUOTES) ?>')">
            <i class="bi bi-trash3"></i>
          </button>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($obras)): ?>
      <tr><td colspan="8" class="text-center py-4" style="color:#94a3b8">Nenhuma obra cadastrada. <button class="po-btn-p ms-2" onclick="document.getElementById('mo-nova').style.display='flex'">Cadastrar primeira obra</button></td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal Nova/Editar Obra -->
<div id="mo-nova" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:1050;align-items:center;justify-content:center;padding:16px">
  <div class="po-dark" style="width:100%;max-width:680px;max-height:90vh;overflow-y:auto;padding:28px;border-radius:16px">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h5 class="mb-0" id="mo-title" style="color:#f8fafc">Nova Obra</h5>
      <button class="btn-close btn-close-white" onclick="obFecharModal()"></button>
    </div>
    <input type="hidden" id="ob-id" value="">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="po-label">Código *</label>
        <input id="ob-codigo" class="po-input" placeholder="ex.: REF-07, CON-03, P002" style="text-transform:uppercase">
      </div>
      <div class="col-md-8">
        <label class="po-label">Nome *</label>
        <input id="ob-nome" class="po-input" placeholder="Nome da obra">
      </div>
      <div class="col-md-4">
        <label class="po-label">Tipo *</label>
        <select id="ob-tipo" class="po-input">
          <option>Reforma</option>
          <option>Construção</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="po-label">Situação *</label>
        <select id="ob-situacao" class="po-input">
          <option>Ativa</option>
          <option>Concluída</option>
          <option>Pausada</option>
          <option>Cancelada</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="po-label">Cliente</label>
        <input id="ob-cliente" class="po-input" placeholder="Nome do cliente">
      </div>
      <div class="col-md-6">
        <label class="po-label">Valor Contratado *</label>
        <input id="ob-contratado" class="po-input" placeholder="R$ 0,00" oninput="mascaraMoeda(this)">
      </div>
      <div class="col-md-6">
        <label class="po-label">Data do Contrato *</label>
        <input id="ob-data-contrato" type="date" class="po-input">
      </div>
      <div class="col-md-6">
        <label class="po-label">Data de Início</label>
        <input id="ob-data-inicio" type="date" class="po-input">
      </div>
      <div class="col-md-6">
        <label class="po-label">Data Prevista de Fim</label>
        <input id="ob-data-fim" type="date" class="po-input">
      </div>
      <div class="col-12">
        <label class="po-label">Centros de Custo do Nibo <small style="color:#64748b">(vincula lançamentos à obra)</small></label>
        <div id="ob-cc-lista" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:6px"></div>
        <select id="ob-cc-sel" class="po-input" style="margin-bottom:4px" onchange="obAdicionarCc()">
          <option value="">— Selecionar centro de custo —</option>
          <?php foreach ($costCenters as $cc): ?>
          <option value="<?= htmlspecialchars($cc['id']) ?>"><?= htmlspecialchars($cc['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <input type="hidden" id="ob-cc-json" value="[]">
      </div>
      <div class="col-12">
        <label class="po-label">Códigos alternativos <small style="color:#64748b">(separados por vírgula, ex.: ADM-105, P002)</small></label>
        <input id="ob-codigos-alt" class="po-input" placeholder="ADM-105, P002">
      </div>
      <div class="col-md-6">
        <label class="po-label">Responsável</label>
        <input id="ob-responsavel" class="po-input" placeholder="Nome do responsável">
      </div>
      <div class="col-12">
        <label class="po-label">Observações</label>
        <textarea id="ob-obs" class="po-input" rows="2" placeholder="Observações gerais"></textarea>
      </div>
    </div>
    <div id="ob-erro" style="color:#fca5a5;font-size:13px;margin-top:12px;display:none"></div>
    <div class="d-flex gap-2 mt-4">
      <button class="po-btn-p" onclick="obSalvar()"><i class="bi bi-check2"></i> Salvar</button>
      <button class="po-btn-g" onclick="obFecharModal()">Cancelar</button>
    </div>
  </div>
</div>

</div><!-- /po-obras-wrap -->

<script>
// Filtro da tabela
function obFiltrar() {
  const q   = document.getElementById('ob-busca').value.toLowerCase();
  const sit = document.getElementById('ob-sit').value;
  document.querySelectorAll('#ob-tbody tr[data-nome]').forEach(tr => {
    const nome   = tr.dataset.nome   || '';
    const codigo = tr.dataset.codigo || '';
    const cli    = tr.dataset.cliente || '';
    const s      = tr.dataset.sit    || '';
    const show = (!q || nome.includes(q) || codigo.includes(q) || cli.includes(q))
              && (!sit || s === sit);
    tr.style.display = show ? '' : 'none';
  });
}

// Modal
function obFecharModal() {
  document.getElementById('mo-nova').style.display = 'none';
  document.getElementById('ob-id').value = '';
  document.getElementById('ob-cc-json').value = '[]';
  document.getElementById('ob-cc-lista').innerHTML = '';
  document.getElementById('ob-erro').style.display = 'none';
}
function obEditar(o) {
  document.getElementById('mo-title').textContent = 'Editar Obra';
  document.getElementById('ob-id').value = o.id;
  document.getElementById('ob-codigo').value = o.codigo;
  document.getElementById('ob-nome').value = o.nome;
  document.getElementById('ob-tipo').value = o.tipo;
  document.getElementById('ob-situacao').value = o.situacao;
  document.getElementById('ob-cliente').value = o.cliente || '';
  document.getElementById('ob-contratado').value = o.valor_contratado > 0 ? formatBRL(o.valor_contratado) : '';
  document.getElementById('ob-data-contrato').value = o.data_contrato || '';
  document.getElementById('ob-data-inicio').value = o.data_inicio || '';
  document.getElementById('ob-data-fim').value = o.data_prev_fim || '';
  document.getElementById('ob-responsavel').value = o.responsavel || '';
  document.getElementById('ob-obs').value = o.observacoes || '';
  const ccs = JSON.parse(o.centros_custo || '[]');
  document.getElementById('ob-cc-json').value = JSON.stringify(ccs);
  renderCcTags(ccs);
  const alts = JSON.parse(o.codigos_alt || '[]');
  document.getElementById('ob-codigos-alt').value = alts.join(', ');
  document.getElementById('mo-nova').style.display = 'flex';
}

// Centros de custo (tags)
function renderCcTags(ccs) {
  const lista = document.getElementById('ob-cc-lista');
  const sel = document.getElementById('ob-cc-sel');
  lista.innerHTML = ccs.map((id,i) => {
    const opt = [...sel.options].find(o => o.value === id);
    const nome = opt ? opt.text : id;
    return `<span style="background:rgba(59,130,246,.12);color:#60a5fa;font-size:12px;padding:3px 10px;border-radius:20px;display:flex;align-items:center;gap:6px">
      ${nome} <span style="cursor:pointer" onclick="obRemoverCc(${i})">×</span>
    </span>`;
  }).join('');
}
function obAdicionarCc() {
  const sel = document.getElementById('ob-cc-sel');
  const val = sel.value;
  if (!val) return;
  const ccs = JSON.parse(document.getElementById('ob-cc-json').value || '[]');
  if (!ccs.includes(val)) { ccs.push(val); document.getElementById('ob-cc-json').value = JSON.stringify(ccs); renderCcTags(ccs); }
  sel.value = '';
}
function obRemoverCc(i) {
  const ccs = JSON.parse(document.getElementById('ob-cc-json').value || '[]');
  ccs.splice(i,1);
  document.getElementById('ob-cc-json').value = JSON.stringify(ccs);
  renderCcTags(ccs);
}

// Salvar
function obSalvar() {
  const id = document.getElementById('ob-id').value;
  const url = id ? '/admin/painel-obras/obras/update' : '/admin/painel-obras/obras/store';
  const payload = new URLSearchParams({
    id,
    codigo:          document.getElementById('ob-codigo').value.trim().toUpperCase(),
    nome:            document.getElementById('ob-nome').value.trim(),
    tipo:            document.getElementById('ob-tipo').value,
    situacao:        document.getElementById('ob-situacao').value,
    cliente:         document.getElementById('ob-cliente').value.trim(),
    valor_contratado:document.getElementById('ob-contratado').value,
    data_contrato:   document.getElementById('ob-data-contrato').value,
    data_inicio:     document.getElementById('ob-data-inicio').value,
    data_prev_fim:   document.getElementById('ob-data-fim').value,
    responsavel:     document.getElementById('ob-responsavel').value.trim(),
    observacoes:     document.getElementById('ob-obs').value.trim(),
    centros_custo:   document.getElementById('ob-cc-json').value,
    codigos_alt:     document.getElementById('ob-codigos-alt').value,
  });
  fetch(url, { method:'POST', body: payload })
    .then(r => r.json())
    .then(res => {
      if (!res.ok) { mostrarErro(res.error || 'Erro desconhecido'); return; }
      location.reload();
    }).catch(() => mostrarErro('Falha de conexão.'));
}

function obDeletar(id, nome) {
  if (!confirm('Excluir a obra "' + nome + '"? Esta ação não pode ser desfeita.')) return;
  fetch('/admin/painel-obras/obras/delete', { method:'POST', body: new URLSearchParams({id}) })
    .then(r => r.json()).then(res => { if (res.ok) location.reload(); });
}

function mostrarErro(msg) {
  const el = document.getElementById('ob-erro');
  el.textContent = msg; el.style.display = 'block';
}

function mascaraMoeda(el) {
  let v = el.value.replace(/\D/g,'');
  v = (parseInt(v||'0')/100).toFixed(2);
  el.value = 'R$ ' + v.replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.');
}
function formatBRL(v) {
  return 'R$ ' + parseFloat(v).toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.');
}
</script>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/Views/admin/layouts/app.php';
