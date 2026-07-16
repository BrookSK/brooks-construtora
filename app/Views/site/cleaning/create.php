<?php
$pageTitle = 'Novo Checklist de Limpeza';
$currentPage = 'cleaning_create';
$user = $user ?? \App\Core\Auth::user();
?>
<?php ob_start(); ?>

<div style="max-width: 900px;">
    <?php if (!empty($flash) && $flash['type'] === 'error'): ?>
    <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($flash['message']) ?></div>
    <?php endif; ?>

    <form method="POST" action="/checklist-limpeza/salvar" id="checklistForm">
        <!-- Dados Gerais -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-bold"><i class="bi bi-clipboard-check text-primary"></i> Dados da Inspeção</div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Data da Realização *</label>
                    <input type="date" class="form-control" name="performed_at" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Responsável pela Atividade *</label>
                    <input type="text" class="form-control" name="responsible_name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Responsável pela Inspeção</label>
                    <input type="text" class="form-control" name="inspector_name" placeholder="Nome do inspetor">
                </div>
            </div>
        </div>

        <!-- Setores -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-bold"><i class="bi bi-building text-primary"></i> Setores Realizados *</div>
            <div class="card-body">
                <p class="text-muted small mb-3"><i class="bi bi-info-circle"></i> Selecione os setores que serão inspecionados para visualizar os itens de verificação.</p>
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($defaultItems as $key => $sector): ?>
                    <div class="form-check">
                        <input class="form-check-input sector-check" type="checkbox" name="sectors[]" value="<?= $key ?>" id="sector_<?= $key ?>">
                        <label class="form-check-label fw-bold small" for="sector_<?= $key ?>"><?= htmlspecialchars($sector['label']) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Itens do Checklist por Setor -->
        <?php foreach ($defaultItems as $sectorKey => $sector): ?>
        <div class="card border-0 shadow-sm mb-3 sector-card" id="card_<?= $sectorKey ?>" style="display:none;">
            <div class="card-header bg-white fw-bold">
                <i class="bi bi-check2-square text-success"></i> <?= htmlspecialchars($sector['label']) ?>
                <span class="badge bg-secondary ms-2 small"><?= count($sector['items']) ?> itens</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:5%">#</th>
                                <th style="width:40%">Verificação</th>
                                <th style="width:10%" class="text-center">C</th>
                                <th style="width:10%" class="text-center">NC</th>
                                <th style="width:10%" class="text-center">NA</th>
                                <th style="width:25%">Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sector['items'] as $idx => $item): ?>
                            <tr>
                                <td class="text-muted small"><?= $idx + 1 ?></td>
                                <td class="small"><?= htmlspecialchars($item) ?></td>
                                <td class="text-center">
                                    <input type="radio" class="form-check-input" name="item_<?= $sectorKey ?>_<?= $idx ?>" value="c">
                                </td>
                                <td class="text-center">
                                    <input type="radio" class="form-check-input" name="item_<?= $sectorKey ?>_<?= $idx ?>" value="nc">
                                </td>
                                <td class="text-center">
                                    <input type="radio" class="form-check-input" name="item_<?= $sectorKey ?>_<?= $idx ?>" value="na" checked>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" name="obs_<?= $sectorKey ?>_<?= $idx ?>" placeholder="—">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Observações Gerais -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-bold"><i class="bi bi-chat-left-text text-primary"></i> Observações Gerais</div>
            <div class="card-body">
                <textarea class="form-control" name="observations" rows="3" placeholder="Observações adicionais (opcional)"></textarea>
            </div>
        </div>

        <!-- Assinatura -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-bold"><i class="bi bi-pen text-primary"></i> Assinatura do Responsável pela Inspeção</div>
            <div class="card-body">
                <canvas id="signaturePad" style="border:1px solid #ced4da; border-radius:8px; width:100%; height:150px; touch-action:none; background:#fff;"></canvas>
                <div class="mt-1">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSignature"><i class="bi bi-eraser"></i> Limpar</button>
                </div>
                <input type="hidden" name="signature_data" id="input_signature">
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-check-circle"></i> Registrar Checklist</button>
    </form>
</div>

<script src="/assets/js/signature-pad.js"></script>
<script>
// Mostrar/ocultar setores com base nos checkboxes
document.querySelectorAll('.sector-check').forEach(cb => {
    cb.addEventListener('change', function() {
        const card = document.getElementById('card_' + this.value);
        if (card) card.style.display = this.checked ? '' : 'none';
    });
});

// Signature pad
(function() {
    const canvas = document.getElementById('signaturePad');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    let drawing = false;

    function resize() {
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width;
        canvas.height = rect.height;
    }
    resize();
    window.addEventListener('resize', resize);

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        const t = e.touches ? e.touches[0] : e;
        return { x: t.clientX - rect.left, y: t.clientY - rect.top };
    }

    canvas.addEventListener('mousedown', e => { drawing = true; ctx.beginPath(); const p = getPos(e); ctx.moveTo(p.x, p.y); });
    canvas.addEventListener('mousemove', e => { if (!drawing) return; const p = getPos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); });
    canvas.addEventListener('mouseup', () => { drawing = false; });
    canvas.addEventListener('mouseleave', () => { drawing = false; });

    canvas.addEventListener('touchstart', e => { e.preventDefault(); drawing = true; ctx.beginPath(); const p = getPos(e); ctx.moveTo(p.x, p.y); });
    canvas.addEventListener('touchmove', e => { e.preventDefault(); if (!drawing) return; const p = getPos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); });
    canvas.addEventListener('touchend', () => { drawing = false; });

    ctx.strokeStyle = '#000';
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';

    document.getElementById('clearSignature').addEventListener('click', () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    });

    document.getElementById('checklistForm').addEventListener('submit', () => {
        document.getElementById('input_signature').value = canvas.toDataURL('image/png');
    });
})();
</script>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/app/Views/admin/layouts/app.php';
?>
