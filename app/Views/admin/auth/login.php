<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Brooks Construtora Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #1a472a;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-card {
            background: #fff;
            border-radius: 12px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .login-card .brand {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-card .brand h2 {
            color: #1a472a;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        .login-card .brand p {
            color: #666;
            font-size: 0.9rem;
        }
        .btn-login {
            background-color: #1a472a;
            border-color: #1a472a;
            color: #fff;
            padding: 0.75rem;
            font-weight: 600;
        }
        .btn-login:hover {
            background-color: #2d6b40;
            border-color: #2d6b40;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">
            <h2>BROOKS</h2>
            <p>Construtora - Painel Administrativo</p>
        </div>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="/admin/login/authenticate">
            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" class="form-control" id="email" name="email" required autofocus>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Senha</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-login w-100">Entrar</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
