<?php
$pageTitle = htmlspecialchars($magazine['title']) . ' — Revista Brooks';
$pageDescription = 'Leia a edição "' . htmlspecialchars($magazine['title']) . '" da Revista Digital Brooks Construtora.';
$currentPage = 'revista';
$bodyClass = 'page-revista-show';
include ROOT_PATH . '/app/Views/site/layouts/new-header.php';
?>

<!-- Page Header -->
<section style="padding-top: calc(var(--header-height) + var(--space-2xl)); padding-bottom: var(--space-xl); background: var(--brooks-off-white);">
    <div class="container" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: var(--space-md);">
        <div>
            <a href="/revista" style="display: inline-flex; align-items: center; gap: var(--space-xs); font-size: var(--text-sm); color: var(--brooks-gray-500); margin-bottom: var(--space-sm);">
                <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Voltar para Revista
            </a>
            <h1 style="font-size: var(--text-2xl); font-weight: 700; color: var(--brooks-navy);"><?= htmlspecialchars($magazine['title']) ?></h1>
            <?php if (!empty($magazine['subtitle'])): ?>
                <p style="font-size: var(--text-sm); color: var(--brooks-gray-500); margin-top: var(--space-xs);"><?= htmlspecialchars($magazine['subtitle']) ?></p>
            <?php endif; ?>
        </div>
        <button onclick="downloadPDF()" id="btn-pdf-site" class="btn btn--primary btn--sm">
            <i data-lucide="download" style="width:16px;height:16px;"></i> Baixar PDF
        </button>
    </div>
</section>

<!-- Magazine Content via iframe (isolamento total de CSS) -->
<section style="padding: var(--space-2xl) 0 var(--space-4xl);">
    <div style="max-width:620px;margin:0 auto;padding:0 10px;">
        <iframe id="magazine-frame" src="/revista/embed/<?= $magazine['id'] ?>" style="width:100%;border:none;display:block;" onload="resizeIframe(this)"></iframe>
    </div>
</section>

<script>
function resizeIframe(iframe) {
    try {
        iframe.style.height = iframe.contentWindow.document.documentElement.scrollHeight + 'px';
        // Observa mudanças (paginação pode alterar altura)
        var observer = new MutationObserver(function() {
            iframe.style.height = iframe.contentWindow.document.documentElement.scrollHeight + 'px';
        });
        observer.observe(iframe.contentWindow.document.body, { childList: true, subtree: true, attributes: true });
        // Fallback: recheck after images load
        setTimeout(function() {
            iframe.style.height = iframe.contentWindow.document.documentElement.scrollHeight + 'px';
        }, 4000);
    } catch(e) {}
}

function downloadPDF() {
    var iframe = document.getElementById('magazine-frame');
    try {
        iframe.contentWindow.generatePDF();
    } catch(e) {
        alert('Erro ao gerar PDF. Tente novamente.');
    }
}
</script>

<?php include ROOT_PATH . '/app/Views/site/layouts/new-footer.php'; ?>
