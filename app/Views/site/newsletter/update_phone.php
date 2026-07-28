<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizar WhatsApp - Brooks Construtora</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); max-width: 420px; width: 100%; padding: 2.5rem; text-align: center; }
        .card img { max-width: 180px; margin-bottom: 1.5rem; }
        .card h1 { font-size: 1.4rem; color: #3a3b4e; margin-bottom: 0.5rem; }
        .card p { color: #666; font-size: 0.9rem; margin-bottom: 1.5rem; line-height: 1.5; }
        .card .name { font-weight: 600; color: #3a3b4e; }
        .form-group { margin-bottom: 1rem; text-align: left; }
        .form-group label { display: block; font-size: 0.8rem; color: #666; margin-bottom: 0.3rem; }
        .form-group input { width: 100%; padding: 12px 16px; border: 1px solid #dee2e6; border-radius: 8px; font-size: 1rem; outline: none; transition: border 0.2s; }
        .form-group input:focus { border-color: #3a3b4e; }
        .btn { width: 100%; padding: 14px; background: #25D366; color: #fff; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background: #1da851; }
        .success { background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .error { background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .hint { font-size: 0.75rem; color: #999; margin-top: 0.5rem; }
    </style>
</head>
<body>
    <div class="card">
        <img src="/assets/images/wp/2024/11/logo-brooks-1400x396.webp" alt="Brooks Construtora">
        
        <?php if (!empty($success)): ?>
            <div class="success">
                <strong>WhatsApp cadastrado com sucesso!</strong><br>
                Você receberá nossas novidades também pelo WhatsApp.
            </div>
        <?php elseif (!empty($errorMsg)): ?>
            <div class="error"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <?php if (!empty($subscriber)): ?>
            <h1>Cadastre seu WhatsApp</h1>
            <p>Olá, <span class="name"><?= htmlspecialchars($subscriber['name'] ?: 'assinante') ?></span>! Cadastre seu WhatsApp para receber nossas revistas e novidades diretamente no celular.</p>

            <?php if (empty($success)): ?>
            <form method="POST" action="<?= $_SERVER['REQUEST_URI'] ?>">
                <?php if (!empty($isGlobal)): ?>
                    <input type="hidden" name="email" value="<?= htmlspecialchars($subscriber['email']) ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label>Seu WhatsApp</label>
                    <input type="tel" name="phone" placeholder="(11) 99999-9999" required value="<?= htmlspecialchars($subscriber['phone'] ?? '') ?>" autofocus>
                    <p class="hint">Formato: DDD + número (ex: 11999999999)</p>
                </div>
                <button type="submit" class="btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:6px;"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Salvar WhatsApp
                </button>
            </form>
            <?php endif; ?>

        <?php elseif (!empty($isGlobal)): ?>
            <h1>Cadastre seu WhatsApp</h1>
            <p>Informe seu e-mail de assinante e cadastre o WhatsApp para receber nossas revistas no celular.</p>

            <form method="POST" action="/newsletter/atualizar">
                <div class="form-group">
                    <label>Seu e-mail (cadastrado na newsletter)</label>
                    <input type="email" name="email" placeholder="seu@email.com" required autofocus>
                </div>
                <div class="form-group">
                    <label>Seu WhatsApp</label>
                    <input type="tel" name="phone" placeholder="(11) 99999-9999" required>
                    <p class="hint">Formato: DDD + número (ex: 11999999999)</p>
                </div>
                <button type="submit" class="btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle;margin-right:6px;"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Salvar WhatsApp
                </button>
            </form>

        <?php else: ?>
            <h1>Link inválido</h1>
            <p>Este link não é válido ou já expirou. Se você é assinante da nossa revista, entre em contato conosco.</p>
        <?php endif; ?>
    </div>
</body>
</html>
