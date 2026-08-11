<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta | Brooks Construtora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .account-card { max-width: 440px; width: 100%; }
        .pin-input { font-size: 2rem; text-align: center; letter-spacing: 12px; font-weight: 700; }
    </style>
</head>
<body>
    <div class="account-card">
        <div class="card border-0 shadow-lg">
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <i class="bi bi-person-circle" style="font-size: 2.5rem; color: #3a3b4e;"></i>
                    <h5 class="fw-bold mt-2 mb-1">Minha Conta</h5>
                    <p class="text-muted small mb-0">
                        <?= ['buyer'=>'Comprador/Entrega','quoter'=>'Cotador','approver'=>'Aprovador','payment'=>'Financeiro','delivery'=>'Entrega','epi'=>'EPI','stock'=>'Estoque','all'=>'Completo'][$user['role']] ?? $user['role'] ?>
                        &middot; PIN: <code><?= $user['pin'] ?></code>
                    </p>
                </div>

                <?php if (!empty($flash)): ?>
                <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> small py-2">
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
                <?php endif; ?>

                <?php if (empty($user['phone'])): ?>
                <div class="alert alert-warning small py-2">
                    <i class="bi bi-exclamation-triangle"></i> <strong>Cadastre seu WhatsApp</strong> para continuar usando o sistema.
                </div>
                <?php endif; ?>

                <form method="POST" action="/pin/minha-conta/salvar">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nome completo *</label>
                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">E-mail</label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="seu@email.com (opcional)">
                        <small class="text-muted">Usado para recuperação de PIN.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">WhatsApp *</label>
                        <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" inputmode="numeric" placeholder="5511999999999" required>
                        <small class="text-muted">DDD + número. Usado para notificações.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Novo PIN</label>
                        <input type="text" class="form-control pin-input" name="new_pin" maxlength="4" pattern="\d{4}" inputmode="numeric" placeholder="····">
                        <small class="text-muted">Deixe vazio para manter o PIN atual.</small>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 mt-2">
                        <i class="bi bi-check-circle"></i> Salvar
                    </button>

                    <a href="/pedidos" class="btn btn-outline-secondary w-100 mt-2 <?= empty($user['phone']) ? 'd-none' : '' ?>">
                        <i class="bi bi-arrow-left"></i> Voltar
                    </a>
                </form>
            </div>
        </div>
    </div>
    <script>
    document.querySelector('.pin-input').addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '');
    });
    </script>
</body>
</html>
