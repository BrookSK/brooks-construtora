<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastre seu WhatsApp - Brooks Construtora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #3a3b4e;
            --accent: #25D366;
            --accent-hover: #1da851;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-500: #6c757d;
            --radius: 12px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', -apple-system, sans-serif;
            background: linear-gradient(135deg, #1a1b2e 0%, #2d2e42 50%, #3a3b4e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .container {
            max-width: 440px;
            width: 100%;
        }
        .card {
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 3rem 2.5rem;
            text-align: center;
            animation: fadeUp 0.5s ease;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .logo { max-width: 160px; margin-bottom: 2rem; filter: brightness(0) saturate(100%); }
        .card h1 { font-size: 1.5rem; color: var(--primary); margin-bottom: 0.5rem; font-weight: 700; }
        .card .subtitle { color: var(--gray-500); font-size: 0.9rem; margin-bottom: 2rem; line-height: 1.6; }
        .form-group { margin-bottom: 1.25rem; text-align: left; }
        .form-group label { display: block; font-size: 0.8rem; color: var(--gray-500); margin-bottom: 0.4rem; font-weight: 500; }
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-group input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(37, 211, 102, 0.1);
        }
        .hint { font-size: 0.72rem; color: #adb5bd; margin-top: 0.4rem; }
        .btn {
            width: 100%;
            padding: 15px;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: var(--radius);
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 0.5rem;
        }
        .btn:hover { background: var(--accent-hover); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37,211,102,0.3); }
        .btn:active { transform: translateY(0); }
        .btn i { font-size: 1.2rem; }
        .success-container { text-align: center; }
        .success-icon { width: 80px; height: 80px; background: #d4edda; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; }
        .success-icon i { font-size: 2.5rem; color: var(--accent); }
        .success-title { font-size: 1.4rem; color: var(--primary); font-weight: 700; margin-bottom: 0.75rem; }
        .success-text { color: var(--gray-500); font-size: 0.9rem; line-height: 1.6; margin-bottom: 1.5rem; }
        .success-badge { display: inline-flex; align-items: center; gap: 6px; background: #d4edda; color: #155724; padding: 8px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 500; }
        .error-msg { background: #fff3f3; border: 1px solid #ffcdd2; color: #c62828; padding: 12px 16px; border-radius: var(--radius); margin-bottom: 1.25rem; font-size: 0.85rem; text-align: left; }
        .footer-text { text-align: center; margin-top: 1.5rem; font-size: 0.75rem; color: rgba(255,255,255,0.4); }
        .footer-text a { color: rgba(255,255,255,0.6); text-decoration: none; }
        @media (max-width: 480px) {
            .card { padding: 2rem 1.5rem; }
            body { padding: 1rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <img src="/assets/images/wp/2024/11/logo-brooks-1-800x227.webp" alt="Brooks Construtora" class="logo">

            <?php if (!empty($success)): ?>
                <!-- Sucesso -->
                <div class="success-container">
                    <div class="success-icon"><i class="bi bi-check-lg"></i></div>
                    <h2 class="success-title">Tudo certo!</h2>
                    <p class="success-text">
                        Seu WhatsApp foi cadastrado com sucesso.<br>
                        Agora você receberá nossas revistas e novidades diretamente no celular.
                    </p>
                    <div class="success-badge">
                        <i class="bi bi-whatsapp"></i>
                        <?= htmlspecialchars($subscriber['phone'] ?? '') ?>
                    </div>
                </div>

            <?php elseif (!empty($subscriber) && empty($isGlobal)): ?>
                <!-- Com token (identificado) -->
                <h1>Cadastre seu WhatsApp</h1>
                <p class="subtitle">Olá, <strong><?= htmlspecialchars($subscriber['name'] ?: 'assinante') ?></strong>! Receba nossas revistas e novidades direto no celular.</p>

                <?php if (!empty($errorMsg)): ?>
                    <div class="error-msg"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($errorMsg) ?></div>
                <?php endif; ?>

                <form method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>">
                    <div class="form-group">
                        <label>Seu WhatsApp</label>
                        <input type="tel" name="phone" placeholder="(11) 99999-9999" required value="<?= htmlspecialchars($subscriber['phone'] ?? '') ?>" autofocus>
                        <p class="hint">DDD + número, sem espaços</p>
                    </div>
                    <button type="submit" class="btn">
                        <i class="bi bi-whatsapp"></i> Salvar WhatsApp
                    </button>
                </form>

            <?php elseif (!empty($isGlobal)): ?>
                <!-- Link global (pede email) -->
                <h1>Cadastre seu WhatsApp</h1>
                <p class="subtitle">Informe seu e-mail de assinante e cadastre o WhatsApp para receber nossas revistas no celular.</p>

                <?php if (!empty($errorMsg)): ?>
                    <div class="error-msg"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($errorMsg) ?></div>
                <?php endif; ?>

                <form method="POST" action="/newsletter/atualizar">
                    <div class="form-group">
                        <label>Seu e-mail</label>
                        <input type="email" name="email" placeholder="seu@email.com" required autofocus>
                    </div>
                    <div class="form-group">
                        <label>Seu WhatsApp</label>
                        <input type="tel" name="phone" placeholder="(11) 99999-9999" required>
                        <p class="hint">DDD + número, sem espaços</p>
                    </div>
                    <button type="submit" class="btn">
                        <i class="bi bi-whatsapp"></i> Salvar WhatsApp
                    </button>
                </form>

            <?php else: ?>
                <!-- Token inválido -->
                <div style="padding:1rem 0;">
                    <i class="bi bi-link-45deg" style="font-size:3rem; color:#dee2e6;"></i>
                    <h1 style="margin-top:1rem;">Link inválido</h1>
                    <p class="subtitle">Este link não é válido ou já expirou.</p>
                    <a href="/newsletter/atualizar" class="btn" style="text-decoration:none; background:var(--primary);">
                        <i class="bi bi-arrow-right"></i> Usar link geral
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <p class="footer-text">
            <a href="/">Brooks Construtora</a> · Construção de alto padrão
        </p>
    </div>
</body>
</html>
