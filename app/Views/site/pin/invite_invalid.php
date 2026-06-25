<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convite Inválido | Brooks Construtora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background:#f4f6f9; display:flex; align-items:center; justify-content:center; min-height:100vh;">
    <div class="card border-0 shadow text-center p-5" style="max-width:400px;">
        <h4 class="text-danger mb-3">❌ Convite Inválido</h4>
        <p class="text-muted"><?= htmlspecialchars($message ?? 'Este link de convite não é válido ou expirou.') ?></p>
        <a href="/pin/login" class="btn btn-outline-dark mt-3">Já tenho conta → Login</a>
    </div>
</body>
</html>
