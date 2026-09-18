<?php
$pageTitle = 'Revista Digital';
$pageDescription = 'Revista Digital da Brooks Construtora. Conteúdo exclusivo sobre construção sustentável, reformas de alto padrão e tendências de arquitetura.';
$currentPage = 'revista';
$bodyClass = 'page-revista';
include ROOT_PATH . '/app/Views/site/layouts/new-header.php';
?>

<!-- Page Hero -->
<section class="section section--dark" style="padding-top: calc(var(--header-height) + var(--space-5xl)); padding-bottom: var(--space-4xl);">
    <div class="container">
        <div class="grid grid--2 reveal" style="align-items: center; gap: var(--space-3xl);">
            <div>
                <span class="label" style="color: var(--brooks-blue-accent);">Conteúdo Exclusivo</span>
                <h1 class="headline-hero" style="color: white;">Revista Digital Brooks</h1>
                <p style="font-size: var(--text-lg); color: rgba(255,255,255,0.6); line-height: 1.7; margin-top: var(--space-lg);">
                    Conteúdo sobre construção sustentável, reformas de alto padrão, tendências de arquitetura e muito mais. Edições gratuitas direto no seu e-mail.
                </p>
            </div>
            <div>
                <div style="background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); border-radius: var(--radius-xl); padding: var(--space-2xl);">
                    <h3 style="color: white; font-size: var(--text-lg); margin-bottom: var(--space-md);">Assine gratuitamente</h3>
                    <p style="color: rgba(255,255,255,0.5); font-size: var(--text-sm); margin-bottom: var(--space-lg);">Receba cada nova edição diretamente no seu e-mail.</p>
                    <form action="/newsletter/subscribe" method="POST" class="newsletter-form-ajax" style="display: flex; flex-direction: column; gap: var(--space-md);">
                        <input type="text" name="name" placeholder="Seu nome" required style="padding: 12px 16px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: var(--radius-md); color: white; font-size: var(--text-sm); outline: none;">
                        <input type="email" name="email" placeholder="Seu melhor e-mail" required style="padding: 12px 16px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: var(--radius-md); color: white; font-size: var(--text-sm); outline: none;">
                        <input type="tel" name="phone" placeholder="WhatsApp (opcional)" style="padding: 12px 16px; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: var(--radius-md); color: white; font-size: var(--text-sm); outline: none;">
                        <button type="submit" class="btn btn--primary" style="width: 100%;">Assinar Grátis</button>
                    </form>
                    <p style="font-size: var(--text-xs); color: rgba(255,255,255,0.3); text-align: center; margin-top: var(--space-sm);">Sem spam. Cancele quando quiser.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Magazines Grid -->
<section class="section">
    <div class="container">
        <?php if (!empty($magazines)): ?>
            <div class="section-header reveal">
                <span class="label">Edições Publicadas</span>
                <h2 class="headline-section">Explore nossas edições</h2>
            </div>
            
            <div class="grid grid--3 reveal" style="gap: var(--space-xl);">
                <?php foreach ($magazines as $mag): ?>
                    <a href="/revista/ver/<?= $mag['id'] ?>" class="magazine-card" style="position:relative;">
                        <?php if (($mag['status'] ?? '') === 'test'): ?>
                            <span style="position:absolute;top:10px;left:10px;z-index:2;background:#e63946;color:#fff;font-size:11px;font-weight:700;letter-spacing:0.5px;padding:5px 10px;border-radius:5px;box-shadow:0 2px 8px rgba(0,0,0,0.3);">
                                MODO TESTE &middot; só você vê
                            </span>
                        <?php endif; ?>
                        <div class="magazine-card__cover">
                            <?php if (!empty($mag['cover_image'])): ?>
                                <img src="<?= htmlspecialchars($mag['cover_image']) ?>" alt="<?= htmlspecialchars($mag['title']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="magazine-card__placeholder">
                                    <i data-lucide="book-open" style="width:48px;height:48px;color:var(--brooks-blue-accent);"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="magazine-card__info">
                            <?php
                                // Nome exibido = Nome da Revista (editável) → título → tema.
                                $magDisplayName = trim($mag['magazine_name'] ?? '') !== ''
                                    ? $mag['magazine_name']
                                    : (trim($mag['title'] ?? '') !== '' ? $mag['title'] : ($mag['topic_title'] ?? ''));
                            ?>
                            <h3 class="magazine-card__title"><?= htmlspecialchars($magDisplayName) ?></h3>
                            <?php $magDate = $mag['published_at'] ?? $mag['created_at'] ?? ''; ?>
                            <?php if (!empty($magDate)): ?>
                                <span class="magazine-card__date"><?= date('d/m/Y', strtotime($magDate)) ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center reveal" style="padding: var(--space-4xl) 0;">
                <i data-lucide="book-open" style="width:64px;height:64px;color:var(--brooks-gray-300);margin-bottom:var(--space-lg);"></i>
                <h2 class="headline-subsection">Em breve, novas edições.</h2>
                <p class="subtitle subtitle--centered">Estamos preparando conteúdos exclusivos para você. Assine acima e seja o primeiro a receber.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- CTA Newsletter -->
<section class="section section--gray" style="padding: var(--space-4xl) 0;">
    <div class="container text-center reveal">
        <h2 class="headline-subsection">Não perca nenhuma edição</h2>
        <p class="subtitle subtitle--centered" style="margin-bottom: var(--space-xl);">Assine gratuitamente e receba conteúdo sobre construção, reformas e arquitetura direto no seu e-mail.</p>
        <form action="/newsletter/subscribe" method="POST" class="newsletter-form-ajax" style="display: flex; gap: var(--space-sm); max-width: 600px; margin: 0 auto; flex-wrap: wrap; justify-content: center;">
            <input type="text" name="name" placeholder="Seu nome" required style="flex: 1; min-width: 120px; padding: 14px 16px; border: 1px solid var(--brooks-gray-200); border-radius: var(--radius-md); font-size: var(--text-sm); outline: none;">
            <input type="email" name="email" placeholder="Seu e-mail" required style="flex: 1; min-width: 160px; padding: 14px 16px; border: 1px solid var(--brooks-gray-200); border-radius: var(--radius-md); font-size: var(--text-sm); outline: none;">
            <input type="tel" name="phone" placeholder="WhatsApp" style="flex: 1; min-width: 130px; padding: 14px 16px; border: 1px solid var(--brooks-gray-200); border-radius: var(--radius-md); font-size: var(--text-sm); outline: none;">
            <button type="submit" class="btn btn--primary">Assinar</button>
        </form>
    </div>
</section>

<?php include ROOT_PATH . '/app/Views/site/layouts/new-footer.php'; ?>
