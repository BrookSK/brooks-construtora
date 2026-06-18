<?php $pageTitle = $supplier ? 'Editar Fornecedor' : 'Novo Fornecedor'; $currentPage = 'suppliers'; ?>
<?php ob_start(); ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <form method="POST" action="<?= $supplier ? '/admin/suppliers/update' : '/admin/suppliers/store' ?>">
            <?php if ($supplier): ?>
            <input type="hidden" name="id" value="<?= $supplier['id'] ?>">
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Nome do Fornecedor *</label>
                            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($supplier['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">CNPJ</label>
                            <input type="text" class="form-control" name="cnpj" value="<?= htmlspecialchars($supplier['cnpj'] ?? '') ?>" placeholder="00.000.000/0000-00">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($supplier['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telefone</label>
                            <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($supplier['phone'] ?? '') ?>" placeholder="(11) 99999-9999">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pessoa de Contato</label>
                            <input type="text" class="form-control" name="contact_person" value="<?= htmlspecialchars($supplier['contact_person'] ?? '') ?>">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Endereço</label>
                            <textarea class="form-control" name="address" rows="2"><?= htmlspecialchars($supplier['address'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Observações</label>
                            <textarea class="form-control" name="notes" rows="2"><?= htmlspecialchars($supplier['notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="/admin/suppliers" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Voltar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> <?= $supplier ? 'Atualizar' : 'Cadastrar' ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php require ROOT_PATH . '/app/Views/admin/layouts/app.php'; ?>
