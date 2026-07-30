<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta | Brooks Construtora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; min-height: 100vh; }
        .page-header { background: #3a3b4e; color: #fff; padding: 1rem 0; }
        .account-card { border: none; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); max-width: 500px; margin: 0 auto; }
        @media (max-width: 768px) { input, select { font-size: 16px !important; } }
    </style>
</head>
<body>
    <div class="page-header text-center">
        <div class="container">
            <h4 class="mb-0">Minha Conta</h4>
        </div>
    </div>

    <div class="container py-4">
        <?php if (!empty($flash)): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" style="max-width:500px; margin:0 auto 1rem;">
            <?= htmlspecialchars($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card account-card">
            <div class="card-body p-4">
                <form method="POST" action="/pin/minha-conta/salvar">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nome *</label>
                        <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">E-mail</label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="seu@email.com">
                        <small class="text-muted">Usado para recuperação de PIN.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Telefone / WhatsApp</label>
                        <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="5511999999999" inputmode="numeric">
                        <small class="text-muted">Formato: DDD + número (ex: 5511999999999). Usado para notificações via WhatsApp.</small>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Alterar PIN</label>
                        <input type="text" class="form-control" name="new_pin" maxlength="4" pattern="\d{4}" inputmode="numeric" placeholder="Deixe vazio para manter o atual" style="text-align:center; letter-spacing:8px; font-size:1.3rem; max-width:180px;">
                        <small class="text-muted">4 dígitos numéricos. Só preencha se quiser trocar.</small>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="/pedidos" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Salvar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="text-center mt-3">
            <small class="text-muted">
                PIN atual: <code><?= $user['pin'] ?></code> &middot;
                Permissão: <strong><?= ['buyer'=>'Comprador','quoter'=>'Cotador','approver'=>'Aprovador','payment'=>'Financeiro','delivery'=>'Entrega','epi'=>'EPI','all'=>'Completo'][$user['role']] ?? $user['role'] ?></strong>
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
