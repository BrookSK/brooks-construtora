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
        <button onclick="generatePDF()" id="btn-pdf" class="btn btn--primary btn--sm">
            <i data-lucide="download" style="width:16px;height:16px;"></i> Baixar PDF
        </button>
    </div>
</section>

<!-- Magazine Content -->
<section style="padding: var(--space-2xl) 0 var(--space-4xl);">

<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
<style>
.mag-preview{max-width:595px;margin:0 auto;padding:0 10px}
.mag-preview .page{background:#fff;width:100%;max-width:595px;min-height:842px;aspect-ratio:unset;margin:0 auto 25px;position:relative;overflow:visible;box-shadow:0 8px 30px rgba(0,0,0,0.12);border-radius:4px;page-break-before:always}
.mag-preview .pg-cover{display:flex;flex-direction:column;align-items:center;padding:0;background:#1a472a;height:842px}
.mag-preview .pg-cover .bg{position:absolute;top:0;left:0;right:0;bottom:0;object-fit:cover;width:100%;height:100%}
.mag-preview .pg-cover .overlay{position:absolute;top:0;left:0;right:0;bottom:0;background:linear-gradient(180deg,rgba(0,0,0,0.4) 0%,rgba(0,0,0,0.05) 35%,rgba(0,0,0,0.05) 50%,rgba(0,0,0,0.4) 70%,rgba(0,0,0,0.8) 90%,rgba(0,0,0,0.92) 100%)}
.mag-preview .pg-cover .content{position:relative;z-index:2;text-align:center;width:100%;height:100%;display:flex;flex-direction:column;padding:30px 40px}
.mag-preview .pg-cover .title{font-size:min(5rem, 11vw);font-weight:900;color:#fff;text-transform:uppercase;letter-spacing:min(2px, 0.3vw);margin-top:10px;text-shadow:0 2px 10px rgba(0,0,0,0.3)}
.mag-preview .pg-cover .sub-line{display:flex;align-items:center;justify-content:center;gap:15px;margin-top:5px;font-size:0.7rem;letter-spacing:5px;text-transform:uppercase;color:rgba(255,255,255,0.9)}
.mag-preview .pg-cover .sub-line .ln{width:40px;height:2px;background:#fff}
.mag-preview .pg-cover .logo{margin:auto;max-width:220px;filter:drop-shadow(0 4px 20px rgba(0,0,0,0.7))}
.mag-preview .pg-cover .topic{font-size:1.4rem;font-weight:800;color:#fff;font-style:italic;text-align:left;padding:15px 30px;margin-top:auto;margin-bottom:70px;text-shadow:0 2px 10px rgba(0,0,0,0.8)}
.mag-preview .pg-cover .foot{position:absolute;bottom:15px;left:25px;right:25px;display:flex;justify-content:space-between;font-size:0.55rem;color:rgba(255,255,255,0.8)}
.mag-preview .pg-int{padding:30px 35px;min-height:842px;overflow:visible}
.mag-preview .pg-int .hdr{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px}
.mag-preview .pg-int .logo-sm{font-weight:800;font-size:0.9rem;color:#111;line-height:1}
.mag-preview .pg-int .logo-sm .ck{color:#2e7d32}
.mag-preview .pg-int .logo-sm small{display:block;font-size:0.4rem;font-weight:400;letter-spacing:2px;color:#666}
.mag-preview .pg-int .pn{font-size:1rem;font-weight:300;color:#333}
.mag-preview .img-full{width:100%;object-fit:cover}
.mag-preview .img-half{width:48%;object-fit:cover}
.mag-preview .title-big{font-family:'Montserrat',sans-serif;font-size:min(2.8rem, 7vw);font-weight:900;color:#111;margin-bottom:15px;line-height:1.1}
.mag-preview .title-upper{font-size:0.7rem;text-transform:uppercase;letter-spacing:1.5px;font-weight:600;color:#111;margin-bottom:18px;border-bottom:1px solid #ddd;padding-bottom:10px}
.mag-preview .subtitle{font-size:1.1rem;font-weight:400;color:#333;margin-bottom:18px}
.mag-preview .text{font-size:0.78rem;line-height:1.8;color:#333;margin-bottom:12px;text-align:justify}
.mag-preview .text-sm{font-size:0.78rem;line-height:1.8;color:#333;margin-bottom:12px;text-align:justify}
.mag-preview .caption{font-size:0.78rem;font-weight:700;color:#111;margin-top:10px}
.mag-preview .two-col{display:flex;gap:18px}
.mag-preview .two-col .col{flex:1}
.mag-preview .overlay-section{position:relative;width:100%;height:420px;overflow:hidden;margin-bottom:15px}
.mag-preview .overlay-section img{width:100%;height:100%;object-fit:cover}
.mag-preview .overlay-section .ov{position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,50,0,0.95));padding:25px 30px 20px}
.mag-preview .overlay-section .ov h2{font-family:'Montserrat',sans-serif;font-size:2rem;font-weight:900;color:#fff}
.mag-preview .overlay-section .ov p{font-size:0.6rem;text-transform:uppercase;letter-spacing:1px;color:rgba(255,255,255,0.8);margin-top:5px}
.mag-preview .pg-back{background:#1a3a2a;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;height:842px}
.mag-preview .pg-back .logo{max-width:250px;margin-bottom:35px}
.mag-preview .pg-back .txt{color:rgba(255,255,255,0.85);font-size:0.9rem;max-width:380px;line-height:1.6}
.mag-preview .pg-back .bar{position:absolute;bottom:0;left:0;right:0;background:#e53935;padding:12px 25px;display:flex;justify-content:space-between;font-size:0.6rem;color:#fff}
.mag-preview .img-placeholder{background:linear-gradient(135deg,#e3f0e8,#b8d4c8);display:flex;align-items:center;justify-content:center;color:#2e7d32;font-size:0.6rem;text-transform:uppercase;letter-spacing:1px}
.mag-preview .pg-stories{padding:35px 40px;height:auto;min-height:842px;overflow:visible}
.mag-preview .pg-stories .hdr{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px}
.mag-preview .pg-stories .logo-sm{font-weight:800;font-size:0.9rem;color:#111;line-height:1}
.mag-preview .pg-stories .logo-sm .ck{color:#2e7d32}
.mag-preview .pg-stories .logo-sm small{display:block;font-size:0.4rem;font-weight:400;letter-spacing:2px;color:#666}
.mag-preview .pg-stories .pn{font-size:1rem;font-weight:300;color:#333}
.mag-preview .pg-stories .stories-label{font-size:0.6rem;text-transform:uppercase;letter-spacing:3px;color:#2e7d32;font-weight:600;margin-bottom:6px}
.mag-preview .pg-stories .stories-title{font-family:'Montserrat',sans-serif;font-size:2rem;font-weight:900;color:#111;line-height:1.1;margin-bottom:8px}
.mag-preview .pg-stories .stories-subtitle{font-size:0.75rem;color:#666;font-style:italic;margin-bottom:20px;padding-bottom:15px;border-bottom:2px solid #2e7d32}
.mag-preview .pg-stories .story-item{margin-bottom:18px;padding-left:15px;border-left:3px solid #2e7d32}
.mag-preview .pg-stories .story-item .story-title{font-weight:700;font-size:0.82rem;color:#111;margin-bottom:4px}
.mag-preview .pg-stories .story-item .story-text{font-size:0.75rem;line-height:1.7;color:#444;text-align:justify}
.mag-preview .pg-guest{padding:40px 40px;height:auto;min-height:842px;overflow:visible}
.mag-preview .pg-guest .hdr{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px}
.mag-preview .pg-guest .logo-sm{font-weight:800;font-size:0.9rem;color:#111;line-height:1}
.mag-preview .pg-guest .logo-sm .ck{color:#2e7d32}
.mag-preview .pg-guest .logo-sm small{display:block;font-size:0.4rem;font-weight:400;letter-spacing:2px;color:#666}
.mag-preview .pg-guest .pn{font-size:1rem;font-weight:300;color:#333}
#pdf-loading{display:none;text-align:center;margin:30px 0 50px;}
#pdf-loading .spinner{display:inline-block;width:24px;height:24px;border:3px solid #ddd;border-top-color:var(--brooks-navy);border-radius:50%;animation:pdfspin 0.8s linear infinite;margin-bottom:10px;}
@keyframes pdfspin{to{transform:rotate(360deg)}}
#pdf-loading p{margin:0;font-size:13px;color:#555;font-weight:500;}
@media(max-width:620px){
    .mag-preview .page{height:auto;min-height:500px}
    .mag-preview .pg-cover{height:auto;overflow:visible}
    .mag-preview .pg-cover .content{padding:20px 15px}
    .mag-preview .pg-cover .title{font-size:min(2.5rem, 11vw);letter-spacing:0}
    .mag-preview .pg-cover .topic{font-size:1rem;margin-bottom:50px}
    .mag-preview .pg-cover .logo{max-width:150px}
    .mag-preview .pg-int{padding:20px 15px;height:auto;overflow:visible}
    .mag-preview .pg-guest{padding:20px 15px;height:auto;overflow:visible}
    .mag-preview .pg-stories{padding:20px 15px;height:auto;overflow:visible}
    .mag-preview .pg-back{height:auto;min-height:500px}
    .mag-preview .title-big{font-size:min(1.4rem, 7vw)}
    .mag-preview .title-upper{font-size:0.55rem}
    .mag-preview .text,.mag-preview .text-sm{font-size:0.65rem}
    .mag-preview .two-col{flex-direction:column}
    .mag-preview .img-half{width:100%}
    .mag-preview .overlay-section{height:280px}
    .mag-preview .pg-back .logo{max-width:180px}
}
</style>

<div id="pdf-loading">
    <div class="spinner"></div>
    <p>Gerando PDF, aguarde...</p>
</div>

<div class="mag-preview">
<?php
$intNum = 0;
foreach ($pages as $page):
    $img1 = $page['image_url'] ?? '';
    $img2 = $page['image_url_2'] ?? '';
    $showImages = ($page['show_images'] ?? '1') !== '0';
    $layout = $page['layout_type'] ?? 'internal_01';
    // guest_column e construction_stories sempre mostram imagens
    if (!$showImages && !in_array($layout, ['guest_column', 'construction_stories'])) { $img1 = ''; $img2 = ''; }
    if (!in_array($layout, ['cover','subcover','backcover'])) $intNum++;
    $displayPageNum = str_pad($intNum, 2, '0', STR_PAD_LEFT);
?>

<?php if ($layout === 'cover'): ?>
<div class="page pg-cover" <?php if ($magazine['cover_image']): ?>style="background-image:url('<?= $magazine['cover_image'] ?>');background-size:cover;background-position:center;"<?php endif; ?>>
    <div class="overlay"></div>
    <div class="content">
        <div class="title"><?= htmlspecialchars($page['title'] ?? $magazine['title']) ?></div>
        <div class="sub-line"><span><?= htmlspecialchars(explode('—', $page['subtitle'] ?? 'CONSTRUÇÃO — SUSTENTÁVEL')[0] ?? 'CONSTRUÇÃO') ?></span><span class="ln"></span><span><?= htmlspecialchars(trim(explode('—', $page['subtitle'] ?? 'CONSTRUÇÃO — SUSTENTÁVEL')[1] ?? 'SUSTENTÁVEL')) ?></span></div>
        <img src="<?= $magazineLogo ?>" class="logo" alt="Brooks">
        <div class="topic"><?= htmlspecialchars($magazine['subtitle'] ?? '') ?></div>
        <div class="foot"><span>&copy; <?= $year ?> BROOKS CONSTRUTORA. TODOS OS DIREITOS RESERVADOS.</span><span><?= $siteUrl ?></span></div>
    </div>
</div>

<?php elseif ($layout === 'subcover'): ?>
<div class="page pg-cover" <?php if ($magazine['cover_image']): ?>style="background-image:url('<?= $magazine['cover_image'] ?>');background-size:cover;background-position:center;"<?php endif; ?>>
    <div class="overlay"></div>
    <div class="content">
        <div style="display:flex;align-items:center;gap:10px;justify-content:center;margin-top:20px;"><span style="font-size:3.5rem;font-weight:900;color:#fff;"><?= htmlspecialchars($page['title'] ?? 'ECO') ?></span><img src="<?= $magazineLogo ?>" style="max-width:180px" alt="Brooks"></div>
        <div class="sub-line" style="margin-top:12px"><span><?= htmlspecialchars(explode('—', $page['subtitle'] ?? 'CONSTRUÇÃO — CONSCIENTE')[0] ?? 'CONSTRUÇÃO') ?></span><span class="ln"></span><span><?= htmlspecialchars(trim(explode('—', $page['subtitle'] ?? 'CONSTRUÇÃO — CONSCIENTE')[1] ?? 'CONSCIENTE')) ?></span></div>
        <div style="flex:1"></div>
        <div class="topic"><?= htmlspecialchars($magazine['subtitle'] ?? '') ?></div>
        <div class="foot"><span>&copy; <?= $year ?> BROOKS CONSTRUTORA. TODOS OS DIREITOS RESERVADOS.</span><span><?= $siteUrl ?></span></div>
    </div>
</div>

<?php elseif ($layout === 'guest_column'): ?>
<div class="page pg-guest">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <div style="font-size:0.65rem;text-transform:uppercase;letter-spacing:3px;color:#2e7d32;font-weight:600;margin-bottom:8px;"><?= htmlspecialchars($page['caption'] ?? 'Coluna do Convidado') ?></div>
    <div style="display:flex;align-items:center;gap:15px;margin-bottom:25px;padding-bottom:20px;border-bottom:2px solid #2e7d32;">
        <?php if($img1): ?>
            <img src="<?= $img1 ?>" style="width:70px;height:70px;border-radius:50%;object-fit:cover;border:3px solid #2e7d32;" alt="<?= htmlspecialchars($page['title'] ?? '') ?>">
        <?php else: ?>
            <div style="width:70px;height:70px;border-radius:50%;background:linear-gradient(135deg,#e3f0e8,#b8d4c8);display:flex;align-items:center;justify-content:center;border:3px solid #2e7d32;color:#2e7d32;font-size:0.5rem;text-transform:uppercase;">FOTO</div>
        <?php endif; ?>
        <div>
            <div style="font-weight:700;font-size:1rem;color:#111;"><?= htmlspecialchars($page['title'] ?? 'Nome do Convidado') ?></div>
            <div style="font-size:0.75rem;color:#666;font-style:italic;"><?= htmlspecialchars($page['subtitle'] ?? 'Cargo / Empresa') ?></div>
        </div>
    </div>
    <div class="column-content"><?php foreach(explode("\n", $page['content'] ?? '') as $p): if(trim($p)): ?><p class="text" style="text-align:justify;line-height:1.9;"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?></div>
</div>

<?php elseif ($layout === 'internal_01'): ?>
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <?php if($img1): ?><img src="<?= $img1 ?>" class="img-full" style="height:300px;margin-bottom:18px" alt=""><?php endif; ?>
    <?php if($page['title']): ?><div class="title-upper"><?= htmlspecialchars($page['title']) ?></div><?php endif; ?>
    <div class="two-col"><div class="col"><?php foreach(explode("\n",$page['content']??'') as $p): if(trim($p)): ?><p class="text"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?></div><div class="col"><?php if($img2): ?><img src="<?= $img2 ?>" style="width:100%;height:280px;object-fit:cover" alt=""><?php endif; ?></div></div>
</div>

<?php elseif ($layout === 'internal_02'): ?>
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <div class="two-col" style="margin-bottom:15px"><div class="col"><?php if($img1): ?><img src="<?= $img1 ?>" style="width:100%;height:250px;object-fit:cover" alt=""><?php endif; ?></div><div class="col"><?php if($page['title']): ?><div class="title-upper" style="margin-top:10px"><?= htmlspecialchars($page['title']) ?></div><?php endif; ?><p class="text-sm"><?= htmlspecialchars($page['subtitle'] ?? '') ?></p></div></div>
    <div class="title-big"><?= htmlspecialchars($page['title'] ?? '') ?></div>
    <div class="two-col"><div class="col"><?php foreach(explode("\n",$page['content']??'') as $p): if(trim($p)): ?><p class="text"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?></div><div class="col"><?php if($img2): ?><img src="<?= $img2 ?>" style="width:100%;height:150px;object-fit:cover" alt=""><?php endif; ?></div></div>
</div>

<?php elseif ($layout === 'internal_03'): ?>
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <div class="title-big"><?= htmlspecialchars($page['title'] ?? '') ?></div>
    <?php if($page['subtitle']??''): ?><div class="subtitle"><?= htmlspecialchars($page['subtitle']) ?></div><?php endif; ?>
    <?php foreach(explode("\n",$page['content']??'') as $p): if(trim($p)): ?><p class="text"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?>
    <div style="display:flex;gap:10px;margin-top:15px"><?php if($img1): ?><img src="<?= $img1 ?>" class="img-half" style="height:260px" alt=""><?php endif; ?><?php if($img2): ?><img src="<?= $img2 ?>" class="img-half" style="height:260px" alt=""><?php endif; ?></div>
    <?php if($page['caption']??''): ?><div class="caption"><?= htmlspecialchars($page['caption']) ?></div><?php endif; ?>
</div>

<?php elseif ($layout === 'internal_04'): ?>
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <div class="overlay-section"><?php if($img1): ?><img src="<?= $img1 ?>" alt=""><?php endif; ?><div class="ov"><h2><?= htmlspecialchars($page['title'] ?? '') ?></h2><?php if($page['subtitle']??''): ?><p><?= htmlspecialchars($page['subtitle']) ?></p><?php endif; ?></div></div>
    <?php foreach(explode("\n",$page['content']??'') as $p): if(trim($p)): ?><p class="text"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?>
</div>

<?php elseif ($layout === 'internal_05'): ?>
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <div style="display:flex;gap:10px;margin-bottom:10px"><?php if($img1): ?><img src="<?= $img1 ?>" class="img-half" style="height:260px" alt=""><?php endif; ?><?php if($img2): ?><img src="<?= $img2 ?>" class="img-half" style="height:260px" alt=""><?php endif; ?></div>
    <?php if($page['caption']??''): ?><div class="caption"><?= htmlspecialchars($page['caption']) ?></div><?php endif; ?>
    <div style="margin-top:15px"><div class="title-big" style="font-size:1.8rem"><?= htmlspecialchars($page['title'] ?? '') ?></div></div>
    <div class="two-col"><?php $cols = explode('|||', $page['content']??''); if(count($cols) < 2) { $lines = explode("\n", $cols[0] ?? ''); $mid = (int)ceil(count($lines)/2); $cols = [implode("\n", array_slice($lines, 0, $mid)), implode("\n", array_slice($lines, $mid))]; } ?><div class="col"><?php foreach(explode("\n",$cols[0]??'') as $p): if(trim($p)): ?><p class="text"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?></div><div class="col"><?php foreach(explode("\n",$cols[1]??'') as $p): if(trim($p)): ?><p class="text"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?></div></div>
</div>

<?php elseif ($layout === 'internal_06'): ?>
<?php
    $allLines06 = array_values(array_filter(explode("\n", $page['content'] ?? ''), function($l){ return trim($l) !== ''; }));
    $sideLines = array_slice($allLines06, 0, min(5, count($allLines06)));
    $bottomLines = array_slice($allLines06, count($sideLines));
    $bottomMid = (int)ceil(count($bottomLines) / 2);
?>
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <div style="display:flex;gap:8px;margin-bottom:8px"><?php if($img1): ?><img src="<?= $img1 ?>" style="width:50%;height:220px;object-fit:cover" alt=""><?php endif; ?><?php if($img2): ?><img src="<?= $img2 ?>" style="width:50%;height:220px;object-fit:cover" alt=""><?php endif; ?></div>
    <div style="display:flex;gap:8px;align-items:flex-start;margin-bottom:10px"><?php $img3=$page['image_url_3']??''; if($img3): ?><img src="<?= $img3 ?>" style="width:50%;height:220px;object-fit:cover" alt=""><?php else: ?><div class="img-placeholder" style="width:50%;height:220px">IMAGEM</div><?php endif; ?><div style="width:50%"><?php foreach($sideLines as $p): ?><p class="text-sm"><?= htmlspecialchars(trim($p)) ?></p><?php endforeach; ?></div></div>
    <?php if (count($bottomLines) > 0): ?>
    <div class="two-col"><div class="col"><?php foreach(array_slice($bottomLines, 0, $bottomMid) as $p): ?><p class="text-sm"><?= htmlspecialchars(trim($p)) ?></p><?php endforeach; ?></div><div class="col"><?php foreach(array_slice($bottomLines, $bottomMid) as $p): ?><p class="text-sm"><?= htmlspecialchars(trim($p)) ?></p><?php endforeach; ?></div></div>
    <?php endif; ?>
</div>

<?php elseif ($layout === 'internal_07'): ?>
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <div style="margin:40px 0 30px"><p style="font-size:1.8rem;font-weight:600;color:#111;line-height:1.3"><?= htmlspecialchars($page['title'] ?? '') ?></p></div>
    <div class="two-col"><div class="col"><?php if($img1): ?><img src="<?= $img1 ?>" style="width:100%;height:420px;object-fit:cover" alt=""><?php endif; ?></div><div class="col"><?php foreach(explode("\n",$page['content']??'') as $p): if(trim($p)): ?><p class="text-sm"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?></div></div>
</div>

<?php elseif ($layout === 'construction_stories'): ?>
<div class="page pg-stories">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <div class="stories-label"><?= htmlspecialchars($page['caption'] ?? 'Histórias da Obra') ?></div>
    <div class="stories-title"><?= htmlspecialchars($page['title'] ?? 'Causos de Obra') ?></div>
    <div class="stories-subtitle"><?= htmlspecialchars($page['subtitle'] ?? 'Histórias reais (ou quase) dos bastidores da construção') ?></div>
    <?php
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
<div class="page pg-back">
    <img src="<?= $magazineLogo ?>" class="logo" alt="Brooks Construtora">
    <div class="txt"><?= nl2br(htmlspecialchars($page['content'] ?? 'Construção consciente do zero ao acabamento. Comprometidos com o meio ambiente, com as pessoas e com o futuro.')) ?></div>
    <div class="bar"><span>&copy; <?= $year ?> BROOKS CONSTRUTORA. TODOS OS DIREITOS RESERVADOS.</span><span><?= $siteUrl ?></span></div>
</div>

<?php else: ?>
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <?php if($img1): ?><img src="<?= $img1 ?>" class="img-full" style="height:250px;margin-bottom:15px" alt=""><?php endif; ?>
    <?php if($page['title']): ?><div class="title-big"><?= htmlspecialchars($page['title']) ?></div><?php endif; ?>
    <?php foreach(explode("\n",$page['content']??'') as $p): if(trim($p)): ?><p class="text"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?>
</div>
<?php endif; ?>

<?php endforeach; ?>
</div><!-- .mag-preview -->

</section>

<!-- PDF Generator Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
function generatePDF() {
    var btn = document.getElementById('btn-pdf');
    btn.disabled = true;
    btn.innerHTML = '<span>Gerando PDF...</span>';
    var loading = document.getElementById('pdf-loading');
    if (loading) loading.style.display = 'block';

    var container = document.querySelector('.mag-preview');
    var pages = container.querySelectorAll('.page');
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
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="download" style="width:16px;height:16px;"></i> Baixar PDF';
        if (loading) loading.style.display = 'none';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    })();
}

// Sistema de paginação e ajuste de páginas
document.addEventListener('DOMContentLoaded', function() {
    var PAGE_HEIGHT = 842;
    var PAGE_PADDING = 60;
    var MAX_CONTENT = PAGE_HEIGHT - PAGE_PADDING;

    function processPages() {
        // No mobile, não pagina — conteúdo flui naturalmente
        if (window.innerWidth <= 620) return;
        
        var allPages = Array.from(document.querySelectorAll('.mag-preview .page'));
        
        allPages.forEach(function(page) {
            if (page.classList.contains('pg-cover') || page.classList.contains('pg-back')) {
                page.style.height = PAGE_HEIGHT + 'px';
                page.style.overflow = 'hidden';
                return;
            }

            if (page.scrollHeight <= PAGE_HEIGHT + 5) {
                page.style.height = PAGE_HEIGHT + 'px';
                page.style.overflow = 'hidden';
                return;
            }

            // Pagina TODAS as páginas internas que não cabem
            paginatePage(page);
        });

        renumberPages();
    }

    function paginatePage(page) {
        var header = page.querySelector('.hdr');
        var headerHTML = header ? header.outerHTML : '';
        var pageClass = page.className;
        var headerHeight = header ? header.offsetHeight + 20 : 0;

        var contentElements = [];
        Array.from(page.children).forEach(function(child) {
            if (!child.classList.contains('hdr')) contentElements.push(child);
        });

        var currentHeight = headerHeight;
        var splitIndex = contentElements.length;

        for (var i = 0; i < contentElements.length; i++) {
            var el = contentElements[i];
            var style = window.getComputedStyle(el);
            var elH = el.offsetHeight + parseInt(style.marginTop || 0) + parseInt(style.marginBottom || 0);

            if (currentHeight + elH > MAX_CONTENT) {
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

                    if (cloneBefore.children.length > 0) {
                        el.parentNode.insertBefore(cloneBefore, el);
                    }
                    el.parentNode.removeChild(el);
                    contentElements.splice(i, 1, cloneBefore);
                    splitIndex = i + 1;
                    contentElements.splice(splitIndex, 0, cloneAfter);
                } else {
                    splitIndex = i;
                }
                break;
            }
            currentHeight += elH;
        }

        if (splitIndex >= contentElements.length) {
            page.style.height = PAGE_HEIGHT + 'px';
            page.style.overflow = 'hidden';
            return;
        }

        var overflowEls = contentElements.slice(splitIndex);

        while (page.firstChild) page.removeChild(page.firstChild);
        if (header) page.appendChild(header);
        for (var k = 0; k < splitIndex; k++) {
            page.appendChild(contentElements[k]);
        }
        page.style.height = PAGE_HEIGHT + 'px';
        page.style.overflow = 'hidden';

        var remaining = overflowEls;
        var prevPage = page;

        while (remaining.length > 0) {
            var newPage = document.createElement('div');
            newPage.className = pageClass;
            newPage.setAttribute('data-continuation', 'true');
            newPage.innerHTML = headerHTML;
            prevPage.parentNode.insertBefore(newPage, prevPage.nextSibling);

            var newH = headerHTML ? 50 : 0;
            var fitted = 0;
            var didBreak = false;
            for (var m = 0; m < remaining.length; m++) {
                newPage.appendChild(remaining[m]);
                var mH = remaining[m].offsetHeight + 14;

                if (newH + mH > MAX_CONTENT) {
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
                        newPage.removeChild(remaining[m]);
                        remaining = remaining.slice(m);
                    } else {
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
        var allPages = Array.from(document.querySelectorAll('.mag-preview .page'));
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

    // Auto-fit: reduz fonte dos títulos até caber sem quebrar linha
    function fitTitles() {
        document.querySelectorAll('.mag-preview .pg-cover .title').forEach(function(el) {
            shrinkToFit(el, 16);
        });
        document.querySelectorAll('.mag-preview .title-big').forEach(function(el) {
            shrinkToFit(el, 12);
        });
    }
    function shrinkToFit(el, minSize) {
        var parent = el.parentElement;
        var maxW = parent.clientWidth;
        var attempts = 0;
        while (el.scrollWidth > maxW + 1 && attempts < 30) {
            var cur = parseFloat(window.getComputedStyle(el).fontSize);
            if (cur <= minSize) break;
            el.style.fontSize = (cur - 1) + 'px';
            attempts++;
        }
    }

    // Aguarda imagens
    var images = document.querySelectorAll('.mag-preview img');
    var loaded = 0;
    var total = images.length;

    function check() {
        loaded++;
        if (loaded >= total) { fitTitles(); processPages(); }
    }

    if (total === 0) {
        fitTitles();
        processPages();
    } else {
        images.forEach(function(img) {
            if (img.complete) check();
            else {
                img.addEventListener('load', check);
                img.addEventListener('error', check);
            }
        });
        setTimeout(function() { fitTitles(); processPages(); }, 3000);
    }
});
</script>

<?php include ROOT_PATH . '/app/Views/site/layouts/new-footer.php'; ?>
