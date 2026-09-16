<?php
/**
 * Visualizador de PDF da revista.
 *
 * O PDF foi gerado no servidor (Browserless / Chrome na nuvem) e é o mesmo
 * arquivo para todos — então aparece IDÊNTICO em qualquer aparelho
 * (iPhone, iPad, Safari, Android, PC). Nada é renderizado pelo navegador do
 * leitor além do próprio PDF.
 *
 * Variáveis esperadas: $magazine, $pdfUrl (relativa), $previewToken (opcional).
 */
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'www.brooksconstrutora.com.br';
$baseUrl = $scheme . '://' . $host;

// Cache-busting pelo mtime do arquivo (garante a versão nova após regerar).
$pdfAbs = ROOT_PATH . '/public' . $pdfUrl;
$ver = is_file($pdfAbs) ? filemtime($pdfAbs) : time();
$pdfFull = $baseUrl . $pdfUrl . '?v=' . $ver;

$title = $magazine['title'] ?? 'Revista Brooks';
$esc = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $esc($title) ?> | Revista Brooks Construtora</title>
    <link rel="icon" href="/assets/images/wp/2023/01/cropped-favicon-1-32x32.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        html,body{height:100%}
        body{font-family:'Inter',Arial,sans-serif;background:#0a1628;display:flex;flex-direction:column}
        .topbar{background:#0a1628;color:#fff;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;border-bottom:1px solid rgba(255,255,255,0.08);flex-shrink:0}
        .topbar .brand{display:flex;flex-direction:column;min-width:0}
        .topbar .brand .name{font-weight:900;letter-spacing:2px;font-size:15px;line-height:1}
        .topbar .brand .name .ck{color:#e63946}
        .topbar .brand .mag{font-size:11px;color:rgba(255,255,255,0.6);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:3px}
        .topbar .actions{display:flex;gap:8px;flex-shrink:0}
        .btn{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;text-decoration:none;padding:9px 14px;border-radius:6px;border:none;cursor:pointer;white-space:nowrap}
        .btn-primary{background:#fff;color:#0a1628}
        .btn-ghost{background:rgba(255,255,255,0.08);color:#fff}
        .viewer{flex:1;position:relative;background:#525659;min-height:0}
        .viewer iframe{width:100%;height:100%;border:0;display:block}
        /* Aviso/fallback exibido por baixo do iframe (aparece se o embed falhar,
           típico em alguns iPhones que não embutem PDF). */
        .fallback{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:30px;color:#fff;gap:18px;z-index:0}
        .fallback .ico{font-size:44px}
        .fallback p{color:rgba(255,255,255,0.75);font-size:14px;max-width:320px;line-height:1.6}
        .viewer iframe{position:relative;z-index:1;background:#525659}
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
            <a href="<?= $esc($pdfFull) ?>" target="_blank" rel="noopener" class="btn btn-primary">Abrir</a>
            <a href="<?= $esc($pdfFull) ?>" download class="btn btn-ghost">Baixar</a>
        </div>
    </div>

    <div class="viewer">
        <!-- Fallback (fica por baixo): aparece se o iframe não renderizar o PDF -->
        <div class="fallback">
            <div class="ico">&#128214;</div>
            <p>Toque em <strong>“Abrir”</strong> no topo para ler a revista completa no seu dispositivo.</p>
            <a href="<?= $esc($pdfFull) ?>" target="_blank" rel="noopener" class="btn btn-primary">Abrir a revista</a>
        </div>
        <!-- Embed do PDF: bom no desktop e Android. No iOS o botão "Abrir" leva
             ao visualizador nativo (melhor experiência lá). -->
        <iframe src="<?= $esc($pdfFull) ?>" title="<?= $esc($title) ?>" allow="fullscreen"></iframe>
    </div>
</body>
</html>
