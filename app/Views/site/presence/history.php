<?php $pageTitle = 'Histórico de Presença'; $currentPage = 'presence_history'; $user = $user ?? \App\Core\Auth::user(); ?>
<?php ob_start(); ?>

<style>
    .thumb { width: 54px; height: 54px; object-fit: contain; border-radius: 8px; border: 1px solid #dee2e6; cursor: pointer; background:#fff; }
</style>

<div>
    <!-- Filtros -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="/historico-presenca" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Obra</label>
                    <input type="text" class="form-control form-control-sm" name="site" list="siteList" value="<?= htmlspecialchars($filters['site'] ?? '') ?>">
                    <datalist id="siteList">
                        <?php foreach (($sites ?? []) as $s): ?><option value="<?= htmlspecialchars($s) ?>"><?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-2"><label class="form-label small fw-bold">Empresa</label><input type="text" class="form-control form-control-sm" name="company" value="<?= htmlspecialchars($filters['company'] ?? '') ?>"></div>
                <div class="col-md-2"><label class="form-label small fw-bold">Prestador</label><input type="text" class="form-control form-control-sm" name="provider" value="<?= htmlspecialchars($filters['provider'] ?? '') ?>"></div>
                <div class="col-md-2"><label class="form-label small fw-bold">De</label><input type="date" class="form-control form-control-sm" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>"></div>
                <div class="col-md-2"><label class="form-label small fw-bold">Até</label><input type="date" class="form-control form-control-sm" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>"></div>
                <div class="col-md-1 d-grid"><button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i></button></div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
            <span><i class="bi bi-calendar2-week"></i> Registros</span>
            <span class="badge bg-secondary"><?= count($records) ?></span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($records)): ?>
            <p class="text-muted text-center py-5 mb-0"><i class="bi bi-inbox"></i> Nenhum registro encontrado.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="table-light">
                        <tr><th>Prestador</th><th>Empresa</th><th>Obra</th><th>Data</th><th>Hora</th><th>Responsável</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($records as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['provider_name']) ?></td>
                            <td><?= htmlspecialchars($r['company'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($r['site']) ?></td>
                            <td><?= date('d/m/Y', strtotime($r['presence_date'])) ?></td>
                            <td><?= substr($r['presence_time'], 0, 5) ?></td>
                            <td><?= htmlspecialchars($r['created_by_name'] ?? '—') ?></td>
                            <td><span class="badge bg-<?= $r['status'] === 'registered' ? 'success' : 'secondary' ?>"><?= $r['status'] === 'registered' ? 'Registrado' : 'Cancelado' ?></span></td>
                            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-secondary" onclick='showDetail(<?= json_encode($r) ?>)'><i class="bi bi-eye"></i></button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Detalhes -->
<div class="modal fade" id="detailModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header py-2"><h6 class="modal-title"><i class="bi bi-clipboard-check"></i> Detalhes da Presença</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <dl class="row mb-0 small">
            <dt class="col-4">Prestador</dt><dd class="col-8" id="dtName"></dd>
            <dt class="col-4">Empresa</dt><dd class="col-8" id="dtCompany"></dd>
            <dt class="col-4">Obra</dt><dd class="col-8" id="dtSite"></dd>
            <dt class="col-4">Data</dt><dd class="col-8" id="dtDate"></dd>
            <dt class="col-4">Hora</dt><dd class="col-8" id="dtTime"></dd>
            <dt class="col-4">Responsável</dt><dd class="col-8" id="dtBy"></dd>
            <dt class="col-4">Registrado em</dt><dd class="col-8" id="dtCreated"></dd>
            <dt class="col-4">Observações</dt><dd class="col-8" id="dtNotes"></dd>
        </dl>
        <div class="text-center mt-2" id="dtSignatureWrap" style="display:none;">
            <label class="form-label small fw-bold d-block">Assinatura do prestador</label>
            <img id="dtSignature" style="max-width:100%; border:1px solid #dee2e6; border-radius:8px; background:#fff;">
        </div>
    </div>
</div></div></div>

<script>
function showDetail(r) {
    document.getElementById('dtName').textContent = r.provider_name || '—';
    document.getElementById('dtCompany').textContent = r.company || '—';
    document.getElementById('dtSite').textContent = r.site || '—';
    document.getElementById('dtDate').textContent = r.presence_date ? new Date(r.presence_date + 'T00:00:00').toLocaleDateString('pt-BR') : '—';
    document.getElementById('dtTime').textContent = (r.presence_time || '').substring(0,5);
    document.getElementById('dtBy').textContent = r.created_by_name || '—';
    document.getElementById('dtCreated').textContent = r.created_at ? new Date(r.created_at.replace(' ','T')).toLocaleString('pt-BR') : '—';
    document.getElementById('dtNotes').textContent = r.notes || '—';
    const wrap = document.getElementById('dtSignatureWrap');
    if (r.signature_path) { document.getElementById('dtSignature').src = r.signature_path; wrap.style.display = 'block'; }
    else { wrap.style.display = 'none'; }
    new bootstrap.Modal(document.getElementById('detailModal')).show();
}
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
