<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Pedidos | Brooks Construtora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #3a3b4e; font-family: 'Segoe UI', sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .pin-card { background: #fff; border-radius: 16px; padding: 2.5rem 2rem; box-shadow: 0 10px 40px rgba(0,0,0,0.3); max-width: 360px; width: 100%; text-align: center; }
        .pin-input { font-size: 2rem; text-align: center; letter-spacing: 12px; font-weight: 700; border: 2px solid #dee2e6; border-radius: 10px; padding: 0.75rem; }
        .pin-input:focus { border-color: #3a3b4e; box-shadow: 0 0 0 3px rgba(58,59,78,0.15); outline: none; }
        .pin-dots { display: flex; justify-content: center; gap: 10px; margin: 1.5rem 0; }
        .pin-dot { width: 14px; height: 14px; border-radius: 50%; background: #dee2e6; transition: all 0.2s; }
        .pin-dot.filled { background: #3a3b4e; transform: scale(1.1); }
        @media (max-width: 768px) {
            .pin-card { margin: 1rem; padding: 2rem 1.5rem; }
            .pin-input { font-size: 16px; letter-spacing: 10px; }
        }
    </style>
</head>
<body>
    <div class="pin-card">
        <img src="/assets/images/wp/2024/11/logo-brooks-1400x396.webp" alt="Brooks Construtora" style="max-width:180px; margin-bottom:1.5rem;">
        <h5 class="mb-1">Painel de Pedidos</h5>
        <p class="text-muted small mb-4">Digite o PIN de 4 dígitos para acessar</p>

        <?php if (!empty($flash)): ?>
        <div class="alert alert-danger small py-2"><?= htmlspecialchars($flash['message']) ?></div>
        <?php endif; ?>

        <form method="POST" action="/pedidos/auth" id="pinForm">
            <div class="pin-dots" id="pinDots">
                <div class="pin-dot"></div>
                <div class="pin-dot"></div>
                <div class="pin-dot"></div>
                <div class="pin-dot"></div>
            </div>
            <input type="password" name="pin" id="pinInput" class="pin-input w-100" maxlength="4" inputmode="numeric" pattern="\d{4}" autofocus autocomplete="off" required>
            <button type="submit" class="btn btn-primary w-100 mt-4 py-2" style="font-size:1rem;">
                <i class="bi bi-unlock"></i> Entrar
            </button>
        </form>
    </div>

    <script>
    const pinInput = document.getElementById('pinInput');
    const dots = document.querySelectorAll('.pin-dot');

    pinInput.addEventListener('input', function() {
        const len = this.value.length;
        dots.forEach((dot, i) => {
            dot.classList.toggle('filled', i < len);
        });
        // Auto-submit quando digita 4 dígitos
        if (len === 4) {
            setTimeout(() => document.getElementById('pinForm').submit(), 200);
        }
    });

    // Foco no input ao carregar
    pinInput.focus();
    </script>
</body>
</html>
