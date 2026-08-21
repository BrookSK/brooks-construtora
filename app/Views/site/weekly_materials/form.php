<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista Semanal de Materiais | Brooks Construtora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/searchable-select.css" rel="stylesheet">
    <link href="/assets/css/audio-recorder.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
        .page-header { background: #3a3b4e; color: #fff; padding: 1rem 0; }
        .item-row { position: relative; }
        @media (max-width: 576px) {
            .item-row .remove-btn { position: absolute; top: 4px; right: 4px; }
            .card-body { padding: 0.75rem !important; }
            .card-header { padding: 0.75rem !important; }
            .card-header h5 { font-size: 1.1rem; }
            .form-control, .form-select, .ss-input { font-size: 16px !important; }
            .btn-lg { font-size: 1rem; padding: 0.75rem 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="page-header text-center">
        <div class="container">
            <h5 class="mb-1">BROOKS CONSTRUTORA</h5>
            <p class="mb-0 opacity-75 small">Lista Semanal de Materiais</p>
        </div>
    </div>

    <div class="container py-3" style="max-width:700px;">
        <?php if (!empty($_SESSION['flash'])): ?>
        <div class="alert alert-<?= $_SESSION['flash']['type'] === 'error' ? 'danger' : $_SESSION['flash']['type'] ?>">
            <?= htmlspecialchars($_SESSION['flash']['message']) ?>
        </div>
        <?php unset($_SESSION['flash']); endif; ?>

        <div class="card">
            <div class="card-header bg-primary bg-opacity-10 border-0 p-3">
                <h5 class="mb-1">Olá, <?= htmlspecialchars($request['manager_name']) ?>!</h5>
                <p class="mb-0 text-muted small">
                    Informe os materiais que você vai precisar na semana de
                    <strong><?= date('d/m/Y', strtotime($request['week_start'])) ?></strong>
                </p>
            </div>

            <form method="POST" action="/lista-semanal/enviar/<?= htmlspecialchars($token) ?>" id="weeklyForm" enctype="multipart/form-data">
                <div class="card-body p-3">
                    <!-- Itens -->
                    <h6 class="mb-2"><i class="bi bi-list-check"></i> Materiais Necessários</h6>
                    <div id="itemsContainer">
                        <div class="item-row border rounded p-2 mb-2" data-index="0">
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-sm-5">
                                    <label class="form-label small mb-0">Material *</label>
                                    <select class="form-select form-select-sm material-select" name="items[0][material_name]" data-index="0" required style="display:none;">
                                        <option value="">-- Selecione ou digite --</option>
                                        <?php foreach ($materials as $m): ?>
                                        <option value="<?= htmlspecialchars($m['name']) ?>" data-unit="<?= htmlspecialchars($m['unit_abbr'] ?? '') ?>"><?= htmlspecialchars($m['name']) ?><?= !empty($m['specification']) ? ' (' . $m['specification'] . ')' : '' ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-4 col-sm-2">
                                    <label class="form-label small mb-0">Qtd</label>
                                    <input type="number" class="form-control form-control-sm" name="items[0][quantity]" min="0.01" step="0.01" value="1">
                                </div>
                                <div class="col-4 col-sm-2">
                                    <label class="form-label small mb-0">Unidade</label>
                                    <input type="text" class="form-control form-control-sm item-unit" name="items[0][unit]" placeholder="un" data-index="0">
                                </div>
                                <div class="col-4 col-sm-3">
                                    <label class="form-label small mb-0">Obs.</label>
                                    <input type="text" class="form-control form-control-sm" name="items[0][notes]" placeholder="">
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary mb-3" id="addItemBtn">
                        <i class="bi bi-plus-lg"></i> Adicionar Material
                    </button>

                    <!-- Observações -->
                    <div class="mb-3">
                        <label class="form-label">Observações gerais</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Algo que precise explicar..."></textarea>
                    </div>

                    <!-- Áudio -->
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-mic-fill text-danger"></i> Áudio (opcional)</label>
                        <p class="text-muted small mb-2">Grave um áudio se quiser explicar algo que não cabe em texto.</p>
                        <div id="audio-recorder-weekly"></div>
                        <input type="hidden" name="audio_uploaded" id="audioUploaded" value="0">
                    </div>
                </div>

                <div class="card-footer text-center p-3">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                        <i class="bi bi-send"></i> Enviar Lista
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/searchable-select.js"></script>
    <script src="/assets/js/audio-recorder.js"></script>
    <script>
    const materialsData = <?= json_encode(array_map(fn($m) => ['name' => $m['name'], 'spec' => $m['specification'] ?? '', 'unit' => $m['unit_abbr'] ?? ''], $materials)) ?>;
    let itemCount = 1;

    // Inicializar searchable-select no primeiro item
    function initSearchableSelect(selectEl) {
        const idx = selectEl.dataset.index;
        new SearchableSelect(selectEl, {
            placeholder: 'Buscar material ou digitar...',
            allowCreate: true,
            onSelect: function(value, text, dataset) {
                // Auto-preencher unidade
                if (dataset && dataset.unit) {
                    const unitInput = document.querySelector(`.item-unit[data-index="${idx}"]`);
                    if (unitInput && !unitInput.value) {
                        unitInput.value = dataset.unit;
                    }
                }
            }
        });
    }

    // Init primeiro item
    document.querySelectorAll('.material-select').forEach(initSearchableSelect);

    // Adicionar item
    document.getElementById('addItemBtn').addEventListener('click', function() {
        const container = document.getElementById('itemsContainer');
        const div = document.createElement('div');
        div.className = 'item-row border rounded p-2 mb-2';
        div.dataset.index = itemCount;
        div.innerHTML = `
            <div class="row g-2 align-items-end">
                <div class="col-12 col-sm-5">
                    <label class="form-label small mb-0">Material *</label>
                    <select class="form-select form-select-sm material-select" name="items[${itemCount}][material_name]" data-index="${itemCount}" required style="display:none;">
                        <option value="">-- Selecione ou digite --</option>
                        ${materialsData.map(m => `<option value="${m.name}" data-unit="${m.unit}">${m.name}${m.spec ? ' (' + m.spec + ')' : ''}</option>`).join('')}
                    </select>
                </div>
                <div class="col-4 col-sm-2">
                    <label class="form-label small mb-0">Qtd</label>
                    <input type="number" class="form-control form-control-sm" name="items[${itemCount}][quantity]" min="0.01" step="0.01" value="1">
                </div>
                <div class="col-4 col-sm-2">
                    <label class="form-label small mb-0">Unidade</label>
                    <input type="text" class="form-control form-control-sm item-unit" name="items[${itemCount}][unit]" placeholder="un" data-index="${itemCount}">
                </div>
                <div class="col-3 col-sm-2">
                    <label class="form-label small mb-0">Obs.</label>
                    <input type="text" class="form-control form-control-sm" name="items[${itemCount}][notes]">
                </div>
                <div class="col-1 d-flex align-items-end">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.item-row').remove()"><i class="bi bi-x"></i></button>
                </div>
            </div>
        `;
        container.appendChild(div);
        initSearchableSelect(div.querySelector('.material-select'));
        itemCount++;
    });

    // Áudio - gravação local que envia junto com o form via hidden file input
    // Usamos o AudioRecorder para gravar, mas armazenamos o blob localmente
    // e adicionamos ao form no submit
    let audioBlob = null;

    AudioRecorder.init({
        container: '#audio-recorder-weekly',
        uploadUrl: '#', // não faz upload via AJAX
        deleteUrl: '#',
        listUrl: '#',
        stage: 'create',
        recordedBy: '<?= addslashes(htmlspecialchars($request['manager_name'])) ?>',
        readOnly: false,
    });

    // Override: vamos usar uma abordagem mais simples para o áudio aqui
    // O componente AudioRecorder faz upload via AJAX, mas neste form queremos enviar junto
    // Então vou substituir por um gravador inline simples
    document.querySelector('#audio-recorder-weekly').innerHTML = '';

    let mediaRecorder = null;
    let audioChunks = [];
    let isRecording = false;
    let recordingStart = 0;
    let timerInt = null;

    const audioWidget = document.getElementById('audio-recorder-weekly');
    audioWidget.innerHTML = `
        <div class="audio-recorder-widget">
            <div id="audioPlayback"></div>
            <div id="audioIdle" class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-danger btn-sm" id="btnRec" style="width:100%; padding:0.6rem;">
                    <i class="bi bi-mic-fill"></i> Toque aqui para gravar áudio
                </button>
            </div>
            <div id="audioRecording" class="d-none d-flex align-items-center gap-2">
                <span class="recording-indicator"><span class="recording-dot"></span> <span id="recTimer">0:00</span></span>
                <button type="button" class="btn btn-danger btn-sm" id="btnStop"><i class="bi bi-stop-fill"></i> Parar</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCancel"><i class="bi bi-x-lg"></i></button>
            </div>
        </div>
    `;

    document.getElementById('btnRec').addEventListener('click', startRec);
    document.getElementById('btnStop').addEventListener('click', stopRec);
    document.getElementById('btnCancel').addEventListener('click', cancelRec);

    async function startRec() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            audioChunks = [];
            const mimeTypes = ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus'];
            let mimeType = '';
            for (const mt of mimeTypes) { if (MediaRecorder.isTypeSupported(mt)) { mimeType = mt; break; } }
            mediaRecorder = new MediaRecorder(stream, mimeType ? { mimeType } : {});
            mediaRecorder.ondataavailable = e => { if (e.data.size > 0) audioChunks.push(e.data); };
            mediaRecorder.onstop = () => {
                stream.getTracks().forEach(t => t.stop());
                audioBlob = new Blob(audioChunks, { type: mediaRecorder.mimeType || 'audio/webm' });
                showPlayback();
            };
            mediaRecorder.start(1000);
            isRecording = true;
            recordingStart = Date.now();
            timerInt = setInterval(() => {
                const s = Math.floor((Date.now() - recordingStart) / 1000);
                document.getElementById('recTimer').textContent = Math.floor(s/60) + ':' + (s%60<10?'0':'') + s%60;
            }, 500);
            document.getElementById('audioIdle').classList.add('d-none');
            document.getElementById('audioRecording').classList.remove('d-none');
            document.getElementById('audioRecording').classList.add('d-flex');
        } catch(e) {
            alert('Não foi possível acessar o microfone.');
        }
    }

    function stopRec() {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') mediaRecorder.stop();
        isRecording = false;
        clearInterval(timerInt);
        document.getElementById('audioRecording').classList.add('d-none');
        document.getElementById('audioRecording').classList.remove('d-flex');
    }

    function cancelRec() {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.ondataavailable = null;
            mediaRecorder.onstop = () => { mediaRecorder.stream.getTracks().forEach(t => t.stop()); };
            mediaRecorder.stop();
        }
        isRecording = false;
        audioChunks = [];
        audioBlob = null;
        clearInterval(timerInt);
        document.getElementById('audioRecording').classList.add('d-none');
        document.getElementById('audioRecording').classList.remove('d-flex');
        document.getElementById('audioIdle').classList.remove('d-none');
        document.getElementById('audioPlayback').innerHTML = '';
    }

    function showPlayback() {
        const url = URL.createObjectURL(audioBlob);
        document.getElementById('audioPlayback').innerHTML = `
            <div class="d-flex align-items-center gap-2 p-2 border rounded mb-2 bg-white">
                <audio controls src="${url}" style="height:32px; flex-grow:1;"></audio>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeAudio()"><i class="bi bi-trash"></i></button>
            </div>
        `;
        document.getElementById('audioIdle').classList.remove('d-none');
        document.getElementById('audioUploaded').value = '1';
    }

    window.removeAudio = function() {
        audioBlob = null;
        document.getElementById('audioPlayback').innerHTML = '';
        document.getElementById('audioUploaded').value = '0';
    };

    // Interceptar submit para enviar áudio como file
    document.getElementById('weeklyForm').addEventListener('submit', function(e) {
        if (audioBlob) {
            // Criar um File a partir do blob e adicioná-lo ao form
            const dt = new DataTransfer();
            const file = new File([audioBlob], 'audio.webm', { type: audioBlob.type });
            dt.items.add(file);

            // Criar input file hidden
            let audioInput = document.getElementById('audioFileInput');
            if (!audioInput) {
                audioInput = document.createElement('input');
                audioInput.type = 'file';
                audioInput.name = 'audio';
                audioInput.id = 'audioFileInput';
                audioInput.style.display = 'none';
                this.appendChild(audioInput);
            }
            audioInput.files = dt.files;
        }
    });
    </script>
</body>
</html>
