<?php
$pageTitle   = 'Pauta de Revisão';
$currentPage = 'painel_obras';
ob_start();
?>
<style>
.po-dark{background:#151f32;border:1px solid rgba(255,255,255,.06);border-radius:12px;color:#e2e8f0}
.po-input{background:#0f1829;border:1px solid rgba(255,255,255,.1);color:#e2e8f0;border-radius:8px;padding:8px 12px;width:100%;outline:none;font-size:14px}
.po-input:focus{border-color:#3b82f6}
.po-label{font-size:12px;color:#94a3b8;margin-bottom:4px;display:block}
.po-btn-p{background:linear-gradient(135deg,#3b82f6,#22d3ee);color:#fff;border:none;border-radius:10px;padding:8px 18px;font-size:13px;font-weight:600;cursor:pointer}
.po-btn-g{background:#151f32;border:1px solid rgba(255,255,255,.1);color:#e2e8f0;border-radius:10px;padding:8px 14px;font-size:13px;cursor:pointer}
</style>

<nav aria-label="breadcrumb" style="margin-bottom:16px">
  <ol class="breadcrumb" style="background:none;padding:0;margin:0;font-size:13px;color:#94a3b8">
    <li class="breadcrumb-item"><a href="/admin/painel-obras" style="color:#60a5fa">Painel de Obras</a></li>
    <li class="breadcrumb-item active" style="color:#e2e8f0">Pauta de Revisão</li>
  </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <h5 class="mb-0">Pauta de Revisão</h5>
  <button class="po-btn-p" onclick="document.getElementById('mo-pauta').style.display='flex'">
    <i class="bi bi-calendar-plus"></i> Adicionar item
  </button>
</div>

<div class="po-dark" style="overflow-x:auto">
  <table class="table table-dark table-hover mb-0" style="font-size:13px;color:#e2e8f0">
    <thead style="font-size:11px;text-transform:uppercase;color:#94a3b8">
      <tr><th>Obra</th><th>Data da reunião</th><th>Motivo</th><th>Adicionado por</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($pauta as $p): ?>
      <tr>
        <td><span style="color:#60a5fa;font-weight:600"><?= htmlspecialchars($p['codigo']) ?></span> <?= htmlspecialchars($p['nome']) ?></td>
        <td><?= htmlspecialchars(date('d/m/Y', strtotime($p['data_reuniao']))) ?></td>
        <td style="color:#94a3b8"><?= htmlspecialchars($p['motivo'] ?? '—') ?></td>
        <td style="color:#94a3b8"><?= htmlspecialchars($p['adicionado_por']) ?></td>
        <td>
          <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:12px;
                background:<?= $p['status']==='Pendente'?'rgba(245,158,11,.12)':'rgba(16,185,129,.12)' ?>;
                color:<?= $p['status']==='Pendente'?'#fcd34d':'#6ee7b7' ?>">
            <?= $p['status'] ?>
          </span>
        </td>
        <td>
          <?php if ($p['status'] === 'Pendente'): ?>
          <button class="po-btn-g" style="padding:4px 10px;font-size:12px"
            onclick="pautaRevisar(<?= $p['id'] ?>)">Marcar revisada</button>
          <?php endif; ?>
          <button class="btn btn-sm ms-1" style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#fca5a5;font-size:12px"
            onclick="pautaDeletar(<?= $p['id'] ?>)"><i class="bi bi-trash3"></i></button>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($pauta)): ?>
      <tr><td colspan="6" class="text-center py-4" style="color:#94a3b8">Nenhum item na pauta ainda.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal Adicionar -->
<div id="mo-pauta" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:1050;align-items:center;justify-content:center;padding:16px">
  <div class="po-dark" style="width:100%;max-width:480px;padding:28px;border-radius:16px">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h5 class="mb-0" style="color:#f8fafc">Adicionar à Pauta</h5>
      <button class="btn-close btn-close-white" onclick="document.getElementById('mo-pauta').style.display='none'"></button>
    </div>
    <div class="mb-3">
      <label class="po-label">Obra(s) *</label>
      <select id="pt-obras" class="po-input" multiple size="5">
        <?php foreach ($obras as $o): ?>
        <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['codigo'] . ' — ' . $o['nome']) ?></option>
        <?php endforeach; ?>
      </select>
      <small style="color:#64748b;font-size:11px">Segure Ctrl/Cmd para selecionar múltiplas</small>
    </div>
    <div class="mb-3">
      <label class="po-label">Data da reunião *</label>
      <input id="pt-data" type="date" class="po-input" value="<?= date('Y-m-d', strtotime('next friday')) ?>">
    </div>
    <div class="mb-3">
      <label class="po-label">Motivo (opcional)</label>
      <textarea id="pt-motivo" class="po-input" rows="2" placeholder="Descreva o motivo"></textarea>
    </div>
    <div id="pt-err" style="color:#fca5a5;font-size:13px;display:none;margin-bottom:10px"></div>
    <div class="d-flex gap-2">
      <button class="po-btn-p" onclick="pautaSalvar()"><i class="bi bi-check2"></i> Adicionar</button>
      <button class="po-btn-g" onclick="document.getElementById('mo-pauta').style.display='none'">Cancelar</button>
    </div>
  </div>
</div>

<script>
function pautaSalvar() {
  const sel = document.getElementById('pt-obras');
  const ids = [...sel.selectedOptions].map(o => o.value);
  if (!ids.length) { document.getElementById('pt-err').textContent='Selecione ao menos uma obra.'; document.getElementById('pt-err').style.display='block'; return; }
  const params = new URLSearchParams({ data_reuniao: document.getElementById('pt-data').value, motivo: document.getElementById('pt-motivo').value.trim() });
  ids.forEach(id => params.append('obra_ids[]', id));
  fetch('/admin/painel-obras/pauta/store', { method:'POST', body: params })
    .then(r=>r.json()).then(res => {
      if (res.ok) location.reload();
      else { document.getElementById('pt-err').textContent=res.error||'Erro'; document.getElementById('pt-err').style.display='block'; }
    });
}
function pautaRevisar(id) {
  fetch('/admin/painel-obras/pauta/update', { method:'POST', body: new URLSearchParams({id, status:'Revisada'}) })
    .then(r=>r.json()).then(res => { if (res.ok) location.reload(); });
}
function pautaDeletar(id) {
  if (!confirm('Remover este item da pauta?')) return;
  fetch('/admin/painel-obras/pauta/delete', { method:'POST', body: new URLSearchParams({id}) })
    .then(r=>r.json()).then(res => { if (res.ok) location.reload(); });
}
</script>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/Views/admin/layouts/app.php';
