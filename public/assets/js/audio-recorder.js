/**
 * Audio Recorder Component
 * Componente reutilizável para gravação e playback de áudios nos pedidos.
 * Suporta múltiplas instâncias na mesma página.
 *
 * Uso:
 *   AudioRecorder.init({
 *     container: '#audio-recorder-container',
 *     uploadUrl: '/admin/orders/upload-audio',
 *     deleteUrl: '/admin/orders/delete-audio',
 *     listUrl: '/admin/orders/list-audios',
 *     stage: 'create',
 *     orderId: 123,
 *     token: 'abc...',
 *     recordedBy: 'João',
 *     readOnly: false,
 *     showAllStages: false,
 *     existingAudios: [],
 *   });
 */
const AudioRecorder = (function () {
    'use strict';

    function createInstance(options) {
        let config = Object.assign({
            container: '#audio-recorder-container',
            uploadUrl: '/admin/orders/upload-audio',
            deleteUrl: '/admin/orders/delete-audio',
            listUrl: '/admin/orders/list-audios',
            stage: 'create',
            orderId: null,
            tempKey: null,
            token: null,
            recordedBy: 'Usuário',
            readOnly: false,
            showAllStages: false,
            existingAudios: null,
        }, options);

        let mediaRecorder = null;
        let audioChunks = [];
        let isRecording = false;
        let recordingStartTime = 0;
        let timerInterval = null;
        let stream = null;
        let currentAudio = null;
        let currentPlayBtn = null;
        let currentAudioId = null;
        let playbackInterval = null;

        // Unique ID para evitar conflitos de DOM entre instâncias
        const uid = Math.random().toString(36).substr(2, 6);

        const container = document.querySelector(config.container);
        if (!container) return null;

        renderUI();

        if (config.existingAudios) {
            renderAudioList(config.existingAudios);
        } else {
            loadAudios();
        }

        function renderUI() {
            container.innerHTML = `
                <div class="audio-recorder-widget">
                    <div class="audio-list" id="audioList-${uid}"></div>
                    ${config.readOnly ? '' : `
                    <div class="audio-controls mt-2">
                        <div class="d-flex align-items-center gap-2" id="audioRecordArea-${uid}">
                            <button type="button" class="btn btn-outline-danger btn-sm audio-start-btn" title="Gravar áudio">
                                <i class="bi bi-mic-fill"></i> Toque aqui para gravar áudio
                            </button>
                        </div>
                        <div class="d-none align-items-center gap-2" id="audioRecordingArea-${uid}">
                            <div class="recording-indicator">
                                <span class="recording-dot"></span>
                                <span class="recording-timer" id="recordingTimer-${uid}">00:00</span>
                            </div>
                            <button type="button" class="btn btn-danger btn-sm audio-stop-btn" title="Parar gravação">
                                <i class="bi bi-stop-fill"></i> Parar
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm audio-cancel-btn" title="Cancelar">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        <div class="d-none" id="audioUploadingArea-${uid}">
                            <span class="text-muted small"><i class="bi bi-cloud-upload"></i> Enviando áudio...</span>
                        </div>
                    </div>
                    `}
                </div>
            `;

            if (!config.readOnly) {
                container.querySelector('.audio-start-btn').addEventListener('click', startRecording);
                container.querySelector('.audio-stop-btn').addEventListener('click', stopRecording);
                container.querySelector('.audio-cancel-btn').addEventListener('click', cancelRecording);
            }
        }

        function renderAudioList(audios) {
            const list = document.getElementById('audioList-' + uid);
            if (!list) return;

            if (!audios || audios.length === 0) {
                list.innerHTML = config.readOnly ? '<p class="text-muted small mb-0">Nenhum áudio registrado.</p>' : '';
                return;
            }

            const stageLabels = { create: 'Pedido', quote: 'Cotação', approval: 'Aprovação', financial: 'Financeiro' };

            let html = '';
            audios.forEach(function (audio) {
                const duration = audio.duration_seconds ? formatTime(audio.duration_seconds) : '';
                const date = audio.created_at ? formatDate(audio.created_at) : '';
                const stageTag = config.showAllStages && audio.stage ? '<span class="badge bg-secondary me-1" style="font-size:0.65rem;">' + (stageLabels[audio.stage] || audio.stage) + '</span>' : '';
                html += '<div class="audio-item d-flex align-items-center gap-2 p-2 border rounded mb-2" id="audio-item-' + uid + '-' + audio.id + '">'
                    + '<button type="button" class="btn btn-sm btn-outline-primary flex-shrink-0 audio-play-btn" data-url="' + audio.url + '" data-id="' + audio.id + '" title="Reproduzir">'
                    + '<i class="bi bi-play-fill"></i></button>'
                    + '<div class="audio-progress-wrap flex-grow-1 d-none" id="audio-progress-' + uid + '-' + audio.id + '">'
                    + '<div class="progress" style="height:6px; cursor:pointer;" id="audio-bar-' + uid + '-' + audio.id + '">'
                    + '<div class="progress-bar bg-primary" style="width:0%;"></div></div>'
                    + '<small class="text-muted" id="audio-time-' + uid + '-' + audio.id + '" style="font-size:0.7rem;">0:00</small></div>'
                    + '<div class="audio-info flex-grow-1 small text-muted" id="audio-info-' + uid + '-' + audio.id + '">'
                    + stageTag + '<span class="fw-medium">' + escapeHtml(audio.recorded_by || 'Usuário') + '</span>'
                    + (duration ? ' &middot; ' + duration : '')
                    + (date ? ' &middot; ' + date : '') + '</div>'
                    + (!config.readOnly && (!config.showAllStages || audio.stage === config.stage) ? '<button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0 audio-delete-btn" data-id="' + audio.id + '" title="Excluir"><i class="bi bi-trash"></i></button>' : '')
                    + '</div>';
            });

            list.innerHTML = html;

            // Bind play
            list.querySelectorAll('.audio-play-btn').forEach(function (btn) {
                btn.addEventListener('click', function () { playAudio(btn.dataset.url, btn.dataset.id, btn); });
            });

            // Bind delete
            list.querySelectorAll('.audio-delete-btn').forEach(function (btn) {
                btn.addEventListener('click', function () { deleteAudio(btn.dataset.id); });
            });

            // Bind seek
            list.querySelectorAll('.progress').forEach(function (bar) {
                bar.addEventListener('click', function (e) { seekAudio(bar, e); });
            });
        }

        // ─── Gravação ────────────────────────────────────────────────────────

        async function startRecording() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            } catch (err) {
                alert('Não foi possível acessar o microfone. Verifique as permissões do navegador.');
                return;
            }

            audioChunks = [];
            const mimeTypes = ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/mp4'];
            let mimeType = '';
            for (const mt of mimeTypes) {
                if (MediaRecorder.isTypeSupported(mt)) { mimeType = mt; break; }
            }

            const opts = mimeType ? { mimeType: mimeType } : {};
            mediaRecorder = new MediaRecorder(stream, opts);

            mediaRecorder.ondataavailable = function (e) {
                if (e.data.size > 0) audioChunks.push(e.data);
            };

            mediaRecorder.onstop = function () {
                const blob = new Blob(audioChunks, { type: mediaRecorder.mimeType || 'audio/webm' });
                uploadAudio(blob);
                stopStream();
            };

            mediaRecorder.start(1000);
            isRecording = true;
            recordingStartTime = Date.now();
            startTimer();
            showRecordingUI();
        }

        function stopRecording() {
            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                mediaRecorder.stop();
            }
            isRecording = false;
            stopTimer();
            showUploadingUI();
        }

        function cancelRecording() {
            if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                mediaRecorder.ondataavailable = null;
                mediaRecorder.onstop = null;
                mediaRecorder.stop();
            }
            isRecording = false;
            audioChunks = [];
            stopTimer();
            stopStream();
            showIdleUI();
        }

        function stopStream() {
            if (stream) {
                stream.getTracks().forEach(function (t) { t.stop(); });
                stream = null;
            }
        }

        // ─── Upload ──────────────────────────────────────────────────────────

        async function uploadAudio(blob) {
            const formData = new FormData();
            formData.append('audio', blob, 'audio.' + getExtFromMime(blob.type));
            formData.append('stage', config.stage);
            formData.append('duration', Math.round((Date.now() - recordingStartTime) / 1000));

            if (config.orderId) formData.append('order_id', config.orderId);
            if (config.tempKey) formData.append('temp_key', config.tempKey);
            if (config.token) {
                formData.append('token', config.token);
                formData.append('recorded_by', config.recordedBy);
            }

            try {
                const resp = await fetch(config.uploadUrl, { method: 'POST', body: formData });
                const data = await resp.json();
                if (data.success) {
                    loadAudios();
                } else {
                    alert('Erro ao enviar áudio: ' + (data.error || 'Erro desconhecido'));
                }
            } catch (err) {
                alert('Erro de conexão ao enviar áudio.');
            }
            showIdleUI();
        }

        // ─── Playback ────────────────────────────────────────────────────────

        function playAudio(url, audioId, btn) {
            if (currentAudio && currentAudioId === audioId) {
                if (currentAudio.paused) {
                    currentAudio.play();
                    btn.innerHTML = '<i class="bi bi-pause-fill"></i>';
                } else {
                    currentAudio.pause();
                    btn.innerHTML = '<i class="bi bi-play-fill"></i>';
                }
                return;
            }

            if (currentAudio) {
                currentAudio.pause();
                currentAudio = null;
                if (currentPlayBtn) currentPlayBtn.innerHTML = '<i class="bi bi-play-fill"></i>';
                hideProgress(currentAudioId);
                if (playbackInterval) clearInterval(playbackInterval);
            }

            currentAudio = new Audio(url);
            currentPlayBtn = btn;
            currentAudioId = audioId;
            btn.innerHTML = '<i class="bi bi-pause-fill"></i>';
            showProgress(audioId);
            currentAudio.play();

            playbackInterval = setInterval(function () { updateProgress(audioId); }, 200);

            currentAudio.onended = function () {
                btn.innerHTML = '<i class="bi bi-play-fill"></i>';
                hideProgress(audioId);
                if (playbackInterval) clearInterval(playbackInterval);
                currentAudio = null;
                currentPlayBtn = null;
                currentAudioId = null;
            };

            currentAudio.onerror = function () {
                btn.innerHTML = '<i class="bi bi-play-fill"></i>';
                hideProgress(audioId);
                if (playbackInterval) clearInterval(playbackInterval);
                currentAudio = null;
                alert('Erro ao reproduzir o áudio.');
            };
        }

        function showProgress(audioId) {
            const wrap = document.getElementById('audio-progress-' + uid + '-' + audioId);
            const info = document.getElementById('audio-info-' + uid + '-' + audioId);
            if (wrap) wrap.classList.remove('d-none');
            if (info) info.classList.add('d-none');
        }

        function hideProgress(audioId) {
            const wrap = document.getElementById('audio-progress-' + uid + '-' + audioId);
            const info = document.getElementById('audio-info-' + uid + '-' + audioId);
            if (wrap) wrap.classList.add('d-none');
            if (info) info.classList.remove('d-none');
            const bar = document.querySelector('#audio-bar-' + uid + '-' + audioId + ' .progress-bar');
            if (bar) bar.style.width = '0%';
        }

        function updateProgress(audioId) {
            if (!currentAudio || !currentAudio.duration) return;
            const pct = (currentAudio.currentTime / currentAudio.duration) * 100;
            const bar = document.querySelector('#audio-bar-' + uid + '-' + audioId + ' .progress-bar');
            const timeEl = document.getElementById('audio-time-' + uid + '-' + audioId);
            if (bar) bar.style.width = pct + '%';
            if (timeEl) timeEl.textContent = formatTime(Math.floor(currentAudio.currentTime)) + ' / ' + formatTime(Math.floor(currentAudio.duration));
        }

        function seekAudio(barEl, event) {
            if (!currentAudio || !currentAudio.duration) return;
            const rect = barEl.getBoundingClientRect();
            const x = event.clientX - rect.left;
            currentAudio.currentTime = (x / rect.width) * currentAudio.duration;
        }

        // ─── Delete ──────────────────────────────────────────────────────────

        async function deleteAudio(audioId) {
            if (!confirm('Excluir este áudio?')) return;

            const formData = new FormData();
            formData.append('audio_id', audioId);
            if (config.token) formData.append('token', config.token);

            try {
                const resp = await fetch(config.deleteUrl, { method: 'POST', body: formData });
                const data = await resp.json();
                if (data.success) {
                    const item = document.getElementById('audio-item-' + uid + '-' + audioId);
                    if (item) item.remove();
                    if (currentAudioId == audioId && currentAudio) {
                        currentAudio.pause();
                        currentAudio = null;
                        if (playbackInterval) clearInterval(playbackInterval);
                    }
                } else {
                    alert('Erro ao excluir: ' + (data.error || ''));
                }
            } catch (err) {
                alert('Erro de conexão.');
            }
        }

        // ─── Carregar lista ──────────────────────────────────────────────────

        async function loadAudios() {
            let url = config.listUrl + '?';
            if (!config.showAllStages) {
                url += 'stage=' + config.stage + '&';
            }
            if (config.orderId) url += 'order_id=' + config.orderId + '&';
            if (config.tempKey) url += 'temp_key=' + config.tempKey + '&';
            if (config.token) url += 'token=' + config.token + '&';

            try {
                const resp = await fetch(url);
                const data = await resp.json();
                if (data.success) {
                    renderAudioList(data.audios || []);
                }
            } catch (err) { /* silencioso */ }
        }

        // ─── UI States ───────────────────────────────────────────────────────

        function showRecordingUI() {
            const idle = document.getElementById('audioRecordArea-' + uid);
            const recording = document.getElementById('audioRecordingArea-' + uid);
            const uploading = document.getElementById('audioUploadingArea-' + uid);
            if (idle) idle.classList.add('d-none');
            if (recording) { recording.classList.remove('d-none'); recording.classList.add('d-flex'); }
            if (uploading) uploading.classList.add('d-none');
        }

        function showUploadingUI() {
            const idle = document.getElementById('audioRecordArea-' + uid);
            const recording = document.getElementById('audioRecordingArea-' + uid);
            const uploading = document.getElementById('audioUploadingArea-' + uid);
            if (idle) idle.classList.add('d-none');
            if (recording) { recording.classList.add('d-none'); recording.classList.remove('d-flex'); }
            if (uploading) uploading.classList.remove('d-none');
        }

        function showIdleUI() {
            const idle = document.getElementById('audioRecordArea-' + uid);
            const recording = document.getElementById('audioRecordingArea-' + uid);
            const uploading = document.getElementById('audioUploadingArea-' + uid);
            if (idle) idle.classList.remove('d-none');
            if (recording) { recording.classList.add('d-none'); recording.classList.remove('d-flex'); }
            if (uploading) uploading.classList.add('d-none');
        }

        // ─── Timer ───────────────────────────────────────────────────────────

        function startTimer() {
            const timerEl = document.getElementById('recordingTimer-' + uid);
            if (!timerEl) return;
            timerInterval = setInterval(function () {
                const elapsed = Math.floor((Date.now() - recordingStartTime) / 1000);
                timerEl.textContent = formatTime(elapsed);
            }, 500);
        }

        function stopTimer() {
            if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
        }

        // ─── Helpers ─────────────────────────────────────────────────────────

        function formatTime(seconds) {
            const m = Math.floor(seconds / 60);
            const s = seconds % 60;
            return m + ':' + (s < 10 ? '0' : '') + s;
        }

        function formatDate(dateStr) {
            try {
                const d = new Date(dateStr);
                return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            } catch (e) { return dateStr; }
        }

        function getExtFromMime(mime) {
            if (mime.includes('ogg')) return 'ogg';
            if (mime.includes('mp4')) return 'mp4';
            if (mime.includes('mpeg') || mime.includes('mp3')) return 'mp3';
            if (mime.includes('wav')) return 'wav';
            return 'webm';
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        return { loadAudios: loadAudios };
    }

    // ─── Public API ──────────────────────────────────────────────────────────

    return {
        init: function (options) {
            return createInstance(options);
        }
    };
})();
