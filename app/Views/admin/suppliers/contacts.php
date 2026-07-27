<?php
$currentPage = 'suppliers';
ob_start();
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <a href="/admin/suppliers" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
        <span class="ms-2 text-muted">Fornecedor: <strong><?= htmlspecialchars($supplier['name']) ?></strong></span>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary btn-sm" onclick="showAddContact()">
            <i class="bi bi-plus-lg"></i> Novo Vendedor
        </button>
        <button type="button" class="btn btn-outline-success btn-sm" onclick="importFromQuotes()">
            <i class="bi bi-download"></i> Importar de Cotações
        </button>
    </div>
</div>

<?php if (empty($contacts)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-people text-muted" style="font-size:3rem;"></i>
            <p class="text-muted mt-3 mb-2">Nenhum vendedor cadastrado para este fornecedor.</p>
            <p class="text-muted small mb-0">Clique em "Importar de Cotações" para trazer vendedores de pedidos anteriores.</p>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($contacts as $contact): ?>
            <div class="col-12 col-md-6 col-lg-4" id="contact-card-<?= $contact['id'] ?>">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1"><?= htmlspecialchars($contact['name']) ?></h6>
                                <span class="badge bg-secondary mb-2"><?= htmlspecialchars($contact['role'] ?? 'vendedor') ?></span>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="editContact(<?= htmlspecialchars(json_encode($contact)) ?>)"><i class="bi bi-pencil"></i> Editar</a></li>
                                    <li><a class="dropdown-item text-danger" href="#" onclick="deleteContact(<?= $contact['id'] ?>)"><i class="bi bi-trash"></i> Excluir</a></li>
                                </ul>
                            </div>
                        </div>
                        <?php if (!empty($contact['phone'])): ?>
                            <div class="small mb-1"><i class="bi bi-phone text-muted"></i> <?= htmlspecialchars($contact['phone']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($contact['email'])): ?>
                            <div class="small mb-1"><i class="bi bi-envelope text-muted"></i> <?= htmlspecialchars($contact['email']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($contact['notes'])): ?>
                            <div class="small text-muted mt-2"><?= htmlspecialchars($contact['notes']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Modal Adicionar/Editar Vendedor -->
<div class="modal fade" id="contactModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="contactModalTitle">Novo Vendedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="contactId" value="">
                <div class="mb-3">
                    <label class="form-label">Nome *</label>
                    <input type="text" class="form-control" id="contactName" placeholder="Nome do vendedor" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Telefone</label>
                    <input type="text" class="form-control" id="contactPhone" placeholder="5511999999999">
                </div>
                <div class="mb-3">
                    <label class="form-label">E-mail</label>
                    <input type="email" class="form-control" id="contactEmail" placeholder="vendedor@empresa.com">
                </div>
                <div class="mb-3">
                    <label class="form-label">Cargo/Função</label>
                    <input type="text" class="form-control" id="contactRole" placeholder="Vendedor" value="vendedor">
                </div>
                <div class="mb-3">
                    <label class="form-label">Observações</label>
                    <textarea class="form-control" id="contactNotes" rows="2" placeholder="Observações..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveContact()">Salvar</button>
            </div>
        </div>
    </div>
</div>

<script>
const supplierId = <?= $supplier['id'] ?>;

function showAddContact() {
    document.getElementById('contactModalTitle').textContent = 'Novo Vendedor';
    document.getElementById('contactId').value = '';
    document.getElementById('contactName').value = '';
    document.getElementById('contactPhone').value = '';
    document.getElementById('contactEmail').value = '';
    document.getElementById('contactRole').value = 'vendedor';
    document.getElementById('contactNotes').value = '';
    new bootstrap.Modal(document.getElementById('contactModal')).show();
}

function editContact(contact) {
    document.getElementById('contactModalTitle').textContent = 'Editar Vendedor';
    document.getElementById('contactId').value = contact.id;
    document.getElementById('contactName').value = contact.name || '';
    document.getElementById('contactPhone').value = contact.phone || '';
    document.getElementById('contactEmail').value = contact.email || '';
    document.getElementById('contactRole').value = contact.role || 'vendedor';
    document.getElementById('contactNotes').value = contact.notes || '';
    new bootstrap.Modal(document.getElementById('contactModal')).show();
}

async function saveContact() {
    const id = document.getElementById('contactId').value;
    const name = document.getElementById('contactName').value.trim();
    if (!name) { alert('Nome é obrigatório.'); return; }

    const data = new URLSearchParams({
        supplier_id: supplierId,
        name: name,
        phone: document.getElementById('contactPhone').value.trim(),
        email: document.getElementById('contactEmail').value.trim(),
        role: document.getElementById('contactRole').value.trim(),
        notes: document.getElementById('contactNotes').value.trim(),
    });

    const url = id ? '/admin/suppliers/update-contact' : '/admin/suppliers/store-contact';
    if (id) data.append('id', id);

    const resp = await fetch(url, { method: 'POST', body: data });
    const result = await resp.json();

    if (result.success) {
        bootstrap.Modal.getInstance(document.getElementById('contactModal')).hide();
        location.reload();
    } else {
        alert(result.error || 'Erro ao salvar.');
    }
}

async function deleteContact(id) {
    if (!confirm('Excluir este vendedor?')) return;

    const resp = await fetch('/admin/suppliers/delete-contact', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ id: id })
    });
    const result = await resp.json();

    if (result.success) {
        document.getElementById('contact-card-' + id)?.remove();
    } else {
        alert(result.error || 'Erro ao excluir.');
    }
}

async function importFromQuotes() {
    if (!confirm('Importar vendedores de cotações anteriores para este e todos os fornecedores?')) return;

    const resp = await fetch('/admin/suppliers/import-contacts', { method: 'POST' });
    const result = await resp.json();

    if (result.success) {
        alert(`${result.imported} vendedor(es) importado(s) com sucesso!`);
        if (result.imported > 0) location.reload();
    } else {
        alert(result.error || 'Erro ao importar.');
    }
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/app/Views/admin/layouts/app.php';
?>
