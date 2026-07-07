<?php $pageTitle = 'Devoluções e Substituições'; $currentPage = 'epi_replacement'; $user = $user ?? \App\Core\Auth::user(); ?>
<?php ob_start(); ?>

<style>
    .epi-item { border: 1px solid #dee2e6; border-radius: 10px; padding: 0.8rem; margin-bottom: 0.6rem; background: #fff; }
    .epi-item.eligible { border-color: #28a745; }
    .evidence-box { border: 2px dashed #ced4da; border-radius: 12px; padding: 1rem; text-align: center; background: #fff; }
    .evidence-box.filled { border-color: #28a745; border-style: solid; }
    .evidence-preview { max-width: 100%; max-height: 200px; border-radius: 8px; margin-top: 0.5rem; }
    .cam-video { width: 100%; max-height: 320px; border-radius: 8px; background: #000; }
</style>

<div style="max-width: 800px;">
    <!-- Selecionar operário -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-bold"><i class="bi bi-person"></i> Selecione o operário</div>
        <div class="card-body">
            <select id="workerSelect">
                <option value="">Selecione...</option>
                <?php foreach ($workers as $w): ?>
                <option value="<?= htmlspecialchars($w['worker_document']) ?>" data-name="<?= htmlspecialchars($w['worker_name']) ?>">
                    <?= htmlspecialchars($w['worker_name']) ?> — <?= htmlspecialchars($w['worker_document']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php if (empty($workers)): ?>
            <div class="alert alert-warning py-2 small mt-2 mb-0">Nenhum colaborador com EPIs entregues ainda.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Lista de EPIs recebidos -->
    <div id="episArea" style="display:none;">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-bold"><i class="bi bi-list-check"></i> EPIs recebidos por <span id="workerNameLabel"></span></div>
            <div class="card-body">
                <div id="epiList"><p class="text-muted small text-center mb-0">Carregando...</p></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Substituição -->
<div class="modal fade" id="repModal" tabindex="-1" data-bs-backdrop="static"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header py-2 bg-warning"><h6 class="modal-title"><i class="bi bi-arrow-repeat"></i> Fazer substituição</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <input type="hidden" id="repItemId">
        <p class="small mb-3">Substituindo: <strong id="repEpiName"></strong></p>
        <div class="mb-3">
            <label class="form-label small fw-bold">Quantidade a substituir *</label>
            <input type="number" class="form-control" id="repQuantity" min="1" step="1" value="1">
            <small class="text-muted">Disponível: <span id="repMaxQty">1</span></small>
        </div>
        <div class="mb-3">
            <label class="form-label small fw-bold">📷 Foto do material substituído (devolvido) *</label>
            <div class="evidence-box" id="box_old">
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="openCamera('old', 'environment')"><i class="bi bi-camera"></i> Câmera</button>
                    <label class="btn btn-outline-secondary btn-sm mb-0"><i class="bi bi-upload"></i> Upload<input type="file" accept="image/*" hidden onchange="uploadFile(event,'old')"></label>
                </div>
                <img class="evidence-preview" id="preview_old" style="display:none;">
            </div>
        </div>
        <div class="mb-2">
            <label class="form-label small fw-bold">📷 Foto da entrega ao operário *</label>
            <div class="evidence-box" id="box_new">
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="openCamera('new', 'environment')"><i class="bi bi-camera"></i> Câmera</button>
                    <label class="btn btn-outline-secondary btn-sm mb-0"><i class="bi bi-upload"></i> Upload<input type="file" accept="image/*" hidden onchange="uploadFile(event,'new')"></label>
                </div>
                <img class="evidence-preview" id="preview_new" style="display:none;">
            </div>
        </div>
    </div>
    <div class="modal-footer py-2"><button type="button" class="btn btn-warning w-100" id="repSubmitBtn" onclick="submitReplacement()"><i class="bi bi-check-lg"></i> Registrar substituição</button></div>
</div></div></div>

<!-- Modal Devolução -->
<div class="modal fade" id="retModal" tabindex="-1" data-bs-backdrop="static"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header py-2 bg-info text-white"><h6 class="modal-title"><i class="bi bi-box-arrow-in-left"></i> Registrar devolução</h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <input type="hidden" id="retItemId">
        <p class="small mb-3">Devolvendo: <strong id="retEpiName"></strong></p>
        <div class="mb-3">
            <label class="form-label small fw-bold">Quantidade a devolver *</label>
            <input type="number" class="form-control" id="retQuantity" min="1" step="1" value="1">
            <small class="text-muted">Em posse do colaborador: <span id="retMaxQty">1</span></small>
        </div>
        <div class="mb-3">
            <label class="form-label small fw-bold">Observações</label>
            <textarea class="form-control" id="retNotes" rows="2" placeholder="Motivo, estado do EPI, etc. (opcional)"></textarea>
        </div>
        <div class="mb-2">
            <label class="form-label small fw-bold">📷 Foto do EPI devolvido (opcional)</label>
            <div class="evidence-box" id="box_ret">
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="openCamera('ret', 'environment')"><i class="bi bi-camera"></i> Câmera</button>
                    <label class="btn btn-outline-secondary btn-sm mb-0"><i class="bi bi-upload"></i> Upload<input type="file" accept="image/*" hidden onchange="uploadFile(event,'ret')"></label>
                </div>
                <img class="evidence-preview" id="preview_ret" style="display:none;">
            </div>
        </div>
    </div>
    <div class="modal-footer py-2"><button type="button" class="btn btn-info text-white w-100" id="retSubmitBtn" onclick="submitReturn()"><i class="bi bi-check-lg"></i> Registrar devolução</button></div>
</div></div></div>

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
<script>
const photos = { old: null, new: null, ret: null };
let repModal = null;
let retModal = null;

const ss = new SearchableSelect(document.getElementById('workerSelect'), {
    placeholder: 'Buscar operário...',
    onSelect: (value, text, dataset) => { if (value) loadItems(value, dataset.name); }
});

function loadItems(doc, name) {
    document.getElementById('episArea').style.display = 'block';
    document.getElementById('workerNameLabel').textContent = name;
    const list = document.getElementById('epiList');
    list.innerHTML = '<p class="text-muted small text-center mb-0">Carregando...</p>';
    fetch('/substituicao-de-epi/itens?document=' + encodeURIComponent(doc))
        .then(r => r.json())
        .then(d => renderItems(d.items || []))
        .catch(() => { list.innerHTML = '<p class="text-danger small text-center mb-0">Erro ao carregar.</p>'; });
}

function ordinal(n) {
    return n + 'ª';
}

function renderItems(items) {
    const list = document.getElementById('epiList');
    if (items.length === 0) { list.innerHTML = '<p class="text-muted small text-center mb-0">Nenhum EPI ativo para este colaborador.</p>'; return; }
    list.innerHTML = '';
    items.forEach(it => {
        const div = document.createElement('div');
        div.className = 'epi-item' + (it.eligible ? ' eligible' : '');
        const deliveredDate = new Date(it.delivered_at.replace(' ', 'T')).toLocaleDateString('pt-BR');
        const btnLabel = it.replacement_count > 0
            ? `Fazer ${ordinal(it.next_sequence)} substituição`
            : 'Fazer substituição';
        // Substituição
        let repBtnHtml;
        if (it.eligible) {
            repBtnHtml = `<button class="btn btn-warning btn-sm" onclick='openReplacement(${JSON.stringify(it)})'><i class="bi bi-arrow-repeat"></i> ${btnLabel}</button>`;
        } else {
            repBtnHtml = `<button class="btn btn-warning btn-sm" disabled title="Prazo mínimo não atingido"><i class="bi bi-lock"></i> ${btnLabel}</button>`;
        }
        // Devolução: só habilita se ainda houver quantidade em posse
        let retBtnHtml;
        if (it.available_to_return > 0) {
            retBtnHtml = `<button class="btn btn-info btn-sm text-white" onclick='openReturn(${JSON.stringify(it)})'><i class="bi bi-box-arrow-in-left"></i> Devolver</button>`;
        } else {
            retBtnHtml = `<button class="btn btn-info btn-sm text-white" disabled title="Nada a devolver"><i class="bi bi-lock"></i> Devolver</button>`;
        }

        const statusHtml = it.eligible
            ? '<span class="badge bg-success">Liberado para troca</span>'
            : `<span class="badge bg-secondary">Troca em ${it.days_remaining} dia(s)</span>`;

        let countBadge = '';
        if (it.replacement_count > 0) {
            countBadge = `<span class="badge bg-warning text-dark ms-1"><i class="bi bi-arrow-repeat"></i> ${it.replacement_count} troca(s)</span>`;
        }
        let posseBadge = `<span class="badge bg-light text-dark border ms-1">Em posse: ${it.available_to_return}</span>`;
        if (it.returned_quantity > 0) {
            posseBadge += `<span class="badge bg-secondary ms-1">Devolvido: ${it.returned_quantity}</span>`;
        }
        const refInfo = it.replacement_count > 0
            ? `Última troca: ${it.last_replaced_at ? new Date(it.last_replaced_at.replace(' ', 'T')).toLocaleDateString('pt-BR') : '-'}`
            : `Entregue: ${deliveredDate}`;
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <div class="fw-bold">${it.epi_name} ${countBadge}${posseBadge}</div>
                    <div class="small text-muted">${it.ca ? 'CA ' + it.ca + ' · ' : ''}Entregue: ${it.quantity} · ${refInfo}</div>
                    <div class="small text-muted">Prazo mínimo: ${it.min_days} dia(s) · Decorridos desde a referência: ${it.days_elapsed} dia(s)</div>
                    <div class="mt-1">${statusHtml}</div>
                </div>
                <div class="d-flex flex-column gap-1">${repBtnHtml}${retBtnHtml}</div>
            </div>`;
        list.appendChild(div);
    });
}

function openReplacement(it) {
    document.getElementById('repItemId').value = it.id;
    const seqLabel = it.next_sequence > 1 ? ` — ${ordinal(it.next_sequence)} substituição` : '';
    document.getElementById('repEpiName').textContent = it.epi_name + (it.ca ? ' (CA ' + it.ca + ')' : '') + seqLabel;
    const qtyInput = document.getElementById('repQuantity');
    qtyInput.value = it.quantity;
    qtyInput.max = it.quantity;
    document.getElementById('repMaxQty').textContent = it.quantity;
    photos.old = null; photos.new = null;
    ['old', 'new'].forEach(t => {
        document.getElementById('preview_' + t).style.display = 'none';
        document.getElementById('box_' + t).classList.remove('filled');
    });
    repModal = new bootstrap.Modal(document.getElementById('repModal'));
    repModal.show();
}

function uploadFile(e, target) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = () => setPhoto(target, reader.result);
    reader.readAsDataURL(file);
}
function setPhoto(target, dataUrl) {
    photos[target] = dataUrl;
    const p = document.getElementById('preview_' + target);
    p.src = dataUrl; p.style.display = 'block';
    document.getElementById('box_' + target).classList.add('filled');
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
    } catch (err) { alert('Não foi possível acessar a câmera: ' + err.message); camModal.hide(); }
}
function stopCamera() { if (camStream) { camStream.getTracks().forEach(t => t.stop()); camStream = null; } }
function capturePhoto() {
    const video = document.getElementById('camVideo');
    const canvas = document.getElementById('camCanvas');
    canvas.width = video.videoWidth; canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    setPhoto(camTarget, canvas.toDataURL('image/jpeg', 0.85));
    stopCamera(); camModal.hide();
}

function submitReplacement() {
    const qty = parseInt(document.getElementById('repQuantity').value) || 0;
    if (qty < 1) { alert('Informe a quantidade a substituir.'); return; }
    if (!photos.old) { alert('Foto do material substituído é obrigatória.'); return; }
    if (!photos.new) { alert('Foto da entrega ao operário é obrigatória.'); return; }
    const btn = document.getElementById('repSubmitBtn');
    btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Salvando...';
    const fd = new FormData();
    fd.append('delivery_item_id', document.getElementById('repItemId').value);
    fd.append('quantity', qty);
    fd.append('old_item_photo_data', photos.old);
    fd.append('new_delivery_photo_data', photos.new);
    fetch('/substituicao-de-epi/salvar', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                repModal.hide();
                const doc = document.getElementById('workerSelect').value;
                const name = document.getElementById('workerNameLabel').textContent;
                loadItems(doc, name);
            } else {
                alert(d.error || 'Erro ao registrar.');
            }
        })
        .catch(() => alert('Sem conexão.'))
        .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg"></i> Registrar substituição'; });
}

// ---- Devolução ----
function openReturn(it) {
    document.getElementById('retItemId').value = it.id;
    document.getElementById('retEpiName').textContent = it.epi_name + (it.ca ? ' (CA ' + it.ca + ')' : '');
    const qtyInput = document.getElementById('retQuantity');
    qtyInput.value = it.available_to_return;
    qtyInput.max = it.available_to_return;
    document.getElementById('retMaxQty').textContent = it.available_to_return;
    document.getElementById('retNotes').value = '';
    photos.ret = null;
    document.getElementById('preview_ret').style.display = 'none';
    document.getElementById('box_ret').classList.remove('filled');
    retModal = new bootstrap.Modal(document.getElementById('retModal'));
    retModal.show();
}

function submitReturn() {
    const qty = parseInt(document.getElementById('retQuantity').value) || 0;
    const max = parseInt(document.getElementById('retMaxQty').textContent) || 0;
    if (qty < 1) { alert('Informe a quantidade a devolver.'); return; }
    if (qty > max) { alert('Quantidade superior à que o colaborador ainda possui.'); return; }
    const btn = document.getElementById('retSubmitBtn');
    btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Salvando...';
    const fd = new FormData();
    fd.append('delivery_item_id', document.getElementById('retItemId').value);
    fd.append('quantity', qty);
    fd.append('notes', document.getElementById('retNotes').value);
    if (photos.ret) fd.append('photo_data', photos.ret);
    fetch('/substituicao-de-epi/devolver', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                retModal.hide();
                const doc = document.getElementById('workerSelect').value;
                const name = document.getElementById('workerNameLabel').textContent;
                loadItems(doc, name);
            } else {
                alert(d.error || 'Erro ao registrar.');
            }
        })
        .catch(() => alert('Sem conexão.'))
        .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg"></i> Registrar devolução'; });
}
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
