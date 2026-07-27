<?php
$pageTitle = $pageTitle ?? 'Depósitos / Estoques';
$currentPage = 'stock';
ob_start();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <a href="/admin/stock" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Voltar ao Estoque
    </a>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#locationModal" onclick="clearLocationForm()">
        <i class="bi bi-plus-lg"></i> Novo Depósito/Estoque
    </button>
</div>

<?php if (empty($locations)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-geo-alt text-muted" style="font-size:3rem;"></i>
            <p class="text-muted mt-3 mb-2">Nenhum estoque cadastrado.</p>
            <p class="text-muted small">Crie estoques como "Galpão Central", "Estoque Obra X", etc.</p>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($locations as $loc): ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">
                                    <i class="bi bi-geo-alt text-primary"></i>
                                    <?= htmlspecialchars($loc['name']) ?>
                                </h6>
                                <span class="badge bg-light text-dark"><?= htmlspecialchars($loc['code'] ?? '') ?></span>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="/admin/stock?location_id=<?= $loc['id'] ?>"><i class="bi bi-box-seam"></i> Ver Itens</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="editLocation(<?= htmlspecialchars(json_encode($loc)) ?>)"><i class="bi bi-pencil"></i> Editar</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="/admin/stock/delete-location" onsubmit="return confirm('Desativar este estoque?')">
                                            <input type="hidden" name="id" value="<?= $loc['id'] ?>">
                                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash"></i> Desativar</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <?php if (!empty($loc['construction_site_name'])): ?>
                            <div class="small text-muted mt-2"><i class="bi bi-buildings"></i> Obra: <?= htmlspecialchars($loc['construction_site_name']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($loc['address'])): ?>
                            <div class="small text-muted mt-1"><i class="bi bi-pin-map"></i> <?= htmlspecialchars($loc['address']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($loc['responsible_name'])): ?>
                            <div class="small text-muted mt-1"><i class="bi bi-person"></i> <?= htmlspecialchars($loc['responsible_name']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Modal Criar/Editar Depósito -->
<div class="modal fade" id="locationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="locationForm" action="/admin/stock/store-location">
                <input type="hidden" name="id" id="locId" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="locationModalTitle">Novo Depósito/Estoque</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome do Estoque *</label>
                        <input type="text" class="form-control" name="name" id="locName" required placeholder="Ex: Galpão Central, Estoque Obra X, Almoxarifado">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Obra Vinculada (opcional)</label>
                        <select class="form-select" name="construction_site_id" id="locSiteId">
                            <option value="">Nenhuma (estoque independente)</option>
                            <?php
                            $sites = \App\Models\ConstructionSite::allActive();
                            foreach ($sites as $site): ?>
                                <option value="<?= $site['id'] ?>"><?= htmlspecialchars($site['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Vincule a uma obra se este estoque é específico dela.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Endereço/Localização</label>
                        <input type="text" class="form-control" name="address" id="locAddress" placeholder="Endereço do depósito">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Responsável</label>
                        <input type="text" class="form-control" name="responsible_name" id="locResponsible" placeholder="Nome do responsável">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="notes" id="locNotes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function clearLocationForm() {
    document.getElementById('locationModalTitle').textContent = 'Novo Depósito/Estoque';
    document.getElementById('locationForm').action = '/admin/stock/store-location';
    document.getElementById('locId').value = '';
    document.getElementById('locName').value = '';
    document.getElementById('locSiteId').value = '';
    document.getElementById('locAddress').value = '';
    document.getElementById('locResponsible').value = '';
    document.getElementById('locNotes').value = '';
}

function editLocation(loc) {
    document.getElementById('locationModalTitle').textContent = 'Editar Estoque';
    document.getElementById('locationForm').action = '/admin/stock/update-location';
    document.getElementById('locId').value = loc.id;
    document.getElementById('locName').value = loc.name || '';
    document.getElementById('locSiteId').value = loc.construction_site_id || '';
    document.getElementById('locAddress').value = loc.address || '';
    document.getElementById('locResponsible').value = loc.responsible_name || '';
    document.getElementById('locNotes').value = loc.notes || '';
    new bootstrap.Modal(document.getElementById('locationModal')).show();
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/Views/admin/layouts/app.php';
?>
