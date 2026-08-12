<?php
$pageTitle = htmlspecialchars($magazine['title']) . ' — Revista Brooks';
$pageDescription = 'Leia a edição "' . htmlspecialchars($magazine['title']) . '" da Revista Digital Brooks Construtora.';
$currentPage = 'revista';
$bodyClass = 'page-revista-show';
$siteUrl = 'WWW.BROOKSCONSTRUTORA.COM.BR';
$year = date('Y');
try { $magazineLogo = \App\Models\Setting::get('magazine_logo', ''); } catch (\Exception $e) { $magazineLogo = ''; }
if (empty($magazineLogo)) $magazineLogo = '/assets/images/wp/2024/11/logo-brooks-1400x396.webp';
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
        <button onclick="downloadPDF()" id="btn-pdf" class="btn btn--primary btn--sm">
            <i data-lucide="download" style="width:16px;height:16px;"></i> Baixar PDF
        </button>
    </div>
</section>

<!-- Magazine Preview (iframe isolado - sem conflito de CSS) -->
<section style="padding: var(--space-xl) 0 var(--space-4xl);">
    <div style="max-width: 615px; margin: 0 auto;">
        <div id="pdf-loading" style="display:none;text-align:center;margin:30px 0;">
            <div style="display:inline-block;width:24px;height:24px;border:3px solid #ddd;border-top-color:#1a3d6d;border-radius:50%;animation:pdfspin 0.8s linear infinite;margin-bottom:10px;"></div>
            <p style="margin:0;font-size:13px;color:#555;font-weight:500;">Gerando PDF, aguarde...</p>
        </div>
        <style>@keyframes pdfspin{to{transform:rotate(360deg)}}</style>
        <iframe id="magazine-frame" src="/revista/preview/<?= $magazine['id'] ?>" style="width:100%;border:none;display:block;" onload="adjustIframeHeight()"></iframe>
    </div>
</section>

<script>
// Ajusta altura do iframe para mostrar todo o conteúdo
function adjustIframeHeight() {
    var iframe = document.getElementById('magazine-frame');
    function doAdjust() {
        try {
            var doc = iframe.contentDocument || iframe.contentWindow.document;
            var body = doc.body;
            if (body) {
                var height = Math.max(body.scrollHeight, body.offsetHeight);
                iframe.style.height = height + 50 + 'px';
            }
        } catch(e) {
            iframe.style.height = '8000px';
        }
    }
    // Ajusta múltiplas vezes pra pegar a paginação
    setTimeout(doAdjust, 1000);
    setTimeout(doAdjust, 3000);
    setTimeout(doAdjust, 5000);
    setTimeout(doAdjust, 8000);
}

// Recalcula altura quando a janela redimensiona
window.addEventListener('resize', function() {
    setTimeout(adjustIframeHeight, 500);
});

// Baixa o PDF chamando a função dentro do iframe
function downloadPDF() {
    var iframe = document.getElementById('magazine-frame');
    var btn = document.getElementById('btn-pdf');
    var loading = document.getElementById('pdf-loading');
    
    try {
        var iframeWin = iframe.contentWindow;
        if (iframeWin && typeof iframeWin.generatePDF === 'function') {
            btn.disabled = true;
            btn.innerHTML = '<span>Gerando PDF...</span>';
            if (loading) loading.style.display = 'block';
            
            // Chama a função de gerar PDF que já existe no iframe (do admin)
            iframeWin.generatePDF();
            
            // Restaura botão após um tempo (o PDF é gerado no iframe)
            setTimeout(function() {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="download" style="width:16px;height:16px;"></i> Baixar PDF';
                if (loading) loading.style.display = 'none';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }, 8000);
        } else {
            alert('Aguarde o carregamento da revista.');
        }
    } catch(e) {
        // Fallback: abre o preview em nova aba para baixar
        window.open('/revista/preview/' + <?= $magazine['id'] ?>, '_blank');
    }
}
</script>

<?php include ROOT_PATH . '/app/Views/site/layouts/new-footer.php'; ?>
