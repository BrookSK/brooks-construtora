<?php
$pageTitle = 'Erro interno';
$pageDescription = 'Ocorreu um erro inesperado no servidor.';
$currentPage = '';
$bodyClass = 'page-error';

try {
    $settings = \App\Models\Setting::getGroup('site_');
} catch (\Exception $e) {
    $settings = [];
}

include ROOT_PATH . '/app/Views/site/layouts/new-header.php';
?>

<section style="padding-top: calc(var(--header-height) + var(--space-4xl)); padding-bottom: var(--space-5xl); min-height: 60vh; display: flex; align-items: center; background: linear-gradient(160deg, #f8f9fa 0%, #ffffff 100%); position: relative; overflow: hidden;">
    <!-- Decorative elements -->
    <div style="position: absolute; top: 20%; right: 10%; width: 300px; height: 300px; background: radial-gradient(circle, rgba(231, 76, 60, 0.04), transparent 70%); border-radius: 50%;"></div>
    <div style="position: absolute; bottom: 10%; left: 5%; width: 200px; height: 200px; background: radial-gradient(circle, rgba(231, 76, 60, 0.03), transparent 70%); border-radius: 50%;"></div>

    <div class="container" style="position: relative; z-index: 1;">
        <div class="reveal" style="text-align: center; max-width: 600px; margin: 0 auto;">
            <div style="font-size: clamp(6rem, 15vw, 10rem); font-weight: 800; color: var(--brooks-gray-200); line-height: 1; margin-bottom: var(--space-md); letter-spacing: -4px;">500</div>
            <h1 style="font-size: var(--text-2xl); font-weight: 700; color: var(--brooks-navy); margin-bottom: var(--space-md);">Erro interno do servidor</h1>
            <p style="font-size: var(--text-base); color: var(--brooks-gray-500); line-height: 1.7; margin-bottom: var(--space-2xl);">
                Ocorreu um erro inesperado. Nossa equipe já foi notificada. Tente novamente em alguns instantes.
            </p>
            <div style="display: flex; gap: var(--space-md); justify-content: center; flex-wrap: wrap;">
                <a href="/" class="btn btn--primary btn--lg">Voltar para Home</a>
                <a href="/contato" class="btn btn--outline btn--lg">Falar conosco</a>
            </div>
        </div>
    </div>
</section>

<?php include ROOT_PATH . '/app/Views/site/layouts/new-footer.php'; ?>
