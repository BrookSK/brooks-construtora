<?php
$pageTitle = 'Checklist de Limpeza - Detalhes';
$currentPage = 'cleaning_index';
$user = $user ?? \App\Core\Auth::user();
$items = json_decode($checklist['items'], true) ?: [];
$sectors = json_decode($checklist['sectors'], true) ?: [];
$statusLabels = ['c' => 'Conforme', 'nc' => 'Não Conforme', 'na' => 'Não Aplicável'];
$statusColors = ['c' => 'success', 'nc' => 'danger', 'na' => 'secondary'];
?>
<?php ob_start(); ?>

<div style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="bi bi-clipboard-check text-primary"></i> Checklist de Limpeza</h5>
        <a href="/checklist-limpeza" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Voltar</a>
    </div>

    <!-- Dados Gerais -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body row g-3">
            <div class="col-md-3">
                <small class="text-muted d-block">Data da Realização</small>
                <strong><?= date('d/m/Y', strtotime($checklist['performed_at'])) ?></strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Responsável pela Atividade</small>
                <strong><?= htmlspecialchars($checklist['responsible_name']) ?></strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Responsável pela Inspeção</small>
                <strong><?= htmlspecialchars($checklist['inspector_name'] ?? '—') ?></strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Registrado em</small>
                <strong><?= date('d/m/Y H:i', strtotime($checklist['created_at'])) ?></strong>
            </div>
        </div>
    </div>

    <!-- Itens por Setor -->
    <?php foreach ($items as $sectorKey => $sector): ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-bold">
            <i class="bi bi-check2-square text-success"></i> <?= htmlspecialchars($sector['label'] ?? $sectorKey) ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:5%">#</th>
                            <th style="width:45%">Verificação</th>
                            <th style="width:20%">Status</th>
                            <th style="width:30%">Observações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sector['items'] as $idx => $item): ?>
                        <tr>
                            <td class="text-muted small"><?= $idx + 1 ?></td>
                            <td class="small"><?= htmlspecialchars($item['label']) ?></td>
                            <td>
                                <span class="badge bg-<?= $statusColors[$item['status']] ?? 'secondary' ?>">
                                    <?= $statusLabels[$item['status']] ?? $item['status'] ?>
                                </span>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars($item['obs'] ?? '') ?: '—' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Observações -->
    <?php if (!empty($checklist['observations'])): ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-bold"><i class="bi bi-chat-left-text text-primary"></i> Observações</div>
        <div class="card-body small"><?= nl2br(htmlspecialchars($checklist['observations'])) ?></div>
    </div>
    <?php endif; ?>

    <!-- Assinatura -->
    <?php if (!empty($checklist['signature_data'])): ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-bold"><i class="bi bi-pen text-primary"></i> Assinatura</div>
        <div class="card-body text-center">
            <img src="<?= htmlspecialchars($checklist['signature_data']) ?>" alt="Assinatura" style="max-width: 300px; max-height: 120px;">
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include ROOT_PATH . '/app/Views/admin/layouts/app.php';
?>
