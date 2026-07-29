<?php
$pageTitle = htmlspecialchars($magazine['title']);
$currentPage = 'revista';
$prefix = defined('ANTIGO_PREFIX') ? ANTIGO_PREFIX : '';
$siteUrl = 'WWW.BROOKSCONSTRUTORA.COM.BR';
$year = date('Y');
try { $magazineLogo = \App\Models\Setting::get('magazine_logo', ''); } catch (\Exception $e) { $magazineLogo = ''; }
if (empty($magazineLogo)) $magazineLogo = '/assets/images/wp/2024/11/logo-brooks-1400x396.webp';
include ROOT_PATH . '/app/Views/site/layouts/header.php';
?>

<div id="content" role="main" class="content-area">

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
<style>
.mag-preview{max-width:595px;margin:30px auto;padding:0 10px}
.mag-preview .page{background:#fff;width:100%;max-width:595px;height:842px;min-height:842px;aspect-ratio:unset;margin:0 auto 25px;position:relative;overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,0.12);page-break-before:always}
.mag-preview .pg-cover{display:flex;flex-direction:column;align-items:center;padding:0;background:#1a472a;height:842px}
.mag-preview .pg-cover .bg{position:absolute;top:0;left:0;right:0;bottom:0;object-fit:cover;width:100%;height:100%}
.mag-preview .pg-cover .overlay{position:absolute;top:0;left:0;right:0;bottom:0;background:linear-gradient(180deg,rgba(0,0,0,0.4) 0%,rgba(0,0,0,0.05) 35%,rgba(0,0,0,0.05) 50%,rgba(0,0,0,0.4) 70%,rgba(0,0,0,0.8) 90%,rgba(0,0,0,0.92) 100%)}
.mag-preview .pg-cover .content{position:relative;z-index:2;text-align:center;width:100%;height:100%;display:flex;flex-direction:column;padding:30px 40px}
.mag-preview .pg-cover .title{font-size:5rem;font-weight:900;color:#fff;text-transform:uppercase;letter-spacing:2px;margin-top:10px;text-shadow:0 2px 10px rgba(0,0,0,0.3)}
.mag-preview .pg-cover .sub-line{display:flex;align-items:center;justify-content:center;gap:15px;margin-top:5px;font-size:0.7rem;letter-spacing:5px;text-transform:uppercase;color:rgba(255,255,255,0.9)}
.mag-preview .pg-cover .sub-line .ln{width:40px;height:2px;background:#fff}
.mag-preview .pg-cover .logo{margin:auto;max-width:220px;filter:drop-shadow(0 4px 20px rgba(0,0,0,0.7))}
.mag-preview .pg-cover .topic{font-size:1.4rem;font-weight:800;color:#fff;font-style:italic;text-align:left;padding:15px 30px;margin-top:auto;margin-bottom:70px;text-shadow:0 2px 10px rgba(0,0,0,0.8)}
.mag-preview .pg-cover .foot{position:absolute;bottom:15px;left:25px;right:25px;display:flex;justify-content:space-between;font-size:0.55rem;color:rgba(255,255,255,0.8)}
.mag-preview .pg-int{padding:30px 35px;height:842px;overflow:hidden}
.mag-preview .pg-int .hdr{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px}
.mag-preview .pg-int .logo-sm{font-weight:800;font-size:0.9rem;color:#111;line-height:1}
.mag-preview .pg-int .logo-sm .ck{color:#2e7d32}
.mag-preview .pg-int .logo-sm small{display:block;font-size:0.4rem;font-weight:400;letter-spacing:2px;color:#666}
.mag-preview .pg-int .pn{font-size:1rem;font-weight:300;color:#333}
.mag-preview .img-full{width:100%;object-fit:cover}
.mag-preview .img-half{width:48%;object-fit:cover}
.mag-preview .title-big{font-family:'Montserrat',sans-serif;font-size:2.8rem;font-weight:900;color:#111;margin-bottom:15px;line-height:1.1}
.mag-preview .title-upper{font-size:0.7rem;text-transform:uppercase;letter-spacing:1.5px;font-weight:600;color:#111;margin-bottom:18px;border-bottom:1px solid #ddd;padding-bottom:10px}
.mag-preview .subtitle{font-size:1.1rem;font-weight:400;color:#333;margin-bottom:18px}
.mag-preview .text{font-size:0.78rem;line-height:1.8;color:#333;margin-bottom:12px}
.mag-preview .text-sm{font-size:0.68rem;line-height:1.7;color:#444;margin-bottom:8px}
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
@media(max-width:620px){
    .mag-preview .page{height:auto;min-height:500px}
    .mag-preview .pg-cover{height:auto}
    .mag-preview .pg-cover .title{font-size:2.5rem}
    .mag-preview .pg-cover .topic{font-size:1rem;margin-bottom:50px}
    .mag-preview .pg-cover .logo{max-width:150px}
    .mag-preview .pg-int{padding:20px 20px;height:auto;overflow:visible}
    .mag-preview .pg-back{height:auto;min-height:500px}
    .mag-preview .title-big{font-size:1.8rem}
    .mag-preview .title-upper{font-size:0.6rem}
    .mag-preview .text,.mag-preview .text-sm{font-size:0.75rem}
    .mag-preview .two-col{flex-direction:column}
    .mag-preview .img-half{width:100%}
    .mag-preview .overlay-section{height:280px}
    .mag-preview .pg-back .logo{max-width:180px}
}
</style>

<div class="mag-preview">
<?php
$intNum = 0;
foreach ($pages as $page):
    $img1 = $page['image_url'] ?? '';
    $img2 = $page['image_url_2'] ?? '';
    $showImages = ($page['show_images'] ?? '1') !== '0';
    if (!$showImages) { $img1 = ''; $img2 = ''; }
    $layout = $page['layout_type'] ?? 'internal_01';
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
        <div class="foot"><span>&copy; <?= $year ?> BROOKS CONSTRUTORA.<br>TODOS OS DIREITOS RESERVADOS.</span><span><?= $siteUrl ?></span></div>
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
        <div class="foot"><span>&copy; <?= $year ?> BROOKS CONSTRUTORA.<br>TODOS OS DIREITOS RESERVADOS.</span><span><?= $siteUrl ?></span></div>
    </div>
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
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <div style="display:flex;gap:8px;margin-bottom:8px"><?php if($img1): ?><img src="<?= $img1 ?>" style="width:50%;height:280px;object-fit:cover" alt=""><?php endif; ?><?php if($img2): ?><img src="<?= $img2 ?>" style="width:50%;height:280px;object-fit:cover" alt=""><?php endif; ?></div>
    <div style="display:flex;gap:8px"><?php $img3=$page['image_url_3']??''; if($img3): ?><img src="<?= $img3 ?>" style="width:50%;height:280px;object-fit:cover" alt=""><?php else: ?><div class="img-placeholder" style="width:50%;height:280px">IMAGEM</div><?php endif; ?><div style="width:50%;padding:15px 10px"><?php foreach(explode("\n",$page['content']??'') as $p): if(trim($p)): ?><p class="text-sm"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?></div></div>
</div>

<?php elseif ($layout === 'internal_07'): ?>
<div class="page pg-int">
    <div class="hdr"><div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div><div class="pn"><?= $displayPageNum ?></div></div>
    <div style="margin:40px 0 30px"><p style="font-size:1.8rem;font-weight:600;color:#111;line-height:1.3"><?= htmlspecialchars($page['title'] ?? '') ?></p></div>
    <div class="two-col"><div class="col"><?php if($img1): ?><img src="<?= $img1 ?>" style="width:100%;height:420px;object-fit:cover" alt=""><?php endif; ?></div><div class="col"><?php foreach(explode("\n",$page['content']??'') as $p): if(trim($p)): ?><p class="text-sm"><?= htmlspecialchars(trim($p)) ?></p><?php endif; endforeach; ?></div></div>
</div>

<?php elseif ($layout === 'backcover'): ?>
<div class="page pg-back">
    <img src="<?= $magazineLogo ?>" class="logo" alt="Brooks Construtora">
    <div class="txt"><?= nl2br(htmlspecialchars($page['content'] ?? 'Construção consciente do zero ao acabamento. Comprometidos com o meio ambiente, com as pessoas e com o futuro.')) ?></div>
    <div class="bar"><span>&copy; <?= $year ?> BROOKS CONSTRUTORA.<br>TODOS OS DIREITOS RESERVADOS.</span><span><?= $siteUrl ?></span></div>
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

<style>
#mag-actions{text-align:center;margin:30px 0 50px;display:flex;gap:12px;justify-content:center;align-items:center}
#mag-actions button,#mag-actions a{
    all:unset !important;
    padding:13px 30px !important;border-radius:3px !important;font-weight:600 !important;font-size:13px !important;
    text-decoration:none !important;display:inline-flex !important;align-items:center !important;justify-content:center !important;
    cursor:pointer !important;line-height:1 !important;text-transform:uppercase !important;letter-spacing:0.5px !important;
    height:44px !important;box-sizing:border-box !important;font-family:Inter,sans-serif !important;
}
#mag-actions button{background:#3a3b4e !important;color:#fff !important;border:2px solid #3a3b4e !important}
#mag-actions a{background:#fff !important;color:#3a3b4e !important;border:2px solid #3a3b4e !important}
#pdf-loading{display:none;text-align:center;margin:30px 0 50px;font-family:Inter,sans-serif;}
#pdf-loading .spinner{display:inline-block;width:24px;height:24px;border:3px solid #ddd;border-top-color:#3a3b4e;border-radius:50%;animation:pdfspin 0.8s linear infinite;margin-bottom:10px;}
@keyframes pdfspin{to{transform:rotate(360deg)}}
#pdf-loading p{margin:0;font-size:13px;color:#555;font-weight:500;}
</style>
<div id="mag-actions">
    <button onclick="generatePDF()" id="btn-pdf">Baixar PDF</button>
    <a href="<?= $prefix ?>/revista">Voltar</a>
</div>
<div id="pdf-loading">
    <div class="spinner"></div>
    <p>Gerando PDF, aguarde...</p>
</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
function generatePDF() {
    var btn = document.getElementById('btn-pdf');
    btn.disabled = true; btn.textContent = 'Gerando PDF...';
    var toolbar = document.getElementById('mag-actions');
    if (toolbar) toolbar.style.display = 'none';
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
        if (toolbar) toolbar.style.display = '';
        if (loading) loading.style.display = 'none';
        btn.disabled = false; btn.textContent = 'Baixar PDF';
    })();
}

// Auto-ajuste de font-size quando conteúdo não cabe na página
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.mag-preview .page').forEach(function(page) {
        var maxH = page.offsetHeight;
        var attempts = 0;
        while (page.scrollHeight > maxH + 2 && attempts < 8) {
            var currentSize = parseFloat(window.getComputedStyle(page).fontSize) || 14;
            page.style.fontSize = (currentSize - 0.5) + 'px';
            attempts++;
        }
    });
});
</script>

</div><!-- #content -->

<?php include ROOT_PATH . '/app/Views/site/layouts/footer.php'; ?>
