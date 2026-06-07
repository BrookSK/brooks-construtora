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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,700;0,800;0,900;1,700;1,800&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',sans-serif;background:#333;padding:20px 0}
        .preview{max-width:595px;margin:0 auto}
        .page{background:#fff;width:100%;height:842px;margin:0 auto 25px;position:relative;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,0.4);page-break-after:always}

        /* ===== CAPA ===== */
        .pg-cover{display:flex;flex-direction:column;align-items:center;padding:0}
        .pg-cover .bg{position:absolute;top:0;left:0;right:0;bottom:0;object-fit:cover;width:100%;height:100%}
        .pg-cover .overlay{position:absolute;top:0;left:0;right:0;bottom:0;background:linear-gradient(180deg,rgba(0,0,0,0.1) 0%,rgba(0,0,0,0.5) 100%)}
        .pg-cover .content{position:relative;z-index:2;text-align:center;width:100%;height:100%;display:flex;flex-direction:column;padding:40px}
        .pg-cover .title{font-size:4.5rem;font-weight:900;color:#fff;text-transform:uppercase;letter-spacing:2px;margin-top:20px}
        .pg-cover .sub-line{display:flex;align-items:center;justify-content:center;gap:15px;margin-top:8px;font-size:0.7rem;letter-spacing:5px;text-transform:uppercase;color:#fff}
        .pg-cover .sub-line .ln{width:40px;height:2px;background:#fff}
        .pg-cover .logo{margin:auto;max-width:220px}
        .pg-cover .topic{font-size:1.2rem;font-weight:800;color:#fff;font-style:italic;text-align:left;padding:0 30px;margin-bottom:60px}
        .pg-cover .foot{position:absolute;bottom:20px;left:25px;right:25px;display:flex;justify-content:space-between;font-size:0.55rem;color:rgba(255,255,255,0.7)}

        /* ===== PÁGINA INTERNA BASE ===== */
        .pg-int{padding:30px 35px}
        .pg-int .hdr{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px}
        .pg-int .logo-sm{font-weight:800;font-size:0.9rem;color:#111;line-height:1}
        .pg-int .logo-sm .ck{color:#2e7d32}
        .pg-int .logo-sm small{display:block;font-size:0.4rem;font-weight:400;letter-spacing:2px;color:#666}
        .pg-int .pn{font-size:1rem;font-weight:300;color:#333}

        /* Elementos comuns */
        .img-full{width:100%;object-fit:cover;border-radius:0}
        .img-half{width:48%;object-fit:cover}
        .title-big{font-family:'Playfair Display',serif;font-size:2.2rem;font-weight:800;font-style:italic;color:#111;margin-bottom:12px}
        .title-upper{font-size:0.65rem;text-transform:uppercase;letter-spacing:1px;font-weight:500;color:#111;margin-bottom:15px}
        .subtitle{font-size:0.95rem;font-weight:400;color:#333;margin-bottom:12px}
        .text{font-size:0.72rem;line-height:1.7;color:#333;text-align:justify;margin-bottom:10px}
        .text-sm{font-size:0.6rem;line-height:1.6;color:#444;text-align:justify}
        .caption{font-size:0.7rem;font-weight:600;color:#111;margin-top:5px}
        .caption-sub{font-size:0.55rem;color:#666}
        .two-col{display:flex;gap:15px}
        .two-col .col{flex:1}
        .img-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}
        .img-grid img{width:100%;height:180px;object-fit:cover}

        /* ===== INTERNAL_04 - Imagem full com overlay ===== */
        .overlay-section{position:relative;width:100%;height:380px;overflow:hidden;margin-bottom:15px}
        .overlay-section img{width:100%;height:100%;object-fit:cover}
        .overlay-section .ov{position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,50,0,0.95));padding:25px 30px 20px}
        .overlay-section .ov h2{font-family:'Playfair Display',serif;font-size:2rem;font-weight:800;font-style:italic;color:#fff}
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
        <div class="topic">(<?= htmlspecialchars($magazine['subtitle'] ?? 'tema e assunto da revista') ?>)</div>
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
        <div class="topic">(<?= htmlspecialchars($magazine['subtitle'] ?? 'tema e assunto da revista') ?>)</div>
        <div class="foot">
            <span>&copy; <?= $year ?> BROOKS CONSTRUTORA.<br>TODOS OS DIREITOS RESERVADOS.</span>
            <span><?= $siteUrl ?></span>
        </div>
    </div>
</div>

<?php elseif ($layout === 'internal_01'): ?>
<!-- PÁG INTERNA 01: Imagem full topo + texto 2 colunas com imagem -->
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= str_pad($page['page_number'],2,'0',STR_PAD_LEFT) ?></div></div>
    <?php if ($img1): ?><img src="<?= $img1 ?>" class="img-full" style="height:220px;margin-bottom:15px" alt=""><?php else: ?><div class="img-full img-placeholder" style="height:220px;margin-bottom:15px">IMAGEM</div><?php endif; ?>
    <?php if ($page['title']): ?><div class="title-upper"><?= htmlspecialchars($page['title']) ?></div><?php endif; ?>
    <div class="two-col">
        <div class="col"><?php foreach(explode("\n",$page['content']??'') as $p): if(trim($p)): ?><p class="text"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?></div>
        <div class="col"><?php if($img2): ?><img src="<?= $img2 ?>" style="width:100%;height:180px;object-fit:cover" alt=""><?php else: ?><div class="img-placeholder" style="width:100%;height:180px">IMAGEM</div><?php endif; ?></div>
    </div>
</div>

<?php elseif ($layout === 'internal_02'): ?>
<!-- PÁG INTERNA 02: Imagem grande esquerda + texto direita + título bold -->
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= str_pad($page['page_number'],2,'0',STR_PAD_LEFT) ?></div></div>
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
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= str_pad($page['page_number'],2,'0',STR_PAD_LEFT) ?></div></div>
    <div class="title-big"><?= htmlspecialchars($page['title'] ?? '') ?></div>
    <?php if($page['subtitle']??''): ?><div class="subtitle"><?= htmlspecialchars($page['subtitle']) ?></div><?php endif; ?>
    <?php foreach(explode("\n",$page['content']??'') as $p): if(trim($p)): ?><p class="text"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?>
    <div style="display:flex;gap:10px;margin-top:15px">
        <?php if($img1): ?><img src="<?= $img1 ?>" class="img-half" style="height:200px" alt=""><?php else: ?><div class="img-half img-placeholder" style="height:200px">IMAGEM</div><?php endif; ?>
        <?php if($img2): ?><img src="<?= $img2 ?>" class="img-half" style="height:200px" alt=""><?php else: ?><div class="img-half img-placeholder" style="height:200px">IMAGEM</div><?php endif; ?>
    </div>
    <?php if($page['caption']??''): ?><div class="caption" style="margin-top:8px"><?= htmlspecialchars($page['caption']) ?></div><?php endif; ?>
</div>

<?php elseif ($layout === 'internal_04'): ?>
<!-- PÁG INTERNA 04: Imagem full com overlay + título sobreposto -->
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= str_pad($page['page_number'],2,'0',STR_PAD_LEFT) ?></div></div>
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
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= str_pad($page['page_number'],2,'0',STR_PAD_LEFT) ?></div></div>
    <div style="display:flex;gap:10px;margin-bottom:10px">
        <?php if($img1): ?><img src="<?= $img1 ?>" class="img-half" style="height:200px" alt=""><?php else: ?><div class="img-half img-placeholder" style="height:200px">IMAGEM</div><?php endif; ?>
        <?php if($img2): ?><img src="<?= $img2 ?>" class="img-half" style="height:200px" alt=""><?php else: ?><div class="img-half img-placeholder" style="height:200px">IMAGEM</div><?php endif; ?>
    </div>
    <?php if($page['caption']??''): ?><div class="caption"><?= htmlspecialchars($page['caption']) ?></div><div class="caption-sub"><?= htmlspecialchars($page['subtitle']??'') ?></div><?php endif; ?>
    <div style="margin-top:15px"><div class="title-big" style="font-size:1.5rem"><?= htmlspecialchars($page['title'] ?? '') ?></div></div>
    <div class="two-col">
        <?php $cols = explode('|||', $page['content']??''); ?>
        <div class="col"><?php foreach(explode("\n",$cols[0]??'') as $p): if(trim($p)): ?><p class="text"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?></div>
        <div class="col"><?php foreach(explode("\n",$cols[1]??$cols[0]??'') as $p): if(trim($p)): ?><p class="text"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?></div>
    </div>
</div>

<?php elseif ($layout === 'internal_06'): ?>
<!-- PÁG INTERNA 06: Grid 4 imagens + texto lateral -->
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= str_pad($page['page_number'],2,'0',STR_PAD_LEFT) ?></div></div>
    <div class="img-grid" style="margin-bottom:15px">
        <?php if($img1): ?><img src="<?= $img1 ?>" alt=""><?php else: ?><div class="img-placeholder">IMAGEM 1</div><?php endif; ?>
        <?php if($img2): ?><img src="<?= $img2 ?>" alt=""><?php else: ?><div class="img-placeholder">IMAGEM 2</div><?php endif; ?>
        <?php if($img1): ?><img src="<?= $img1 ?>" alt=""><?php else: ?><div class="img-placeholder">IMAGEM 3</div><?php endif; ?>
        <?php if($img2): ?><img src="<?= $img2 ?>" alt=""><?php else: ?><div class="img-placeholder">IMAGEM 4</div><?php endif; ?>
    </div>
    <div class="two-col">
        <div class="col"></div>
        <div class="col"><?php foreach(explode("\n",$page['content']??'') as $p): if(trim($p)): ?><p class="text"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?></div>
    </div>
</div>

<?php elseif ($layout === 'internal_07'): ?>
<!-- PÁG INTERNA 07: Citação grande + imagem com texto -->
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= str_pad($page['page_number'],2,'0',STR_PAD_LEFT) ?></div></div>
    <div style="margin:30px 0 25px"><p style="font-size:1.6rem;font-weight:600;color:#111;line-height:1.3"><?= htmlspecialchars($page['title'] ?? '') ?></p></div>
    <div class="two-col">
        <div class="col"><?php if($img1): ?><img src="<?= $img1 ?>" style="width:100%;height:320px;object-fit:cover" alt=""><?php else: ?><div class="img-placeholder" style="width:100%;height:320px">IMAGEM</div><?php endif; ?></div>
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
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= str_pad($page['page_number'],2,'0',STR_PAD_LEFT) ?></div></div>
    <?php if($img1): ?><img src="<?= $img1 ?>" class="img-full" style="height:250px;margin-bottom:15px" alt=""><?php endif; ?>
    <?php if($page['title']): ?><div class="title-big"><?= htmlspecialchars($page['title']) ?></div><?php endif; ?>
    <?php foreach(explode("\n",$page['content']??'') as $p): if(trim($p)): ?><p class="text"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?>
</div>
<?php endif; ?>

<?php endforeach; ?>
</div>
</body>
</html>
