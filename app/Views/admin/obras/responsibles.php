<?php $pageTitle = 'Responsáveis por Obra'; $currentPage = 'obras'; ?>
<?php ob_start(); ?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h5 class="mb-0"><i class="bi bi-person-check"></i> Responsáveis por Obra</h5>
    <div class="d-flex gap-2">
        <a href="/admin/obras" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Obras</a>
        <a href="/admin/obras/responsaveis/export" id="btnExportResp" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-spreadsheet"></i> Exportar (filtro atual)
        </a>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label small mb-1" for="fUser">Usuário</label>
                <select id="fUser" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($users as $uid => $uname): ?>
                    <option value="<?= (int) $uid ?>"><?= htmlspecialchars($uname) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1" for="fPhase">Fase</label>
                <select id="fPhase" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    <?php foreach ($phases as $pk => $pl): ?>
                    <option value="<?= $pk ?>"><?= htmlspecialchars($pl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1" for="fStatus">Status da obra</label>
                <select id="fStatus" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($statusLabels as $sk => $sl): ?>
                    <option value="<?= $sk ?>" <?= $sk === 'active' ? 'selected' : '' ?>><?= htmlspecialchars($sl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label small mb-1" for="fSearch">Buscar obra</label>
                <input type="text" id="fSearch" class="form-control form-control-sm" placeholder="Código ou nome da obra">
            </div>
            <div class="col-12 col-md-2">
                <div class="form-check mb-1">
                    <input class="form-check-input" type="checkbox" id="fMissing">
                    <label class="form-check-label small" for="fMissing">Só obras com fase sem responsável</label>
                </div>
            </div>
        </div>
        <small class="text-muted d-block mt-2">
            Ex.: escolha um usuário e a fase "Aprovação" para ver todas as obras onde ele é aprovador.
            <span id="respCount" class="fw-bold"></span>
        </small>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm table-bordered table-hover mb-0 align-top" id="respTable">
            <thead class="table-light">
                <tr>
                    <th style="min-width:220px;">Obra</th>
                    <?php foreach ($phases as $pk => $pl): ?>
                    <th class="phase-col" data-phase="<?= $pk ?>" style="min-width:150px;"><?= htmlspecialchars($pl) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sites as $s): ?>
                <?php
                $siteMatrix = $matrix[$s['id']] ?? [];
                // Mapa fase → ids (para filtro no JS)
                $phaseUsers = [];
                foreach ($siteMatrix as $pk => $list) {
                    $phaseUsers[$pk] = array_map(fn($u) => (int) $u['user_id'], $list);
                }
                ?>
                <tr class="resp-row"
                    data-status="<?= htmlspecialchars($s['status']) ?>"
                    data-search="<?= htmlspecialchars(mb_strtolower(($s['code'] ?? '') . ' ' . $s['name'])) ?>"
                    data-phases='<?= htmlspecialchars(json_encode($phaseUsers), ENT_QUOTES) ?>'>
                    <td>
                        <a href="/admin/obras/edit/<?= (int) $s['id'] ?>" class="fw-bold text-decoration-none">
                            <?php if (!empty($s['code'])): ?><span class="badge bg-dark me-1"><?= htmlspecialchars($s['code']) ?></span><?php endif; ?>
                            <?= htmlspecialchars($s['name']) ?>
                        </a>
                        <br><small class="text-muted"><?= htmlspecialchars($statusLabels[$s['status']] ?? $s['status']) ?></small>
                    </td>
                    <?php foreach ($phases as $pk => $pl): ?>
                    <td class="phase-col" data-phase="<?= $pk ?>">
                        <?php if (empty($siteMatrix[$pk])): ?>
                        <small class="text-danger">—</small>
                        <?php else: foreach ($siteMatrix[$pk] as $u): ?>
                        <div class="small resp-user" data-user-id="<?= (int) $u['user_id'] ?>">
                            <?= htmlspecialchars($u['user_name']) ?>
                            <?php if (!(int) $u['user_active']): ?><span class="badge bg-secondary">inativo</span><?php endif; ?>
                        </div>
                        <?php endforeach; endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .resp-user.hl { background:#fff3cd; font-weight:600; border-radius:3px; padding:0 3px; }
</style>
<script>
(function () {
    const fUser = document.getElementById('fUser');
    const fPhase = document.getElementById('fPhase');
    const fStatus = document.getElementById('fStatus');
    const fSearch = document.getElementById('fSearch');
    const fMissing = document.getElementById('fMissing');
    const rows = Array.from(document.querySelectorAll('.resp-row'));
    const btnExport = document.getElementById('btnExportResp');
    const countEl = document.getElementById('respCount');

    function apply() {
        const uid = parseInt(fUser.value || '0', 10);
        const phase = fPhase.value;
        const status = fStatus.value;
        const q = fSearch.value.trim().toLowerCase();
        let visible = 0;

        // Mostra só a coluna da fase escolhida
        document.querySelectorAll('.phase-col').forEach(el => {
            el.style.display = (!phase || el.dataset.phase === phase) ? '' : 'none';
        });

        rows.forEach(tr => {
            const map = JSON.parse(tr.dataset.phases || '{}');
            const phasesToCheck = phase ? [phase] : Object.keys(map);
            let ok = (!status || tr.dataset.status === status) && (!q || tr.dataset.search.includes(q));
            if (ok && uid) ok = phasesToCheck.some(p => (map[p] || []).includes(uid));
            if (ok && fMissing.checked) {
                const all = phase ? [phase] : <?= json_encode(array_keys($phases)) ?>;
                ok = all.some(p => !(map[p] || []).length);
            }
            tr.style.display = ok ? '' : 'none';
            if (ok) visible++;
            tr.querySelectorAll('.resp-user').forEach(el => {
                el.classList.toggle('hl', !!uid && parseInt(el.dataset.userId, 10) === uid);
            });
        });

        countEl.textContent = visible + ' obra(s) exibida(s).';
        const params = new URLSearchParams();
        if (uid) params.set('user_id', uid);
        if (phase) params.set('phase', phase);
        if (status) params.set('status', status);
        btnExport.href = '/admin/obras/responsaveis/export' + (params.toString() ? '?' + params : '');
    }

    [fUser, fPhase, fStatus, fMissing].forEach(el => el.addEventListener('change', apply));
    fSearch.addEventListener('input', apply);
    apply();
})();
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
