<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Rápido | Brooks Construtora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .pin-card { max-width: 380px; width: 100%; }
        .pin-input { font-size: 2.5rem; text-align: center; letter-spacing: 15px; font-weight: 700; padding: 15px; }
    </style>
</head>
<body>
    <div class="pin-card">
        <div class="card border-0 shadow-lg">
            <div class="card-body p-4 text-center">
                <div class="mb-3">
                    <i class="bi bi-shield-lock" style="font-size: 3rem; color: #3a3b4e;"></i>
                </div>
                <h5 class="fw-bold mb-1">Acesso Rápido</h5>
                <p class="text-muted small mb-4">Digite seu PIN de 4 dígitos</p>

                <?php if (!empty($flash)): ?>
                <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> small py-2">
                    <?= htmlspecialchars($flash['message']) ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="/pin/authenticate" id="pinForm">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
                    <input type="text" class="form-control pin-input" name="pin" maxlength="4" pattern="\d{4}" inputmode="numeric" autofocus autocomplete="off" placeholder="····">
                    <button type="submit" class="btn btn-dark btn-lg w-100 mt-3"><i class="bi bi-unlock"></i> Entrar</button>
                </form>

                <p class="text-muted small mt-3 mb-0">Sessão mantida por 30 dias.</p>
            </div>
        </div>
    </div>
    <script>
    // Auto-submit ao digitar 4 dígitos
    document.querySelector('.pin-input').addEventListener('input', function() {
        if (this.value.length === 4) {
            setTimeout(() => document.getElementById('pinForm').submit(), 200);
        }
    });
    </script>
</body>
</html>
