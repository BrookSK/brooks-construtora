<?php include ROOT_PATH . '/app/Views/site/layouts/new-header.php'; ?>

<section class="section" style="padding: var(--space-5xl) 0; min-height: 60vh; display: flex; align-items: center;">
    <div class="container" style="max-width: 500px; margin: 0 auto; text-align: center;">

        <?php if ($success): ?>
            <div style="width:80px;height:80px;background:#d4edda;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 2rem;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#2e7d32" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9 12l2 2 4-4"/></svg>
            </div>
            <h2 class="headline-subsection" style="margin-bottom: 1rem;">Inscrição cancelada</h2>
            <p class="subtitle subtitle--centered"><?= htmlspecialchars($message) ?></p>
            <a href="/" class="btn btn--primary" style="margin-top:2rem;">Voltar ao site</a>

        <?php elseif (!empty($email)): ?>
            <div style="width:80px;height:80px;background:#fff3e0;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 2rem;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#e65100" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            </div>
            <h2 class="headline-subsection" style="margin-bottom: 1rem;">Cancelar inscrição</h2>
            <p class="subtitle subtitle--centered" style="margin-bottom: 1.5rem;">Deseja realmente cancelar sua inscrição na newsletter da Brooks Construtora?</p>
            <p style="color: var(--brooks-gray-400); font-size: 0.9rem; margin-bottom: 2rem;">E-mail: <strong><?= htmlspecialchars($email) ?></strong></p>
            
            <form method="POST" action="/newsletter/unsubscribe?email=<?= urlencode($email) ?>">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                <button type="submit" class="btn" style="background:#e53935;color:#fff;border:none;padding:14px 32px;border-radius:8px;font-weight:700;font-size:0.95rem;cursor:pointer;">Sim, cancelar minha inscrição</button>
            </form>
            <p style="margin-top: 1.5rem;"><a href="/" style="color: var(--brooks-gray-400); text-decoration: underline; font-size: 0.9rem;">Não, quero continuar recebendo</a></p>

        <?php else: ?>
            <div style="width:80px;height:80px;background:#f5f5f5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 2rem;">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </div>
            <h2 class="headline-subsection" style="margin-bottom: 1rem;">Link inválido</h2>
            <p class="subtitle subtitle--centered">O link de cancelamento está incompleto. Verifique o e-mail recebido e tente novamente.</p>
            <a href="/" class="btn btn--primary" style="margin-top:2rem;">Voltar ao site</a>
        <?php endif; ?>

    </div>
</section>

<?php include ROOT_PATH . '/app/Views/site/layouts/new-footer.php'; ?>
