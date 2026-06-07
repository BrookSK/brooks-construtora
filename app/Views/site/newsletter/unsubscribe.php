<?php include ROOT_PATH . '/app/Views/site/layouts/header.php'; ?>

<div id="content" role="main" class="content-area">
<section class="section" style="padding: 60px 0;">
    <div class="container" style="max-width: 500px; margin: 0 auto; text-align: center;">

        <?php if ($success): ?>
            <div style="margin-bottom: 30px;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#2e7d32" stroke-width="2" style="margin-bottom:15px;"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
            </div>
            <h2 style="margin-bottom: 15px; color: #333;">Inscrição cancelada</h2>
            <p style="color: #666; font-size: 1rem;"><?= htmlspecialchars($message) ?></p>
            <a href="/" style="display:inline-block; margin-top:30px; padding:10px 25px; background:#3a3b4e; color:#fff; border-radius:50px; text-decoration:none; font-weight:600;">Voltar ao site</a>

        <?php elseif (!empty($email)): ?>
            <h2 style="margin-bottom: 15px; color: #333;">Cancelar inscrição</h2>
            <p style="color: #666; font-size: 1rem; margin-bottom: 25px;">Deseja realmente cancelar sua inscrição na newsletter da Brooks Construtora?</p>
            <p style="color: #999; font-size: 0.9rem; margin-bottom: 25px;">E-mail: <strong><?= htmlspecialchars($email) ?></strong></p>
            
            <form method="POST" action="/newsletter/unsubscribe?email=<?= urlencode($email) ?>">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                <button type="submit" style="padding:12px 30px; background:#e53935; color:#fff; border:none; border-radius:50px; font-weight:700; font-size:14px; cursor:pointer;">Sim, cancelar minha inscrição</button>
            </form>
            <p style="margin-top: 20px;"><a href="/" style="color: #666; text-decoration: underline;">Não, quero continuar recebendo</a></p>

        <?php else: ?>
            <h2 style="margin-bottom: 15px; color: #333;">Link inválido</h2>
            <p style="color: #666;">O link de cancelamento está incompleto. Verifique o e-mail recebido e tente novamente.</p>
            <a href="/" style="display:inline-block; margin-top:30px; padding:10px 25px; background:#3a3b4e; color:#fff; border-radius:50px; text-decoration:none; font-weight:600;">Voltar ao site</a>
        <?php endif; ?>

    </div>
</section>
</div>

<?php include ROOT_PATH . '/app/Views/site/layouts/footer.php'; ?>
