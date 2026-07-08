<?php
$recipientType = $recipientType ?? 'worker';
$isThird = $recipientType === 'third_party';
$pageTitle = $isThird ? 'Distribuição de EPIs para Terceiros' : 'Registro de Entrega de EPIs';
$currentPage = $isThird ? 'epi_thirdparty' : 'epi_delivery';
$formAction = $isThird ? '/distribuicao-terceiros/salvar' : '/registro-de-entrega/salvar';
$searchAction = $isThird ? '/distribuicao-terceiros/buscar' : '/registro-de-entrega/buscar-colaborador';
$recipientLabel = $isThird ? 'Terceiro' : 'Colaborador';
$user = $user ?? \App\Core\Auth::user();
?>
<?php ob_start(); ?>

<style>
    .evidence-box { border: 2px dashed #ced4da; border-radius: 12px; padding: 1rem; text-align: center; background: #fff; }
    .evidence-box.filled { border-color: #28a745; border-style: solid; }
    .evidence-preview { max-width: 100%; max-height: 220px; border-radius: 8px; margin-top: 0.5rem; }
    #signaturePad { border: 1px solid #ced4da; border-radius: 8px; width: 100%; height: 180px; touch-action: none; background: #fff; }
    .cam-video { width: 100%; max-height: 320px; border-radius: 8px; background: #000; }
</style>

<div style="max-width: 800px;">
    <?php if ($isThird): ?>
    <div class="alert alert-info py-2 small"><i class="bi bi-info-circle"></i> Distribuição para pessoas fora do quadro de colaboradores (prestadores, visitantes, terceirizadas). Os dados são cadastrados manualmente e não ficam vinculados ao cadastro de colaboradores.</div>
    <?php endif; ?>
    <form method="POST" action="<?= $formAction ?>" id="deliveryForm">
        <!-- Dados do destinatário -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-bold"><i class="bi bi-person-badge text-primary"></i> Dados do <?= $recipientLabel ?></div>
            <div class="card-body row g-2">
                <div class="col-md-6 position-relative">
                    <label class="form-label small fw-bold">Nome *</label>
                    <input type="text" class="form-control" name="worker_name" id="workerName" autocomplete="off" placeholder="Digite para buscar ou cadastrar..." required>
                    <div id="workerSuggestions" class="list-group position-absolute w-100 shadow-sm" style="z-index:1050; display:none; max-height:240px; overflow-y:auto;"></div>
                </div>
                <div class="col-md-3"><label class="form-label small fw-bold">CPF ou Matrícula *</label><input type="text" class="form-control" name="worker_document" id="workerDocument" required></div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Cargo *</label>
                    <select class="form-select" name="worker_role" id="workerRole" required>
                        <option value="">Selecione...</option>
                        <?php
                        $cargos = [
                            'Servente', 'Ajudante Geral', 'Pedreiro', 'Meio-Oficial', 'Carpinteiro',
                            'Armador', 'Pintor', 'Eletricista', 'Encanador', 'Gesseiro',
                            'Azulejista', 'Ferreiro', 'Soldador', 'Operador de Máquinas',
                            'Operador de Guindaste', 'Motorista', 'Almoxarife', 'Apontador',
                            'Mestre de Obras', 'Encarregado', 'Técnico em Segurança do Trabalho',
                            'Técnico em Edificações', 'Engenheiro Civil', 'Arquiteto',
                            'Estagiário', 'Vigia', 'Auxiliar Administrativo',
                        ];
                        foreach ($cargos as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Entrega -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-bold"><i class="bi bi-box-seam text-primary"></i> Entrega</div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-6"><label class="form-label small fw-bold">Data e hora</label><input type="text" class="form-control" value="<?= date('d/m/Y H:i') ?>" disabled></div>
                    <div class="col-md-6"><label class="form-label small fw-bold">Responsável pela entrega *</label><input type="text" class="form-control" name="delivered_by" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required></div>
                </div>

                <label class="form-label small fw-bold">Adicionar EPI</label>
                <select id="epiSelect">
                    <option value="">Selecione um EPI...</option>
                    <?php
                    $episByCat = [];
                    foreach ($epis as $e) { $episByCat[$e['category'] ?: 'Outros'][] = $e; }
                    foreach ($episByCat as $cat => $catEpis): ?>
                    <optgroup label="<?= htmlspecialchars($cat) ?>">
                        <?php foreach ($catEpis as $e): ?>
                        <option value="<?= $e['id'] ?>" data-name="<?= htmlspecialchars($e['name']) ?>" data-ca="<?= htmlspecialchars($e['ca'] ?? '') ?>">
                            <?= htmlspecialchars($e['name']) ?><?= $e['ca'] ? ' (CA ' . htmlspecialchars($e['ca']) . ')' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($epis)): ?>
                <div class="alert alert-warning py-2 small mt-2 mb-0">Nenhum EPI cadastrado. <a href="/cadastro-de-epi">Cadastre primeiro</a>.</div>
                <?php endif; ?>

                <table class="table table-sm mt-3 align-middle" id="epiTable" style="display:none;">
                    <thead class="table-light"><tr><th>EPI</th><th>CA</th><th style="width:110px;">Qtd</th><th style="width:40px;"></th></tr></thead>
                    <tbody id="epiTableBody"></tbody>
                </table>
                <p class="text-muted small mb-0" id="epiEmpty">Nenhum EPI adicionado.</p>
            </div>
        </div>

        <!-- Evidências -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-bold"><i class="bi bi-camera text-primary"></i> Evidências (obrigatórias)</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">📷 Selfie do colaborador *</label>
                    <div class="evidence-box" id="box_selfie">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="openCamera('selfie', 'user')"><i class="bi bi-camera"></i> Abrir câmera</button>
                        <img class="evidence-preview" id="preview_selfie" style="display:none;">
                    </div>
                    <input type="hidden" name="selfie_data" id="input_selfie">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">📷 Colaborador com os EPIs *</label>
                    <div class="evidence-box" id="box_epis">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="openCamera('epis', 'environment')"><i class="bi bi-camera"></i> Abrir câmera</button>
                        <img class="evidence-preview" id="preview_epis" style="display:none;">
                    </div>
                    <input type="hidden" name="epis_photo_data" id="input_epis">
                </div>
            </div>
        </div>

        <!-- Confirmação -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-bold"><i class="bi bi-check2-square text-primary"></i> Confirmação e Assinaturas</div>
            <div class="card-body">
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" name="confirmed" id="confirmChk" value="1" required>
                    <label class="form-check-label small" for="confirmChk">Confirmo que realizei a entrega dos EPIs acima ao <?= strtolower($recipientLabel) ?> identificado neste registro.</label>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Assinatura do <?= strtolower($recipientLabel) ?> *</label>
                        <canvas id="signatureWorker" class="signature-canvas" style="border:1px solid #ced4da; border-radius:8px; width:100%; height:170px; touch-action:none; background:#fff;"></canvas>
                        <div class="mt-1"><button type="button" class="btn btn-sm btn-outline-secondary" onclick="padWorker.clear()"><i class="bi bi-eraser"></i> Limpar</button></div>
                        <input type="hidden" name="worker_signature_data" id="input_worker_signature">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Assinatura do responsável *</label>
                        <canvas id="signaturePad" class="signature-canvas" style="border:1px solid #ced4da; border-radius:8px; width:100%; height:170px; touch-action:none; background:#fff;"></canvas>
                        <div class="mt-1"><button type="button" class="btn btn-sm btn-outline-secondary" onclick="padResponsible.clear()"><i class="bi bi-eraser"></i> Limpar</button></div>
                        <input type="hidden" name="signature_data" id="input_signature">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-save"></i> Registrar Entrega</button>
    </form>
</div>

<!-- Modal Câmera -->
<div class="modal fade" id="camModal" tabindex="-1" data-bs-backdrop="static"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header py-2"><h6 class="modal-title"><i class="bi bi-camera"></i> Capturar foto</h6><button type="button" class="btn-close" data-bs-dismiss="modal" onclick="stopCamera()"></button></div>
    <div class="modal-body text-center">
        <video id="camVideo" class="cam-video" autoplay playsinline></video>
        <canvas id="camCanvas" style="display:none;"></canvas>
    </div>
    <div class="modal-footer py-2"><button type="button" class="btn btn-primary w-100" onclick="capturePhoto()"><i class="bi bi-camera-fill"></i> Tirar foto</button></div>
</div></div></div>

<script src="/assets/js/searchable-select.js"></script>
<script src="/assets/js/signature-pad.js"></script>
<script>
// ---- Busca dinâmica de colaboradores ----
(function() {
    const input = document.getElementById('workerName');
    const box = document.getElementById('workerSuggestions');
    const docField = document.getElementById('workerDocument');
    const roleField = document.getElementById('workerRole');
    let timer = null;

    function hide() { box.style.display = 'none'; box.innerHTML = ''; }

    function selectWorker(w) {
        input.value = w.name;
        docField.value = w.document;
        // Define o cargo se existir na lista de opções
        if (w.role) {
            const opt = Array.from(roleField.options).find(o => o.value === w.role);
            if (opt) roleField.value = w.role;
        }
        hide();
    }

    input.addEventListener('input', function() {
        const term = this.value.trim();
        clearTimeout(timer);
        if (term.length < 1) { hide(); return; }
        timer = setTimeout(() => {
            fetch('<?= $searchAction ?>?q=' + encodeURIComponent(term))
                .then(r => r.json())
                .then(d => {
                    const workers = d.workers || [];
                    if (workers.length === 0) { hide(); return; }
                    box.innerHTML = '';
                    workers.forEach(w => {
                        const a = document.createElement('button');
                        a.type = 'button';
                        a.className = 'list-group-item list-group-item-action py-2';
                        a.innerHTML = `<strong>${w.name}</strong> <span class="text-muted small">· ${w.document}${w.role ? ' · ' + w.role : ''}</span>`;
                        a.addEventListener('click', () => selectWorker(w));
                        box.appendChild(a);
                    });
                    box.style.display = 'block';
                })
                .catch(hide);
        }, 250);
    });

    document.addEventListener('click', function(e) {
        if (e.target !== input && !box.contains(e.target)) hide();
    });
})();

// ---- Seleção de EPIs ----
let epiItems = [];
const epiSelectEl = document.getElementById('epiSelect');
const ss = new SearchableSelect(epiSelectEl, {
    placeholder: 'Buscar EPI...',
    onSelect: (value, text, dataset) => {
        if (!value) return;
        addEpi(value, dataset.name, dataset.ca);
        ss.clear();
    }
});

function addEpi(id, name, ca) {
    if (epiItems.some(i => i.id === id)) return;
    epiItems.push({ id, name, ca });
    renderEpis();
}
function removeEpi(id) { epiItems = epiItems.filter(i => i.id !== id); renderEpis(); }
function renderEpis() {
    const body = document.getElementById('epiTableBody');
    const table = document.getElementById('epiTable');
    const empty = document.getElementById('epiEmpty');
    body.innerHTML = '';
    if (epiItems.length === 0) { table.style.display = 'none'; empty.style.display = 'block'; return; }
    table.style.display = 'table'; empty.style.display = 'none';
    epiItems.forEach(i => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${i.name}<input type="hidden" name="epi_id[]" value="${i.id}"></td>
            <td>${i.ca || '—'}</td>
            <td><input type="number" class="form-control form-control-sm" name="quantity[]" value="1" min="1" step="1" required></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="removeEpi('${i.id}')"><i class="bi bi-x"></i></button></td>`;
        body.appendChild(tr);
    });
}

// ---- Câmera ----
let camStream = null, camTarget = null, camModal = null;
async function openCamera(target, facing) {
    camTarget = target;
    camModal = new bootstrap.Modal(document.getElementById('camModal'));
    camModal.show();
    try {
        camStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: facing }, audio: false });
        document.getElementById('camVideo').srcObject = camStream;
    } catch (err) {
        alert('Não foi possível acessar a câmera: ' + err.message);
        camModal.hide();
    }
}
function stopCamera() {
    if (camStream) { camStream.getTracks().forEach(t => t.stop()); camStream = null; }
}
function capturePhoto() {
    const video = document.getElementById('camVideo');
    const canvas = document.getElementById('camCanvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
    document.getElementById('input_' + camTarget).value = dataUrl;
    const preview = document.getElementById('preview_' + camTarget);
    preview.src = dataUrl; preview.style.display = 'block';
    document.getElementById('box_' + camTarget).classList.add('filled');
    stopCamera();
    camModal.hide();
}

// ---- Assinaturas (componente reutilizável) ----
const padWorker = new SignaturePad(document.getElementById('signatureWorker'), { hiddenInput: document.getElementById('input_worker_signature') });
const padResponsible = new SignaturePad(document.getElementById('signaturePad'), { hiddenInput: document.getElementById('input_signature') });

// ---- Submit ----
document.getElementById('deliveryForm').addEventListener('submit', function(e) {
    if (epiItems.length === 0) { e.preventDefault(); alert('Adicione ao menos um EPI.'); return; }
    if (!document.getElementById('input_selfie').value) { e.preventDefault(); alert('Capture a selfie do colaborador.'); return; }
    if (!document.getElementById('input_epis').value) { e.preventDefault(); alert('Capture a foto do colaborador com os EPIs.'); return; }
    if (padWorker.isEmpty()) { e.preventDefault(); alert('A assinatura do colaborador é obrigatória.'); return; }
    if (padResponsible.isEmpty()) { e.preventDefault(); alert('A assinatura do responsável é obrigatória.'); return; }
    padWorker.sync();
    padResponsible.sync();
});
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
