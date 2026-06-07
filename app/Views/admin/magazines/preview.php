<?php
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'www.brooksconstrutora.com.br';
$baseUrl = $scheme . '://' . $host;
$siteUrl = 'WWW.BROOKSCONSTRUTORA.COM.BR';
$year = date('Y');
try { $magazineLogo = \App\Models\Setting::get('magazine_logo', ''); } catch(\Exception $e) { $magazineLogo = ''; }
if (empty($magazineLogo)) $magazineLogo = '/assets/images/wp/2024/11/logo-brooks-1400x396.webp';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview - <?= htmlspecialchars($magazine['title']) ?></title>
    <link rel="icon" href="/assets/images/wp/2023/01/cropped-favicon-1-32x32.png" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',sans-serif;background:#333;padding:20px 0}
        .preview{max-width:595px;margin:0 auto}
        .page{background:#fff;width:595px;height:842px;margin:0 auto 25px;position:relative;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,0.4);page-break-before:always;page-break-inside:avoid}

        /* ===== CAPA ===== */
        .pg-cover{display:flex;flex-direction:column;align-items:center;padding:0;background:#1a472a}
        .pg-cover .bg{position:absolute;top:0;left:0;right:0;bottom:0;object-fit:cover;width:100%;height:100%}
        .pg-cover .overlay{position:absolute;top:0;left:0;right:0;bottom:0;background:linear-gradient(180deg,rgba(0,0,0,0.4) 0%,rgba(0,0,0,0.05) 35%,rgba(0,0,0,0.05) 50%,rgba(0,0,0,0.4) 70%,rgba(0,0,0,0.8) 90%,rgba(0,0,0,0.92) 100%)}
        .pg-cover .content{position:relative;z-index:2;text-align:center;width:100%;height:100%;display:flex;flex-direction:column;padding:30px 40px}
        .pg-cover .title{font-size:5rem;font-weight:900;color:#fff;text-transform:uppercase;letter-spacing:2px;margin-top:10px;text-shadow:0 2px 10px rgba(0,0,0,0.3)}
        .pg-cover .sub-line{display:flex;align-items:center;justify-content:center;gap:15px;margin-top:5px;font-size:0.7rem;letter-spacing:5px;text-transform:uppercase;color:rgba(255,255,255,0.9)}
        .pg-cover .sub-line .ln{width:40px;height:2px;background:#fff}
        .pg-cover .logo{margin:auto;max-width:220px;filter:drop-shadow(0 4px 20px rgba(0,0,0,0.7))}
        .pg-cover .topic{font-size:1.4rem;font-weight:800;color:#fff;font-style:italic;text-align:left;padding:15px 30px;margin-top:auto;margin-bottom:70px;text-shadow:0 2px 10px rgba(0,0,0,0.8)}
        .pg-cover .foot{position:absolute;bottom:15px;left:25px;right:25px;display:flex;justify-content:space-between;font-size:0.55rem;color:rgba(255,255,255,0.8)}

        /* ===== PÁGINA INTERNA BASE ===== */
        .pg-int{padding:30px 35px;height:842px;overflow:hidden}
        .pg-int .hdr{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px}
        .pg-int .logo-sm{font-weight:800;font-size:0.9rem;color:#111;line-height:1}
        .pg-int .logo-sm .ck{color:#2e7d32}
        .pg-int .logo-sm small{display:block;font-size:0.4rem;font-weight:400;letter-spacing:2px;color:#666}
        .pg-int .pn{font-size:1rem;font-weight:300;color:#333}

        /* Elementos comuns */
        .img-full{width:100%;object-fit:cover;border-radius:0}
        .img-half{width:48%;object-fit:cover}
        .title-big{font-family:'Montserrat',sans-serif;font-size:2.8rem;font-weight:900;font-style:normal;color:#111;margin-bottom:15px;line-height:1.1}
        .title-upper{font-size:0.7rem;text-transform:uppercase;letter-spacing:1.5px;font-weight:600;color:#111;margin-bottom:18px;border-bottom:1px solid #ddd;padding-bottom:10px}
        .subtitle{font-size:1.1rem;font-weight:400;color:#333;margin-bottom:18px}
        .text{font-size:0.78rem;line-height:1.8;color:#333;text-align:left;margin-bottom:12px}
        .text-sm{font-size:0.68rem;line-height:1.7;color:#444;text-align:justify;margin-bottom:8px}
        .caption{font-size:0.78rem;font-weight:700;color:#111;margin-top:10px}
        .caption-sub{font-size:0.58rem;color:#666}
        .two-col{display:flex;gap:18px}
        .two-col .col{flex:1}
        .img-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
        .img-grid img,.img-grid .img-placeholder{width:100%;height:280px;object-fit:cover}

        /* ===== INTERNAL_04 - Imagem full com overlay ===== */
        .overlay-section{position:relative;width:100%;height:420px;overflow:hidden;margin-bottom:15px}
        .overlay-section img{width:100%;height:100%;object-fit:cover}
        .overlay-section .ov{position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,50,0,0.95));padding:25px 30px 20px}
        .overlay-section .ov h2{font-family:'Montserrat',sans-serif;font-size:2rem;font-weight:900;color:#fff}
        .overlay-section .ov p{font-size:0.6rem;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,0.8);margin-top:5px}

        /* ===== CONTRACAPA ===== */
        .pg-back{background:#1a3a2a;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center}
        .pg-back .logo{max-width:250px;margin-bottom:35px}
        .pg-back .txt{color:rgba(255,255,255,0.85);font-size:0.9rem;max-width:380px;line-height:1.6}
        .pg-back .bar{position:absolute;bottom:0;left:0;right:0;background:#e53935;padding:12px 25px;display:flex;justify-content:space-between;font-size:0.6rem;color:#fff}

        /* Placeholder para imagens não carregadas */
        .img-placeholder{background:linear-gradient(135deg,#e3f0e8,#b8d4c8);display:flex;align-items:center;justify-content:center;color:#2e7d32;font-size:0.6rem;text-transform:uppercase;letter-spacing:1px}
    </style>
</head>
<body>
<div class="preview">
<?php foreach ($pages as $page):
    $img1 = $page['image_url'] ?? '';
    $img2 = $page['image_url_2'] ?? '';
    $layout = $page['layout_type'] ?? 'internal_01';
    // Numeração começa em 01 a partir das páginas internas (ignora cover e subcover)
    static $internalPageNum = 0;
    if (!in_array($layout, ['cover', 'subcover', 'backcover'])) {
        $internalPageNum++;
    }
    $displayPageNum = str_pad($internalPageNum, 2, '0', STR_PAD_LEFT);
?>

<?php if ($layout === 'cover'): ?>
<!-- CAPA -->
<div class="page pg-cover">
    <?php if ($magazine['cover_image']): ?><img src="<?= $magazine['cover_image'] ?>" class="bg" alt=""><?php endif; ?>
    <div class="overlay"></div>
    <div class="content">
        <div class="title"><?= htmlspecialchars($page['title'] ?? $magazine['title']) ?></div>
        <div class="sub-line">
            <span><?= htmlspecialchars(explode('—', $page['subtitle'] ?? 'CONSTRUÇÃO — SUSTENTÁVEL')[0] ?? 'CONSTRUÇÃO') ?></span>
            <span class="ln"></span>
            <span><?= htmlspecialchars(trim(explode('—', $page['subtitle'] ?? 'CONSTRUÇÃO — SUSTENTÁVEL')[1] ?? 'SUSTENTÁVEL')) ?></span>
        </div>
        <img src="<?= $magazineLogo ?>" class="logo" alt="Brooks">
        <div class="topic"><?= htmlspecialchars($magazine['subtitle'] ?? 'tema e assunto da revista') ?></div>
        <div class="foot">
            <span>&copy; <?= $year ?> BROOKS CONSTRUTORA.<br>TODOS OS DIREITOS RESERVADOS.</span>
            <span><?= $siteUrl ?></span>
        </div>
    </div>
</div>

<?php elseif ($layout === 'subcover'): ?>
<!-- SUBCAPA -->
<div class="page pg-cover">
    <?php if ($magazine['cover_image']): ?><img src="<?= $magazine['cover_image'] ?>" class="bg" alt=""><?php endif; ?>
    <div class="overlay"></div>
    <div class="content">
        <div style="display:flex;align-items:center;gap:10px;justify-content:center;margin-top:20px;">
            <span style="font-size:3.5rem;font-weight:900;color:#fff;"><?= htmlspecialchars($page['title'] ?? 'ECO') ?></span>
            <img src="<?= $magazineLogo ?>" style="max-width:180px" alt="Brooks">
        </div>
        <div class="sub-line" style="margin-top:12px">
            <span><?= htmlspecialchars(explode('—', $page['subtitle'] ?? 'CONSTRUÇÃO — CONSCIENTE')[0] ?? 'CONSTRUÇÃO') ?></span>
            <span class="ln"></span>
            <span><?= htmlspecialchars(trim(explode('—', $page['subtitle'] ?? 'CONSTRUÇÃO — CONSCIENTE')[1] ?? 'CONSCIENTE')) ?></span>
        </div>
        <div style="flex:1"></div>
        <div class="topic"><?= htmlspecialchars($magazine['subtitle'] ?? 'tema e assunto da revista') ?></div>
        <div class="foot">
            <span>&copy; <?= $year ?> BROOKS CONSTRUTORA.<br>TODOS OS DIREITOS RESERVADOS.</span>
            <span><?= $siteUrl ?></span>
        </div>
    </div>
</div>

<?php elseif ($layout === 'internal_01'): ?>
<!-- PÁG INTERNA 01: Imagem full topo + texto 2 colunas com imagem -->
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <?php if ($img1): ?><img src="<?= $img1 ?>" class="img-full" style="height:300px;margin-bottom:18px" alt=""><?php else: ?><div class="img-full img-placeholder" style="height:300px;margin-bottom:18px">IMAGEM</div><?php endif; ?>
    <?php if ($page['title']): ?><div class="title-upper"><?= htmlspecialchars($page['title']) ?></div><?php endif; ?>
    <div class="two-col">
        <div class="col"><?php foreach(explode("\n",$page['content']??'') as $p): if(trim($p)): ?><p class="text"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?></div>
        <div class="col"><?php if($img2): ?><img src="<?= $img2 ?>" style="width:100%;height:280px;object-fit:cover" alt=""><?php else: ?><div class="img-placeholder" style="width:100%;height:280px">IMAGEM</div><?php endif; ?></div>
    </div>
</div>

<?php elseif ($layout === 'internal_02'): ?>
<!-- PÁG INTERNA 02: Imagem grande esquerda + texto direita + título bold -->
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <div class="two-col" style="margin-bottom:15px">
        <div class="col"><?php if($img1): ?><img src="<?= $img1 ?>" style="width:100%;height:250px;object-fit:cover" alt=""><?php else: ?><div class="img-placeholder" style="width:100%;height:250px">IMAGEM</div><?php endif; ?></div>
        <div class="col">
            <?php if($page['title']): ?><div class="title-upper" style="margin-top:10px"><?= htmlspecialchars($page['title']) ?></div><?php endif; ?>
            <p class="text-sm"><?= htmlspecialchars($page['subtitle'] ?? '') ?></p>
        </div>
    </div>
    <div class="title-big"><?= htmlspecialchars($page['title'] ?? '') ?></div>
    <div class="two-col">
        <div class="col"><?php foreach(explode("\n",$page['content']??'') as $p): if(trim($p)): ?><p class="text"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?></div>
        <div class="col"><?php if($img2): ?><img src="<?= $img2 ?>" style="width:100%;height:150px;object-fit:cover" alt=""><?php else: ?><div class="img-placeholder" style="width:100%;height:150px">IMAGEM</div><?php endif; ?></div>
    </div>
</div>

<?php elseif ($layout === 'internal_03'): ?>
<!-- PÁG INTERNA 03: Título bold + subtítulo + texto full + 2 imagens -->
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <div class="title-big"><?= htmlspecialchars($page['title'] ?? '') ?></div>
    <?php if($page['subtitle']??''): ?><div class="subtitle"><?= htmlspecialchars($page['subtitle']) ?></div><?php endif; ?>
    <?php foreach(explode("\n",$page['content']??'') as $p): if(trim($p)): ?><p class="text"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?>
    <div style="display:flex;gap:10px;margin-top:15px">
        <?php if($img1): ?><img src="<?= $img1 ?>" class="img-half" style="height:260px" alt=""><?php else: ?><div class="img-half img-placeholder" style="height:260px">IMAGEM</div><?php endif; ?>
        <?php if($img2): ?><img src="<?= $img2 ?>" class="img-half" style="height:260px" alt=""><?php else: ?><div class="img-half img-placeholder" style="height:260px">IMAGEM</div><?php endif; ?>
    </div>
    <?php if($page['caption']??''): ?><div class="caption" style="margin-top:8px"><?= htmlspecialchars($page['caption']) ?></div><?php endif; ?>
</div>

<?php elseif ($layout === 'internal_04'): ?>
<!-- PÁG INTERNA 04: Imagem full com overlay + título sobreposto -->
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <div class="overlay-section">
        <?php if($img1): ?><img src="<?= $img1 ?>" alt=""><?php else: ?><div class="img-placeholder" style="width:100%;height:100%">IMAGEM</div><?php endif; ?>
        <div class="ov">
            <h2><?= htmlspecialchars($page['title'] ?? '') ?></h2>
            <?php if($page['subtitle']??''): ?><p><?= htmlspecialchars($page['subtitle']) ?></p><?php endif; ?>
        </div>
    </div>
    <?php foreach(explode("\n",$page['content']??'') as $p): if(trim($p)): ?><p class="text"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?>
</div>

<?php elseif ($layout === 'internal_05'): ?>
<!-- PÁG INTERNA 05: 2 imagens + 2 colunas texto -->
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <div style="display:flex;gap:10px;margin-bottom:10px">
        <?php if($img1): ?><img src="<?= $img1 ?>" class="img-half" style="height:260px" alt=""><?php else: ?><div class="img-half img-placeholder" style="height:260px">IMAGEM</div><?php endif; ?>
        <?php if($img2): ?><img src="<?= $img2 ?>" class="img-half" style="height:260px" alt=""><?php else: ?><div class="img-half img-placeholder" style="height:260px">IMAGEM</div><?php endif; ?>
    </div>
    <?php if($page['caption']??''): ?><div class="caption"><?= htmlspecialchars($page['caption']) ?></div><div class="caption-sub"><?= htmlspecialchars($page['subtitle']??'') ?></div><?php endif; ?>
    <div style="margin-top:15px"><div class="title-big" style="font-size:1.8rem"><?= htmlspecialchars($page['title'] ?? '') ?></div></div>
    <div class="two-col">
        <?php $cols = explode('|||', $page['content']??''); ?>
        <div class="col"><?php foreach(explode("\n",$cols[0]??'') as $p): if(trim($p)): ?><p class="text"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?></div>
        <div class="col"><?php foreach(explode("\n",$cols[1]??$cols[0]??'') as $p): if(trim($p)): ?><p class="text"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?></div>
    </div>
</div>

<?php elseif ($layout === 'internal_06'): ?>
<!-- PÁG INTERNA 06: Grid 2 imagens topo + 1 imagem esquerda + texto direita -->
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <div style="display:flex;gap:8px;margin-bottom:8px">
        <?php if($img1): ?><img src="<?= $img1 ?>" style="width:50%;height:280px;object-fit:cover" alt=""><?php else: ?><div class="img-placeholder" style="width:50%;height:280px">IMAGEM 1</div><?php endif; ?>
        <?php if($img2): ?><img src="<?= $img2 ?>" style="width:50%;height:280px;object-fit:cover" alt=""><?php else: ?><div class="img-placeholder" style="width:50%;height:280px">IMAGEM 2</div><?php endif; ?>
    </div>
    <div style="display:flex;gap:8px">
        <?php $img3 = $page['image_url_3'] ?? ''; if($img3): ?><img src="<?= $img3 ?>" style="width:50%;height:280px;object-fit:cover" alt=""><?php else: ?><div class="img-placeholder" style="width:50%;height:280px">IMAGEM 3</div><?php endif; ?>
        <div style="width:50%;padding:15px 10px"><?php foreach(explode("\n",$page['content']??'') as $p): if(trim($p)): ?><p class="text-sm"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?></div>
    </div>
</div>

<?php elseif ($layout === 'internal_07'): ?>
<!-- PÁG INTERNA 07: Citação grande + imagem com texto -->
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <div style="margin:40px 0 30px"><p style="font-size:1.8rem;font-weight:600;color:#111;line-height:1.3"><?= htmlspecialchars($page['title'] ?? '') ?></p></div>
    <div class="two-col">
        <div class="col"><?php if($img1): ?><img src="<?= $img1 ?>" style="width:100%;height:420px;object-fit:cover" alt=""><?php else: ?><div class="img-placeholder" style="width:100%;height:420px">IMAGEM</div><?php endif; ?></div>
        <div class="col"><?php foreach(explode("\n",$page['content']??'') as $p): if(trim($p)): ?><p class="text-sm"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?></div>
    </div>
</div>

<?php elseif ($layout === 'backcover'): ?>
<!-- CONTRACAPA -->
<div class="page pg-back">
    <img src="<?= $magazineLogo ?>" class="logo" alt="Brooks Construtora">
    <div class="txt"><?= nl2br(htmlspecialchars($page['content'] ?? 'Construção consciente do zero ao acabamento. Comprometidos com o meio ambiente, com as pessoas e com o futuro.')) ?></div>
    <div class="bar">
        <span>&copy; <?= $year ?> BROOKS CONSTRUTORA.<br>TODOS OS DIREITOS RESERVADOS.</span>
        <span><?= $siteUrl ?></span>
    </div>
</div>

<?php else: ?>
<!-- FALLBACK -->
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <?php if($img1): ?><img src="<?= $img1 ?>" class="img-full" style="height:250px;margin-bottom:15px" alt=""><?php endif; ?>
    <?php if($page['title']): ?><div class="title-big"><?= htmlspecialchars($page['title']) ?></div><?php endif; ?>
    <?php foreach(explode("\n",$page['content']??'') as $p): if(trim($p)): ?><p class="text"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?>
</div>
<?php endif; ?>

<?php endforeach; ?>
</div>

<!-- Botão flutuante para baixar PDF -->
<div id="pdf-toolbar" style="position:fixed;top:20px;right:20px;z-index:9999;display:flex;gap:10px;">
    <button onclick="generatePDF()" id="btn-pdf" style="background:#e53935;color:#fff;border:none;padding:12px 24px;border-radius:50px;font-weight:700;font-size:14px;cursor:pointer;box-shadow:0 4px 15px rgba(0,0,0,0.3);display:flex;align-items:center;gap:8px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Baixar PDF
    </button>
    <a href="/admin/magazines/edit/<?= $magazine['id'] ?>" style="background:#3a3b4e;color:#fff;border:none;padding:12px 24px;border-radius:50px;font-weight:700;font-size:14px;cursor:pointer;box-shadow:0 4px 15px rgba(0,0,0,0.3);text-decoration:none;display:flex;align-items:center;gap:8px;">
        ← Voltar
    </a>
</div>

<!-- html2pdf.js (client-side PDF generation) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js"></script>
<script>
function generatePDF() {
    var btn = document.getElementById('btn-pdf');
    btn.disabled = true;
    btn.innerHTML = '<span style="display:inline-block;width:16px;height:16px;border:2px solid #fff;border-top-color:transparent;border-radius:50%;animation:spin 1s linear infinite;"></span> Gerando...';
    
    var style = document.createElement('style');
    style.textContent = '@keyframes spin{to{transform:rotate(360deg)}}';
    document.head.appendChild(style);

    var element = document.querySelector('.preview');
    var filename = '<?= preg_replace('/[^a-zA-Z0-9_-]/', '_', $magazine['title']) ?>_Brooks_Construtora.pdf';

    // Esconde toolbar durante geração
    document.getElementById('pdf-toolbar').style.display = 'none';

    var opt = {
        margin: 0,
        filename: filename,
        image: { type: 'jpeg', quality: 0.95 },
        html2canvas: { 
            scale: 2, 
            useCORS: true,
            allowTaint: true,
            scrollY: 0,
            windowWidth: 595
        },
        jsPDF: { 
            unit: 'px', 
            format: [595, 842], 
            orientation: 'portrait',
            hotfixes: ['px_scaling']
        },
        pagebreak: { mode: ['css', 'legacy'], before: '.page' }
    };

    html2pdf().set(opt).from(element).save().then(function() {
        document.getElementById('pdf-toolbar').style.display = 'flex';
        btn.disabled = false;
        btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg> Baixar PDF';
    });
}
</script>

</body>
</html>


