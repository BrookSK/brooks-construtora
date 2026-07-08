<?php $pageTitle = 'Lista de Presença'; $currentPage = 'presence'; $user = $user ?? \App\Core\Auth::user(); ?>
<?php ob_start(); ?>

<style>
    #presenceSignature { border: 1px solid #ced4da; border-radius: 8px; width: 100%; height: 180px; touch-action: none; background: #fff; }
</style>

<div style="max-width: 800px;">
    <form method="POST" action="/lista-de-presenca/salvar" id="presenceForm">
        <!-- Prestador -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-bold"><i class="bi bi-person-vcard text-primary"></i> Prestador</div>
            <div class="card-body row g-2">
                <div class="col-md-6 position-relative">
                    <label class="form-label small fw-bold">Nome do prestador *</label>
                    <input type="text" class="form-control" name="provider_name" id="providerName" autocomplete="off" placeholder="Digite para buscar..." required>
                    <input type="hidden" name="provider_id" id="providerId">
                    <div id="providerSuggestions" class="list-group position-absolute w-100 shadow-sm" style="z-index:1050; display:none; max-height:240px; overflow-y:auto;"></div>
                </div>
                <div class="col-md-6"><label class="form-label small fw-bold">Empresa</label><input type="text" class="form-control" name="company" id="providerCompany"></div>
            </div>
        </div>

        <!-- Dados da presença -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-bold"><i class="bi bi-clipboard-check text-primary"></i> Registro</div>
            <div class="card-body row g-2">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Obra *</label>
                    <input type="text" class="form-control" name="site" list="siteList" required placeholder="Nome da obra">
                    <datalist id="siteList">
                        <?php foreach (($sites ?? []) as $s): ?><option value="<?= htmlspecialchars($s) ?>"><?php endforeach; ?>
                    </datalist>
                </div>
                <div class="col-md-3"><label class="form-label small fw-bold">Data *</label><input type="date" class="form-control" name="presence_date" value="<?= date('Y-m-d') ?>" required></div>
                <div class="col-md-3"><label class="form-label small fw-bold">Hora *</label><input type="time" class="form-control" name="presence_time" value="<?= date('H:i') ?>" required></div>
                <div class="col-12"><label class="form-label small fw-bold">Observações</label><textarea class="form-control" name="notes" rows="2" placeholder="Opcional"></textarea></div>
            </div>
        </div>

        <!-- Assinatura -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white fw-bold"><i class="bi bi-pencil-square text-primary"></i> Assinatura do Prestador</div>
            <div class="card-body">
                <canvas id="presenceSignature"></canvas>
                <div class="mt-1"><button type="button" class="btn btn-sm btn-outline-secondary" onclick="presencePad.clear()"><i class="bi bi-eraser"></i> Limpar</button></div>
                <input type="hidden" name="signature_data" id="input_presence_signature">
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-save"></i> Registrar Presença</button>
    </form>
</div>

<!-- Modal Cadastro Rápido de Prestador -->
<div class="modal fade" id="providerModal" tabindex="-1" data-bs-backdrop="static"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header py-2"><h6 class="modal-title"><i class="bi bi-person-plus"></i> Cadastro rápido de prestador</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <p class="small text-muted">Prestador não encontrado. Cadastre os dados abaixo para continuar.</p>
        <div class="mb-2"><label class="form-label small fw-bold">Nome *</label><input type="text" class="form-control" id="pmName" required></div>
        <div class="row g-2">
            <div class="col-md-6 mb-2"><label class="form-label small fw-bold">CPF / Documento</label><input type="text" class="form-control" id="pmDocument"></div>
            <div class="col-md-6 mb-2"><label class="form-label small fw-bold">Empresa</label><input type="text" class="form-control" id="pmCompany"></div>
            <div class="col-md-6 mb-2"><label class="form-label small fw-bold">Função</label><input type="text" class="form-control" id="pmRole"></div>
            <div class="col-md-6 mb-2"><label class="form-label small fw-bold">Telefone</label><input type="text" class="form-control" id="pmPhone"></div>
        </div>
        <div class="mb-2"><label class="form-label small fw-bold">Observações</label><textarea class="form-control" id="pmNotes" rows="2"></textarea></div>
    </div>
    <div class="modal-footer py-2"><button type="button" class="btn btn-primary w-100" id="pmSaveBtn" onclick="saveProvider()"><i class="bi bi-check-lg"></i> Salvar e continuar</button></div>
</div></div></div>

<script src="/assets/js/signature-pad.js"></script>
<script>
const presencePad = new SignaturePad(document.getElementById('presenceSignature'), { hiddenInput: document.getElementById('input_presence_signature') });
let providerModal = null;

// ---- Autocomplete de prestadores ----
(function() {
    const input = document.getElementById('providerName');
    const box = document.getElementById('providerSuggestions');
    let timer = null, lastTerm = '';

    function hide() { box.style.display = 'none'; box.innerHTML = ''; }

    function selectProvider(p) {
        document.getElementById('providerId').value = p.id;
        input.value = p.name;
        if (p.company) document.getElementById('providerCompany').value = p.company;
        hide();
    }

    function showQuickAdd() {
        // Preenche o nome digitado no modal e abre
        document.getElementById('pmName').value = input.value.trim();
        ['pmDocument','pmCompany','pmRole','pmPhone','pmNotes'].forEach(id => document.getElementById(id).value = '');
        providerModal = new bootstrap.Modal(document.getElementById('providerModal'));
        providerModal.show();
    }

    input.addEventListener('input', function() {
        document.getElementById('providerId').value = ''; // limpa vínculo ao editar
        const term = this.value.trim();
        lastTerm = term;
        clearTimeout(timer);
        if (term.length < 1) { hide(); return; }
        timer = setTimeout(() => {
            fetch('/lista-de-presenca/buscar-prestador?q=' + encodeURIComponent(term))
                .then(r => r.json())
                .then(d => {
                    const providers = d.providers || [];
                    box.innerHTML = '';
                    providers.forEach(p => {
                        const a = document.createElement('button');
                        a.type = 'button';
                        a.className = 'list-group-item list-group-item-action py-2';
                        a.innerHTML = `<strong>${p.name}</strong> <span class="text-muted small">${p.company ? '· ' + p.company : ''}${p.document ? ' · ' + p.document : ''}</span>`;
                        a.addEventListener('click', () => selectProvider(p));
                        box.appendChild(a);
                    });
                    // Opção de cadastro rápido sempre no fim
                    const add = document.createElement('button');
                    add.type = 'button';
                    add.className = 'list-group-item list-group-item-action py-2 text-primary';
                    add.innerHTML = `<i class="bi bi-person-plus"></i> Cadastrar "<strong>${lastTerm}</strong>" como novo prestador`;
                    add.addEventListener('click', showQuickAdd);
                    box.appendChild(add);
                    box.style.display = 'block';
                })
                .catch(hide);
        }, 250);
    });

    document.addEventListener('click', function(e) {
        if (e.target !== input && !box.contains(e.target)) hide();
    });

    // Expor para o saveProvider
    window._presenceSelectProvider = selectProvider;
})();

function saveProvider() {
    const name = document.getElementById('pmName').value.trim();
    if (!name) { alert('Informe o nome do prestador.'); return; }
    const btn = document.getElementById('pmSaveBtn');
    btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Salvando...';
    const fd = new URLSearchParams({
        name,
        document: document.getElementById('pmDocument').value,
        company: document.getElementById('pmCompany').value,
        role: document.getElementById('pmRole').value,
        phone: document.getElementById('pmPhone').value,
        notes: document.getElementById('pmNotes').value,
    });
    fetch('/lista-de-presenca/salvar-prestador', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                window._presenceSelectProvider(d.provider);
                providerModal.hide();
            } else {
                alert(d.error || 'Erro ao cadastrar.');
            }
        })
        .catch(() => alert('Sem conexão.'))
        .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg"></i> Salvar e continuar'; });
}

// ---- Submit ----
document.getElementById('presenceForm').addEventListener('submit', function(e) {
    if (presencePad.isEmpty()) { e.preventDefault(); alert('A assinatura do prestador é obrigatória.'); return; }
    presencePad.sync();
});
</script>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
