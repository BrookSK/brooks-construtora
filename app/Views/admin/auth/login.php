<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Brooks Construtora Admin</title>
    <link rel="icon" href="/assets/images/wp/2023/01/cropped-favicon-1-32x32.png" sizes="32x32" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #3a3b4e;
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
        .login-card .brand img {
            max-width: 200px;
            margin-bottom: 0.75rem;
        }
        .login-card .brand p {
            color: #666;
            font-size: 0.9rem;
            margin: 0;
        }
        .btn-login {
            background-color: #3a3b4e;
            border-color: #3a3b4e;
            color: #fff;
            padding: 0.75rem;
            font-weight: 600;
        }
        .btn-login:hover {
            background-color: #446084;
            border-color: #446084;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">
            <img src="/assets/images/wp/2024/11/logo-brooks-1400x396.webp" alt="Brooks Construtora">
            <p>Painel Administrativo</p>
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
