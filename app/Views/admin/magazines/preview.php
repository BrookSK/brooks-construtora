<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview - <?= htmlspecialchars($magazine['title']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #333; }
        .magazine-preview { max-width: 800px; margin: 0 auto; }
        .page {
            background: #fff;
            width: 100%;
            min-height: 1000px;
            margin: 20px auto;
            padding: 60px;
            position: relative;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        .page-number {
            position: absolute;
            top: 40px;
            right: 60px;
            font-size: 1.5rem;
            font-weight: 300;
            color: #333;
        }
        .logo {
            position: absolute;
            top: 30px;
            left: 60px;
            font-weight: 700;
            font-size: 1.2rem;
        }
        .logo span { color: #4CAF50; }

        /* Cover */
        .page-cover {
            background: linear-gradient(135deg, #1a472a 0%, #2d6b40 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .page-cover h1 { font-size: 3.5rem; font-weight: 900; margin-bottom: 1rem; }
        .page-cover h2 { font-size: 1.5rem; font-weight: 300; opacity: 0.9; }
        .page-cover .brand { position: absolute; top: 50px; }
        .page-cover .footer-info {
            position: absolute;
            bottom: 40px;
            font-size: 0.8rem;
            opacity: 0.7;
        }
        .page-cover img.cover-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            top: 0;
            left: 0;
        }
        .page-cover .overlay {
            position: relative;
            z-index: 2;
            background: rgba(26, 71, 42, 0.85);
            padding: 40px;
            border-radius: 10px;
        }

        /* Content pages */
        .page-content h2 {
            font-size: 2rem;
            font-weight: 800;
            color: #1a472a;
            margin: 2rem 0 1rem;
            font-style: italic;
        }
        .page-content p {
            font-size: 0.95rem;
            line-height: 1.8;
            color: #333;
            margin-bottom: 1rem;
            text-align: justify;
        }
        .page-content .page-image {
            width: 100%;
            max-height: 350px;
            object-fit: cover;
            border-radius: 8px;
            margin: 1.5rem 0;
        }
        .page-content .highlight {
            font-size: 1.1rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #1a472a;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }

        /* Backcover */
        .page-backcover {
            background: #1a472a;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .page-backcover h2 { font-size: 2rem; margin-bottom: 2rem; }
        .page-backcover p { font-size: 1.1rem; opacity: 0.9; max-width: 500px; }
        .page-backcover .footer-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: #e53935;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="magazine-preview">
        <?php foreach ($pages as $index => $page): ?>
            <?php if ($page['layout_type'] === 'cover'): ?>
                <div class="page page-cover">
                    <?php if ($magazine['cover_image']): ?>
                        <img src="<?= $magazine['cover_image'] ?>" alt="Capa" class="cover-image">
                    <?php endif; ?>
                    <div class="overlay">
                        <h1><?= htmlspecialchars($magazine['title']) ?></h1>
                        <h2><?= htmlspecialchars($magazine['subtitle'] ?? '') ?></h2>
                    </div>
                    <div class="footer-info">
                        &copy; <?= date('Y') ?> BROOKS CONSTRUTORA. TODOS OS DIREITOS RESERVADOS. &nbsp; | &nbsp; WWW.BROOKSCONSTRUTORA.COM.BR
                    </div>
                </div>

            <?php elseif ($page['layout_type'] === 'backcover'): ?>
                <div class="page page-backcover">
                    <h2>BROOKS CONSTRUTORA</h2>
                    <p><?= nl2br(htmlspecialchars($page['content'] ?? 'Construção consciente do zero ao acabamento. Comprometidos com o meio ambiente, com as pessoas e com o futuro.')) ?></p>
                    <div class="footer-bar">
                        <span>&copy; <?= date('Y') ?> BROOKS CONSTRUTORA. TODOS OS DIREITOS RESERVADOS.</span>
                        <span>WWW.BROOKSCONSTRUTORA.COM.BR</span>
                    </div>
                </div>

            <?php else: ?>
                <div class="page page-content">
                    <div class="logo">BROO<span>K</span>S<br><small style="font-weight:400;font-size:0.6rem;">CONSTRUTORA</small></div>
                    <span class="page-number"><?= str_pad($page['page_number'], 2, '0', STR_PAD_LEFT) ?></span>

                    <?php if ($page['image_url'] && in_array($page['layout_type'], ['full_image', 'text_image'])): ?>
                        <img src="<?= $page['image_url'] ?>" alt="" class="page-image" style="margin-top: 3rem;">
                    <?php endif; ?>

                    <?php if ($page['title']): ?>
                        <h2><?= htmlspecialchars($page['title']) ?></h2>
                    <?php endif; ?>

                    <?php if ($page['content']): ?>
                        <?php foreach (explode("\n", $page['content']) as $paragraph): ?>
                            <?php if (trim($paragraph)): ?>
                                <p><?= htmlspecialchars(trim($paragraph)) ?></p>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if ($page['image_url'] && $page['layout_type'] === 'image_text'): ?>
                        <img src="<?= $page['image_url'] ?>" alt="" class="page-image">
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</body>
</html>
