<?php
/**
 * Visualizador da revista DENTRO do site.
 *
 * Estratégia principal: exibe as IMAGENS de cada página, renderizadas pelo
 * Chrome do Browserless (mesmo motor do PDF). Assim as sombras, gradientes e
 * efeitos aparecem perfeitos e IDÊNTICOS em qualquer aparelho (iPhone, iPad,
 * Safari, Android, PC) — sem os bugs do PDF.js (que não suporta ShadingType/
 * SMask e deixava a capa rosa). Um botão "Baixar" entrega o PDF.
 *
 * Fallback: se ainda não houver imagens geradas (revista antiga só com PDF),
 * usa o PDF.js para renderizar o arquivo.
 *
 * Variáveis: $magazine, $pdfUrl (relativa), $pageImages (array de URLs), $previewToken.
 */
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'www.brooksconstrutora.com.br';
$baseUrl = $scheme . '://' . $host;

$pdfAbs = ROOT_PATH . '/public' . $pdfUrl;
$ver = is_file($pdfAbs) ? filemtime($pdfAbs) : time();
$pdfFull = $pdfUrl . '?v=' . $ver;

$images = $pageImages ?? [];
$title = $magazine['title'] ?? 'Revista Brooks';
$esc = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?= $esc($title) ?> | Revista Brooks Construtora</title>
    <link rel="icon" href="/assets/images/wp/2023/01/cropped-favicon-1-32x32.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        html,body{height:100%}
        body{font-family:'Inter',Arial,sans-serif;background:#525659;display:flex;flex-direction:column}
        .topbar{background:#0a1628;color:#fff;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,0.3);z-index:10}
        .topbar .brand{display:flex;flex-direction:column;min-width:0}
        .topbar .brand .name{font-weight:900;letter-spacing:2px;font-size:15px;line-height:1}
        .topbar .brand .name .ck{color:#e63946}
        .topbar .brand .mag{font-size:11px;color:rgba(255,255,255,0.6);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:3px}
        .topbar .actions{display:flex;gap:8px;flex-shrink:0}
        .btn{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;text-decoration:none;padding:9px 14px;border-radius:6px;border:none;cursor:pointer;white-space:nowrap}
        .btn-primary{background:#fff;color:#0a1628}
        .btn-ghost{background:rgba(255,255,255,0.08);color:#fff}

        #viewer{flex:1;overflow:auto;-webkit-overflow-scrolling:touch;padding:16px 10px 40px;min-height:0}
        #pages{max-width:820px;margin:0 auto;display:flex;flex-direction:column;align-items:center;gap:16px}
        #pages img,#pages canvas{width:100%;height:auto;display:block;background:#fff;box-shadow:0 6px 24px rgba(0,0,0,0.35);border-radius:2px}

        #loading{color:#fff;text-align:center;padding:60px 20px;font-size:14px}
        #loading .spin{display:inline-block;width:26px;height:26px;border:3px solid rgba(255,255,255,0.25);border-top-color:#fff;border-radius:50%;animation:sp 0.8s linear infinite;margin-bottom:12px}
        @keyframes sp{to{transform:rotate(360deg)}}
        #errbox{display:none;color:#fff;text-align:center;padding:50px 24px;gap:16px;flex-direction:column;align-items:center}
        #errbox p{color:rgba(255,255,255,0.8);font-size:14px;max-width:340px;line-height:1.6}
    </style>
</head>
<body>
    <div class="topbar">
        <a href="/revista" class="btn btn-ghost" title="Voltar">&larr;</a>
        <div class="brand">
            <span class="name">BRO<span class="ck">O</span>KS</span>
            <span class="mag"><?= $esc($title) ?></span>
        </div>
        <div class="actions">
            <a href="<?= $esc($pdfFull) ?>" download class="btn btn-primary" title="Baixar PDF">&#8681; Baixar</a>
        </div>
    </div>

    <div id="viewer">
        <?php if (!empty($images)): ?>
            <!-- Páginas como imagens (renderizadas pelo Chrome): idêntico ao PDF -->
            <div id="pages">
                <?php foreach ($images as $i => $img): ?>
                    <img src="<?= $esc($img) ?>?v=<?= $ver ?>"
                         alt="Página <?= $i + 1 ?> - <?= $esc($title) ?>"
                         loading="<?= $i < 2 ? 'eager' : 'lazy' ?>">
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Fallback: sem imagens geradas ainda → renderiza o PDF com PDF.js -->
            <div id="loading"><span class="spin"></span><br>Carregando revista...</div>
            <div id="errbox">
                <div style="font-size:42px">&#128214;</div>
                <p>Não foi possível carregar o visualizador aqui. Você pode abrir a revista em PDF diretamente.</p>
                <a href="<?= $esc($pdfFull) ?>" target="_blank" rel="noopener" class="btn btn-primary">Abrir a revista (PDF)</a>
            </div>
            <div id="pages"></div>
        <?php endif; ?>
    </div>

    <?php if (empty($images)): ?>
    <!-- PDF.js só é carregado no fallback (quando não há imagens) -->
    <script src="/assets/pdfjs/pdf.min.js"></script>
    <script>
    (function () {
        var PDF_URL = <?= json_encode($pdfFull) ?>;
        var loadingEl = document.getElementById('loading');
        var errEl = document.getElementById('errbox');
        var pagesEl = document.getElementById('pages');
        function showError() { if (loadingEl) loadingEl.style.display='none'; if (errEl) errEl.style.display='flex'; }
        if (!window['pdfjsLib']) { showError(); return; }
        pdfjsLib.GlobalWorkerOptions.workerSrc = '/assets/pdfjs/pdf.worker.min.js';
        var DPR = Math.min(window.devicePixelRatio || 1, 2);
        pdfjsLib.getDocument({
            url: PDF_URL,
            cMapUrl: '/assets/pdfjs/cmaps/',
            cMapPacked: true,
            standardFontDataUrl: '/assets/pdfjs/standard_fonts/'
        }).promise.then(function (pdf) {
            if (loadingEl) loadingEl.style.display = 'none';
            var chain = Promise.resolve();
            for (var n = 1; n <= pdf.numPages; n++) {
                (function (num) {
                    chain = chain.then(function () {
                        return pdf.getPage(num).then(function (page) {
                            var cw = pagesEl.clientWidth || 800;
                            var base = page.getViewport({ scale: 1 });
                            var vp = page.getViewport({ scale: (cw / base.width) * DPR });
                            var canvas = document.createElement('canvas');
                            var ctx = canvas.getContext('2d', { alpha: false });
                            canvas.width = Math.floor(vp.width);
                            canvas.height = Math.floor(vp.height);
                            canvas.style.width = '100%';
                            pagesEl.appendChild(canvas);
                            return page.render({ canvasContext: ctx, viewport: vp, background: '#ffffff' }).promise;
                        });
                    });
                })(n);
            }
            return chain;
        }).catch(function () { showError(); });
    })();
    </script>
    <?php endif; ?>
</body>
</html>
