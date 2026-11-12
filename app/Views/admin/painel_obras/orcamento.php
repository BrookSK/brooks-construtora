<?php
$pageTitle   = 'Orçamento por Obra';
$currentPage = 'painel_obras';
ob_start();
$cats = $categoriasObra;
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
    <li class="breadcrumb-item active" style="color:#e2e8f0">Orçamento</li>
  </ol>
</nav>

<div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
  <h5 class="mb-0">Orçamento por Obra e Categoria</h5>
  <select id="orc-obra-sel" class="po-input" style="max-width:320px" onchange="orcCarregar()">
    <option value="">— Selecionar obra —</option>
    <?php foreach ($obras as $o): ?>
    <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['codigo'] . ' — ' . $o['nome']) ?></option>
    <?php endforeach; ?>
  </select>
</div>

<div id="orc-area" style="display:none">
  <!-- Grade de orçamento -->
  <div class="po-dark p-4 mb-3">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <div>
        <span style="font-size:14px;font-weight:600;color:#f8fafc">Versão vigente: <span id="orc-versao" style="color:#60a5fa">—</span></span>
        <span style="font-size:12px;color:#94a3b8;margin-left:12px">Data vigência: <span id="orc-vigencia">—</span></span>
      </div>
      <button class="po-btn-g" onclick="orcNovaVersao()"><i class="bi bi-plus-circle"></i> Nova revisão</button>
    </div>

    <table class="table table-dark mb-0" style="color:#e2e8f0;font-size:13px">
      <thead style="font-size:11px;text-transform:uppercase;color:#94a3b8">
        <tr><th style="width:60%">Categoria de obra</th><th class="text-end">Valor Orçado</th></tr>
      </thead>
      <tbody id="orc-tbody">
        <tr><td colspan="2" class="text-center py-3" style="color:#94a3b8">Selecione uma obra acima.</td></tr>
      </tbody>
      <tfoot>
        <tr style="border-top:1px solid rgba(255,255,255,.1)">
          <td style="font-weight:700;color:#f8fafc">Total</td>
          <td class="text-end" style="font-weight:700;color:#60a5fa" id="orc-total">—</td>
        </tr>
      </tfoot>
    </table>
  </div>

  <!-- Histórico de versões -->
  <div class="po-dark p-4">
    <h6 style="color:#94a3b8;font-size:12px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">Histórico de Versões</h6>
    <div id="orc-historico" style="color:#94a3b8;font-size:13px">—</div>
  </div>
</div>

<!-- Modal Nova Versão -->
<div id="mo-orc" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:1050;align-items:center;justify-content:center;padding:16px">
  <div class="po-dark" style="width:100%;max-width:560px;max-height:90vh;overflow-y:auto;padding:28px;border-radius:16px">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h5 class="mb-0" style="color:#f8fafc">Novo Orçamento</h5>
      <button class="btn-close btn-close-white" onclick="document.getElementById('mo-orc').style.display='none'"></button>
    </div>
    <div class="mb-3">
      <label class="po-label">Data de vigência *</label>
      <input id="orc-data-vig" type="date" class="po-input">
    </div>
    <div id="orc-motivo-wrap" style="display:none" class="mb-3">
      <label class="po-label">Motivo da revisão * <small style="color:#64748b">(obrigatório a partir da versão 2)</small></label>
      <textarea id="orc-motivo" class="po-input" rows="2" placeholder="Descreva o motivo da revisão"></textarea>
    </div>
    <table class="table table-dark mb-3" style="font-size:13px;color:#e2e8f0">
      <thead style="font-size:11px;color:#94a3b8;text-transform:uppercase">
        <tr><th>Categoria</th><th class="text-end">Valor Orçado</th></tr>
      </thead>
      <tbody>
        <?php foreach ($cats as $cat): ?>
        <tr>
          <td><?= htmlspecialchars($cat) ?></td>
          <td><input class="po-input text-end orc-val" data-cat="<?= htmlspecialchars($cat) ?>" placeholder="R$ 0,00" oninput="mascaraMoeda(this);orcCalcTotal()"></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr><td style="font-weight:700">Total</td><td class="text-end" style="font-weight:700;color:#60a5fa" id="orc-mo-total">R$ 0,00</td></tr>
      </tfoot>
    </table>
    <div id="orc-err" style="color:#fca5a5;font-size:13px;display:none;margin-bottom:10px"></div>
    <div class="d-flex gap-2">
      <button class="po-btn-p" onclick="orcSalvar()"><i class="bi bi-check2"></i> Salvar orçamento</button>
      <button class="po-btn-g" onclick="document.getElementById('mo-orc').style.display='none'">Cancelar</button>
    </div>
  </div>
</div>

<script>
const BRL = v => v == null ? '—' : new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL'}).format(v);

function orcCarregar() {
  const obraId = document.getElementById('orc-obra-sel').value;
  if (!obraId) { document.getElementById('orc-area').style.display='none'; return; }
  document.getElementById('orc-area').style.display='block';
  fetch('/admin/painel-obras/orcamento/data?obra_id=' + obraId)
    .then(r => r.json())
    .then(res => {
      if (!res.ok) return;
      const v = res.versao;
      document.getElementById('orc-versao').textContent = v || '(nenhuma)';
      document.getElementById('orc-vigencia').textContent = res.linhas[0]?.data_vigencia || '—';
      const cats = <?= json_encode($cats) ?>;
      const linhasMap = {};
      res.linhas.forEach(l => { linhasMap[l.categoria_obra] = l.valor_orcado; });
      let total = 0;
      document.getElementById('orc-tbody').innerHTML = cats.map(cat => {
        const val = linhasMap[cat] || 0;
        total += parseFloat(val);
        return `<tr><td>${cat}</td><td class="text-end">${val > 0 ? BRL(val) : '<span style="color:#64748b">—</span>'}</td></tr>`;
      }).join('');
      document.getElementById('orc-total').textContent = BRL(total);

      if (res.historico?.length) {
        document.getElementById('orc-historico').innerHTML = `<table class="table table-dark table-sm mb-0" style="font-size:12px;color:#e2e8f0">
          <thead><tr><th>Versão</th><th>Data vigência</th><th class="text-end">Total</th><th>Motivo</th></tr></thead>
          <tbody>${res.historico.map(h => `<tr>
            <td>v${h.versao}</td>
            <td>${h.data_vigencia||'—'}</td>
            <td class="text-end">${BRL(h.total)}</td>
            <td style="color:#94a3b8">${h.motivo_revisao||'—'}</td>
          </tr>`).join('')}</tbody>
        </table>`;
      } else {
        document.getElementById('orc-historico').textContent = 'Nenhuma versão cadastrada ainda.';
      }
    });
}

function orcNovaVersao() {
  const obraId = document.getElementById('orc-obra-sel').value;
  if (!obraId) { alert('Selecione uma obra primeiro.'); return; }
  const versaoAtual = parseInt(document.getElementById('orc-versao').textContent) || 0;
  document.getElementById('orc-motivo-wrap').style.display = versaoAtual >= 1 ? 'block' : 'none';
  document.getElementById('orc-data-vig').value = new Date().toISOString().substring(0,10);
  document.querySelectorAll('.orc-val').forEach(i => i.value = '');
  document.getElementById('orc-mo-total').textContent = 'R$ 0,00';
  document.getElementById('orc-err').style.display = 'none';
  document.getElementById('mo-orc').style.display = 'flex';
}

function orcCalcTotal() {
  let t = 0;
  document.querySelectorAll('.orc-val').forEach(i => { t += parseMoeda(i.value); });
  document.getElementById('orc-mo-total').textContent = new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL'}).format(t);
}

function orcSalvar() {
  const obraId = document.getElementById('orc-obra-sel').value;
  const versaoAtual = parseInt(document.getElementById('orc-versao').textContent) || 0;
  const motivo = document.getElementById('orc-motivo').value.trim();
  if (versaoAtual >= 1 && !motivo) { document.getElementById('orc-err').textContent='Motivo obrigatório a partir da versão 2.'; document.getElementById('orc-err').style.display='block'; return; }
  const params = new URLSearchParams({ obra_id: obraId, data_vigencia: document.getElementById('orc-data-vig').value, motivo_revisao: motivo });
  document.querySelectorAll('.orc-val').forEach(i => { params.append('valores[' + i.dataset.cat + ']', i.value); });
  fetch('/admin/painel-obras/orcamento/salvar', { method:'POST', body: params })
    .then(r => r.json())
    .then(res => {
      if (!res.ok) { document.getElementById('orc-err').textContent=res.error||'Erro'; document.getElementById('orc-err').style.display='block'; return; }
      document.getElementById('mo-orc').style.display='none';
      orcCarregar();
    }).catch(() => { document.getElementById('orc-err').textContent='Falha de conexão.'; document.getElementById('orc-err').style.display='block'; });
}

function mascaraMoeda(el) {
  let v = el.value.replace(/\D/g,'');
  v = (parseInt(v||'0')/100).toFixed(2);
  el.value = 'R$ ' + v.replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.');
}
function parseMoeda(v) { return parseFloat(String(v).replace(/[R$\s.]/g,'').replace(',','.'))||0; }
</script>
<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/Views/admin/layouts/app.php';
