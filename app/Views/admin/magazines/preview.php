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
        .preview{max-width:595px;margin:0 auto;padding:0 10px}
        .page{background:#fff;width:595px;min-height:842px;margin:0 auto 25px;position:relative;overflow:visible;box-shadow:0 8px 40px rgba(0,0,0,0.4);page-break-before:always;page-break-inside:avoid}
        @media(max-width:620px){
            .page{width:100%;height:auto;min-height:500px;aspect-ratio:595/842}
            .pg-cover .title{font-size:3rem}
            .pg-cover .topic{font-size:1rem;margin-bottom:50px}
            .pg-cover .logo{max-width:150px}
            .pg-int{padding:20px;height:auto;overflow:visible}
            .title-big{font-size:1.8rem}
            .two-col{flex-direction:column}
            .img-half{width:100%}
            .overlay-section{height:250px}
        }

        /* ===== CAPA ===== */
        .pg-cover{display:flex;flex-direction:column;align-items:center;padding:0;background:#1a472a;height:842px;overflow:hidden}
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
        .pg-int{padding:30px 35px;min-height:842px;overflow:visible}
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
        .pg-back{background:#1a3a2a;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;height:842px;overflow:hidden}
        .pg-back .logo{max-width:250px;margin-bottom:35px}
        .pg-back .txt{color:rgba(255,255,255,0.85);font-size:0.9rem;max-width:380px;line-height:1.6}
        .pg-back .bar{position:absolute;bottom:0;left:0;right:0;background:#e53935;padding:12px 25px;display:flex;justify-content:space-between;font-size:0.6rem;color:#fff}

        /* Placeholder para imagens não carregadas */
        .img-placeholder{background:linear-gradient(135deg,#e3f0e8,#b8d4c8);display:flex;align-items:center;justify-content:center;color:#2e7d32;font-size:0.6rem;text-transform:uppercase;letter-spacing:1px}

        /* ===== COLUNA DO CONVIDADO ===== */
        .pg-guest{padding:40px 40px;height:auto;min-height:842px;overflow:visible}
        .pg-guest .hdr{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:25px}
        .pg-guest .logo-sm{font-weight:800;font-size:0.9rem;color:#111;line-height:1}
        .pg-guest .logo-sm .ck{color:#2e7d32}
        .pg-guest .logo-sm small{display:block;font-size:0.4rem;font-weight:400;letter-spacing:2px;color:#666}
        .pg-guest .pn{font-size:1rem;font-weight:300;color:#333}
        .pg-guest .column-label{font-size:0.65rem;text-transform:uppercase;letter-spacing:3px;color:#2e7d32;font-weight:600;margin-bottom:8px}
        .pg-guest .column-title{font-family:'Montserrat',sans-serif;font-size:2.2rem;font-weight:900;color:#111;line-height:1.1;margin-bottom:20px}
        .pg-guest .author-box{display:flex;align-items:center;gap:15px;margin-bottom:25px;padding-bottom:20px;border-bottom:2px solid #2e7d32}
        .pg-guest .author-photo{width:70px;height:70px;border-radius:50%;object-fit:cover;border:3px solid #2e7d32}
        .pg-guest .author-photo-placeholder{width:70px;height:70px;border-radius:50%;background:linear-gradient(135deg,#e3f0e8,#b8d4c8);display:flex;align-items:center;justify-content:center;border:3px solid #2e7d32;color:#2e7d32;font-size:0.5rem;text-transform:uppercase}
        .pg-guest .author-info .author-name{font-weight:700;font-size:1rem;color:#111}
        .pg-guest .author-info .author-role{font-size:0.75rem;color:#666;font-style:italic}
        .pg-guest .column-content p{font-size:0.82rem;line-height:1.9;color:#333;margin-bottom:14px;text-align:justify}

        /* ===== CAUSOS DE OBRA ===== */
        .pg-stories{padding:35px 40px;height:auto;min-height:842px;overflow:visible}
        .pg-stories .hdr{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px}
        .pg-stories .logo-sm{font-weight:800;font-size:0.9rem;color:#111;line-height:1}
        .pg-stories .logo-sm .ck{color:#2e7d32}
        .pg-stories .logo-sm small{display:block;font-size:0.4rem;font-weight:400;letter-spacing:2px;color:#666}
        .pg-stories .pn{font-size:1rem;font-weight:300;color:#333}
        .pg-stories .stories-label{font-size:0.6rem;text-transform:uppercase;letter-spacing:3px;color:#2e7d32;font-weight:600;margin-bottom:6px}
        .pg-stories .stories-title{font-family:'Montserrat',sans-serif;font-size:2rem;font-weight:900;color:#111;line-height:1.1;margin-bottom:8px}
        .pg-stories .stories-subtitle{font-size:0.75rem;color:#666;font-style:italic;margin-bottom:20px;padding-bottom:15px;border-bottom:2px solid #2e7d32}
        .pg-stories .story-item{margin-bottom:18px;padding-left:15px;border-left:3px solid #2e7d32}
        .pg-stories .story-item .story-title{font-weight:700;font-size:0.82rem;color:#111;margin-bottom:4px}
        .pg-stories .story-item .story-text{font-size:0.75rem;line-height:1.7;color:#444;text-align:justify}
    </style>
</head>
<body>
<div class="preview">
<?php foreach ($pages as $page):
    $img1 = $page['image_url'] ?? '';
    $img2 = $page['image_url_2'] ?? '';
    $showImages = ($page['show_images'] ?? '1') !== '0';
    $layout = $page['layout_type'] ?? 'internal_01';
    // guest_column e construction_stories sempre mostram imagens (foto do autor, etc.)
    if (!$showImages && !in_array($layout, ['guest_column', 'construction_stories'])) { $img1 = ''; $img2 = ''; }
    $hideImagesClass = !$showImages ? ' style="display:none"' : '';
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
        <div style="display:flex;align-items:center;gap:10px;justify-content:center;margin-top:20px;flex-wrap:wrap-reverse;padding:0 20px;">
            <span style="font-size:3.5rem;font-weight:900;color:#fff;text-align:center;"><?= htmlspecialchars($page['title'] ?? 'ECO') ?></span>
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

<?php elseif ($layout === 'guest_column'): ?>
<!-- COLUNA DO CONVIDADO -->
<div class="page pg-guest">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <div class="column-label"><?= htmlspecialchars($page['caption'] ?? 'Coluna do Convidado') ?></div>
    <div class="author-box">
        <?php if($img1): ?>
            <img src="<?= $img1 ?>" class="author-photo" alt="<?= htmlspecialchars($page['title'] ?? '') ?>">
        <?php else: ?>
            <div class="author-photo-placeholder">FOTO</div>
        <?php endif; ?>
        <div class="author-info">
            <div class="author-name"><?= htmlspecialchars($page['title'] ?? 'Nome do Convidado') ?></div>
            <div class="author-role"><?= htmlspecialchars($page['subtitle'] ?? 'Cargo / Empresa') ?></div>
        </div>
    </div>
    <div class="column-content">
        <?php foreach(explode("\n", $page['content'] ?? '') as $p): if(trim($p)): ?>
            <p><?= htmlspecialchars(trim($p)) ?></p>
        <?php endif; endforeach; ?>
    </div>
</div>

<?php elseif ($layout === 'internal_01'): ?>
<!-- PÁG INTERNA 01: Imagem full topo + texto 2 colunas com imagem -->
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <?php if ($img1): ?><img src="<?= $img1 ?>" class="img-full" style="height:300px;margin-bottom:18px" alt=""><?php elseif ($showImages): ?><div class="img-full img-placeholder" style="height:300px;margin-bottom:18px">IMAGEM</div><?php endif; ?>
    <?php if ($page['title']): ?><div class="title-upper"><?= htmlspecialchars($page['title']) ?></div><?php endif; ?>
    <div class="two-col">
        <div class="col"><?php foreach(explode("\n",$page['content']??'') as $p): if(trim($p)): ?><p class="text"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?></div>
        <div class="col"><?php if($img2): ?><img src="<?= $img2 ?>" style="width:100%;height:280px;object-fit:cover" alt=""><?php elseif($showImages): ?><div class="img-placeholder" style="width:100%;height:280px">IMAGEM</div><?php endif; ?></div>
    </div>
</div>

<?php elseif ($layout === 'internal_02'): ?>
<!-- PÁG INTERNA 02: Imagem grande esquerda + texto direita + título bold -->
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <div class="two-col" style="margin-bottom:15px">
        <div class="col"><?php if($img1): ?><img src="<?= $img1 ?>" style="width:100%;height:250px;object-fit:cover" alt=""><?php elseif($showImages): ?><div class="img-placeholder" style="width:100%;height:250px">IMAGEM</div><?php endif; ?></div>
        <div class="col">
            <?php if($page['title']): ?><div class="title-upper" style="margin-top:10px"><?= htmlspecialchars($page['title']) ?></div><?php endif; ?>
            <p class="text-sm"><?= htmlspecialchars($page['subtitle'] ?? '') ?></p>
        </div>
    </div>
    <div class="title-big"><?= htmlspecialchars($page['title'] ?? '') ?></div>
    <div class="two-col">
        <div class="col"><?php foreach(explode("\n",$page['content']??'') as $p): if(trim($p)): ?><p class="text"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?></div>
        <div class="col"><?php if($img2): ?><img src="<?= $img2 ?>" style="width:100%;height:150px;object-fit:cover" alt=""><?php elseif($showImages): ?><div class="img-placeholder" style="width:100%;height:150px">IMAGEM</div><?php endif; ?></div>
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
        <?php if($img1): ?><img src="<?= $img1 ?>" class="img-half" style="height:260px" alt=""><?php elseif($showImages): ?><div class="img-half img-placeholder" style="height:260px">IMAGEM</div><?php endif; ?>
        <?php if($img2): ?><img src="<?= $img2 ?>" class="img-half" style="height:260px" alt=""><?php elseif($showImages): ?><div class="img-half img-placeholder" style="height:260px">IMAGEM</div><?php endif; ?>
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
        <?php if($img1): ?><img src="<?= $img1 ?>" class="img-half" style="height:260px" alt=""><?php elseif($showImages): ?><div class="img-half img-placeholder" style="height:260px">IMAGEM</div><?php endif; ?>
        <?php if($img2): ?><img src="<?= $img2 ?>" class="img-half" style="height:260px" alt=""><?php elseif($showImages): ?><div class="img-half img-placeholder" style="height:260px">IMAGEM</div><?php endif; ?>
    </div>
    <?php if($page['caption']??''): ?><div class="caption"><?= htmlspecialchars($page['caption']) ?></div><div class="caption-sub"><?= htmlspecialchars($page['subtitle']??'') ?></div><?php endif; ?>
    <div style="margin-top:15px"><div class="title-big" style="font-size:1.8rem"><?= htmlspecialchars($page['title'] ?? '') ?></div></div>
    <div class="two-col">
        <?php $cols = explode('|||', $page['content']??''); if(count($cols) < 2) { $lines = explode("\n", $cols[0] ?? ''); $mid = (int)ceil(count($lines)/2); $cols = [implode("\n", array_slice($lines, 0, $mid)), implode("\n", array_slice($lines, $mid))]; } ?>
        <div class="col"><?php foreach(explode("\n",$cols[0]??'') as $p): if(trim($p)): ?><p class="text"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?></div>
        <div class="col"><?php foreach(explode("\n",$cols[1]??'') as $p): if(trim($p)): ?><p class="text"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?></div>
    </div>
</div>

<?php elseif ($layout === 'internal_06'): ?>
<!-- PÁG INTERNA 06 v2: img3 esquerda + texto direita, depois 2 colunas -->
<?php
    $allLines06 = array_values(array_filter(explode("\n", $page['content'] ?? ''), function($l){ return trim($l) !== ''; }));
    // Texto ao lado da img3 (coluna direita superior)
    $sideLines = array_slice($allLines06, 0, min(3, count($allLines06)));
    // Texto embaixo em 2 colunas: esq continua, dir finaliza
    $bottomLines = array_slice($allLines06, count($sideLines));
    $bottomMid = (int)ceil(count($bottomLines) / 2);
?>
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <div style="display:flex;gap:8px;margin-bottom:8px">
        <?php if($img1): ?><img src="<?= $img1 ?>" style="width:50%;height:220px;object-fit:cover" alt=""><?php else: ?><div class="img-placeholder" style="width:50%;height:220px">IMAGEM 1</div><?php endif; ?>
        <?php if($img2): ?><img src="<?= $img2 ?>" style="width:50%;height:220px;object-fit:cover" alt=""><?php else: ?><div class="img-placeholder" style="width:50%;height:220px">IMAGEM 2</div><?php endif; ?>
    </div>
    <div style="display:flex;gap:8px;align-items:flex-start;margin-bottom:10px">
        <?php $img3 = $page['image_url_3'] ?? ''; if($img3): ?><img src="<?= $img3 ?>" style="width:50%;height:220px;object-fit:cover" alt=""><?php else: ?><div class="img-placeholder" style="width:50%;height:220px">IMAGEM 3</div><?php endif; ?>
        <div style="width:50%"><?php foreach($sideLines as $p): ?><p class="text-sm"><?= htmlspecialchars(trim($p)) ?></p><?php endforeach; ?></div>
    </div>
    <?php if (count($bottomLines) > 0): ?>
    <div class="two-col"><div class="col"><?php foreach(array_slice($bottomLines, 0, $bottomMid) as $p): ?><p class="text-sm"><?= htmlspecialchars(trim($p)) ?></p><?php endforeach; ?></div><div class="col"><?php foreach(array_slice($bottomLines, $bottomMid) as $p): ?><p class="text-sm"><?= htmlspecialchars(trim($p)) ?></p><?php endforeach; ?></div></div>
    <?php endif; ?>
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

<?php elseif ($layout === 'construction_stories'): ?>
<!-- CAUSOS DE OBRA -->
<div class="page pg-stories">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <div class="stories-label"><?= htmlspecialchars($page['caption'] ?? 'Histórias da Obra') ?></div>
    <div class="stories-title"><?= htmlspecialchars($page['title'] ?? 'Causos de Obra') ?></div>
    <div class="stories-subtitle"><?= htmlspecialchars($page['subtitle'] ?? 'Histórias reais (ou quase) dos bastidores da construção') ?></div>
    <?php
    // Causos são armazenados no content separados por "|||" (separador entre causos)
    // Cada causo tem formato: "TITULO_DO_CAUSO\nTexto do causo"
    $stories = array_filter(explode('|||', $page['content'] ?? ''));
    foreach ($stories as $story):
        $storyLines = explode("\n", trim($story), 2);
        $storyTitle = trim($storyLines[0] ?? '');
        $storyText = trim($storyLines[1] ?? '');
        if (empty($storyTitle) && empty($storyText)) continue;
    ?>
    <div class="story-item">
        <?php if ($storyTitle): ?><div class="story-title"><?= htmlspecialchars($storyTitle) ?></div><?php endif; ?>
        <?php if ($storyText): ?><div class="story-text"><?= htmlspecialchars($storyText) ?></div><?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<?php elseif ($layout === 'backcover'): ?>
<!-- PÁGINA DE FONTES (antes da contracapa) -->
<?php
$sources = \App\Core\Database::fetchAll("SELECT * FROM magazine_sources WHERE magazine_id = ? ORDER BY sort_order", [$magazine['id']]);
if (!empty($sources)):
?>
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <div style="margin-top:30px">
        <div class="title-big" style="font-size:1.8rem; margin-bottom:5px;">Fontes e Referências</div>
        <div style="width:60px; height:3px; background:#2e7d32; margin-bottom:25px;"></div>
        <p class="text" style="margin-bottom:20px; color:#666;">As informações e dados apresentados nesta edição foram baseados nas seguintes fontes:</p>
        <?php foreach ($sources as $i => $src): ?>
        <div style="padding:8px 0; border-bottom:1px solid #eee; font-size:0.75rem;">
            <strong style="color:#111;"><?= ($i + 1) ?>. <?= htmlspecialchars($src['title']) ?></strong>
            <?php if ($src['author']): ?><span style="color:#666;"> — <?= htmlspecialchars($src['author']) ?></span><?php endif; ?>
            <?php if ($src['url']): ?><br><span style="color:#2e7d32; font-size:0.65rem;"><?= htmlspecialchars($src['url']) ?></span><?php endif; ?>
            <?php if ($src['accessed_at']): ?><span style="color:#999; font-size:0.6rem; margin-left:5px;">Acesso em <?= date('d/m/Y', strtotime($src['accessed_at'])) ?></span><?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

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

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
function generatePDF() {
    var btn = document.getElementById('btn-pdf');
    btn.disabled = true;
    
    var toolbar = document.getElementById('pdf-toolbar');
    if (toolbar) toolbar.style.display = 'none';

    var pages = document.querySelectorAll('.page');
    var { jsPDF } = window.jspdf;
    var pdf = new jsPDF({ orientation: 'portrait', unit: 'pt', format: 'a4' });
    var pdfW = 595.28;
    var pdfH = 841.89;

    (async function() {
        for (var i = 0; i < pages.length; i++) {
            if (i > 0) pdf.addPage();
            var canvas = await html2canvas(pages[i], {
                scale: 3,
                useCORS: true,
                allowTaint: true,
                logging: false,
                imageTimeout: 30000,
                backgroundColor: null
            });
            pdf.addImage(canvas.toDataURL('image/jpeg', 0.95), 'JPEG', 0, 0, pdfW, pdfH);
        }

        pdf.save('Revista_Brooks_<?= preg_replace('/[^a-zA-Z0-9]/', '_', $magazine['title'] ?? 'Construtora') ?>.pdf');
        if (toolbar) toolbar.style.display = 'flex';
        btn.disabled = false;
    })();
}

// Sistema de paginação e ajuste de páginas
document.addEventListener('DOMContentLoaded', function() {
    var PAGE_HEIGHT = 842;
    var PAGE_PADDING = 60; // top + bottom padding aproximado
    var MAX_CONTENT = PAGE_HEIGHT - PAGE_PADDING;

    function processPages() {
        var allPages = Array.from(document.querySelectorAll('.preview .page'));
        
        allPages.forEach(function(page) {
            // Ignora capas e contracapas
            if (page.classList.contains('pg-cover') || page.classList.contains('pg-back')) {
                page.style.height = PAGE_HEIGHT + 'px';
                page.style.overflow = 'hidden';
                return;
            }

            // Se a página cabe, apenas fixa a altura
            if (page.scrollHeight <= PAGE_HEIGHT + 5) {
                page.style.height = PAGE_HEIGHT + 'px';
                page.style.overflow = 'hidden';
                return;
            }

            // Só pagina guest_column e construction_stories — demais reduzem fonte
            var shouldPaginate = page.classList.contains('pg-guest') || page.classList.contains('pg-stories');
            if (shouldPaginate) {
                paginatePage(page);
            } else {
                // Reduz font-size dos textos gradualmente para caber
                var attempts = 0;
                while (page.scrollHeight > PAGE_HEIGHT + 2 && attempts < 12) {
                    var textEls = page.querySelectorAll('.text, .text-sm, .title-upper, .title-big, .subtitle, .caption, p');
                    textEls.forEach(function(el) {
                        var cur = parseFloat(window.getComputedStyle(el).fontSize);
                        el.style.fontSize = (cur - 0.3) + 'px';
                    });
                    // Também reduz imagens levemente
                    if (attempts > 5) {
                        var imgs = page.querySelectorAll('img, .img-placeholder');
                        imgs.forEach(function(img) {
                            var curH = img.offsetHeight;
                            if (curH > 100) img.style.height = (curH - 15) + 'px';
                        });
                    }
                    attempts++;
                }
                page.style.height = PAGE_HEIGHT + 'px';
                page.style.overflow = 'hidden';
            }
        });

        // Após paginação, renumera todas as páginas internas
        renumberPages();
    }

    function paginatePage(page) {
        var header = page.querySelector('.hdr');
        var headerHTML = header ? header.outerHTML : '';
        var pageClass = page.className;
        var headerHeight = header ? header.offsetHeight + 20 : 0;

        // Coleta elementos de conteúdo (exceto header)
        var contentElements = [];
        Array.from(page.children).forEach(function(child) {
            if (!child.classList.contains('hdr')) {
                contentElements.push(child);
            }
        });

        // Determina o que cabe na primeira página
        var currentHeight = headerHeight;
        var splitIndex = contentElements.length; // por padrão tudo cabe

        for (var i = 0; i < contentElements.length; i++) {
            var el = contentElements[i];
            var style = window.getComputedStyle(el);
            var elH = el.offsetHeight + parseInt(style.marginTop || 0) + parseInt(style.marginBottom || 0);

            if (currentHeight + elH > MAX_CONTENT) {
                // Tenta dividir containers com múltiplos filhos
                var innerKids = Array.from(el.children);
                if (innerKids.length > 1) {
                    var cloneBefore = el.cloneNode(false);
                    var cloneAfter = el.cloneNode(false);
                    var splitInner = false;
                    var innerH = currentHeight;

                    for (var j = 0; j < innerKids.length; j++) {
                        var kidH = innerKids[j].offsetHeight + 14;
                        if (!splitInner && innerH + kidH > MAX_CONTENT) {
                            splitInner = true;
                        }
                        if (splitInner) {
                            cloneAfter.appendChild(innerKids[j].cloneNode(true));
                        } else {
                            innerH += kidH;
                            cloneBefore.appendChild(innerKids[j].cloneNode(true));
                        }
                    }

                    // Substitui o elemento original pelo que cabe
                    if (cloneBefore.children.length > 0) {
                        el.parentNode.insertBefore(cloneBefore, el);
                    }
                    el.parentNode.removeChild(el);
                    contentElements.splice(i, 1, cloneBefore);

                    // O resto vai para overflow
                    splitIndex = i + 1;
                    // Insere cloneAfter como próximo elemento
                    contentElements.splice(splitIndex, 0, cloneAfter);
                } else {
                    splitIndex = i;
                }
                break;
            }
            currentHeight += elH;
        }

        if (splitIndex >= contentElements.length) {
            // Tudo coube — fixa a página
            page.style.height = PAGE_HEIGHT + 'px';
            page.style.overflow = 'hidden';
            return;
        }

        // Separa: o que fica na página atual vs overflow
        var overflowEls = contentElements.slice(splitIndex);

        // Limpa e reconstrói a página atual
        while (page.firstChild) page.removeChild(page.firstChild);
        if (header) page.appendChild(header);
        for (var k = 0; k < splitIndex; k++) {
            page.appendChild(contentElements[k]);
        }
        page.style.height = PAGE_HEIGHT + 'px';
        page.style.overflow = 'hidden';

        // Cria páginas de continuação
        var remaining = overflowEls;
        var prevPage = page;

        while (remaining.length > 0) {
            var newPage = document.createElement('div');
            newPage.className = pageClass;
            newPage.setAttribute('data-continuation', 'true');
            newPage.innerHTML = headerHTML;
            prevPage.parentNode.insertBefore(newPage, prevPage.nextSibling);

            // Adiciona elementos até encher
            var newH = headerHTML ? 50 : 0;
            var fitted = 0;
            var didBreak = false;
            for (var m = 0; m < remaining.length; m++) {
                newPage.appendChild(remaining[m]);
                var mH = remaining[m].offsetHeight + 14;

                if (newH + mH > MAX_CONTENT) {
                    // Elemento não cabe — tenta dividir seus filhos
                    // Mede filhos ENQUANTO no DOM
                    var innerC = Array.from(remaining[m].children);
                    if (innerC.length > 1) {
                        var childHeights = [];
                        for (var ci = 0; ci < innerC.length; ci++) {
                            childHeights.push(innerC[ci].offsetHeight + 14);
                        }
                        newPage.removeChild(remaining[m]);
                        var fitClone = remaining[m].cloneNode(false);
                        var restClone = remaining[m].cloneNode(false);
                        var innerFitH = newH;
                        var addedSome = false;
                        for (var n = 0; n < innerC.length; n++) {
                            if (innerFitH + childHeights[n] > MAX_CONTENT && addedSome) {
                                for (var o = n; o < innerC.length; o++) {
                                    restClone.appendChild(innerC[o].cloneNode(true));
                                }
                                break;
                            }
                            innerFitH += childHeights[n];
                            fitClone.appendChild(innerC[n].cloneNode(true));
                            addedSome = true;
                        }
                        if (fitClone.children.length > 0) newPage.appendChild(fitClone);
                        if (restClone.children.length > 0) {
                            remaining = [restClone].concat(remaining.slice(m + 1));
                        } else {
                            remaining = remaining.slice(m + 1);
                        }
                    } else if (fitted > 0) {
                        // Não tem filhos para dividir e já tem algo na página
                        newPage.removeChild(remaining[m]);
                        remaining = remaining.slice(m);
                    } else {
                        // Elemento atômico que não cabe sozinho — aceita cortado
                        remaining = remaining.slice(m + 1);
                    }
                    didBreak = true;
                    break;
                }
                newH += mH;
                fitted++;
            }
            if (!didBreak) {
                remaining = [];
            }

            newPage.style.height = PAGE_HEIGHT + 'px';
            newPage.style.overflow = 'hidden';
            prevPage = newPage;
        }
    }

    function renumberPages() {
        var allPages = Array.from(document.querySelectorAll('.preview .page'));
        var pageNum = 0;

        allPages.forEach(function(page) {
            if (page.classList.contains('pg-cover') || page.classList.contains('pg-back')) return;

            pageNum++;
            var pnEl = page.querySelector('.pn');
            if (pnEl) {
                pnEl.textContent = pageNum < 10 ? '0' + pageNum : '' + pageNum;
            }
        });
    }

    // Aguarda imagens carregarem
    var images = document.querySelectorAll('.preview img');
    var loaded = 0;
    var total = images.length;

    function check() {
        loaded++;
        if (loaded >= total) processPages();
    }

    if (total === 0) {
        processPages();
    } else {
        images.forEach(function(img) {
            if (img.complete) check();
            else {
                img.addEventListener('load', check);
                img.addEventListener('error', check);
            }
        });
        setTimeout(processPages, 3000);
    }
});
</script>

</body>
</html>