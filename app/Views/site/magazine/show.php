<?php
$pageTitle = htmlspecialchars($magazine['title']);
$currentPage = 'revista';
$siteUrl = 'WWW.BROOKSCONSTRUTORA.COM.BR';
$year = date('Y');
// Logo da revista
try {
    $magazineLogo = \App\Models\Setting::get('magazine_logo', '');
} catch (\Exception $e) {
    $magazineLogo = '';
}
if (empty($magazineLogo)) {
    $magazineLogo = '<?= `$magazineLogo ?>';
}
include ROOT_PATH . '/app/Views/site/layouts/header.php';
?>

<div id="content" role="main" class="content-area">

<style>
    .mag-viewer { max-width: 794px; margin: 30px auto; }
    .mag-page {
        background: #fff;
        width: 100%;
        min-height: 900px;
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 5px 30px rgba(0,0,0,0.08);
    }
    /* Capa */
    .mag-page.cover {
        background: #1a472a;
        display: flex; flex-direction: column;
        justify-content: center; align-items: center;
        text-align: center; padding: 60px;
    }
    .mag-page.cover .cover-img {
        position: absolute; top:0; left:0; right:0; bottom:0;
        object-fit: cover; width:100%; height:100%;
    }
    .mag-page.cover .overlay { position:absolute; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.3); }
    .mag-page.cover .content { position: relative; z-index:2; }
    .mag-page.cover h1 { font-size: 3.5rem; font-weight: 900; color:#fff; text-transform:uppercase; letter-spacing:2px; margin-bottom:10px; }
    .mag-page.cover .subtitle-line { display:flex; align-items:center; justify-content:center; gap:15px; font-size:0.8rem; letter-spacing:4px; text-transform:uppercase; color:#fff; margin-bottom:40px; }
    .mag-page.cover .subtitle-line .ln { width:40px; height:2px; background:#fff; }
    .mag-page.cover .logo-img { max-width: 250px; margin: 30px 0; }
    .mag-page.cover .topic { font-size:1.3rem; font-weight:700; color:#fff; margin-top:30px; }
    .mag-page.cover .foot { position:absolute; bottom:25px; left:0; right:0; display:flex; justify-content:space-between; padding:0 35px; font-size:0.65rem; color:rgba(255,255,255,0.6); }
    /* Interna */
    .mag-page.internal { padding: 40px 50px; }
    .mag-page.internal .hdr { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:30px; }
    .mag-page.internal .logo-sm { font-weight:800; font-size:1rem; color:#1a1a1a; line-height:1; }
    .mag-page.internal .logo-sm .ck { color:#2e7d32; }
    .mag-page.internal .logo-sm small { display:block; font-size:0.45rem; font-weight:400; letter-spacing:2px; text-transform:uppercase; color:#666; }
    .mag-page.internal .pnum { font-size:1.2rem; font-weight:300; color:#333; }
    .mag-page.internal .pimg { width:100%; border-radius:4px; margin-bottom:25px; max-height:350px; object-fit:cover; }
    .mag-page.internal .ptitle { font-family: Georgia, serif; font-size:1.8rem; font-weight:700; font-style:italic; color:#1a472a; margin-bottom:15px; }
    .mag-page.internal .ptext { font-size:0.88rem; line-height:1.8; color:#444; text-align:justify; margin-bottom:12px; }
    /* Contracapa */
    .mag-page.backcover {
        background: #1a472a;
        display:flex; flex-direction:column;
        justify-content:center; align-items:center; text-align:center;
    }
    .mag-page.backcover .logo-img { max-width:280px; margin-bottom:30px; }
    .mag-page.backcover .txt { color:rgba(255,255,255,0.9); font-size:1rem; max-width:450px; line-height:1.6; }
    .mag-page.backcover .bfoot { position:absolute; bottom:0; left:0; right:0; background:#e53935; padding:15px 35px; display:flex; justify-content:space-between; font-size:0.7rem; color:#fff; }
</style>

<div class="mag-viewer">

    <?php foreach ($pages as $page): ?>

        <?php if ($page['layout_type'] === 'cover'): ?>
        <div class="mag-page cover">
            <?php if ($magazine['cover_image']): ?>
                <img src="<?= $magazine['cover_image'] ?>" class="cover-img" alt="">
                <div class="overlay"></div>
            <?php endif; ?>
            <div class="content">
                <h1><?= htmlspecialchars($magazine['title']) ?></h1>
                <div class="subtitle-line"><span>CONSTRUÃ‡ÃƒO</span><span class="ln"></span><span>SUSTENTÃVEL</span></div>
                <img src="<?= `$magazineLogo ?>" class="logo-img" alt="Brooks">
                <?php if ($magazine['subtitle']): ?>
                    <div class="topic"><?= htmlspecialchars($magazine['subtitle']) ?></div>
                <?php endif; ?>
            </div>
            <div class="foot">
                <span>&copy; <?= $year ?> BROOKS CONSTRUTORA. TODOS OS DIREITOS RESERVADOS.</span>
                <span><?= $siteUrl ?></span>
            </div>
        </div>

        <?php elseif ($page['layout_type'] === 'backcover'): ?>
        <div class="mag-page backcover">
            <img src="<?= `$magazineLogo ?>" class="logo-img" alt="Brooks">
            <div class="txt"><?= nl2br(htmlspecialchars($page['content'] ?? 'ConstruÃ§Ã£o consciente do zero ao acabamento. Comprometidos com o meio ambiente, com as pessoas e com o futuro.')) ?></div>
            <div class="bfoot">
                <span>&copy; <?= $year ?> BROOKS CONSTRUTORA. TODOS OS DIREITOS RESERVADOS.</span>
                <span><?= $siteUrl ?></span>
            </div>
        </div>

        <?php else: ?>
        <div class="mag-page internal">
            <div class="hdr">
                <div class="logo-sm">BROO<span class="ck">K</span>S<small>CONSTRUTORA</small></div>
                <div class="pnum"><?= str_pad($page['page_number'], 2, '0', STR_PAD_LEFT) ?></div>
            </div>
            <?php if ($page['image_url']): ?>
                <img src="<?= $page['image_url'] ?>" class="pimg" alt="">
            <?php endif; ?>
            <?php if ($page['title']): ?>
                <h2 class="ptitle"><?= htmlspecialchars($page['title']) ?></h2>
            <?php endif; ?>
            <?php if ($page['content']): ?>
                <?php foreach (explode("\n", $page['content']) as $p): ?>
                    <?php if (trim($p)): ?>
                        <p class="ptext"><?= htmlspecialchars(trim($p)) ?></p>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    <?php endforeach; ?>

    <div style="text-align:center; margin: 30px 0 50px;">
        <a href="/revista" class="button secondary" style="padding: 10px 25px;">â† Voltar para Revistas</a>
    </div>
</div>

</div><!-- #content -->

<?php include ROOT_PATH . '/app/Views/site/layouts/footer.php'; ?>

