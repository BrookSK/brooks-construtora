<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitação de Materiais | Brooks Construtora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/searchable-select.css" rel="stylesheet">
    <link href="/assets/css/audio-recorder.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; min-height: 100vh; padding-bottom: 90px; }
        .page-header { background: #3a3b4e; color: #fff; padding: 1rem 0; }
        .item-card { border:1px solid #e0e0e0; border-radius:8px; padding:12px; margin-bottom:10px; background:#fafbfc; position:relative; }
        .item-card .item-number { position:absolute; top:-8px; left:10px; background:#3a3b4e; color:#fff; font-size:0.65rem; padding:1px 8px; border-radius:10px; }
        .item-card .item-details { display:flex; flex-wrap:wrap; gap:4px; margin-top:6px; }
        .item-card .item-details .badge { font-weight:400; font-size:0.7rem; }
        @media (max-width: 576px) {
            .form-control, .form-select, .ss-input { font-size: 16px !important; }
        }
    </style>
</head>
<body>
    <div class="page-header text-center">
        <div class="container">
            <h5 class="mb-1">BROOKS CONSTRUTORA</h5>
            <p class="mb-0 opacity-75 small">Solicitação Semanal de Materiais</p>
        </div>
    </div>

    <div class="container py-3" style="max-width:900px;">
        <?php if (!empty($_SESSION['flash'])): ?>
        <div class="alert alert-<?= $_SESSION['flash']['type'] === 'error' ? 'danger' : $_SESSION['flash']['type'] ?>">
            <?= htmlspecialchars($_SESSION['flash']['message']) ?>
        </div>
        <?php unset($_SESSION['flash']); endif; ?>

        <div class="card mb-3">
            <div class="card-header bg-primary bg-opacity-10 border-0 p-3">
                <h5 class="mb-1">Olá, <?= htmlspecialchars($request['manager_name']) ?>!</h5>
                <?php if (!empty($cycleLabel)): ?>
                <div class="mb-2">
                    <span class="badge bg-primary"><i class="bi bi-calendar-week"></i> <?= (int) $cycleLabel['number'] ?>º ciclo</span>
                    <span class="badge bg-info text-dark"><?= htmlspecialchars($cycleLabel['week_of_month']) ?></span>
                </div>
                <p class="mb-0 small">
                    Este pedido é referente ao <strong><?= (int) $cycleLabel['number'] ?>º ciclo</strong>
                    (<strong><?= (int) $cycleLabel['interval'] ?> <?= $cycleLabel['interval'] === 1 ? 'dia' : 'dias' ?></strong>),
                    da <strong><?= htmlspecialchars($cycleLabel['week_of_month']) ?></strong>.
                    <br>
                    Período: <strong><?= htmlspecialchars($cycleLabel['start']) ?></strong> a <strong><?= htmlspecialchars($cycleLabel['end']) ?></strong>.
                    Informe os materiais que você vai precisar neste período.
                </p>
                <?php else: ?>
                <p class="mb-0 text-muted small">
                    Informe os materiais que você vai precisar no ciclo que começa em
                    <strong><?= date('d/m/Y', strtotime($request['week_start'])) ?></strong>
                </p>
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" action="/lista-semanal/enviar/<?= htmlspecialchars($token) ?>" id="orderForm" enctype="multipart/form-data">
            <input type="hidden" name="order_type" value="material">

            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-buildings"></i> Obra *</div>
                <div class="card-body">
                    <?php if (!empty($lockedSite)): ?>
                        <input type="hidden" name="construction_site_id" value="<?= (int) $lockedSite['id'] ?>">
                        <input type="text" class="form-control" value="<?= htmlspecialchars((!empty($lockedSite['code']) ? $lockedSite['code'] . ' - ' : '') . $lockedSite['name']) ?>" readonly>
                        <small class="text-muted d-block mt-1">Este link é específico desta obra.</small>
                    <?php elseif (count($sites) === 1): ?>
                        <input type="hidden" name="construction_site_id" value="<?= (int) $sites[0]['id'] ?>">
                        <input type="text" class="form-control" value="<?= htmlspecialchars(($sites[0]['code'] ? $sites[0]['code'] . ' - ' : '') . $sites[0]['name']) ?>" readonly>
                    <?php else: ?>
                        <select class="form-select" name="construction_site_id" id="constructionSiteSelect" required>
                            <option value="">-- Selecione a obra --</option>
                            <?php foreach ($sites as $s): ?>
                            <option value="<?= (int) $s['id'] ?>" <?= ($preselectedSite == $s['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars(($s['code'] ? $s['code'] . ' - ' : '') . $s['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-calendar-check"></i> Data da Necessidade</div>
                <div class="card-body">
                    <label class="form-label">Preciso deste material até *</label>
                    <input type="date" class="form-control" name="needed_date" id="neededDate" required
                           min="<?= htmlspecialchars($minNeededDate ?? '') ?>"
                           value="<?= htmlspecialchars($defaultNeededDate ?? '') ?>">
                    <small class="text-muted d-block mt-1">A previsão é feita com no mínimo <?= (int) ($minAdvanceDays ?? 15) ?> dias de antecedência. Você pode escolher uma data posterior, se necessário.</small>
                </div>
            </div>

            <div class="card mb-3 border-danger border-opacity-50">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="bg-danger bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px; height:44px;">
                            <i class="bi bi-mic-fill text-danger" style="font-size:1.3rem;"></i>
                        </div>
                        <div>
                            <strong class="d-block" style="font-size:0.95rem;">Gravar Observação em Áudio</strong>
                            <small class="text-muted">Toque para gravar. Quem cotar e aprovar vai ouvir.</small>
                        </div>
                    </div>
                    <input type="hidden" name="audio_uploaded" id="audioUploaded" value="0">
                    <div id="audio-recorder-weekly"></div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-list-check"></i> Itens do Pedido <span class="badge bg-primary ms-1" id="itemCountBadge">0</span></span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#newMaterialModal">
                            <i class="bi bi-box-seam"></i> <span class="d-none d-sm-inline">Novo Material</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" id="addItemBtn">
                            <i class="bi bi-plus"></i> Adicionar Item
                        </button>
                    </div>
                </div>
                <div class="card-body" style="overflow:visible;">
                    <div class="d-none d-md-block">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr class="bg-light">
                                    <th style="min-width:250px;">Material</th>
                                    <th style="min-width:120px;">Especificação</th>
                                    <th style="min-width:100px;">Classificação</th>
                                    <th style="width:90px;">Qtd</th>
                                    <th style="width:50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsBodyDesktop"></tbody>
                        </table>
                        <div class="p-3 text-center text-muted" id="emptyDesktop">
                            <i class="bi bi-inbox"></i> Clique em "Adicionar Item" para começar
                        </div>
                    </div>
                    <div class="d-md-none">
                        <div id="itemsBodyMobile"></div>
                        <div class="text-center text-muted py-2" id="emptyMobile">
                            <i class="bi bi-inbox d-block mb-1" style="font-size:1.5rem;"></i>
                            Nenhum item adicionado
                        </div>
                        <button type="button" class="btn btn-outline-primary w-100 mt-2" id="addItemBtnInline">
                            <i class="bi bi-plus-lg"></i> Adicionar Item
                        </button>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><i class="bi bi-chat-left-text"></i> Observações</div>
                <div class="card-body">
                    <textarea class="form-control" name="notes" rows="3" placeholder="Observações adicionais sobre a solicitação..."></textarea>
                </div>
            </div>
        </form>
    </div>

    <div class="position-fixed start-0 end-0 bg-white border-top shadow" style="z-index:1100; bottom:0;">
        <div class="container p-2" style="max-width:900px;">
            <button type="button" class="btn btn-primary w-100 py-2" onclick="showReview()" style="font-size:1rem;">
                <i class="bi bi-eye"></i> Revisar e Enviar
            </button>
        </div>
    </div>

    <div class="modal fade" id="reviewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Revisão da Solicitação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="reviewBody"></div>
                <div class="modal-footer flex-column flex-sm-row gap-2">
                    <button type="button" class="btn btn-outline-secondary w-100 order-2 order-sm-1" data-bs-dismiss="modal" style="flex:1;">
                        <i class="bi bi-pencil"></i> Voltar e Editar
                    </button>
                    <button type="button" class="btn btn-primary w-100 order-1 order-sm-2" onclick="confirmSubmit()" style="flex:1;">
                        <i class="bi bi-send"></i> Confirmar e Enviar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newMaterialModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Novo Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Nome do Material *</label><input type="text" class="form-control" id="newMatName" required></div>
                    <div class="mb-3">
                        <label class="form-label">Especificação (Tipo)</label>
                        <select class="form-select" id="newMatSpec">
                            <option value="">-- Selecione --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['name']) ?>" data-id="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Classificação</label><input type="text" class="form-control" id="newMatClassification" placeholder="Ex: 100mm"></div>
                    <div class="mb-3">
                        <label class="form-label">Unidade de Medida</label>
                        <select class="form-select" id="newMatUnit">
                            <option value="">-- Selecione --</option>
                            <?php foreach ($units as $u): ?>
                            <option value="<?= $u['id'] ?>" data-abbr="<?= htmlspecialchars($u['abbreviation']) ?>"><?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['abbreviation']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="saveMaterialBtn">Salvar Material</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/searchable-select.js"></script>
    <script src="/assets/js/audio-recorder.js"></script>
    <script>
    window.WEEKLY_TOKEN = <?= json_encode($token) ?>;
    window.WEEKLY_MIN_ADVANCE = <?= (int) ($minAdvanceDays ?? 15) ?>;
    window.WEEKLY_MIN_DATE = <?= json_encode($minNeededDate ?? '') ?>;
    window.WEEKLY_MATERIALS = <?= json_encode(array_values($materials)) ?>;
    </script>
    <script src="/assets/js/weekly-material-form.js"></script>
</body>
</html>
