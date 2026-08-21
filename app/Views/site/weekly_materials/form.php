<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista Semanal de Materiais | Brooks Construtora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/audio-recorder.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
        .page-header { background: #3a3b4e; color: #fff; padding: 1rem 0; }
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
                    <?php if (!empty($request['construction_site_name'])): ?>
                    — Obra: <?= htmlspecialchars($request['construction_site_name']) ?>
                    <?php endif; ?>
                </p>
            </div>

            <form method="POST" action="/lista-semanal/enviar/<?= htmlspecialchars($token) ?>" id="weeklyForm" enctype="multipart/form-data">
                <div class="card-body p-3">
                    <!-- Itens -->
                    <h6 class="mb-2"><i class="bi bi-list-check"></i> Materiais Necessários</h6>
                    <div id="itemsContainer">
                        <div class="item-row border rounded p-2 mb-2">
                            <div class="row g-2">
                                <div class="col-12 col-sm-5">
                                    <input type="text" class="form-control form-control-sm" name="items[0][material_name]" placeholder="Nome do material *" required>
                                </div>
                                <div class="col-4 col-sm-2">
                                    <input type="number" class="form-control form-control-sm" name="items[0][quantity]" placeholder="Qtd" min="0.01" step="0.01" value="1">
                                </div>
                                <div class="col-4 col-sm-2">
                                    <input type="text" class="form-control form-control-sm" name="items[0][unit]" placeholder="Un.">
                                </div>
                                <div class="col-4 col-sm-3">
                                    <input type="text" class="form-control form-control-sm" name="items[0][notes]" placeholder="Obs.">
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
                        <div id="audio-recorder-weekly"></div>
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
    <script src="/assets/js/audio-recorder.js"></script>
    <script>
    let itemCount = 1;
    document.getElementById('addItemBtn').addEventListener('click', function() {
        const container = document.getElementById('itemsContainer');
        const html = `<div class="item-row border rounded p-2 mb-2">
            <div class="row g-2">
                <div class="col-12 col-sm-5">
                    <input type="text" class="form-control form-control-sm" name="items[${itemCount}][material_name]" placeholder="Nome do material *" required>
                </div>
                <div class="col-4 col-sm-2">
                    <input type="number" class="form-control form-control-sm" name="items[${itemCount}][quantity]" placeholder="Qtd" min="0.01" step="0.01" value="1">
                </div>
                <div class="col-4 col-sm-2">
                    <input type="text" class="form-control form-control-sm" name="items[${itemCount}][unit]" placeholder="Un.">
                </div>
                <div class="col-3 col-sm-2">
                    <input type="text" class="form-control form-control-sm" name="items[${itemCount}][notes]" placeholder="Obs.">
                </div>
                <div class="col-1 d-flex align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.item-row').remove()"><i class="bi bi-x"></i></button>
                </div>
            </div>
        </div>`;
        container.insertAdjacentHTML('beforeend', html);
        itemCount++;
    });

    // Áudio simples (upload junto com form via hidden input - simplificado)
    // O áudio aqui é opcional, o form envia normal via multipart
    </script>
</body>
</html>
