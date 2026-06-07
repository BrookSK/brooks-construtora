<?php
$pageTitle = htmlspecialchars($magazine['title']);
$currentPage = 'revista';
$siteUrl = 'WWW.BROOKSCONSTRUTORA.COM.BR';
$year = date('Y');
try { $magazineLogo = \App\Models\Setting::get('magazine_logo', ''); } catch (\Exception $e) { $magazineLogo = ''; }
if (empty($magazineLogo)) $magazineLogo = '/assets/images/wp/2024/11/logo-brooks-1400x396.webp';
include ROOT_PATH . '/app/Views/site/layouts/header.php';
?>

<div id="content" role="main" class="content-area">

<style>
.mag-viewer{max-width:595px;margin:30px auto}
.mag-page{background:#fff;width:100%;height:842px;margin-bottom:20px;position:relative;overflow:hidden;box-shadow:0 5px 30px rgba(0,0,0,0.08);page-break-before:always}
.mag-page.cover{background:#1a472a;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;padding:30px 40px}
.mag-page.cover .bg{position:absolute;top:0;left:0;right:0;bottom:0;object-fit:cover;width:100%;height:100%}
.mag-page.cover .ov{position:absolute;top:0;left:0;right:0;bottom:0;background:linear-gradient(180deg,rgba(26,71,42,0.7) 0%,rgba(26,71,42,0.3) 25%,rgba(0,0,0,0.1) 50%,rgba(0,0,0,0.6) 85%,rgba(0,0,0,0.85) 100%)}
.mag-page.cover .ct{position:relative;z-index:2;width:100%;height:100%;display:flex;flex-direction:column}
.mag-page.cover h1{font-size:3.5rem;font-weight:900;color:#fff;text-transform:uppercase;letter-spacing:2px;margin-top:10px}
.mag-page.cover .sl{display:flex;align-items:center;justify-content:center;gap:12px;font-size:0.65rem;letter-spacing:4px;text-transform:uppercase;color:rgba(255,255,255,0.9);margin-top:5px}
.mag-page.cover .sl .ln{width:35px;height:2px;background:#fff}
.mag-page.cover .lg{margin:auto;max-width:200px}
.mag-page.cover .tp{font-size:1.1rem;font-weight:800;color:#fff;font-style:italic;text-align:left;margin-top:auto;margin-bottom:60px}
.mag-page.cover .ft{position:absolute;bottom:15px;left:25px;right:25px;display:flex;justify-content:space-between;font-size:0.5rem;color:rgba(255,255,255,0.8)}
.mag-page.internal{padding:35px 40px;height:842px;overflow:hidden}
.mag-page.internal .hdr{display:flex;justify-content:space-between;margin-bottom:20px}
.mag-page.internal .lsm{font-weight:800;font-size:0.9rem;color:#111;line-height:1}
.mag-page.internal .lsm .ck{color:#2e7d32}
.mag-page.internal .lsm small{display:block;font-size:0.4rem;font-weight:400;letter-spacing:2px;color:#666}
.mag-page.internal .pn{font-size:1rem;font-weight:300;color:#333}
.mag-page.internal .pi{width:100%;max-height:300px;object-fit:cover;margin-bottom:20px}
.mag-page.internal .pt{font-family:'Playfair Display',Georgia,serif;font-size:2rem;font-weight:900;color:#111;margin-bottom:12px;line-height:1.1}
.mag-page.internal .ps{font-size:0.9rem;color:#333;margin-bottom:12px}
.mag-page.internal .pp{font-size:0.75rem;line-height:1.7;color:#333;margin-bottom:10px}
.mag-page.backcover{background:#1a3a2a;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center}
.mag-page.backcover .lg{max-width:250px;margin-bottom:30px}
.mag-page.backcover .tx{color:rgba(255,255,255,0.85);font-size:0.95rem;max-width:380px;line-height:1.6}
.mag-page.backcover .bf{position:absolute;bottom:0;left:0;right:0;background:#e53935;padding:12px 25px;display:flex;justify-content:space-between;font-size:0.6rem;color:#fff}
</style>

<div class="mag-viewer">
<?php
$intNum = 0;
foreach ($pages as $page):
    $layout = $page['layout_type'] ?? 'internal_01';
    if (!in_array($layout, ['cover','subcover','backcover'])) $intNum++;
    $pn = str_pad($intNum, 2, '0', STR_PAD_LEFT);
?>

<?php if ($layout === 'cover'): ?>
<div class="mag-page cover">
    <?php if ($magazine['cover_image']): ?><img src="<?= $magazine['cover_image'] ?>" class="bg" alt=""><?php endif; ?>
    <div class="ov"></div>
    <div class="ct">
        <h1><?= htmlspecialchars($magazine['title']) ?></h1>
        <div class="sl"><span>CONSTRUÇÃO</span><span class="ln"></span><span>SUSTENTÁVEL</span></div>
        <img src="<?= $magazineLogo ?>" class="lg" alt="Brooks">
        <?php if ($magazine['subtitle']): ?><div class="tp"><?= htmlspecialchars($magazine['subtitle']) ?></div><?php endif; ?>
        <div class="ft">
            <span>&copy; <?= $year ?> BROOKS CONSTRUTORA. TODOS OS DIREITOS RESERVADOS.</span>
            <span><?= $siteUrl ?></span>
        </div>
    </div>
</div>

<?php elseif ($layout === 'subcover'): ?>
<div class="mag-page cover">
    <?php if ($magazine['cover_image']): ?><img src="<?= $magazine['cover_image'] ?>" class="bg" alt=""><?php endif; ?>
    <div class="ov"></div>
    <div class="ct">
        <div style="display:flex;align-items:center;gap:10px;justify-content:center;margin-top:15px;">
            <span style="font-size:2.8rem;font-weight:900;color:#fff;"><?= htmlspecialchars($page['title'] ?? 'ECO') ?></span>
            <img src="<?= $magazineLogo ?>" style="max-width:160px" alt="Brooks">
        </div>
        <div class="sl" style="margin-top:8px"><span>CONSTRUÇÃO</span><span class="ln"></span><span>CONSCIENTE</span></div>
        <div style="flex:1"></div>
        <?php if ($magazine['subtitle']): ?><div class="tp"><?= htmlspecialchars($magazine['subtitle']) ?></div><?php endif; ?>
        <div class="ft">
            <span>&copy; <?= $year ?> BROOKS CONSTRUTORA. TODOS OS DIREITOS RESERVADOS.</span>
            <span><?= $siteUrl ?></span>
        </div>
    </div>
</div>

<?php elseif ($layout === 'backcover'): ?>
<div class="mag-page backcover">
    <img src="<?= $magazineLogo ?>" class="lg" alt="Brooks Construtora">
    <div class="tx"><?= nl2br(htmlspecialchars($page['content'] ?? 'Construção consciente do zero ao acabamento. Comprometidos com o meio ambiente, com as pessoas e com o futuro.')) ?></div>
    <div class="bf">
        <span>&copy; <?= $year ?> BROOKS CONSTRUTORA. TODOS OS DIREITOS RESERVADOS.</span>
        <span><?= $siteUrl ?></span>
    </div>
</div>

<?php else: ?>
<div class="mag-page internal">
    <div class="hdr">
        <div class="lsm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div>
        <div class="pn"><?= $pn ?></div>
    </div>
    <?php if ($page['image_url']): ?><img src="<?= $page['image_url'] ?>" class="pi" alt=""><?php endif; ?>
    <?php if ($page['title']): ?><h2 class="pt"><?= htmlspecialchars($page['title']) ?></h2><?php endif; ?>
    <?php if ($page['subtitle'] ?? ''): ?><p class="ps"><?= htmlspecialchars($page['subtitle']) ?></p><?php endif; ?>
    <?php if ($page['content']): ?>
        <?php foreach (explode("\n", $page['content']) as $p): ?>
            <?php if (trim($p) && trim($p) !== '|||'): ?><p class="pp"><?= htmlspecialchars(trim($p)) ?></p><?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if ($page['image_url_2'] ?? ''): ?><img src="<?= $page['image_url_2'] ?>" style="width:100%;max-height:200px;object-fit:cover;margin-top:15px;" alt=""><?php endif; ?>
</div>
<?php endif; ?>

<?php endforeach; ?>

    <div style="text-align:center; margin: 30px 0 50px; display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
        <button onclick="generatePDF()" id="btn-pdf" class="button secondary" style="padding: 10px 25px; cursor:pointer; background:#3a3b4e; color:#fff; border:none; font-weight:700; text-transform:uppercase; font-size:0.85rem;">Baixar PDF</button>
        <a href="/revista" class="button secondary" style="padding: 10px 25px; background:transparent; border:1px solid #3a3b4e; color:#3a3b4e; font-weight:700; text-transform:uppercase; font-size:0.85rem; text-decoration:none;">Voltar</a>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js"></script>
<script>
function generatePDF() {
    var btn = document.getElementById('btn-pdf');
    btn.disabled = true; btn.textContent = 'Gerando PDF...';
    var element = document.querySelector('.mag-viewer');
    html2pdf().set({
        margin: 0,
        filename: '<?= preg_replace('/[^a-zA-Z0-9_-]/', '_', $magazine['title']) ?>_Brooks.pdf',
        image: { type: 'jpeg', quality: 0.92 },
        html2canvas: { scale: 2, useCORS: true, scrollY: 0, windowWidth: 595 },
        jsPDF: { unit: 'px', format: [595, 842], orientation: 'portrait', hotfixes: ['px_scaling'] },
        pagebreak: { mode: ['css','legacy'], before: '.mag-page' }
    }).from(element).save().then(function(){ btn.disabled=false; btn.textContent='Baixar PDF'; });
}
</script>

</div><!-- #content -->

<?php include ROOT_PATH . '/app/Views/site/layouts/footer.php'; ?>
