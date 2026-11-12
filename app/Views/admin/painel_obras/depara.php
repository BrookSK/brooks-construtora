<?php
$pageTitle   = 'De-para de Categorias';
$currentPage = 'painel_obras';
ob_start();
$cats = $categoriasObra;
?>
<style>
.po-dark{background:#151f32;border:1px solid rgba(255,255,255,.06);border-radius:12px;color:#e2e8f0}
.po-input{background:#0f1829;border:1px solid rgba(255,255,255,.1);color:#e2e8f0;border-radius:8px;padding:7px 12px;width:100%;outline:none;font-size:13px}
.po-input:focus{border-color:#3b82f6}
.po-label{font-size:11px;color:#94a3b8;margin-bottom:3px;display:block}
.po-btn-p{background:linear-gradient(135deg,#3b82f6,#22d3ee);color:#fff;border:none;border-radius:10px;padding:7px 16px;font-size:13px;font-weight:600;cursor:pointer}
.po-btn-g{background:#151f32;border:1px solid rgba(255,255,255,.1);color:#e2e8f0;border-radius:10px;padding:7px 12px;font-size:13px;cursor:pointer}
.nm-row{display:grid;grid-template-columns:1fr 200px 110px auto;gap:8px;align-items:center;padding:8px 0;border-bottom:1px solid rgba(255,255,255,.05)}
.nm-row:last-child{border-bottom:none}
@media(max-width:760px){.nm-row{grid-template-columns:1fr;}}
</style>

<nav aria-label="breadcrumb" style="margin-bottom:16px">
  <ol class="breadcrumb" style="background:none;padding:0;margin:0;font-size:13px;color:#94a3b8">
    <li class="breadcrumb-item"><a href="/admin/painel-obras" style="color:#60a5fa">Painel de Obras</a></li>
    <li class="breadcrumb-item active" style="color:#e2e8f0">De-para de Categorias</li>
  </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h5 class="mb-0">De-para: Categorias Nibo → Categoria de Obra</h5>
  <div class="d-flex gap-2 align-items-center">
    <select id="dp-company" class="po-input" style="max-width:200px" onchange="location.href='/admin/painel-obras/depara?company='+this.value">
      <?php foreach ($companies as $k => $l): ?>
      <option value="<?= $k ?>" <?= $k === $company ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="po-btn-g" onclick="dpSync()"><i class="bi bi-arrow-repeat"></i> Atualizar categorias Nibo</button>
  </div>
</div>

<!-- Não mapeadas (prioridade) -->
<?php $totalNaoMap = count($naoMapeadas) + count($semEntrada); ?>
<?php if ($totalNaoMap > 0): ?>
<div class="po-dark p-4 mb-3">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:8px">
    <span style="color:#fcd34d;font-size:14px;font-weight:600"><i class="bi bi-exclamation-triangle-fill" style="margin-right:6px"></i><?= $totalNaoMap ?> categorias não mapeadas</span>
    <button class="po-btn-p" onclick="dpSalvarLote()"><i class="bi bi-check2-all"></i> Salvar todos</button>
  </div>

  <?php foreach ($semEntrada as $cat): ?>
  <div class="nm-row" id="nm-<?= htmlspecialchars($cat['id'], ENT_QUOTES) ?>">
    <div>
      <div style="font-size:13px;font-weight:600;color:#e2e8f0"><?= htmlspecialchars($cat['nome']) ?></div>
      <div style="font-size:11px;color:#64748b"><?= htmlspecialchars($cat['caminho'] ?? '') ?></div>
    </div>
    <select class="po-input dp-catobra" data-id="<?= htmlspecialchars($cat['id'], ENT_QUOTES) ?>" data-nome="<?= htmlspecialchars($cat['nome'], ENT_QUOTES) ?>">
      <option value="">— Selecionar —</option>
      <?php foreach ($cats as $c): ?>
      <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
      <?php endforeach; ?>
    </select>
    <div class="d-flex align-items-center gap-2">
      <input type="checkbox" class="dp-ignorar" title="Ignorar no painel" id="ig-<?= htmlspecialchars($cat['id'], ENT_QUOTES) ?>">
      <label for="ig-<?= htmlspecialchars($cat['id'], ENT_QUOTES) ?>" style="font-size:11px;color:#94a3b8;cursor:pointer">Ignorar</label>
    </div>
    <button class="po-btn-g" style="padding:5px 10px;font-size:12px" onclick="dpSalvarUm('<?= htmlspecialchars($cat['id'], ENT_QUOTES) ?>','<?= htmlspecialchars($cat['nome'], ENT_QUOTES) ?>')">Salvar</button>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Mapeamentos existentes -->
<div class="po-dark" style="overflow-x:auto">
  <div style="padding:16px 20px;border-bottom:1px solid rgba(255,255,255,.06)">
    <span style="font-size:14px;font-weight:600;color:#f8fafc">Mapeamentos existentes</span>
    <span style="font-size:12px;color:#64748b;margin-left:8px"><?= count($deParaMap) ?> categorias mapeadas</span>
  </div>
  <table class="table table-dark table-hover mb-0" style="font-size:13px;color:#e2e8f0">
    <thead style="font-size:11px;text-transform:uppercase;color:#94a3b8">
      <tr><th>Categoria Nibo</th><th>Caminho</th><th>Categoria de Obra</th><th>Ignorar</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($deParaMap as $niboId => $row): $catInfo = $catMap[$niboId] ?? null; ?>
      <tr id="dp-row-<?= $row['id'] ?>">
        <td style="font-weight:600"><?= htmlspecialchars($row['categoria_nibo_nome']) ?></td>
        <td style="color:#64748b;font-size:12px"><?= htmlspecialchars($catInfo['caminho'] ?? '—') ?></td>
        <td>
          <select class="po-input dp-edit-cat" data-id="<?= htmlspecialchars($niboId, ENT_QUOTES) ?>" data-nome="<?= htmlspecialchars($row['categoria_nibo_nome'], ENT_QUOTES) ?>" style="max-width:220px">
            <option value="">— Sem mapeamento —</option>
            <?php foreach ($cats as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= $row['categoria_obra'] === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td>
          <input type="checkbox" class="dp-edit-ignorar" data-id="<?= htmlspecialchars($niboId, ENT_QUOTES) ?>" <?= $row['ignorar_no_painel'] ? 'checked' : '' ?>>
        </td>
        <td>
          <button class="po-btn-g" style="padding:4px 10px;font-size:12px"
            onclick="dpAtualizarLinha('<?= htmlspecialchars($niboId, ENT_QUOTES) ?>','<?= htmlspecialchars($row['categoria_nibo_nome'], ENT_QUOTES) ?>', this)">
            <i class="bi bi-check2"></i>
          </button>
          <button class="btn btn-sm" style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#fca5a5;font-size:12px;margin-left:4px"
            onclick="dpDeletar(<?= $row['id'] ?>)"><i class="bi bi-trash3"></i></button>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($deParaMap)): ?>
      <tr><td colspan="5" class="text-center py-4" style="color:#94a3b8">Nenhum mapeamento ainda. Use a seção acima para mapear as categorias do Nibo.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
function dpSync() {
  const company = document.getElementById('dp-company').value;
  fetch('/admin/painel-obras/depara/sync-categorias', {
    method:'POST', body: new URLSearchParams({company})
  }).then(r=>r.json()).then(res => {
    if (res.ok) { alert('Categorias sincronizadas com sucesso!'); location.reload(); }
    else alert('Erro: ' + (res.error||'desconhecido'));
  });
}

function dpSalvarUm(niboId, niboNome) {
  const sel  = document.querySelector('.dp-catobra[data-id="' + CSS.escape(niboId) + '"]');
  const ign  = document.getElementById('ig-' + CSS.escape(niboId));
  const cat  = sel?.value || '';
  const ignorar = ign?.checked ? '1' : '0';
  fetch('/admin/painel-obras/depara/salvar', {
    method:'POST',
    body: new URLSearchParams({ categoria_nibo_id: niboId, categoria_nibo_nome: niboNome, categoria_obra: cat, ignorar_no_painel: ignorar })
  }).then(r=>r.json()).then(res => {
    if (res.ok) { const row = document.getElementById('nm-' + CSS.escape(niboId)); if (row) row.style.opacity='.4'; }
    else alert('Erro: ' + (res.error||'desconhecido'));
  });
}

function dpSalvarLote() {
  const rows = document.querySelectorAll('.dp-catobra');
  const mapeamentos = [];
  rows.forEach(sel => {
    const niboId   = sel.dataset.id;
    const niboNome = sel.dataset.nome;
    const ign      = document.getElementById('ig-' + CSS.escape(niboId));
    mapeamentos.push({ categoria_nibo_id: niboId, categoria_nibo_nome: niboNome, categoria_obra: sel.value, ignorar_no_painel: ign?.checked ? '1' : '0' });
  });
  if (!mapeamentos.length) return;
  const params = new URLSearchParams();
  mapeamentos.forEach((m, i) => {
    Object.keys(m).forEach(k => params.append('mapeamentos[' + i + '][' + k + ']', m[k]));
  });
  fetch('/admin/painel-obras/depara/salvar-lote', { method:'POST', body: params })
    .then(r=>r.json()).then(res => {
      if (res.ok) { alert(res.salvos + ' mapeamento(s) salvos.'); location.reload(); }
      else alert('Erro: ' + (res.error||'desconhecido'));
    });
}

function dpAtualizarLinha(niboId, niboNome, btn) {
  const row = btn.closest('tr');
  const sel  = row.querySelector('.dp-edit-cat');
  const ign  = row.querySelector('.dp-edit-ignorar');
  fetch('/admin/painel-obras/depara/salvar', {
    method:'POST',
    body: new URLSearchParams({ categoria_nibo_id: niboId, categoria_nibo_nome: niboNome, categoria_obra: sel?.value||'', ignorar_no_painel: ign?.checked ? '1' : '0' })
  }).then(r=>r.json()).then(res => {
    if (res.ok) { btn.innerHTML='<i class="bi bi-check2-all"></i>'; setTimeout(()=>btn.innerHTML='<i class="bi bi-check2"></i>',1500); }
    else alert('Erro: ' + (res.error||'desconhecido'));
  });
}

function dpDeletar(id) {
  if (!confirm('Remover este mapeamento?')) return;
  fetch('/admin/painel-obras/depara/delete', { method:'POST', body: new URLSearchParams({id}) })
    .then(r=>r.json()).then(res => { if (res.ok) location.reload(); });
}
</script>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/Views/admin/layouts/app.php';
