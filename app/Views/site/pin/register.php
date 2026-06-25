<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro | Brooks Construtora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .register-card { max-width: 440px; width: 100%; }
        .pin-input { font-size: 2rem; text-align: center; letter-spacing: 12px; font-weight: 700; }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="card border-0 shadow-lg">
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <i class="bi bi-person-plus" style="font-size: 2.5rem; color: #28a745;"></i>
                    <h5 class="fw-bold mt-2 mb-1">Criar Conta</h5>
                    <p class="text-muted small">Acesso: <strong><?= htmlspecialchars($roleLabel) ?></strong></p>
                </div>

                <?php if (!empty($flash)): ?>
                <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> small py-2">
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="/pin/store">
                    <input type="hidden" name="invite_token" value="<?= htmlspecialchars($inviteToken) ?>">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nome completo *</label>
                        <input type="text" class="form-control" name="name" required placeholder="Seu nome completo">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">E-mail</label>
                        <input type="email" class="form-control" name="email" placeholder="seu@email.com (opcional)">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">PIN de 4 dígitos *</label>
                        <input type="text" class="form-control pin-input" name="pin" maxlength="4" pattern="\d{4}" inputmode="numeric" required placeholder="····">
                        <small class="text-muted">Escolha 4 números que só você saiba. Este será seu login.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Frase de recuperação</label>
                        <input type="text" class="form-control" name="recovery_phrase" placeholder="Ex: nome do seu pet (opcional)">
                        <small class="text-muted">Caso esqueça o PIN.</small>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg w-100 mt-2">
                        <i class="bi bi-check-circle"></i> Criar Minha Conta
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
