<?php
$pageTitle = htmlspecialchars($magazine['title']) . ' — Revista Brooks';
$pageDescription = 'Leia a edição "' . htmlspecialchars($magazine['title']) . '" da Revista Digital Brooks Construtora.';
$currentPage = 'revista';
$bodyClass = 'page-revista-show';
include ROOT_PATH . '/app/Views/site/layouts/new-header.php';

// Gera o HTML do preview (idêntico ao admin) via output buffer
ob_start();
$isAdmin = false;
$siteUrl = 'WWW.BROOKSCONSTRUTORA.COM.BR';
$year = date('Y');
try { $magazineLogo = \App\Models\Setting::get('magazine_logo', ''); } catch (\Exception $e) { $magazineLogo = ''; }
if (empty($magazineLogo)) $magazineLogo = '/assets/images/wp/2024/11/logo-brooks-1400x396.webp';
$pages = \App\Models\Magazine::getPages($magazine['id']);
include ROOT_PATH . '/app/Views/admin/magazines/preview.php';
$previewHtml = ob_get_clean();

// Converte URLs relativas para absolutas (necessário para iframe blob)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'www.brooksconstrutora.com.br';
$absBase = $scheme . '://' . $host;
$previewHtml = preg_replace('/(src|href)=(["\'])\//', '$1=$2' . $absBase . '/', $previewHtml);
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

<!-- Magazine Preview (iframe blob - isolamento total) -->
<section style="padding: var(--space-xl) 0 var(--space-4xl); background: #f5f5f5;">
    <div style="max-width: 615px; margin: 0 auto;">
        <iframe id="magazine-frame" style="width:100%;min-height:5000px;border:none;display:block;"></iframe>
    </div>
</section>

<script id="preview-data" type="text/plain"><?= base64_encode($previewHtml) ?></script>
<script>
(function() {
    var b64 = document.getElementById('preview-data').textContent.trim();
    var binary = atob(b64);
    var bytes = new Uint8Array(binary.length);
    for (var i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    var html = new TextDecoder('utf-8').decode(bytes);
    var iframe = document.getElementById('magazine-frame');
    var blob = new Blob([html], {type: 'text/html;charset=utf-8'});
    var url = URL.createObjectURL(blob);
    iframe.src = url;

    iframe.onload = function() {
        URL.revokeObjectURL(url);
        adjustIframeHeight();
    };
})();

function adjustIframeHeight() {
    var iframe = document.getElementById('magazine-frame');
    function doAdjust() {
        try {
            var doc = iframe.contentDocument || iframe.contentWindow.document;
            var body = doc.body;
            if (body) {
                var height = Math.max(body.scrollHeight, body.offsetHeight);
                iframe.style.height = (height + 50) + 'px';
            }
        } catch(e) {}
    }
    setTimeout(doAdjust, 1000);
    setTimeout(doAdjust, 3000);
    setTimeout(doAdjust, 5000);
    setTimeout(doAdjust, 8000);
}

window.addEventListener('resize', function() { setTimeout(adjustIframeHeight, 500); });

function downloadPDF() {
    var iframe = document.getElementById('magazine-frame');
    var btn = document.getElementById('btn-pdf');
    try {
        var iframeWin = iframe.contentWindow;
        if (iframeWin && typeof iframeWin.generatePDF === 'function') {
            btn.disabled = true;
            btn.innerHTML = '<span>Gerando PDF...</span>';
            iframeWin.generatePDF();
            setTimeout(function() {
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="download" style="width:16px;height:16px;"></i> Baixar PDF';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }, 10000);
        } else {
            alert('Aguarde o carregamento completo da revista.');
        }
    } catch(e) {
        alert('Erro ao gerar PDF. Tente novamente.');
    }
}
</script>

<?php include ROOT_PATH . '/app/Views/site/layouts/new-footer.php'; ?>
