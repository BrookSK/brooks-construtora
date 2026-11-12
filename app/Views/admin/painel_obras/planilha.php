<?php
$pageTitle   = 'Planilha de Controle';
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
    <li class="breadcrumb-item active" style="color:#e2e8f0">Planilha de Controle</li>
  </ol>
</nav>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
  <h5 class="mb-0">Planilha de Controle Mensal</h5>
  <div class="d-flex gap-2 align-items-center">
    <label class="po-label mb-0" style="white-space:nowrap">Competência:</label>
    <input type="month" id="pl-comp" class="po-input" style="max-width:160px" value="<?= htmlspecialchars($competencia) ?>"
           onchange="window.location='/admin/painel-obras/planilha?competencia='+this.value">
    <button class="po-btn-p" onclick="document.getElementById('mo-plan').style.display='flex'">
      <i class="bi bi-plus-circle"></i> Registrar
    </button>
  </div>
</div>

<div class="po-dark" style="overflow-x:auto">
  <table class="table table-dark table-hover mb-0" style="color:#e2e8f0;font-size:13px">
    <thead style="font-size:11px;text-transform:uppercase;color:#94a3b8">
      <tr><th>Código</th><th>Nome</th><th class="text-end">Total Nibo*</th><th class="text-end">Total Planilha</th><th class="text-end">Diferença</th><th>Informado por</th><th>Data</th><th>Obs.</th><th></th></tr>
    </thead>
    <tbody>
      <?php
      $registrosMap = [];
      foreach ($registros as $r) $registrosMap[$r['obra_id']] = $r;
      foreach ($obras as $o):
        $r = $registrosMap[$o['id']] ?? null;
      ?>
      <tr>
        <td><span style="color:#60a5fa;font-weight:600"><?= htmlspecialchars($o['codigo']) ?></span></td>
        <td><?= htmlspecialchars($o['nome']) ?></td>
        <td class="text-end" style="color:#94a3b8">—*</td>
        <td class="text-end">
          <?= $r ? 'R$ ' . number_format($r['total_planilha'], 2, ',', '.') : '<span style="color:#64748b">—</span>' ?>
        </td>
        <td class="text-end" style="color:<?= $r ? ($r['total_planilha'] > 0 ? '#94a3b8' : '#fca5a5') : '#64748b' ?>">—</td>
        <td style="color:#94a3b8"><?= htmlspecialchars($r['informado_por'] ?? '—') ?></td>
        <td style="color:#94a3b8"><?= $r ? substr($r['data_informacao'], 0, 16) : '—' ?></td>
        <td style="color:#94a3b8;font-size:12px"><?= htmlspecialchars($r['observacao'] ?? '') ?></td>
        <td>
          <button class="btn btn-sm" style="background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.2);color:#60a5fa;font-size:12px"
            onclick='plPrePreencher(<?= $o["id"] ?>, "<?= htmlspecialchars($o["codigo"] . " — " . $o["nome"], ENT_QUOTES) ?>", "<?= htmlspecialchars($r["total_planilha"] ?? "", ENT_QUOTES) ?>")'>
            <?= $r ? 'Atualizar' : 'Informar' ?>
          </button>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($obras)): ?>
      <tr><td colspan="9" class="text-center py-4" style="color:#94a3b8">Nenhuma obra ativa cadastrada.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<p style="font-size:12px;color:#64748b;margin-top:8px">* Total Nibo calculado em tempo real no dashboard (fluxo de caixa). Aqui exibe apenas o dado da planilha interna.</p>

<!-- Modal -->
<div id="mo-plan" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:1050;align-items:center;justify-content:center;padding:16px">
  <div class="po-dark" style="width:100%;max-width:460px;padding:28px;border-radius:16px">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h5 class="mb-0" style="color:#f8fafc">Registrar Total da Planilha</h5>
      <button class="btn-close btn-close-white" onclick="document.getElementById('mo-plan').style.display='none'"></button>
    </div>
    <div class="mb-3">
      <label class="po-label">Obra *</label>
      <select id="pl-obra" class="po-input">
        <option value="">— Selecionar —</option>
        <?php foreach ($obras as $o): ?>
        <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['codigo'] . ' — ' . $o['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="po-label">Competência *</label>
      <input id="pl-comp-mo" type="month" class="po-input" value="<?= htmlspecialchars($competencia) ?>">
    </div>
    <div class="mb-3">
      <label class="po-label">Total da Planilha *</label>
      <input id="pl-total" class="po-input" placeholder="R$ 0,00" oninput="mascaraMoeda(this)">
    </div>
    <div class="mb-3">
      <label class="po-label">Observação</label>
      <textarea id="pl-obs" class="po-input" rows="2" placeholder="Observações (opcional)"></textarea>
    </div>
    <div id="pl-err" style="color:#fca5a5;font-size:13px;display:none;margin-bottom:10px"></div>
    <div class="d-flex gap-2">
      <button class="po-btn-p" onclick="plSalvar()"><i class="bi bi-check2"></i> Salvar</button>
      <button class="po-btn-g" onclick="document.getElementById('mo-plan').style.display='none'">Cancelar</button>
    </div>
  </div>
</div>

<script>
function plPrePreencher(id, nome, total) {
  document.getElementById('pl-obra').value = id;
  if (total) {
    const f = parseFloat(total).toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.');
    document.getElementById('pl-total').value = 'R$ ' + f;
  }
  document.getElementById('mo-plan').style.display='flex';
}
function plSalvar() {
  const params = new URLSearchParams({
    obra_id:    document.getElementById('pl-obra').value,
    competencia:document.getElementById('pl-comp-mo').value,
    total_planilha: document.getElementById('pl-total').value,
    observacao: document.getElementById('pl-obs').value.trim(),
  });
  if (!params.get('obra_id') || !params.get('competencia')) {
    document.getElementById('pl-err').textContent='Obra e competência obrigatórios.';
    document.getElementById('pl-err').style.display='block'; return;
  }
  fetch('/admin/painel-obras/planilha/salvar', { method:'POST', body: params })
    .then(r => r.json())
    .then(res => {
      if (!res.ok) { document.getElementById('pl-err').textContent=res.error||'Erro'; document.getElementById('pl-err').style.display='block'; return; }
      location.reload();
    }).catch(() => { document.getElementById('pl-err').textContent='Falha de conexão.'; document.getElementById('pl-err').style.display='block'; });
}
function mascaraMoeda(el) {
  let v = el.value.replace(/\D/g,'');
  v = (parseInt(v||'0')/100).toFixed(2);
  el.value = 'R$ ' + v.replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.');
}
</script>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/Views/admin/layouts/app.php';
